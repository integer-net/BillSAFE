<?php
class AwHh_Billsafe_Model_Config extends Varien_Object
{
    const CONFIG_PATH_ACTIVE = 'payment/billsafe/active';
    const CONFIG_PATH_SANDBOX_MODE = 'payment/billsafe/sandbox';
    const CONFIG_PATH_LIVE_API_URL = 'payment/billsafe/live_api_url';
    const CONFIG_PATH_LIVE_GATEWAY_URL = 'payment/billsafe/live_gateway_url';
    const CONFIG_PATH_SANDBOX_API_URL = 'payment/billsafe/sandbox_api_url';
    const CONFIG_PATH_SANDBOX_GATEWAY_URL = 'payment/billsafe/sandbox_gateway_url';
    const CONFIG_PATH_MERCHAND_ID = 'payment/billsafe/merchant_id';
    const CONFIG_PATH_MERCHAND_LICENSE = 'payment/billsafe/merchant_license';
    const CONFIG_PATH_BILLSAFE_LOGO = 'payment/billsafe/billsafe_logo';
    const CONFIG_PATH_SHOP_LOGO = 'payment/billsafe/shop_logo';
    const CONFIG_PATH_APPLICATION_SIGNATURE = 'payment/billsafe/application_signature';
    const CONFIG_PATH_LOGGING = 'payment/billsafe/logging';
    const CONFIG_PATH_PAYMENT_SKU = 'payment_services/paymentfee/sku';
    const CONFIG_PATH_PAYMENT_FEE_ACTIVE = 'payment_services/paymentfee/active';
    const TOKEN_REGISTRY_KEY = 'billsafe_token';
    
    /**
     * Returns Url of Soap API regarding sandboxmode setting
     * 
     * @return string
     */
    public function getApiUrl()
    {
        return $this->isSandboxMode()
            ? Mage::getStoreConfig(self::CONFIG_PATH_SANDBOX_API_URL)
            : Mage::getStoreConfig(self::CONFIG_PATH_LIVE_API_URL);
    }
    
    /**
     * Returns Url of gateway regarding sandboxmode setting
     * 
     * @return string
     */
    public function getGatewayUrl()
    {
        return $this->isSandboxMode()
            ? Mage::getStoreConfig(self::CONFIG_PATH_SANDBOX_GATEWAY_URL)
            : Mage::getStoreConfig(self::CONFIG_PATH_LIVE_GATEWAY_URL);
    }

    /**
     * Returns configured merchant id
     * 
     * @return string
     */
    public function getMerchantId()
    {
        if (is_null(parent::getMerchantId())) {
            $this->setMerchantId(Mage::getStoreConfig(self::CONFIG_PATH_MERCHAND_ID));
        }
        return parent::getMerchantId();
    }
    
    /**
     * Returns configured merchant license
     * 
     * @return string
     */
    public function getMerchantLicense()
    {
        if (is_null(parent::getMerchantLicense())) {
            $this->setMerchantLicense(Mage::getStoreConfig(self::CONFIG_PATH_MERCHAND_LICENSE));
        }
        return parent::getMerchantLicense();
    }

    /**
     * Returns configured application signature
     * 
     * @return string
     */
    public function getApplicationSignature()
    {
        if (is_null(parent::getApplicationSignature())) {
            $this->setApplicationSignature(Mage::getStoreConfig(self::CONFIG_PATH_APPLICATION_SIGNATURE));
        }
        return parent::getApplicationSignature();
    }
    
    /**
     * Returns if sandbox mode ist enabled
     * 
     * @return boolean
     */
    public function isSandboxMode()
    {
        if (is_null(parent::getSandboxMode())) {
            $this->setSandboxMode(Mage::getStoreConfig(self::CONFIG_PATH_SANDBOX_MODE));
        }
        return parent::getSandboxMode();
    }
    
    /**
     * Returns true if module is enabled in backend
     * 
     * @return booelean
     */
    public function isActive()
    {
        if (is_null(parent::getActive())) {
            $this->setActive(Mage::getStoreConfig(self::CONFIG_PATH_ACTIVE));
        }
        return parent::getActive();
    }
    
    /**
     * Returns URL of billsafe logo
     * 
     * @return string
     */
    public function getBillsafeLogoUrl()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_BILLSAFE_LOGO);
    }
    
    /**
     * Returns URL of shop logo
     * 
     * @return string
     */
    public function getShopLogoUrl()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_SHOP_LOGO);
    }
    
    /**
     * Checks if requests should be logged or not regardiing configuration
     * 
     * @return boolean
     */
    public function shouldLogRequests()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_LOGGING);
    }
    
    /**
     * Get payment fee SKU
     * 
     * @return string
     */
    public function getPaymentFeeSku()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_PAYMENT_SKU);
    }

    /**
     * if payment fee is enabled
     * 
     * @return boolean
     */
    public function isPaymentFeeEnabled()
    {
        if (is_null(parent::getPaymentFeeEnabled())) {
            $this->setPaymentFeeEnabled(Mage::getStoreConfig(self::CONFIG_PATH_PAYMENT_FEE_ACTIVE));
        }
        return parent::getPaymentFeeEnabled();
    }
}
