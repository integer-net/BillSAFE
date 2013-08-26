<?php
class AwHh_PaymentFee_Test_Config_ConfigTest extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testObserverExists()
    {
        $this->assertModelAlias('paymentfee/observer','AwHh_PaymentFee_Model_Observer');
        $observer = Mage::getModel('paymentfee/observer');
    }
    
    public function testQuoteCollectTotalsObserver()
    {
        $this->assertEventObserverDefined(
            'frontend', 'sales_quote_collect_totals_after', 'paymentfee/observer', 'addFeeToQuoteTotals'
        );
    }
}
