<?php

class AwHh_Billsafe_Model_Client extends AwHh_Billsafe_Model_Client_Abstract {

    const TYPE_PO = 'prepareOrder';
    const TYPE_RS = 'reportShipment';
    const TYPE_PI = 'paymentInstruction';
    const TYPE_RF = 'refund';
    const TYPE_VO = 'void';

    protected $_response = null;
    private $_config = null;
    protected $_agreedCharges = null;

    public function __construct() {
        parent::__construct();
        $this->setWsdl($this->getConfig()->getApiUrl());

        $timeout = Mage::getStoreConfig('payment/billsafe/timeout');
        $this->_connection_timeout = (int) $timeout;
        ini_set("default_socket_timeout", $timeout);
    }

    /**
     * Returns options array
     * 
     * @return array
     */
    public function getOptions() {
        $options = parent::getOptions();
        $options['connection_timeout'] = $this->_connection_timeout;

        return $options;
    }

    /**
     * Returns response if set
     * 
     * @return mixed
     */
    public function getResponse() {
        return $this->_response;
    }

    /**
     * Returns true if request was valid
     * 
     * @return boolean
     */
    public function isValid() {
        if (is_null($this->getResponse())) {
            throw new Mage_Exception('Response is null, no request done, yet');
        }

        return $this->getResponse()->ack == 'OK';
    }

    /**
     * Returns response error message if one is set
     * 
     * @return string|null
     */
    public function getResponseError() {
        $response = $this->getResponse();
        return isset($response->errorList) ? $response->errorList[0]->message : null;
    }

    /**
     * Returns response token if one is set
     * 
     * @return string|null
     */
    public function getResponseToken() {
        $response = $this->getResponse();
        return isset($response->token) ? $response->token : null;
    }

    /**
     * Call prevalidateOrder API
     *
     * @param Mage_Sales_Model_Order $order
     * @return AwHh_Billsafe_Model_Request
     */
    public function prevalidateOrder($order) {
        $params = array_merge(
                $this->getPreparedPrevalidationOrderParams($order), $this->getDefaultParams()
        );

        $this->_response = parent::prevalidateOrder($params);
        return $this;
    }

    /**
     * Call prepareOrder API
     * 
     * @param Mage_Sales_Model_Order $order 
     * @return AwHh_Billsafe_Model_Request
     */
    public function prepareOrder($order) {
        $params = array_merge(
                $this->getPreparedOrderParams($order), $this->getDefaultParams()
        );

        $this->_response = parent::prepareOrder($params);
        return $this;
    }

    /**
     * Call prepareOrder API
     * 
     * @param Mage_Sales_Model_Quote $order 
     * @return AwHh_Billsafe_Model_Request
     */
    public function prepareOrderByQuote(Mage_Sales_Model_Quote $quote, $backend = false) {
        $params = array_merge(
                $this->getPreparedQuoteParams($quote, null, null, $backend), $this->getDefaultParams()
        );

        $this->_response = parent::prepareOrder($params);
        return $this;
    }

    /**
     * Returns array of default configuration  
     * 
     * @return array 
     */
    protected function getDefaultParams() {
        return array(
            'merchant' => array(
                'id' => $this->getConfig()->getMerchantId(),
                'license' => $this->getConfig()->getMerchantLicense(),
            ),
            'application' => array(
                'signature' => $this->getConfig()->getApplicationSignature(),
                'version' => (string) Mage::getConfig()->getNode()->modules->AwHh_Billsafe->version,
            )
        );
    }

    public function getDefaultParamsExt() {
        return $this->getDefaultParams();
    }

