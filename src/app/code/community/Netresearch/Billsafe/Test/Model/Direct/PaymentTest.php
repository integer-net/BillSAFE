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

        $localeMock = Mage::app()->getLocale();
        $localeMock->setLocale('de_DE');

        $date = new Zend_Date($createdAtDate, 'YYYY-MM-DD');
        $this->assertEquals(
            $date->toString(Zend_Date::DATES, null, $localeMock->getLocale()),
            $modelMock->getCreatedAtFormatted()
        );
    }
}
