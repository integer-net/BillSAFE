<?php
class Netresearch_PaymentFee_Model_Observer
{
    protected function getHelper()
    {
        return Mage::helper('paymentfee');
    }

    public function controllerActionPredispatchCheckoutCartIndex($event)
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        if ($this->getHelper()->hasFeeProduct()) {
            // remove current fee if exists
            $feeAmount = 0;
            foreach ($quote->getItemsCollection() as $quoteItem) {
                if ($this->getHelper()->isFeeProduct($quoteItem->getProduct())) {
                    $feeAmount = $quoteItem->getBaseRowTotalInclTax();
                    $quote->removeItem($quoteItem->getId());
                }
            }
        }
        $quote->save();
    }

    /**
     * On event "controller_action_predispatch_checkout_onepage_savePayment", 
     * add payment fee to quote if enabled or remove from quote if disabled
     * 
     * @param Varien_Event_Observer $event
     */
    public function handlePaymentFee($event)
    {
        $helper = $this->getHelper();
        /* @var $helper Netresearch_PaymentFee_Helper_Data */
        
        // check if extension is enabled and given fees are sane
        if (!$helper->isEnabled() || !$helper->hasFeeValues()) {
            $helper->removeFeeFromQuote(Mage::getModel('checkout/cart')->getQuote());
        } else {
            $params = $event->getControllerAction()->getRequest()->getParams();
            $code = null;
            if (array_key_exists('payment', $params)) {
                if (array_key_exists('method', $params['payment'])) {
                    $code =  $params['payment']['method'];
                }
            }

            $quote = Mage::getModel('checkout/cart')->getQuote();

            if ($helper->hasFeeProduct()) {
                // remove current fee if exists
                $feeAmount = 0;
                foreach ($quote->getItemsCollection() as $quoteItem) {
                    if ($helper->isFeeProduct($quoteItem->getProduct())) {
                        $feeAmount = $quoteItem->getBaseRowTotalInclTax();
                        $quote->removeItem($quoteItem->getId());
                    }
                }

                $grandTotal = $quote->getGrandTotal() - $feeAmount;

                // add payment fee if payment method is set
                $feeMethods = explode(',', Mage::getStoreConfig(
                    'payment_services/paymentfee/payment_methods'));

                if (in_array($code, $feeMethods)) {
                    // add new fee
                    try {
                        $fee = $helper->getUpdatedFeeProduct(null, $grandTotal);
                        $quoteItem = $quote->addProduct($fee);
                        $this->adjustFeeItem($quoteItem);
                        $quoteItem->save();

                        $quote->setItemsCount($quote->getItemsCount()-1);
                        $quote->setItemsQty((float) $quote->getItemsQty()-1);
                    } catch (Mage_Core_Exception $e) {
                        $result = $e->getMessage();
                    }
                }
                $quote->save();
            }
        }
    }


    public function updateQuoteItemsCount($event)
    {
        if (Mage::getStoreConfig('payment_services/paymentfee/active')) {
            $quote = $event->getQuote();
            foreach ($quote->getAllItems() as $quoteItem) {
                if ($this->getHelper()->isFeeProduct($quoteItem->getProduct())) {
                    $quote->setItemsCount($quote->getItemsCount()-1);
                    $quote->setItemsQty((float) $quote->getItemsQty()-1);
                    $quote->save();
                }
            }
        }
    }

    protected function adjustFeeItem($item)
    {
        if (Mage::getStoreConfig('payment_services/paymentfee/active')) {
            $fee = $this->getHelper()->getUpdatedFeeProduct();
            $item->setCustomPrice($fee->getPrice());
            $item->setOriginalCustomPrice($fee->getPrice());
            $item->setName($fee->getName());
            $item->setCheckoutDescription($fee->getCheckoutDescription());
            $item->save();
        }
    }
}

