<?php

class AwHh_Billsafe_Model_Installment extends Mage_Payment_Model_Method_Abstract {

    const CODE = 'billsafe_installment';

    protected $_infoBlockType = 'billsafe/info';
    protected $_formBlockType = 'billsafe/form';
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;

    /**
     * Check whether payment method can be used
     *
     * TODO: payment method instance is not supposed to know about quote
     *
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null) {
        if (Mage::getStoreConfig('payment/' . $this->getCode() . '/active') == true) {
            // JSON-Request   
            $client = Mage::getSingleton('billsafe/client');

            // Check for OneStepCheckout
            if ($quote->getBaseGrandTotal() == 0) {
                return false;
            } else {
                $client->prevalidateOrderByQuote($quote);

                $response = $client->getResponse();

                $this->setHpIsAvailable($response->hirePurchase->isAvailable);

                if ($this->getHpIsAvailable()) {
                    $this->setHpAnnualPercentage($response->hirePurchase->annualPercentageRate);
                    $this->setHpCurrencyCode($response->hirePurchase->currencyCode);
                    $this->setHpInstallmentAmount($response->hirePurchase->installmentAmount);
                    $this->setHpInstallCount($response->hirePurchase->installmentCount);

                    $this->setHpMessage($response->hirePurchase->message);
                    $this->setHpProcessingFee($response->hirePurchase->processingFee);
                    $this->setHasError(false);
                } else {
                    $this->setHpMessage($response->hirePurchase->message);
                    $this->setHasError(true);
                }

                // Always return true - then on the "payment method page" output the message if it is available or not
                return true;
            }
        } else {
            $this->setHpMessage(Mage::helper("billsafe")->__("BillSAFE Installment is not available"));
            $this->setHasError(true);
            $this->setHpIsAvailable(false);

            // Always return true - then on the "payment method page" output the message if it is available or not
            return false;
        }
    }

    /**
     * authorize 
     * 
     * @param Varien_Object $payment 
     * @param float $amount 
     * @return void
     */
    public function authorize(Varien_Object $payment, $amount) {

        $client = $this->getClient();
        $token = null;

        if (Mage::helper("billsafe")->openInLayer()) {
            $token = Mage::app()->getRequest()->getParam("token");
        }

        if ($session_token = Mage::getSingleton("core/session")->getBillsafeBackendToken()) {
            $token = $session_token;
            Mage::getSingleton("core/session")->setBillsafeBackendToken(null);
        }

        if (!$token) {
            parent::authorize($payment, $amount);
            $order = $this->getInfoInstance()->getOrder();

            $client->prevalidateOrder($order);
            $response = $client->getResponse();

            if (empty($response->invoice->message) === false) {
                throw new Mage_Checkout_Exception($this->getHelper()->__($response->invoice->message));
            } elseif ($response->invoice->isAvailable != true) {
                throw new Mage_Checkout_Exception($this->getHelper()->__('An error occured during payment process. Please try another payment method'));
            }

            $client->prepareOrder($order);

            $response = $client->getResponse();
            $token = $client->getResponseToken();
        }


        if (!$token) {
            if (isset($response->errorList->message) === true) {
                throw new Mage_Checkout_Exception($this->getHelper()->__($response->errorList->message));
            } else {
                throw new Mage_Checkout_Exception($this->getHelper()->__('An error occured during payment process. Please try another payment method'));
            }
        }

        Mage::register(AwHh_Billsafe_Model_Config::TOKEN_REGISTRY_KEY, $token);
        $payment
                ->setIsTransactionPending(true)
                ->setIsTransactionClosed(false)
                ->setPreparedMessage($this->getHelper()->__('Payment registered at Billsafe'))
                ->setTransactionId($token);
    }

    public function getCode() {
        return "billsafe_installment";
    }

    protected function getHelper() {
        return Mage::helper('billsafe');
    }

    public function getBillsafeText() {
        $text = "";

        $text .= "Kaufen Sie bequem und schnell in " . $this->getHpInstallCount() . " Raten ab " . $this->getHpInstallmentAmount() . $this->getHpCurrencyCode() . "/Monat";

        return $text;
    }

    public function getMethodTitle() {
        return "Ratenkauf";
        #return sprintf('<img src="%s"/>', Mage::getStoreConfig('payment/billsafe_installment/billsafe_logo'));
    }
    
    /**
     * refund 
     * 
     * @param Varien_Object $payment 
     * @param float $amount 
     * @return void
     */
    public function refund(Varien_Object $payment, $amount) {
        $this->getClient()->updateArticleList($payment->getOrder(), AwHh_Billsafe_Model_Client::TYPE_RF);
        return parent::refund($payment, $amount);
    }
    
    public function canRefund() {
        return $this->_canRefund;
    }
    
    /**
     * Cancel payment at billsafe.
     * 
     * @param Varien_Object $payment 
     * @return AwHh_Billsafe_Model_Payment
     */
    public function cancel(Varien_Object $payment) {        
        return Mage::helper("billsafe")->cancelBillsafeTransaction($payment, $payment->getOrder());
    }
}