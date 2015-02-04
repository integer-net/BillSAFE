<?php

/**
 * Netresearch_Billsafe_Model_Config_Abstract
 *
 * @author Thomas Birke <thomas.birke@netresearch.de>
 */
class Netresearch_Billsafe_Model_Config_Abstract extends Mage_Core_Model_Config_Data
{

    protected $_config;
    protected $_dataHelper;
    protected $_customerHelper;
    protected $_realConfigData = null;


    /**
     * @param mixed $config
     */
    public function setConfig(Netresearch_Billsafe_Model_Config $config)
    {
        $this->_config = $config;
    }

    /**
     * @return Netresearch_Billsafe_Model_Config
     */
    public function getConfig()
    {
        if(is_null($this->_config)){
            $this->_config = Mage::getSingleton('billsafe/config');
        }
        return $this->_config;
    }

    /**
     * @param Netresearch_Billsafe_Helper_Data $dataHelper
     */
    public function setDataHelper(Netresearch_Billsafe_Helper_Data $dataHelper)
    {
        $this->_dataHelper = $dataHelper;
    }

    /**
     * @return Netresearch_Billsafe_Helper_Data
     */
    public function getDataHelper()
    {
        if(is_null($this->_dataHelper)){
            $this->_dataHelper = Mage::helper('billsafe/data');
        }
        return $this->_dataHelper;
    }

    /**
     * @param Netresearch_Billsafe_Helper_Customer $customerHelper
     */
    public function setCustomerHelper(Netresearch_Billsafe_Helper_Customer $customerHelper)
    {
        $this->_customerHelper = $customerHelper;
    }

    /**
     * @return Netresearch_Billsafe_Helper_Customer
     */
    public function getCustomerHelper()
    {
        if(is_null($this->_customerHelper)){
            $this->_customerHelper = Mage::helper('billsafe/customer');
        }
        return $this->_customerHelper;
    }


    /**
     * apply new settings temporarily
     *
     * @return Netresearch_Billsafe_Model_Config
     */
    protected function getTempConfig()
    {
        $scopeId = $this->getScopeId();
        $store = Mage::app()->getStore($scopeId);
        $config = Mage::getSingleton('billsafe/config');
        $newConfigData = $this->getFieldsetData();
        if (is_array($newConfigData)
            && 1 == $newConfigData['active']
        ) {
            // save old values
            $this->_realConfigData = new Varien_Object();
            $this->_realConfigData->setActive($store->getConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_ACTIVE));
            $this->_realConfigData->setFeeActive($store->getConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_PAYMENT_FEE_ACTIVE));
            $this->_realConfigData->setMerchantId($store->getConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_MERCHAND_ID));
            $this->_realConfigData->setMerchantLicense($store->getConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_MERCHAND_LICENSE));
            // temporarily set new values
            $store->setConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_MERCHAND_ID, $newConfigData['merchant_id']);
            $store->setConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_MERCHAND_LICENSE, $newConfigData['merchant_license']);
            $store->setConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_ACTIVE, $newConfigData['active']);
            $store->setConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_PAYMENT_FEE_ACTIVE, $newConfigData['fee_active']);
        }
        // set current scope at the config for deeper models to access
        $config->setData('scope_id', $scopeId);
        return $config;
    }

    protected function restoreConfig()
    {
        // restore the data just in case we have config data to set
        if ($this->_realConfigData instanceof Varien_Object) {
            $scopeId = $this->getScopeId();
            $store = Mage::app()->getStore($scopeId);
            $store->setConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_MERCHAND_ID, $this->_realConfigData->getMerchantId());
            $store->setConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_MERCHAND_LICENSE, $this->_realConfigData->getMerchantLicense());
            $store->setConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_ACTIVE, $this->_realConfigData->getActive());
            $store->setConfig(Netresearch_Billsafe_Model_Config::CONFIG_PATH_PAYMENT_FEE_ACTIVE, $this->_realConfigData->getFeeActive());
        }
    }
}
