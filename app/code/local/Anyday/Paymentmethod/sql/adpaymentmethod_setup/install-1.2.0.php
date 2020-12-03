<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->addAttribute(
    'order',
    Anyday_Paymentmethod_Helper_Settings::NAME_ISONLINE_ORDER_FIELD,
    [
        'type'      => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        'nullable'  => false,
        'default'   => false
    ]
);
$installer->endSetup();