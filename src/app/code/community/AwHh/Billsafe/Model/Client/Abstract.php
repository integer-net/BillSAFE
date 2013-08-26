<?php

class AwHh_Billsafe_Model_Client_Abstract extends Zend_Soap_Client {

    /**
     * Perform a SOAP call and log request end response to logfile
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        $helper = Mage::helper('billsafe');

        try {
            //$this->setLocation($this->getWsdl());
            $response = parent::__call($name, $arguments);
        } catch (Exception $e) {
            $msg = $helper->__('BillSAFE server is temporarily not available, please try again later -> Error: '. $e->getMessage());
            Mage::throwException($msg);
        }

        $separator = '===================================================================';
        $helper->log($helper->__("Calling API method \"%s\" with params: \n\n%s\n\nResult:\n-------\n\n%s\n%s", $name, Zend_Json::encode($arguments), Zend_Json::encode($response), $separator
                ));
        return $response;
    }

}