    /**
     * Returns order params prepared for billsafe call
     *
     * @param Mage_Sales_Model_Order $order
     * @return void
     */
    public function getPreparedOrderParams(Mage_Sales_Model_Order $order, $returnUrl = null, $cancelUrl = null) {
        if (!isset($_SERVER["HTTPS"])) {
            $_SERVER["HTTPS"] = "off";
        }
        
        if (is_null($returnUrl)) {
            $returnUrl = Mage::getUrl('billsafe/payment/verify', array("_secure" => $_SERVER["HTTPS"] === "on"));
        }

        if (is_null($cancelUrl)) {
            if (Mage::helper("billsafe")->openInLayer()) {
                $cancelUrl = Mage::getUrl('billsafe/payment/cancellation/?billsafe_close=true', array("_secure" => $_SERVER["HTTPS"] === "on")) . "?billsafe_close=true";
            } else {
                $cancelUrl = Mage::getUrl('billsafe/payment/cancellation', array("_secure" => $_SERVER["HTTPS"] === "on"));
            }
        }

        $customer = $order->getCustomer();
        $address = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $street = $address->getStreet();
        $quote = Mage::getModel('checkout/cart')->getQuote();

        $email = $this->coalesce($customer->getEmail(), $order->getCustomerEmail(), $quote->getCustomerEmail());

        if ($shippingAddress == false) {
            $shippingAddress = $customer->getDefaultShippingAddress();
            
            if($shippingAddress == false) {
                $shippingAddress = $address;
            }
        }

        $product = ($quote->getPayment()->getMethod() == "billsafe" ? "invoice" : "installment");

        $params = array(
            'order' => array(
                'number' => $order->getIncrementId(),
                'amount' => $this->format($order->getGrandTotal()),
                'taxAmount' => $this->format($order->getTaxAmount()),
                'currencyCode' => 'EUR',
            ),
            'customer' => array(
                'company' => $address->getCompany(),
                'gender' => $this->getCustomerGender($address, $order, $customer),
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'street' => implode(' ', $street),
                'houseNumber' => '',
                'postcode' => $address->getPostcode(),
                'city' => $address->getCity(),
                'country' => $address->getCountry(),
                'email' => $email,
                'dateOfBirth' => $this->getCustomerDob($customer),
                'phone' => $address->getTelephone(),
            ),
            'deliveryAddress' => array(
                'company' => $shippingAddress->getCompany(),
                'gender' => $this->getCustomerGender($shippingAddress, $order, $customer),
                'firstname' => $shippingAddress->getFirstname(),
                'lastname' => $shippingAddress->getLastname(),
                'street' => implode(' ', $shippingAddress->getStreet()),
                'houseNumber' => '',
                'postcode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity(),
                'country' => $shippingAddress->getCountry(),
            ),
            'articleList' => $this->buildArticleList($order, self::TYPE_PO),
            'product' => $product,
            'url' => array(
                'return' => $returnUrl,
                'cancel' => $cancelUrl,
                'image' => $this->getConfig()->getShopLogoUrl(),
            ),
        );

        return $params;
    }

    /**
     * Returns order params prepared for billsafe call
     *
     * @param Mage_Sales_Model_Order $order
     * @return void
     */
    public function getPreparedQuoteParams(Mage_Sales_Model_Quote $order, $returnUrl = null, $cancelUrl = null, $backend = false) {
        if (!isset($_SERVER["HTTPS"])) {
            $_SERVER["HTTPS"] = "off";
        }

        if (is_null($returnUrl)) {
            if ($backend) {
                $returnUrl = Mage::getUrl('admin_billsafe/adminhtml_billsafe_payment/verify', array("_secure" => $_SERVER["HTTPS"] === "on"));
                $returnUrl .= "key/" . Mage::getSingleton('adminhtml/url')->getSecretKey("adminhtml_billsafe_payment", "verify") . "/";
            } else {
                $returnUrl = Mage::getUrl('billsafe/payment/verify', array("_secure" => $_SERVER["HTTPS"] === "on"));
            }
        }

        if (is_null($cancelUrl)) {
            if (Mage::helper("billsafe")->openInLayer()) {
                $cancelUrl = Mage::getUrl('billsafe/payment/cancellation/?billsafe_close=true', array("_secure" => $_SERVER["HTTPS"] === "on")) . "?billsafe_close=true";
            } else {
                $cancelUrl = Mage::getUrl('billsafe/payment/cancellation', array("_secure" => $_SERVER["HTTPS"] === "on"));
            }
        }

        $customer = $order->getCustomer();
        $address = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $street = $address->getStreet();
        $quote = Mage::getModel('checkout/cart')->getQuote();

        $email = $this->coalesce($customer->getEmail(), $order->getCustomerEmail(), $quote->getCustomerEmail());
        $post = Mage::app()->getRequest()->getPost();
        
        if($email == null) {
            if(isset($post["billing"]["email"])) {                
                $email = $post["billing"]["email"];
            }
        }

        if ($shippingAddress == false) {
            $shippingAddress = $customer->getDefaultShippingAddress();
            
            if($shippingAddress == false) {
                $shippingAddress = $address;
            }
        }

        if ($backend == false) {
            $product = ($quote->getPayment()->getMethod() == "billsafe" ? "invoice" : "installment");
        } else {
            $product = ($post["payment"]["method"] == "billsafe" ? "invoice" : "installment");
        }

        $params = array(
            'order' => array(
                'number' => $order->getId(),
                'amount' => $this->format($order->getGrandTotal()),
                'taxAmount' => $this->format($order->getTaxAmount()),
                'currencyCode' => 'EUR',
            ),
            'customer' => array(
                'company' => $address->getCompany(),
                'gender' => $this->getCustomerGender($address, $order, $customer),
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'street' => implode(' ', $street),
                'houseNumber' => '',
                'postcode' => $address->getPostcode(),
                'city' => $address->getCity(),
                'country' => $address->getCountry(),
                'email' => $email,
                'dateOfBirth' => $this->getCustomerDob($customer),
                'phone' => $address->getTelephone(),
            ),
            'deliveryAddress' => array(
                'company' => $shippingAddress->getCompany(),
                'gender' => $this->getCustomerGender($shippingAddress, $order, $customer),
                'firstname' => $shippingAddress->getFirstname(),
                'lastname' => $shippingAddress->getLastname(),
                'street' => implode(' ', $shippingAddress->getStreet()),
                'houseNumber' => '',
                'postcode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity(),
                'country' => $shippingAddress->getCountry(),
            ),
            'articleList' => $this->buildArticleList($order, self::TYPE_PO),
            'product' => $product,
            'url' => array(
                'return' => $returnUrl,
                'cancel' => $cancelUrl,
                'image' => $this->getConfig()->getShopLogoUrl(),
            ),
        );

        if ($backend == true) {
            // TODO: Wieder aktivieren
            $params["salesChannel"] = "phone";
        }

        return $params;
    }

