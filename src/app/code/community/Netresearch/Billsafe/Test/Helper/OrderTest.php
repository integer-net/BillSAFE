<?php

class Netresearch_Billsafe_Test_Helper_OrderTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function hasBillsafePayment()
    {
        $billsafeOrder          = Mage::getModel('sales/order');
        $billsafePayment        = Mage::getModel('sales/order_payment');
        $billsafeMethodInstance = new Varien_Object();
        $billsafeCode   = Netresearch_Billsafe_Model_Payment::CODE;

        $billsafeOrder->setPayment(
            $billsafePayment->setMethodInstance(
                $billsafeMethodInstance->setCode(
                    $billsafeCode
                )
            )
        );

        $checkmoOrder          = Mage::getModel('sales/order');
        $checkmoPayment        = Mage::getModel('sales/order_payment');
        $checkmoMethodInstance = new Varien_Object();
        $checkmoCode    = Mage::getModel('payment/method_checkmo')->getCode();

        $checkmoOrder->setPayment(
            $checkmoPayment->setMethodInstance(
                $checkmoMethodInstance->setCode(
                    $checkmoCode
                )
            )
        );

        $this->assertTrue(Mage::helper('billsafe/order')->hasBillsafePayment($billsafeOrder));
        $this->assertFalse(Mage::helper('billsafe/order')->hasBillsafePayment($checkmoOrder));
    }

    public function testPrevalidateOrder()
    {

    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testPrepareParamsForPrevalidateOrder()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $result = Mage::helper('billsafe/order')
            ->prepareParamsForPrevalidateOrder($quote);
        $this->assertArrayHasKey('order', $result);
        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('deliveryAddress', $result);

        $this->assertArrayHasKey('amount', $result['order']);
        $this->assertArrayHasKey('currencyCode', $result['order']);
        $this->assertEquals(
            $quote->getGrandTotal(), $result['order']['amount']
        );
        $this->assertEquals(
            $quote->getCurrencyCode(), $result['order']['currencyCode']
        );

        $this->assertArrayHasKey('firstname', $result['customer']);
        $this->assertArrayHasKey('lastname', $result['customer']);
        $this->assertArrayHasKey('street', $result['customer']);
        $this->assertArrayHasKey('postcode', $result['customer']);
        $this->assertArrayHasKey('city', $result['customer']);
        $this->assertArrayHasKey('country', $result['customer']);
        $this->assertArrayHasKey('email', $result['customer']);
        $this->assertArrayHasKey('company', $result['customer']);
        $this->assertArrayHasKey('phone', $result['customer']);

        $billingAddress = $quote->getBillingAddress();
        $this->assertEquals('', $result['customer']['firstname']);
        $this->assertEquals('', $result['customer']['lastname']);
        $this->assertEquals(
            $billingAddress->getStreetFull(), $result['customer']['street']
        );
        $this->assertEquals(
            $billingAddress->getPostcode(), $result['customer']['postcode']
        );
        $this->assertEquals(
            $billingAddress->getCity(), $result['customer']['city']
        );
        $this->assertEquals(
            $billingAddress->getCountry(), $result['customer']['country']
        );
        $this->assertEquals(
            $billingAddress->getCompany(), $result['customer']['company']
        );
        $this->assertEquals(
            $billingAddress->getTelephone(), $result['customer']['phone']
        );
        $this->assertEquals(
            $billingAddress->getEmail(), $result['customer']['email']
        );


        $this->assertEquals('', $result['deliveryAddress']['firstname']);
        $this->assertEquals('', $result['deliveryAddress']['lastname']);
        $this->assertEquals(
            $billingAddress->getStreetFull(),
            $result['deliveryAddress']['street']
        );
        $this->assertEquals(
            $billingAddress->getPostcode(),
            $result['deliveryAddress']['postcode']
        );
        $this->assertEquals(
            $billingAddress->getCity(), $result['deliveryAddress']['city']
        );
        $this->assertEquals(
            $billingAddress->getCountry(), $result['deliveryAddress']['country']
        );

        $quote = Mage::getModel('sales/quote')->load(2);
        $result = Mage::helper('billsafe/order')
            ->prepareParamsForPrevalidateOrder($quote);
        $this->assertEquals(
            'An der Tabaksmühle 3b', $result['deliveryAddress']['street']
        );
        $this->assertEquals('04229', $result['deliveryAddress']['postcode']);
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testPrepareParamsForProcessOrder()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $fakeOrder = Mage::getModel('sales/order');

        $helperMock = $this->getHelperMock(
            'billsafe/data', array('getQuotefromSession')
        );
        $helperMock->expects($this->any())
            ->method('getQuotefromSession')
            ->will($this->returnValue($quote));
        $this->replaceByMock('helper', 'billsafe/data', $helperMock);

        $customerHelperMock = $this->getHelperMock(
            'billsafe/customer', array('getCustomerGender', 'getCustomerDob')
        );
        $helperMock->expects($this->any())
            ->method('getCustomerGender')
            ->will($this->returnValue('m'));

        $customerHelperMock->expects($this->any())
            ->method('getCustomerDob')
            ->will($this->returnValue('1980-01-01'));

        $fakeArticleList = array(
            array(
                'number'          => 1,
                'name'            => 'TEST-ARTIKEL',
                'description'     => 'TEST-ARTIKEL',
                'type'            => 'goods',
                'quantity'        => 1,
                'quantityShipped' => 1,
                'netPrice'        => 19.99,
                'tax'             => 19.99,
            )
        );

        $this->replaceByMock(
            'helper', 'billsafe/customer', $customerHelperMock
        );

        $sessionMock = $this->getModelMock(
            'checkout/session', array('getSessionId', 'init', 'addHost')
        );

        $sessionMock->expects($this->any())
            ->method('getSessionId')
            ->will($this->returnValue('12345678'));
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $orderHelperMock = $this->getHelperMock(
            'billsafe/order', array('buildArticleList')
        );
        $orderHelperMock->expects($this->any())
            ->method('buildArticleList')
            ->will($this->returnValue($fakeArticleList));


        $result = $orderHelperMock->prepareParamsForProcessOrder(
            $quote, $fakeOrder
        );

        $this->assertArrayHasKey('order', $result);
        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('articleList', $result);
        $this->assertArrayHasKey('product', $result);
        $this->assertArrayHasKey('sessionId', $result);

        $orderData = $result['order'];
        $this->assertParamsForOrder($orderData);
        $customerData = $result['customer'];
        $this->assertParamsForCustomer($quote, $customerData, $result);
        $articleList = $result['articleList'];
        $this->assertParamsForArticleList($articleList);
        $this->assertEquals('invoice', $result['product']);
        $this->assertEquals(md5('12345678'), $result['sessionId']);

        $quote = Mage::getModel('sales/quote')->load(2);
        $result = $orderHelperMock->prepareParamsForProcessOrder(
            $quote, $fakeOrder
        );
        $this->assertArrayNotHasKey('dateOfBirth', $result['customer']);

    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testPrepareParamsForProcessOrderWithDobFromSession()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $fakeOrder = Mage::getModel('sales/order');

        $helperMock = $this->getHelperMock(
            'billsafe/data', array('getQuotefromSession')
        );
        $helperMock->expects($this->any())
            ->method('getQuotefromSession')
            ->will($this->returnValue($quote));
        $this->replaceByMock('helper', 'billsafe/data', $helperMock);

        $customerHelperMock = $this->getHelperMock(
            'billsafe/customer', array('getCustomerGender', 'getCustomerDob')
        );
        $helperMock->expects($this->any())
            ->method('getCustomerGender')
            ->will($this->returnValue('m'));

        $fakeArticleList = array(
            array(
                'number'          => 1,
                'name'            => 'TEST-ARTIKEL',
                'description'     => 'TEST-ARTIKEL',
                'type'            => 'goods',
                'quantity'        => 1,
                'quantityShipped' => 1,
                'netPrice'        => 19.99,
                'tax'             => 19.99,
            )
        );

        $this->replaceByMock(
            'helper', 'billsafe/customer', $customerHelperMock
        );

        $sessionMock = $this->getModelMock(
            'checkout/session', array('getSessionId', 'init', 'addHost', 'getData')
        );

        $sessionMock->expects($this->any())
            ->method('getSessionId')
            ->will($this->returnValue('12345678'));

        $sessionMock->expects($this->any())
            ->method('getData')
            ->will($this->returnValue('1980-01-01'));


        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $orderHelperMock = $this->getHelperMock(
            'billsafe/order', array('buildArticleList')
        );
        $orderHelperMock->expects($this->any())
            ->method('buildArticleList')
            ->will($this->returnValue($fakeArticleList));


        $result = $orderHelperMock->prepareParamsForProcessOrder(
            $quote, $fakeOrder
        );

        $this->assertArrayHasKey('order', $result);
        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('articleList', $result);
        $this->assertArrayHasKey('product', $result);
        $this->assertArrayHasKey('sessionId', $result);

        $orderData = $result['order'];
        $this->assertParamsForOrder($orderData);
        $customerData = $result['customer'];
        $this->assertParamsForCustomer($quote, $customerData, $result);
        $articleList = $result['articleList'];
        $this->assertParamsForArticleList($articleList);
        $this->assertEquals('invoice', $result['product']);
        $this->assertEquals(md5('12345678'), $result['sessionId']);
    }

    protected function assertParamsForOrder($orderData)
    {
        $this->assertTrue(is_array($orderData));
        $this->assertArrayHasKey('amount', $orderData);
        $this->assertArrayHasKey('currencyCode', $orderData);
        $this->assertArrayHasKey('taxAmount', $orderData);
        $this->assertEquals(119, $orderData['amount']);
    }

    protected function assertParamsForCustomer($quote, $customerData, $result)
    {
        $this->assertTrue(is_array($customerData));
        $this->assertArrayHasKey('firstname', $customerData);
        $this->assertArrayHasKey('lastname', $customerData);
        $this->assertArrayHasKey('street', $customerData);
        $this->assertArrayHasKey('postcode', $customerData);
        $this->assertArrayHasKey('city', $customerData);
        $this->assertArrayHasKey('country', $customerData);
        $this->assertArrayHasKey('email', $customerData);
        $this->assertArrayHasKey('gender', $customerData);
        $this->assertArrayHasKey('dateOfBirth', $customerData);
        $this->assertEquals(
            $quote->getBillingAddress()->getFirstname(),
            $customerData['firstname']
        );
        $this->assertEquals(
            $quote->getBillingAddress()->getLastname(),
            $customerData['lastname']
        );
        $this->assertEquals(
            $quote->getBillingAddress()->getStreetFull(),
            $customerData['street']
        );
        $this->assertEquals(
            $quote->getBillingAddress()->getPostcode(),
            $customerData['postcode']
        );
        $this->assertEquals(
            $quote->getBillingAddress()->getCity(), $customerData['city']
        );
        $this->assertEquals(
            $quote->getBillingAddress()->getCountry(),
            $result['customer']['country']
        );
        $this->assertEquals(
            $quote->getBillingAddress()->getEmail(),
            $result['customer']['email']
        );

    }

    protected function assertParamsForArticleList($articleList)
    {
        $this->assertTrue(is_array($articleList));
        $article = current($articleList);
        $this->assertEquals(1, $article['number']);
        $this->assertEquals('TEST-ARTIKEL', $article['name']);
        $this->assertEquals('TEST-ARTIKEL', $article['description']);
        $this->assertEquals('goods', $article['type']);
        $this->assertEquals(1, $article['quantity']);
        $this->assertEquals(1, $article['quantityShipped']);
        $this->assertEquals(19.99, $article['netPrice']);
        $this->assertEquals(19.99, $article['tax']);
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testGetShippingTaxPercent()
    {
        $order = new Varien_Object();
        $order->setShippingTaxAmount(19);
        $order->setShippingAmount(2);
        $helper = Mage::helper('billsafe/order');
        $method = self::runProtectedMethod('getShippingTaxPercent', $helper);
        $this->assertEquals(950, $method->invoke($helper, $order));
        $this->assertNotEquals(1950, $method->invoke($helper, $order));
    }

    public function testGetShippingNetPrice()
    {
        $order = new Varien_Object();
        $order->setShippingInclTax(30);
        $order->setShippingTaxAmount(10);
        $order->setShippingRefunded(10);
        $order->setShippingCanceled(5);
        $helper = Mage::helper('billsafe/order');
        $method = self::runProtectedMethod('getShippingNetPrice', $helper);
        $this->assertEquals(5, $method->invoke($helper, $order));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testGetAllOrderItems()
    {
        $contex = Netresearch_Billsafe_Helper_Order::TYPE_RS;
        $order = Mage::getModel('sales/order')->load(11);
        $helper = Mage::helper('billsafe/order');
        $method = self::runProtectedMethod('getAllOrderItems', $helper);
        $this->assertEquals(
            $order->getAllItems(),
            $method->invoke($helper, $order, $order, $contex)
        );
        $this->assertEquals(
            $order->getAllItems(),
            $method->invoke($helper, $order, $order, null)
        );
    }

    public function testGetDiscountItemData()
    {
        $order = new Varien_Object();
        $order->setDiscountAmount(-2);
        $order->setDiscountDescription('test');
        $helper = Mage::helper('billsafe/order');
        $method = self::runProtectedMethod('getDiscountItemData', $helper);
        //context not refund
        $discountItemData = $method->invoke($helper, $order, null);
        // array keys
        $this->assertArrayHasKey('number', $discountItemData);
        $this->assertArrayHasKey('name', $discountItemData);
        $this->assertArrayHasKey('type', $discountItemData);
        $this->assertArrayHasKey('quantity', $discountItemData);
        $this->assertArrayHasKey('netPrice', $discountItemData);
        $this->assertArrayHasKey('tax', $discountItemData);

        // key values
        $this->assertEquals('___discount___-test', $discountItemData['number']);
        $this->assertEquals('Discount test', $discountItemData['name']);
        $this->assertEquals('voucher', $discountItemData['type']);
        $this->assertEquals(1, $discountItemData['quantity']);
        $this->assertEquals(-2.0, $discountItemData['netPrice']);
        $this->assertEquals(0.00, $discountItemData['tax']);


        //context refund
        $discountItemData = $method->invoke(
            $helper, $order, Netresearch_Billsafe_Helper_Order::TYPE_RF
        );
        $this->assertEquals(0, count($discountItemData));
    }

    public function testGetAdjustmentData()
    {
        $order = new Varien_Object();
        $order->setAdjustmentPositive(2);
        $amount = 5;
        $helper = Mage::helper('billsafe/order');
        $method = self::runProtectedMethod('getAdjustmentData', $helper);
        $adjustmentData = array();
        $adjustmentData = $method->invoke($helper, $order, null, $amount);

        // array keys
        $this->assertArrayHasKey('data', $adjustmentData);
        $this->assertArrayHasKey('amount', $adjustmentData);
        $this->assertArrayHasKey('number', $adjustmentData['data']);
        $this->assertArrayHasKey('name', $adjustmentData['data']);
        $this->assertArrayHasKey('type', $adjustmentData['data']);
        $this->assertArrayHasKey('quantity', $adjustmentData['data']);
        $this->assertArrayHasKey('quantityShipped', $adjustmentData['data']);
        $this->assertArrayHasKey('netPrice', $adjustmentData['data']);
        $this->assertArrayHasKey('tax', $adjustmentData['data']);

        //values
        $this->assertEquals(
            '___adjustment__', $adjustmentData['data']['number']
        );
        $this->assertEquals('Creditmemo', $adjustmentData['data']['name']);
        $this->assertEquals('voucher', $adjustmentData['data']['type']);
        $this->assertEquals(1, $adjustmentData['data']['quantity']);
        $this->assertEquals(1, $adjustmentData['data']['quantityShipped']);
        $this->assertEquals(-2.0, $adjustmentData['data']['netPrice']);
        $this->assertEquals(0.00, $adjustmentData['data']['tax']);

        $this->assertEquals(3, $adjustmentData['amount']);

        $adjustmentData = $method->invoke(
            $helper, $order, Netresearch_Billsafe_Helper_Order::TYPE_RS, $amount
        );
        $this->assertEquals(0, count($adjustmentData));
    }

    public function testGetShippingItemData()
    {
        $orderHelperMock = $this->getHelperMock(
            'billsafe/order', array(
                                   'getRemainingShipmentItemQty',
                                   'getShippingNetPrice',
                                   'getShippingTaxPercent',
                              )
        );
        $orderHelperMock->expects($this->any())
            ->method('getRemainingShipmentItemQty')
            ->will($this->returnValue(2));

        $orderHelperMock->expects($this->any())
            ->method('getShippingNetPrice')
            ->will($this->returnValue(10));

        $orderHelperMock->expects($this->any())
            ->method('getShippingTaxPercent')
            ->will($this->returnValue(19));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $order = Mage::getModel('sales/order');
        $order->setShippingInclTax(10);
        $order->setShippingDescription('test');

        $helper = Mage::helper('billsafe/order');
        $method = self::runProtectedMethod('getShippingItemData', $helper);

        $shippingItemData = $method->invoke($helper, $order, null);

        // array keys
        $this->assertArrayHasKey('number', $shippingItemData);
        $this->assertArrayHasKey('name', $shippingItemData);
        $this->assertArrayHasKey('description', $shippingItemData);
        $this->assertArrayHasKey('type', $shippingItemData);
        $this->assertArrayHasKey('quantity', $shippingItemData);
        $this->assertArrayHasKey('quantityShipped', $shippingItemData);
        $this->assertArrayHasKey('netPrice', $shippingItemData);
        $this->assertArrayHasKey('tax', $shippingItemData);

        // key values
        $this->assertEquals('___shipment___', $shippingItemData['number']);
        $this->assertEquals('Shipment', $shippingItemData['name']);
        $this->assertEquals('test', $shippingItemData['description']);
        $this->assertEquals('shipment', $shippingItemData['type']);
        $this->assertEquals(2, $shippingItemData['quantity']);
        $this->assertEquals(2, $shippingItemData['quantityShipped']);

        $this->assertEquals(10.0, $shippingItemData['netPrice']);
        $this->assertEquals(19.00, $shippingItemData['tax']);

        $order->setShippingInclTax(0);
        $shippingItemData = $method->invoke($helper, $order, null);
        $this->assertEquals(0, count($shippingItemData));

    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testGetRemainingShipmentItemQty()
    {
        $order = Mage::getModel('sales/order')->load(11);
        $orderHelper = Mage::helper('billsafe/order');
        $this->assertEquals(
            1, $orderHelper->getRemainingShipmentItemQty(
                $order, Netresearch_Billsafe_Helper_Order::TYPE_PI
            )
        );
        $this->assertEquals(
            1, $orderHelper->getRemainingShipmentItemQty(
                $order, Netresearch_Billsafe_Helper_Order::TYPE_VO
            )
        );
        $this->assertEquals(
            0, $orderHelper->getRemainingShipmentItemQty(
                $order, Netresearch_Billsafe_Helper_Order::TYPE_RF
            )
        );
        $this->assertEquals(
            0, $orderHelper->getRemainingShipmentItemQty($order, null)
        );

        $order->setShippingAmount(5);
        $order->setShippingRefunded(2);

        $this->assertEquals(
            1, $orderHelper->getRemainingShipmentItemQty($order, null)
        );

        $order = Mage::getModel('sales/order')->load(12);
        $order->setShippingAmount(2);
        $order->setShippingRefunded(2);
        $this->assertEquals(
            0, $orderHelper->getRemainingShipmentItemQty($order, null)
        );


    }


    public function testBuildArticleListReturnsEmptyArray()
    {
        $helperMock = Mage::helper('billsafe/order');
        $order = Mage::getModel('sales/order');
        $this->assertEquals(
            array(), $helperMock->buildArticleList(
                $order, Netresearch_Billsafe_Helper_Order::TYPE_PI
            )
        );
    }

    public function testBuildArticleListThrowsExceptionOnRfWithNegativeAdjustment()
    {
        $helperMock = Mage::helper('billsafe/order');
        $order = Mage::getModel('sales/order');
        $order->setAdjustmentNegative(1);
        try {
            $helperMock->buildArticleList(
                $order, Netresearch_Billsafe_Helper_Order::TYPE_RF
            );
        } catch (Exception $e) {
            $this->assertEquals(
                Mage::helper('billsafe/data')->__(
                    'Add adjustment fees is not supported by BillSAFE'
                ), $e->getMessage()
            );
        }
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testBuildArticleListWithOrder()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('model', 'checkout/session', $sessionMock);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data', array('getStoreIdfromQuote')
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(1));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $helperMock = $this->getHelperMock(
            'billsafe/order', array('isFeeItem', 'getOrderItemData')
        );
        $helperMock->expects($this->any())
            ->method('isFeeItem')
            ->will($this->returnValue(false));
        $orderItems = array(
            'data' => array(
                'number'          => substr('4711', 0, 50),
                'name'            => '4711',
                'description'     => '4711',
                'type'            => 'goods',
                'quantity'        => 1,
                'quantityShipped' => 1,
                'netPrice'        => 22.00,
                'tax'             => 19.00
            ));
        $helperMock->expects($this->any())
            ->method('getOrderItemData')
            ->will(
                $this->returnValue(
                    array_merge(
                        $orderItems,
                        array('amount'           => 41.00,
                              'tax_amount'       => 19.00,
                              'payment_fee_item' => new Varien_Object()
                        )
                    )
                )
            );
        $order = Mage::getModel('sales/order')->load(13);

        $result = $helperMock->buildArticleList(
            $order, Netresearch_Billsafe_Helper_Order::TYPE_PO
        );
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result));
        $this->assertEquals($orderItems['data'], $result);

    }


    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testBuildArticleListWithOrderAndShipment()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('model', 'checkout/session', $sessionMock);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data', array('getStoreIdfromQuote')
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(1));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $helperMock = $this->getHelperMock(
            'billsafe/order',
            array('isFeeItem', 'getOrderItemData', 'getShippingItemData',
                  'getShippingTaxPercent')
        );
        $helperMock->expects($this->any())
            ->method('isFeeItem')
            ->will($this->returnValue(false));
        $orderItems = array(
            'data' => array(array(
                'number'          => substr('4711', 0, 50),
                'name'            => '4711',
                'description'     => '4711',
                'type'            => 'goods',
                'quantity'        => 1,
                'quantityShipped' => 1,
                'netPrice'        => 22.00,
                'tax'             => 19.00
            )));

        $shippingItem = array(
            'number'          => '___shipment___',
            'name'            => 'Shipment',
            'description'     => 'shipment',
            'type'            => 'shipment',
            'quantity'        => 1,
            'quantityShipped' => 1,
            'netPrice'        => 6.90,
            'tax'             => 19.00,
        );

        $helperMock->expects($this->any())
            ->method('getOrderItemData')
            ->will(
                $this->returnValue(
                    array_merge(
                        $orderItems,
                        array('amount'     => 41.00,
                              'tax_amount' => 19.00
                        )
                    )
                )
            );

        $helperMock->expects($this->any())
            ->method('getShippingItemData')
            ->will($this->returnValue($shippingItem));

        $helperMock->expects($this->any())
            ->method('getShippingTaxPercent')
            ->will($this->returnValue(19));
        $order = Mage::getModel('sales/order')->load(13);
        $order->setShippingAmount(5.90);
        $order->setShippingRefunded(0.00);
        $order->setTaxAmount(0.00);
        $entity = Mage::getModel('sales/order');
        $entity->setOrder($order);
        $result = $helperMock->buildArticleList(
            $entity, Netresearch_Billsafe_Helper_Order::TYPE_RF
        );
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array($result));
        $this->assertEquals(current($orderItems['data']), current($result));
        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('tax_amount', $result);
        $this->assertEquals(48, $result['amount']);
        $this->assertEquals(61.1, $result['tax_amount']);

    }


    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testBuildArticleListWithOrderAndDiscountAmount()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('model', 'checkout/session', $sessionMock);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data', array('getStoreIdfromQuote')
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(1));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $helperMock = $this->getHelperMock(
            'billsafe/order',
            array('isFeeItem', 'getOrderItemData', 'getDiscountItemData')
        );
        $helperMock->expects($this->any())
            ->method('isFeeItem')
            ->will($this->returnValue(false));
        $orderItems = array(
            'data' => array(array(
                'number'          => substr('4711', 0, 50),
                'name'            => '4711',
                'description'     => '4711',
                'type'            => 'goods',
                'quantity'        => 1,
                'quantityShipped' => 1,
                'netPrice'        => 22.00,
                'tax'             => 19.00
            )));
        $helperMock->expects($this->any())
            ->method('getOrderItemData')
            ->will(
                $this->returnValue(
                    array_merge(
                        $orderItems,
                        array('amount'     => 41.00,
                              'tax_amount' => 19.00
                        )
                    )
                )
            );

        $discountData =  array(
            'number'   => '___discount___-' . 'discount',
            'name'     => 'Discount ' . 'discount',
            'type'     => 'voucher',
            'quantity' => 1,
            'netPrice' => round(5.00, 2),
            'tax'      => 0.00,
        );

        $helperMock->expects($this->any())
            ->method('getDiscountItemData')
            ->will($this->returnValue($discountData));


        $order = Mage::getModel('sales/order')->load(13);
        $order->setDiscountAmount(5.00);
        $result = $helperMock->buildArticleList(
            $order, Netresearch_Billsafe_Helper_Order::TYPE_PO
        );
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array(current($result)));
        $this->assertEquals(current($orderItems['data']), current($result));
        $this->assertEquals($discountData, end($result));

    }


    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testBuildArticleListWithOrderAndAdjustment()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('model', 'checkout/session', $sessionMock);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data', array('getStoreIdfromQuote')
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(1));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $helperMock = $this->getHelperMock(
            'billsafe/order',
            array('isFeeItem', 'getOrderItemData', 'getAdjustmentData')
        );
        $helperMock->expects($this->any())
            ->method('isFeeItem')
            ->will($this->returnValue(false));
        $orderItems = array(
            'data' => array(
                    array(
                        'number'          => substr('4711', 0, 50),
                        'name'            => '4711',
                        'description'     => '4711',
                        'type'            => 'goods',
                        'quantity'        => 1,
                        'quantityShipped' => 1,
                        'netPrice'        => 22.00,
                        'tax'             => 19.00
            )));
        $helperMock->expects($this->any())
            ->method('getOrderItemData')
            ->will(
                $this->returnValue(
                    array_merge(
                        $orderItems,
                        array('amount'     => 41.00,
                              'tax_amount' => 19.00
                        )
                    )
                )
            );

        $adjustmentData['data'] = array(
            'number'   => '___adjustment__',
            'name'     => 'Creditmemo',
            'type'     => 'voucher',
            'quantity' => 1,
            'netPrice' => round(5.00, 2),
            'tax'      => 0.00,
        );
        $adjustmentData['amount'] = 36.00;

        $helperMock->expects($this->any())
            ->method('getAdjustmentData')
            ->will($this->returnValue($adjustmentData));


        $order = Mage::getModel('sales/order')->load(13);
        $order->setDiscountAmount(5.00);
        $result = $helperMock->buildArticleList(
            $order, Netresearch_Billsafe_Helper_Order::TYPE_PO
        );
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array(current($result)));
        $this->assertEquals(current($orderItems['data']), current($result));
        $this->assertEquals($adjustmentData['data'], end($result));
    }


    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testBuildArticleListWithOrderAndPaymentFee()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('model', 'checkout/session', $sessionMock);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data', array('getStoreIdfromQuote')
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(1));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $helperMock = $this->getHelperMock(
            'billsafe/order',
            array('isFeeItem', 'getOrderItemData', 'getPaymentFeeData')
        );
        $helperMock->expects($this->any())
            ->method('isFeeItem')
            ->will($this->returnValue(false));
        $orderItems = array(
            'data' => array(
                        array(
                            'number'          => substr('4711', 0, 50),
                            'name'            => '4711',
                            'description'     => '4711',
                            'type'            => 'goods',
                            'quantity'        => 1,
                            'quantityShipped' => 1,
                            'netPrice'        => 22.00,
                            'tax'             => 19.00
            )));
        $helperMock->expects($this->any())
            ->method('getOrderItemData')
            ->will(
                $this->returnValue(
                    array_merge(
                        $orderItems,
                        array('amount'     => 41.00,
                              'tax_amount' => 19.00
                        )
                    )
                )
            );

        $paymentFeeData['data'] = array(
            'number'   => '___adjustment__',
            'name'     => 'Creditmemo',
            'type'     => 'voucher',
            'quantity' => 1,
            'netPrice' => round(5.00, 2),
            'tax'      => 0.00,
        );
        $paymentFeeData['amount'] = 36.00;
        $paymentFeeData['tax_amount'] = 8.00;

        $helperMock->expects($this->any())
            ->method('getPaymentFeeData')
            ->will($this->returnValue($paymentFeeData));


        $order = Mage::getModel('sales/order')->load(13);
        $order->setDiscountAmount(5.00);
        $result = $helperMock->buildArticleList(
            $order, Netresearch_Billsafe_Helper_Order::TYPE_PO
        );
        $this->assertTrue(is_array($result));
        $this->assertEquals(current($orderItems['data']), current($result));
        $this->assertEquals($paymentFeeData['data'], end($result));
    }


    public function testGetPaymentFeeData()
    {
        $helper = Mage::helper('billsafe/order');
        $method = self::runProtectedMethod('getPaymentFeeData', $helper);
        $paymentFeeData = $method->invoke($helper, null, null, null, null);
        $this->assertEquals(0, count($paymentFeeData));

        $paymentFeeItem = new Varien_Object();
        $paymentFeeItem->setQtyOrdered(1);
        $paymentFeeItem->setQtyRefunded(1);
        $paymentFeeItem->setQtyCanceled(0);
        $paymentFeeData = $method->invoke(
            $helper, $paymentFeeItem, null, null, null
        );
        $this->assertEquals(0, count($paymentFeeData));
        $paymentFeeItem->setQtyOrdered(1);
        $paymentFeeItem->setQtyRefunded(0);
        $paymentFeeItem->setQtyCanceled(0);
        $paymentFeeItem->setName('Fee');
        $paymentFeeItem->setRowTotal(10.00);
        $paymentFeeItem->setTaxAmount(19.00);
        $paymentFeeItem->setPriceInclTax(10.00);
        $paymentFeeData = $method->invoke(
            $helper, $paymentFeeItem, null, 10, 1
        );

        $this->assertEquals(3, count($paymentFeeData));
        $this->assertArrayHasKey('data', $paymentFeeData);
        $this->assertArrayHasKey('amount', $paymentFeeData);
        $this->assertArrayHasKey('tax_amount', $paymentFeeData);
        $this->assertEquals('___fee___', $paymentFeeData['data']['number']);
        $this->assertEquals(
            $paymentFeeItem->getName(), $paymentFeeData['data']['name']
        );
        $this->assertEquals('handling', $paymentFeeData['data']['type']);
        $this->assertEquals(1, $paymentFeeData['data']['quantity']);
        $this->assertEquals(
            Mage::helper('billsafe/data')->format(
                $paymentFeeItem->getRowTotal()
            ), $paymentFeeData['data']['netPrice']
        );
        $this->assertEquals(
            Mage::helper('billsafe/data')->format(
                $paymentFeeItem->getTaxPercent()
            ), $paymentFeeData['data']['tax']
        );
        $this->assertEquals(20, $paymentFeeData['amount']);
        $this->assertEquals(20, $paymentFeeData['tax_amount']);

        $paymentFeeData = $method->invoke(
            $helper, $paymentFeeItem,
            Netresearch_Billsafe_Helper_Order::TYPE_PI, 10, 1
        );
        $this->assertEquals(1, $paymentFeeData['data']['quantityShipped']);

    }


    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testGetPreparedOrderParams()
    {
        $order = Mage::getModel('sales/order')->load(13);
        $billingAddress = $this->getBillingAddress();
        $order->setBillingAddress($billingAddress);
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $sessionMock = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $sessionMock);

        $fakeQuote = Mage::getModel('sales/quote');
        $billingAddressQuote = $this->getBillingAddress(false);
        $billingAddressQuote->setEmail('a@b.com');
        $fakeQuote->setBillingAddress($billingAddressQuote);
        $cartMock = $this->getModelMock('checkout/cart', array('getQuote'));
        $cartMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($fakeQuote));
        $this->replaceByMock('model', 'checkout/cart', $cartMock);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data', array('coalesce')
        );
        $dataHelperMock->expects($this->any())
            ->method('coalesce')
            ->will($this->returnValue('a@b.com'));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $customerHelperMock = $this->getHelperMock(
            'billsafe/customer', array('getCustomerGender', 'getCustomerDob')
        );
        $customerHelperMock->expects($this->any())
            ->method('getCustomerGender')
            ->will($this->returnValue('m'));
        $customerHelperMock->expects($this->any())
            ->method('getCustomerDob')
            ->will($this->returnValue('1981-01-01'));
        $this->replaceByMock(
            'helper', 'billsafe/customer', $customerHelperMock
        );

        $orderHelperMock = $this->getHelperMock(
            'billsafe/order', array('buildArticleList')
        );
        $orderHelperMock->expects($this->any())
            ->method('buildArticleList')
            ->will($this->returnValue(array('name' => 'item 1')));

        $customer = Mage::getModel('customer/customer');
        $order->setCustomer($customer);

        $result = $orderHelperMock->getPreparedOrderParams($order);

        $this->resultAssertions($result, $order, $dataHelperMock,
            $billingAddress, $orderHelperMock, $customerHelperMock, $customer
        );


    }


    protected function resultAssertions($result, $order, $dataHelperMock,
        $billingAddress, $orderHelperMock, $customerHelperMock, $customer
    )
    {
        $this->assertArrayHasKey('order', $result);
        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('articleList', $result);
        $this->assertArrayHasKey('product', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals(
            $order->getIncrementId(), $result['order']['number']
        );
        $this->assertEquals(
            $dataHelperMock->format($order->getGrandTotal()),
            $result['order']['amount']
        );
        $this->assertEquals(
            $dataHelperMock->format($order->getTaxAmount()),
            $result['order']['taxAmount']
        );
        $this->assertEquals('EUR', $result['order']['currencyCode']);
        $this->assertEquals(
            $billingAddress->getCompany(), $result['customer']['company']
        );
        $this->assertEquals(
            $customerHelperMock->getCustomerGender($customer, $order, null),
            $result['customer']['gender']
        );
        $this->assertEquals(
            $billingAddress->getFirstname(), $result['customer']['firstname']
        );
        $this->assertEquals(
            $billingAddress->getLastname(), $result['customer']['lastname']
        );
        $this->assertEquals(
            implode(' ', $billingAddress->getStreet()),
            $result['customer']['street']
        );
        $this->assertEquals(
            $billingAddress->getPostcode(), $result['customer']['postcode']
        );

        $this->assertEquals(
            $billingAddress->getCity(), $result['customer']['city']
        );
        $this->assertEquals($billingAddress->getCountry(), $result['customer']['country']);
        $this->assertEquals('a@b.com', $result['customer']['email']);
        if (0 === strlen(trim($billingAddress->getCompany()))) {
            $this->assertEquals('1981-01-01', $result['customer']['dateOfBirth']);
        }
        $this->assertEquals(
            $billingAddress->getTelephone(), $result['customer']['phone']
        );
        $this->assertEquals(
            $orderHelperMock->buildArticleList(
                $order, Netresearch_Billsafe_Helper_Order::TYPE_PO
            ), $result['articleList']
        );
        $this->assertEquals('invoice', $result['product']);
        $this->assertEquals(
            Mage::getUrl('billsafe/payment/verify'), $result['url']['return']
        );
        $this->assertEquals(
            Mage::getUrl('billsafe/payment/cancellation'),
            $result['url']['cancel']
        );
        $result = $orderHelperMock->getPreparedOrderParams(
            $order, 'foo', 'bar'
        );
        $this->assertEquals('foo', $result['url']['return']);
        $this->assertEquals('bar', $result['url']['cancel']);
    }

    protected function getBillingAddress($useForOrder = true)
    {
        if ($useForOrder) {
            $billingAddress = Mage::getModel('sales/order_address');
        } else {
            $billingAddress = Mage::getModel('sales/quote_address');
        }
        $billingAddress->setStreet('Nonnenstr. 11');
        $billingAddress->setCompany('NR');
        $billingAddress->setFirstname('Net');
        $billingAddress->setLastname('Research');
        $billingAddress->setPostcode('04229');
        $billingAddress->setCity('Leipzig');
        $billingAddress->setTelephone('123456');
        return $billingAddress;
    }

    protected static function runProtectedMethod($name, $object)
    {
        $class = new ReflectionClass(get_class($object));
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testBuildArticleWithRsContextAndNonVirtualArticles()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('model', 'checkout/session', $sessionMock);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data', array('getStoreIdfromQuote')
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(1));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $helperMock = $this->getHelperMock(
            'billsafe/order',
            array('isFeeItem', 'getOrderItemData', 'getDiscountItemData', 'areAllPhysicalItemsShipped', 'getVirtualItemData')
        );
        $helperMock->expects($this->any())
            ->method('isFeeItem')
            ->will($this->returnValue(false));
        $helperMock->expects($this->any())
            ->method('areAllPhysicalItemsShipped')
            ->will($this->returnValue(true));
        $helperMock->expects($this->any())
            ->method('getVirtualItemData')
            ->will($this->returnValue(array('data' => array())));

        $orderItems = array(
            'data' => array(array(
                                'number'          => substr('4711', 0, 50),
                                'name'            => '4711',
                                'description'     => '4711',
                                'type'            => 'goods',
                                'quantity'        => 1,
                                'quantityShipped' => 1,
                                'netPrice'        => 22.00,
                                'tax'             => 19.00
                            )));
        $helperMock->expects($this->any())
            ->method('getOrderItemData')
            ->will(
                $this->returnValue(
                    array_merge(
                        $orderItems,
                        array('amount'     => 41.00,
                              'tax_amount' => 19.00
                        )
                    )
                )
            );

        $discountData =  array(
            'number'   => '___discount___-' . 'discount',
            'name'     => 'Discount ' . 'discount',
            'type'     => 'voucher',
            'quantity' => 1,
            'netPrice' => round(5.00, 2),
            'tax'      => 0.00,
        );

        $helperMock->expects($this->any())
            ->method('getDiscountItemData')
            ->will($this->returnValue($discountData));


        $order = Mage::getModel('sales/order')->load(13);
        $order->setDiscountAmount(5.00);
        $fakeShipment = Mage::getModel('sales/order_shipment');
        $fakeShipment->setOrder($order);
        $result = $helperMock->buildArticleList(
            $fakeShipment, Netresearch_Billsafe_Helper_Order::TYPE_RS
        );
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array(current($result)));
        $this->assertEquals(current($orderItems['data']), current($result));
        $this->assertEquals($discountData, end($result));

    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testBuildArticleWithRsContextAndVirtualArticles()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('model', 'checkout/session', $sessionMock);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data', array('getStoreIdfromQuote')
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(1));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $helperMock = $this->getHelperMock(
            'billsafe/order',
            array('isFeeItem', 'getOrderItemData', 'getDiscountItemData', 'areAllPhysicalItemsShipped', 'getVirtualItemData')
        );

        $virtOrderItems = array(
            'data' => array(array(
                                'number'          => substr('4711', 0, 50),
                                'name'            => '4711',
                                'description'     => '4711',
                                'type'            => 'goods',
                                'quantity'        => 1,
                                'quantityShipped' => 1,
                                'netPrice'        => 22.00,
                                'tax'             => 19.00
                            )),
            'amount' => 22.00,
            'tax_amount' => 4.18);

        $helperMock->expects($this->any())
            ->method('isFeeItem')
            ->will($this->returnValue(false));
        $helperMock->expects($this->any())
            ->method('areAllPhysicalItemsShipped')
            ->will($this->returnValue(true));
        $helperMock->expects($this->any())
            ->method('getVirtualItemData')
            ->will($this->returnValue($virtOrderItems));

        $orderItems = array(
            'data' => array(array(
                                'number'          => substr('4711', 0, 50),
                                'name'            => '4711',
                                'description'     => '4711',
                                'type'            => 'goods',
                                'quantity'        => 1,
                                'quantityShipped' => 1,
                                'netPrice'        => 22.00,
                                'tax'             => 19.00
                            )));
        $helperMock->expects($this->any())
            ->method('getOrderItemData')
            ->will(
                $this->returnValue(
                    array_merge(
                        $orderItems,
                        array('amount'     => 41.00,
                              'tax_amount' => 19.00
                        )
                    )
                )
            );

        $discountData =  array(
            'number'   => '___discount___-' . 'discount',
            'name'     => 'Discount ' . 'discount',
            'type'     => 'voucher',
            'quantity' => 1,
            'netPrice' => round(5.00, 2),
            'tax'      => 0.00,
        );

        $helperMock->expects($this->any())
            ->method('getDiscountItemData')
            ->will($this->returnValue($discountData));


        $order = Mage::getModel('sales/order')->load(13);
        $order->setDiscountAmount(5.00);
        $fakeShipment = Mage::getModel('sales/order_shipment');
        $fakeShipment->setOrder($order);
        $result = $helperMock->buildArticleList(
            $fakeShipment, Netresearch_Billsafe_Helper_Order::TYPE_RS
        );
        $this->assertTrue(is_array($result));
        $this->assertTrue(is_array(current($result)));
        $this->assertEquals(current($orderItems['data']), current($result));
        $this->assertEquals(3, count($result));
        $this->assertEquals(current($virtOrderItems['data']), $result[1]);
        $this->assertEquals($discountData, end($result));

    }

    /**
     * @loadFixture orders
     */
    public function testAreAllPhysicalItemsShipped() {
        //init
        /* @var $helperClass Netresearch_Billsafe_Helper_Order */
        $helperClass = Mage::helper('billsafe/order');

        //use reflection to test protected
        $reflectionClass = new ReflectionClass($helperClass);
        $method = $reflectionClass->getMethod('areAllPhysicalItemsShipped');
        $method->setAccessible(true);

        //test if all items are sent
        $order = Mage::getModel('sales/order')->load(1);
        $return = $method->invoke($helperClass, $order);
        $this->assertTrue($return);
        //test if some item is not sent
        $order = Mage::getModel('sales/order')->load(2);
        $return = $method->invoke($helperClass, $order);
        $this->assertFalse($return);

        //test magento thinks all shipped, but they aren't
        $order = Mage::getModel('sales/order')->load(3);
        $return = $method->invoke($helperClass, $order);
        $this->assertFalse($return);

        //test magento thinks all shipped, but they aren't
        $order = Mage::getModel('sales/order')->load(4);
        $return = $method->invoke($helperClass, $order);
        $this->assertFalse($return);

        //test magento thinks all shipped, but they aren't
        $order = Mage::getModel('sales/order')->load(5);
        $return = $method->invoke($helperClass, $order);
        $this->assertTrue($return);

        //test order contains a virtual article and a physical which is shipped
        $order = Mage::getModel('sales/order')->load(6);
        $return = $method->invoke($helperClass, $order);
        $this->assertTrue($return);
    }

    /**
     * @test
     */
    public function isBillsafeOnsiteCheckout()
    {
        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getQuotefromSession'));
        $dataHelperMock
            ->expects($this->any())
            ->method('getQuotefromSession')
            ->will($this->returnValue(Mage::getModel('sales/quote')))
        ;
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $customerHelperMock = $this->getHelperMock('billsafe/customer', array('getCustomerCompany'));
        $customerHelperMock
            ->expects($this->any())
            ->method('getCustomerCompany')
            ->will($this->onConsecutiveCalls('Foo GmbH', 'Foo GmbH', '', ''))
        ;
        $this->replaceByMock('helper', 'billsafe/customer', $customerHelperMock);

        $configMock = $this->getModelMock('billsafe/config', array('isBillSafeDirectEnabled'));
        $configMock
            ->expects($this->any())
            ->method('isBillSafeDirectEnabled')
            ->will($this->onConsecutiveCalls(true, false, true, false))
        ;
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        /* @var $orderHelper Netresearch_Billsafe_Helper_Order */
        $orderHelper = Mage::helper('billsafe/order');
        $this->assertFalse($orderHelper->isBillsafeOnsiteCheckout());
        $this->assertFalse($orderHelper->isBillsafeOnsiteCheckout());
        $this->assertTrue($orderHelper->isBillsafeOnsiteCheckout());
        $this->assertFalse($orderHelper->isBillsafeOnsiteCheckout());
    }

    /**
     * @test
     */
    public function getCapturedTransaction()
    {
        $orderId = 99;
        $firstItem = 'foo';

        $this->assertInstanceOf(
            'Mage_Sales_Model_Order_Payment_Transaction',
            Mage::helper('billsafe/order')->getCapturedTransaction($orderId)
        );

        $collectionMock = $this->getResourceModelMock(
            'sales/order_payment_transaction_collection',
            array('getFirstItem')
        );
        $collectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->will($this->returnValue($firstItem));
        $this->replaceByMock('resource_model', 'sales/order_payment_transaction_collection', $collectionMock);

        $this->assertEquals(
            $firstItem,
            Mage::helper('billsafe/order')->getCapturedTransaction($orderId)
        );
    }

    /**
     * @test
     */
    public function getOpenPaymentAmount()
    {
        $orderId = 99;
        $orderTotal     = 10.01;

        $amountReported = 1.01;
        $firstItem = new Varien_Object();
        $firstItem->setData('base_total_report_amount', $amountReported);

        $orderMock = $this->getModelMock('sales/order', array('getId', 'getBaseTotalInvoiced'));
        $orderMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));
        $orderMock
            ->expects($this->any())
            ->method('getBaseTotalInvoiced')
            ->will($this->returnValue($orderTotal));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $collectionMock = $this->getResourceModelMock('billsafe/direct_payment_collection', array('getFirstItem'));
        $collectionMock
            ->expects($this->any())
            ->method('getFirstItem')
            ->will($this->returnValue($firstItem));
        $this->replaceByMock('resource_model', 'billsafe/direct_payment_collection', $collectionMock);

        $this->assertEquals(
            $orderTotal - $amountReported,
            Mage::helper('billsafe/order')->getOpenPaymentAmount(Mage::getModel('sales/order'))
        );
    }

    /**
     * @test
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function cancelLastOrderAndRestoreCart()
    {
        $itemsCollection = array(Mage::getModel('sales/order_item'));
        $orderIncrementId = '100000011';
        $couponCode = 'foo.';

        // session mock
        $sessionMock = $this->getModelMock('checkout/session', array(
            'init',
            'getLastRealOrderId',
        ));
        $sessionMock->expects($this->any())
            ->method('getLastRealOrderId')
            ->will($this->returnValue($orderIncrementId));
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        // quote mock
        $quoteMock = $this->getModelMock('sales/quote', array(
            'save',
        ));
        $quoteMock->expects($this->any())->method('save')->will($this->returnSelf());
        $this->replaceByMock('model', 'sales/quote', $quoteMock);

        // cart mock
        $cartMock = $this->getModelMock('checkout/cart', array(
            'getItemsQty',
            'getQuote',
            'save',
        ));
        $cartMock->expects($this->any())->method('getItemsQty')->will($this->returnValue(0));
        $cartMock->expects($this->any())->method('getQuote')->will($this->returnValue(Mage::getModel('sales/quote')->load(1)));
        $cartMock->expects($this->any())->method('save')->will($this->returnSelf());
        $this->replaceByMock('singleton', 'checkout/cart', $cartMock);


        $orderMock = $this->getModelMock('sales/order', array(
            'getItemsCollection',
            'hasCouponCode',
            'getCouponCode',
            'cancel',
            'isCanceled',
        ));
        $orderMock->expects($this->any())->method('getItemsCollection')->will($this->returnValue($itemsCollection));
        $orderMock->expects($this->any())->method('hasCouponCode')->will($this->returnValue((bool)$couponCode));
        $orderMock->expects($this->any())->method('getCouponCode')->will($this->returnValue($couponCode));
        $orderMock->expects($this->any())->method('cancel')->will($this->returnSelf());
        $orderMock->expects($this->any())->method('isCanceled')->will($this->returnValue(false));
        $this->replaceByMock('model', 'sales/order', $orderMock);


        /* @var $orderHelper Netresearch_Billsafe_Helper_Order */
        $orderHelper = Mage::helper('billsafe/order');
        $orderHelper->cancelLastOrderAndRestoreCart(Mage::getSingleton('checkout/session'));

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $this->assertEquals(Mage_Sales_Model_Order::STATE_CANCELED, $order->getState());
        $this->assertEquals(1, count($order->getStatusHistoryCollection()->getItems()));
        $this->assertEquals($couponCode, Mage::getSingleton('checkout/cart')->getQuote()->getCouponCode());
    }
}
