<?php

class Netresearch_Billsafe_Model_Config extends Varien_Object
{
    /*
     * Billsafe active config path
     */
    const CONFIG_PATH_ACTIVE = 'payment/billsafe/active';
    /*
     * Billsafe sandbox mode config path
     */
    const CONFIG_PATH_SANDBOX_MODE = 'payment/billsafe/sandbox';

    /*
     * Billsafe Live API URL
     */
    const CONFIG_PATH_LIVE_API_URL = 'payment/billsafe/live_api_url';

    /*
     * Billsafe Live Gateway URL
     */
    const CONFIG_PATH_LIVE_GATEWAY_URL = 'payment/billsafe/live_gateway_url';

    /*
     * Billsafe Sandbox API URL
     */
    const CONFIG_PATH_SANDBOX_API_URL = 'payment/billsafe/sandbox_api_url';

    /*
     * Billsafe Sandbox Gatway URL
     */
    const CONFIG_PATH_SANDBOX_GATEWAY_URL = 'payment/billsafe/sandbox_gateway_url';

    /*
     * Billsafe merchant id config path
     */
    const CONFIG_PATH_MERCHAND_ID = 'payment/billsafe/merchant_id';

    /*
     * Billsafe merchant license config path
     */
    const CONFIG_PATH_MERCHAND_LICENSE = 'payment/billsafe/merchant_license';

    /*
     * Billsafe logo config path
     */
    const CONFIG_PATH_BILLSAFE_LOGO = 'payment/billsafe/billsafe_logo';

    /*
     * Billsafe shop logo config path
     */
    const CONFIG_PATH_SHOP_LOGO = 'payment/billsafe/shop_logo';

    /*
     * Billsafe application signature config path
     */
    const CONFIG_PATH_APPLICATION_SIGNATURE = 'payment/billsafe/application_signature';

    /*
     * Billsafe logging config path
     */
    const CONFIG_PATH_LOGGING = 'payment/billsafe/logging';

    /*
     * Billsafe paymentfee sku config path
     */
    const CONFIG_PATH_PAYMENT_SKU = 'payment_services/paymentfee/sku';

    /*
     * Billsafe paymentfee activated config path
     */
    const CONFIG_PATH_PAYMENT_FEE_ACTIVE = 'payment_services/paymentfee/active';

    /*
     * Billsafe token config path
     */
    const TOKEN_REGISTRY_KEY = 'billsafe_token';

    /*
     * Billsafe direct config path
     */
    const CONFIG_PATH_BILLSAFE_DIRECT = 'payment/billsafe/enable_billsafe_direct';

    /*
     * Billsafe min amount config path
     */
    const CONFIG_PATH_BILLSAFE_MIN_AMOUNT = 'payment/billsafe/min_amount';

    /*
     * Billsafe max amount config path
     */
    const CONFIG_PATH_BILLSAFE_MAX_AMOUNT = 'payment/billsafe/max_amount';

    /*
     * Billsafe title config path
     */
    const CONFIG_PATH_BILLSAFE_TITLE = 'payment/billsafe/title';

    /*
     * Billsafe timeout config path
     */
    const CONFIG_PATH_BILLSAFE_TIMEOUT = 'payment/billsafe/timeout';

    /*
     * Billsafe order status config path
     */
    const CONFIG_PATH_BILLSAFE_ORDER_STATUS = 'payment/billsafe/order_status';

    /*
     * Billsafe exceeding max fee amount config path
     */
    const CONFIG_PATH_BILLSAFE_EXCEEDING_MAX_FEE = 'payment/billsafe/disable_after_exceeding_max_fee_amount';

    /*
     * Billsafe exceeding min fee amount config path
     */
    const CONFIG_PATH_BILLSAFE_EXCEEDING_MIN_FEE = 'payment/billsafe/disable_after_exceeding_min_fee_amount';

    /**
     * Billsafe public key config path
     */
    const CONFIG_PATH_BILLSAFE_PUBLIC_KEY = 'payment/billsafe/merchant_public_key';

    /**
     * BillSAFE default customer gender config path
     * @var string
     */
    const CONFIG_PATH_BILLSAFE_CUSTOMER_GENDER = 'payment/billsafe/default_gender';


