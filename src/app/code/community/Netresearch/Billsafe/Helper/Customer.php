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
 * @copyright   Copyright (c) 2013 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper class for retrieving values related to the customer for Billsafe module
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Michael Lühr <michael.luehr@netresearch.de>
 */
class Netresearch_Billsafe_Helper_Customer extends Mage_Customer_Helper_Data
{
    protected $_dataHelper = null;

    /**
     * Tries to guess customers gender in billsafe required form (f || m)
     *
     * @param Mage_Customer_Model_Address $address
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Customer_Model_Customer $customer
     * @return string
     */
    public function getCustomerGender($address, $order, $customer)
    {
        $gender = Mage::helper('billsafe/data')->coalesce(
            $this->getGenderText($address, 'gender'),
            $this->getGenderText($order, 'customer_gender'),
            $this->getGenderText($customer, 'gender'),
            Mage::getModel('billsafe/config')->getDefaultCustomerGender($order->getStoreId())
        );

        // override previous gender configuration with the most common prefixes (DE)
        if (in_array('Herr', array(
            $address->getPrefix(),
            $order->getCustomerPrefix(),
            $customer->getPrefix())
        )) {
            $gender = 'Male';
        }

        if (in_array('Frau', array(
            $address->getPrefix(),
            $order->getCustomerPrefix(),
            $customer->getPrefix())
        )) {
            $gender = 'Female';
        }

        return ($gender === 'Female') ? 'f' : 'm';
    }


    /**
     * Get formated date of birth of customer, if not set return default.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return string
     */
    public function getCustomerDob($customer, $salesObject)
    {
        $dob = null;
        if ($salesObject instanceof Mage_Sales_Model_Order) {
            $dob = $salesObject->getCustomerDob();
        }
        if (!$dob) {
            if (!$customer || !$customer->getDob()) {
                return '1970-01-01';
            }
            $dob = $customer->getDob();
        }
        $date = new Zend_Date(strtotime($dob));
        return $date->get('yyyy-MM-dd');
    }


    /**
     * Retrieve text of gender attribute of given entity.
     *
     * @param Mage_Core_Model_Abstract $entity
     * @param string $attributeCode
     * @return string
     */
    public function getGenderText($entity, $attributeCode)
    {
        return Mage::getSingleton('eav/config')
            ->getAttribute('customer', 'gender')
            ->getSource()
            ->getOptionText($entity->getData($attributeCode));
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return string
     */
    public function getCustomerCompany(Mage_Sales_Model_Quote $quote = null)
    {
        if(is_null($quote)){
            $quote = $this->getDataHelper()->getQuotefromSession();
        }
        return $quote->getBillingAddress()->getCompany();
    }

    /**
     * @return Netresearch_Billsafe_Helper_Data|null
     */

    protected function getDataHelper()
    {
        if(is_null($this->_dataHelper)){
            $this->_dataHelper = Mage::helper('billsafe/data');
        }
        return $this->_dataHelper;
    }

    /**
     * @param Netresearch_Billsafe_Helper_Data $dataHelper
     */
    public function setDataHelper(Netresearch_Billsafe_Helper_Data $dataHelper){
        $this->_dataHelper = $dataHelper;
    }

}