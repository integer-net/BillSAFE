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
        $config = $this->getTempConfig();
        if ($config->isPaymentFeeEnabled()) {
            $max = $this->getMaxFee();
            if ($max && ($max < $this->getValue())) {
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
        $scopeId = $this->getScopeId();
        $config = $this->getTempConfig();
        if ($config->isActive($scopeId)
            && $config->isPaymentFeeEnabled($scopeId)
            && strlen($config->getMerchantId($scopeId))
            && strlen($config->getMerchantLicense($scopeId))
        ) {
            $dataHelper = $this->getDataHelper();
            if ($this->getValue() == '') {
                $msg = 'Maximum/Default fee is required entry!';
                throw new Netresearch_Billsafe_Model_Config_Exception($dataHelper->__($msg));
            }

            $max = $this->getMaxFee();
            if (is_null($max)) {
                $max = INF;
            }

            if ($max < $this->getValue()) {
                $msg = 'Maximum/Default fee %s exceeded the allowed maximum by BillSAFE of %s.';
                throw new Netresearch_Billsafe_Model_Config_Exception(
                    $dataHelper->__($msg, $this->getValue(), $max)
                );
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
            $max = $this->getTempConfig()->getMaxFee();
        } catch (Exception $e) {
            $this->getDataHelper()->log('error obtaining the max fee ' . $e->getMessage());
            Mage::logException($e);
        }
        return $max;
    }
}
