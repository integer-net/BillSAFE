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
 * Resource Model
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Billsafe_Model_Resource_Direct_Payment
    extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('billsafe/direct_payment', 'payment_id');
    }
}
