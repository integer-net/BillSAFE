<?php

class AwHh_Billsafe_Block_Adminhtml_Sales_Order_View_Tab_Billsafe extends Mage_Adminhtml_Block_Sales_Order_Abstract implements Mage_Adminhtml_Block_Widget_Tab_Interface {
    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Retrieve source model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getOrder();
    }

    /**
     * Retrieve order totals block settings
     *
     * @return array
     */
    public function getOrderTotalData()
    {
        return array(
            'can_display_total_due'      => true,
            'can_display_total_paid'     => true,
            'can_display_total_refunded' => true,
        );
    }

    public function getOrderInfoData()
    {
        return array(
            'no_use_order_link' => true,
        );
    }
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('aw/sales/order/view/tab/billsafe/content.phtml');
    }
    
    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return Mage::helper('sales')->__('BillSAFE informations');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('BillSAFE informations');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}