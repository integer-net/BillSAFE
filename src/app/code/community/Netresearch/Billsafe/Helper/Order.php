<?php
/**
 * Netresearch Billsafe
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @copyright   Copyright (c) 2013 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Order Helper for Billsafe module
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Netresearch_Billsafe_Helper_Order extends Mage_Payment_Helper_Data
{
    const TYPE_PO = 'prepareOrder';
    const TYPE_RS = 'reportShipment';
    const TYPE_PI = 'paymentInstruction';
    const TYPE_RF = 'refund';
    const TYPE_VO = 'void';

    /**
     *  return Data helper object
     *
     * @return Netresearch_Billsafe_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('billsafe/data');
    }

    /**
     * Check if the payment of the given order is made via BillSAFE payment method.
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function hasBillsafePayment(Mage_Sales_Model_Order $order)
    {
        $billsafeCode = Netresearch_Billsafe_Model_Payment::CODE;
        $paymentCode  = $order->getPayment()->getMethodInstance()->getCode();
        return ($paymentCode === $billsafeCode);
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     */
    public function prepareParamsForPrevalidateOrder(Mage_sales_Model_Quote $quote)
    {
        $params = array(
            'order' => $this->getOrderParams($quote),
            'customer' => $this->getCustomerData(
                $quote->getBillingAddress()
            ),
            'deliveryAddress' => $this->getDeliveryAddressData($quote),
        );
        $params['deliveryAddress']['postcode'] = substr(
            $params['deliveryAddress']['postcode'], 0, 5
        );

        return $params;
    }

    /**
     *
     *
     * @param $quote the quote which contains the data
     *
     * @return an array containing the order params
     */
    protected function getOrderParams(Mage_sales_Model_Quote $quote)
    {
        $orderParams = array(
            'amount' => round($quote->getGrandTotal(), 2),
            'currencyCode' => $quote->getQuoteCurrencyCode(),
        );
        return $orderParams;
    }

    /**
     *
     * gets relevant parts from the customers address
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @param bool                           $includeEmail - returned data contain customer's e-mail address if true
     * @param bool                           $useNames - return data contains customer's first- and lastname if true
     *
     * @return array - the parts for billSAFE
     */
    protected function getCustomerData(Mage_Sales_Model_Quote_Address $address,
        $includeEmail = true, $useNames = false
    ) {
        $firstName = ($useNames) ? $address->getFirstname() : '';
        $lastName = ($useNames) ? $address->getLastname() : '';
        $customerParams = array(
            'firstname' => $firstName,
            'lastname'  => $lastName,
            'street'    => $address->getStreetFull(),
            'postcode'  => $address->getPostcode(),
            'city'      => $address->getCity(),
            'country'   => $address->getCountry(),
            'phone'     => $address->getTelephone(),
            'company'   => $address->getCompany(),
        );
        if ($includeEmail) {
            $customerParams['email'] = $address->getEmail();
        }
        return $customerParams;
    }

    /**
     *
     * gets relevant parts for the delivery address part
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @param bool                           $includeEmail
     *
     * @return array - the parts for billSAFE
     */
    protected function getDeliveryAddressData(Mage_Sales_Model_Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        // special treatment for digital goods because in this case we don't have a shipping address
        if (false === ($shippingAddress instanceof Mage_Sales_Model_Quote_Address)
            || 0 == strlen($shippingAddress->getPostcode())
        ) {
            $shippingAddress = $quote->getBillingAddress();
        }
        return $this->getCustomerData($shippingAddress, false);
    }

    /**
     * triggers web service call
     *
     * @param $quote
     */
    public function prevalidateOrder($quote)
    {
        try {
            $params = $this->prepareParamsForPrevalidateOrder($quote);
            return Mage::getModel('billsafe/client')->prevalidateOrder($params, $quote);
        } catch (Exception $e) {
            return new stdClass();
        }
    }

    /**
     *
     * performs process Order call
     *
     * @param $quote
     * @param $order
     *
     * @return mixed
     */
    public function processOrder($quote, $order)
    {
        $params = $this->prepareParamsForProcessOrder($quote, $order);
        $result = Mage::getModel('billsafe/client')->processOrder($params, $quote);
        return $result;
    }

    public function prepareParamsForProcessOrder($quote, $order)
    {
        $params = array(
            'order' => $this->getOrderParamsForProcessOrder(
                $quote, $order
            ),
            'customer' => $this->getFullCustomerData($quote),
            'articleList' => $this->buildArticleList($order, self::TYPE_PO),
            'product' => 'invoice',
            'sessionId' => md5(
                Mage::getSingleton('checkout/session')->getSessionId()
            )
        );

        return $params;
    }

    protected function getOrderParamsForProcessOrder(Mage_Sales_Model_Quote $quote, $order)
    {
        $params = $this->getOrderParams($quote);
        $address = ($quote->getShippingAddress()) ? $quote->getShippingAddress() : $quote->getBillingAddress();
        $params['taxAmount'] = round($address->getTaxAmount(), 2);
        $params['number'] = $order->getIncrementId();
        return $params;
    }

    /**
     * retrieves the complete customer data for calls to BillSAFE
     *
     * @param Mage_Sales_Model_Quote $quote - the current quote
     *
     * @return array - the customer data including dob and gender
     */
    protected function getFullCustomerData(Mage_Sales_Model_Quote $quote)
    {
        $customerData = $this->getCustomerData(
            $quote->getBillingAddress(), true, true
        );
        $customerHelper = Mage::helper('billsafe/customer');

        $customerData['gender'] = $customerHelper->getCustomerGender(
            $quote->getBillingAddress(), $quote, $quote->getCustomer()
        );

        if (0 == strlen($quote->getBillingAddress()->getCompany())) {
            $dob = Mage::getSingleton('checkout/session')->getData('customer_dob');
            if ($dob) {
                $date = new Zend_Date(strtotime($dob));
                $dob = $date->get('yyyy-MM-dd');
            } else {
                $dob = $customerHelper->getCustomerDob(
                    $quote->getCustomer(), $quote
                );
            }
            $customerData['dateOfBirth'] = $dob;
        }
        return $customerData;
    }

    /**
     * generates hash from address data
     *
     * @param Mage_Sales_Model_Quote_Address $address the address data to hash
     *
     * @returns sha1 hash of address
     */
    public function generateAddressHash(Mage_Customer_Model_Address_Abstract $address)
    {
        $addressString  = $address->getFirstname();
        $addressString .= $address->getMiddlename();
        $addressString .= $address->getLastname();
        $addressString .= $address->getCompany();
        $street = $address->getStreetFull();
        if (is_array($street)) {
            $street = implode('', $street);
        }
        $addressString .= $street;
        $addressString .= $address->getPostcode();
        $addressString .= $address->getCity();
        $addressString .= $address->getCountryId();

        return sha1($addressString);
    }

    /**
     * Collects all entitys items
     * *
     *
     * @param Mage_Sales_Model_Abstract $entity
     * @param Mage_Sales_Model_Order    $order
     * @param string                    $context
     *
     * @return array
     */
    protected function getAllOrderItems($entity, $order, $context)
    {
        if ($context == self::TYPE_RS) {
            return $entity->getAllItems();
        }
        return $order->getAllItems();
    }

    /**
     * Generates articleList array, according to billsafe standard
     *
     * @param Varien_Object $order
     * @param boolean       $includeShipment
     *
     * @return array
     */
    public function buildArticleList(Mage_Sales_Model_Abstract $entity, $context)
    {
        if ($context == self::TYPE_PI) {
            return array();
        }
        // collection adjustment fees is not supported by billsafe
        if (self::TYPE_RF == $context && $entity->getAdjustmentNegative()) {
            throw new Mage_Core_Exception($this->getHelper()->__(
                'Add adjustment fees is not supported by BillSAFE'
            ));
        }

        $order = $entity;
        if (in_array($context, array(self::TYPE_RS, self::TYPE_RF))) {
            $order = $entity->getOrder();
        }

        $data = array();
        $items = $this->getAllOrderItems($entity, $order, $context);

        $remainShipItemQty = $this->getRemainingShipmentItemQty(
            $order, $context
        );



        $taxAmount = 0;
        $amount = 0;
        $paymentFeeItem = null;
        // order items
        $orderItemData = $this->getOrderItemData(
            $items, $amount, $taxAmount, $context
        );

        /*
         * append any virtual products to the last shipping, so that the billsafe state is changed correctly
         *
         */
        if (($context == self::TYPE_RS && $this->areAllPhysicalItemsShipped($order))
            || $context == self::TYPE_VO || $context == self::TYPE_VO) {
            $amount    = $orderItemData['amount'];
            $taxAmount = $orderItemData['tax_amount'];
            $virtualItemData = $this->getVirtualItemData($order, $amount, $taxAmount, $context);
            if (0 < count($virtualItemData['data'])) {
                $orderItemData['data'] = array_merge($orderItemData['data'], $virtualItemData['data']);
                $orderItemData['amount'] = $virtualItemData['amount'];
                $orderItemData['tax_amount'] = $virtualItemData['tax_amount'];
            }
        }

        if (0 < count($orderItemData)) {
            if (array_key_exists('payment_fee_item', $orderItemData)) {
                $paymentFeeItem = $orderItemData['payment_fee_item'];
            }
            if (array_key_exists('data', $orderItemData)) {
                $data = $orderItemData['data'];
            }
            if (array_key_exists('amount', $orderItemData)) {
                $amount = $orderItemData['amount'];
            }
            if (array_key_exists('tax_amount', $orderItemData)) {
                $taxAmount = $orderItemData['tax_amount'];
            }
        }
        //shipping item data
        $shippingItemData = $this->getShippingItemData($order, $context);
        if (0 < count($shippingItemData)) {
            $data[] = $shippingItemData;
            $amountExclTax
                = $order->getShippingAmount() - $order->getShippingRefunded();
            $amount += round(
                $amountExclTax * (1 +
                $this->getShippingTaxPercent($order) / 100)
            );
            $taxAmount += round($amount - $amountExclTax, 2);
        }
        // discount item
        $discountItemData = $this->getDiscountItemData($order, $context);
        if (0 < count($discountItemData)) {
            $data[] = $discountItemData;
            $amount -= $order->getDiscountAmount();
        }
        // adjustment (refund)
        $adjustmentData = $this->getAdjustmentData($order, $context, $amount);
        if (0 < count($adjustmentData)) {
            if (array_key_exists('data', $adjustmentData)) {
                $data[] = $adjustmentData['data'];
            }
            if (array_key_exists('amount', $adjustmentData)) {
                $amount = $adjustmentData['amount'];
            }
        }

        // payment fee
        $paymentFeeData = $this->getPaymentFeeData(
            $paymentFeeItem, $context, $amount, $taxAmount
        );
        if (0 < count($paymentFeeData)) {
            if (array_key_exists('data', $paymentFeeData)) {
                $data[] = $paymentFeeData['data'];
            }
            if (array_key_exists('amount', $paymentFeeData)) {
                $amount = $paymentFeeData['amount'];
            }
            if (array_key_exists('tax_amount', $paymentFeeData)) {
                $taxAmount = $paymentFeeData['tax_amount'];
            }
        }
        // special refund
        if (self::TYPE_RF == $context) {
            $data['tax_amount'] = $taxAmount;
            $data['amount'] = $amount;
        }
        return $data;
    }

    /**
     * Returns remaining shipment quantity
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return integer
     */
    public function getRemainingShipmentItemQty(Mage_Sales_Model_Order $order, $context)
    {
        if (in_array($context, array(self::TYPE_VO, self::TYPE_PI))) {
            return 1;
        }

        $shipments = $order->getShipmentsCollection();
        if (is_object($shipments) && $shipments->count() > 1 && self::TYPE_RF != $context
        ) {
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
     *
     * @return float
     */
    protected function getShippingNetPrice($order)
    {
        $price = $order->getShippingInclTax() - $order->getShippingTaxAmount();
        $price -= $order->getShippingRefunded();
        $price -= $order->getShippingCanceled();
        return $price;
    }

    /**
     * Calculate shipping tax amount in percent
     *
     * @param Varien_Object $order
     *
     * @return float
     */
    protected function getShippingTaxPercent($order)
    {
        $tax = ($order->getShippingTaxAmount() * 100) / $order->getShippingAmount();
        return $this->getHelper()->format(round($tax));
    }

    /**
     * return the shipping item data as array
     *
     * @param Mage_Sales_Model_Order $order
     * @param string                 $context
     *
     * @return array
     */
    protected function getShippingItemData($order, $context)
    {
        $data = array();
        $remainShipItemQty = $this->getRemainingShipmentItemQty(
            $order, $context
        );
        if ($remainShipItemQty > 0 && $order->getShippingInclTax() > 0) {
            $data = array(
                'number' => '___shipment___',
                'name' => 'Shipment',
                'description' => $order->getShippingDescription(),
                'type' => 'shipment',
                'quantity' => $remainShipItemQty,
                'quantityShipped' => $remainShipItemQty,
                'netPrice' => $this->getHelper()->format(
                    $this->getShippingNetPrice($order)
                ),
                'tax' => $this->getShippingTaxPercent($order),
            );
        }
        return $data;
    }

    /**
     * return the discount item data as array
     *
     * @param Mage_Sales_Model_Order $order
     * @param string                 $context
     *
     * @return array
     */
    protected function getDiscountItemData($order, $context)
    {
        $data = array();
        if (self::TYPE_RF != $context && $order->getDiscountAmount() < 0) {
            $data = array(
                'number' =>
                '___discount___-' . $order->getDiscountDescription(),
                'name' => 'Discount ' . $order->getDiscountDescription(),
                'type' => 'voucher',
                'quantity' => 1,
                'netPrice' => round($order->getDiscountAmount(), 2),
                'tax' => 0.00,
            );
        }
        return $data;
    }

    /**
     * return the adjustment data as array
     *
     * @param Mage_Sales_Model_Order $order
     * @param decimal                $context
     * @param decimal                $amount
     *
     * @return array
     */
    protected function getAdjustmentData($order, $context, $amount)
    {
        $data = array();
        $adjustmentPositiv = $order->getAdjustmentPositive();
        if (self::TYPE_RS != $context && $adjustmentPositiv > 0) {
            $data['data'] = array(
                'number' => '___adjustment__',
                'name' => 'Creditmemo',
                'type' => 'voucher',
                'quantity' => 1,
                'quantityShipped' => 1,
                'netPrice' => -$adjustmentPositiv,
                'tax' => 0.00
            );
            $data['amount'] = $amount - $adjustmentPositiv;
        }
        return $data;
    }

    /**
     * return payment fee data as array
     *
     * @param Mage_Sales_Model_Order_Item $paymentFeeItem
     * @param string                      $context
     * @param decimal                     $amount
     * @param decimal                     $taxAmount
     *
     * @return array
     */
    protected function getPaymentFeeData($paymentFeeItem, $context, $amount, $taxAmount)
    {
        $data = array();
        if (false === is_null($paymentFeeItem)) {
            $qty = $paymentFeeItem->getQtyOrdered() - $paymentFeeItem->getQtyRefunded() - $paymentFeeItem->getQtyCanceled();
            if ($qty) {
                $paymentFeeData = array(
                    'number' => '___fee___',
                    'name' => $paymentFeeItem->getName(),
                    'type' => 'handling',
                    'quantity' => $qty,
                    'netPrice' => $this->getHelper()->format(
                        $paymentFeeItem->getRowTotal()
                    ),
                    'tax' => $this->getHelper()->format(
                        $paymentFeeItem->getTaxPercent()
                    ),
                );
                if (self::TYPE_PI == $context || self::TYPE_RF == $context || self::TYPE_VO == $context) {
                    $paymentFeeData['quantityShipped'] = 1;
                }
                $data['data'] = $paymentFeeData;
                $data['amount'] = $amount + $paymentFeeItem->getPriceInclTax();
                $data['tax_amount'] = $taxAmount + $paymentFeeItem->getTaxAmount();
            }
        }
        return $data;
    }

    /**
     * returns the order item data as array
     *
     * @param array   $orderItems
     * @param decimal $amount
     * @param decimal $taxAmountbill
     *
     * @return array
     */
    protected function getOrderItemData($orderItems, $amount, $taxAmount, $context)
    {
        $data = array(
            'amount' => 0,
            'tax_amount' => 0
        );
        foreach ($orderItems as $item) {
            if ($this->getHelper()->isFeeItem($item)) {
                $data['payment_fee_item'] = $item;
                continue;
            }
            $qty = $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled();

            if (self::TYPE_VO == $context) {
                $qty = (int) $item->getQtyShipped();
            }

            if ($context == self::TYPE_RS) {
                $qty = $item->getQty();
                if ($item instanceof Mage_Sales_Model_Order_Shipment_Item) {
                    $item = $item->getOrderItem();
                }
            }

            if ($item->isDummy() || $qty <= 0) {
                continue;
            }

            $number = sprintf('%d-%s', $item->getItemId(), $item->getSku());
            $data['data'][] = array(
                'number' => substr($number, 0, 50),
                'name' => $item->getName(),
                'description' => $item->getName(),
                'type' => 'goods',
                'quantity' => (int) $qty,
                'quantityShipped' => (int) $item->getQtyShipped(),
                'netPrice' => $this->getHelper()->format(
                    $item->getPrice()
                ),
                'tax' => $this->getHelper()->format(
                    $item->getTaxPercent()
                ),
            );
            $data['amount'] += $amount + $item->getPriceInclTax() * $qty;
            $data['tax_amount'] += $taxAmount + $item->getTaxAmount() * $qty;
        }
        return $data;
    }

    protected function getVirtualItemData($order, $amount, $taxAmount, $context)
    {
        $data = array(
            'data' => array(),
            'amount' => 0,
            'tax_amount' => 0
        );
        foreach ($order->getAllItems() as $item) {
            if (!$this->getHelper()->isFeeItem($item)
                && (($item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL
                    || $item->getProduct()->getTypeId() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE))
            ) {
                $qty = $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled();
                $number = sprintf('%d-%s', $item->getItemId(), $item->getSku());
                $data['data'][] = array(
                    'number' => substr($number, 0, 50),
                    'name' => $item->getName(),
                    'description' => $item->getName(),
                    'type' => 'goods',
                    'quantity' => (int) $qty,
                    'quantityShipped' => $qty,
                    'netPrice' => $this->getHelper()->format(
                        $item->getPrice()
                    ),
                    'tax' => $this->getHelper()->format(
                        $item->getTaxPercent()
                    ),
                );
                $data['amount'] += $amount + $item->getPriceInclTax() * $qty;
                $data['tax_amount'] += $taxAmount + $item->getTaxAmount() * $qty;
            }
        }

        return $data;
    }

    /**
     * Returns order params prepared for billsafe call
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return void
     */
    public function getPreparedOrderParams(Mage_Sales_Model_Order $order,
        $returnUrl = null, $cancelUrl = null
    ) {
        if (is_null($returnUrl)) {
            $returnUrl = Mage::getUrl('billsafe/payment/verify');
        }

        if (is_null($cancelUrl)) {
            $cancelUrl = Mage::getUrl('billsafe/payment/cancellation');
        }

        $quote = Mage::getModel('checkout/cart')->getQuote();

        $params = array(
            'order' => array(
                'number' => $order->getIncrementId(),
                'amount' => $this->getHelper()->format(
                    $order->getGrandTotal()
                ),
                'taxAmount' => $this->getHelper()->format(
                    $order->getTaxAmount()
                ),
                'currencyCode' => 'EUR',
            ),
            'customer' => $this->getFullCustomerData($quote),
            'articleList' => $this->buildArticleList($order, self::TYPE_PO),
            'product' => 'invoice',
            'url' => array(
                'return' => $returnUrl,
                'cancel' => $cancelUrl,
                'image' => Mage::getModel('billsafe/config')->getShopLogoUrl(
                    $order->getStoreId()
                ),
            ),
        );
        return $params;
    }

    public function getPaymentInstruction($order)
    {
        $payment = $order->getPayment();
        $code = $payment->getMethodInstance()->getCode();
        if ($code != Netresearch_Billsafe_Model_Payment::CODE) {
            return;
        }

        try {
            $data = Mage::getSingleton('billsafe/client')->getPaymentInstruction($order);
            if ($data) {
                $payment->setAdditionalInformation(
                    'BillsafeStatus', Netresearch_Billsafe_Model_Payment::BILLSAFE_STATUS_ACTIVE
                );
                $payment->setAdditionalInformation(
                    'Recipient', $data->recipient
                );
                $payment->setAdditionalInformation('BankCode', $data->bankCode);
                $payment->setAdditionalInformation(
                    'AccountNumber', $data->accountNumber
                );
                $payment->setAdditionalInformation('BankName', $data->bankName);
                $payment->setAdditionalInformation('Bic', $data->bic);
                $payment->setAdditionalInformation('Iban', $data->iban);
                $payment->setAdditionalInformation(
                    'Reference', $data->reference
                );
                $payment->setAdditionalInformation('Amount', $data->amount);
                $payment->setAdditionalInformation(
                    'CurrencyCode', $data->currencyCode
                );
                $payment->setAdditionalInformation('Note', $data->note);
                $payment->setAdditionalInformation(
                    'legalNote', $data->legalNote
                );
            } else {
                $payment->setAdditionalInformation(
                    'BillsafeStatus', Netresearch_Billsafe_Model_Payment::BILLSAFE_STATUS_CANCELLED
                );
            }
            $payment->setDataChanges(true);
            $payment->save();
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->getHelper()->log(
                'exception during handling getPaymentInstruction. Message is %s', $message
            );
        }
    }

    /**
     * check if all physical items of order are shipped
     *
     * @param Mage_Sales_Model_Order $order - order of shipping
     * @return boolean - all physical items of order are shipped
     */
    protected function areAllPhysicalItemsShipped(Mage_Sales_Model_Order $order)
    {
        $shipmentCollection = $order->getShipmentsCollection();

        foreach ($order->getAllItems() as $item) {
            if ( ($item->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL
                || $item->getProduct()->getTypeId() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE)
            ) {
                continue;
            }

            //quick check on item to rise performance if order not shipped yet
            if ($item->canShip()) {
                return false;
            }

            //item is a subitem of a configurable product; shipment is linked with configurable product
            if (null !== $item->getParentItemId()) {
                continue;
            }

            //is item complete refunded and/or cancelled
            $qtyRefundedCanceled = $item->getQtyRefunded() + $item->getQtyCanceled();
            if ($item->getQtyOrdered() <= $qtyRefundedCanceled) {
                continue;
            }

            //real check if all items are shipped
            $itemsShipped = 0;
            foreach ($shipmentCollection as $shipment) {
                foreach ($shipment->getAllItems() as $shipmentItem) {
                    if ($item->getId() == $shipmentItem->getOrderItemId()) {
                        $itemsShipped += $shipmentItem->getQty();
                    }
                }
            }

            if ($itemsShipped < $item->getQtyOrdered() - $qtyRefundedCanceled) {
                //there is at lease one piece not shipped
                return false;
            }
        }

        return true;
    }

    /**
     * Add order items and coupon code from given order object to cart.
     *
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Checkout_Model_Cart $cart
     */
    public function restoreCart(Mage_Sales_Model_Order $order, Mage_Checkout_Model_Cart $cart)
    {
        if (Mage::helper('persistent/data')->isEnabled() && $cart->getItemsQty()) {
            // Persistent cart is enabled, no need to restore anything.
            return;
        }

        // Restore order items
        foreach ($order->getItemsCollection() as $orderItem) {
            try {
                $cart->addOrderItem($orderItem);
            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }
        }

        // Add coupon code
        if ($order->hasCouponCode()) {
            $cart
                ->getQuote()
                ->setCouponCode($order->getCouponCode())
                ->save();
        }

        $cart->save();
    }

    /**
     * Cancel order or, if not possible, at least set status accordingly.
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function cancelOrder(Mage_Sales_Model_Order $order)
    {
        $order->cancel();
        if (!$order->isCanceled()) {
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
        }
        $order->save();
    }

    public function cancelLastOrderAndRestoreCart(Mage_Checkout_Model_Session $session)
    {
        $order = Mage::getModel('sales/order')
            ->loadByIncrementId($session->getLastRealOrderId());
        $cart = Mage::getSingleton('checkout/cart');

        $this->restoreCart($order, $cart);
        $this->cancelOrder($order);
    }

    /**
     * Check if current order applies for BillSAFE Onsite Checkout.
     *
     * @return boolean
     */
    public function isBillsafeOnsiteCheckout(Mage_Sales_Model_Quote $quote = null)
    {
        if (!$quote) {
            $quote = $this->getHelper()->getQuotefromSession();
        }

        // onsite checkout must be enabled via config
        $onsiteConfig = Mage::getModel('billsafe/config')
            ->isBillSafeDirectEnabled($quote->getStoreId());
        // b2b orders (company is set in quote) must use redirect gateway
        $onsiteCompany = !Mage::helper('billsafe/customer')
            ->getCustomerCompany($quote);
        return ($onsiteConfig && $onsiteCompany);
    }

    /**
     * Obtain the first captured transaction for a given order.
     *
     * @param int $orderId
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    public function getCapturedTransaction($orderId)
    {
        /* @var $transactionCollection Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection */
        $transactionCollection = Mage::getModel('sales/order_payment_transaction')
            ->getCollection();
        return $transactionCollection
            ->addOrderIdFilter($orderId)
            ->addTxnTypeFilter(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE)
            ->getFirstItem();
    }

    /**
     * Caclulate invoiced base total minus all reported direct payments.
     *
     * @param Mage_Sales_Model_Order $order
     * @return number
     */
    public function getOpenPaymentAmount(Mage_Sales_Model_Order $order)
    {
        /* @var $directPaymentCollection Netresearch_Billsafe_Model_Resource_Direct_Payment_Collection */
        $directPaymentCollection = Mage::getModel('billsafe/direct_payment')
            ->getCollection();
        $amountReported = $directPaymentCollection
            ->addTotalReportAmount()
            ->setOrderFilter($order)
            ->getFirstItem()
            ->getData('base_total_report_amount');

        return ($order->getBaseTotalInvoiced() - $amountReported);
    }
}
