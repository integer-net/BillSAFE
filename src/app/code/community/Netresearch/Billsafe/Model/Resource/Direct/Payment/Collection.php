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
 * Resource Collection Model
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Billsafe_Model_Resource_Direct_Payment_Collection
    extends Mage_Sales_Model_Resource_Order_Collection_Abstract
{
    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix    = 'billsafe_direct_payment_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject    = 'direct_payment_collection';

   /**
     * Order field for setOrderFilter
     *
     * @var string
     */
    protected $_orderField     = 'order_id';

    /**
     * Model initialization
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('billsafe/direct_payment');
    }


    /**
     * Sum up all reported payments.
     *
     * @return Netresearch_Billsafe_Model_Resource_Direct_Payment_Collection
     */
    public function addTotalReportAmount()
    {
        $this->getSelect()
            ->columns(array("base_total_report_amount" => "COALESCE(SUM(base_report_amount), 0)"));

        return $this;
    }
}
