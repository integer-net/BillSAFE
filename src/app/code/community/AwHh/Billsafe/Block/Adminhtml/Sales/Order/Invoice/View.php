<?php

class AwHh_Billsafe_Block_Adminhtml_Sales_Order_Invoice_View extends Mage_Adminhtml_Block_Sales_Order_Invoice_View {
    
    public function __construct() {
        parent::__construct();
        $this->_removeButton("capture");
    }
}