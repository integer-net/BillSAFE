<?php
class Netresearch_Billsafe_Model_Pdf_Items_Invoice_Info
    extends Symmetrics_InvoicePdf_Model_Pdf_Items_Invoice_Info
{
    /**
     * method to draw the info text block to the invoice
     *
     * @return void
     */
    public function draw()
    {
        $order  = $this->getOrder();
        $pdf    = $this->getPdf();
        $page   = $this->getPage();

        $helper = Mage::helper('invoicepdf');
        $font = $helper->getFont();
        $tableRowItem = Mage::getModel('invoicepdf/pdf_items_item');
        /* @var $tableRowItem Symmetrics_InvoicePdf_Model_Pdf_Items_Item */

        $infoText = $helper->getSalesPdfInvoiceConfigKey('infotxt', $order->getStore());
        if ($order->getPayment()->getMethod() === Netresearch_Billsafe_Model_Payment::CODE) {
            // append billsafe invoice data to info text. unfortunately there is no
            // straightforward, less invasive way to accomplish this…
            $infoText.= Mage::helper('payment')->getInfoBlock($order->getPayment())->toMrgPdf();
        }

        if (!empty($infoText)) {
            $tableRowItem = Mage::getModel('invoicepdf/pdf_items_item');
            $infoText = explode("\n", $infoText);
            $tableRowItem->addColumn('note', $infoText, 0, 'left', 0, $font, 10);
            $this->addRow($tableRowItem);
        }

        $page = $pdf->insertTableRow($page, $this);
        $this->setPage($page);
        $this->clearRows();
    }
}