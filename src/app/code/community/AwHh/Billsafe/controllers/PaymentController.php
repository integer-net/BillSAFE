<?php

class AwHh_Billsafe_PaymentController extends Mage_Core_Controller_Front_Action {

    /**
     * Redirects customer according to billsafe payment status and updates transaction id
     * 
     * @return void
     */
    public function verifyAction() {
        $token = $this->_request->getParam('token', null);
        $client = Mage::getModel('billsafe/client')->getTransactionResult($token);

        if (!$client->isValid() || !$client->isAccepted()) {
            $this->cancelLastOrderAndRestoreCart();
            $msg = $client->getResponse()->declineReason->buyerMessage;

            if ($msg == null) {
                $msg = $client->getResponse()->errorList->message;
            }

            Mage::getSingleton('core/session')->addError(Mage::helper('billsafe')->__($msg));
            Mage::getSingleton("core/session")->setBillsafeTransactionIdForPaymentInstructions(null);

            echo "<script>window.parent.location.href='" . Mage::getBaseUrl() . "/checkout/cart';</script>";

            return false;
        } else {
            Mage::getSingleton("core/session")->setBillsafeTransactionIdForPaymentInstructions($client->getResponse()->transactionId);
        }

        $quote = Mage::getModel("checkout/cart")->getQuote();
        $quote->collectTotals();
        $onepage = Mage::getSingleton('checkout/type_onepage');

        if (method_exists($onepage, "setQuote")) {
            // Magento 1.4.1.1 Bugfix
            $onepage->setQuote($quote);
        }

        $onepage->saveOrder();

        try {
            Mage::helper('billsafe')
                    ->getTransactionByTransactionId($token)
                    ->setTxnId($client->getResponseTransactionId())
                    ->save();
        } catch (Exception $exc) {
            $bla = 1;
        }


        $state = Mage::getStoreConfig('payment/billsafe/order_status');
        if ('pending' == $state) {
            $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        }

        $text = 'Successful BillSAFE payment.<br/>Transaction ID: %d.<br/>BillSAFE Transaction Status: ACCEPTED.';
        $notice = Mage::helper('billsafe')->__($text, $client->getResponseTransactionId());
        $this->getOrder()->setState($state, true, $notice)->save();

        try {
            $this->getOrder()->sendNewOrderEmail();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        // Truncate cart
        $cart = Mage::getSingleton('checkout/cart');

        foreach (Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item) {
            $cart->removeItem($item->getId());
        }

        $cart->save();

        if (Mage::helper("billsafe")->openInLayer()) {
            return $this->_redirect('billsafe/payment/success', array("_secure" => true));
        } else {
            // TODO: Also watch out for onestepcheckout
            return $this->_redirect('checkout/onepage/success', array("_secure" => true));
        }
    }

    protected function getOrder() {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        return Mage::getModel('sales/order')->load($orderId);
    }

    /**
     * Redirect to cart, display a flash message
     * 
     * @return void
     */
    public function cancellationAction() {
        $params = $this->getRequest()->getParams();
        $closePopup = false;

        if (array_key_exists("billsafe_close", $params)) {
            if ($params["billsafe_close"] == true) {
                $closePopup = true;
            }
        }

        if ($closePopup == true && Mage::helper("billsafe")->openInLayer() == 1) {
            echo "<script>document.getElementById('billsafeWrapper').style.display='none'</script>";
            return false;
        } else {
            $this->cancelLastOrderAndRestoreCart();
            // Set flash message
            $msg = 'Billsafe payment has been cancelled';
            Mage::getSingleton('core/session')->addNotice($this->getHelper()->__($msg));

            return $this->_redirect('checkout/cart');
        }
    }

    protected function cancelLastOrderAndRestoreCart() {
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
        $quote = Mage::getModel('sales/quote')->load($session->getLastQuoteId());

        // Recover quote
        $quote->setIsActive(true)->save(true);

        // Add coupon code if one is set
        $coupon = $order->getCouponCode();
        if (!is_null($coupon)) {
            $quote->setCouponCode($coupon)->save();
        }

        // Cancel order
        try {
            $this->getHelper()->cancelOrder($order);
        } catch (Exception $ex) {
            
        }
    }

    protected function getHelper() {
        return Mage::helper('billsafe');
    }

    public function getsettlementAction() {
        $parameter = $this->getRequest()->getParams();
        $order = Mage::getModel("sales/order")->load($parameter["order_id"]);
        $arguments = $this->getSoapArguments("getSettlement", $order);

        $client = Mage::getSingleton("billsafe/client");
        $response = $client->getSettlement($arguments);

        $filename = "billsafe-settlement-" . date("YmdHis", time()) . ".csv";

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Description: csv File");
        header("Pragma: no-cache");
        header("Expires: 0");

        if ($response->ack == "OK") {
            $data = base64_decode($response->data);
            echo $data;
        } else {
            echo $response->errorList->message;
        }
    }

    public function reportdirectpaymentAction() {
        $parameter = $this->getRequest()->getParams();
        $order = Mage::getModel("sales/order")->load($parameter["order_id"]);
        $arguments = $this->getSoapArguments("reportDirectPayment", $order);

        $arguments["transactionId"] = urlencode($parameter["token"]);
        $arguments["amount"] = urlencode($parameter["amount"]);
        $arguments["currencyCode"] = "EUR";
        $arguments["date"] = urlencode($parameter["date"]);

        $client = Mage::getSingleton("billsafe/client");
        $response = $client->reportDirectPayment($arguments);

        $order_id = $parameter["order_id"];

        $this->_redirect("adminhtml/sales_order/view", array(
            'order_id' => $order_id,
            'billsafe_reportdirectpayment' => ($response->ack == "OK" ? 1 : 0)));
    }

    public function pausetransactionAction() {
        $parameter = $this->getRequest()->getParams();
        $order = Mage::getModel("sales/order")->load($parameter["order_id"]);
        $arguments = $this->getSoapArguments("pauseTransaction", $order);

        $order_id = $parameter["order_id"];

        #$arguments["transactionId"] = urlencode("11573609");
        $arguments["transactionId"] = urlencode($parameter["token"]);
        $arguments["pause"] = $parameter["amount"];

        $client = Mage::getSingleton("billsafe/client");
        $response = $client->pauseTransaction($arguments);

        $this->_redirect("adminhtml/sales_order/view", array(
            'order_id' => $order_id,
            'billsafe_pausetransaction' => ($response->ack == "OK" ? 1 : 0)));
    }

    public function gettokenAction($gateway = false) {
        $quote = Mage::getModel("checkout/cart")->getQuote();
        $client = Mage::getModel("billsafe/client");

        $client->prepareOrderByQuote($quote);
        $response = $client->getResponse();
        $token = $client->getResponseToken();

        if ($gateway == false) {
            if ($response->ack != "OK") {
                echo "<script>window.parent.document.getElementById('billsafeWrapper').style.display='none';window.parent.alert('" . Mage::helper("billsafe")->__($response->errorList->message) . "')</script>";
            } else {
                echo "Here is a div id'ed \"BillSAFE_Token\" which has the value: <div id='BillSAFE_Token'>$token</div>";
            }
        } else {
            $live_gateway = Mage::getStoreConfig("payment/billsafe/live_gateway_url");
            $sandbox_gateway = Mage::getStoreConfig("payment/billsafe/sandbox_gateway_url");
            $sandbox_mode = Mage::getStoreConfig("payment/billsafe/sandbox");
            $gateway_url = ($sandbox_mode == true ? $sandbox_gateway : $live_gateway);

            if ($response->ack != "OK") {
                // set error, redirect to cart
                #throw new Exception($this->__("BillSAFE Request was not successful!"));
                Mage::getSingleton("core/session")->addError($this->__("BillSAFE Request was not successful!"));
                Mage::app()->getResponse()->setRedirect(Mage::getBaseUrl() . "checkout/cart");
                Mage::log($this->__("BillSAFE Request was not successful!") . " -> " . $response->errorList->message . Mage::helper("billsafe")->prepareObjectForLog($quote));
            } else {
                // redirect to gateway url
                Mage::app()->getResponse()->setRedirect($gateway_url . "?token=" . $token);
            }
        }
    }

    public function successAction() {
        $url = Mage::getUrl("checkout/onepage/success", array("_secure" => $_SERVER["HTTPS"] === "on"));
        $output = '<html><head><script type="text/javascript">if (top.lpg) { top.lpg.close("' . $url . '"); } //--></script></head></html>';

        echo $output;
    }

    private function getSoapArguments($method, $order = null) {
        $arguments = array();

        if ($order != null) {
            $store_id = $order->getStoreId();
            
            $arguments["merchant"]["id"] = Mage::getStoreConfig("payment/billsafe/merchant_id", $store_id);
            $arguments["merchant"]["license"] = Mage::getStoreConfig("payment/billsafe/merchant_license", $store_id);
        } else {
            $arguments["merchant"]["id"] = Mage::getStoreConfig("payment/billsafe/merchant_id");
            $arguments["merchant"]["license"] = Mage::getStoreConfig("payment/billsafe/merchant_license");
        }

        $arguments["application"]["signature"] = Mage::getStoreConfig("payment/billsafe/application_signature");
        $arguments["application"]["version"] = (string) Mage::getConfig()->getNode()->modules->AwHh_Billsafe->version;
        $arguments["method"] = $method;
        $arguments["format"] = "JSON";

        return $arguments;
    }

    public function prepareorderAction() {
        $this->gettokenAction(true);
    }

}
