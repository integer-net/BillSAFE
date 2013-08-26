<?php

class AwHh_Billsafe_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
                
        if($this->getBillsafeData("AccountNumber") == "XXXXX") {
            $this->setTemplate('billsafe/info_installment.phtml');
        } else {
            $this->setTemplate('billsafe/info.phtml');
        }
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
        if ($this->isInvoice()) {
            return $this->getInvoice()->getOrder();
        }
        return Mage::registry('current_order');
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
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('BankCode');
        }
    }

    /**
     * Return payments AccountNumber.
     * 
     * @return string
     */
    public function getAccountNumber()
    {
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('AccountNumber');
        }
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
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('Bic');
        }
    }

    /**
     * Return payments Iban.
     * 
     * @return string
     */
    public function getIban()
    {
        if ($this->hasBillsafeData()) {
            return $this->getBillsafeData('Iban');
        }
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
     * Get invoice related billsafe invoice 
     * 
     * @return AwHh_Billsafe_Model_Invoice
     */
    public function getBillsafeData($key = null)
    {
        if (!$this->getOrder()) {
            return $key ? '' : array();
        }
        $this->billsafeInvoiceData = $this->getOrder()->getPayment()->getAdditionalInformation();

        if (is_null($key)) {
            return $this->billsafeInvoiceData;
        }
        if($key && array_key_exists($key, $this->billsafeInvoiceData)) {
            return $this->billsafeInvoiceData[$key];
        }
        return '';
    }

    public function isBillsafeCancelled()
    {
        if ($this->hasBillsafeData() 
            and AwHh_Billsafe_Model_Payment::BILLSAFE_STATUS_CANCELLED == $this->getBillsafeData('BillsafeStatus')
        ) {
            return true;
        }
        return false;
    }
    
    public function getBillsafeHelper()
    {
        return Mage::helper('billsafe');
    }
}
