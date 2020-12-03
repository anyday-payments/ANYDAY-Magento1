<?php
class Anyday_Paymentmethod_Helper_Data extends Mage_Core_Helper_Abstract
{
    const NAME_LOG_FILE = 'anyday.log';

    public function printLogFile($message)
    {
        Mage::log($message, null, self::NAME_LOG_FILE, true);
    }
}