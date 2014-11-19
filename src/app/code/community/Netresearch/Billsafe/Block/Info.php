<?php
class Netresearch_Billsafe_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('billsafe/info.phtml');
    }

    /**
     * Returns code of payment method
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }

    /**
     * Build PDF content of info block
     *
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('billsafe/pdf/info.phtml');
        return $this->toHtml();
    }

    /**
     * Build plain content of info block for display in MRG invoice info text.
     *
     * @return string
     */
    public function toMrgPdf()
    {
        $this->setTemplate('billsafe/pdf/mrg.phtml');
        return $this->toHtml();
    }

    /**
     * Checks if current item is an order
     *
     * @return void
     */
    public function isOrder()
    {
        return !is_null($this->getOrder());
    }

    /**
     * Checks if current item is an invoice
     *
     * @return void
     */
    public function isInvoice()
    {
        return !is_null($this->getInvoice());
    }

    /**
     * Checks if current order or invoice already has invoice information
     *
     * @return boolean
     */
    public function hasBillsafeData()
    {
        return count($this->getBillsafeData()) > 0;
    }

    /**
     * Return current order, null if no order is displayed.
     *
     * @return mixed
     */
    public function getOrder()
    {
        $order = $this->getInfo()->getOrder();
        return $order ? $order : Mage::registry('current_order');
    }

    /**
     * Return current invoice, null if no invoice is displayed.
     *
     * @return mixed
     */
    public function getInvoice()
    {
        $this->invoice = Mage::registry('current_invoice');
        if (is_null($this->invoice)) {
            $this->invoice = Mage::registry('invoice');
        }
        return $this->invoice;
    }

    /**
     * Return payments BankCode.
     *
     * @return string
     */
    public function getBankCode()
    {
        $returnValue = "";
        if ($this->hasBillsafeData()) {
            $returnValue = $this->getBillsafeData('BankCode');
        }
        return $returnValue;
    }

    /**
     * Return payments AccountNumber.
     *
     * @return string
     */
    public function getAccountNumber()
    {
        $returnValue = "";
        if ($this->hasBillsafeData()) {
            $returnValue = $this->getBillsafeData('AccountNumber');
        }
        return $returnValue;
    }

    /**
     * Return payments recipient.
     *
     * @return string
     */
    public function getRecipient()
    {
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('Recipient');
        }
    }

    /**
     * Return payments BankName.
     *
     * @return string
     */
    public function getBankName()
    {
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('BankName');
        }
    }

    /**
     * Return payments Bic.
     *
     * @return string
     */
    public function getBic()
    {
        $returnValue = "";
        if ($this->hasBillsafeData()) {
            $returnValue = $this->getBillsafeData('Bic');
        }
        return $returnValue;
    }

    /**
     * Return payments Iban.
     *
     * @return string
     */
    public function getIban()
    {
        $returnValue = "";
        if ($this->hasBillsafeData()) {
            $returnValue = $this->getBillsafeData('Iban');
        }
        return $returnValue;
    }

    /**
     * Return payments Reference.
     *
     * @return string
     */
    public function getReference()
    {
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('Reference');
        }
    }

    /**
     * Return payments Amount.
     *
     * @return string
     */
    public function getAmount()
    {
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('Amount');
        }
    }

    /**
     * Return payments CurrencyCode.
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('CurrencyCode');
        }
    }

    /**
     * Return payments Note.
     *
     * @return string
     */
    public function getNote()
    {
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('Note');
        }
    }

    /**
     * Return legal Note
     *
     * @return string
     */
    public function getLegalNote()
    {
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('legalNote');
        }
    }

    /**
     * Get invoice related billsafe data
     *
     * @return string|array
     */
    public function getBillsafeData($key = null)
    {
        if (!$this->getOrder()) {
            return $key ? '' : array();
        }
        $this->billsafeInvoiceData = $this->getOrder()->getPayment()
            ->getAdditionalInformation();

        if (is_null($key)) {
            return $this->billsafeInvoiceData;
        }
        if ($key && array_key_exists($key, $this->billsafeInvoiceData)) {
            return $this->billsafeInvoiceData[$key];
        }
        return '';
    }

    /**
     * Obtain BillSAFE data for PDF display. Take care of line breaks for earlier Magento versions.
     * @param string $key
     * @param string $label
     * @param int $length
     * @param string $separator
     */
    public function getBillsafePdfData($key, $label = null, $length = 45, $separator = '{{pdf_row_separator}}')
    {
        if ($label) {
            $data = sprintf("%s: %s", $this->__($label), $this->getBillsafeData($key));
        } else {
            $data = $this->getBillsafeData($key);
        }

        $mageSalesVersion = Mage::getConfig()->getModuleConfig('Mage_Sales')->version;
        if (version_compare($mageSalesVersion, '1.6.0.4', '>')) {
            return $data;
        }

        $data = Mage::helper('core/string')->str_split($data, $length, true, true);
        $data = implode($separator, $data);

        return $data;
    }

    public function isBillsafeCancelled()
    {
        if ($this->hasBillsafeData()
        and Netresearch_Billsafe_Model_Payment::BILLSAFE_STATUS_CANCELLED
        == $this->getBillsafeData('BillsafeStatus')) {
            return true;
        }
        return false;
    }

    public function getBillsafeHelper()
    {
        return Mage::helper('billsafe');
    }

    public function getBillsafeTitle()
    {
        return Mage::getModel('billsafe/config')->getBillsafeTitle();
    }
}
