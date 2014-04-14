<?php
/**
 * Created by JetBrains PhpStorm.
 * User: michael
 * Date: 24.09.13
 * Time: 13:02
 * To change this template use File | Settings | File Templates.
 */

class Netresearch_Billsafe_Test_Controller_PaymentControllerTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{

    public function testVerifyActionRedirectsToCart()
    {
        $customerSessionMock = $this->getModelMock('checkout/session', array('init', 'save', 'addError', 'isLoggedIn'));
        $this->replaceByMock('model', 'checkout/session', $customerSessionMock);

        $clientMock = $this->getModelMock('billsafe/client', array('isValid', 'isAccepted', 'getResponse', 'getTransactionResult'));
        $clientMock->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(false));
        $clientMock->expects($this->any())
            ->method('isAccepted')
            ->will($this->returnValue(false));

        $clientMock->expects($this->any())
            ->method('getTransactionResult')
            ->will($this->returnValue($clientMock));

        $declineReason = new stdClass();
        $declineReason->buyerMessage = 'foo';
        $fakeResponse = new stdClass();
        $fakeResponse->declineReason = $declineReason;
        $clientMock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($fakeResponse));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('cancelOrder'));
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $this->dispatch('billsafe/payment/verify', array('_store' => 1));
        $this->assertRedirect();
        $this->assertRedirectTo('checkout/cart', array('_store' => 1));




    }

    public function testVerifyActionRedirectsToCheckoutSuccess()
    {
        $clientMock = $this->getModelMock('billsafe/client', array('isValid', 'isAccepted', 'getTransactionResult'));
        $clientMock->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(true));
        $clientMock->expects($this->any())
            ->method('isAccepted')
            ->will($this->returnValue(true));

        $clientMock->expects($this->any())
            ->method('getTransactionResult')
            ->will($this->returnValue($clientMock));

        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $txMock = $this->getModelMock('sales/order_payment_transaction', array('save'));

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getTransactionByTransactionId'));
        $dataHelperMock->expects($this->any())
            ->method('getTransactionByTransactionId')
            ->will($this->returnValue($txMock));
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $orderMock = $this->getModelMock('sales/order', array('save', 'sendNewOrderEmail'));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('getPaymentInstruction'));
        $orderHelperMock->expects($this->any())
            ->method('getPaymentInstruction')
            ->will($this->returnValue(''));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $this->dispatch('billsafe/payment/verify', array('_store' => 1));
        $this->assertRedirect();
        $this->assertRedirectTo('checkout/onepage/success', array('_store' => 1));

        $configMock = $this->getModelMock('billsafe/config', array('getBillSafeOrderStatus'));
        $configMock->expects($this->any())
            ->method('getBillSafeOrderStatus')
            ->will($this->returnValue('pending'));
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $orderMock = $this->getModelMock('sales/order', array('save', 'sendNewOrderEmail'));
        $orderMock->expects($this->any())
            ->method('sendNewOrderEmail')
            ->will($this->throwException(new Exception('Test Exception')));
        $this->replaceByMock('model', 'sales/order', $orderMock);



        $this->dispatch('billsafe/payment/verify', array('_store' => 1));
        $this->assertRedirect();
        $this->assertRedirectTo('checkout/onepage/success', array('_store' => 1));

    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testCancellationAction()
    {
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('getLastRealOrderId'));
        $checkoutSessionMock->expects($this->any())
            ->method('getLastRealOrderId')
            ->will($this->returnValue('100000013'));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('cancelOrder'));
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $this->dispatch('billsafe/payment/cancellation', array('_store' => 1));

        $this->assertRedirect();
        $this->assertRedirectTo('checkout/cart', array('_store' => 1));
    }

}