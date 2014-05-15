<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ObserverTest
 *
 * @author sebastian
 */
class Netresearch_Billsafe_Test_Model_ObserverTest
    extends EcomDev_PHPUnit_Test_Case_Controller
{

    private $_model;

    public function setUp()
    {
        parent::setup();
        $this->_model = Mage::getModel('billsafe/observer');
    }

    public function testType()
    {
        $this->assertInstanceOf(
            'Netresearch_Billsafe_Model_Observer', $this->_model
        );
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testUpdatePaymentInstructionsWithData()
    {
        $order = Mage::getModel('sales/order')->load(11);
        $observer = new Varien_Event_Observer();
        $invoice = new Varien_Object();
        $invoice->setOrder($order);
        $event = new Varien_Object();
        $event->setInvoice($invoice);
        $observer->setEvent($event);

        $dataObject = new stdClass();
        $dataObject->recipient = 'Max Muster';
        $dataObject->bankCode = '0123456';
        $dataObject->accountNumber = '11111';
        $dataObject->bankName = 'Test Bank';
        $dataObject->bic = 'ABC12345';
        $dataObject->iban = '000111';
        $dataObject->reference = '123';
        $dataObject->amount = '12';
        $dataObject->currencyCode = 'euro';
        $dataObject->note = 'test note';
        $dataObject->legalNote = 'legal note';


        $clienMock = $this->getModelMock(
            'billsafe/client', array('getPaymentInstruction')
        );
        $clienMock->expects($this->any())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($dataObject));
        $this->replaceByMock('model', 'billsafe/client', $clienMock);
        Mage::getModel('billsafe/observer')->updatePaymentInstructionInvoice(
            $observer
        );

        $payment = $order->getPayment();
        $this->assertEquals(
            Netresearch_Billsafe_Model_Payment::BILLSAFE_STATUS_ACTIVE,
            $payment->getAdditionalInformation('BillsafeStatus')
        );
        $this->assertEquals(
            $dataObject->recipient,
            $payment->getAdditionalInformation('Recipient')
        );
        $this->assertEquals(
            $dataObject->bankCode,
            $payment->getAdditionalInformation('BankCode')
        );
        $this->assertEquals(
            $dataObject->accountNumber,
            $payment->getAdditionalInformation('AccountNumber')
        );
        $this->assertEquals(
            $dataObject->bankName,
            $payment->getAdditionalInformation('BankName')
        );
        $this->assertEquals(
            $dataObject->bic, $payment->getAdditionalInformation('Bic')
        );
        $this->assertEquals(
            $dataObject->iban, $payment->getAdditionalInformation('Iban')
        );
        $this->assertEquals(
            $dataObject->reference,
            $payment->getAdditionalInformation('Reference')
        );
        $this->assertEquals(
            $dataObject->amount, $payment->getAdditionalInformation('Amount')
        );
        $this->assertEquals(
            $dataObject->currencyCode,
            $payment->getAdditionalInformation('CurrencyCode')
        );
        $this->assertEquals(
            $dataObject->note, $payment->getAdditionalInformation('Note')
        );
        $this->assertEquals(
            $dataObject->legalNote,
            $payment->getAdditionalInformation('legalNote')
        );

    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testUpdatePaymentInstructionsNoData()
    {
        $order = Mage::getModel('sales/order')->load(11);
        $observer = new Varien_Event_Observer();
        $observer = new Varien_Event_Observer();
        $invoice = new Varien_Object();
        $invoice->setOrder($order);
        $event = new Varien_Object();
        $event->setInvoice($invoice);
        $observer->setEvent($event);

        $clienMock = $this->getModelMock(
            'billsafe/client', array('getPaymentInstruction')
        );
        $clienMock->expects($this->any())
            ->method('getPaymentInstruction')
            ->will($this->returnValue(null));
        $this->replaceByMock('model', 'billsafe/client', $clienMock);

        Mage::getModel('billsafe/observer')->updatePaymentInstructionInvoice(
            $observer
        );

        $payment = $order->getPayment();
        $this->assertEquals(
            Netresearch_Billsafe_Model_Payment::BILLSAFE_STATUS_CANCELLED,
            $payment->getAdditionalInformation('BillsafeStatus')
        );
    }

    public function testImportPaymentData()
    {
        $observer = Mage::getModel('billsafe/observer');
        $input = new Varien_Object();
        $event = new Varien_Object();
        $event->setInput($input);
        $observedEvent = new Varien_Event_Observer();
        $observedEvent->setEvent($event);
        $observer->importPaymentData($observedEvent);
        $this->assertNull(
            Mage::getSingleton('checkout/session')->getData('customer_dob')
        );

        $input->setDob('2000-01-01');
        $event->setInput($input);
        $observedEvent->setEvent($event);
        $observer->importPaymentData($observedEvent);
        $this->assertEquals(
            '2000-01-01',
            Mage::getSingleton('checkout/session')->getData('customer_dob')
        );
    }

    public function testCleanUpSession()
    {
        Mage::getSingleton('customer/session')->setData('authorize_failed', true);
        Mage::getSingleton('customer/session')->setData('billsafe_billingAddrHash', 'abcdef');
        Mage::getSingleton('checkout/session')->setData('customer_dob', '2000-01-01');
        $observedEvent = new Varien_Event_Observer();
        $observer = Mage::getModel('billsafe/observer');
        $observer->cleanUpSession($observedEvent);
        $this->assertNull(Mage::getSingleton('customer/session')->getData('authorize_failed'));
        $this->assertNull(Mage::getSingleton('customer/session')->getData('billsafe_billingAddrHash'));
        $this->assertNull(Mage::getSingleton('checkout/session')->getData('customer_dob'));
    }

    /**
     * @test
     * @loadFixture ../../../var/fixtures/stores.yaml
     */
    public function getSettlementFiles()
    {
        $this->setCurrentStore('admin');

        $basename = 'foo.csv';

        $configMock = $this->getModelMock('billsafe/config', array('isSettlementDownloadEnabled'));
        $configMock
            ->expects($this->any())
            ->method('isSettlementDownloadEnabled')
            ->will($this->onConsecutiveCalls(
                false, false,
                true, false,
                true, true
            ))
        ;
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $clientMock = $this->getModelMock('billsafe/client', array('getSettlement'));
        $clientMock
            ->expects($this->any())
            ->method('getSettlement')
            ->will($this->returnValue($basename));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        /* @var $schedule Mage_Cron_Model_Schedule */
        $schedule = Mage::getModel('cron/schedule');
        /* @var $observer Netresearch_Billsafe_Model_Observer */
        $observer = Mage::getModel('billsafe/observer');

        $observer->getSettlementFiles($schedule);
        $this->assertNull($schedule->getMessages());

        $observer->getSettlementFiles($schedule);
        $this->assertInternalType("string", $schedule->getMessages());
        $this->assertStringStartsWith("$basename was successfully downloaded", $schedule->getMessages());

        $observer->getSettlementFiles($schedule);
        $this->assertInternalType("string", $schedule->getMessages());
        $messages = explode("\n", $schedule->getMessages());
        $this->assertCount(2, $messages);
    }
}
