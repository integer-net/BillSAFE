<?php

class Netresearch_Billsafe_Test_Model_ConfigTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Netresearch_Billsafe_Model_Config
     */
    protected $config;

    public function setUp()
    {
        $this->store = Mage::app()->getStore(0)->load(0);
        $this->config = Mage::getModel('billsafe/config');
    }

    protected function setConfig($storeId = 0)
    {
        $this->store = Mage::app()->getStore($storeId)->load($storeId);
    }

    public function testIsBillsafeDirectEnabled()
    {
        $storeCode = Mage_Core_Model_Store::DEFAULT_CODE;
        $this->setCurrentStore($storeCode);

        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_DIRECT;

        //Check if module is initially disabled
        Mage::app()->getStore($storeCode)->resetConfig();

        $this->assertFalse($this->config->isBillSafeDirectEnabled($storeCode));

        Mage::app()->getStore($storeCode)->setConfig($path, 1);
        $this->assertTrue($this->config->isBillSafeDirectEnabled($storeCode));

        Mage::app()->getStore($storeCode)->setConfig($path, 0);
        $this->assertFalse($this->config->isBillSafeDirectEnabled($storeCode));

        Mage::app()->getStore($storeCode)->resetConfig();
    }

    public function testGetBillsafeLogoUrl()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_LOGO;
        $storeFirst = Mage::app()->getStore(0)->load(0);
        $storeFirst->setConfig($path, "logo_1");

        $this->assertEquals("logo_1", $this->config->getBillsafeLogoUrl(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond = $storeFirst;
        $storeSecond->setConfig($path, "logo_2");
        $this->assertEquals("logo_2", $this->config->getBillsafeLogoUrl(1));
        $storeSecond->resetConfig();
        $this->store->resetConfig();
        $storeFirst = Mage::app()->getStore(0)->load(0);
        */
    }

    public function testGetBillsafeTitle()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_TITLE;
        $storeFirst = Mage::app()->getStore(0)->load(0);
        $storeFirst->setConfig($path, "title_store1");

        $this->assertEquals("title_store1", $this->config->getBillsafeTitle());
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, "title_store2");

        $this->assertEquals("title_store2", $this->config->getBillsafeTitle(1));
        $storeSecond->resetConfig();
        */
    }

    public function testGetBillsafeTimeout()
    {
        $storeCode = Mage_Core_Model_Store::DEFAULT_CODE;
        $this->setCurrentStore($storeCode);

        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_TIMEOUT;

        Mage::app()->getStore($storeCode)->resetConfig();

        Mage::app()->getStore($storeCode)->setConfig($path, 30);
        $this->assertEquals(30, $this->config->getBillsafeTimeout($storeCode));

        Mage::app()->getStore($storeCode)->resetConfig();
    }

    public function testGetBillSafeOrderStatus()
    {
        $path
            = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_ORDER_STATUS;
        $storeFirst = Mage::app()->getStore(0)->load(0);
        $storeFirst->setConfig($path, 'pending');

        $this->assertEquals(
            'pending', $this->config->getBillSafeOrderStatus(0)
        );
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'canceled');
        $this->assertEquals('canceled', $this->config->getBillSafeOrderStatus(1));
        $storeSecond->resetConfig();
        */
    }

    public function testIsBillsafeExceedingMinFeeAmount()
    {
        $path
            = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_EXCEEDING_MIN_FEE;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 500);

        $this->assertEquals(
            500, $this->config->isBillsafeExeedingMinFeeAmount(0)
        );
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 1000);

        $this->assertEquals(1000, $this->config->isBillsafeExeedingMinFeeAmount(1));
        $storeSecond->resetConfig();
        */
    }

    public function testIsBillsafeExeedingMaxFeeAmount()
    {
        $path
            = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_EXCEEDING_MAX_FEE;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 3000);

        $this->assertEquals(
            3000, $this->config->isBillsafeExeedingMaxFeeAmount(0)
        );
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 9999);

        $this->assertEquals(9999, $this->config->isBillsafeExeedingMaxFeeAmount(1));
        $storeSecond->resetConfig();
        */
    }

    public function testIsActive()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_ACTIVE;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 1);

        $this->assertEquals(1, $this->config->isActive(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 0);

        $this->assertEquals(0, $this->config->isActive(1));
        $storeSecond->resetConfig();
        */
    }

    public function testGetShopLogoUrl()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_SHOP_LOGO;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 'shop_logo');

        $this->assertEquals('shop_logo', $this->config->getShopLogoUrl(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'shop_logo_2');

        $this->assertEquals('shop_logo_2', $this->config->getShopLogoUrl(1));
        $storeSecond->resetConfig();
        */
    }

    public function testShouldLogRequests()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_LOGGING;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 1);

        $this->assertEquals(1, $this->config->shouldLogRequests(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 0);

        $this->assertEquals(0, $this->config->shouldLogRequests(1));
        $storeSecond->resetConfig();
        */
    }


    public function testGetPaymentFeeSku()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_PAYMENT_SKU;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 'FeeSKU123');

        $this->assertEquals('FeeSKU123', $this->config->getPaymentFeeSku(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'FeeSKU12356');

        $this->assertEquals('FeeSKU12356', $this->config->getPaymentFeeSku(1));
        $storeSecond->resetConfig();
        */
    }

    public function testIsPaymentFeeEnabled()
    {
        $path
            = Netresearch_Billsafe_Model_Config::CONFIG_PATH_PAYMENT_FEE_ACTIVE;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 1);

        $this->assertEquals(1, $this->config->isPaymentFeeEnabled(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 0);

        $this->assertEquals(0, $this->config->isPaymentFeeEnabled(1));
        $storeSecond->resetConfig();
        */
    }

    public function testgetBillSafeMinAmount()
    {
        $path
            = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_MIN_AMOUNT;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 1000);

        $this->assertEquals(1000, $this->config->getBillSafeMinAmount(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 999);

        $this->assertEquals(999, $this->config->getBillSafeMinAmount(1));
        $storeSecond->resetConfig();
        */
    }

    public function testgetBillSafeMaxAmount()
    {
        $path
            = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_MAX_AMOUNT;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 3500);

        $this->assertEquals(3500, $this->config->getBillSafeMaxAmount(0));
        /*
        $storeFirst->resetConfig();

        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 1111);

        $this->assertEquals(1111, $this->config->getBillSafeMaxAmount(1));
        $storeSecond->resetConfig();
        */
    }

    public function testgetMerchantId()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_MERCHAND_ID;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 1234);

        $this->assertEquals(1234, $this->config->getMerchantId(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 4321);

        $this->assertEquals(4321, $this->config->getMerchantId(1));
        $storeSecond->resetConfig();
        */
    }

    public function testGetMerchantLicense()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_MERCHAND_LICENSE;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 'license 1234');

        $this->assertEquals(
            'license 1234', $this->config->getMerchantLicense(0)
        );
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'license 12');

        $this->assertEquals('license 12', $this->config->getMerchantLicense(1));
        $storeSecond->resetConfig();
        */
    }

    public function testGetApplicationSignature()
    {
        $path
            = Netresearch_Billsafe_Model_Config::CONFIG_PATH_APPLICATION_SIGNATURE;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 'GDHGDH//("§');

        $this->assertEquals(
            'GDHGDH//("§', $this->config->getApplicationSignature(0)
        );
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'ABD&"%§%');

        $this->assertEquals('ABD&"%§%', $this->config->getApplicationSignature(1));
        $storeSecond->resetConfig();
        */
    }

    public function testIsSandboxMode()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_SANDBOX_MODE;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 1);

        $this->assertEquals(1, $this->config->isSandboxMode(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 0);

        $this->assertEquals(0, $this->config->isSandboxMode(1));
        $storeSecond->resetConfig();
        */
    }

    public function testGetApiUrl()
    {
        //Sandbox mode
        $configMock = $this->getModelMock(
            'billsafe/config', array('isSandboxMode')
        );
        $configMock->expects($this->any())
            ->method('isSandboxMode')
            ->will($this->returnValue(true));


        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_SANDBOX_API_URL;
        $storeFirst = Mage::app()->getStore(0)->load(0);
        $storeFirst->setConfig($path, 'www.sandboxapi.dev');

        $this->assertEquals('www.sandboxapi.dev', $configMock->getApiUrl(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'www.sandboxapi2.dev');

        $this->assertEquals('www.sandboxapi2.dev', $configMock->getApiUrl(1));
        $storeSecond->resetConfig();
        */

        //live mode
        $configMock = $this->getModelMock(
            'billsafe/config', array('isSandboxMode')
        );
        $configMock->expects($this->any())
            ->method('isSandboxMode')
            ->will($this->returnValue(false));


        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_LIVE_API_URL;
        $storeFirst = Mage::app()->getStore(0)->load(0);
        $storeFirst->setConfig($path, 'www.liveapi.dev');

        $this->assertEquals('www.liveapi.dev', $configMock->getApiUrl(0));
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'www.liveapi2.dev');

        $this->assertEquals('www.liveapi2.dev', $configMock->getApiUrl(1));
        $storeSecond->resetConfig();
        */
    }


    public function testGetGatewayUrl()
    {
        //Sandbox mode
        $configMock = $this->getModelMock(
            'billsafe/config', array('isSandboxMode')
        );
        $configMock->expects($this->any())
            ->method('isSandboxMode')
            ->will($this->returnValue(true));


        $path
            = Netresearch_Billsafe_Model_Config::CONFIG_PATH_SANDBOX_GATEWAY_URL;
        $storeFirst = Mage::app()->getStore(0)->load(0);
        $storeFirst->setConfig($path, 'www.sandboxgateway.dev');

        $this->assertEquals(
            'www.sandboxgateway.dev', $configMock->getGatewayUrl(0)
        );
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'www.sandboxgateway2.dev');

        $this->assertEquals('www.sandboxgateway2.dev', $configMock->getGatewayUrl(1));
        $storeSecond->resetConfig();
        */
        //live mode
        $configMock = $this->getModelMock(
            'billsafe/config', array('isSandboxMode')
        );
        $configMock->expects($this->any())
            ->method('isSandboxMode')
            ->will($this->returnValue(false));


        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_LIVE_GATEWAY_URL;
        $storeFirst = Mage::app()->getStore(0)->load(0);
        $storeFirst->setConfig($path, 'www.livegateway.dev');

        $this->assertEquals(
            'www.livegateway.dev', $configMock->getGatewayUrl(0)
        );
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'www.livegateway2.dev');

        $this->assertEquals('www.livegateway2.dev', $configMock->getGatewayUrl(1));
        $storeSecond->resetConfig();
        */
    }

    public function testGetMerchantPublicKey()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_PUBLIC_KEY;
        $storeFirst = Mage::app()->getStore(0)->load(0);

        $storeFirst->setConfig($path, 'default public key');

        $this->assertEquals(
            'default public key', $this->config->getPublicKey(0)
        );
        $storeFirst->resetConfig();

        /*
        $storeSecond = Mage::app()->getStore(1)->load(0);
        $storeSecond->setConfig($path, 'default public key store 1');

        $this->assertEquals('default public key store 1', $this->config->getPublicKey(1));
        $storeSecond->resetConfig();
        */
    }

    public function testGetDefaultCustomerGender()
    {
        $defaultGender = 'Female';
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_CUSTOMER_GENDER;

        $code = Mage_Core_Model_Store::DEFAULT_CODE;
        Mage::app()->getStore($code)->setConfig($path, $defaultGender);
        $this->assertEquals($this->config->getDefaultCustomerGender($code), $defaultGender);
    }

    public function testIsSettlementDownloadEnabled()
    {
        $path = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_DOWNLOAD_SETTLEMENT;
        $code = Mage_Core_Model_Store::DEFAULT_CODE;

        Mage::app()->getStore($code)->setConfig($path, true);
        $this->assertTrue($this->config->isSettlementDownloadEnabled($code));

        Mage::app()->getStore($code)->setConfig($path, false);
        $this->assertFalse($this->config->isSettlementDownloadEnabled($code));
    }

    public function testGetMaxFee()
    {
        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $fakeQuote = new Varien_Object();
        $fakeQuote->setStoreId(1);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data',
            array('getStoreIdfromQuote', 'getQuotefromSession', 'log')
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(1));
        $dataHelperMock->expects($this->any())
            ->method('getQuotefromSession')
            ->will($this->returnValue($fakeQuote));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $clientMock = $this->getModelMock(
            'billsafe/client', array('getAgreedHandlingCharges')
        );
        $clientMock->expects($this->any())
            ->method('getAgreedHandlingCharges')
            ->will($this->returnValue(11));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);
        $config = Mage::getModel('billsafe/config');
        $this->assertEquals(11, $config->getMaxFee());

    }

    public function testGetMaxAmount()
    {

        $sessionMock = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor(
            ) // This one removes session_start and other methods usage
            ->getMock();
        $this->replaceByMock('singleton', 'checkout/session', $sessionMock);

        $fakeQuote = new Varien_Object();
        $fakeQuote->setStoreId(1);

        $dataHelperMock = $this->getHelperMock(
            'billsafe/data',
            array('getStoreIdfromQuote', 'getQuotefromSession', 'log')
        );
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue(1));
        $dataHelperMock->expects($this->any())
            ->method('getQuotefromSession')
            ->will($this->returnValue($fakeQuote));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);
        $this->replaceByMock('helper', 'billsafe', $dataHelperMock);

        $clientMock = $this->getModelMock(
            'billsafe/client', array('getAgreedHandlingCharges')
        );
        $clientMock->expects($this->any())
            ->method('getAgreedHandlingCharges')
            ->will($this->returnValue(99));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);
        $config = Mage::getModel('billsafe/config');
        $this->assertEquals(99, $config->getMaxAmount());
    }
}
