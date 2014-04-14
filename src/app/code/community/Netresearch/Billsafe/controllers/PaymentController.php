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

        if (!$client->isValid() || !$client->isAccepted()) {
            $this->cancelLastOrderAndRestoreCart();
            $msg = 'An error occured during payment process. Please try another payment method';
            $response = $client->getResponse();
            if (property_exists($response, 'declineReason') && property_exists($response->declineReason, 'buyerMessage')) {
                $msg = $response->declineReason->buyerMessage;
            }
            Mage::getSingleton('checkout/session')->addError(Mage::helper('billsafe')->__($msg));

            return $this->_redirect('checkout/cart');
        }
        Mage::helper('billsafe')->getTransactionByTransactionId($token)
            ->setTxnId($client->getResponseTransactionId())->save();

        $state = Mage::getModel('billsafe/config')->getBillSafeOrderStatus($this->getOrder()->getStoreId());
        if ('pending' == $state) {
            $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        }
        Mage::helper('billsafe/order')->getPaymentInstruction($this->getOrder());
        $text = 'Successful BillSAFE payment.<br/>Transaction ID: %d.<br/>BillSAFE Transaction Status: ACCEPTED.';
        $notice = Mage::helper('billsafe')->__($text, $client->getResponseTransactionId());
        $this->getOrder()->setState($state, true, $notice)->save();

        try {
            $this->getOrder()->sendNewOrderEmail();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this->_redirect('checkout/onepage/success');
    }

    protected function getOrder()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        return Mage::getModel('sales/order')->load($orderId);
    }
    /**
     * Redirect to cart, display a flash message
     *
     * @return void
     */
    public function cancellationAction()
    {
        $this->cancelLastOrderAndRestoreCart();
        // Set flash message
        $msg = 'Billsafe payment has been cancelled';
        Mage::getSingleton('checkout/session')->addNotice($this->getHelper()->__($msg));

        return $this->_redirect('checkout/cart');
    }

    protected function cancelLastOrderAndRestoreCart()
    {
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
        // Re-fill cart
        $cart = Mage::getSingleton('checkout/cart');
        foreach ($order->getItemsCollection() as $item) {
            try {
                $cart->addOrderItem($item);
            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }
        }

        $cart->save();

        // Add coupon code if one is set
        $coupon = $order->getCouponCode();
        if (!is_null($coupon)) {
            $session->getQuote()->setCouponCode($coupon)->save();
        }

        // Cancel order
        $this->getHelper()->cancelOrder($order);
    }

    protected function getHelper()
    {
        return Mage::helper('billsafe');
    }
}
