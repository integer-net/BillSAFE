<?php

class AwHh_Billsafe_Model_Creditmemo extends Mage_Sales_Model_Order_Creditmemo {

    /**
     * Register creditmemo
     *
     * Apply to order, order items etc.
     *
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    public function register() {
        if ($this->getId()) {
            Mage::throwException(
                    Mage::helper('sales')->__('Cannot register an existing credit memo.')
            );
        }

        foreach ($this->getAllItems() as $item) {
            if ($item->getQty() > 0) {
                $item->register();
            } else {
                $item->isDeleted(true);
            }
        }

        $this->setDoTransaction(true);
        $order = $this->getOrder();
        $payment = $order->getPayment();

        if ($this->getOfflineRequested()) {
            $this->setDoTransaction(false);
        }

        $this->refund();

        if ($this->getDoTransaction()) {
            $this->getOrder()->setTotalOnlineRefunded(
                    $this->getOrder()->getTotalOnlineRefunded() + $this->getGrandTotal()
            );
            $this->getOrder()->setBaseTotalOnlineRefunded(
                    $this->getOrder()->getBaseTotalOnlineRefunded() + $this->getBaseGrandTotal()
            );
        } else {
            $this->getOrder()->setTotalOfflineRefunded(
                    $this->getOrder()->getTotalOfflineRefunded() + $this->getGrandTotal()
            );
            $this->getOrder()->setBaseTotalOfflineRefunded(
                    $this->getOrder()->getBaseTotalOfflineRefunded() + $this->getBaseGrandTotal()
            );
        }

        $this->getOrder()->setBaseTotalInvoicedCost(
                $this->getOrder()->getBaseTotalInvoicedCost() - $this->getBaseCost()
        );

        $state = $this->getState();
        if (is_null($state)) {
            $this->setState(self::STATE_OPEN);
        }

        if ($payment->getMethod() == AwHh_Billsafe_Model_Installment::CODE || $payment->getMethod() == AwHh_Billsafe_Model_Payment::CODE) {
            // update und so
            $payment = $order->getPayment();
            $infos = $payment->getAdditionalInformation();
            $transcation_id = $infos["BillsafeBTN"];
            $amount = $order->getBaseGrandTotal();
            $taxAmount = $order->getTaxAmount();
            $client = Mage::getModel("billsafe/client");
            $articleList = $client->buildArticleList($order, "refund");
            $default_params = $client->getDefaultParamsExt();
            $order_amount = 0;
            
            for($i=0; $i<count($articleList); $i++) {
                //$articleList[$i]["quantityShipped"] = $articleList[$i]["quantity"];
                unset($articleList[$i]["quantityShipped"]);
                $articleList[$i]["grossPrice"] = Mage::helper("billsafe")->format($articleList[$i]["netPrice"] * ((1) + ($articleList[$i]["tax"] / 100)));
                unset($articleList[$i]["netPrice"]);
                $order_amount += Mage::helper("billsafe")->format($articleList[$i]["grossPrice"] * $articleList[$i]["quantity"]);
            }

            $params = array_merge(
                    array(
                'order' => array(
                    'number' => $order->getIncrementId(),
                    'amount' => Mage::helper("billsafe")->format($order_amount),
                    'taxAmount' => Mage::helper("billsafe")->format($taxAmount),
                    'currencyCode' => 'EUR',
                ),
                #'orderNumber' => $order->getIncrementId(),
                "transactionId" => $transcation_id,
                'articleList' => ($amount > 0) ? $articleList : array()), $default_params
            );

            $result = $client->updateArticleListExt($params);
        }

        return $this;
    }

}