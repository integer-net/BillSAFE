<?php

/**
 * Netresearch_Billsafe_Model_Config_Maxamount
 *
 * @author Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Billsafe_Model_Config_Maxamount extends Netresearch_Billsafe_Model_Config_Abstract
{

    /**
     * Set maximum to billsafe maximum if its larger then allowed.
     *
     * @return void
     */
    public function _afterLoad()
    {
        $config = $this->getTempConfig();
        $max = $this->getMax();
        if ($max && ($max < $this->getValue())) {
            $this->setValue($max);
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
        $scopeId = $this->getScopeId();
        $config = $this->getTempConfig();
        if ($config->isActive($scopeId)) {
            if (0 == strlen($config->getMerchantId($scopeId)) || 0 == strlen($config->getMerchantLicense($scopeId))
            ) {
                throw new Netresearch_Billsafe_Model_Config_Exception(Mage::helper('billsafe')->__(
                        'Please enter your BillSAFE credentials.'
                ));
            }

            if ($this->getValue() == '') {
                $msg = 'Maximum order amount is a required entry!';
                throw new Netresearch_Billsafe_Model_Config_Exception(Mage::helper('billsafe')->__($msg));
            }

            $max = $this->getMax();
            if (is_null($max)) {
                $max = INF;
            }

            if ($max < $this->getValue()) {
                $msg = 'Maximum order amount exceeds the allowed maximum by BillSAFE of %s.';
                throw new Netresearch_Billsafe_Model_Config_Exception(
                    Mage::helper('billsafe')->__($msg, $max)
                );
            }

            $this->restoreConfig();
        }

        parent::_beforeSave();
    }

    /**
     * gets the max amount for the payment from BillSAFE
     *
     * @return float - the maximum amount of the payment fee or null if it couldn't be obtained
     */
    protected function getMax()
    {
        $max = null;
        try {
            $max = $this->getTempConfig()->getMaxAmount();
        } catch (Exception $e) {
            $this->getDataHelper()->log('error obtaining the max fee amount ' . $e->getMessage());
            Mage::logException($e);
        }
        return $max;
    }

}
