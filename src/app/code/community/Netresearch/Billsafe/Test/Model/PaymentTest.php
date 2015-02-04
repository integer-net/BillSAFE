<?php

class Netresearch_Billsafe_Test_Model_PaymentTest extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testPrevalidateOrder()
    {
        $quote = Mage::getModel('sales/quote')->load(1);

        $fakeResponse = new stdClass();
        $fakeResponse->ack = 'OK';
        $invoice = new stdClass();
        $invoice->isAvailable = true;
        $fakeResponse->invoice = $invoice;

        $helperMock = $this->getHelperMock('billsafe/order', array('prevalidateOrder'));
        $helperMock->expects($this->any())
            ->method('prevalidateOrder')
            ->will($this->returnValue($fakeResponse));
        $this->replaceByMock('helper', 'billsafe/order', $helperMock);

        $model = Mage::getModel('billsafe/payment');

        $this->assertTrue($model->prevalidateOrder($quote));
        $this->assertTrue($model->isAvailableCheck());
        $this->assertEquals('', $model->getUnavailableMessage);

        $fakeResponse = new stdClass();
        $fakeResponse->ack = 'ERR';


        $helperMock = $this->getHelperMock('billsafe/order', array('prevalidateOrder'));
        $helperMock->expects($this->any())
            ->method('prevalidateOrder')
            ->will($this->returnValue($fakeResponse));
        $model->setOrderHelper($helperMock);
        $this->assertFalse($model->prevalidateOrder($quote));

        $fakeResponse = new stdClass();
        $fakeResponse->ack = 'OK';
        $invoice = new stdClass();
        $invoice->isAvailable = false;
        $invoice->message = 'foo';
        $fakeResponse->invoice = $invoice;

        $helperMock = $this->getHelperMock('billsafe/order', array('prevalidateOrder'));
        $helperMock->expects($this->any())
            ->method('prevalidateOrder')
            ->will($this->returnValue($fakeResponse));
        $model->setOrderHelper($helperMock);
        $this->assertFalse($model->prevalidateOrder($quote));
        $this->assertFalse($model->isAvailableCheck());
        $this->assertEquals('foo', $model->getUnavailableMessage());
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     * @expectedException Mage_Core_Exception
     */
    public function testPrevalidateOrderWithException()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $fakeCheckoutSession = $this->getModelMock('checkout/session', array('init', 'save'));
        $this->replaceByMock('model', 'checkout/session', $fakeCheckoutSession);
        $fakeCustomerSession = $this->getModelMock('customer/session', array('init', 'save'));
        $this->replaceByMock('model', 'customer/session', $fakeCustomerSession);
        $fakeResponse = new stdClass();
        $fakeResponse->ack = 'OK';
        $invoice = new stdClass();
        $invoice->isAvailable = true;
        $fakeResponse->invoice = $invoice;

        $helperMock = $this->getHelperMock('billsafe/order', array('prevalidateOrder'));
        $helperMock->expects($this->any())
            ->method('prevalidateOrder')
            ->will($this->throwException(new Exception()));
        $this->replaceByMock('helper', 'billsafe/order', $helperMock);
        $model = Mage::getModel('billsafe/payment');
        $model->prevalidateOrder($quote);
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testAuthorizeSuccessFalse()
    {
        $order = Mage::getModel('sales/order')->load(11);
        $quote = Mage::getModel('sales/quote')->load(1);
        $order->setQuote($quote);
        $payment = $order->getPayment();
        $amount = $order->getBaseGrandTotal();
        $infoInstance = new Varien_Object();
        $infoInstance->setOrder($order);

        $configMock = $this->getModelMock('billsafe/config', array('isBillSafeDirectEnabled'));
        $configMock->expects($this->any())
            ->method('isBillSafeDirectEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $sessionMock = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        //orderHelper
        $orderHelperMock = $this->getHelperMock('billsafe/order', array('processOrder'));
        $orderHelperMock->expects($this->once())
            ->method('processOrder')
            ->will($this->returnValue(array('success' => false)));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);


        // data helper
        $dataHelperMock = $this->getHelperMock('billsafe/data',
                                               array('getStoreIdfromQuote', 'getCustomerCompany'));
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(null));
        $dataHelperMock->expects($this->any())
            ->method('getCustomerCompany')
            ->will($this->returnValue(null));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getInfoInstance', 'cancel'));
        $paymentModelMock->expects($this->any())
            ->method('getInfoInstance')
            ->will($this->returnValue($infoInstance));
        $paymentModelMock->expects($this->any())
            ->method('cancel')
            ->will($this->returnValue(null));
        $this->replaceByMock('model', 'billsafe/payment', $paymentModelMock);

        try {
            $paymentModelMock->authorize($payment, $amount);
        } catch (Exception $e) {
            $this->assertEquals('Please select another payment method!', $e->getMessage());
        }
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testAuthorizeSuccessTrue()
    {
        $order = Mage::getModel('sales/order')->load(11);
        $quote = Mage::getModel('sales/quote')->load(1);
        $order->setQuote($quote);
        $payment = $order->getPayment();
        $amount = $order->getBaseGrandTotal();
        $infoInstance = new Varien_Object();
        $infoInstance->setOrder($order);

        $configMock = $this->getModelMock('billsafe/config', array(
            'isBillSafeDirectEnabled',
            'getBillSafeOrderStatus'
            )
        );
        $configMock->expects($this->any())
            ->method('isBillSafeDirectEnabled')
            ->will($this->returnValue(true));

        $configMock->expects($this->any())
            ->method('getBillSafeOrderStatus')
            ->will($this->returnValue('pending'));
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $sessionMock = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        //orderHelper
        $orderHelperMock = $this->getHelperMock('billsafe/order', array('processOrder', 'getPaymentInstruction'));
        $orderHelperMock->expects($this->any())
            ->method('processOrder')
            ->will($this->returnValue(array(
                    'success' => true,
                    'transactionId' => '123'
                    )
                )
        );
        $orderHelperMock->expects($this->any())
            ->method('getPaymentInstruction')
            ->will($this->returnValue(''));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        // data helper
        $dataHelperMock = $this->getHelperMock('billsafe/data',
                                               array('getStoreIdfromQuote', 'getCustomerCompany'));
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(null));
        $dataHelperMock->expects($this->any())
                       ->method('getCustomerCompany')
                       ->will($this->returnValue(null));

        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getInfoInstance', 'cancel'));
        $paymentModelMock->expects($this->any())
            ->method('getInfoInstance')
            ->will($this->returnValue($infoInstance));
        $paymentModelMock->expects($this->any())
            ->method('cancel')
            ->will($this->returnValue(null));
        $paymentModelMock->setDataHelper($dataHelperMock);

        $paymentModelMock->authorize($payment, $amount);
        $this->assertEquals('123', $payment->getTransactionId());
        $this->assertEquals(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $order->getState());

        $configMock = $this->getModelMock('billsafe/config', array(
            'isBillSafeDirectEnabled',
            'getBillSafeOrderStatus'
            )
        );
        $configMock->expects($this->any())
            ->method('isBillSafeDirectEnabled')
            ->will($this->returnValue(true));

        $configMock->expects($this->any())
            ->method('getBillSafeOrderStatus')
            ->will($this->returnValue(Mage_Sales_Model_Order::STATE_PROCESSING));

        $paymentModelMock->setConfig($configMock);
        $paymentModelMock->authorize($payment, $amount);
        $this->assertEquals(Mage_Sales_Model_Order::STATE_PROCESSING, $order->getState());
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testAuthorizeWithToken()
    {
        $this->mockSessions();
        $this->mockDataHelperForQuote();

        $order = Mage::getModel('sales/order')->load(11);
        $quote = Mage::getModel('sales/quote')->load(1);
        $order->setQuote($quote);
        $payment = $order->getPayment();
        $amount = $order->getBaseGrandTotal();
        $infoInstance = new Varien_Object();
        $infoInstance->setOrder($order);

        $configMock = $this->getModelMock('billsafe/config', array(
                'isBillSafeDirectEnabled',
                'getBillSafeOrderStatus'
            )
        );
        $configMock->expects($this->any())
            ->method('isBillSafeDirectEnabled')
            ->will($this->returnValue(false));

        $configMock->expects($this->any())
            ->method('getBillSafeOrderStatus')
            ->will($this->returnValue('pending'));
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $fakeResponse = new Varien_Object();
        $fakeResponse->setResponseToken('token');
        $clientMock = $this->getModelMock('billsafe/client', array('prepareOrder'));
        $clientMock->expects($this->once())
            ->method('prepareOrder')
            ->will($this->returnValue($fakeResponse));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getInfoInstance', 'cancel'));
        $paymentModelMock->expects($this->any())
            ->method('getInfoInstance')
            ->will($this->returnValue($infoInstance));
        $paymentModelMock->expects($this->any())
            ->method('cancel')
            ->will($this->returnValue(null));
        $paymentModelMock->authorize($payment, $amount);
        $this->assertEquals('token', Mage::registry(Netresearch_Billsafe_Model_Config::TOKEN_REGISTRY_KEY));

    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     * @expectedException Mage_Core_Exception
     */
    public function testAuthorizeWithTokenFails()
    {
        $this->mockSessions();
        $this->mockDataHelperForQuote();

        $order = Mage::getModel('sales/order')->load(11);
        $quote = Mage::getModel('sales/quote')->load(1);
        $order->setQuote($quote);

        $infoInstance = new Varien_Object();
        $infoInstance->setOrder($order);

        $configMock = $this->getModelMock('billsafe/config', array(
                'isBillSafeDirectEnabled',
            )
        );
        $configMock->expects($this->any())
            ->method('isBillSafeDirectEnabled')
            ->will($this->returnValue(false));

        $clientMock = $this->getModelMock('billsafe/client', array('prepareOrder'));
        $clientMock->expects($this->any())
            ->method('prepareOrder')
            ->will($this->throwException(new Exception('catch me')));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);
        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getInfoInstance', 'cancel'));
        $paymentModelMock->expects($this->any())
            ->method('getInfoInstance')
            ->will($this->returnValue($infoInstance));
        $paymentModelMock->expects($this->any())
            ->method('cancel')
            ->will($this->returnValue(null));
        $paymentModelMock->authorize($order->getPayment(), $order->getBaseGrandTotal());

    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testAreAllCaptureItemsSent()
    {
        $helperMock = $this->getHelperMock('billsafe/data', array('isDoShipment', 'isCaptureOffline'));
        $helperMock->expects($this->any())
            ->method('isDoShipment')
            ->will($this->returnValue(false));
        $helperMock->expects($this->any())
            ->method('isCaptureOffline')
            ->will($this->returnValue(false));
        $this->replaceByMock('helper', 'billsafe/data', $helperMock);
        $order = Mage::getModel('sales/order')->load(11);
        $payment = $order->getPayment();
        $reflectionClass = new ReflectionClass(get_class(Mage::getModel('billsafe/payment')));
        $method = $reflectionClass->getMethod("areAllCaptureItemsSent");
        $method->setAccessible(true);
        $methodMock = $this->getModelMock('billsafe/payment', array('getHelper'));
        $methodMock->expects($this->any())
            ->method('getHelper')
            ->will($this->returnValue($helperMock));
        $this->assertFalse($method->invoke($methodMock, $payment));

        $order = Mage::getModel('sales/order')->load(12);
        $payment = $order->getPayment();
        $this->assertTrue($method->invoke($methodMock, $payment));

        $order = Mage::getModel('sales/order')->load(13);
        $payment = $order->getPayment();
        $this->assertFalse($method->invoke($methodMock, $payment));

        $paymentFeeHelper = $this->getHelperMock('paymentfee/data', array('isFeeProduct'));
        $paymentFeeHelper->expects($this->once())
            ->method('isFeeProduct')
            ->will($this->returnValue(true));
        $this->replaceByMock('helper', 'paymentfee/data', $paymentFeeHelper);

        $order = Mage::getModel('sales/order')->load(14);
        $payment = $order->getPayment();
        $this->assertTrue($method->invoke($methodMock, $payment));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testAreAllCaptureItemsSentIfShipmentIsDoneOrIsOffline()
    {
        $helperMock = $this->getHelperMock('billsafe/data', array('isDoShipment', 'isCaptureOffline'));
        $helperMock->expects($this->any())
            ->method('isDoShipment')
            ->will($this->returnValue(true));
        $helperMock->expects($this->any())
            ->method('isCaptureOffline')
            ->will($this->returnValue(true));
        $this->replaceByMock('helper', 'billsafe/data', $helperMock);

        $reflectionClass = new ReflectionClass(get_class(Mage::getModel('billsafe/payment')));
        $method = $reflectionClass->getMethod("areAllCaptureItemsSent");
        $method->setAccessible(true);
        $methodMock = $this->getModelMock('billsafe/payment', array('getHelper'));
        $methodMock->expects($this->any())
            ->method('getHelper')
            ->will($this->returnValue($helperMock));

        $order = Mage::getModel('sales/order')->load(13);
        $payment = $order->getPayment();

        $this->assertTrue($method->invoke($methodMock, $payment));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsAvailableIsAvailableFalse()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_ACTIVE;
        $storeFirst = Mage::app()->getStore(0)->load(0);
        $this->mockSessions();
        $this->mockDataHelperForQuote();
        $storeFirst->setConfig($path, 0);
        $quote = Mage::getModel('sales/quote')->load(1);
        $this->assertFalse(Mage::getModel('billsafe/payment')->isAvailable($quote));
        $storeFirst->resetConfig();
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsNotAvailableWhenDisabled()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array(
            'getStoreId',
            'getQuotefromSession'
            )
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreId')
            ->will($this->returnValue(null));

        $dataHelperMock->expects($this->any())
            ->method('getQuotefromSession')
            ->will($this->returnValue($quote));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);


        $configModelMock = $this->getModelMock('billsafe/config', array('isBillsafeDirectEnabled'));
        $configModelMock->expects($this->any())
            ->method('isBillsafeDirectEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'billsafe/config', $configModelMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('generateAddressHash'));
        $orderHelperMock->expects($this->any())
            ->method('generateAddressHash')
            ->will($this->returnValue('abcd'));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $customerSessionMock = $this->getModelMockBuilder('customer/session', array('getData'))
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $customerSessionMock->expects($this->any())
            ->method('getData')
            ->will($this->onConsecutiveCalls(true, 'abcd'));

        $this->replaceByMock('singleton', 'customer/session', $customerSessionMock);

        $this->assertFalse(Mage::getModel('billsafe/payment')->isAvailable($quote));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsAvailableTotalAmountDoesNotFit()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $quote->setGrandTotal(11);

        $sessionMock = $this->getModelMockBuilder('customer/session', array('getCustomer'))
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $customer = Mage::getModel('customer/customer');
        $customer->setGroupId(1);
        $sessionMock->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue($customer));
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $configModelMock = $this->getModelMock('billsafe/config', array(
            'getBillSafeMinAmount',
            'getBillSafeMaxAmount',
        ));
        $configModelMock->expects($this->any())
            ->method('getBillSafeMinAmount')
            ->will($this->returnValue(15));
        $configModelMock->expects($this->any())
            ->method('getBillSafeMaxAmount')
            ->will($this->returnValue(10));
        $this->replaceByMock('model', 'billsafe/config', $configModelMock);

        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getConfigData', 'checkIfAllItemsAreVirtual'));
        $paymentModelMock->expects($this->any())
            ->method('getConfigData')
            ->will($this->returnValue(true));
        $paymentModelMock->expects($this->any())
            ->method('checkIfAllItemsAreVirtual')
            ->will($this->returnValue(false));
        $this->assertFalse($paymentModelMock->isAvailable($quote));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsAvailableAvoidOverMax()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $quote->setGrandTotal(110);

        $sessionMock = $this->getModelMockBuilder('customer/session', array('getCustomer'))
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $customer = Mage::getModel('customer/customer');
        $customer->setGroupId(1);
        $sessionMock->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue($customer));
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $configModelMock = $this->getModelMock('billsafe/config', array(
            'getBillSafeMinAmount',
            'getBillSafeMaxAmount',
            'isBillsafeExeedingMaxFeeAmount',
        ));
        $configModelMock->expects($this->any())
            ->method('getBillSafeMinAmount')
            ->will($this->returnValue(15));

        $configModelMock->expects($this->any())
            ->method('getBillSafeMaxAmount')
            ->will($this->returnValue(999));

        $configModelMock->expects($this->any())
            ->method('isBillsafeExeedingMaxFeeAmount')
            ->will($this->returnValue(true));

        $this->replaceByMock('model', 'billsafe/config', $configModelMock);

        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getConfigData', 'checkIfAllItemsAreVirtual'));
        $paymentModelMock->expects($this->any())
            ->method('getConfigData')
            ->will($this->returnValue(true));
        $paymentModelMock->expects($this->any())
            ->method('checkIfAllItemsAreVirtual')
            ->will($this->returnValue(false));

        $feeProduct = new Varien_Object();
        $feeProduct->setExceedsMaxAmount(true);

        $feeHelperMock = $this->getHelperMock('paymentfee/data', array('getUpdatedFeeProduct'));
        $feeHelperMock->expects($this->any())
            ->method('getUpdatedFeeProduct')
            ->will($this->returnValue($feeProduct));
        $this->replaceByMock('helper', 'paymentfee/data', $feeHelperMock);

        $this->assertFalse($paymentModelMock->isAvailable($quote));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsAvailableAvoidBelowMin()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $quote->setGrandTotal(110);

        $sessionMock = $this->getModelMockBuilder('customer/session', array('getCustomer'))
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $customer = Mage::getModel('customer/customer');
        $customer->setGroupId(1);
        $sessionMock->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue($customer));
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $configModelMock = $this->getModelMock('billsafe/config', array(
            'getBillSafeMinAmount',
            'getBillSafeMaxAmount',
            'isBillsafeExeedingMaxFeeAmount',
            'isBillsafeExeedingMinFeeAmount',
        ));
        $configModelMock->expects($this->any())
            ->method('getBillSafeMinAmount')
            ->will($this->returnValue(15));

        $configModelMock->expects($this->any())
            ->method('getBillSafeMaxAmount')
            ->will($this->returnValue(999));

        $configModelMock->expects($this->any())
            ->method('isBillsafeExeedingMaxFeeAmount')
            ->will($this->returnValue(false));

        $configModelMock->expects($this->any())
            ->method('isBillsafeExeedingMinFeeAmount')
            ->will($this->returnValue(true));

        $this->replaceByMock('model', 'billsafe/config', $configModelMock);

        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getConfigData', 'checkIfAllItemsAreVirtual'));
        $paymentModelMock->expects($this->any())
            ->method('getConfigData')
            ->will($this->returnValue(true));
        $paymentModelMock->expects($this->any())
            ->method('checkIfAllItemsAreVirtual')
            ->will($this->returnValue(false));

        $feeProduct = new Varien_Object();
        $feeProduct->setExceedsMaxAmount(false);
        $feeProduct->setExceedsMinAmount(true);

        $feeDataHelperMock = $this->getHelperMock('paymentfee/data', array('getUpdatedFeeProduct'));
        $feeDataHelperMock->expects($this->any())
            ->method('getUpdatedFeeProduct')
            ->will($this->returnValue($feeProduct));
        $this->replaceByMock('helper', 'paymentfee/data', $feeDataHelperMock);

        $this->assertFalse($paymentModelMock->isAvailable($quote));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsAvailableNoTax()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $quote->setGrandTotal(110);

        $sessionMock = $this->getModelMockBuilder('customer/session', array('getCustomer'))
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $customer = Mage::getModel('customer/customer');
        $customer->setGroupId(1);
        $sessionMock->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue($customer));
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $configModelMock = $this->getModelMock('billsafe/config', array(
            'getBillSafeMinAmount',
            'getBillSafeMaxAmount',
        ));
        $configModelMock->expects($this->any())
            ->method('getBillSafeMinAmount')
            ->will($this->returnValue(15));

        $configModelMock->expects($this->any())
            ->method('getBillSafeMaxAmount')
            ->will($this->returnValue(999));

        $this->replaceByMock('model', 'billsafe/config', $configModelMock);

        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getConfigData', 'checkIfAllItemsAreVirtual'));
        $paymentModelMock->expects($this->any())
            ->method('getConfigData')
            ->will($this->returnValue(true));

        $paymentModelMock->expects($this->any())
            ->method('checkIfAllItemsAreVirtual')
            ->will($this->returnValue(false));

        $feeProduct = new Varien_Object();

        $feeDataHelperMock = $this->getHelperMock('paymentfee/data', array('getUpdatedFeeProduct'));
        $feeDataHelperMock->expects($this->any())
            ->method('getUpdatedFeeProduct')
            ->will($this->returnValue($feeProduct));
        $this->replaceByMock('helper', 'paymentfee/data', $feeDataHelperMock);

        $this->assertFalse($paymentModelMock->isAvailable($quote));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsAvailableWithTaxAndSameAddress()
    {

        $tax = new Varien_Object();
        $tax->setValue(10);
        $quote = $this->getModelMock('sales/quote', array('getTotals'));
        $quote->expects($this->any())
            ->method('getTotals')
            ->will($this->returnValue(array('tax' => $tax)));

        $quote->setGrandTotal(110);

        $sessionMock = $this->getModelMockBuilder('customer/session', array('getCustomer'))
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $customer = Mage::getModel('customer/customer');
        $customer->setGroupId(1);
        $sessionMock->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue($customer));
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $configModelMock = $this->getModelMock('billsafe/config', array(
            'getBillSafeMinAmount',
            'getBillSafeMaxAmount',
        ));
        $configModelMock->expects($this->any())
            ->method('getBillSafeMinAmount')
            ->will($this->returnValue(15));

        $configModelMock->expects($this->any())
            ->method('getBillSafeMaxAmount')
            ->will($this->returnValue(999));
        $this->replaceByMock('model', 'billsafe/config', $configModelMock);

        $paymentModelMock = $this->getModelMock('billsafe/payment', array(
            'getConfigData',
            'checkIfAllItemsAreVirtual',
            'prevalidateOrder'
            )
        );
        $paymentModelMock->expects($this->any())
            ->method('checkIfAllItemsAreVirtual')
            ->will($this->returnValue(false));

        $paymentModelMock->expects($this->any())
            ->method('getConfigData')
            ->will($this->returnValue(true));

        $feeProduct = new Varien_Object();

        $feeDataHelperMock = $this->getHelperMock('paymentfee/data', array('getUpdatedFeeProduct'));
        $feeDataHelperMock->expects($this->any())
            ->method('getUpdatedFeeProduct')
            ->will($this->returnValue($feeProduct));
        $this->replaceByMock('helper', 'paymentfee/data', $feeDataHelperMock);

        $this->assertTrue($paymentModelMock->isAvailable($quote));
        $quote->getShippingAddress()->setStreet('not equal anymore');
        $this->assertFalse($paymentModelMock->isAvailable($quote));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsAvailableReturnsFalseIfPrevalidateOrdercheckFails()
    {
        $this->mockSessions();
        $tax = new Varien_Object();
        $tax->setValue(10);
        $quote = $this->getModelMock('sales/quote', array('getTotals'));
        $quote->expects($this->any())
            ->method('getTotals')
            ->will($this->returnValue(array('tax' => $tax)));

        $quote->setGrandTotal(110);

        $sessionMock = $this->getModelMockBuilder('customer/session', array('getCustomer'))
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $customer = Mage::getModel('customer/customer');
        $customer->setGroupId(1);
        $sessionMock->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue($customer));
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $configModelMock = $this->getModelMock('billsafe/config', array(
                'getBillSafeMinAmount',
                'getBillSafeMaxAmount',
            )
        );
        $configModelMock->expects($this->any())
            ->method('getBillSafeMinAmount')
            ->will($this->returnValue(15));

        $configModelMock->expects($this->any())
            ->method('getBillSafeMaxAmount')
            ->will($this->returnValue(999));

        $this->replaceByMock('model', 'billsafe/config', $configModelMock);

        $paymentModelMock = $this->getModelMock('billsafe/payment', array(
                'getConfigData',
                'checkIfAllItemsAreVirtual',
                'prevalidateOrder'
            )
        );
        $paymentModelMock->expects($this->any())
            ->method('checkIfAllItemsAreVirtual')
            ->will($this->returnValue(false));

        $paymentModelMock->expects($this->any())
            ->method('getConfigData')
            ->will($this->returnValue(true));
        $paymentModelMock->expects($this->any())
            ->method('prevalidateOrder')
            ->will($this->throwException(new Mage_Core_Exception('test exception')));

        $feeProduct = new Varien_Object();

        $feeDataHelperMock = $this->getHelperMock('paymentfee/data', array('getUpdatedFeeProduct'));
        $feeDataHelperMock->expects($this->any())
            ->method('getUpdatedFeeProduct')
            ->will($this->returnValue($feeProduct));
        $this->replaceByMock('helper', 'paymentfee/data', $feeDataHelperMock);

        $this->assertFalse($paymentModelMock->isAvailable($quote));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsAvailableWithWithTaxAndAddressesNotEquals()
    {

        $tax = new Varien_Object();
        $tax->setValue(10);
        $quote = $this->getModelMock('sales/quote', array('getTotals', 'getShippingAddress', 'getBillingAddress'));
        $quote->expects($this->any())
            ->method('getTotals')
            ->will($this->returnValue(array('tax' => $tax)));

        $quote->setGrandTotal(110);

        $sessionMock = $this->getModelMockBuilder('customer/session', array('getCustomer'))
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $customer = Mage::getModel('customer/customer');
        $customer->setGroupId(1);
        $sessionMock->expects($this->any())
            ->method('getCustomer')
            ->will($this->returnValue($customer));
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);

        $configModelMock = $this->getModelMock('billsafe/config', array(
            'getBillSafeMinAmount',
            'getBillSafeMaxAmount',
        ));
        $configModelMock->expects($this->any())
            ->method('getBillSafeMinAmount')
            ->will($this->returnValue(15));

        $configModelMock->expects($this->any())
            ->method('getBillSafeMaxAmount')
            ->will($this->returnValue(999));

        $this->replaceByMock('model', 'billsafe/config', $configModelMock);

        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getConfigData'));
        $paymentModelMock->expects($this->any())
            ->method('getConfigData')
            ->will($this->returnValue(true));

        $shippingAddress = Mage::getModel('sales/quote_address');
        $shippingAddress->setSameAsBilling(false);
        $shippingAddress->setFirstname('Hans');

        $billingAddress = Mage::getModel('sales/quote_address');
        $billingAddress->setFirstname('Wurst');

        $quote->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($shippingAddress));

        $quote->expects($this->any())
            ->method('getBillingAddress')
            ->will($this->returnValue($billingAddress));

        $feeProduct = new Varien_Object();

        $feeDataHelperMock = $this->getHelperMock('paymentfee/data', array('getUpdatedFeeProduct'));
        $feeDataHelperMock->expects($this->any())
            ->method('getUpdatedFeeProduct')
            ->will($this->returnValue($feeProduct));
        $this->replaceByMock('helper', 'paymentfee/data', $feeDataHelperMock);

        $this->assertFalse($paymentModelMock->isAvailable($quote));
    }

    public function testCodeIsBillsafe()
    {
        $this->assertEquals('billsafe', Mage::getModel('billsafe/payment')->getCode());
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testClient()
    {
        $quote = Mage::getModel('sales/quote')->load(1);
        $dataHelperMock = $this->getHelperMock('billsafe/data', array(
            'getStoreId',
            'getQuotefromSession'
            )
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreId')
            ->will($this->returnValue(null));

        $dataHelperMock->expects($this->any())
            ->method('getQuotefromSession')
            ->will($this->returnValue($quote));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->assertInstanceOf('Netresearch_Billsafe_Model_Client', Mage::getModel('billsafe/payment')->getClient());
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testGetOrderPlaceRedirectUrl()
    {
        $quote = Mage::getModel('sales/quote')->load(1);

        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array(
            'getStoreIdfromQuote',
            'getQuotefromSession'
            )
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(null));

        $dataHelperMock->expects($this->any())
            ->method('getQuotefromSession')
            ->will($this->returnValue($quote));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $configModelMock = $this->getModelMock('billsafe/config', array(
            'isBillSafeDirectEnabled',
            'getGatewayUrl'
            )
        );
        $configModelMock->expects($this->any())
            ->method('isBillSafeDirectEnabled')
            ->will($this->returnValue(false));

        $configModelMock->expects($this->any())
            ->method('getGatewayUrl')
            ->will($this->returnValue('www.test.de'));
        $this->replaceByMock('model', 'billsafe/config', $configModelMock);

        $client = Mage::getModel('billsafe/client');
        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getClient'));
        $paymentModelMock->expects($this->any())
            ->method('getClient')
            ->will($this->returnValue($client));

        $url = $paymentModelMock->getOrderPlaceRedirectUrl();

        $this->assertInternalType('string', $url);
        $this->assertContains('?token=', $url);

        $configMock = $this->getModelMock('billsafe/config', array('isBillSafeDirectEnabled'));
        $configMock->expects($this->any())
            ->method('isBillSafeDirectEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'billsafe/config', $configMock);
        $paymentModelMock = $this->getModelMock('billsafe/payment', array('getClient'));
        $paymentModelMock->expects($this->any())
            ->method('getClient')
            ->will($this->returnValue($client));
        $this->assertEquals('', $paymentModelMock->getOrderPlaceRedirectUrl());
    }

    protected function mockSessions()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $sessionMock = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor() // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'customer/session', $sessionMock);
    }

    protected function mockDataHelperForQuote()
    {
        $dataHelperMock = $this->getHelperMock('billsafe/data',
                                               array('getStoreIdfromQuote', 'getCustomerCompany'));
        $dataHelperMock->expects($this->any())
                       ->method('getStoreIdfromQuote')
                       ->will($this->returnValue(null));
        $dataHelperMock->expects($this->any())
                       ->method('getCustomerCompany')
                       ->will($this->returnValue(null));

        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
    }
}