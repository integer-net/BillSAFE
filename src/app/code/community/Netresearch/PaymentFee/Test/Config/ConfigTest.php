<?php
class Netresearch_PaymentFee_Test_Config_ConfigTest extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testObserverExists()
    {
        $this->assertModelAlias('paymentfee/observer','Netresearch_PaymentFee_Model_Observer');
        $observer = Mage::getModel('paymentfee/observer');
    }

}
