<?php
class Netresearch_Billsafe_Model_Pdf_Engine_Invoice_Default
    extends FireGento_Pdf_Model_Engine_Invoice_Default
{
    /**
     * Insert footer
     *
     * @param Zend_Pdf_Page $page Current page object of Zend_Pdf
     * @return void
     */
    protected function _insertFooter(&$page)
    {
        $page->setLineColor($this->colors['black']);
        $page->setLineWidth(0.5);
        $page->drawLine($this->margin['left'] - 20, $this->y - 5, $this->margin['right'] + 30, $this->y - 5);

        $this->Ln(15);
        $this->_insertFooterAddress($page);

        $fields = array(
            'telephone' => Mage::helper('firegento_pdf')->__('Telephone:'),
            'fax'       => Mage::helper('firegento_pdf')->__('Fax:'),
            'email'     => Mage::helper('firegento_pdf')->__('E-Mail:'),
            'web'       => Mage::helper('firegento_pdf')->__('Web:'),
        );
        $this->_insertFooterBlock($page, $fields, 70, 40, 140);

        if ($this->getOrder()->getPayment()->getMethod() != Netresearch_Billsafe_Model_Payment::CODE) {
            $fields = array(
                'bank_name'             => Mage::helper('firegento_pdf')->__('Bank name:'),
                'bank_account'          => Mage::helper('firegento_pdf')->__('Account:'),
                'bank_code_number'      => Mage::helper('firegento_pdf')->__('Bank number:'),
                'bank_account_owner'    => Mage::helper('firegento_pdf')->__('Account owner:'),
                'swift'                 => Mage::helper('firegento_pdf')->__('SWIFT:'),
                'iban'                  => Mage::helper('firegento_pdf')->__('IBAN:'),
            );
            $this->_insertFooterBlock($page, $fields, 215, 50, 140);
        }

        $fields = array(
            'tax_number'        => Mage::helper('firegento_pdf')->__('Tax number:'),
            'vat_id'            => Mage::helper('firegento_pdf')->__('VAT-ID:'),
            'register_number'   => Mage::helper('firegento_pdf')->__('Register number:'),
            'ceo'               => Mage::helper('firegento_pdf')->__('CEO:'),
        );
        $this->_insertFooterBlock($page, $fields, 355, 60, $this->margin['right'] - 355 - 10);
    }
}
