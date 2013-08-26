<?php

class AwHh_PaymentFee_Model_Observer {

    protected function getHelper() {
        return Mage::helper('paymentfee');
    }

    public function controllerActionPredispatchCheckoutCartIndex($event) {
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
    public function handlePaymentFee($event = "billsafe") {
        /* @var $helper AwHh_PaymentFee_Helper_Data */
        $helper = $this->getHelper();

        /* @var $quote Mage_Sales_Model_Quote */
        $quote = Mage::getModel('checkout/cart')->getQuote();

        // check if extension is enabled and given fees are sane
        if (!$helper->isEnabled() || !$helper->hasFeeValues()) {
            $helper->removeFeeFromQuote(Mage::getModel('checkout/cart')->getQuote());
        } else {
            if ($event) {
                if (is_string($event)) {
                    $code = $event;
                } else {
                    $frontController = $event->getControllerAction();

                    if ($frontController) {
                        $request = $frontController->getRequest();

                        if ($request) {
                            $params = $request->getParams();
                            $code = null;

                            if (array_key_exists('payment', $params)) {
                                if (array_key_exists('method', $params['payment'])) {
                                    $code = $params['payment']['method'];
                                }
                            }
                        }
                    } else {
                        // OneStepCheckout
                        $code = $quote->getPayment()->getMethod();

                        // Fallback for customers, which haven´t setup "billsafe_installment" in "valid paymentfee paymentmethods"
                        if ($code == "billsafe_installment") {
                            $code = "billsafe";
                        }
                    }
                }
            }

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

                $is_allowed = true;
                $request = Mage::app()->getFrontController()->getRequest();

                if ($request) {
                    if ($request->getControllerName() == "cart") {
                        $is_allowed = false;
                    }
                }

                if ($is_allowed) {
                    if (in_array($code, $feeMethods)) {
                        // add new fee
                        try {
                            $fee = $helper->getUpdatedFeeProduct(null, $grandTotal);
                            $quoteItem = $quote->addProduct($fee);
                            $quoteItem = $this->adjustFeeItem($quoteItem);
                            #$quoteItem->save();

                            $quote->setItemsCount($quote->getItemsCount() - 1);
                            $quote->setItemsQty((float) $quote->getItemsQty() - 1);
                        } catch (Mage_Core_Exception $e) {
                            $result = $e->getMessage();
                        }
                    }
                }

                $items = ($quote->getAllItems());
                $quote->save();
            }
        }
    }

    public function updateQuoteItemsCount($event) {
        if (Mage::getStoreConfig('payment_services/paymentfee/active')) {
            $quote = $event->getQuote();
            foreach ($quote->getAllItems() as $quoteItem) {
                if ($this->getHelper()->isFeeProduct($quoteItem->getProduct())) {
                    $quote->setItemsCount($quote->getItemsCount() - 1);
                    $quote->setItemsQty((float) $quote->getItemsQty() - 1);
                    $quote->save();
                }
            }
        }
    }

    protected function adjustFeeItem($item) {
        if (Mage::getStoreConfig('payment_services/paymentfee/active')) {
            $fee = $this->getHelper()->getUpdatedFeeProduct();
            $item->setCustomPrice($fee->getPrice());
            $item->setPrice($fee->getPrice());
            #$item->setRowTotal($fee->getPrice());
            $item->setRowTotalInclTax($fee->getPrice());
            $item->setOriginalCustomPrice($fee->getPrice());
            $item->setName($fee->getName());
            $item->setCheckoutDescription($fee->getCheckoutDescription());
            $item->save();
        }
        
        return $item;
    }

}

