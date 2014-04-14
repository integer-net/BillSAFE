<?php
class Netresearch_PaymentFee_Helper_Data extends Mage_Core_Helper_Data
{
    protected $feeProduct;

    /**
     * Check if the extension is active
     * 
     * @return boolean
     */
    public function isEnabled()
    {
        return (bool)Mage::getStoreConfig('payment_services/paymentfee/active');
    }

    /**
     * Check if minimum fee amount, maximum fee amount or percentage rate is given
     * @return boolean
     */
    public function hasFeeValues()
    {
        $min = (bool)max(0, Mage::getStoreConfig('payment_services/paymentfee/min_fee_amount'));
        $max = (bool)Mage::getStoreConfig('payment_services/paymentfee/max_fee_amount');
        $rate = (bool)Mage::getStoreConfig('payment_services/paymentfee/relative_fee');
        return ($min || $max || $rate);
    }

    public function getFeeProductSku()
    {
        return Mage::getStoreConfig('payment_services/paymentfee/sku');
    }

    /**
     * if item represents fee product
     *
     * @param Mage_Catalog_Model_Product|Mage_Sales_Model_Item $product
     * @return boolean
     */
    public function isFeeProduct($product)
    {
        return ($product->getSku() == $this->getFeeProductSku());
    }

    public function setFeeProduct($feeProduct)
    {
        $this->feeProduct = $feeProduct;
    }

    public function getFeeProduct()
    {
        if (is_null($this->feeProduct)) {
            $this->feeProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $this->getFeeProductSku());
        }

        return $this->feeProduct;
    }

    public function hasFeeProduct()
    {
        $feeProduct = $this->getFeeProduct();
        return ($feeProduct && 0 < $feeProduct->getId());
    }

    /**
     * Obtain the fee that is set for the current payment method
     * @return float
     */
    public function getPaymentFee()
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        if (!Mage::getModel('checkout/cart')->getQuote()->getPayment()->hasMethodInstance()) {
            return 0;
        }

        // get the currently set payment method
        $payment_model = Mage::getModel('checkout/cart')->getQuote()->getPayment()->getMethodInstance();

        // check which methods are enabled for payment fee via backend
        $enabled_methods = explode(',', Mage::getStoreConfig('payment_services/paymentfee/payment_methods'));

        if (!$payment_model || !in_array($payment_model->getCode(), $enabled_methods)) {
            return 0;
        }

        // return fee if
        // (1) a payment method has been selected by the customer
        // (2) the selected payment method is enabled for payment fee via backend
        // (3) the payment method has a fee
        return (float)$payment_model->getFee();
    }

    /**
     * get quote item representing fee
     * 
     * @return Mage_Sales_Model_Quote_Item
     */
    protected function getFeeQuoteItem()
    {
        foreach (Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item) {
            if ($this->isFeeProduct($item->getProduct())) {
                return $item;
            }
        }
    }

    /**
     * Computed amount of payment fee based on backend configuration
     * and grand total and attach it to fee product.
     */
    public function getUpdatedFeeProduct($product=null, $grandTotal=null)
    {
        if (!$product) {
            $product = $this->getFeeProduct();
        }
        $product->setName($product->getResource()->getAttributeRawValue($product->getId(), 'name', Mage::app()->getStore()->getId()));
        if (!$grandTotal) {
            $quote      = Mage::getSingleton('checkout/session')->getQuote();
            $grandTotal = $quote->getGrandTotal();
            $feeAmount  = 0;
            foreach ($quote->getItemsCollection() as $quoteItem) {
                if ($this->isFeeProduct($quoteItem->getProduct())) {
                    $feeAmount = $quoteItem->getBaseRowTotalInclTax();
                    continue;
                }
            }
            $grandTotal -= $feeAmount;
        }
        $min = max(0, Mage::getStoreConfig('payment_services/paymentfee/min_fee_amount'));
        $max = Mage::getStoreConfig('payment_services/paymentfee/max_fee_amount');

        $rate = Mage::getStoreConfig('payment_services/paymentfee/relative_fee');
        //$product->setName($this->__('Payment fee'));
        if ($this->getFeeQuoteItem()) {
            $product->setTaxPercent($this->getFeeQuoteItem()->getTaxPercent());
        }

        // first, set payment fee to the price configured in backend
        $price = $max;
        
        // If set to zero, do not limit the final fee
        if (!$max) {
            $max = INF;
        }
        
        $product->setCheckoutDescription($this->formatPrice($price))
            ->setExceedsMaxAmount(false)
            ->setExceedsMinAmount(false);

        // calculate relative fee if given in backend
        if ($rate) {
            $price = $grandTotal * $rate / 100;

            if ($max < $price) {
                // calculated relative fee exceeds maximum charge 
                // -> use maximum charge
                $product->setCheckoutDescription($this->formatPrice($max));
                $product->setExceedsMaxAmount(true);
                $price = $max;
            } elseif ($price < $min) {
                // calculated relative fee is below minimum charge 
                // -> use minimum charge
                $product->setCheckoutDescription($this->formatPrice($min));
                $product->setExceedsMinAmount(true);
                $price = $min;
            } else {
                // calculated relative fee is between minimum and maximum charge
                // -> use calculated relative fee
                $msg = '%s (%s%% of Total %s)';
                $product->setCheckoutDescription($this->__(
                    $msg,
                    $this->formatPrice($price),
                    $rate,
                    $this->formatPrice($grandTotal)
                ));
                $msg = '%s %s (%s%% of Total %s)';
                $product->setName($this->__(
                    $msg,
                    $product->getName(),
                    strip_tags($this->formatPrice($price)),
                    $rate,
                    strip_tags($this->formatPrice($grandTotal))
                ));
            }
        }
        $product->setPriceInclTax($price)
            ->setPrice($price)
            ->setFinalPrice($price);

        // Make sure fee product is "in stock"
        $stockItem = Mage::getModel('cataloginventory/stock_item');
        $stockItem->assignProduct($product);
        $stockItem->setIsInStock(1);
        $stockItem->setManageStock(1);
        $stockItem->setQty(10000);
        $stockItem->save();

        return $product;
    }
    
    public function removeFeeFromQuote(Mage_Sales_Model_Quote $quote)
    {
        foreach ($quote->getItemsCollection() as $quoteItem) {
            if ($this->isFeeProduct($quoteItem->getProduct())) {
                $quote->removeItem($quoteItem->getId());
            }
        }
    }
}
