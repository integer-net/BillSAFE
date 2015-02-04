<?php

$path  = Netresearch_Billsafe_Model_Config::CONFIG_PATH_BILLSAFE_SETTLEMENT_CRON_EXPR;
$value = sprintf("0 %d * * 6", mt_rand(0, 23));
Mage::getModel('core/config_data')
    ->load($path, 'path')
    ->setValue($value)
    ->setPath($path)
    ->save();
