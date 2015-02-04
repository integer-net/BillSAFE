<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Netresearch
 * @package     Netresearch_PaymentFee
 * @copyright   Copyright (c) 2011 Netresearch GmbH & Co. KG (http://www.netresearch.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Netresearch_PaymentFee_Adminhtml_FeeController extends Mage_Adminhtml_Controller_Action
{
    public function createAction()
    {
        // Build the product
        $product = new Mage_Catalog_Model_Product();
        $product
            ->setSku((string) Mage::getConfig()->getNode('default/payment_services/paymentfee/sku'))
            ->setStoreId(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
            ->setAttributeSetId($product->getDefaultAttributeSetId())
            ->setTypeId('virtual')
            ->setName('Payment Fee')
            ->setDescription('Payment Fee')
            ->setShortDescription('Payment Fee')
            ->setPrice(3.5)
            ->setTaxClassId(Mage::getSingleton('tax/config')->getShippingTaxClass())
            ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
            ->setStatus(1)
            ->setTaxClassId(0)
            ->setStockData(array(
                'is_in_stock'             => 1,
                'use_config_manage_stock' => 0,
                'manage_stock'            => 0,
                'qty'                     => 99999
            ))
            ->setCreatedAt(strtotime('now'))
            ->save();
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
        $stockItem
            ->setData('is_in_stock', 1)
            ->save();

        Mage::log('created paymentfee product ' . $product->getId() . ' with attribute set id ' . $attributeSetId);
        return $product->getSku();
    }

    protected function _isAllowed()
    {
        return true;
        return Mage::getSingleton('admin/session')->isAllowed('catalog');
    }
}
