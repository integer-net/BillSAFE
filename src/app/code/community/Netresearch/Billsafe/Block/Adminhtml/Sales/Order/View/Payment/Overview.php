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
 * Admin Block for BillSAFE direct payments table
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Payment_Overview
    extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    /**
     * Obtain payment collection for current order.
     *
     * @return Netresearch_Billsafe_Model_Resource_Direct_Payment_Collection
     */
    public function getDirectPaymentCollection()
    {
        /* @var $directPaymentCollection Netresearch_Billsafe_Model_Resource_Direct_Payment_Collection */
        $directPaymentCollection = Mage::getModel('billsafe/direct_payment')
            ->getCollection();
        $directPaymentCollection->setOrderFilter($this->getOrder());
        return $directPaymentCollection;
    }

    /**
     * Format value based on order currency.
     *
     * @param number $value
     */
    public function formatAmount($value)
    {
        return Mage::helper('adminhtml/sales')->displayPrices(
            $this->getOrder(),
            $value,
            $value
        );
    }
}
