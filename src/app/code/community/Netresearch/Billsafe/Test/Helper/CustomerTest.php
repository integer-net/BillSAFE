<?php
/**
 * Created by JetBrains PhpStorm.
 * User: michael
 * Date: 20.09.13
 * Time: 12:07
 * To change this template use File | Settings | File Templates.
 */

class Netresearch_Billsafe_Test_Helper_CustomerTest
    extends EcomDev_PHPUnit_Test_Case
{
    public function testGetCustomerDob()
    {
        $helper = Mage::helper('billsafe/customer');
        $this->assertEquals('1970-01-01', $helper->getCustomerDob(null, null));

        $customer = new Varien_Object();
        $this->assertEquals(
            '1970-01-01', $helper->getCustomerDob($customer, null)
        );

        $customer->setDob('1980-01-01');
        $this->assertEquals(
            '1980-01-01', $helper->getCustomerDob($customer, null)
        );

        $order = Mage::getModel('sales/order');
        $order->setCustomerDob('1990-01-01');

        $this->assertEquals(
            '1990-01-01', $helper->getCustomerDob(null, $order)
        );
        $this->assertEquals(
            '1990-01-01', $helper->getCustomerDob($customer, $order)
        );

    }

    public function testGetCustomerGender()
    {
        $address = new Varien_Object();
        $customer = new Varien_Object();
        $order = new Varien_Object();

        $defaultGender = 'Female';

        $configMock = $this->getModelMock('billsafe/config', array(
            'getDefaultCustomerGender',
        ));
        $configMock->expects($this->any())
            ->method('getDefaultCustomerGender')
            ->will($this->returnValue($defaultGender));
        $this->replaceByMock('model', 'billsafe/config', $configMock);

        $helperMock = $this->getHelperMock(
            'billsafe/customer', array('getGenderText')
        );
        $helperMock->expects($this->any())
            ->method('getGenderText')
            ->will($this->onConsecutiveCalls(
                null, null, null,
                null, null, 'MyCustomerGender',
                null, 'MyOrderGender', null,
                'MyAddressGender', null, null,
                null, null, null,
                null, null, null
            ));
        $this->replaceByMock('helper', 'billsafe/customer', $helperMock);

        // Fallback to default gender as configured (transformed)
        $gender = Mage::helper('billsafe/customer')->getCustomerGender(
            $address, $order, $customer
        );
        $this->assertEquals('f', $gender);

        // Gender as given in customer account (transformed)
        $gender = Mage::helper('billsafe/customer')->getCustomerGender(
            $address, $order, $customer
        );
        $this->assertEquals('m', $gender);

        // Gender as given in order (transformed)
        $gender = Mage::helper('billsafe/customer')->getCustomerGender(
            $address, $order, $customer
        );
        $this->assertEquals('m', $gender);

        // Gender as given in address (transformed)
        $gender = Mage::helper('billsafe/customer')->getCustomerGender(
            $address, $order, $customer
        );
        $this->assertEquals('m', $gender);

        // B2B orders also need the gender parameter
        $address->setCompany('Foo AG');
        $gender = Mage::helper('billsafe/customer')->getCustomerGender(
            $address, $order, $customer
        );
        $this->assertEquals('f', $gender);

        // Assume prefix was given in customer account
        $customer->setPrefix('Herr');
        $gender = Mage::helper('billsafe/customer')->getCustomerGender(
            $address, $order, $customer
        );
        $this->assertEquals('m', $gender);
    }

    public function testGetGenderText()
    {
        $helper = Mage::helper('billsafe/customer');
        $method = $this->runProtectedMethod('getGenderText', $helper);
        $address = new Varien_Object();
        $address->setData('gender', 'MR');
        $this->assertFalse($method->invoke($helper, $address, 'gender'));
    }

    protected function getMockedGenderTextHelper($returnValue)
    {
        $helperMock = $this->getHelperMock(
            'billsafe/customer', array('getGenderText')
        );
        $helperMock->expects($this->any())
            ->method('getGenderText')
            ->will($this->returnValue($returnValue));
        return $helperMock;
    }


    protected static function runProtectedMethod($name, $object)
    {
        $class = new ReflectionClass(get_class($object));
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

}