<?php

class AwHh_Billsafe_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {

    public function __construct() {
        parent::__construct();

        $order = $this->getOrder();

        // At first, remove all Buttons
        $this->_removeButton("order_ship");
        $this->_removeButton("order_creditmemo");

        // Check if creditmemo is allowed - then add button for creating a creditmemo
        if ($this->_isAllowedAction('creditmemo') && $order->canCreditmemo()) {
            $onClick = "setLocation('{$this->getCreditmemoUrl()}')";

            $this->_addButton('order_creditmemo', array(
                'label' => Mage::helper('sales')->__('Credit Memo'),
                'onclick' => $onClick,
                'class' => 'go'
            ));
        }

        // Check if not all items are sent - then add button for shipping
        if (!Mage::helper("billsafe")->isOrderCompletelyShipped($order)) {
            $this->_addButton('order_ship', array(
                'label' => Mage::helper('sales')->__('Ship'),
                'onclick' => 'setLocation(\'' . $this->getShipUrl() . '\')',
                'class' => 'go'
            ));
        }
    }

}