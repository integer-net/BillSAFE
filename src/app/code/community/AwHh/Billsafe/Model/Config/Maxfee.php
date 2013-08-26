<?php

/**
 * AwHh_Billsafe_Model_Config_Maxfee
 *
 * @uses Mage
 * @uses _Core_Model_Config_Data
 * @author Christian Schrut <cschrut@agenturwerft.de>
 */
class AwHh_Billsafe_Model_Config_Maxfee extends AwHh_Billsafe_Model_Config_Abstract
{
    /**
     * Set maximum fee to billsafe maximum if its larger then allowed.
     *
     * @return void
     */
    public function _afterLoad()
    {
        $config = $this->getTempConfig();
        if ($config->isPaymentFeeEnabled()) {
            $max = Mage::getSingleton('billsafe/client')->setConfig($config)->getMaxFee();
            if ($max < $this->getValue()) {
                $this->setValue($max);
            }
        }
        $this->restoreConfig();
        parent::_afterLoad();
    }

    /**
     * Check if maximum fee does not exceed billsafe maximum.
     *
     * @return void
     */
    public function _beforeSave()
    {
        $config = $this->getTempConfig();
        if ($config->isActive()
            && $config->isPaymentFeeEnabled()
            && strlen($config->getMerchantId())
            && strlen($config->getMerchantLicense())
        ) {
            if ($this->getValue() == '') {
                $msg = 'Maximum/Default fee is required entry!';
                throw new Exception(Mage::helper('billsafe')->__($msg));
            }
            $max = Mage::getSingleton('billsafe/client')->setConfig($config)->getMaxFee();
            if (is_null($max)) {
                throw new Exception(Mage::helper('billsafe')->__('No connection to BillSAFE. Please check your credentials.'));
            }
            if ($max < $this->getValue()) {
                $msg = 'Maximum/Default fee %s exceeded the allowed maximum by BillSAFE of %s.';
                throw new Exception(Mage::helper('billsafe')->__($msg, $this->getValue(), $max));
            }
        }
        $this->restoreConfig();
        
        parent::_beforeSave();
    }
}