    /**
     * Returns order params prepared for billsafe call
     *
     * @param Mage_Sales_Model_Order $order
     * @return void
     */
    public function getPreparedPrevalidationOrderParams(Mage_Sales_Model_Order $order) {
        $customer = $order->getCustomer();
        $address = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $street = $address->getStreet();
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $order_contains_digital_goods = false;

        if ($shippingAddress == false) {
            $shippingAddress = $customer->getDefaultShippingAddress();
        }

        $email = $this->coalesce($customer->getEmail(), $order->getCustomerEmail(), $quote->getCustomerEmail());

        foreach ($quote->getAllItems() as $item) {
            switch ($item->getProduct()->getTypeId()) {
                case Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL:
                    $helper = Mage::helper("paymentfee");
                    if ($helper) {
                        if ($helper->isEnabled()) {
                            if (!$helper->isFeeProduct($item->getProduct())) {
                                $order_contains_digital_goods = true;
                            }
                        }
                    }
            }
        }

        $params = array(
            'order' => array(
                'amount' => $this->format($order->getGrandTotal()),
                'currencyCode' => 'EUR',
                'containsDigitalGoods' => $order_contains_digital_goods
            ),
            'customer' => array(
                'company' => $address->getCompany(),
                'gender' => $this->getCustomerGender($address, $order, $customer),
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'street' => implode(' ', $street),
                'houseNumber' => '',
                'postcode' => $address->getPostcode(),
                'city' => $address->getCity(),
                'country' => $address->getCountry(),
                'email' => $email,
                'dateOfBirth' => $this->getCustomerDob($customer),
                'phone' => $address->getTelephone(),
            ),
            'deliveryAddress' => array(
                'company' => $shippingAddress->getCompany(),
                'gender' => $this->getCustomerGender($shippingAddress, $order, $customer),
                'firstname' => $shippingAddress->getFirstname(),
                'lastname' => $shippingAddress->getLastname(),
                'street' => implode(' ', $shippingAddress->getStreet()),
                'houseNumber' => '',
                'postcode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity(),
                'country' => $shippingAddress->getCountry(),
            )
        );

        return $params;
    }

    public function prevalidateOrderByQuote(Mage_Sales_Model_Quote $quote) {
        $this->_response = false;

        $params = array_merge(
                $this->getPreparedPrevalidationOrderParamsByQuote($quote), $this->getDefaultParams()
        );

        $this->_response = parent::prevalidateOrder($params);
        return $this;
    }

