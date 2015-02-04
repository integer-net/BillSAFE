<?php
class Netresearch_Billsafe_Block_Payment_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('billsafe/payment/form.phtml');

        $storeId = $this->_getStoreId();
        $config  = $this->_getBillsafeConfig();

        $logo        = $config->getBillsafeLogoUrl($storeId);
        $methodTitle = $config->getBillsafeTitle($storeId);

        if ($logo) {
            $this->setMethodTitle('');
            $this->setMethodLabelAfterHtml(sprintf(
                '<img src="%s" alt="%s" title="%s"/>',
                $logo,
                $methodTitle,
                $methodTitle
            ));
        }
    }

    /**
     * @return Netresearch_Billsafe_Helper_Data
     */
    protected function getDataHelper()
    {
        return Mage::helper('billsafe/data');
    }

    /**
     * @return Netresearch_Billsafe_Helper_Customer
     */
    protected function getCustomerHelper()
    {
        return Mage::helper('billsafe/customer');
    }

    /**
     * @return Netresearch_Billsafe_Helper_Order
     */
    protected function getOrderHelper()
    {
        return Mage::helper('billsafe/order');
    }

    /**
     * Obtain BillSAFE Config
     * @return Netresearch_Billsafe_Model_Config
     */
    protected function _getBillsafeConfig()
    {
        return $this->getDataHelper()->getConfig();
    }

    /**
     * Obtain store ID from current quote
     */
    protected function _getStoreId()
    {
        return $this->getQuote()->getStoreId();
    }

    /**
     * Obtain quote from session
     * @return Ambigous <Mage_Sales_Model_Quote, NULL>
     */
    public function getQuote()
    {
        return $this->getDataHelper()->getQuotefromSession();
    }

    /**
     * Try to obtain the customer's date of birth.
     *
     * @return string Date of Birth if available, empty string otherwise
     */
    public function getCustomerDob()
    {
        // try to obtain from quote payment
        $infoDob = $this->getInfoData('dob');
        if ($infoDob) {
            return $infoDob;
        }

        // try to obtain from checkout session
        $quoteDob = Mage::getSingleton('checkout/session')->getData('customer_dob');
        if ($quoteDob) {
            return $quoteDob;
        }

        // try to obtain from customer session
        $quote = $this->getQuote();
        $quoteDob = $quote->getCustomerDob();
        if ($quoteDob) {
            return $quoteDob;
        }

        // if not set before and customer is guest, return empty string
        if ($quote->getCustomerIsGuest()) {
            return '';
        }

        // at last, try to obtain from customer model
        return $quote->getCustomer()->getDob();
    }

    /**
     * get the company from the quote billing address
     *
     * @return string
     */
    public function getCustomerCompany()
    {

        return $this->getCustomerHelper()->getCustomerCompany();

    }

    /**
     *
     * @return true if billsafe direct is enabled for checkout, false otherwise
     */
    public function isBillSafeDirectEnabled()
    {
        return $this->getOrderHelper()->isBillsafeOnsiteCheckout($this->getQuote());
    }

    /**
     * Return additional text for payment fee if any
     *
     * @return string
     */
    public function getFeeText()
    {
        // Return empty string if payment fee is disabled
        $storeId = $this->_getStoreId();
        if (!$this->_getBillsafeConfig()->isPaymentFeeEnabled($storeId)) {
            return '';
        }

        // Return empty string if no fee is applicable
        $fee = Mage::helper('paymentfee')->getUpdatedFeeProduct();
        if (!$fee instanceof Mage_Catalog_Model_Product || $fee->getPrice() <= 0) {
            return '';
        }

        // Return calculated fee
        $feeText = $this->__(
            'Using BillSAFE will cause an additional payment Fee of %s.',
            $fee->getCheckoutDescription()
        );
        return $feeText;
    }

    /**
     *
     * returns the public key for the merchant
     *
     * @return mixed
     */
    public function getMerchantPublicKey()
    {
        $storeId = $this->_getStoreId();
        return $this->_getBillsafeConfig()->getPublicKey($storeId);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        Mage::dispatchEvent('payment_form_block_to_html_before', array(
            'block'     => $this
        ));
        return parent::_toHtml();
    }

}