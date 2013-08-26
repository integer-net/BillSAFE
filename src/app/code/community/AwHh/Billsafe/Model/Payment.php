<?php

class AwHh_Billsafe_Model_Payment extends Mage_Payment_Model_Method_Abstract {

    const REGISTRY_ORDER_SHOULD_BE_CANCELLED = 'orderShouldBeCancelled';
    const CODE = 'billsafe';
    const BILLSAFE_STATUS_ACTIVE = 'active';
    const BILLSAFE_STATUS_CANCELLED = 'cancelled';

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_canFetchTransactionInfo = false;
    private $_config = null;
    private $_token = null;
    protected $_infoBlockType = 'billsafe/info';
    protected $_formBlockType = 'billsafe/form';
    private $bundles_allowed = FALSE;

    /**
     * Setter for config in for unit testing 
     * 
     * @param AwHh_Billsafe_Model_Config $config 
     * @return AwHh_Billsafe_Model_Payment
     */
    public function setConfig($config) {
        $this->_config = $config;
        return $this;
    }

    /**
     * Getter for config model 
     * 
     * @return AwHh_Billsafe_Model_Config
     */
    public function getConfig() {
        if ($this->_config) {
            return $this->_config;
        }
        $this->_config = Mage::getSingleton('billsafe/config');
        return $this->_config;
    }

    /**
     * Check if payment method is available for current order
     * 
     * @param NULL|Mage_Sales_Model_Quote $quote 
     * @return boolean
     */
    public function isAvailable($quote = null) {
        $this->setHasError(false);

        if (false == parent::isAvailable($quote)) {
            $this->setHasError(true);
            $this->setError(Mage::helper("billsafe")->__("BillSAFE is not available"));
            return true;
        }

        if ($quote) {
            // virtual products allowed, bundles just if $this->bundles_allowed == TRUE
            foreach ($quote->getAllItems() as $item) {
                switch ($item->getProduct()->getTypeId()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                        if ($this->bundles_allowed == FALSE) {
                            $this->setHasError(true);
                            $this->setError(Mage::helper("billsafe")->__("Bundled Products are not allowed"));
                            return true;
                        }
                }
            }

            // total amount must fit between min and max amount
            $total = (float) $quote->getGrandTotal();
            $minAmount = (float) Mage::getStoreConfig('payment/billsafe/min_amount');
            $maxAmount = (float) Mage::getStoreConfig('payment/billsafe/max_amount');

            if ($total < $minAmount || $total > $maxAmount) {
                $this->setHasError(true);
                $this->setError(Mage::helper("billsafe")->__("Cart value too less or too high"));
                return true;
            }

            //Check Min and Fax of Fee
            if (Mage::getModel('billsafe/config')->isPaymentFeeEnabled()) {
                $feeProduct = Mage::helper('paymentfee')->getUpdatedFeeProduct();
                $avoidOverMax = (bool) Mage::getStoreConfig('payment/billsafe/disable_after_exceeding_max_fee_amount');
                $avoidBelowMin = (bool) Mage::getStoreConfig('payment/billsafe/disable_after_exceeding_min_fee_amount');

                if ($avoidOverMax && $feeProduct->getExceedsMaxAmount()) {
                    $this->setHasError(true);
                    $this->setError(Mage::helper("billsafe")->__("Payment fee is too high"));
                    return true;
                }

                if ($avoidBelowMin && $feeProduct->getExceedsMinAmount()) {
                    $this->setHasError(true);
                    $this->setError(Mage::helper("billsafe")->__("Payment fee is too low"));
                    return true;
                }
                $total += $feeProduct->getPrice();
            }

            // Check for OneStepCheckoutcs 
            if ($quote->getBaseGrandTotal() == 0) {
                $this->setHasError(true);
                $this->setError(Mage::helper("billsafe")->__("Cart value can not be zero"));
                return true;
            } else {
                // JSON-Request
                $client = Mage::getSingleton('billsafe/client');
                $client->prevalidateOrderByQuote($quote);

                $response = $client->getResponse();

                $isAvailable = $response->invoice->isAvailable;

                if (!$isAvailable) {
                    $message = $response->invoice->message;

                    if (strpos($message, "Rechnungs-") > -1 && strpos($message, "Lieferanschrift") > -1) {
                        $this->setHasError(true);
                        $this->setError($message);
                    } else {
                        $this->setHasError(true);
                        $this->setError(Mage::helper("billsafe")->__("BillSAFE is not available"));
                    }
                }

                return true;
            }
        }

        return true;
    }

