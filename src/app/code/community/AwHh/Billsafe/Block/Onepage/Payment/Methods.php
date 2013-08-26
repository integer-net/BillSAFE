<?php
class AwHh_Billsafe_Block_Onepage_Payment_Methods extends Mage_Checkout_Block_Onepage_Payment_Methods
{
    /**
     * Return image tag for billsafe logo
     * 
     * @return string
     */
    public function getBillsafeLogo()
    {
        $url = Mage::getStoreConfig('payment/billsafe/billsafe_logo');
        return sprintf('<p><img src="%s" width="75px" /></p>', $url);
    }

    /**
     * Returns extended billsafe text
     * 
     * @return string
     */
    public function getBillsafeText()
    {
        $label = Mage::getStoreConfig('payment/billsafe/title');
        $helper = Mage::helper('billsafe');
        $text = $helper->__('Pay with our strong partner BillSAFE.');
        $feeText = "";
        if (Mage::getModel('billsafe/config')->isPaymentFeeEnabled()) {
            $fee = Mage::helper('paymentfee')->getUpdatedFeeProduct();
            if ($fee instanceof Mage_Catalog_Model_Product && $fee->getPrice() > 0) {
                $cd = $fee->getCheckoutDescription();
                $feeText = $helper->__(
                    'Using BillSAFE will cause an additional payment Fee of %s.',
                    $fee->getCheckoutDescription()
                );
            }
        }
        
        $msg = '<br/><p style="margin-left:20px;font-weight:normal">%s<br/>%s</p>';
        
        #return sprintf($msg, $label, $text, $feeText);
        return sprintf($msg, $text, $feeText);
    }
}
