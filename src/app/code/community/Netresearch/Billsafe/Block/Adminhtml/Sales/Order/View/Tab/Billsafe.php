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
 * Admin Block for BillSAFE tab in order view
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Tab_Billsafe
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function getTabLabel()
    {
        return Mage::helper('billsafe/data')->__('BillSAFE');
    }

    public function getTabTitle()
    {
        return Mage::helper('billsafe/data')->__('BillSAFE');
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Adminhtml_Block_Widget_Tab_Interface::canShowTab()
     */
    public function canShowTab()
    {
        return $this->getOrder()->hasInvoices()
            && $this->helper('billsafe/order')->hasBillsafePayment($this->getOrder())
            && ($this->getOrder()->getBaseTotalInvoiced() !== $this->getOrder()->getBaseTotalRefunded())
        ;
    }

    public function isHidden()
    {
        return !Mage::getSingleton('admin/session')->isAllowed('sales/invoice');
    }
}
