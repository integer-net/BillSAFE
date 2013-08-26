<?php
class AwHh_Billsafe_Block_Adminhtml_Widget_Form_Container extends Mage_Adminhtml_Block_Sales_Order_Create {
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate("widget/form/container_billsafe.phtml");
    }    
}