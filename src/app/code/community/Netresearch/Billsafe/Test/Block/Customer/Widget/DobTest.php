<?php
class Netresearch_Billsafe_Test_Block_Customer_Widget_DobTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function isRequired()
    {
        $storeId = Mage_Core_Model_App::DISTRO_STORE_ID;
        $isDirectEnabled = true;

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue($storeId));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $configMock = $this->getModelMock('billsafe/config', array('isBillSafeDirectEnabled'));
        $configMock->expects($this->any())
            ->method('isBillSafeDirectEnabled')
            ->will($this->returnValue($isDirectEnabled));
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        /* @var $dobBlock Netresearch_Billsafe_Block_Customer_Widget_Dob */
        $dobBlock = Mage::app()->getLayout()->createBlock('billsafe/customer_widget_dob');
        $this->assertEquals($isDirectEnabled, $dobBlock->isRequired());
        $this->assertStringStartsWith('billsafe', $dobBlock->getTemplate());
    }
}
