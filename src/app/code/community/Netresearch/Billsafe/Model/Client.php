<?php
class Netresearch_Billsafe_Model_Client
{
    const TYPE_PO = 'prepareOrder';
    const TYPE_RS = 'reportShipment';
    const TYPE_PI = 'paymentInstruction';
    const TYPE_RF = 'refund';
    const TYPE_VO = 'void';

    const MAX_FEE_KEY = 'charge';
    const MAX_AMOUNT_KEY = 'maxAmount';

    protected $_response = null;
    private $_config = null;
    protected $_agreedCharges = null;
    protected $_client = null;

    public function __construct()
    {
        $this->getClient();
        $this->_client->setWsdl(
            $this->getConfig()->getApiUrl(
                Mage::helper('billsafe/data')->getStoreIdfromQuote()
            )
        );

        $timeout = Mage::getModel('billsafe/config')->getBillsafeTimeout(
            Mage::helper('billsafe/data')->getStoreIdfromQuote()
        );
        $this->_client->setConnectionTimeout((int)$timeout);
        ini_set("default_socket_timeout", $timeout);
    }

    public function getClient()
    {
        if (is_null($this->_client)) {
            $this->_client = Mage::getModel('billsafe/client_base');
        }

        return $this->_client;
    }

    /**
     * Returns options array
     *
     * @return array
     */
    public function getOptions()
    {
        $options = $this->getClient()->getOptions();
        $options['connection_timeout'] = $this->getClient()
            ->getConnectionTimeout();

        return $options;
    }

    /**
     * Returns response if set
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Returns true if request was valid
     *
     * @return boolean
     */
    public function isValid()
    {
        if (is_null($this->getResponse())) {
            throw new Mage_Exception('Response is null, no request done, yet');
        }

        return $this->getResponse()->ack == 'OK';
    }

    /**
     * Returns response error message if one is set
     *
     * @return string
     */
    public function getResponseErrorMessage()
    {
        $error = '';
        $errorMsgContainer = $this->getErrorMessageContainer();
            if (property_exists($errorMsgContainer, 'message')) {
                $error = $errorMsgContainer->message;
            }

        return $error;
    }

    /**
     * Returns response token if one is set
     *
     * @return string|null
     */
    public function getResponseToken()
    {
        $response = $this->getResponse();
        return isset($response->token) ? $response->token : null;
    }

    /**
     * Call prepareOrder API
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return Netresearch_Billsafe_Model_Request
     */
    public function prepareOrder($order)
    {
        $params = array_merge(
            Mage::helper('billsafe/order')->getPreparedOrderParams($order),
            $this->getDefaultParams($order)
        );

        $this->_response = $this->getClient()->prepareOrder($params);
        return $this;
    }

    /**
     * Returns array of default configuration
     *
     * @return array
     */
    protected function getDefaultParams($salesObject = null)
    {
        $storeId = null;
        if (!is_null($salesObject)
            && ($salesObject instanceof Mage_Sales_Model_Quote
                || $salesObject instanceof Mage_Sales_Model_Order)
        ) {
            $storeId = $salesObject->getStoreId();
        } else {
            $storeId = $this->getConfig()->getCurrentScopeId();
        }
        return array(
            'merchant'    => array(
                'id'      => $this->getConfig()->getMerchantId($storeId),
                'license' => $this->getConfig()->getMerchantLicense($storeId),
            ),
            'application' => array(
                'signature' => $this->getConfig()->getApplicationSignature(
                    $storeId
                ),
                'version'   => (string)Mage::getConfig()->getNode(
                )->modules->Netresearch_Billsafe->version,
            )
        );
    }


    public function getHelper()
    {
        return Mage::helper('billsafe/data');
    }

    /**
     * Return config object
     *
     * @return Netresearch_Billsafe_Model_Config
     */
    public function getConfig()
    {
        if (is_null($this->_config)) {
            $this->setConfig();
        }

        return $this->_config;
    }

    /**
     * Set configuration
     *
     * @param mixed $config
     *
     * @return Netresearch_Billsafe_Model_Client
     */
    public function setConfig($config = null)
    {
        if (is_null($config)) {
            $this->_config = Mage::getSingleton('billsafe/config');
        } else {
            $this->_config = $config;
        }

        return $this;
    }

    /**
     * Returns true if payment was accepted
     *
     * @return boolean
     */
    public function isAccepted()
    {
        if (is_null($this->getResponse())) {
            throw new Mage_Exception('Response is null, no request done, yet');
        }

        return $this->getResponse()->status == 'ACCEPTED';
    }

    public function getResponseTransactionId()
    {
        $response = $this->getResponse();
        return isset($response->transactionId) ? $response->transactionId
            : null;
    }