    private function getPreparedPrevalidationOrderParamsByQuote(Mage_Sales_Model_Quote $quote) {
        $customer = $quote->getCustomer();
        $address = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $street = $address->getStreet();
        $order_contains_digital_goods = false;

        $email = $this->coalesce($customer->getEmail(), $quote->getCustomerEmail(), $quote->getCustomerEmail());

        foreach ($quote->getAllItems() as $item) {
            switch ($item->getProduct()->getTypeId()) {
                case Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL:
                    $helper = Mage::helper("paymentfee");
                    if ($helper) {
                        if ($helper->isEnabled()) {
                            if (!$helper->isFeeProduct($item->getProduct())) {
                                $order_contains_digital_goods = true;
                            }
                        }
                    }
            }
        }

        $params = array(
            'order' => array(
                'amount' => $this->format($quote->getGrandTotal()),
                'currencyCode' => 'EUR',
                'containsDigitalGoods' => $order_contains_digital_goods
            ),
            'customer' => array(
                'company' => $address->getCompany(),
                'gender' => $this->getCustomerGender($address, $quote, $customer),
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'street' => implode(' ', $street),
                'houseNumber' => '',
                'postcode' => $address->getPostcode(),
                'city' => $address->getCity(),
                'country' => $address->getCountry(),
                'email' => $email,
                'dateOfBirth' => $this->getCustomerDob($customer),
                'phone' => $address->getTelephone(),
            ),
            'deliveryAddress' => array(
                'company' => $shippingAddress->getCompany(),
                'gender' => $this->getCustomerGender($shippingAddress, $quote, $customer),
                'firstname' => $shippingAddress->getFirstname(),
                'lastname' => $shippingAddress->getLastname(),
                'street' => implode(' ', $shippingAddress->getStreet()),
                'houseNumber' => '',
                'postcode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity(),
                'country' => $shippingAddress->getCountry(),
            )
        );

        return $params;
    }

    /**
     * Tries to guess customers gender in billsafe requirted form (f || m)
     * 
     * @param Mage_Customer_Model_Address $address 
     * @param Mage_Sales_Model_Order $order 
     * @param Mage_Customer_Model_Customer $customer 
     * @return void
     */
    protected function getCustomerGender($address, $order, $customer) {
        $prefix = strtolower($this->coalesce(
                        $this->getGenderText($address, 'gender'), $this->getGenderText($order, 'customer_gender'), $this->getGenderText($customer, 'gender'), $address->getPrefix(), $order->getCustomerPrefix(), $customer->getPrefix()
                ));
        if (in_array($prefix, array('mrs.', 'mrs', 'frau', 'fäulein', 'frau dr.', 'female'))) {
            return 'f';
        }
        return 'm';
    }

    /**
     * Returns first not false value of given params. 
     * 
     * @return mixes
     */
    protected function coalesce() {
        $args = func_get_args();
        foreach ($args as $arg) {
            if ($arg) {
                return $arg;
            }
        }
        return NULL;
    }

    /**
     * Retrive text of gender attribute of given entity. 
     * 
     * @param Mage_Core_Model_Abstract $entity 
     * @param string $attributeCode 
     * @return string
     */
    protected function getGenderText($entity, $attributeCode) {
        return Mage::getSingleton('eav/config')
                        ->getAttribute('customer', 'gender')
                        ->getSource()
                        ->getOptionText($entity->getData($attributeCode));
    }

    /**
     * Get formated date of birth of customer, if not set return default.
     * 
     * @param Mage_Customer_Model_Customer $customer 
     * @return string
     */
    protected function getCustomerDob($customer) {
        if (!$customer->getDob()) {
            return '1970-01-01';
        }
        $date = new Zend_Date($customer->getDob());
        return $date->toString('YYYY-MM-dd');
    }

