<?php
class Netresearch_Billsafe_Block_Adminhtml_System_Config_Paymentfeecheck
    extends Mage_Adminhtml_Block_Template
{
    /**
     * Check if Netresearch_PaymentFee - Module is installed or not
     *
     * @return boolean
     */
    public function isModulePaymentFeeInstalled()
    {
        if (Mage::getConfig()->getNode('modules/Netresearch_PaymentFee')
            === false) {
            return false;
        } else {
            return true;
        }
    }
}