    /**
     * Serialize address data
     * 
     * @param Mage_Sales_Model_Quote_Address $address 
     * @return array
     */
    private function serializeAddress(Mage_Sales_Model_Quote_Address $address) {
        $data = serialize(
                array(
                    'firstname' => $address->getFirstname(),
                    'lastname' => $address->getLastname(),
                    'company' => $address->getCompany(),
                    'street' => $address->getStreet(),
                    'city' => $address->getCity(),
                    'postcode' => $address->getPostcode(),
                )
        );

        return $data;
    }

    /**
     * Returns code
     * 
     * @return string
     */
    public function getCode() {
        return self::CODE;
    }

    /**
     * Return client
     * 
     * @return AwHh_Billsafe_Model_Client
     */
    public function getClient() {
        return Mage::getSingleton('billsafe/client');
    }

    /**
     * Returns URL to redirect to billsafe after finish checkout
     * 
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        return sprintf('%s?token=%s', $this->getClient()->getConfig()->getGatewayUrl(), Mage::registry(AwHh_Billsafe_Model_Config::TOKEN_REGISTRY_KEY)
        );
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

    public function getHelper() {
        return Mage::helper('billsafe');
    }

    /**
     * Capture
     * 
     * @param Varien_Object $payment 
     * @param float $amount 
     * @return AwHh_Billsafe_Model_Payment
     */
    public function capture(Varien_Object $payment, $amount) {
        if (!$this->areAllCaptureItemsSent($payment)) {
            throw new Mage_Core_Exception($this->getHelper()->__('Count of items to invoice does not match sent items count. Please change to items to be invoiced to match shipped items!'));
        }
        parent::capture($payment, $amount);
        if ($this->areThereUninvoicedItems($payment)) {
            Mage::register(self::REGISTRY_ORDER_SHOULD_BE_CANCELLED, true);
        }
        return $this;
    }

    /**
     * Checks if all items are shipped, which should be captured. 
     * 
     * @param Mage_Sales_Model_Order_Payment $payment 
     * @return boolean
     */
    protected function areAllCaptureItemsSent($payment) {
        if ($this->getHelper()->isDoShipment() || $this->getHelper()->isCaptureOffline()) {
            return true;
        }
        $invoice = Mage::registry('current_invoice');
        foreach ($invoice->getAllItems() as $item) {
            if ($item->getOrderItem()->getParentItemId()) {
                continue;
            }
            if (Mage::helper('paymentfee')->isFeeProduct($item)) {
                continue;
            }

            $orderItem = $item->getOrderItem();
            if ((float) $orderItem->getQtyShipped() != (float) $orderItem->getQtyInvoiced()) {
                return false;
            }
        }
        return true;
    }

    protected function areThereUninvoicedItems($payment) {
        foreach ($payment->getOrder()->getAllItems() as $item) {
            if ((float) $item->getQtyOrdered() > (float) $item->getQtyInvoiced()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Capture should only be possible on creation of first invoice.
     * 
     * @return boolean
     */
    public function canCapture() {
        $invoice = Mage::registry('current_invoice');
        if (!$invoice) {
            return true;
        }
        return $invoice->getOrder()->getInvoiceCollection()->count() <= 1;
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

    public function canUseInternal() {
        return $this->_canUseInternal;
    }

    public function getMethodTitleIfError() {
        return Mage::helper("billsafe")->__("BillSAFE is not available: ") . "<br>" . Mage::helper("billsafe")->__($this->getError());
    }

}

