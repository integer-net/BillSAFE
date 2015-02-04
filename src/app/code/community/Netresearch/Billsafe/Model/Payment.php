<?php

class Netresearch_Billsafe_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
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
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_canFetchTransactionInfo = false;
    private $_config = null;
    protected $_infoBlockType = 'billsafe/info';
    protected $_formBlockType = 'billsafe/payment_form';
    protected $_availableCheck = true;
    protected $_unavailableMessage = '';
    protected $_orderHelper;
    protected $_dataHelper;

    /**
     * Setter for config in for unit testing
     *
     * @param Netresearch_Billsafe_Model_Config $config
     *
     * @return Netresearch_Billsafe_Model_Payment
     */
    public function setConfig($config)
    {
        $this->_config = $config;

        return $this;
    }

    /**
     * Getter for config model
     *
     * @return Netresearch_Billsafe_Model_Config
     */
    public function getConfig()
    {
        if ($this->_config) {
            return $this->_config;
        }
        $this->_config = Mage::getSingleton('billsafe/config');

        return $this->_config;
    }

    /**
     * @param null $dataHelper
     */
    public function setDataHelper(Netresearch_Billsafe_Helper_Data $dataHelper)
    {
        $this->_dataHelper = $dataHelper;
    }

    /**
     * @return Netresearch_Billsafe_Helper_Data
     */
    public function getDataHelper()
    {
        if (null == $this->_dataHelper) {
            $this->_dataHelper = Mage::helper('billsafe/data');
        }

        return $this->_dataHelper;
    }

    /**
     * @param Netresearch_Billsafe_Helper_Order $orderHelper
     */
    public function setOrderHelper(Netresearch_Billsafe_Helper_Order $orderHelper)
    {
        $this->_orderHelper = $orderHelper;
    }

    /**
     * @return Netresearch_Billsafe_Helper_Order
     */
    public function getOrderHelper()
    {
        if (null == $this->_orderHelper) {
            $this->_orderHelper = Mage::helper('billsafe/order');
        }

        return $this->_orderHelper;
    }


    /**
     * Check if payment method is available for current order
     *
     * @param NULL|Mage_Sales_Model_Quote $quote
     *
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        if ($this->getOrderHelper()->isBillsafeOnsiteCheckout($quote)
            && true === Mage::getSingleton('customer/session')->getData('authorize_failed')
            && $quote instanceof Mage_Sales_Model_Quote
            && $this->getOrderHelper()->generateAddressHash($quote->getBillingAddress())
            == Mage::getSingleton('customer/session')->getData('billsafe_billingAddrHash')
        ) {
            return false;
        }

        if (false == parent::isAvailable($quote)) {
            return false;
        }

        // disable payment method if all quote items are virtuel
        if (true == $this->checkIfAllItemsAreVirtual($quote)) {
            return false;
        }

        if ($quote) {
            // total amount must fit between min and max amount
            $total     = (float)$quote->getGrandTotal();
            $minAmount = $this->getConfig()->getBillSafeMinAmount($quote->getStoreId());
            $maxAmount = $this->getConfig()->getBillSafeMaxAmount($quote->getStoreId());
            if ($total < $minAmount || $total > $maxAmount) {
                return false;
            }
            // Check Min and Fax of Fee
            if ($this->getConfig()->isPaymentFeeEnabled($quote->getStoreId())) {
                $feeProduct    = Mage::helper('paymentfee/data')->getUpdatedFeeProduct();
                $avoidOverMax  = $this->getConfig()->isBillsafeExeedingMaxFeeAmount($quote->getStoreId());
                $avoidBelowMin = $this->getConfig()->isBillsafeExeedingMinFeeAmount($quote->getStoreId());

                if ($avoidOverMax && $feeProduct->getExceedsMaxAmount()) {
                    return false;
                }

                if ($avoidBelowMin && $feeProduct->getExceedsMinAmount()) {
                    return false;
                }
                $total += $feeProduct->getPrice();
            }
            // Order without tax must not be possible
            $totals = $quote->getTotals();
            if (!isset($totals['tax']) || $totals['tax']->getValue() <= 0) {
                return false;
            }

            // shipping address must equal billing address
            $shippingAddress = $quote->getShippingAddress();
            if (false == $shippingAddress->getSameAsBilling()) {
                $shippingData = $this->serializeAddress($shippingAddress);
                $billingData  = $this->serializeAddress($quote->getBillingAddress());

                if (0 != strcmp($shippingData, $billingData)) {
                    return false;
                }
            }
            /*
             * in cases the quote couldn't be handled by BillSAFE isAvailable return true in order to display a
             * detailed message to the customer, technical errors leads to unavailbilty of BillSAFE payment method
             */
            try {
                $this->prevalidateOrder($quote);
            } catch (Mage_Core_Exception $e) {
                return false;
            }
        }

        return true;
    }


    /**
     * check if all quote items are virtual
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return boolean
     */
    protected function checkIfAllItemsAreVirtual($quote)
    {
        $result = false;
        if ($quote) {
            $virtualItemCounter = null;
            foreach ($quote->getAllItems() as $item) {
                if ($item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL
                    || $item->getProduct()->getTypeId() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE
                ) {
                    ++$virtualItemCounter;

                }
            }
            if ($virtualItemCounter == count($quote->getAllItems())) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * executes prevalidateOrder call and evaluates the result
     *
     * @param Mage_Sales_Model_Quote $quote - the quote which needs to be checked for BillSAFe payment
     *
     * @return bool - true if the quote is valid for billSAFE payment, false otherwise
     */
    public function prevalidateOrder(Mage_Sales_Model_Quote $quote)
    {
        try {
            if ($quote->getBillingAddress() instanceof Mage_Sales_Model_Quote_Address
                && 0 < strlen($quote->getBillingAddress()->getPostcode())
            ) {
                $prevalidateResult = $this->getOrderHelper()->prevalidateOrder($quote);
                if (strtolower(trim($prevalidateResult->ack)) != 'ok' ||
                    (property_exists($prevalidateResult, 'invoice') &&
                        (bool)$prevalidateResult->invoice->isAvailable == false)
                ) {
                    if (property_exists($prevalidateResult, 'invoice')) {
                        $this->_unavailableMessage
                            = $prevalidateResult->invoice->message;
                    }
                    $this->_availableCheck = false;

                    return false;
                }
            }
            $this->_availableCheck     = true;
            $this->_unavailableMessage = '';

            return true;
        } catch (Exception $e) {
            $this->getDataHelper()->log('Exception during prevalidateOrder call ' . $e->getMessage());
            $this->_availableCheck = false;
            Mage::throwException($e);
        }
    }

    /**
     * Serialize address data
     *
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return array
     */
    private function serializeAddress(Mage_Sales_Model_Quote_Address $address)
    {
        $data = serialize(
            array(
                'firstname' => $address->getFirstname(),
                'lastname'  => $address->getLastname(),
                'company'   => $address->getCompany(),
                'street'    => $address->getStreet(),
                'city'      => $address->getCity(),
                'postcode'  => $address->getPostcode(),
            )
        );

        return $data;
    }

    /**
     * Returns code
     *
     * @return string
     */
    public function getCode()
    {
        return self::CODE;
    }

    /**
     * Return client
     *
     * @return Netresearch_Billsafe_Model_Client
     */
    public function getClient()
    {
        return Mage::getSingleton('billsafe/client');
    }

    /**
     * Returns URL to redirect to billsafe after finish checkout
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        if ($this->getOrderHelper()->isBillsafeOnsiteCheckout()) {
            // direct API communication, no gateway redirect
            return '';
        }

        $storeId = $this->getDataHelper()->getStoreIdfromQuote();

        return sprintf(
            '%s?token=%s',
            $this->getClient()->getConfig()->getGatewayUrl($storeId),
            Mage::registry(Netresearch_Billsafe_Model_Config::TOKEN_REGISTRY_KEY)
        );
    }

    /**
     * authorize
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return void
     */
    public function authorize(Varien_Object $payment, $amount)
    {

        parent::authorize($payment, $amount);
        $order        = $this->getInfoInstance()->getOrder();
        $quote        = $order->getQuote();
        $section      = $this->getCheckoutSection($order);
        $buyerMessage = "Please select another payment method!";
        if ($this->getOrderHelper()->isBillsafeOnsiteCheckout($quote)) {
            $result = array();

            try {
                $result = $this->getOrderHelper()->processOrder($quote, $order);
                $this->getDataHelper()->log('result ' . Zend_Json::encode($result));
                if (!array_key_exists('success', $result) || $result['success'] === false) {
                    if (array_key_exists('buyerMessage', $result)) {
                        $buyerMessage = $result['buyerMessage'];
                        Mage::getSingleton('customer/session')->setData(
                            'authorize_failed',
                            true
                        );
                        $addressHash = $this->getOrderHelper()
                                            ->generateAddressHash($quote->getBillingAddress());
                        Mage::getSingleton('customer/session')->setData(
                            'billsafe_billingAddrHash',
                            $addressHash
                        );
                    }
                    Mage::getSingleton('checkout/type_onepage')->getCheckout()
                        ->setGotoSection($section);
                    Mage::throwException(
                        $this->getDataHelper()->__($buyerMessage)
                    );
                }
                if (array_key_exists('transactionId', $result)) {
                    $payment
                        ->setIsTransactionPending(false)
                        ->setIsTransactionClosed(false)
                        ->setPreparedMessage(
                            $this->getDataHelper()->__(
                                'Payment registered at Billsafe'
                            )
                        )
                        ->setTransactionId($result['transactionId'])
                        ->setTxnId($result['transactionId']);
                    $state = $this->getConfig()
                                  ->getBillSafeOrderStatus($order->getStoreId());
                    if ('pending' == $state) {
                        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
                    }

                    $text   = 'Successful BillSAFE payment.<br/>Transaction ID: ' .
                        '%d.<br/>BillSAFE Transaction Status: ACCEPTED.';
                    $notice = $this->getDataHelper()->__($text, $result['transactionId']);
                    $this->getOrderHelper()->getPaymentInstruction($order);
                    $order->setState($state, true, $notice)->save();
                }
            } catch (Exception $e) {
                try {
                    // cancel the billsafe payment only if it was successfully created in a previous step
                    // don't perform the cancel request if the transaction could not be created on billsafe side
                    if (array_key_exists('transactionId', $result)) {
                        $this->cancel($payment);
                    }
                } catch (Exception $e) {
                    $this->getDataHelper()->log($e->getMessage());
                }
                Mage::getSingleton('checkout/type_onepage')->getCheckout()
                    ->setGotoSection($section);
                Mage::throwException(
                    sprintf(
                        '%s',
                        $this->getDataHelper()->__($buyerMessage)
                    )
                );
            }
        } else {
            $token = null;
            try {
                $token = $this->getClient()
                              ->prepareOrder($this->getInfoInstance()->getOrder())
                              ->getResponseToken();
            } catch (Exception $e) {
                $this->getDataHelper()->log('error getting the token ' . $e->getMessage());
            }
            if (!$token) {
                Mage::getSingleton('checkout/type_onepage')->getCheckout()->setGotoSection($section);
                Mage::throwException($this->getDataHelper()->__($buyerMessage));
            }
            Mage::register(
                Netresearch_Billsafe_Model_Config::TOKEN_REGISTRY_KEY,
                $token
            );
            $payment
                ->setIsTransactionPending(true)
                ->setIsTransactionClosed(false)
                ->setPreparedMessage(
                    $this->getDataHelper()->__('Payment registered at Billsafe')
                )
                ->setTransactionId($token);
        }
    }


    /**
     * Capture
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Netresearch_Billsafe_Model_Payment
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if (!$this->areAllCaptureItemsSent($payment)) {
            throw new Mage_Core_Exception($this->getDataHelper()->__(
                'Count of items to invoice does not match sent items count. ' .
                'Please change to items to be invoiced to match shipped items!'
            ));
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
     *
     * @return boolean
     */
    protected function areAllCaptureItemsSent($payment)
    {
        if ($this->getDataHelper()->isDoShipment() || $this->getDataHelper()->isCaptureOffline()
        ) {
            return true;
        }
        $invoice = null;

        if (is_null($payment->getOrder()) || count($payment->getOrder()->getInvoiceCollection()) != 1
        ) {
            return false;
        }
        $invoices = $payment->getOrder()->getInvoiceCollection();
        foreach ($invoices as $invoice) {
            foreach ($invoice->getAllItems() as $item) {
                $product       = Mage::getModel('catalog/product')->load($item->getProductId());
                $parentIdArray = array();
                if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
                    $configurableProductModel = Mage::getModel('catalog/product_type_configurable');
                    $parentIdArray            = $configurableProductModel->getParentIdsByChild($product->getId());
                }
                if ((0 < $product->getId() && $product->isVirtual()) ||
                    0 < count($parentIdArray)
                ) {
                    continue;
                }
                if ($item->getOrderItem()->getParentItemId()) {
                    continue;
                }
                if (Mage::helper('paymentfee/data')->isFeeProduct($item)) {
                    continue;
                }

                $orderItem = $item->getOrderItem();
                if ((float)$orderItem->getQtyShipped() != (float)$orderItem->getQtyInvoiced()
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function areThereUninvoicedItems($payment)
    {
        foreach ($payment->getOrder()->getAllItems() as $item) {
            if ((float)$item->getQtyOrdered() > (float)$item->getQtyInvoiced()
            ) {
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
    public function canCapture()
    {
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
     *
     * @return Netresearch_Billsafe_Model_Payment
     */
    public function cancel(Varien_Object $payment)
    {
        if ($payment->getOrder()->getState() !== Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
            $this->getClient()->void($payment->getOrder());
        }

        return parent::cancel($payment);
    }

    /**
     * refund
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return void
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $this->getClient()->updateArticleList(
            $payment->getOrder(), Netresearch_Billsafe_Model_Client::TYPE_RF
        );

        return parent::refund($payment, $amount);
    }

    public function isAvailableCheck()
    {
        return $this->_availableCheck;
    }

    public function getUnavailableMessage()
    {
        return $this->_unavailableMessage;
    }

    /**
     * gets the checkout section in case of errors
     *
     * @param $order
     *
     * @return string
     */
    protected function getCheckoutSection($order)
    {
        $section = 'shipping_method';
        if (false === ($order->getShippingAddress() instanceof Mage_Sales_Model_Order_Address)
            || 0 === strlen(trim($order->getShippingAddress()->getPostcode()))
        ) {
            $section = 'billing';
        }

        return $section;
    }
}
