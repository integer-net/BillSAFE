<?php
/**
 * Netresearch Billsafe Setup skript
 */
$this->startSetup();

$this->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('billsafe_invoice')}
(
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `pdf_data` TEXT NOT NULL,
    `instruction_recipient` VARCHAR(100) NOT NULL,
    `instruction_bank_code` VARCHAR(8) NOT NULL,
    `instruction_account_number` VARCHAR(10) NOT NULL,
    `instruction_bank_name` VARCHAR(100) NOT NULL,
    `instruction_bic` VARCHAR(11) NOT NULL,
    `instruction_iban` VARCHAR(34) NOT NULL,
    `instruction_reference` VARCHAR(50) NOT NULL,
    `instruction_amount` FLOAT NOT NULL,
    `instruction_currency_code` VARCHAR(3) NOT NULL,
    `instruction_note` VARCHAR(200) NOT NULL
) ENGINE=InnoDB CHARSET=utf8;

");

$this->endSetup();

