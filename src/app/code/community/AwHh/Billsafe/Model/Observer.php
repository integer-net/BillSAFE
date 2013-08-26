<?php
class AwHh_Billsafe_Model_Observer
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
        if (Mage::registry('invoice') instanceof Mage_Sales_Model_Order_Invoice) {
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
        if (!($code == AwHh_Billsafe_Model_Payment::CODE || $code == AwHh_Billsafe_Model_Installment::CODE)) {
            return;
        }
        // only communicate to billsafe if no invoce is created so far
        if (!Mage::helper('billsafe')->isOrderAlreadyInvoiced($order)
            || Mage::helper('billsafe')->isDoShipment()
        ) {
            Mage::getModel('billsafe/client')->reportShipment($observer->getShipment());
        }
    }

    public function cancelOrderIfRequested($observer)
    {
        if (Mage::registry(AwHh_Billsafe_Model_Payment::REGISTRY_ORDER_SHOULD_BE_CANCELLED)) {
            Mage::unregister(AwHh_Billsafe_Model_Payment::REGISTRY_ORDER_SHOULD_BE_CANCELLED);
            // this order has to be canceled
            $order = Mage::registry('current_invoice')->getOrder();
            $order = Mage::getModel('sales/order')->load($order->getId());
            $order->cancel()->save();
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('billsafe')->__('The order has been cancelled.'));
        }
    }
    
    public function updatePaymentInstruction($observer)
    {
        $order = $observer->getOrder();
        $payment = $order->getPayment();
        $code = $payment->getMethodInstance()->getCode();
        if (!($code == AwHh_Billsafe_Model_Payment::CODE || $code == AwHh_Billsafe_Model_Installment::CODE)) {
            return;
        }
        
        $client = Mage::getSingleton('billsafe/client');
        $client->initBackendConfig($order);
        $data = $client->getPaymentInstruction($order);
        
        if ($data) {                        
            $payment->setAdditionalInformation('billsafe_status', AwHh_Billsafe_Model_Payment::BILLSAFE_STATUS_ACTIVE);
            $payment->setAdditionalInformation('Recipient', $data->recipient);
            $payment->setAdditionalInformation('BankCode', $data->bankCode);
            $payment->setAdditionalInformation('AccountNumber', $data->accountNumber);
            $payment->setAdditionalInformation('BankName', $data->bankName);
            $payment->setAdditionalInformation('Bic', $data->bic);
            $payment->setAdditionalInformation('Iban', $data->iban);
            $payment->setAdditionalInformation('Reference', $data->reference);
            $payment->setAdditionalInformation('Amount', $data->amount);
            $payment->setAdditionalInformation('CurrencyCode', $data->currencyCode);
            $payment->setAdditionalInformation('Note', $data->note);
            
            $transaction_id = Mage::getSingleton("core/session")->getBillsafeTransactionIdForPaymentInstructions();
            
            if($transaction_id != null) {
                $payment->setAdditionalInformation('BillsafeBTN', $transaction_id);
            }
        } else {
            $payment->setAdditionalInformation('BillsafeStatus', AwHh_Billsafe_Model_Payment::BILLSAFE_STATUS_CANCELLED);
        }
        $payment->setDataChanges(true);
        $payment->save();
    }
} 

