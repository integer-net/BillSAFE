<?php

class Netresearch_Billsafe_Test_Block_InfoTest extends EcomDev_PHPUnit_Test_Case
{
    private $_dummyInvoice;

    public function setUp()
    {
        $path = 'dev/template/allow_symlink';
        $store = Mage::app()->getStore(0)->load(0);
        $store->setConfig($path, 0);
        $this->_block = Mage::app()->getLayout()->getBlockSingleton('billsafe/info');

        $info = Mage::getModel('payment/info');
        $info->setMethodInstance(Mage::helper('payment')->getMethodInstance('billsafe'));
        $this->_block->setInfo($info);

        $this->_dummyInvoice = Mage::getModel('sales/order_invoice')
            ->setInstructionBankCode('12345678')
            ->setInstructionAccountNumber('987654321')
            ->setInstructionRecipient('BillSAFE GmbH')
            ->setInstructionBankName('Sparkasse')
            ->setInstructionBic('NOLADE22')
            ->setInstructionIban('DE1234')
            ->setInstructionReference('BTN 1')
            ->setInstructionAmount(29.99)
            ->setInstructionCurrencyCode('EUR')
            ->setInstructionNote('Bitte zahlen - ' . microtime(true));
    }

    public function testGetMethodCode()
    {
        $code = $this->_block->getMethodCode();

        $this->assertInternalType('string', $code);
        $this->assertEquals('billsafe', $code);
    }

    public function testIsOrder()
    {
        Mage::unregister('current_order');
        Mage::unregister('current_invoice');
        Mage::unregister('invoice');
        $this->assertFalse($this->_block->isOrder());

        $order = Mage::getModel('sales/order');

        Mage::register('current_order', $order);
        $this->assertInstanceOf('Mage_Sales_Model_Order', $this->_block->getOrder());
        $this->assertTrue($this->_block->isOrder());

        $order = null;
    }

    public function testIsInvoice()
    {
        Mage::unregister('current_order');
        Mage::unregister('current_invoice');
        Mage::unregister('invoice');
        $this->assertFalse($this->_block->isInvoice());

        Mage::register('current_invoice', $this->_dummyInvoice);
        $this->assertInstanceOf('Mage_Sales_Model_Order_Invoice', $this->_block->getInvoice());
        $this->assertTrue($this->_block->isInvoice());
    }

    public function testgetLegalNote()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('legalNote')
            ->will($this->returnValue('BillsaveData'));