    /**
     * Call getTransactionResult API
     *
     * @param string $token
     *
     * @return Netresearch_Billsafe_Model_Client
     */
    public function getTransactionResult($token)
    {
        $this->getConfig()->setScopeId($this->getHelper()->getStoreIdfromQuote());
        $params = array_merge(
            array('token' => $token),
            $this->getDefaultParams()
        );

        $this->_response = $this->getClient()->getTransactionResult($params);
        return $this;
    }

    /**
     * Call getPaymentInstruction API
     *
     * @param Varien_Object $payment
     *
     * @return array
     */
    public function getPaymentInstruction($order)
    {
        $structured = $this->getClient()->getPaymentInstruction(
            array_merge(
                array(
                     'orderNumber' => $order->getIncrementId(),
                     'outputType'  => 'STRUCTURED',
                ),
                $this->getDefaultParams($order)
            )
        );

        if ('OK' != $structured->ack /*|| 'OK' != $pdf->ack*/) {
            $error = $structured->errorList;
            $error = is_array($error) ? $error[0] : $error;
            if (302 == $error->code) {
                /* Billsafe Error:
                 * Transaction has a wrong status for this method
                 * This occurs, if the amount at billsafe becomes 0, due to refunds or cancalations.
                 * In this case, payment can be treated as cancelled.
                 */
                return array();
            }
            throw new Mage_Exception('Unable to retrieve billsafe payment instructions');
        }

        return $structured->instruction;
    }

    /**
     * Call reportShipment API
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     *
     * @return Netresearch_Billsafe_Model_Client
     */
    public function reportShipment(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $params = array_merge(
            array(
                 'orderNumber' => $shipment->getOrder()->getIncrementId(),
                 'articleList' => Mage::helper('billsafe/order')
                     ->buildArticleList($shipment, self::TYPE_RS)),
            $this->getDefaultParams($shipment->getOrder())
        );

        Mage::helper('billsafe/data')->log(Zend_Json::encode($params));
        if ('OK' != $this->getClient()->reportShipment($params)->ack) {
            throw new Mage_Exception('Unable to register billsafe shipment');
        }

        return $this;
    }

    /**
     * Updates ArticleList regarding the modified order
     *
     * @param Mage_Sales_Model_Order $modifiedOrder
     *
     * @return void
     */
    public function updateArticleList(Mage_Sales_Model_Order $order, $context)
    {
        $amount = $this->getHelper()->format(
            $order->getGrandTotal() - $order->getTotalRefunded()
            - $order->getTotalCanceled()
        );
        $taxAmount = $order->getTaxAmount() - $order->getTaxRefunded()
            - $order->getTaxCanceled();

        if (self::TYPE_VO == $context) {
            $amount = $this->getHelper()->format($order->getTotalInvoiced());
        }

        $entity
            = self::TYPE_RF == $context ? Mage::registry('current_creditmemo')
            : $order;

        $articleList = Mage::helper('billsafe/order')->buildArticleList(
            $entity, $context
        );


        if (self::TYPE_RF == $context) {
            if (array_key_exists('amount', $articleList)) {
                $amount = $articleList['amount'];
                $taxAmount = $articleList['tax_amount'];
                unset($articleList['tax_amount']);
                unset($articleList['amount']);
            }
        }

        $params = array_merge(
            array(
                 'order'       => array(
                     'number'       => $order->getIncrementId(),
                     'amount'       => $this->getHelper()->format($amount),
                     'taxAmount'    => $this->getHelper()->format($taxAmount),
                     'currencyCode' => 'EUR',
                 ),
                 'orderNumber' => $order->getIncrementId(),
                 'articleList' => ($amount > 0) ? $articleList : array()),
            $this->getDefaultParams($order)
        );

        if ('OK' != $this->getClient()->updateArticleList($params)->ack) {
            throw new Mage_Exception('Unable to update article list at billsafe.');
        }
        return $this;
    }

    /**
     * Void
     *
     * @param Varien_Object $order
     *
     * @return void
     */
    public function void(Varien_Object $order)
    {
        return $this->updateArticleList($order, self::TYPE_VO);
    }


    /**
     * Fetches and returns handling charges (payment fee) configuration
     * from BillSAFE.
     *
     * @return array
     */
    public function getAgreedHandlingCharges($key = NULL)
    {
        if (is_null($this->_agreedCharges)) {
            $result = $this->getClient()->getAgreedHandlingCharges(
                $this->getDefaultParams()
            );
            if (isset($result->agreedCharge)) {
                $this->_agreedCharges = $result->agreedCharge;
            } else {
                return null;
            }
        }
        if ($key) {
            return $this->_agreedCharges->$key;
        }
        return $this->_agreedCharges;
    }

    public function getMaxAmount(){
        return $this->getAgreedHandlingCharges(self::MAX_AMOUNT_KEY);
    }

