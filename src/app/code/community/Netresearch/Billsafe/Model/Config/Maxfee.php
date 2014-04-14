<?php

/**
 * Netresearch_Billsafe_Model_Config_Maxfee
 *
 * @uses   Mage
 * @uses   _Core_Model_Config_Data
 * @author Stephan Hoyer <stephan.hoyer@netresearch.de>
 */
class Netresearch_Billsafe_Model_Config_Maxfee
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
            $max = $this->getMaxFee();
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
        $storeId = Mage::app()->getStore()->getId();
        $config = $this->getTempConfig();
        if ($config->isActive($storeId)
            && $config->isPaymentFeeEnabled($storeId)
            && strlen($config->getMerchantId($storeId))
            && strlen($config->getMerchantLicense($storeId))
        ) {
            $dataHelper = $this->getDataHelper();
            if ($this->getValue() == '') {
                $msg = 'Maximum/Default fee is required entry!';
                throw new Exception($dataHelper->__($msg));
            }
            $max = $this->getMaxFee();
            if (is_null($max)) {
                throw new Exception($dataHelper->__(
                    'No connection to BillSAFE. Please check your credentials.'
                ));
            }
            if ($max < $this->getValue()) {
                $msg
                    = 'Maximum/Default fee %s exceeded the allowed maximum by BillSAFE of %s.';
                throw new Exception($dataHelper->__(
                    $msg, $this->getValue(), $max
                ));
            }
        }
        $this->restoreConfig();

        parent::_beforeSave();
    }

    /**
     * gets the payment fee from BillSAFE
     *
     * @return float the amount for the payment fee, null if it couldn't be obtained
     */
    protected function getMaxFee()
    {
        $max = null;
        try {
            $max = Mage::getModel('billsafe/config')->getMaxFee();
        } catch (Exception $e) {
            $this->getDataHelper()->log('error obtaining the max fee ' . $e->getMessage());
            Mage::logException($e);
        }
        return $max;
    }
}
