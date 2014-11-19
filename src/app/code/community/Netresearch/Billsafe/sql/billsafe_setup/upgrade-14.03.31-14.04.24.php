<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('billsafe/direct_payment');
$table = $installer->getConnection()
    ->newTable($tableName)
    ->addColumn('payment_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true,
        'identity' => true,
    ), 'Payment Report ID')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
    ), 'Order ID')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATE, null, array(
        'nullable' => false,
    ), 'Direct Payment Capture Date')
    ->addColumn('base_report_amount', Varien_Db_Ddl_Table::TYPE_DECIMAL, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'precision' => 12,
        'scale'     => 4,
    ), 'Direct Payment Amount')
    ->addColumn('base_total_before', Varien_Db_Ddl_Table::TYPE_DECIMAL, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'precision' => 12,
        'scale'     => 4,
    ), 'Amount Before Direct Payment')
    ->addColumn('base_total_after', Varien_Db_Ddl_Table::TYPE_DECIMAL, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'precision' => 12,
        'scale'     => 4,
    ), 'Amount After Direct Payment')
    ->addForeignKey($installer->getFkName('billsafe/direct_payment', 'order_id', 'sales/order', 'entity_id'),
        'order_id', $installer->getTable('sales/order'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Direct Payment Report Table')
    ;
$installer->getConnection()->createTable($table);

$installer->endSetup();