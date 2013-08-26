<?php

include_once("Mage/Adminhtml/controllers/Sales/Order/InvoiceController.php");

class AwHh_Billsafe_Sales_Order_InvoiceController extends Mage_Adminhtml_Sales_Order_InvoiceController {

    public function printAction() {
        $this->_initInvoice();
        
        if ($invoiceId = $this->getRequest()->getParam('invoice_id')) {
            if ($invoice = Mage::getModel('sales/order_invoice')->load($invoiceId)) {
                $model = Mage::getModel("billsafe/invoicepdf");
                #$model = Mage::getModel("sales/order_pdf_invoice"); // Original
                
                $fileName = 'invoice'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s'). '.pdf';
                
                $pdf = $model->getPdf(array($invoice));
                $this->_prepareDownloadResponse($fileName, $pdf->render(), 'application/pdf');
            }
        }
        else {
            $this->_forward('noRoute');
        }
    }
}
