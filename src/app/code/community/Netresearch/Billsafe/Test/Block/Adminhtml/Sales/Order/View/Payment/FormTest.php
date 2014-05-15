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
class Netresearch_Billsafe_Test_Block_Adminhtml_Sales_Order_View_Payment_FormTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var string
     */
    protected $_submitUrl = 'reportDirect';

    /**
     * @var string
     */
    protected $_dateStrFormat;

    /**
     * @var Zend_Date
     */
    protected $_date;

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->_submitUrl     = 'reportDirect';
        $this->_dateStrFormat = 'my_date_str_format';
        $this->_date          = new Zend_Date(array(
            'year'  => 1970,
            'month' => 1,
            'day'   => 2,
        ));
    }

    protected function setUp()
    {
        parent::setUp();

        // LOCALE MOCK
        $localeMock = $this->getModelMock('core/locale', array('date', 'getDateStrFormat'));
        $localeMock
            ->expects($this->any())
            ->method('date')
            ->will($this->returnValue($this->_date))
        ;
        $localeMock
            ->expects($this->any())
            ->method('getDateStrFormat')
            ->will($this->returnValue($this->_dateStrFormat))
        ;
        $this->replaceByMock('singleton', 'core/locale', $localeMock);

        // APP MOCK
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            Mage::app(),
            '_locale',
            Mage::getSingleton('core/locale')
        );

        // PAYMENT FORM BLOCK MOCK
        $paymentFormBlockMock = $this->getBlockMock(
            'billsafe/adminhtml_sales_order_view_payment_form',
            array('getOrder', 'getUrl')
        );
        $paymentFormBlockMock
            ->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue(new Varien_Object()))
        ;
        $paymentFormBlockMock
            ->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue($this->_submitUrl))
        ;
        $this->replaceByMock(
            'block',
            'billsafe/adminhtml_sales_order_view_payment_form',
            $paymentFormBlockMock
        );
    }

    /**
     * @test
     */
    public function getCurrentDate()
    {
        /* @var $paymentFormBlock Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Payment_Form */
        $paymentFormBlock = Mage::app()->getLayout()
            ->createBlock('billsafe/adminhtml_sales_order_view_payment_form')
        ;

        $this->assertEquals(
            $this->_date->get(Zend_Date::DATE_MEDIUM),
            $paymentFormBlock->getCurrentDate()
        );
    }

    /**
     * @test
     */
    public function getDateFormat()
    {
        /* @var $paymentFormBlock Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Payment_Form */
        $paymentFormBlock = Mage::app()->getLayout()
            ->createBlock('billsafe/adminhtml_sales_order_view_payment_form')
        ;

        $this->assertEquals($this->_dateStrFormat, $paymentFormBlock->getDateFormat());
    }

    /**
     * @test
     */
    public function getSubmitUrl()
    {
        /* @var $paymentFormBlock Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Payment_Form */
        $paymentFormBlock = Mage::app()->getLayout()
            ->createBlock('billsafe/adminhtml_sales_order_view_payment_form')
        ;

        $this->assertEquals($this->_submitUrl, $paymentFormBlock->getSubmitUrl());
    }
}
