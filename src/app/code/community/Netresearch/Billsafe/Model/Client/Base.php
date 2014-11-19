<?php

class Netresearch_Billsafe_Model_Client_Base extends Zend_Soap_Client
{

    /**
     * Perform a SOAP call and log request end response to logfile
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $helper = Mage::helper('billsafe');

        try {
            // Check if WSDL is available before SoapClient raises an uncatchable PHP fatal error.
            if (get_headers($this->getWsdl())) {
                $response = parent::__call($name, $arguments);
            } else {
                Mage::throwException("WSDL is currently not available.");
            }
        } catch (Exception $e) {
            $helper->log($e->getMessage());
            $msg = $helper->__(
                'BillSAFE server is temporarily not available, please try again later'
            );
            Mage::throwException($msg);
        }

        $separator
            = '===================================================================';
        $helper->log(
            $helper->__(
                "Calling API method \"%s\" with params: \n\n%s\n\nResult:\n-------\n\n%s\n%s",
                $name, Zend_Json::encode($arguments),
                Zend_Json::encode($response), $separator
            )
        );
        return $response;
    }

    /**
     * set client connection timeout
     *
     * @param float $timeout
     */
    public function setConnectionTimeout($timeout)
    {
        if (!is_null($timeout)) {
            $this->_connection_timeout = $timeout;
        }
    }

    /**
     * return connection timeout
     *
     * @return string
     */
    public function getConnectionTimeout()
    {
        return $this->_connection_timeout;
    }
}
