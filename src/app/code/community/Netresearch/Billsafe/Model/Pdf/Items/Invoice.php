<?php

class Netresearch_Billsafe_Model_Pdf_Items_Invoice
    extends Symmetrics_InvoicePdf_Model_Pdf_Invoice
{
    const SHOW_PAYMENT_PATH = 'sales_pdf/invoice/showpayment';

    /**
     * Rewrite to deactivate the payment in invoice header if billsafe is payment method
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Sales_Model_Order $order
     * @param bool $putOrderId
     */
    protected function _insertOrderInfo(&$page, $order, $putOrderId)
    {
        $storeId = $order->getStoreId();
        $showPayment = Mage::getStoreConfigFlag(self::SHOW_PAYMENT_PATH, $storeId);

        if ($order->getPayment()->getMethod() === Netresearch_Billsafe_Model_Payment::CODE) {
            Mage::app()->getStore($storeId)->setConfig(self::SHOW_PAYMENT_PATH, false);
        }

        // process the standard _insertOrderInfo
        parent::_insertOrderInfo($page, $order, $putOrderId);

        // restore original value
        Mage::app()->getStore($storeId)->setConfig(self::SHOW_PAYMENT_PATH, $showPayment);
    }
}
