<?php

/**
 * AwHh_Billsafe_Model_Config_Maxamount
 *
 * @author Christian Schrut <cschrut@agenturwerft.de>
 */
class AwHh_Billsafe_Model_Config_Maxamount extends AwHh_Billsafe_Model_Config_Abstract
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
            $max = Mage::getSingleton('billsafe/client')->setConfig($config)->getFeeMaxAmount();
            if ($max < $this->getValue()) {
                $this->setValue($max);
            }
        }
        $this->restoreConfig();
        parent::_afterLoad();
    }

    /**
     * Check if maximum order amount does not exceed billsafe maximum.
     *
     * @return void
     */
    public function _beforeSave()
    {
        $config = $this->getTempConfig();
        if ($config->isActive()) {
            if (0 == strlen($config->getMerchantId()) || 0 == strlen($config->getMerchantLicense())) {
                throw new Exception(Mage::helper('billsafe')->__('Please enter your BillSAFE credentials.'));
            }
            if ($config->isPaymentFeeEnabled()) {
                if ($this->getValue() == '') {
                    $msg = 'Maximum order amount is a required entry!';
                    throw new Exception(Mage::helper('billsafe')->__($msg));
                }
                $max = Mage::getSingleton('billsafe/client')->setConfig($config)->getFeeMaxAmount();
                if (is_null($max)) {
                    throw new Exception(Mage::helper('billsafe')->__('No connection to BillSAFE. Please check your credentials.'));
                }
                if ($max < $this->getValue()) {
                    $msg = 'Maximum order amount exceeds the allowed maximum by BillSAFE of %s.';
                    Mage::getSingleton("core/session")->addWarning(Mage::helper("core")->__($msg, $max));
                }
            }
            $this->restoreConfig();
        }

        parent::_beforeSave();
    }
}
