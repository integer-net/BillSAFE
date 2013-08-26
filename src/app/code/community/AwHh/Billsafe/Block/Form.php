<?php
class AwHh_Billsafe_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $label = sprintf('<img src="%s"/>', Mage::getStoreConfig('payment/billsafe/billsafe_logo'));
        $this->setMethodTitle($label);

        return parent::_construct();
    }
}