    /**
     * Generates articleList array, according to billsafe standard
     * 
     * @param Varien_Object $order 
     * @param boolean $includeShipment
     * @return array
     */
    public function buildArticleList($entity, $context, $liveArticles = NULL) {
        if ($context == self::TYPE_PI) {
            return array();
        }
        // collection adjustment fees is not supported by billsafe
        if (self::TYPE_RF == $context && $entity->getAdjustmentNegative()) {
            throw new Mage_Core_Exception($this->getHelper()->__('Add adjustment fees is not supported by BillSAFE'));
        }

        $order = $entity;
        if (in_array($context, array(self::TYPE_RS, self::TYPE_RF))) {
            $order_save = $order;
            $order = $entity->getOrder();

            if ($order == NULL) {
                $order = $order_save;
            }
        }

        $data = array();
        $items = $this->getAllOrderItems($entity, $order, $context);

        $taxAmount = 0;
        $amount = 0;

        $paymentFeeItem = null;

        foreach ($items as $item) {
            $qty_ordered = -1;

            if ($this->getHelper()->isFeeItem($item)) {
                $paymentFeeItem = $item;
                continue;
            }
            $qty = $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled();

            if (self::TYPE_VO == $context) {
                $qty = (int) $item->getQtyShipped();
                $qty_ordered = $item->getQtyOrdered();
            }

            if ($context == self::TYPE_RS) {
                $qty = $item->getQty();
                if ($item instanceof Mage_Sales_Model_Order_Shipment_Item) {
                    $item = $item->getOrderItem();
                }
            }

            if ($context == self::TYPE_PO && Mage::helper("billsafe")->openInLayer()) {
                $qty = $item->getQty();
            } else {
                if($qty == 0 && $context == self::TYPE_PO) {
                    $qty = $item->getQty();
                }
            }

            try {
                if ($item->isDummy() || $qty <= 0) {
                    continue;
                }
            } catch (Exception $ex) {
                
            }

            //$number = sprintf('%d-%s', $item->getItemId(), $item->getSku());
            $number = sprintf('%d-%s', $item->getProductId(), $item->getSku());

            $data_price = $this->format($item->getPrice());

            if ($context == self::TYPE_VO) {
                $data[] = array(
                    'number' => substr($number, 0, 50),
                    'name' => $item->getName(),
                    'description' => $item->getName(),
                    'type' => 'goods',
                    'quantity' => (int) $qty,
                    'quantityShipped' => (int) $item->getQtyShipped(),
                    'quantityOrdered' => $qty_ordered,
                    'netPrice' => $data_price,
                    'tax' => $this->format($item->getTaxPercent()),
                );
            } else {
                if ($context == self::TYPE_RS) {
                    $data[] = array(
                        'number' => substr($number, 0, 50),
                        'name' => $item->getName(),
                        'description' => $item->getName(),
                        'type' => 'goods',
                        'quantity' => (int) $qty,
                        //'quantityShipped' => (int) $item->getQtyShipped() - $qty,
                        'netPrice' => $data_price,
                        'tax' => $this->format($item->getTaxPercent()),
                    );
                } else {
                    $data[] = array(
                        'number' => substr($number, 0, 50),
                        'name' => $item->getName(),
                        'description' => $item->getName(),
                        'type' => 'goods',
                        'quantity' => (int) $qty,
                        'quantityShipped' => (int) $item->getQtyShipped(),
                        'netPrice' => $data_price,
                        'tax' => $this->format($item->getTaxPercent()),
                    );
                }
            }

            $amount += $item->getPriceInclTax() * $qty;
            $taxAmount += $item->getTaxAmount() * $qty;
        }

        $remainingShipmentItemQty = 0;
        try {
            $remainingShipmentItemQty = $this->getRemainingShipmentItemQty($order, $context);
        } catch (Exception $ex) {
            
        }
        $shippingCosts = $this->getShippingNetPrice($order);

        if ($context == self::TYPE_PO) { //  && Mage::helper("billsafe")->openInLayer()
            if ($shippingCosts == NULL) {
                $shippingCostsObject = $order->getShippingAddress();
                $shippingCosts = $shippingCostsObject->getBaseShippingAmount();
            }
        }

        if (($remainingShipmentItemQty > 0 && $shippingCosts > 0) || ($context == self::TYPE_PO && $shippingCosts > 0)) {
            $tax = $this->getShippingTaxPercent($order);

            if ($context == self::TYPE_PO) { //  && Mage::helper("billsafe")->openInLayer()
                if (isset($shippingCostsObject)) {
                    $description = $shippingCostsObject->getShippingDescription();
                } elseif ($order) {
                    $description = $order->getShippingDescription();
                }

                $remainingShipmentItemQty = 1;
            } else {
                if ($shippingCosts == NULL || $shippingCosts == 0) {
                    $shippingCosts = $this->getShippingNetPrice($order);
                }

                $description = $order->getShippingDescription();
            }
            $data[] = array(
                'number' => '___shipment___',
                'name' => 'Shipment',
                'description' => $description,
                'type' => 'shipment',
                'quantity' => $remainingShipmentItemQty,
                'quantityShipped' => $remainingShipmentItemQty,
                'netPrice' => $this->format($shippingCosts),
                'tax' => $tax,
            );
            $amountExclTax = $order->getShippingAmount() - $order->getShippingRefunded();

            try {
                $amount += round($amountExclTax * (1 + $this->getShippingTaxPercent($order) / 100));
            } catch (Exception $ex) {
                $amount += round($amountExclTax);
            }

            $taxAmount += round($amount - $amountExclTax, 2);
        }
        
        foreach($order->getAllItems() as $item) {
            $discount_amount += $item->getDiscountAmount();
        }

        if (self::TYPE_RF != $context && $discount_amount > 0) {
            $data[] = array(
                'number' => '___discount___',
                'name' => 'Discount',
                'type' => 'voucher',
                'quantity' => 1,
                'netPrice' => -$discount_amount,
                'tax' => 0.00,
            );
            $amount -= $order->getDiscountAmount();
        }
        
        $adjustmentPositiv = $order->getAdjustmentPositive();
        if (self::TYPE_RS != $context && $adjustmentPositiv > 0) {
            $data[] = array(
                'number' => '___adjustment__',
                'name' => 'Creditmemo',
                'type' => 'voucher',
                'quantity' => 1,
                'quantityShipped' => 0,
                'netPrice' => -$adjustmentPositiv,
                'tax' => 0.00
            );
            $amount -= $adjustmentPositiv;
        }

        if (false === is_null($paymentFeeItem)) {
            //TODO: CS check
            // $qty = $paymentFeeItem->getQtyOrdered() - $paymentFeeItem->getQtyRefunded() - $paymentFeeItem->getQtyCanceled();
            // if ($qty) {
            if($paymentFeeItem->getRowTotal()) {
                if($paymentFeeItem->getRowTotal() > 0) {
                    $paymentfee_price = $paymentFeeItem->getRowTotal();
                } else {
                    $paymentfee_price = $paymentFeeItem->getRowTotalInclTax();
                }
            } else {
                $paymentfee_price = $paymentFeeItem->getRowTotalInclTax();
            }

            if ($context == self::TYPE_VO) {
                $data[] = array(
                    'number' => '___fee___',
                    'name' => $paymentFeeItem->getName(),
                    'type' => 'handling',
                    'quantity' => 1,
                    'netPrice' => $this->format($paymentfee_price),
                    'tax' => $this->format($paymentFeeItem->getTaxPercent()),
                );
            } else {
                $data[] = array(
                    'number' => '___fee___',
                    'name' => $paymentFeeItem->getName(),
                    'type' => 'handling',
                    'quantity' => 1,
                    'netPrice' => $this->format($paymentfee_price),
                    'tax' => $this->format($paymentFeeItem->getTaxPercent()),
                );
            }

            $amount += $paymentFeeItem->getPriceInclTax();
            $taxAmount += $paymentFeeItem->getTaxAmount();
            //}
        }

        if (self::TYPE_RF == $context) {
            #$data['tax_amount'] = $taxAmount;
            #$data['amount'] = $amount;
        }

        if ($context == self::TYPE_RS) {
            if ($liveArticles) {
                foreach ($liveArticles->articleList as $live_article) {
                    $x = 0;

                    foreach ($data as $d) {
                        if ($d["number"] == $live_article->number) {
                            $data[$x]["grossPrice"] = $live_article->grossPrice;
                            $data[$x]["tax"] = $live_article->tax;
                            unset($data[$x]["netPrice"]);
                        }

                        $x++;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Collects all entitys items
     * * 
     * @param Mage_Sales_Model_Abstract $entity 
     * @param Mage_Sales_Model_Order $order 
     * @param string $context 
     * @return array
     */
    protected function getAllOrderItems($entity, $order, $context) {
        if ($context == self::TYPE_RS || $context == self::TYPE_RF) {
            return $entity->getAllItems();
        }
        return $order->getAllItems();
    }

    public function getHelper() {
        return Mage::helper('billsafe');
    }

    /**
     * Returns remaining shipment quantity
     * 
     * @param Mage_Sales_Model_Order $order 
     * @return integer
     */
    public function getRemainingShipmentItemQty($order, $context) {
        if (in_array($context, array(self::TYPE_VO, self::TYPE_PI))) {
            return 1;
        }

        $shipments = $order->getShipmentsCollection();
        if (is_object($shipments) && $shipments->count() > 1 && self::TYPE_RF != $context) {
            return 0;
        }
        if (0 == $order->getShippingAmount() - $order->getShippingRefunded()) {
            return 0;
        }
        return 1;
    }

    /**
     * Calculates shipping price
     * 
     * @param Varien_Object $order 
     * @return float
     */
    protected function getShippingNetPrice($order) {
        $price = $order->getShippingInclTax() - $order->getShippingTaxAmount();
        $price -= $order->getShippingRefunded();
        $price -= $order->getShippingCanceled();
        return $price;
    }

    /**
     * Calculate shipping tax amount in percent
     * 
     * @param Varien_Object $order 
     * @return float
     */
    protected function getShippingTaxPercent($order) {
        return 19; // TODO
        $tax = ($order->getShippingTaxAmount() * 100) / $order->getShippingAmount();
        return $this->format(round($tax));
    }

    /**
     * Return config object
     * 
     * @return AwHh_Billsafe_Model_Config
     */
    public function getConfig() {
        if (is_null($this->_config)) {
            $this->setConfig();
        }

        return $this->_config;
    }
    
    public function initBackendConfig($order) {
        if (is_null($this->_config)) {
            $this->setConfig();
        }
        
        // get Store
        $store_id = $order->getStoreId();
        
        // get BillSAFE Store Details
        $merchant_id = Mage::getStoreConfig("payment/billsafe/merchant_id", $store_id);
        $merchant_license = Mage::getStoreConfig("payment/billsafe/merchant_license", $store_id);
        
        // set details
        $this->_config->setMerchantId($merchant_id);
        $this->_config->setMerchantLicense($merchant_license);
    }

    /**
     * Set configuration
     * 
     * @param mixed $config 
     * @return AwHh_Billsafe_Model_Client
     */
    public function setConfig($config = null) {
        if (is_null($config)) {
            $this->_config = Mage::getSingleton('billsafe/config');
        } else {
            $this->_config = $config;
        }

        return $this;
    }

    /**
     * Returns true if payment was accepted
     * 
     * @return boolean
     */
    public function isAccepted() {
        if (is_null($this->getResponse())) {
            throw new Mage_Exception('Response is null, no request done, yet');
        }

        return $this->getResponse()->status == 'ACCEPTED';
    }

    public function getResponseTransactionId() {
        $response = $this->getResponse();
        return isset($response->transactionId) ? $response->transactionId : null;
    }

    /**
     * Call getTransactionResult API
     * 
     * @param string $token 
     * @return AwHh_Billsafe_Model_Client
     */
    public function getTransactionResult($token) {
        $params = array_merge(
                array('token' => $token), $this->getDefaultParams()
        );

        $this->_response = parent::getTransactionResult($params);
        return $this;
    }

    /**
     * Call getPaymentInstruction API
     * 
     * @param Varien_Object $payment
     * @return array
     */
    public function getPaymentInstruction($order) {
        $order_quote_id = $order->getQuoteId();
        #$order_quote_incrementId = $order->getIncrementId();
        #if($order_quote_incrementId != null) {
        #$order_quote_id = $order_quote_incrementId;
        #}

        $order_params = array(
            'orderNumber' => $order_quote_id,
            'outputType' => 'STRUCTURED',
        );

        if ($transaction_id = Mage::getSingleton("core/session")->getBillsafeTransactionIdForPaymentInstructions()) {
            $order_params["order_number"] = null;
            $order_params["transactionId"] = $transaction_id;
        } else {
            try {
                $payment = $order->getPayment()->getAdditionalInformation();

                if ($transaction_id = $payment["BillsafeBTN"]) {
                    $order_params["order_number"] = null;
                    $order_params["transactionId"] = $transaction_id;
                }
            } catch (Exception $ex) {
                
            }
        }

        $default_params = $this->getDefaultParams();
        $params = array_merge($order_params, $default_params);

        $structured = parent::getPaymentInstruction($params);

        if ('OK' != $structured->ack /* || 'OK' != $pdf->ack */) {
            $error = $structured->errorList;
            $error = is_array($error) ? $error[0] : $error;
            if (302 == $error->code) {
                /* Billsafe Error:
                 * Transaction has a wrong status for this method
                 * This occurs, if the amount at billsafe becomes 0, due to refunds or cancalations.
                 * In this case, payment can be treated as cancelled.
                 */
                return array();
            }
            throw new Mage_Exception('Unable to retrieve billsafe payment instructions');
        }

        return $structured->instruction;
    }

    /**
     * Call reportShipment API
     * 
     * @param Mage_Sales_Model_Order_Shipment $shipment 
     * @return AwHh_Billsafe_Model_Client
     */
    public function reportShipment(Mage_Sales_Model_Order_Shipment $shipment) {
        $payment_data = $shipment->getOrder()->getPayment()->getAdditionalInformation();
        $transaction_id = (isset($payment_data["BillsafeBTN"]) ? $payment_data["BillsafeBTN"] : str_replace("BTN ", "", $payment_data["Reference"]));
        $this->initBackendConfig($shipment->getOrder());

        $articleListLIVE = parent::getArticleList(array_merge(array(
                            "transactionId" => $transaction_id), $this->getDefaultParams()
                        ));

        $params = array_merge(
                array(
            'transactionId' => $transaction_id,
            'articleList' => $this->buildArticleList($shipment, self::TYPE_RS, $articleListLIVE)), $this->getDefaultParams()
        );

        $result = parent::reportShipment($params);

        if ('OK' != $result->ack) {
            throw new Mage_Exception('Unable to register billsafe shipment');
        }

        return $this;
    }

    /**
     * Updates ArticleList regarding the modified order
     * 
     * @param Mage_Sales_Model_Order $modifiedOrder
     * @return void
     */
    public function updateArticleList(Mage_Sales_Model_Order $order, $context) {
        $amount = $this->format($order->getGrandTotal() - $order->getTotalRefunded() - $order->getTotalCanceled());
        $taxAmount = $order->getTaxAmount() - $order->getTaxRefunded() - $order->getTaxCanceled();

        $entity = self::TYPE_RF == $context ? Mage::registry('current_creditmemo') : $order;

        $articleList = $this->buildArticleList($entity, $context);

        if (self::TYPE_RF == $context) {
            if (array_key_exists('amount', $articleList)) {
                $amount = $articleList['amount'];
                $taxAmount = $articleList['tax_amount'];
                unset($articleList['tax_amount']);
                unset($articleList['amount']);
            }
        }

        if ($context == self::TYPE_VO) {
            $amount = 0;

            foreach ($articleList as $article) {
                if (array_key_exists("quantityOrdered", $article)) {
                    $qty = $article["quantityOrdered"] - $article["quantityShipped"];
                } else {
                    $qty = 1;
                }

                $amount += $this->format((($qty * $article["netPrice"]) * (1 + ($article["tax"] / 100))), 2);
            }
        }

        $payment = $order->getPayment();
        $infos = $payment->getAdditionalInformation();
        $transcation_id = $infos["BillsafeBTN"];

        $params = array_merge(
                array(
            'order' => array(
                'number' => $order->getIncrementId(),
                'amount' => $this->format($amount),
                'taxAmount' => $this->format($taxAmount),
                'currencyCode' => 'EUR',
            ),
            #'orderNumber' => $order->getIncrementId(),
            "transactionId" => $transcation_id,
            'articleList' => ($amount > 0) ? $articleList : array()), $this->getDefaultParams()
        );

        $result = parent::updateArticleList($params);

        if ($result->ack != 'OK') {
            throw new Mage_Exception('Unable to update article list at billsafe.');
        }
        return $this;
    }

    public function updateArticleListExt($params) {
        return parent::updateArticleList($params);
    }

    public function updateArticleListNoArticles($order, $transaction_id) {
        $params = array_merge(
                array(
            'order' => array(
                'number' => $order->getIncrementId(),
                'amount' => 0,
                'taxAmount' => 0,
                'currencyCode' => 'EUR',
            ),
            #'orderNumber' => $order->getIncrementId(),
            "transactionId" => $transaction_id,
            'articleList' => array()), $this->getDefaultParams()
        );

        return parent::updateArticleList($params);
    }

    /**
     * Void 
     * 
     * @param Varien_Object $order 
     * @return void
     */
    public function void(Varien_Object $order) {
        return $this->updateArticleList($order, self::TYPE_VO);
    }

    /**
     * Fetches and returns handling charges (payment fee) configuration
     * from BillSAFE.
     *
     * @return array
     */
    public function getAgreedHandlingCharges($key = NULL) {
        return 1000000;
        
        if (is_null($this->_agreedCharges)) {
            $result = parent::getAgreedHandlingCharges($this->getDefaultParams());
            if (isset($result->agreedCharge)) {
                $this->_agreedCharges = $result->agreedCharge;
            } else {
                return null;
            }
        }
        if ($key) {
            return $this->_agreedCharges->$key;
        }
        return $this->_agreedCharges;
    }

    public function getMaxFee() {
        return $this->getAgreedHandlingCharges('charge');
    }

    public function getFeeMaxAmount() {
        return $this->getAgreedHandlingCharges('maxAmount');
    }

    /**
     * Formats given number according to billsafe standard
     * 
     * @param integer|float $number 
     * @return string
     */
    private function format($number) {
        return number_format($number, 2, '.', '');
    }

}
