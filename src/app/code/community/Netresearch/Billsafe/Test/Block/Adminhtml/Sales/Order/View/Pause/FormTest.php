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
 * Admin Block test for Pause Transaction form
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Billsafe_Test_Block_Adminhtml_Sales_Order_View_Pause_FormTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     */
    public function getPauseDays()
    {
        $pauseFormBlockMock = $this->getBlockMock(
            'billsafe/adminhtml_sales_order_view_pause_form',
            array('getOrder', 'getUrl')
        );
        $pauseFormBlockMock
            ->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue(new Varien_Object()))
        ;
        $pauseFormBlockMock
            ->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue(''))
        ;
        $this->replaceByMock('block', 'billsafe/adminhtml_sales_order_view_pause_form', $pauseFormBlockMock);


        /* @var $pauseFormBlock Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Pause_Form */
        $pauseFormBlock = Mage::app()->getLayout()
            ->createBlock('billsafe/adminhtml_sales_order_view_pause_form')
        ;

        $days = $pauseFormBlock->getPauseDays();

        $this->assertTrue(is_array($days));
        $this->assertContainsOnly('int', $days);

        $minValue = $days[0];
        $maxValue = end($days);

        $this->assertGreaterThan(0, $minValue);
        $this->assertLessThan(11, $maxValue);
    }
}
