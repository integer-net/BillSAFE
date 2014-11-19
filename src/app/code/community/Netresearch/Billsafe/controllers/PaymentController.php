<?php
class Netresearch_Billsafe_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Redirects customer according to billsafe payment status and updates transaction id
     *
     * @return void
     */
    public function verifyAction()
    {
        $token = $this->_request->getParam('token', null);
        $client = Mage::getModel('billsafe/client')->getTransactionResult($token);

        /* @var $session Mage_Checkout_Model_Session */
        $session = Mage::getSingleton('checkout/session');

        if (!$client->isValid() || !$client->isAccepted()) {
            /* @var $orderHelper Netresearch_Billsafe_Helper_Order */
            $orderHelper = Mage::helper('billsafe/order');
            $orderHelper->cancelLastOrderAndRestoreCart($session);

            $msg = 'An error occured during payment process. Please try another payment method';
            $response = $client->getResponse();
            if ((property_exists($response, 'declineReason')
                && property_exists($response->declineReason, 'buyerMessage'))
            ) {
                $msg = $response->declineReason->buyerMessage;
            }
            $session->addError($this->__($msg));

            return $this->_redirect('checkout/cart');
        }

        Mage::helper('billsafe/data')->getTransactionByTransactionId($token)
            ->setTxnId($client->getResponseTransactionId())->save();

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($session->getLastOrderId());

        $state = Mage::getModel('billsafe/config')->getBillSafeOrderStatus($order->getStoreId());
        if ('pending' == $state) {
            $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        }
        Mage::helper('billsafe/order')->getPaymentInstruction($order);
        $text = 'Successful BillSAFE payment.<br/>Transaction ID: %d.<br/>BillSAFE Transaction Status: ACCEPTED.';
        $notice = $this->__($text, $client->getResponseTransactionId());
        $order->setState($state, true, $notice)->save();

        try {
            $order->sendNewOrderEmail();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Redirect to cart, display a flash message
     */
    public function cancellationAction()
    {
        $session = Mage::getSingleton('checkout/session');

        // handle session timeout
        if ($session->getLastRealOrderId()) {
            /* @var $orderHelper Netresearch_Billsafe_Helper_Order */
            $orderHelper = Mage::helper('billsafe/order');
            $orderHelper->cancelLastOrderAndRestoreCart($session);
            // Set flash message
            $session->addNotice($this->__('Billsafe payment has been cancelled'));
        }

        $this->_redirect('checkout/cart');
    }
}
