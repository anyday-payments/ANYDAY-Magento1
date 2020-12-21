<?php
class Anyday_Payment_Block_Settings extends Mage_Core_Block_Template
{
    /**
     * @return string
     */
    public function getSettingsPayment()
    {
        $retArray = [];
        $retArray['script']         = Mage::helper('adpayment/config')->getLinkJsFile();
        $retArray['tagcode']        = Mage::helper('adpayment/config')->getTagToken();
        return Mage::helper('core')->jsonEncode($retArray);
    }
}