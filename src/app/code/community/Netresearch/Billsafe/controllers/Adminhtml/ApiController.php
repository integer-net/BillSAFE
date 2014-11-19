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
 * Admin Controller for BillSAFE API actions
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Billsafe_Adminhtml_ApiController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Pause transaction at BillSAFE.
     */
    public function pauseAction()
    {
        $pause = $this->getRequest()->getPost('pause');
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$pause || !$orderId) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody($this->__("The request misses required parameters."));
            return;
        }

        if ($pause < 1 || $pause > 10) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody($this->__("The given number of days is invalid."));
            return;
        }

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order->getId()) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody(Mage::helper('sales/data')->__("This order no longer exists."));
            return;
        }

        /* @var $orderHelper Netresearch_Billsafe_Helper_Order */
        $orderHelper = Mage::helper('billsafe/order');
        $transaction = $orderHelper->getCapturedTransaction($orderId);
        if ($transaction->getId() && $transaction->getParentTxnId()) {
            $transactionId = $transaction->getParentTxnId();
            $orderNumber   = $order->getIncrementId();
        } else {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody($this->__("No captured transaction found."));
            return;
        }

        try {
            /* @var $soapClient Netresearch_Billsafe_Model_Client */
            $soapClient = Mage::getModel('billsafe/client');
            $success = $soapClient->pauseTransaction($order, $transactionId, $orderNumber, $pause);
            if (!$success) {
                $this->getResponse()
                    ->setHttpResponseCode(404)
                    ->setBody($soapClient->getResponseErrorMessage());
            } else {
                $this->getResponse()->setBody($this->__("The transaction was successfully paused at BillSAFE."));
            }
        } catch (Mage_Core_Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(502)
                ->setBody($this->__("BillSAFE server is temporarily not available, please try again later"));
            return;
        } catch (Mage_Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->setBody($e->getMessage());
            return;
        } catch (Zend_Db_Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->setBody($this->__("An error occurred during request processing."));
            return;
        }
    }

    /**
     * Report direct payment to BillSAFE.
     */
    public function reportDirectAction()
    {
        $paymentAmount = $this->getRequest()->getPost('payment_amount');
        $paymentDate   = $this->getRequest()->getPost('payment_date');
        $orderId       = $this->getRequest()->getParam('order_id');

        if (!$paymentAmount) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody($this->__("Please enter a payment amount."));
            return;
        }
        $paymentAmount = Mage::app()->getLocale()->getNumber($paymentAmount);

        if ($paymentAmount <= 0) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody($this->__("Please enter an amount which is greater than 0."));
            return;
        }

        if (!$paymentDate) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody($this->__("Please enter the date of payment receipt."));
            return;
        }
        $paymentDate = Mage::app()->getLocale()->date($paymentDate)->toString(sprintf(
            "%s-%s-%s", Zend_Date::YEAR, Zend_Date::MONTH, Zend_Date::DAY
        ));

        if (!$orderId) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody($this->__("The request misses required parameters."));
            return;
        }

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order->getId()) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody(Mage::helper('sales/data')->__("This order no longer exists."));
            return;
        }

        /* @var $orderHelper Netresearch_Billsafe_Helper_Order */
        $orderHelper = Mage::helper('billsafe/order');
        $openPaymentAmount = $orderHelper->getOpenPaymentAmount($order);
        if ($paymentAmount > $openPaymentAmount) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody(Mage::helper('sales/data')->__("The given payment amount exceeds the outstanding payment amount."));
            return;
        }

        $transaction = $orderHelper->getCapturedTransaction($orderId);
        if ($transaction->getId() && $transaction->getParentTxnId()) {
            $transactionId = $transaction->getParentTxnId();
            $orderNumber   = $order->getIncrementId();
        } else {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->setBody($this->__("No captured transaction found."));
            return;
        }

        try {
            /* @var $soapClient Netresearch_Billsafe_Model_Client */
            $soapClient = Mage::getModel('billsafe/client');
            $success = $soapClient->reportDirectPayment($order, $transactionId, $orderNumber, $paymentAmount, $paymentDate);
            if (!$success) {
                $this->getResponse()
                    ->setHttpResponseCode(404)
                    ->setBody($this->__($soapClient->getResponseErrorMessage()));
            } else {
                /* @var $directPayment Netresearch_Billsafe_Model_Direct_Payment */
                $directPayment = Mage::getModel('billsafe/direct_payment');
                $directPayment
                    ->setOrderId($orderId)
                    ->setCreatedAt($paymentDate)
                    ->setBaseReportAmount($paymentAmount)
                    ->setBaseTotalBefore($openPaymentAmount)
                    ->setBaseTotalAfter($openPaymentAmount - $paymentAmount)
                    ->save();
                $this->getResponse()->setBody($this->__("The direct payment was successfully reported to BillSAFE."));
            }
        } catch (Mage_Core_Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(502)
                ->setBody($this->__("BillSAFE server is temporarily not available, please try again later"));
            return;
        } catch (Mage_Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->setBody($e->getMessage());
            return;
        } catch (Zend_Db_Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->setBody($this->__("An error occurred during request processing."));
            return;
        }
    }

    protected function _isAllowed()
    {
        return $this->getRequest()->isPost()
            && Mage::getSingleton('admin/session')->isAllowed('sales/invoice');
    }
}
