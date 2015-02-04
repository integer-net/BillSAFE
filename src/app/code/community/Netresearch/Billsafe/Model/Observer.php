<?php
class Netresearch_Billsafe_Model_Observer
{
    /**
     * Register invoice for using it in pdf generation
     *
     * @parmam Varien_Event_Observer $event
     *
     * @return void
     **/
    public function registerInvoice($event)
    {
        if (Mage::registry('invoice')
            instanceof Mage_Sales_Model_Order_Invoice
        ) {
            return;
        }
        Mage::register('invoice', $event->getInvoice());
    }

    /**
     * Report shipment to billsafe
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function reportShipment($observer)
    {
        $order = $observer->getShipment()->getOrder();
        $code = $order->getPayment()->getMethodInstance()->getCode();
        if ($code != Netresearch_Billsafe_Model_Payment::CODE) {
            return;
        }
        // only communicate to billsafe if no invoce is created so far
        if (!Mage::helper('billsafe')->isOrderAlreadyInvoiced($order)
            || Mage::helper('billsafe')->isDoShipment()
        ) {
            Mage::getModel('billsafe/client')->reportShipment(
                $observer->getShipment()
            );
        }
    }

    public function cancelOrderIfRequested($observer)
    {
        if (Mage::registry(
            Netresearch_Billsafe_Model_Payment::REGISTRY_ORDER_SHOULD_BE_CANCELLED
        )
        ) {
            Mage::unregister(
                Netresearch_Billsafe_Model_Payment::REGISTRY_ORDER_SHOULD_BE_CANCELLED
            );
            // this order has to be canceled
            $order = Mage::registry('current_invoice')->getOrder();
            $order = Mage::getModel('sales/order')->load($order->getId());

                $order->cancel()->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('billsafe')->__('The order has been cancelled.')
                );

        }
    }

    /**
     * called after the invoice data are changed
     *
     * @param $observer
     */
    public function updatePaymentInstructionInvoice($observer)
    {
        $order = $observer->getEvent()->getInvoice()->getOrder();
        if (!is_null($order)) {
            Mage::helper('billsafe/order')->getPaymentInstruction($order);
        }
    }

    /**
     * called after the creditmemo data are changed
     *
     * @param $observer
     */
    public function updatePaymentInstructionCreditmemo($observer)
    {
        $order = $observer->getEvent()->getCreditmemo()->getOrder();
        if (!is_null($order)) {
            Mage::helper('billsafe/order')->getPaymentInstruction($order);
        }
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    public function cleanUpSession($observer)
    {
        if (Mage::getSingleton('customer/session')->getData(
            'authorize_failed'
        )
        ) {
            Mage::getSingleton('customer/session')->unsetData('authorize_failed');
            Mage::getSingleton('customer/session')->unsetData(
                'billsafe_billingAddrHash'
            );
        }
        if (Mage::getSingleton('checkout/session')->getData('customer_dob')) {
            Mage::getSingleton('checkout/session')->unsetData('customer_dob');
        }
    }

    /**
     * puts the customer dob for billsafe payment into the checkout session
     * (adding it to the quote->setCustomerDob did not work properly)
     *
     * @param Varien_Event_Observer $observer
     */
    public function importPaymentData(Varien_Event_Observer $observer)
    {
        $dob   = $observer->getEvent()->getInput()->getDob();
        Mage::getSingleton('checkout/session')->setData('customer_dob', $dob);
    }

    /**
    * Adds the billsafe payment and legal notes to the invoice notes
    */
    public function firegentoPdfInvoiceInsertNotice(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $result = $observer->getEvent()->getResult();
        if ($order->getPayment()->getMethod() == Netresearch_Billsafe_Model_Payment::CODE) {
            $notes = $result->getNotes();
            $infoText = Mage::helper('payment')->getInfoBlock($order->getPayment())->toMrgPdf() . "\n";
            $notes = array_merge($notes, explode("\n", $infoText));
            $result->setNotes($notes);
        }
    }

    /**
     * Download settlement file initiated by cron.
     *
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function getSettlementFiles(Mage_Cron_Model_Schedule $schedule)
    {
        /* @var $config Netresearch_Billsafe_Model_Config */
        $config = Mage::getModel('billsafe/config');

        $messages = array();
        foreach (Mage::app()->getStores() as $store) {
            if ($config->isSettlementDownloadEnabled($store)) {
                /* @var $client Netresearch_Billsafe_Model_Client */
                $client = Mage::getModel('billsafe/client');
                $filename = $client->getSettlement($store);
                $messages[]= sprintf(
                    "%s was successfully downloaded for store %s.",
                    $filename,
                    $store->getCode()
                );
            }
        }
        if (count($messages)) {
            $schedule->setMessages(implode("\n", $messages));
        }
    }
}
