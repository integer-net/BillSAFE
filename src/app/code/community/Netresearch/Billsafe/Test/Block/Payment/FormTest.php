<?php
class Netresearch_Billsafe_Test_Block_Payment_FormTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{

    protected function getBlock()
    {
        return Mage::app()->getLayout()
            ->getBlockSingleton('billsafe/payment_form');
    }

    public function testGetMerchantPublicKey()
    {
        $configMock = $this->getModelMock(
            'billsafe/config', array(
                                    'getPublicKey',
                               )
        );
        $configMock->expects($this->any())
            ->method('getPublicKey')
            ->will($this->returnValue('1234'));

        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $block = $this->getBlock();

        $this->assertEquals('1234', $block->getMerchantPublicKey());
    }

    public function testGetFeeTextIsEmptyIfFeeIsNotEnabled()
    {
        $configMock = $this->getModelMock(
            'billsafe/config', array('isPaymentFeeEnabled')
        );
        $configMock->expects($this->any())
            ->method('isPaymentFeeEnabled')
            ->will($this->returnValue(false));
        $this->replaceByMock('model', 'billsafe/config', $configMock);
        $block = $this->getBlock();
        $this->assertEquals('', $block->getFeeText());

        $fakeFeeProduct = Mage::getModel('catalog/product');
        $fakeFeeProduct->setPrice(0);

        $configMock->expects($this->any())
            ->method('isPaymentFeeEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $paymentFeeHelperMock = $this->getHelperMock(
            'paymentfee/data', array('getUpdatedFeeProduct')
        );
        $paymentFeeHelperMock->expects($this->any())
            ->method('getUpdatedFeeProduct')
            ->will($this->returnValue($fakeFeeProduct));
        $this->replaceByMock('helper', 'paymentfee', $paymentFeeHelperMock);

        $block = $this->getBlock();
        $this->assertEquals('', $block->getFeeText());

    }

    public function testGetFeeTextIsEmptyIfFeeProductsPriceIsZero()
    {
        $configMock = $this->getModelMock(
            'billsafe/config', array('isPaymentFeeEnabled')
        );
        $fakeFeeProduct = Mage::getModel('catalog/product');
        $fakeFeeProduct->setPrice(0);

        $configMock->expects($this->any())
            ->method('isPaymentFeeEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $paymentFeeHelperMock = $this->getHelperMock(
            'paymentfee/data', array('getUpdatedFeeProduct')
        );
        $paymentFeeHelperMock->expects($this->any())
            ->method('getUpdatedFeeProduct')
            ->will($this->returnValue($fakeFeeProduct));
        $this->replaceByMock('helper', 'paymentfee', $paymentFeeHelperMock);

        $block = $this->getBlock();
        $this->assertEquals('', $block->getFeeText());
    }

    public function testGetFeeTextIsSetIfFeeProductsPriceIsGreaterThanZero()
    {
        $configMock = $this->getModelMock(
            'billsafe/config', array('isPaymentFeeEnabled')
        );
        $fakeFeeProduct = Mage::getModel('catalog/product');
        $fakeFeeProduct->setPrice(1);
        $fakeFeeProduct->setCheckoutDescription('1');

        $configMock->expects($this->any())
            ->method('isPaymentFeeEnabled')
            ->will($this->returnValue(true));
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $paymentFeeHelperMock = $this->getHelperMock(
            'paymentfee/data', array('getUpdatedFeeProduct')
        );
        $paymentFeeHelperMock->expects($this->any())
            ->method('getUpdatedFeeProduct')
            ->will($this->returnValue($fakeFeeProduct));
        $this->replaceByMock('helper', 'paymentfee', $paymentFeeHelperMock);

        $block = $this->getBlock();
        $expected = Mage::helper('billsafe/data')->__(
            'Using BillSAFE will cause an additional payment Fee of %s.',
            $fakeFeeProduct->getCheckoutDescription()
        );

        $this->assertEquals($expected, $block->getFeeText());
    }


    public function testGetCustomerDob()
    {
        $blockMock = $this->getBlockMock(
            'billsafe/payment_form', array('getInfoData')
        );
        $blockMock->expects($this->any())
            ->method('getInfoData')
            ->will($this->returnValue('2000-01-01'));
        $this->assertEquals('2000-01-01', $blockMock->getCustomerDob());

        $blockMock = $this->getBlockMock(
            'billsafe/payment_form', array('getInfoData')
        );
        $blockMock->expects($this->any())
            ->method('getInfoData')
            ->will($this->returnValue(''));

        $checkoutSessionMock = $this->getModelMock(
            'checkout/session', array('getData')
        );
        $checkoutSessionMock->expects($this->any())
            ->method('getData')
            ->will($this->returnValue('2001-01-01'));
        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);

        $this->assertEquals('2001-01-01', $blockMock->getCustomerDob());

        $checkoutSessionMock = $this->getModelMock(
            'checkout/session', array('getData')
        );
        $checkoutSessionMock->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(null));

        $this->replaceByMock('model', 'checkout/session', $checkoutSessionMock);
    }


}