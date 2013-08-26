<?php

/**
 * AwHh_Billsafe_Model_Config_Abstract
 *
 * @author Christian Schrut <cschrut@agenturwerft.de>
 */
class AwHh_Billsafe_Model_Config_Abstract extends Mage_Core_Model_Config_Data
{
    protected $realConfigData = array();

    /**
     * apply new settings temporarily
     *
     * @return void
     */
    protected function getTempConfig()
    {
        $config = Mage::getSingleton('billsafe/config');
        $newConfigData = $this->getFieldsetData();
        if (is_array($newConfigData)
            && 1 == $newConfigData['active']
        ) {
            $this->realConfigData['active']           = $config->isActive();
            $this->realConfigData['fee_active']       = $config->getPaymentFeeEnabled();
            $this->realConfigData['merchant_id']      = $config->getMerchantId();
            $this->realConfigData['merchant_license'] = $config->getMerchantLicense();

            if(array_key_exists("fee_active", $newConfigData)) {
                // Invoice
                $config->setActive($newConfigData['active'])
                ->setPaymentFeeEnabled($newConfigData['fee_active'])
                ->setMerchantId($newConfigData['merchant_id'])
                ->setMerchantLicense($newConfigData['merchant_license']);
            } else {
                // Installment
                $config->setActive($newConfigData['active']);
            }
        }
        return $config;
    }

    protected function restoreConfig()
    {
        $config = Mage::getSingleton('billsafe/config');
        foreach (array('merchant_id', 'merchant_license', 'fee_active', 'active') as $key) {
            if (array_key_exists($key, $this->realConfigData)) {
                $config->setData($key, $this->realConfigData['merchant_id']);
            }
        }
    }
}
