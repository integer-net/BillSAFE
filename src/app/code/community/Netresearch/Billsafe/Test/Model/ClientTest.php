<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ClientTest
 *
 * @author sebastian
 */
class Netresearch_Billsafe_Test_Model_ClientTest extends EcomDev_PHPUnit_Test_Case
{

    public function testPrevalidateOrder()
    {
        $params = array(
            "ack" => 'OK',
            "invoice" => array(
                "isAvailable" => true,
                "message" => ""
            ),
            "hirePurchase" => array(
                "isAvailable" => false,
                "message" => "test message",
            ),
        );

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('prevalidateOrder'));
        $baseClientMock->expects($this->any())
            ->method('prevalidateOrder')
            ->will($this->returnValue($params));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $result = Mage::getModel('billsafe/client')->prevalidateOrder($params);
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey("ack", $result);
        $this->assertArrayHasKey("invoice", $result);
        $this->assertArrayHasKey("hirePurchase", $result);
        $this->assertTrue(is_array($result['invoice']));
        $this->assertTrue(is_array($result['hirePurchase']));
        $this->assertEquals($params["ack"], $result["ack"]);
        $this->assertEquals($params["invoice"], $result["invoice"]);
        $this->assertArrayHasKey("isAvailable", $result["invoice"]);
        $this->assertArrayHasKey("message", $result["invoice"]);
        $this->assertArrayHasKey("isAvailable", $result["hirePurchase"]);
        $this->assertArrayHasKey("message", $result["hirePurchase"]);
        $this->assertEquals($params["hirePurchase"]["isAvailable"], $result["hirePurchase"]["isAvailable"]);
        $this->assertEquals($params["hirePurchase"]["message"], $result["hirePurchase"]["message"]);
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testUpdateArticleListExceptionCase()
    {
        $order = Mage::getModel("sales/order")->load(11);
        $order->setGrandTotal(200);
        $order->setTotalRefunded(100);
        $order->setTotalCanceled(50);
        $order->setTaxAmount(20);
        $order->setTaxRefunded(10);
        $order->setTaxCanceled(10);
        $context = Netresearch_Billsafe_Model_Client::TYPE_VO;

        $response = new stdClass();
        $response->ack = 'NOK';

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('updateArticleList'));
        $baseClientMock->expects($this->any())
            ->method('updateArticleList')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('buildArticleList'));
        $orderHelperMock->expects($this->any())
           ->method('buildArticleList')
           ->will($this->returnValue(array()));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $this->setExpectedException('Mage_Exception');
        Mage::getModel('billsafe/client')->updateArticleList($order, $context);
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testUpdateArticleListTypeRF()
    {
        $order = Mage::getModel("sales/order")->load(12);
        $order->setGrandTotal(200);
        $order->setTotalRefunded(100);
        $order->setTotalCanceled(50);
        $order->setTaxAmount(20);
        $order->setTaxRefunded(10);
        $order->setTaxCanceled(10);
        $context = Netresearch_Billsafe_Model_Client::TYPE_RF;
        $creditMemo = Mage::getModel('sales/order')->setOrder($order);
        Mage::register('current_creditmemo', $creditMemo);

        $response = new stdClass();
        $response->ack = 'OK';

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('updateArticleList'));
        $baseClientMock->expects($this->any())
            ->method('updateArticleList')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('buildArticleList'));
        $orderHelperMock->expects($this->any())
           ->method('buildArticleList')
           ->will($this->returnValue(array('amount' => 10, 'tax_amount' => '11')));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $client = Mage::getModel('billsafe/client');
        $result = $client->updateArticleList($order, $context);
        Mage::unregister('current_creditmemo');
        $this->assertEquals($client, $result);
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testVoid()
    {
        $order = Mage::getModel("sales/order")->load(12);
        $order->setGrandTotal(200);
        $order->setTotalRefunded(100);
        $order->setTotalCanceled(50);
        $order->setTaxAmount(20);
        $order->setTaxRefunded(10);
        $order->setTaxCanceled(10);
        $context = Netresearch_Billsafe_Model_Client::TYPE_VO;
        $response = new stdClass();
        $response->ack = 'OK';

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('updateArticleList'));
        $baseClientMock->expects($this->any())
            ->method('updateArticleList')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $response = new stdClass();
        $response->ack = 'ERR';
        $response->errorList = new stdClass();
        $response->errorList->code = 302;

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $orderHelperMock = $this->getHelperMock('billsafe/order', array('buildArticleList'));
        $orderHelperMock->expects($this->any())
            ->method('buildArticleList')
            ->will($this->returnValue(array('amount' => 10, 'tax_amount' => '11')));
        $this->replaceByMock('helper', 'billsafe/order', $orderHelperMock);

        $client = Mage::getModel('billsafe/client');

        $this->assertEquals($client->updateArticleList($order, $context), $client->void($order));
    }


    public function testGetAgreedHandlingCharges()
    {
        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);


        $response = new stdClass();
        $response->agreedCharge = null;
        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getAgreedHandlingCharges'));
        $baseClientMock->expects($this->any())
            ->method('getAgreedHandlingCharges')
            ->will($this->returnValue($response));

        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);
        $client = Mage::getModel('billsafe/client');
        $this->assertEquals(null, $client->getAgreedHandlingCharges());


        $response = new stdClass();
        $response->agreedCharge = 200;
        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getAgreedHandlingCharges'));
        $baseClientMock->expects($this->any())
            ->method('getAgreedHandlingCharges')
            ->will($this->returnValue($response));

        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);
        $client = Mage::getModel('billsafe/client');
        $this->assertEquals(200, $client->getAgreedHandlingCharges());


        $response = new stdClass();
        $response->agreedCharge = new stdClass();
        $response->agreedCharge->foo = 300;
        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getAgreedHandlingCharges'));
        $baseClientMock->expects($this->any())
            ->method('getAgreedHandlingCharges')
            ->will($this->returnValue($response));

        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);
        $client = Mage::getModel('billsafe/client');
        $this->assertEquals(300, $client->getAgreedHandlingCharges('foo'));
    }


    public function testGetPaymentInstruction()
    {
        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);


        $response = new stdClass();
        $response->ack = 'ERR';
        $response->errorList = new stdClass();
        $response->errorList->code = 302;

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getPaymentInstruction'));
        $baseClientMock->expects($this->any())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $client = Mage::getModel('billsafe/client');
        $order = new Varien_Object();
        $order->setIncrementId(1);
        $this->assertEquals(0, count($client->getPaymentInstruction($order)));


        $response = new stdClass();
        $response->ack = 'OK';
        $response->instruction = 'instruction';

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getPaymentInstruction'));
        $baseClientMock->expects($this->any())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $client = Mage::getModel('billsafe/client');
        $order = new Varien_Object();
        $order->setIncrementId(1);
        $this->assertEquals('instruction', $client->getPaymentInstruction($order));


        $response = new stdClass();
        $response->ack = 'ERR';
        $response->errorList = new stdClass();
        $response->errorList->code = 303;

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getPaymentInstruction'));
        $baseClientMock->expects($this->any())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $client = Mage::getModel('billsafe/client');
        $order = new Varien_Object();
        $order->setIncrementId(1);
        try {
            $client->getPaymentInstruction($order);
        } catch (Exception $e) {
            $this->assertEquals('Unable to retrieve billsafe payment instructions', $e->getMessage());
        }
    }


    public function testGetConfig()
    {
        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $config = Mage::getModel('billsafe/client')->getConfig();
        $this->assertInstanceOf('Netresearch_Billsafe_Model_Config', $config);
    }

    public function testSetConfig()
    {
        $configMock = $this->getModelMock('billsafe/config', array('getMerchantId'));
        $configMock->expects($this->any())
            ->method('getMerchantId')
            ->will($this->returnValue('12345'));

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $model = Mage::getModel('billsafe/client');
        $this->assertInstanceOf('Netresearch_Billsafe_Model_Client', $model->setConfig($configMock));
        $this->assertEquals($configMock->getMerchantId(), $model->getConfig()->getMerchantId());
    }

    public function testGetOptions()
    {
        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getOptions', 'getConnectionTimeout'));
        $baseClientMock->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue(array('merchantId' => 1, 'license' => 'public')));
        $baseClientMock->expects($this->any())
            ->method('getConnectionTimeout')
            ->will($this->returnValue(30));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $result = Mage::getModel('billsafe/client')->getOptions();
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, $result['merchantId']);
        $this->assertEquals('public', $result['license']);
        $this->assertEquals(30, $result['connection_timeout']);
    }

    public function testGetTransactionResult()
    {
        $response = new stdClass();
        $response->ack = 'OK';
        $response->status = 'ACCEPTED';

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getTransactionResult'));
        $baseClientMock->expects($this->any())
            ->method('getTransactionResult')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $client = Mage::getModel('billsafe/client');
        try {
            $this->assertTrue($client->isValid());
        } catch (Exception $e) {
            $this->assertEquals('Response is null, no request done, yet', $e->getMessage());
        } try {
            $this->assertTrue($client->isAccepted());
        } catch (Exception $e) {
            $this->assertEquals('Response is null, no request done, yet', $e->getMessage());
        }
        $wsResponse = $client->getTransactionResult('abc')->getResponse();
        $this->assertEquals($response, $wsResponse);
        $this->assertTrue($client->isValid());
        $this->assertTrue($client->isAccepted());
        $this->assertEquals('', $client->getResponseErrorMessage());
        $this->assertTrue(is_null($client->getResponseToken()));
        $this->assertTrue(is_null($client->getResponseTransactionId()));

    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testPrepareOrder()
    {
        $response = new stdClass();
        $response->ack = 'OK';
        $response->status = 'ACCEPTED';
        $order = Mage::getModel('sales/order')->load(13);

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('prepareOrder'));
        $baseClientMock->expects($this->any())
            ->method('prepareOrder')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $helperMock = $this->getHelperMock('billsafe/order', array('getPreparedOrderParams'));
        $helperMock->expects($this->any())
            ->method('getPreparedOrderParams')
            ->will($this->returnValue(array()));
        $this->replaceByMock('helper', 'billsafe/order', $helperMock);

        $client = Mage::getModel('billsafe/client');
        $this->assertEquals($response, $client->prepareOrder($order)->getResponse());
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testReportShipment()
    {
        $response = new stdClass();
        $response->ack = 'OK';
        $response->status = 'ACCEPTED';
        $shipment = Mage::getModel('sales/order_shipment')->load(1);

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('reportShipment'));
        $baseClientMock->expects($this->any())
            ->method('reportShipment')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $helperMock = $this->getHelperMock('billsafe/order', array('buildArticleList'));
        $helperMock->expects($this->any())
            ->method('buildArticleList')
            ->will($this->returnValue(array()));
        $this->replaceByMock('helper', 'billsafe/order', $helperMock);

        $client = Mage::getModel('billsafe/client');
        $this->assertEquals($client, $client->reportShipment($shipment));
    }

    /**
     * @loadFixture ../../../var/fixtures/orders.yaml
     */
    public function testReportShipmentThrowsException()
    {
        $shipment = Mage::getModel('sales/order_shipment')->load(1);
        $response = new stdClass();
        $response->ack = 'NOK';
        $response->status = 'NOT ACCEPTED';

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('reportShipment'));
        $baseClientMock->expects($this->any())
            ->method('reportShipment')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $helperMock = $this->getHelperMock('billsafe/order', array('buildArticleList'));
        $helperMock->expects($this->any())
            ->method('buildArticleList')
            ->will($this->returnValue(array()));
        $this->replaceByMock('helper', 'billsafe/order', $helperMock);

        $client = Mage::getModel('billsafe/client');
        try {
            $client->reportShipment($shipment);
        } catch (Exception $e) {
            $this->assertEquals('Unable to register billsafe shipment', $e->getMessage());
        }
    }

    public function testProcessOrderSuccess()
    {
        $response = new stdClass();
        $response->ack = 'OK';
        $response->status = 'ACCEPTED';
        $response->transactionId = '0815';

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('processOrder'));
        $baseClientMock->expects($this->any())
            ->method('processOrder')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $client = Mage::getModel('billsafe/client');
        $result = $client->processOrder(array());
        $this->assertTrue($result['success']);
        $this->assertEquals('0815', $result['transactionId']);
    }

    public function testProcessOrderDeclined()
    {
        $response = new stdClass();
        $response->ack = 'OK';
        $response->status = 'DECLINED';
        $response->declineReason = new stdClass();
        $response->declineReason->buyerMessage = 'Du kommst hier net rein';

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('processOrder'));
        $baseClientMock->expects($this->any())
            ->method('processOrder')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $client = Mage::getModel('billsafe/client');
        $result = $client->processOrder(array());
        $this->assertFalse($result['success']);
        $this->assertEquals('Du kommst hier net rein', $result['buyerMessage']);
    }

    public function testProcessOrderFailed()
    {
        $response = new stdClass();
        $response->ack = 'NOK';
        $response->errorList = new stdClass();
        $response->errorList->message = 'Request failed';

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('processOrder'));
        $baseClientMock->expects($this->any())
            ->method('processOrder')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue(Mage::app()->getStore()->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $client = Mage::getModel('billsafe/client');
        $result = $client->processOrder(array());
        $this->assertFalse($result['success']);
        $this->assertEquals('Request failed', $result['message']);
    }

    /**
     * @test
     */
    public function pauseTransaction()
    {
        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('pauseTransaction'));
        $baseClientMock->expects($this->any())
            ->method('pauseTransaction')
            ->will($this->returnValue(null));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $clientMock = $this->getModelMock('billsafe/client', array('getDefaultParams', 'isValid'));
        $clientMock->expects($this->any())
            ->method('getDefaultParams')
            ->will($this->returnValue(array()));
        $clientMock->expects($this->any())
            ->method('isValid')
            ->will($this->onConsecutiveCalls(true, false));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $order         = Mage::getModel('sales/order');
        $transactionId = '0815';
        $orderNumber   = '808';
        $pause         = 7;

        /* @var $soapClient Netresearch_Billsafe_Model_Client */
        $soapClient = Mage::getModel('billsafe/client');
        $this->assertTrue($soapClient->pauseTransaction(
            $order, $transactionId, $orderNumber, $pause
        ));
        $this->assertFalse($soapClient->pauseTransaction(
            $order, $transactionId, $orderNumber, $pause
        ));
    }

    public function testGetResponseErrorFromStdObject()
    {
        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $message = 'message';
        $errorList = new stdClass();
        $errorList->message = $message;
        $fakeResponse = new stdClass();
        $fakeResponse->errorList = $errorList;

        /** @var Netresearch_Billsafe_Model_Client $client */
        $client = $this->getModelMock('billsafe/client', array('getResponse'));
        $client->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($fakeResponse));

        $this->assertEquals($message, $client->getResponseErrorMessage());
    }

    public function testGetResponseErrorFromArray()
    {
        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $message = 'message';
        $errorList = new stdClass();
        $errorList->message = $message;
        $fakeResponse = new stdClass();
        $fakeResponse->errorList = array($errorList);

        /** @var Netresearch_Billsafe_Model_Client $client */
        $client = $this->getModelMock('billsafe/client', array('getResponse'));
        $client->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($fakeResponse));

        $this->assertEquals($message, $client->getResponseErrorMessage());
    }

    public function testGetResponseErrorIsNull()
    {
        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $fakeResponse = new stdClass();

        /* @var $client Netresearch_Billsafe_Model_Client */
        $client = $this->getModelMock('billsafe/client', array('getResponse'));
        $client->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($fakeResponse));

        $this->assertEquals(null, $client->getResponseErrorMessage());
    }

    /**
     * @test
     */
    public function getSettlementApiFails()
    {
        $defaultStore = Mage::app()->getStore(Mage_Core_Model_Store::DEFAULT_CODE);

        $errorMessage = 'Error foo.';
        $response  = new stdClass();
        $errorList = new stdClass();
        $errorList->message = $errorMessage;

        $response->ack       = 'NOK';
        $response->errorList = $errorList;

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
            ->method('getStoreIdfromQuote')
            ->will($this->returnValue($defaultStore->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getSettlement'));
        $baseClientMock->expects($this->any())
            ->method('getSettlement')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $this->setExpectedException('Mage_Core_Exception', $errorMessage);
        $client = Mage::getModel('billsafe/client');
        $client->getSettlement($defaultStore);
    }

    /**
     * @test
     */
    public function getSettlementIoFails()
    {
        $defaultStore = Mage::app()->getStore(Mage_Core_Model_Store::DEFAULT_CODE);

        $errorMessage = "Foo isn't writeable";
        $data = "foo;bar";

        $response = new stdClass();
        $response->ack              = 'OK';
        $response->settlementNumber = "TEST_SETTLEMENT";
        $response->settlementDate   = "2014-04-03";
        $response->payoutAmount     = "1500.00";
        $response->data             = base64_encode($data);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock
            ->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue($defaultStore->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getSettlement'));
        $baseClientMock->expects($this->any())
            ->method('getSettlement')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $clientMock = $this->getModelMock('billsafe/client', array('isValid'));
        $clientMock
            ->expects($this->any())
           ->method('isValid')
           ->will($this->returnValue(true));
        $this->replaceByMock('model', 'billsafe/client', $clientMock);

        $ioMock = $this->getModelMock('billsafe/io_settlement', array('writeSettlementFile'));
        $ioMock
            ->expects($this->any())
            ->method('writeSettlementFile')
            ->will($this->throwException(new Varien_Io_Exception($errorMessage)));
        $this->replaceByMock('model', 'billsafe/io_settlement', $ioMock);

        $this->setExpectedException('Varien_Io_Exception', $errorMessage);

        /* @var $client Netresearch_Billsafe_Model_Client */
        $client = Mage::getModel('billsafe/client');
        $client->getSettlement($defaultStore);
    }

    /**
     * @test
     */
    public function getSettlement()
    {
        $defaultStore = Mage::app()->getStore(Mage_Core_Model_Store::DEFAULT_CODE);

        $data = "foo;bar";

        $response = new stdClass();
        $response->ack              = 'OK';
        $response->settlementNumber = "TEST_SETTLEMENT";
        $response->settlementDate   = "2014-04-03";
        $response->payoutAmount     = "1500.00";
        $response->data             = base64_encode($data);

        $dataHelperMock = $this->getHelperMock('billsafe/data', array('getStoreIdfromQuote'));
        $dataHelperMock->expects($this->any())
           ->method('getStoreIdfromQuote')
           ->will($this->returnValue($defaultStore->getId()));
        $this->replaceByMock('helper', 'billsafe/data', $dataHelperMock);

        $baseClientMock = $this->getModelMock('billsafe/client_base', array('getSettlement'));
        $baseClientMock->expects($this->any())
            ->method('getSettlement')
            ->will($this->returnValue($response));
        $this->replaceByMock('model', 'billsafe/client_base', $baseClientMock);

        $varienIoMock = $this->getMock('Varien_Io_File', array('createDestinationDir', 'write'));
        $varienIoMock->expects($this->any())
             ->method('createDestinationDir')
             ->will($this->returnValue(true));
        $varienIoMock->expects($this->any())
             ->method('write')
             ->will($this->returnValue(strlen($data)));
        $ioMock = $this->getModelMock('billsafe/io_settlement', array('getFileHandler'));
        $ioMock
            ->expects($this->any())
            ->method('getFileHandler')
            ->will($this->returnValue($varienIoMock));
        $this->replaceByMock('model', 'billsafe/io_settlement', $ioMock);

        /* @var $client Netresearch_Billsafe_Model_Client */
        $client = Mage::getModel('billsafe/client');
        $basename = sprintf(
            "%s-%s.csv",
            $response->settlementNumber,
            str_replace('-', '', $response->settlementDate)
        );

        $this->assertEquals($basename, $client->getSettlement($defaultStore));
    }
}
