<?php
class Netresearch_Billsafe_Block_Customer_Widget_Dob extends Mage_Customer_Block_Widget_Dob
{

    public function _construct()
    {
        parent::_construct();

        // default template location
        $this->setTemplate('billsafe/customer/widget/dob.phtml');
    }

    /**
     * @return boolean True if BillSAFE direct is enabled, false otherwise.
     */
    public function isRequired()
    {
        $storeId = Mage::helper('billsafe/data')->getStoreIdfromQuote();
        return Mage::getModel('billsafe/config')->isBillSafeDirectEnabled(
            $storeId
        );
    }
}
