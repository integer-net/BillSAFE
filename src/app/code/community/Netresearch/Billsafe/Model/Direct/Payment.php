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
 * Direct Payment Model
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 *
 * @method int getOrderId()
 * @method Netresearch_Billsafe_Model_Direct_Payment setOrderId() setOrderId(int $order_id)
 * @method string getCreatedAt()
 * @method Netresearch_Billsafe_Model_Direct_Payment setCreatedAt() setCreatedAt(string $created_at)
 * @method float getBaseReportAmount()
 * @method Netresearch_Billsafe_Model_Direct_Payment setBaseReportAmount() setBaseReportAmount(float $base_report_amount)
 * @method float getBaseTotalBefore()
 * @method Netresearch_Billsafe_Model_Direct_Payment setBaseTotalBefore() setBaseTotalBefore(float $base_total_before)
 * @method float getBaseTotalAfter()
 * @method Netresearch_Billsafe_Model_Direct_Payment setBaseTotalAfter() setBaseTotalAfter(float $base_total_after)
 *
 * @property int $order_id
 * @property string $created_at
 * @property float $base_report_amount
 * @property float $base_total_before
 * @property float $base_total_after
 */
class Netresearch_Billsafe_Model_Direct_Payment extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('billsafe/direct_payment');
    }

    /**
     * Get object created at date affected with object store timezone
     *
     * @return Zend_Date
     */
    public function getCreatedAtStoreDate()
    {
        return Mage::app()->getLocale()->storeDate(
            $this->getStore(),
            Varien_Date::toTimestamp($this->getCreatedAt()),
            true
        );
    }

    /**
     * Get formated payment created date in store timezone
     *
     * @param   string $format date format type (short|medium|long|full)
     * @return  string
     */
    public function getCreatedAtFormatted()
    {
        return Mage::helper('core')->formatDate(
            $this->getCreatedAtStoreDate(),
            Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM
        );
    }
}