    /**
     * Returns current scope id if it is set
     * else returns 0
     *
     * @return int
     */
    public function getCurrentScopeId(){
        $scopeId = $this->getData('scope_id');
        if(is_null($scopeId)){
            return 0;
        }
        return $scopeId;
    }

    /**
     * BillSAFE settlement download config path
     * @var string
     */
    const CONFIG_PATH_BILLSAFE_DOWNLOAD_SETTLEMENT = 'payment/billsafe/download_settlement';

    /**
     * BillSAFE settlement cron expression
     * @var string
     */
    const CONFIG_PATH_BILLSAFE_SETTLEMENT_CRON_EXPR = 'crontab/jobs/billsafe_settlement/schedule/cron_expr';

    /**
     * getter for billsafe exeeding min fee amount
     *
     * @param int $storeId
     *
     * @return boolean
     */
    public function isBillsafeExeedingMinFeeAmount($storeId = null)
    {
        return (bool)Mage::getStoreConfig(
            self::CONFIG_PATH_BILLSAFE_EXCEEDING_MIN_FEE, $storeId
        );
    }

    /**
     * getter for billsafe exeeding max fee amount
     *
     * @param int $storeId
     *
     * @return boolean
     */
    public function isBillsafeExeedingMaxFeeAmount($storeId = null)
    {
        return (bool)Mage::getStoreConfig(
            self::CONFIG_PATH_BILLSAFE_EXCEEDING_MAX_FEE, $storeId
        );
    }

    /**
     * getter for billsafe min amount config value
     *
     * @param int $storeId
     *
     * @return float min_amount
     */
    public function getBillSafeMinAmount($storeId = null)
    {
        return (float)Mage::getStoreConfig(
            self::CONFIG_PATH_BILLSAFE_MIN_AMOUNT, $storeId
        );
    }

    /**
     * getter for billsafe max amount config value
     *
     * @param int $storeId
     *
     * @return float min_amount
     */
    public function getBillSafeMaxAmount($storeId = null)
    {
        return (float)Mage::getStoreConfig(
            self::CONFIG_PATH_BILLSAFE_MAX_AMOUNT, $storeId
        );
    }

    /**
     * getter for billsafe order status config value
     *
     * @param type $store_id
     *
     * @return string billsafe order status
     */
    public function getBillSafeOrderStatus($storeId = null)
    {
        return Mage::getStoreConfig(
            self::CONFIG_PATH_BILLSAFE_ORDER_STATUS, $storeId
        );
    }

