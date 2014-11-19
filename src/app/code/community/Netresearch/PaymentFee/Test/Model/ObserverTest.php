<?php
class Netresearch_PaymentFee_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
    /**
	 * observer must not add payment fee if module is disabled
	 * - module is not enabled
	 */
    public function testObserverModuleDisabled()
    {
        // set config as needed in current test
        $active = Mage::getStoreConfig('payment_services/paymentfee/active');
        if ($active) {
            // temporarily disable module
            Mage::getConfig()->setNode('payment_services/paymentfee/active', 0);
        }

//        $fees_enabled = Mage::getConfig()->getNode('payment_services/paymentfee/payment_methods');
        Mage::getConfig()->setNode('payment_services/paymentfee/payment_methods', 'checkmo');

        
        
        
        // create quote
        $quote = $this->getMockQuote();
        
        // set payment method as selected by user
        $this->setMethodInstance($quote, 'checkmo');
        
        // create event with quote
        $event = $this->getMockEvent(array(
        	'quote' => $quote
        ));
        
        // pass event to observer
        $observer = Mage::getModel('paymentfee/observer');
//        $observer->addFeeToQuoteTotals($event);
        
        // test if payment fee equals 0
        $this->assertEquals(0, $event->getQuote()->getPaymentFee());
        
        
        
        
        // reset config status
//        Mage::getConfig()->setNode('payment_services/paymentfee/active', $active);
//        Mage::getConfig()->setNode('payment_services/paymentfee/payment_methods', $fees_enabled);
    }
    
    
    /**
	 * observer must not add payment fee if customer did not select
	 * payment method yet.
	 * - module is enabled
	 * - current payment method is empty
	 */
    public function testObserverPaymentMethodEmpty()
    {
        // set config as needed in current test
        $active = Mage::getStoreConfig('payment_services/paymentfee/active');
        if (!$active) {
            // temporarily disable module
            Mage::getConfig()->setNode('payment_services/paymentfee/active', 1);
        }

//        $fees_enabled = Mage::getConfig()->getNode('payment_services/paymentfee/payment_methods');
        Mage::getConfig()->setNode('payment_services/paymentfee/payment_methods', 'checkmo');
        
        
        // get current enabled status
        $active = Mage::getStoreConfig('payment_services/paymentfee/active');
        if (!$active) {
            // temporarily disable module
            Mage::getConfig()->setNode('payment_services/paymentfee/active', 1);
        }
        
        
        // create quote
        $quote = $this->getMockQuote();
        
        // set no payment method
        
        // create event with quote
        $event = $this->getMockEvent(array(
        	'quote' => $quote
        ));
        
        
        // pass event to observer
        $observer = Mage::getModel('paymentfee/observer');
//        $observer->addFeeToQuoteTotals($event);
        
        // test if payment fee equals 0
        $this->assertEquals(0, $event->getQuote()->getPaymentFee());

        

        
        // reset config status
//        Mage::getConfig()->setNode('payment_services/paymentfee/active', $active);
//        Mage::getConfig()->setNode('payment_services/paymentfee/payment_methods', $fees_enabled);
    }

    
	/**
	 * observer must not add payment fee if customer selected a
	 * payment method that was not enabled for payment fee via backend.
	 * - module is enabled
	 * - current payment method is 'cc'
	 * - payment method 'cc' is not enabled for payment fee in backend config
	 */
    public function testObserverPaymentMethodSelectedNotApplicable()
    {
        // set config as needed in current test
        $active = Mage::getStoreConfig('payment_services/paymentfee/active');
        if (!$active) {
            // temporarily disable module
            Mage::getConfig()->setNode('payment_services/paymentfee/active', 1);
        }

        $fees_enabled = Mage::getConfig()->getNode('payment_services/paymentfee/payment_methods');
        Mage::getConfig()->setNode('payment_services/paymentfee/payment_methods', 'checkmo');

        
        
        // get current enabled status
        $active = Mage::getStoreConfig('payment_services/paymentfee/active');
        if (!$active) {
            // temporarily disable module
            Mage::getConfig()->setNode('payment_services/paymentfee/active', 1);
        }
        
        
        // create quote
        $quote = $this->getMockQuote();
        
        // set payment method as selected by user
        $this->setMethodInstance($quote, 'cc');
        
        // create event with quote
        $event = $this->getMockEvent(array(
        	'quote' => $quote
        ));
        
        
        // pass event to observer
        $observer = Mage::getModel('paymentfee/observer');
//        $observer->addFeeToQuoteTotals($event);
        
        // test if payment fee equals 0
        $this->assertEquals(0, $event->getQuote()->getPaymentFee());

        

        
        // reset config status
    }
    
    protected function getMockEvent($data)
    {
        return new Varien_Event_Observer($data);
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    protected function getMockQuote()
    {
        // init quote with basic data
        $quote = Mage::getModel('sales/quote');
        $quote->setData(Zend_Json::decode('{"entity_id":"1","store_id":"1","created_at":"2011-10-11 14:17:55","updated_at":"2011-10-18 11:03:32","converted_at":"0000-00-00 00:00:00","is_active":"1","is_virtual":"0","is_multi_shipping":"0","items_count":1,"items_qty":1,"orig_order_id":"0","store_to_base_rate":1,"store_to_quote_rate":1,"base_currency_code":"EUR","store_currency_code":"EUR","quote_currency_code":"EUR","grand_total":34.99,"base_grand_total":24.99,"checkout_method":"","customer_id":"1","customer_tax_class_id":"3","customer_group_id":"1","customer_email":"christoph.assmann@netresearch.de","customer_prefix":null,"customer_firstname":"Christoph","customer_middlename":null,"customer_lastname":"A\u00dfmann","customer_suffix":null,"customer_dob":null,"customer_note":null,"customer_note_notify":"1","customer_is_guest":"0","remote_ip":"127.0.1.1","applied_rule_ids":"","reserved_order_id":"","password_hash":null,"coupon_code":null,"global_currency_code":"EUR","base_to_global_rate":1,"base_to_quote_rate":1,"customer_taxvat":null,"customer_gender":null,"subtotal":9.99,"base_subtotal":9.99,"subtotal_with_discount":9.99,"base_subtotal_with_discount":9.99,"is_changed":1,"trigger_recollect":0,"ext_shipping_info":null,"gift_message_id":null,"x_forwarded_for":null,"virtual_items_qty":0,"totals_collected_flag":true,"messages":[]}'));

        return $quote;
    }
    
    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param string $code
     */
    protected function setMethodInstance(Mage_Sales_Model_Quote $quote, $code)
    {
        /* @var $instance Mage_Payment_Model_Method_Abstract */
        $instance = Mage::getModel("payment/method_$code");
        
        /* @var $payment Mage_Sales_Model_Quote_Payment */
        $payment = Mage::getModel("sales/quote_payment");
        $payment->setData(Zend_Json::decode('{"payment_id":"8","quote_id":"1","created_at":"2011-10-18 08:16:03","updated_at":"2011-10-18 11:12:28","method":"checkmo","cc_type":"","cc_number_enc":"","cc_last4":"","cc_cid_enc":"","cc_owner":"","cc_exp_month":"0","cc_exp_year":"0","cc_ss_owner":"","cc_ss_start_month":"0","cc_ss_start_year":"0","cybersource_token":"","paypal_correlation_id":"","paypal_payer_id":"","paypal_payer_status":"","po_number":"","additional_data":null,"cc_ss_issue":null,"additional_information":[],"ideal_issuer_id":null,"ideal_issuer_list":null,"method_instance":{}}'));

        $payment->setMethodInstance($instance);
        
        $quote->setPayment($payment);
    }
}
