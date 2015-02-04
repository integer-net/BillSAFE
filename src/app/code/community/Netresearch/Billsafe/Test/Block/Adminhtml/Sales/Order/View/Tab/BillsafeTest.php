<?php
/**
 * Netresearch Billsafe
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @copyright   Copyright (c) 2014 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Admin Block test for BillSAFE tab in order view
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Billsafe_Test_Block_Adminhtml_Sales_Order_View_Tab_BillsafeTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function getLabelAndTitle()
    {
        /* @var $tabBlock Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Tab_Billsafe */
        $tabBlock = Mage::app()->getLayout()
            ->createBlock('billsafe/adminhtml_sales_order_view_tab_billsafe')
        ;

        $this->assertEquals('BillSAFE', $tabBlock->getTabLabel());
        $this->assertEquals('BillSAFE', $tabBlock->getTabTitle());
    }

    /**
     * @test
     */
    public function canShowTab()
    {
        $orderHelperMock = $this->getHelperMock('billsafe/order', array('hasBillsafePayment'));
        $orderHelperMock
            ->expects($this->any())
            ->method('hasBillsafePayment')
            ->will($this->onConsecutiveCalls(true, false, true, false, true));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $orderMock = $this->getModelMock('sales/order', array(
            'hasInvoices',
            'getBaseTotalInvoiced',
            'getBaseTotalRefunded',
        ));
        $orderMock
            ->expects($this->any())
            ->method('hasInvoices')
            ->will($this->onConsecutiveCalls(true, true, false, false, true));
        $orderMock
            ->expects($this->any())
            ->method('getBaseTotalInvoiced')
            ->will($this->returnValue(314));
        $orderMock
            ->expects($this->any())
            ->method('getBaseTotalRefunded')
            ->will($this->onConsecutiveCalls(0, 314));
        $this->replaceByMock('model', 'sales/order', $orderMock);

        $this->replaceRegistry('current_order', Mage::getModel('sales/order'));

        /* @var $tabBlock Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Tab_Billsafe */
        $tabBlock = Mage::app()->getLayout()
            ->createBlock('billsafe/adminhtml_sales_order_view_tab_billsafe')
        ;

        $this->assertTrue($tabBlock->canShowTab());
        $this->assertFalse($tabBlock->canShowTab());
        $this->assertFalse($tabBlock->canShowTab());
        $this->assertFalse($tabBlock->canShowTab());
        $this->assertFalse($tabBlock->canShowTab());
    }

    /**
     * @test
     */
    public function isHidden()
    {
        $sessionMock = $this->getModelMock('admin/session', array('isAllowed', 'init'));
        $sessionMock
            ->expects($this->any())
            ->method('isAllowed')
            ->with('sales/invoice')
            ->will($this->onConsecutiveCalls(true, false));
        $sessionMock
            ->expects($this->any())
            ->method('init')
            ->will($this->returnSelf());
        $this->replaceByMock('singleton', 'admin/session', $sessionMock);

        /* @var $tabBlock Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Tab_Billsafe */
        $tabBlock = Mage::app()->getLayout()
            ->createBlock('billsafe/adminhtml_sales_order_view_tab_billsafe')
        ;

        $this->assertFalse($tabBlock->isHidden());
        $this->assertTrue($tabBlock->isHidden());
    }
}
