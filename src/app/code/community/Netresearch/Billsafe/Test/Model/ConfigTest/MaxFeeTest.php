<?php

class Netresearch_Billsafe_Test_Model_ConfigTest_MaxFeeTest
    extends EcomDev_PHPUnit_Test_Case
{
    const MERCHANT_ID = '23244';
    const MERCHANT_LICENSE = '2bef0c94d24cd9d7a6a0073e9926da46';
    const ACCOUT_MAX_FEE = 25;
    
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
     * @expectedExceptionMessage Maximum/Default fee is required entry!
     */
    public function missingMaxFeeTest()
    {        
        $maxFeeMock = $this->getModelMock('billsafe/config_maxfee', 
                array('getTempConfig', 'getValue'));
        $maxFeeMock->expects($this->once())
                ->method('getTempConfig')
                ->will($this->returnValue($this->_configMock));
        $maxFeeMock->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(''));
        $maxFeeMock->_beforeSave();
    }
    
    /**
     * @test
     * @expectedException Netresearch_Billsafe_Model_Config_Exception
     * @expectedExceptionMessage Maximum/Default fee 1000 exceeded the allowed maximum by BillSAFE of 25.
     */
    public function exceedingMaxFeeTest()
    {   
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('init', 'save'));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);
        $customerSessionMock = $this->getModelMock('customer/session', array('init', 'save'));
        $this->replaceByMock('model', 'customer/session', $customerSessionMock);
        
        $maxFeeMock = $this->getModelMock('billsafe/config_maxfee', 
                array('getTempConfig', 'getValue', 'getMaxFee'));
        $maxFeeMock->expects($this->once())
                ->method('getTempConfig')
                ->will($this->returnValue($this->_configMock));
        $maxFeeMock->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(1000));
        $maxFeeMock->expects($this->any())
                ->method('getMaxFee')
                ->will($this->returnValue(self::ACCOUT_MAX_FEE));
        $maxFeeMock->_beforeSave();
    }
    
    /**
     * @test
     * successfull test
     */
    public function beforeSaveTest()
    {
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('init', 'save'));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);
        $customerSessionMock = $this->getModelMock('customer/session', array('init', 'save'));
        $this->replaceByMock('model', 'customer/session', $customerSessionMock);
        
        $maxFeeMock = $this->getModelMock('billsafe/config_maxfee', 
                array('getTempConfig', 'getValue', 'getMaxFee', 'restoreConfig'));
        $maxFeeMock->expects($this->once())
                ->method('getTempConfig')
                ->will($this->returnValue($this->_configMock));
        $maxFeeMock->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(25));
        $maxFeeMock->expects($this->any())
                ->method('getMaxFee')
                ->will($this->returnValue(self::ACCOUT_MAX_FEE));
        $maxFeeMock->_beforeSave();
        
    }
    
    /*
     * @test
     */
    public function afterLoadTest()
    {
        $checkoutSessionMock = $this->getModelMock('checkout/session', array('init', 'save'));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);
        $customerSessionMock = $this->getModelMock('customer/session', array('init', 'save'));
        $this->replaceByMock('model', 'customer/session', $customerSessionMock);
        
        $fakeTempConfig = new Varien_Object();
        $fakeTempConfig->setMaxAmount(self::ACCOUT_MAX_FEE);
        $fakeTempConfig->setPaymentFeeEnabled(true);
        
        $maxFeeMock = $this->getModelMock('billsafe/config_maxfee', 
                array('getTempConfig', 'getValue', 'getMaxFee', 'restoreConfig'));
        $maxFeeMock->expects($this->any())
                ->method('getTempConfig')
                ->will($this->returnValue($fakeTempConfig));
        $maxFeeMock->setValue(25);
        $maxFeeMock->expects($this->any())
                ->method('getMaxFee')
                ->will($this->returnValue(self::ACCOUT_MAX_FEE));
        
        $maxFeeMock->_afterLoad();
        $this->assertEquals(self::ACCOUT_MAX_FEE, $maxFeeMock->getValue());
        
        $maxFeeMock->setValue(399);
        $maxFeeMock->_afterLoad();
        $this->assertEquals(self::ACCOUT_MAX_FEE, $maxFeeMock->getValue());
    }
    
}