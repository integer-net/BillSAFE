<?php
class Netresearch_Billsafe_Block_Adminhtml_Sales_Order_Totalbar
    extends Mage_Adminhtml_Block_Sales_Order_Totalbar
{
    const CONTEXT_INVOICE = 'invoice';

    /**
     * Rewrite block to attach message, if items count does not match requirements.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->isBillsafePayment()) {
            return parent::_toHtml();
        }
        return $this->getNotice() . parent::_toHtml();
    }

    /**
     * Checks, if current payment is a billsafe payment.
     *
     * @return boolean
     */
    protected function isBillsafePayment()
    {
        return $this->getParentBlock()->getOrder()->getPayment()->getMethod()
        == Netresearch_Billsafe_Model_Payment::CODE;
    }

    /**
     * Generates notice based on requirements
     *
     * @return string
     */
    protected function getNotice()
    {
        if ($invoice = $this->getParentBlock()->getInvoice()) {
            if (!$this->willAllItemsBeInvoiced($invoice)) {
                return $this->getBox(
                    'error',
                    $this->__('WARNING! All univoiced items will be canceled.')
                );
            }
            if (!$this->isCountEqualSentItemsCount($invoice)) {
                return $this->getBox(
                    'error', $this->__(
                        'WARNING! Count of items to invoice does not match sent'.
                        ' items count. Please change to items to be invoiced to'.
                        ' match shipped items!'
                    )
                );
            }
        }
        return '';
    }

    /**
     * Checks if all items be invoiced when creating this invoice
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     *
     * @return boolean
     */
    protected function willAllItemsBeInvoiced($invoice)
    {
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->getQtyOrdered() > ($orderItem->getQtyInvoiced()
                    + $item->getQty())
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if count of items invoiced equals count of items shipped.
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     *
     * @return boolean
     */
    protected function isCountEqualSentItemsCount($invoice)
    {
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $parentIdArray = array();
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
                $configurableProductModel = Mage::getModel('catalog/product_type_configurable');
                $parentIdArray = $configurableProductModel->getParentIdsByChild($product->getId());
            }
            if (Mage::helper('paymentfee')->isFeeProduct($orderItem) ||
                (0 < $product->getId() && $product->isVirtual()) ||
                0 < count($parentIdArray)
            ) {
                continue;
            }
            if ($orderItem->getQtyShipped() != ($orderItem->getQtyInvoiced()
                    + $item->getQty())
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Generates htms box containing given message
     *
     * @param string $type
     * @param string $message
     *
     * @return string
     */
    protected function getBox($type, $message)
    {
        return sprintf(
            '<div id="messages"><ul class="messages"><li class="%s-msg"><ul><li>%s</li></ul></li></ul></div>',
            $type, $message
        );
    }
}

