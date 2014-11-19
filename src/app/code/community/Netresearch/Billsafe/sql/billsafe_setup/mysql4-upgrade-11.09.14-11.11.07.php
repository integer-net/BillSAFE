<?php
/**
 * Netresearch Billsafe Update
 */
$this->startSetup();
$this->setConfigData('payment_services/paymentfee/active', 1);
$this->setConfigData('payment_services/paymentfee/payment_methods', 'billsafe');
$this->endSetup();
