<?php
class Netresearch_Billsafe_Test_Controller_Adminhtml_ApiControllerTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{
    const ADMIN_USER_ID = 1;

    protected function setUp()
    {
        parent::setUp();

        $userMock = $this->getModelMock('admin/user');
        $userMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(self::ADMIN_USER_ID));
        $this->replaceByMock('model', 'admin/user', $userMock);

        // LOCALE MOCK
        $localeMock = $this->getModelMock('core/locale', array('date', 'getDateStrFormat'));
        $localeMock
            ->expects($this->any())
            ->method('date')
            ->will($this->returnValue(new Zend_Date(array(
                        'year'  => 1970,
                        'month' => 1,
                        'day'   => 2,
                    ))))
        ;
        $this->replaceByMock('singleton', 'core/locale', $localeMock);

        // APP MOCK
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            Mage::app(),
            '_locale',
            Mage::getSingleton('core/locale')
        );



        Mage::getSingleton('adminhtml/url')->turnOffSecretKey();
    }

    protected function tearDown()
    {
        parent::tearDown();
        /**
         * the following code block is suspected to cause some trouble on jenkins, that's why it's not used for now
         * @TODO NR: check with newer version of testing framework
         */
        /*
        $adminSession = Mage::getSingleton('admin/session');
        $adminSession->unsetAll();
        $adminSession->getCookie()->delete($adminSession->getSessionName());
        */
    }

    protected function performAdminLogin($allowInvoiceAccess = true)
    {
        $sessionMock = $this->getModelMock('admin/session', array('isAllowed', 'renewSession'));
        $sessionMock
            ->expects($this->any())
            ->method('isAllowed')
            ->with($this->logicalOr(
                $this->equalTo('sales/invoice'),
                $this->anything()
            ))
            ->will($this->returnCallback(
                function($resource) use ($allowInvoiceAccess) {
                    return (($resource == 'sales/invoice') ? $allowInvoiceAccess : true);
                }
            ));
        $this->replaceByMock('model', 'admin/session', $sessionMock);

        $session = Mage::getSingleton('admin/session');
        $session->login('admin', 'password');
    }

    /**
     * @test
     */
    public function pauseActionAllowed()
    {
        $this->performAdminLogin();

        $this->getRequest()->setMethod('POST');
        $this->dispatch('adminhtml/api/pause');
        $this->assertRequestRoute('adminhtml/api/pause');
        $this->getRequest()->reset();
    }

    /**
     * @test
     */
    public function pauseActionNotAllowedHttpMethod()
    {
//        $this->performAdminLogin();
//
//        $this->getRequest()->setMethod('GET');
//        $this->dispatch('adminhtml/api/pause');
//        $this->assertRequestRoute('adminhtml/api/denied');
    }

    /**
     * In any Magento version lower than CE 1.7.0.0 this test will lead to a
     * fatal error (Cannot redeclare drawMenuLevel()) because the function is
     * defined in a template (page/menu.phtml) instead of, as from CE 1.7.0.0, a
     * class (Mage_Adminhtml_Block_Page_Menu::getMenuLevel()). In PHPUnit, tests
     * can be skipped when a function does not exist (opposite situation here)
     * but that does not work with class methods. Additionally, the version
     * number of Mage_Adminhtml was never increased and thus cannot be utilized
     * for skipping this test.
     *
     * @test
     */
    public function pauseActionNotAllowedAcl()
    {
        $this->markTestSkipped('Test fails in CE 1.6 and lower.');
        $this->performAdminLogin(false);

        $blockMock = $this->getBlockMock('index/adminhtml_notifications', array('getProcessesForReindex'));
        $blockMock
            ->expects($this->any())
            ->method('getProcessesForReindex')
            ->will($this->returnValue(array()));
        $this->replaceByMock('block', 'index/adminhtml_notifications', $blockMock);

        $this->getRequest()->setMethod('POST');
        $this->dispatch('adminhtml/api/pause');
        $this->assertRequestRoute('adminhtml/api/denied');
    }

    /**
     * @test
     */
    public function pauseParameterMissing()
    {
        $this->performAdminLogin();

        $this->getRequest()->setMethod('POST');
        $this->dispatch('adminhtml/api/pause');
        $this->assertRequestRoute('adminhtml/api/pause');

        $this->assertResponseHttpCode(404);
        $this->assertResponseBodyContains(
            Mage::helper('billsafe/data')->__("The request misses required parameters.")
        );
    }

    /**
     * @test
     */
    public function directPaymentAmountMissing()
    {
        $this->performAdminLogin();

        $this->getRequest()->setMethod('POST');
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(404);
        $this->assertResponseBodyContains(
            Mage::helper('billsafe/data')->__("Please enter a payment amount.")
        );
    }

    /**
     * @test
     */
    public function directPaymentDateMissing()
    {
        $this->performAdminLogin();

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', 10.01);
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(404);
        $this->assertResponseBodyContains(
            Mage::helper('billsafe/data')->__("Please enter the date of payment receipt.")
        );
    }

    /**
     * @test
     */
    public function directPaymentParameterMissing()
    {
        $this->performAdminLogin();

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', 10.01)
            ->setPost('payment_date', '01.01.1970');
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(404);
        $this->assertResponseBodyContains(
            Mage::helper('billsafe/data')->__("The request misses required parameters.")
        );
    }

    /**
     * @test
     */
    public function directPaymentOrderNotFound()
    {
        $this->performAdminLogin();

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', 10.01)
            ->setPost('payment_date', '01.01.1970')
            ->setParam('order_id', 99);
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(404);
        $this->assertResponseBodyContains(
            Mage::helper('sales/data')->__("This order no longer exists.")
        );
    }

    /**
     * @test
     */
    public function directPaymentAmountExceedsInvoice()
    {
        $this->performAdminLogin();

        $orderId = 99;
        $paymentAmount = 10.01;

        $orderMock = $this->getModelMock('sales/order', array('load', 'getId'));
        $orderMock
            ->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());
        $orderMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('getOpenPaymentAmount'));
        $orderHelperMock
            ->expects($this->any())
            ->method('getOpenPaymentAmount')
            ->will($this->returnValue($paymentAmount - 1));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', $paymentAmount)
            ->setPost('payment_date', '01.01.1970')
            ->setParam('order_id', $orderId);
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(404);
        $this->assertResponseBodyContains(
            Mage::helper('sales/data')->__("The given payment amount exceeds the outstanding payment amount.")
        );
    }

    /**
     * @test
     */
    public function directPaymentNoCapturedTxnFound()
    {
        $this->performAdminLogin();

        $orderId = 99;
        $paymentAmount = 10.01;

        $orderMock = $this->getModelMock('sales/order', array('load', 'getId'));
        $orderMock
            ->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());
        $orderMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('getOpenPaymentAmount'));
        $orderHelperMock
            ->expects($this->any())
            ->method('getOpenPaymentAmount')
            ->will($this->returnValue($paymentAmount));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', $paymentAmount)
            ->setPost('payment_date', '01.01.1970')
            ->setParam('order_id', $orderId);
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(404);
        $this->assertResponseBodyContains(
            Mage::helper('billsafe/data')->__("No captured transaction found.")
        );
    }

    /**
     * @test
     */
    public function directPaymentApiException()
    {
        $this->performAdminLogin();

        $orderId = 99;
        $paymentAmount = 10.01;
        $transaction = new Varien_Object();
        $transaction->setData(array('id' => 199, 'parent_txn_id' => 198));

        $orderMock = $this->getModelMock('sales/order', array('load', 'getId'));
        $orderMock
            ->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());
        $orderMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('getOpenPaymentAmount', 'getCapturedTransaction'));
        $orderHelperMock
            ->expects($this->any())
            ->method('getOpenPaymentAmount')
            ->will($this->returnValue($paymentAmount));
        $orderHelperMock
            ->expects($this->any())
            ->method('getCapturedTransaction')
            ->will($this->returnValue($transaction));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $clientMock = $this->getModelMock('billsafe/client', array('reportDirectPayment'));
        $clientMock
            ->expects($this->any())
            ->method('reportDirectPayment')
            ->will($this->throwException(new Mage_Core_Exception()));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', $paymentAmount)
            ->setPost('payment_date', '01.01.1970')
            ->setParam('order_id', $orderId);
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(502);
        $this->assertResponseBodyContains(
            Mage::helper('billsafe/data')->__("BillSAFE server is temporarily not available, please try again later")
        );
    }

    /**
     * @test
     */
    public function directPaymentUnknownException()
    {
        $this->performAdminLogin();

        $exceptionMessage = 'exception foo.';
        $orderId = 99;
        $paymentAmount = 10.01;
        $transaction = new Varien_Object();
        $transaction->setData(array('id' => 199, 'parent_txn_id' => 198));

        $orderMock = $this->getModelMock('sales/order', array('load', 'getId'));
        $orderMock
            ->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());
        $orderMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('getOpenPaymentAmount', 'getCapturedTransaction'));
        $orderHelperMock
            ->expects($this->any())
            ->method('getOpenPaymentAmount')
            ->will($this->returnValue($paymentAmount));
        $orderHelperMock
            ->expects($this->any())
            ->method('getCapturedTransaction')
            ->will($this->returnValue($transaction));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $clientMock = $this->getModelMock('billsafe/client', array('reportDirectPayment'));
        $clientMock
            ->expects($this->any())
            ->method('reportDirectPayment')
            ->will($this->throwException(new Mage_Exception($exceptionMessage)));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', $paymentAmount)
            ->setPost('payment_date', '01.01.1970')
            ->setParam('order_id', $orderId);
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(500);
        $this->assertResponseBodyContains($exceptionMessage);
    }

    /**
     * @test
     */
    public function directPaymentDbException()
    {
        $this->performAdminLogin();

        $orderId = 99;
        $paymentAmount = 10.01;
        $transaction = new Varien_Object();
        $transaction->setData(array('id' => 199, 'parent_txn_id' => 198));

        $orderMock = $this->getModelMock('sales/order', array('load', 'getId'));
        $orderMock
            ->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());
        $orderMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('getOpenPaymentAmount', 'getCapturedTransaction'));
        $orderHelperMock
            ->expects($this->any())
            ->method('getOpenPaymentAmount')
            ->will($this->returnValue($paymentAmount));
        $orderHelperMock
            ->expects($this->any())
            ->method('getCapturedTransaction')
            ->will($this->returnValue($transaction));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $clientMock = $this->getModelMock('billsafe/client', array('reportDirectPayment'));
        $clientMock
            ->expects($this->any())
            ->method('reportDirectPayment')
            ->will($this->throwException(new Zend_Db_Exception('exception some sqlstate foo.')));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', $paymentAmount)
            ->setPost('payment_date', '01.01.1970')
            ->setParam('order_id', $orderId);
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(500);
        $this->assertResponseBodyContains(
            Mage::helper('billsafe/data')->__("An error occurred during request processing.")
        );
    }

    /**
     * @test
     */
    public function directPaymentInvalidRequest()
    {
        $this->performAdminLogin();

        $failureMessage = 'Parameter foo is invalid.';
        $orderId = 99;
        $paymentAmount = 10.01;
        $transaction = new Varien_Object();
        $transaction->setData(array('id' => 199, 'parent_txn_id' => 198));

        $orderMock = $this->getModelMock('sales/order', array('load', 'getId'));
        $orderMock
            ->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());
        $orderMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('getOpenPaymentAmount', 'getCapturedTransaction'));
        $orderHelperMock
            ->expects($this->any())
            ->method('getOpenPaymentAmount')
            ->will($this->returnValue($paymentAmount));
        $orderHelperMock
            ->expects($this->any())
            ->method('getCapturedTransaction')
            ->will($this->returnValue($transaction));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $clientMock = $this->getModelMock('billsafe/client', array('reportDirectPayment', 'getResponseErrorMessage'));
        $clientMock
            ->expects($this->any())
            ->method('reportDirectPayment')
            ->will($this->returnValue(false));
        $clientMock
            ->expects($this->any())
            ->method('getResponseErrorMessage')
            ->will($this->returnValue($failureMessage));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', $paymentAmount)
            ->setPost('payment_date', '01.01.1970')
            ->setParam('order_id', $orderId);
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');
        $this->assertResponseHttpCode(404);
        $this->assertResponseBodyContains($failureMessage);
    }

    /**
     * @test
     */
    public function directPaymentSuccessfulRequest()
    {
        $this->performAdminLogin();

        $orderId = 99;
        $paymentAmount = 10.01;
        $transaction = new Varien_Object();
        $transaction->setData(array('id' => 199, 'parent_txn_id' => 198));

        $orderMock = $this->getModelMock('sales/order', array('load', 'getId'));
        $orderMock
            ->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());
        $orderMock
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('getOpenPaymentAmount', 'getCapturedTransaction'));
        $orderHelperMock
            ->expects($this->any())
            ->method('getOpenPaymentAmount')
            ->will($this->returnValue($paymentAmount));
        $orderHelperMock
            ->expects($this->any())
            ->method('getCapturedTransaction')
            ->will($this->returnValue($transaction));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $clientMock = $this->getModelMock('billsafe/client', array('reportDirectPayment'));
        $clientMock
            ->expects($this->any())
            ->method('reportDirectPayment')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $directPaymentMock = $this->getModelMock('billsafe/direct_payment', array('save'));
        $directPaymentMock
            ->expects($this->any())
            ->method('save')
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'billsafe/direct_payment', $directPaymentMock);

        $this->getRequest()
            ->setMethod('POST')
            ->setPost('payment_amount', $paymentAmount)
            ->setPost('payment_date', '01.01.1970')
            ->setParam('order_id', $orderId);
        $this->dispatch('adminhtml/api/reportDirect');
        $this->assertRequestRoute('adminhtml/api/reportDirect');

        $this->assertResponseHttpCode(200);
        $this->assertResponseBodyContains(
            Mage::helper('billsafe/data')->__("The direct payment was successfully reported to BillSAFE.")
        );
    }
}