    /**
     * getter for billsafe title config value
     *
     * @param type $store_id
     *
     * @return string billsafe title
     */
    public function getBillsafeTitle($storeId = null)
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_BILLSAFE_TITLE, $storeId);
    }

    /**
     * Returns Url of Soap API regarding sandboxmode setting
     *
     * @param type $store_id
     *
     * @return string
     */
    public function getApiUrl($storeId = null)
    {
        return $this->isSandboxMode() ? Mage::getStoreConfig(
            self::CONFIG_PATH_SANDBOX_API_URL, $storeId
        ) : Mage::getStoreConfig(self::CONFIG_PATH_LIVE_API_URL, $storeId);
    }

    /**
     * Returns Url of gateway regarding sandboxmode setting
     *
     * @param type $store_id
     *
     * @return string
     */
    public function getGatewayUrl($storeId = null)
    {
        return $this->isSandboxMode() ? Mage::getStoreConfig(
            self::CONFIG_PATH_SANDBOX_GATEWAY_URL, $storeId
        ) : Mage::getStoreConfig(self::CONFIG_PATH_LIVE_GATEWAY_URL, $storeId);
    }

    /**
     * Returns configured merchant id
     *
     * @param type $store_id
     *
     * @return string
     */
    public function getMerchantId($storeId = null)
    {
        $merchantId = $this->getData('merchant_id');
        if (0 < strlen($merchantId)) {
            return $merchantId;
        }

        return Mage::getStoreConfig(self::CONFIG_PATH_MERCHAND_ID, $storeId);
    }

    /**
     * Returns configured merchant license
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getMerchantLicense($storeId = null)
    {
        $merchantLicence = $this->getData('merchant_license');
        if (0 < strlen($merchantLicence)) {
            return $merchantLicence;
        }

        return Mage::getStoreConfig(
            self::CONFIG_PATH_MERCHAND_LICENSE, $storeId
        );
    }

    /**
     * Returns configured application signature
     *
     * @param type $storeId
     *
     * @return string
     */
    public function getApplicationSignature($storeId = null)
    {
        return Mage::getStoreConfig(
            self::CONFIG_PATH_APPLICATION_SIGNATURE, $storeId
        );
    }

    /**
     * Returns if sandbox mode ist enabled
     *
     * @param int $storeId
     *
     * @return boolean
     */
    public function isSandboxMode($storeId = null)
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_SANDBOX_MODE, $storeId);
    }

    /**
     * Returns true if module is enabled in backend
     *
     * @param int $storeId
     *
     * @return boolean
     */
    public function isActive($storeId = null)
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_ACTIVE, $storeId);
    }

    /**
     * Returns trimmed URL of billsafe logo
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getBillsafeLogoUrl($storeId = null)
    {
        return trim(Mage::getStoreConfig(self::CONFIG_PATH_BILLSAFE_LOGO, $storeId));
    }

    /**
     * Returns URL of shop logo
     *
     * @param type $store_id
     *
     * @return string
     */
    public function getShopLogoUrl($storeId = null)
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_SHOP_LOGO, $storeId);
    }

    /**
     * Checks if requests should be logged or not regardiing configuration
     *
     * @param type $store_id
     *
     * @return boolean
     */
    public function shouldLogRequests($storeId = null)
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_LOGGING, $storeId);
    }

    /**
     * Get payment fee SKU
     *
     * @param type $store_id
     *
     * @return string
     */
    public function getPaymentFeeSku($storeId = null)
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_PAYMENT_SKU, $storeId);
    }

    /**
     * if payment fee is enabled
     *
     * @param type $store_id
     *
     * @return boolean
     */
    public function isPaymentFeeEnabled($storeId = null)
    {
        $fee = $this->getData('fee_active');
        if (isset($fee)) {
            return $fee;
        }
        return Mage::getStoreConfig(
            self::CONFIG_PATH_PAYMENT_FEE_ACTIVE, $storeId
        );
    }

    /**
     *
     * if the usage of billsafe direct is enabled
     *
     * @param $storeId - the store which confing should be used
     *
     * @return bool - true if billsafe direct is enabled, false otherwise
     */
    public function isBillSafeDirectEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_BILLSAFE_DIRECT, $storeId);
    }

    /**
     * config getter for the billsafe timeout
     *
     * @param int $storeId
     *
     * @return string timeout
     */
    public function getBillsafeTimeout($storeId = null)
    {
        return Mage::getStoreConfig(
            self::CONFIG_PATH_BILLSAFE_TIMEOUT, $storeId
        );
    }


    /**
     * config getter for merchants public key
     *
     * @param int $storeId  - the store which confing should be used
     *
     * @return string - the merchants public key
     */
    public function getPublicKey($storeId = null)
    {
        return Mage::getStoreConfig(
            self::CONFIG_PATH_BILLSAFE_PUBLIC_KEY, $storeId
        );
    }

    /**
     * Return default gender as set in module configuration.
     *
     * @param mixed $storeId
     * @return string Gender label
     */
    public function getDefaultCustomerGender($storeId = null)
    {
        return Mage::getStoreConfig(
            self::CONFIG_PATH_BILLSAFE_CUSTOMER_GENDER, $storeId
        );
    }

    /**
     * Check if settlement files should get downloaded to disk.
     *
     * @param mixed $storeId
     * @return boolean
     */
    public function isSettlementDownloadEnabled($storeId = null)
    {
        return Mage::getStoreConfigFlag(
            self::CONFIG_PATH_BILLSAFE_DOWNLOAD_SETTLEMENT, $storeId
        );
    }

    /**
     * Fetches and returns handling maximum charges (payment fee)
     *
     * @return double
     */
    public function getMaxFee()
    {
        $client = $this->getClient()->setConfig($this);
        return $client->getMaxFee();
    }

    /**
     * Fetches and returns maximum billing amount
     *
     * @return double
     */

    public function getMaxAmount()
    {
        $client = $this->getClient()->setConfig($this);
        return $client->getMaxAmount();
    }

    public function getClient()
    {
        $client = $this->getData('client');
        if(!is_null($client)){
            return $client;
        }
        return Mage::getModel('billsafe/client');
    }
}
