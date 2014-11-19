<?php

class Netresearch_Billsafe_Test_Model_ConfigTest_MaxAmountTest
    extends EcomDev_PHPUnit_Test_Case
{
    const MERCHANT_ID = '23244';
    const MERCHANT_LICENSE = '2bef0c94d24cd9d7a6a0073e9926da46';
    const ACCOUT_MAX_AMOUNT = 1025;


    protected $_configMock;

    public function setUp()
    {
        $this->_configMock = $this->getModelMock('billsafe/config',
                array('isActive', 'getMerchantId', 'getMerchantLicense', 'getPaymentFeeEnabled'));
        $this->_configMock->expects($this->any())
                ->method('isActive')
                ->will($this->returnValue(true));
        $this->_configMock->expects($this->any())
                ->method('getMerchantId')
                ->will($this->returnValue(self::MERCHANT_ID));
        $this->_configMock->expects($this->any())
                ->method('getMerchantLicense')
                ->will($this->returnValue(self::MERCHANT_LICENSE));
        $this->_configMock->expects($this->any())
                ->method('getPaymentFeeEnabled')
                ->will($this->returnValue(true));
        parent::setUp();
    }

    /**
     * @test
     * @expectedException Netresearch_Billsafe_Model_Config_Exception
     * @expectedExceptionMessage Please enter your BillSAFE credentials.
     */
    public function missingCredentialsTest()
    {
        $configMock = $this->getModelMock('billsafe/config',
                array('isActive', 'getMerchantId', 'getMerchantLicense')
                );
        $configMock->expects($this->any())
                ->method('isActive')
                ->will($this->returnValue(true));
        $configMock->expects($this->any())
                ->method('getMerchantId')
                ->will($this->returnValue(''));
        $configMock->expects($this->any())
                ->method('getMerchantLicense')
                ->will($this->returnValue(''));
        $maxAmountMock = $this->getModelMock('billsafe/config_maxamount',
                array('getTempConfig')
                );
        $maxAmountMock->expects($this->once())
                ->method('getTempConfig')
                ->will($this->returnValue($configMock));
        $maxAmountMock->_beforeSave();
    }
    /**
     * @test
     * @expectedException Netresearch_Billsafe_Model_Config_Exception
     * @expectedExceptionMessage Maximum order amount is a required entry!
     */
    public function missingMaxAmountTest()
    {
        $maxAmountMock = $this->getModelMock('billsafe/config_maxamount',
                array('getTempConfig', 'getValue')
                );
        $maxAmountMock->expects($this->once())
                ->method('getTempConfig')
                ->will($this->returnValue($this->_configMock));
        $maxAmountMock->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(''));
        $maxAmountMock->_beforeSave();
    }
    /**
     * @test
     * @expectedException Netresearch_Billsafe_Model_Config_Exception
     * @expectedExceptionMessage Maximum order amount exceeds the allowed maximum by BillSAFE of 1025
     */
    public function exeedingMaxAmountTest()
    {
        $maxAmountMock = $this->getModelMock('billsafe/config_maxamount',
                array('getTempConfig', 'getValue', 'getMax')
                );
        $maxAmountMock->expects($this->once())
                ->method('getTempConfig')
                ->will($this->returnValue($this->_configMock));
        $maxAmountMock->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(90000));
        $maxAmountMock->expects($this->any())
                ->method('getMax')
                ->will($this->returnValue(self::ACCOUT_MAX_AMOUNT));
        $maxAmountMock->_beforeSave();
    }
    /**
     * @test
     */
    public function beforeSaveTest()
    {
        $maxAmountMock = $this->getModelMock('billsafe/config_maxamount',
                array('getTempConfig', 'getValue', 'getMax', 'restoreConfig')
                );
        $maxAmountMock->expects($this->once())
                ->method('getTempConfig')
                ->will($this->returnValue($this->_configMock));
        $maxAmountMock->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(1000));
        $maxAmountMock->expects($this->any())
                ->method('getMax')
                ->will($this->returnValue(1025));
        $maxAmountMock->_beforeSave();
    }

    /**
     * @test
     */
    public function afterLoadTest()
    {
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('init', 'save'));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);
        $customerSessionMock = $this->getModelMock('customer/session', array('init', 'save'));
        $this->replaceByMock('model', 'customer/session', $customerSessionMock);

        $fieldSetData = array(
          'active'  => 1,
            'merchant_id' => self::MERCHANT_ID,
            'merchant_license' => self::MERCHANT_LICENSE,
            'fee_active' => 0
        );
        $maxAmountMock = $this->getModelMock('billsafe/config_maxamount',
                array('getFieldSetData', 'getValue', 'getMax')
                );
        $maxAmountMock->expects($this->any())
                ->method('getFieldSetData')
                ->will($this->returnValue($fieldSetData));
        $maxAmountMock->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(1000));
        $maxAmountMock->expects($this->any())
                ->method('getMax')
                ->will($this->returnValue(self::ACCOUT_MAX_AMOUNT));
        $maxAmountMock->_afterLoad();
        $this->assertEquals(1000, $maxAmountMock->getValue());

        $maxAmountMock = $this->getModelMock('billsafe/config_maxamount',
                array('getFieldSetData', 'getTempConfig', 'getMax', 'restoreConfig')
                );
        $maxAmountMock->expects($this->any())
                ->method('getFieldSetData')
                ->will($this->returnValue($fieldSetData));

        $fakeTempConfig = new Varien_Object();
        $fakeTempConfig->setMaxAmount(1025);
        $maxAmountMock->expects($this->any())
                ->method('getTempConfig')
                ->will($this->returnValue($fakeTempConfig));
        $maxAmountMock->setValue(2000);
        $maxAmountMock->expects($this->any())
                ->method('getMax')
                ->will($this->returnValue(self::ACCOUT_MAX_AMOUNT));
        $maxAmountMock->_afterLoad();
        $this->assertEquals(self::ACCOUT_MAX_AMOUNT, $maxAmountMock->getValue());
    }
}