        $this->assertEquals('BillsaveData', $blockMock->getLegalNote());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(false));

        $this->assertEquals(null, $blockMock->getLegalNote());
    }

    public function testGetBankCode()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('BankCode')
            ->will($this->returnValue('BankCode'));

        $this->assertEquals('BankCode', $blockMock->getBankCode());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getBankCode())));
    }

    public function testGetBankName()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('BankName')
            ->will($this->returnValue('BankName'));

        $this->assertEquals('BankName', $blockMock->getBankName());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getBankName())));
    }

    public function testGetIban()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('Iban')
            ->will($this->returnValue('Iban'));

        $this->assertEquals('Iban', $blockMock->getIban());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getIban())));
    }

    public function testGetBic()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('Bic')
            ->will($this->returnValue('Bic'));

        $this->assertEquals('Bic', $blockMock->getBic());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getBic())));
    }

    public function testGetAccountNumber()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('AccountNumber')
            ->will($this->returnValue('AccountNumber'));

        $this->assertEquals('AccountNumber', $blockMock->getAccountNumber());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getAccountNumber())));
    }

    public function testGetRecipient()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('Recipient')
            ->will($this->returnValue('Recipient'));

        $this->assertEquals('Recipient', $blockMock->getRecipient());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getRecipient())));
    }

    public function testGetReference()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('Reference')
            ->will($this->returnValue('Reference'));

        $this->assertEquals('Reference', $blockMock->getReference());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getReference())));
    }

    public function testGetAmount()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('Amount')
            ->will($this->returnValue('Amount'));

        $this->assertEquals('Amount', $blockMock->getAmount());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getAmount())));
    }

    public function testGetCurrencyCode()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('CurrencyCode')
            ->will($this->returnValue('CurrencyCode'));

        $this->assertEquals('CurrencyCode', $blockMock->getCurrencyCode());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getCurrencyCode())));
    }

    public function testGetNote()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('Note')
            ->will($this->returnValue('Note'));

        $this->assertEquals('Note', $blockMock->getNote());

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $this->assertEquals(0, strlen(trim($blockMock->getNote())));
    }

    public function testHasBillsafeData()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array('getBillsafeData'));
        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->will($this->returnValue(array(1, 2)));

        $this->assertTrue(0 < count($blockMock->hasBillsafeData()));
    }

    public function testGetBillsafeHelper()
    {
        $dataHelper = Mage::helper('billsafe');
        $blockMock = $this->getBlockMock('billsafe/info', array('getBillsafeHelper'));
        $blockMock->expects($this->any())
            ->method('getBillsafeHelper')
            ->will($this->returnValue($dataHelper));
        $this->assertTrue($blockMock->getBillsafeHelper() instanceof Netresearch_Billsafe_Helper_Data);
    }

    public function testToPdf()
    {
        $this->_block->toPdf();
        $this->assertEquals('billsafe/pdf/info.phtml', $this->_block->getTemplate());
    }

    public function testToMrgPdf()
    {
        $this->_block->toMrgPdf();
        $this->assertEquals('billsafe/pdf/mrg.phtml', $this->_block->getTemplate());
    }

    public function testIsBillsafeCancelledTrue()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );
        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(true));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('BillsafeStatus')
            ->will($this->returnValue(Netresearch_Billsafe_Model_Payment::BILLSAFE_STATUS_CANCELLED));

        $this->assertTrue($blockMock->isBillsafeCancelled());
    }

    public function testIsBillsafeCancelledFalse()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'hasBillsafeData',
            'getBillsafeData'
            )
        );

        $blockMock->expects($this->any())
            ->method('hasBillsafeData')
            ->will($this->returnValue(false));

        $blockMock->expects($this->any())
            ->method('getBillsafeData')
            ->with('BillsafeStatus')
            ->will($this->returnValue(null));

        $this->assertFalse($blockMock->isBillsafeCancelled());
    }

    public function testGetOrder()
    {
        $dummyOrder = Mage::getModel('sales/order');
        $dummyInfo  = Mage::getModel('payment/info');

        $dummyOrder->setId(123);
        $dummyInfo->setOrder($dummyOrder);

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'getInfo'
        ));

        $blockMock->expects($this->any())
            ->method('getInfo')
            ->will($this->returnValue($dummyInfo));

        $this->replaceByMock('block', 'billsafe/info', $blockMock);

        $order = $blockMock->getOrder();
        $this->assertEquals(123, $order->getId());
    }

    public function testGetBillsafeDataWithNoOrder()
    {
        $blockMock = $this->getBlockMock('billsafe/info', array(
            'getOrder',
            )
        );
        $blockMock->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue(null));

        $this->assertTrue(0 == count($blockMock->getBillsafeData()));
    }


    public function testGetBillsafeDataWithOrder()
    {
        $order = new Varien_Object();
        $payment = new Varien_Object();
        $payment->setAdditionalInformation(array('fooo' => 1));
        $order->setPayment($payment);

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'getOrder',
            )
        );
        $blockMock->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($order));

        $billsafeData = $blockMock->getBillsafeData();
        $this->assertTrue(is_array($billsafeData));
        $this->assertArrayHasKey('fooo', $billsafeData);
        $this->assertEquals(1, $billsafeData['fooo']);

    }
    public function testGetBillsafeDataWithOrderAndNotExistantKey()
    {
        $order = new Varien_Object();
        $payment = new Varien_Object();
        $payment->setAdditionalInformation(array('fooo' => 1));
        $order->setPayment($payment);

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'getOrder',
            )
        );
        $blockMock->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($order));

        $billsafeData = $blockMock->getBillsafeData('test');
        $this->assertEquals("", $billsafeData);

    }

    public function testGetBillsafeDataWithOrderAndExistantKey()
    {
        $order = new Varien_Object();
        $payment = new Varien_Object();
        $payment->setAdditionalInformation(array('fooo' => 1));
        $order->setPayment($payment);

        $blockMock = $this->getBlockMock('billsafe/info', array(
            'getOrder',
            )
        );
        $blockMock->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($order));

        $billsafeData = $blockMock->getBillsafeData('fooo');
        $this->assertEquals(1, $billsafeData);

    }

    public function testGetBillsafeTitle()
    {
        $this->assertEquals(
            Mage::getModel('billsafe/config')->getBillsafeTitle(),
            $this->_block->getBillsafeTitle()
        );
    }
}
