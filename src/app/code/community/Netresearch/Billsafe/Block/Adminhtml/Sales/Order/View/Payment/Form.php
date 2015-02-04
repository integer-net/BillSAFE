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
 * Admin Block for Direct Payment form
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 *
 * @method Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Payment_Form setLocale() setLocale(Mage_Core_Model_Locale $locale)
 */
class Netresearch_Billsafe_Block_Adminhtml_Sales_Order_View_Payment_Form
    extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    /**
     * Retrieve localized date for calendar (date picker).
     *
     * @return string
     */
    public function getCurrentDate()
    {
        return Mage::app()->getLocale()->date()->get(Zend_Date::DATE_MEDIUM);
    }

    /**
     * Retrieve currently used date format for calendar (date picker).
     *
     * @return string
     */
    public function getDateFormat()
    {
        return Mage::app()->getLocale()->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
    }

    protected function _prepareLayout()
    {
        $onclick = "submitAndReloadBillsafeArea($('billsafe_payment_form').parentNode, '".$this->getSubmitUrl()."')";
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'   => $this->__('Report Direct Payment'),
                'class'   => 'save',
                'onclick' => $onclick
            ));
        $this->setChild('submit_button', $button);
        return parent::_prepareLayout();
    }

    public function getSubmitUrl()
    {
        return $this->getUrl('*/api/reportDirect', array(
            'order_id' => $this->getOrder()->getId()
        ));
    }
}
