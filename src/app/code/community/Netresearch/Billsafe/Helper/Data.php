<?php
class Netresearch_Billsafe_Helper_Data extends Mage_Payment_Helper_Data
{
    const LOG_FILE_NAME = 'billsafe.log';
    protected $_customerHelper = null;
    /**
     * Returns config model
     *
     * @return Netresearch_Billsafe_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('billsafe/config');
    }

    /**
     * @return Netresearch_Billsafe_Helper_Customer|null
     */
    public function getCustomerHelper()
    {
        if(is_null($this->_customerHelper)){
            $this->_customerHelper = Mage::helper('billsafe/customer');
        }
        return $this->_customerHelper;
    }

    /**
     * @param Netresearch_Billsafe_Helper_Customer $customerHelper
     */
    public function setCustomerHelper(Netresearch_Billsafe_Helper_Customer $customerHelper)
    {
        $this->_customerHelper = $customerHelper;
    }

    /**
     * Checks if logging is enabled and if yes, logs given message to logfile
     *
     * @param string $message
     * @param int $level
     */
    public function log($message, $level = null)
    {
        if ($this->getConfig()->shouldLogRequests($this->getStoreIdfromQuote())) {
            Mage::log($message, $level, self::LOG_FILE_NAME);
        }
    }

    /**
     * Fetches transaction with given transaction id
     *
     * @param string $txnId
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    public function getTransactionByTransactionId($transactionId)
    {
        if (!$transactionId) {
            return;
        }
        $transaction = Mage::getModel('sales/order_payment_transaction')
            ->getCollection()
            ->addAttributeToFilter('txn_id', $transactionId)
            ->getLastItem();
        $transaction->getOrderPaymentObject();
        return $transaction;
    }

    /**
     * Checks, if order is already invoiced.
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function isOrderAlreadyInvoiced($order)
    {
        return $order->getInvoiceCollection()->count() > 0;
    }

    /**
     * Checks, if invoice is together with shipment.
     *
     * @return boolean
     */
    public function isDoShipment()
    {
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
    public function isCaptureOffline()
    {
        if (!($request = Mage::app()->getRequest())) {
            return false;
        }
        if (!($post = $request->getPost())) {
            return false;
        }
        if (array_key_exists('invoice', $post)) {
            if (array_key_exists('capture_case', $post['invoice'])
                && $post['invoice']['capture_case'] != 'online'
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
    public function isFeeItem($item)
    {
        $config = $this->getConfig();
        $storeId = $this->getStoreIdfromQuote();
        return ($config->isPaymentFeeEnabled($storeId) && $config->getPaymentFeeSku($storeId) == $item->getSku());
    }

    /**
     *  get the quote from current session
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuotefromSession()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * get the store_id from quote
     *
     * @return int store_id
     */
    public function getStoreIdfromQuote()
    {
        return $this->getQuotefromSession()->getStoreId();
    }


    /**
     * Formats given number according to billsafe standard
     *
     * @param integer|float $number
     * @return string
     */
    public function format($number)
    {
        return number_format($number, 2, '.', '');
    }


    /**
     * Returns first not false value of given params.
     * @return mixed
     */
    public function coalesce()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            if ($arg) {
                return $arg;
            }
        }
        return NULL;
    }

    public function wrap($stringToWrap, $wrapAfterChars = 0, $lineSeparator = null)
    {
        if (0 < $wrapAfterChars && !is_null($lineSeparator)) {
            $stringToWrap = wordwrap($stringToWrap, $wrapAfterChars, $lineSeparator);
        }
        return $stringToWrap;
    }
}
