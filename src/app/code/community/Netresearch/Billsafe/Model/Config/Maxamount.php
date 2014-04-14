<?php

/**
 * Netresearch_Billsafe_Model_Config_Maxamount
 *
 * @author Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Billsafe_Model_Config_Maxamount
    extends Netresearch_Billsafe_Model_Config_Abstract
{
    /**
     * Set maximum fee to billsafe maximum if its larger then allowed.
     *
     * @return void
     */
    public function _afterLoad()
    {
        $storeId = Mage::app()->getStore()->getId();
        $config = $this->getTempConfig();
        if ($config->isPaymentFeeEnabled($storeId)) {
            $max = $this->getMax();
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
        $storeId = Mage::app()->getStore()->getId();
        $config = $this->getTempConfig();
        if ($config->isActive($storeId)) {
            if (0 == strlen($config->getMerchantId())
                || 0 == strlen($config->getMerchantLicense($storeId))
            ) {
                throw new Exception(Mage::helper('billsafe')->__(
                    'Please enter your BillSAFE credentials.'
                ));
            }
            if ($config->isPaymentFeeEnabled($storeId)) {
                if ($this->getValue() == '') {
                    $msg = 'Maximum order amount is a required entry!';
                    throw new Exception(Mage::helper('billsafe')->__($msg));
                }
                $max = $this->getMax();
                if (is_null($max)) {
                    throw new Exception(Mage::helper('billsafe')->__(
                        'No connection to BillSAFE. Please check your credentials.'
                    ));
                }
                if ($max < $this->getValue()) {
                    $msg
                        = 'Maximum order amount exceeds the allowed maximum by BillSAFE of %s.';
                    throw new Exception(Mage::helper('billsafe')->__(
                        $msg, $max
                    ));
                }
            }
            $this->restoreConfig();
        }

        parent::_beforeSave();
    }

    /**
     * gets the max amount for the payment fee from BillSAFE
     *
     * @return float - the maximum amount of the payment fee or null if it couldn't be obtained
     */
    protected function getMax()
    {
        $max = null;
        try {
            $max = Mage::getModel('billsafe/config')->getFeeMaxAmount();
        } catch (Exception $e) {
            $this->getDataHelper()->log('error obtaining the max fee amount ' . $e->getMessage());
            Mage::logException($e);
        }
        return $max;
    }
}
