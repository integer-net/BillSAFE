<?php

class AwHh_Billsafe_Helper_Data extends Mage_Payment_Helper_Data {

    const LOG_FILE_NAME = 'billsafe.log';

    /**
     * Returns config model
     * 
     * @return AwHh_Billsafe_Model_Config
     */
    public function getConfig() {
        return Mage::getSingleton('billsafe/config');
    }

    /**
     * Checks if logging is enabled and if yes, logs given message to logfile
     * 
     * @param string $message
     * @param int $level
     */
    public function log($message, $level = null) {
        if ($this->getConfig()->shouldLogRequests()) {
            Mage::log($message, $level, self::LOG_FILE_NAME);
        }
    }

    /**
     * Cancel given order
     * 
     * @param Mage_Sales_Model_Order $order 
     * @return void
     */
    public function cancelOrder(Mage_Sales_Model_Order $order) {
        if ($order->canCancel()) {
            $order->registerCancellation();
            return $order->save();
        } else {
            try {
                if (count($order->getData()) == 0 || count($order->getOrigData()) == 0) {
                    return true;
                } else {
                    return $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
                }
            } catch (Exception $ex) {
                return true;
            }
        }
    }

    /**
     * Fetches transaction with given transaction id
     * 
     * @param string $txnId 
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    public function getTransactionByTransactionId($transactionId) {
        if (!$transactionId) {
            return;
        }
        $transaction = Mage::getModel('sales/order_payment_transaction')
                ->getCollection()
                ->addAttributeToFilter('txn_id', $transactionId)
                ->getLastItem();

        try {
            $transaction->getOrderPaymentObject();
        } catch (Exception $ex) {
            
        }

        return $transaction;
    }

    /**
     * Checks, if order is already invoiced. 
     * 
     * @param Mage_Sales_Model_Order $order 
     * @return boolean
     */
    public function isOrderAlreadyInvoiced($order) {
        return $order->getInvoiceCollection()->count() > 0;
    }

    /**
     * Checks, if invoice is together with shipment.
     *
     * @return boolean
     */
    public function isDoShipment() {
        if (!($request = Mage::app()->getRequest())) {
            return false;
        }
        if (!($post = $request->getPost())) {
            return false;
        }
        if (array_key_exists('invoice', $post)) {
            if (array_key_exists('do_shipment', $post['invoice'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks, if invoice is created without capturing online
     *
     * @return boolean
     */
    public function isCaptureOffline() {
        if (!($request = Mage::app()->getRequest())) {
            return false;
        }
        if (!($post = $request->getPost())) {
            return false;
        }
        if (array_key_exists('invoice', $post)) {
            if (array_key_exists('capture_case', $post['invoice']) && $post['invoice']['capture_case'] != 'online'
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * if order item is fee item
     * 
     * @param mixed $item 
     *
     * @return boolean
     */
    public function isFeeItem($item) {
        $config = Mage::getSingleton('billsafe/config');
        return ($config->isPaymentFeeEnabled() && $config->getPaymentFeeSku() == $item->getSku());
    }

    /*
     * returns if payment should been opened in layer or not
     * 
     * @return boolean
     * 
     */

    public function openInLayer() {
        $setting = Mage::getStoreConfig("payment/billsafe/open_in_layer");
        return $setting;
    }

    public function createdFromBackend() {
        $url = Mage::getUrl("*/");
        return (strpos($url, "admin") == false ? false : true);
    }

    public function getTransactionResultByToken($token) {
        $response = Mage::getModel('billsafe/client')->getTransactionResult($token);

        return $response;
    }

    private function getSoapArguments($method) {
        $arguments = array();

        $arguments["merchant"]["id"] = Mage::getStoreConfig("payment/billsafe/merchant_id");
        $arguments["merchant"]["license"] = Mage::getStoreConfig("payment/billsafe/merchant_license");
        $arguments["application"]["signature"] = Mage::getStoreConfig("payment/billsafe/application_signature");
        $arguments["application"]["version"] = (string) Mage::getConfig()->getNode()->modules->AwHh_Billsafe->version;
        $arguments["method"] = $method;
        $arguments["format"] = "JSON";

        return $arguments;
    }

    public function isOneStepCheckoutActive() {
        return Mage::getConfig()->getModuleConfig('Idev_OneStepCheckout')->is('active', 'true');
    }

    //////////////////////////////////////////////////////////////////////////////
    // THANKS FOR THE FOLLOWING 3 FUNCTIONS TO: https://gist.github.com/1541793 //
    //////////////////////////////////////////////////////////////////////////////

    public function isMageEnterprise() {
        return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise') && Mage::getConfig()->getModuleConfig('Enterprise_AdminGws') && Mage::getConfig()->getModuleConfig('Enterprise_Checkout') && Mage::getConfig()->getModuleConfig('Enterprise_Customer');
    }

    /**
     * True if the version of Magento currently being rune is Enterprise Edition
     */
    public function isMageProfessional() {
        return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise') && !Mage::getConfig()->getModuleConfig('Enterprise_AdminGws') && !Mage::getConfig()->getModuleConfig('Enterprise_Checkout') && !Mage::getConfig()->getModuleConfig('Enterprise_Customer');
    }

    /**
     * True if the version of Magento currently being rune is Enterprise Edition
     */
    public function isMageCommunity() {
        return !$this->isMageEnterprise() && !$this->isMageProfessional();
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////

    public function getBillsafeWrapperCloseText() {
        return "<script>document.getElementById('billsafeWrapper').style.display='none'</script>";
    }

    public function format($number) {
        return number_format($number, 2, '.', '');
    }

    public function cancelBillsafeTransaction($payment, $order) {
        $transaction_id = $payment->getMethodInstance()->getInfoInstance()->getTransactionId();
        return Mage::getModel("billsafe/client")->updateArticleListNoArticles($order, $transaction_id);
    }

    public function isSandbox() {
        $model = Mage::getModel("billsafe/payment");
        $value = $model->getConfigData("sandbox");
        return $value;
    }

    public function prepareObjectForLog($object) {
        $data = $object->debug();
        $output = "";

        foreach ($data as $key => $value) {
            $output .= $key . " => " . $value . ", ";
        }

        return $output;
    }

    /*
     * Checks, if all items of the order are already shipped
     * 
     * @param Mage_Sales_Model_Order $order 
     * 
     * @return boolean
     */

    public function isOrderCompletelyShipped($order) {
        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyToShip() > 0 && !$item->getIsVirtual() && !$item->getLockedDoShip()) {
                return false;
            }
        }

        return true;
    }

}

