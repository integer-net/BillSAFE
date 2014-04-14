<?php
class Netresearch_Billsafe_Test_Model_Adminhtml_System_Config_Source_GenderTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function toOptionArray()
    {
        /* @var $source Netresearch_Billsafe_Model_Adminhtml_System_Config_Source_Gender */
        $source = Mage::getModel('billsafe/adminhtml_system_config_source_gender');
        $optionArray = $source->toOptionArray();

        $this->assertInternalType('array', $optionArray);
        $this->assertArrayHasKey('Male', $optionArray);
        $this->assertArrayHasKey('Female', $optionArray);
        $this->assertContains('Male', $optionArray);
        $this->assertContains('Female', $optionArray);
    }
}
