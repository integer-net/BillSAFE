<?php
class AwHh_Billsafe_Block_Adminhtml_System_Config_Paymentfeecheck extends Mage_Adminhtml_Block_Template
{
    /**
     * Check if AwHh_PaymentFee - Module is installed or not
     *
     * @return boolean
     */
    public function isModulePaymentFeeInstalled()
    {
        if (Mage::getConfig()->getNode('modules/AwHh_PaymentFee') === false):
            return false;
        else:
            return true;
        endif;
    }
}
