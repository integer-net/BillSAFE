<?php

class Netresearch_Billsafe_Test_Helper_DataTest
    extends EcomDev_PHPUnit_Test_Case
{

    public function testGetStoreIdfromQuote()
    {
        $storeId = 1;
        $quote = Mage::getModel('sales/quote');
        $quote->setStoreId($storeId);

        $helperMock = $this->getHelperMock(
            'billsafe/data', array('getQuotefromSession')
        );

        $helperMock->expects($this->any())
            ->method('getQuotefromSession')
            ->will($this->returnValue($quote));

        $this->assertEquals($storeId, $helperMock->getStoreIdfromQuote());
    }

    public function testGetQuotefromSession()
    {
        $quote = Mage::getModel('sales/quote');

        $sessionMock = $this->getModelMock('checkout/session', array('getQuote', 'init', 'save'));
        $sessionMock->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($quote));
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $this->assertEquals(
            $quote, Mage::helper('billsafe/data')->getQuotefromSession()
        );
    }

    public function testFormat()
    {
        $number = 2;
        $this->assertEquals(
            2.00, Mage::helper('billsafe/data')->format($number)
        );
        $this->assertFalse(
            2 === Mage::helper('billsafe/data')->format($number)
        );
    }

    public function testCoalesce()
    {
        $helper = Mage::helper('billsafe/data');
        $this->assertEquals(null, $helper->coalesce());
        $this->assertEquals(1, $helper->coalesce(1, 2, 3));
        $this->assertEquals(2, $helper->coalesce(false, 2, 3));
        $this->assertEquals(3, $helper->coalesce(false, null, 3));
    }

    public function testGetConfig()
    {
        $this->assertInstanceOf(
            'Netresearch_Billsafe_Model_Config',
            Mage::helper('billsafe/data')->getConfig()
        );
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testGetTransactionByTransactionId()
    {
        $this->assertTrue(
            is_null(
                Mage::helper('billsafe/data')->getTransactionByTransactionId(
                    null
                )
            )
        );
        $this->assertTrue(
            is_null(
                Mage::helper('billsafe/data')->getTransactionByTransactionId(
                    false
                )
            )
        );
        $this->assertTrue(
            is_null(
                Mage::helper('billsafe/data')->getTransactionByTransactionId(0)
            )
        );
        $this->assertTrue(
            is_null(
                Mage::helper('billsafe/data')->getTransactionByTransactionId(
                    '0'
                )
            )
        );
        $this->assertTrue(
            is_null(
                Mage::helper('billsafe/data')->getTransactionByTransactionId('')
            )
        );

        $this->markTestIncomplete(
            'loading the transaction needs to be implemented, but building the fixtures did not work yet'
        );
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testIsOrderAlreadyInvoiced()
    {
        $order = Mage::getModel('sales/order')->load(11);
        $this->assertFalse(
            Mage::helper('billsafe/data')->isOrderAlreadyInvoiced($order)
        );
        $order = Mage::getModel('sales/order')->load(12);
        $this->assertTrue(
            Mage::helper('billsafe/data')->isOrderAlreadyInvoiced($order)
        );
    }


    public function testIsDoShipmentWithoutPost()
    {
        // first check in isDoShipment can never be false
        $request = new Varien_Object();
        $appMock = $this->getModelMock('core/app', array('getRequest'));
        $appMock->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $this->replaceByMock('model', 'core/app', $appMock);

        $this->assertFalse(Mage::helper('billsafe/data')->isDoShipment());

    }

    public function testIsDoShipmentWithPost()
    {
        $post = array('invoice' => array('do_shipment' => true));
        Mage::app()->getRequest()->setPost($post);
        $this->assertTrue(Mage::helper('billsafe/data')->isDoShipment());

        $post = array('invoice' => array());
        Mage::app()->getRequest()->setPost($post);
        $this->assertFalse(Mage::helper('billsafe/data')->isDoShipment());

        Mage::app()->getRequest()->setPost(array());
        $this->assertFalse(Mage::helper('billsafe/data')->isDoShipment());

    }

    public function testIsCaptureOffline()
    {
        $post = array('invoice' => array('capture_case' => 'offline'));
        Mage::app()->getRequest()->setPost($post);
        $this->assertTrue(Mage::helper('billsafe/data')->isCaptureOffline());

        $post = array('invoice' => array('capture_case' => 'online'));
        Mage::app()->getRequest()->setPost($post);
        $this->assertFalse(Mage::helper('billsafe/data')->isCaptureOffline());

        $post = array('invoice' => array());
        Mage::app()->getRequest()->setPost($post);
        $this->assertFalse(Mage::helper('billsafe/data')->isCaptureOffline());

        Mage::app()->getRequest()->setPost(array());
        $this->assertFalse(Mage::helper('billsafe/data')->isCaptureOffline());

    }


    public function testIsFeeItem()
    {
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('init', 'save'));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);
        $customerSessionMock = $this->getModelMock('customer/session', array('init', 'save'));
        $this->replaceByMock('model', 'customer/session', $customerSessionMock);

        $itemOne = new Varien_Object();
        $itemOne->setSku('fee');

        $itemTwo = new Varien_Object();
        $itemTwo->setSku('no fee');

        $configModelMock = $this->getModelMock(
            'billsafe/config', array('isPaymentFeeEnabled', 'getPaymentFeeSku')
        );
        $configModelMock->expects($this->any())
            ->method('isPaymentFeeEnabled')
            ->will($this->returnValue(true));

        $configModelMock->expects($this->any())
            ->method('getPaymentFeeSku')
            ->will($this->returnValue('fee'));
        $helperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $helperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(null));
        $this->replaceByMock('model', 'billsafe/config', $configModelMock);
        $this->mockSessions();
        $this->assertFalse($helperMock->isFeeItem($itemTwo));
        $this->assertTrue($helperMock->isFeeItem($itemOne));
    }


    public function testWrap()
    {
        $helper = Mage::helper('billsafe/data');
        $testStringToWrap = 'Dies ist ein ganz toller Teststring, ' .
            'der gewrappt werden soll';
        $expectedResult = wordwrap($testStringToWrap, 2, "\n");
        $result = $helper->wrap($testStringToWrap, 2, "\n");
        $this->assertEquals($expectedResult, $result);
        $this->assertEquals($testStringToWrap, $helper->wrap($testStringToWrap));
    }


    protected static function runProtectedMethod($name, $object)
    {
        $class = new ReflectionClass(get_class($object));
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
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
}