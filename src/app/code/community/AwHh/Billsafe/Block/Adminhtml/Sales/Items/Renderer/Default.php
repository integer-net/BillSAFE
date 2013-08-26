<?php
class AwHh_Billsafe_Block_Adminhtml_Sales_Items_Renderer_Default extends Mage_Adminhtml_Block_Sales_Items_Renderer_Default
{
    /**
     * Rewrite base method due to bug in magento
     *
     * @return boolean
     */
    public function canEditQty()
    {
        /**
         * Disable editing of quantity of item if creating of shipment forced
         * and ship partially disabled for order,n
         */
        if ($this->getOrder()->getForcedDoShipmentWithInvoice()
            && !($this->canShipPartially($this->getOrder()) && $this->canShipPartiallyItem($this->getOrder()))
        ) {
            return false;
        }
        if ($this->getOrder()->getPayment()->canCapture()) {
            return $this->getOrder()->getPayment()->canCapturePartial();
        }
        return true;
    }
    
}