    public function getMaxFee(){
        return $this->getAgreedHandlingCharges(self::MAX_FEE_KEY);
    }

    /**
     * checks if order can be processed by BillSAFE
     *
     * @param $params
     *
     * @return mixed
     */
    public function prevalidateOrder($params, $quote = null)
    {
        $params = array_merge($this->getDefaultParams($quote), $params);
        return $this->getClient()->prevalidateOrder($params);
    }

    /**
     * checks if order passes the risk checks by BillSAFE
     *
     * @param $params
     *
     * @return mixed
     */
    public function processOrder($params, $quote = null)
    {
        $result = array();
        $params = array_merge($this->getDefaultParams($quote), $params);
        $wsResult = $this->getClient()->processOrder($params);
        if (strtolower(trim($wsResult->ack)) != 'ok') {
            if (property_exists($wsResult, 'errorList')) {
                $result['success'] = false;
                if (property_exists($wsResult->errorList, 'message')) {
                    $result['message'] = $wsResult->errorList->message;
                }
            }
        }
        if (strtolower(trim($wsResult->ack)) == 'ok'
            && strtolower(trim($wsResult->status)) == 'accepted'
        ) {
            $result['success'] = true;
            if (property_exists($wsResult, 'transactionId')) {
                $result['transactionId'] = $wsResult->transactionId;
            }
        }
        if (strtolower(trim($wsResult->ack)) == 'ok'
            && strtolower(trim($wsResult->status)) == 'declined'
        ) {
            $result['success'] = false;
            if (property_exists($wsResult, 'declineReason')
                && property_exists($wsResult->declineReason, 'buyerMessage')
            ) {
                $result['buyerMessage']
                    = $wsResult->declineReason->buyerMessage;
            }
        }
        Mage::helper('billsafe/data')->log(Zend_Json::encode($result));
        return $result;
    }

    /**
     * Init pause transaction call.
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $transactionId
     * @param string $orderNumber
     * @param int $pause
     * @return boolean
     */
    public function pauseTransaction(Mage_Sales_Model_Order $order,
        $transactionId, $orderNumber, $pause
    ) {
        $params                  = $this->getDefaultParams($order);
        $params['transactionId'] = $transactionId;
        $params['orderNumber']   = $orderNumber;
        $params['pause']         = $pause;

        $this->_response = $this
            ->getClient()
            ->pauseTransaction($params);
        return $this->isValid();
    }

    /**
     * Init report direct payment call.
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $transactionId
     * @param string $orderNumber
     * @param int $pause
     * @return boolean
     */
    public function reportDirectPayment(Mage_Sales_Model_Order $order,
        $transactionId, $orderNumber, $paymentAmount, $paymentDate
    ) {
        $params                  = $this->getDefaultParams($order);
        $params['transactionId'] = $transactionId;
        $params['orderNumber']   = $orderNumber;
        $params['amount']        = $paymentAmount;
        // BillSAFE only accepts EUR.
        // Once that changes, currency code might be retrieved via order object.
        $params['currencyCode']  = 'EUR';
        $params['date']          = $paymentDate;

        $this->_response = $this
            ->getClient()
            ->reportDirectPayment($params);
        return $this->isValid();
    }

    /**
     * retrieves the error message container from the response
     *
     * @return stdClass - the container of the error message, empty class if the error list couldn't be obtained
     */
    protected function getErrorMessageContainer()
    {
        $errorMessageContainer  = new stdClass();
        $response               = $this->getResponse();
        if (isset($response->errorList)) {
            $errorMessageContainer = $response->errorList;
            if (is_array($errorMessageContainer) && current($errorMessageContainer) instanceof stdClass) {
                $errorMessageContainer = current($errorMessageContainer);
            }
        }

        return $errorMessageContainer;
    }

    /**
     * Obtain settlement file from BillSAFE.
     *
     * @throws Mage_Core_Exception BillSAFE API exception
     * @return string File basename
     */
    public function getSettlement(Mage_Core_Model_Store $store)
    {
        $this->getConfig()->setScopeId($store->getId());

        $params = $this->getDefaultParams();
        $this->_response = $this->getClient()->getSettlement($params);

        if (!$this->isValid()) {
            Mage::throwException($this->getResponseErrorMessage());
        }

        $basename = sprintf(
            "%s-%s.csv",
            $this->getResponse()->settlementNumber,
            str_replace('-', '', $this->getResponse()->settlementDate)
        );
        $dirname = Mage::getBaseDir('var') . DS . 'billsafe' . DS . 'settlement' . DS . $store->getCode();

        /* @var $settlementIo Netresearch_Billsafe_Model_Io_Settlement */
        $settlementIo = Mage::getModel('billsafe/io_settlement');
        $settlementIo->writeSettlementFile($dirname, $basename, $this->getResponse()->data);

        return $basename;
    }
}
