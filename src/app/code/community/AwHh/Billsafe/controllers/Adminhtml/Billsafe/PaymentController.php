<?php

class AwHh_Billsafe_Adminhtml_Billsafe_PaymentController extends Mage_Adminhtml_Controller_Action {

    public function gettokenAction() {
        $quote = Mage::getSingleton("adminhtml/session_quote")->getQuote();
        $client = Mage::getModel("billsafe/client");

        $client->prepareOrderByQuote($quote, true);
        $token = $client->getResponseToken();
        
        Mage::getSingleton("core/session")->setBillsafeBackendToken($token);

        echo "Here is a div id'ed \"BillSAFE_Token\" which has the value: <div id='BillSAFE_Token'>$token</div>";
    }

    public function verifyAction() {
        $token = $this->_request->getParam('token', null);
        $client = Mage::getModel('billsafe/client')->getTransactionResult($token);

        if (!$client->isValid() || !$client->isAccepted()) {
            $this->cancelLastOrderAndRestoreCart();

            $msg = $client->getResponse()->declineReason->buyerMessage;

            if ($msg == null) {
                $msg = $client->getResponse()->errorList->message;
            }

            Mage::getSingleton('core/session')->addError(Mage::helper('billsafe')->__($msg));
        } else {
            echo '<html><head><script type="text/javascript">top.editForm.submitBillsafe()</script></head></html>';
        }
    }

}