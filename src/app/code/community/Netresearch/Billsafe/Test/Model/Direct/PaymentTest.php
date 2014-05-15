<?php
class Netresearch_Billsafe_Test_Model_Direct_PaymentTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function getCreatedAtFormatted()
    {
        Mage::app()->getStore()->setConfig('general/locale/timezone', 'Europe/Berlin');

        $createdAtDate = '1999-03-14';
        $modelMock = $this->getModelMock('billsafe/direct_payment', array('getCreatedAt'));
        $modelMock
            ->expects($this->any())
            ->method('getCreatedAt')
            ->will($this->returnValue($createdAtDate));
        $this->replaceByMock('model', 'billsafe/direct_payment', $modelMock);

        /* @var $directPayment Netresearch_Billsafe_Model_Direct_Payment */
        $directPayment = Mage::getModel('billsafe/direct_payment');

        $date = new Zend_Date($createdAtDate, 'YYYY-MM-DD');
        $this->assertEquals(
            $date->toString(Zend_Date::DATE_MEDIUM),
            $directPayment->getCreatedAtFormatted()
        );
    }
}
