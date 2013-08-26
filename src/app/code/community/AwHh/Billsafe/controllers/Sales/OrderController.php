<?php

include_once("Mage/Adminhtml/controllers/Sales/OrderController.php");

class AwHh_Billsafe_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController {    
    /**
     * View order detale
     */
    public function viewAction()
    {
        $this->_title($this->__('Sales'))->_title($this->__('Orders'));
        
        $params = $this->getRequest()->getParams();
        
        if(array_key_exists("billsafe_reportdirectpayment", $params)) {
            if($params["billsafe_reportdirectpayment"] == "1") {
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper("adminhtml")->__("Direct payment was successfully reported"));
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper("adminhtml")->__("Direct payment was NOT successfully reported"));
            }
        }
        
        if(array_key_exists("billsafe_pausetransaction", $params)) {
            if($params["billsafe_pausetransaction"] == "1") {
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper("adminhtml")->__("Pause Transaction was successfully reported"));
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper("adminhtml")->__("Pause Transaction was NOT successfully reported"));
            }
        }

        if ($order = $this->_initOrder()) {
            $this->_initAction();

            $this->_title(sprintf("#%s", $order->getRealOrderId()));

            $this->renderLayout();
        }
    }
}