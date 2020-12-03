<?php
abstract class Anyday_Payment_Block_Settings_Abstractsettings extends Mage_Core_Block_Template
{
    /**
     * @var Anyday_Payment_Helper_Config
     */
    protected $helperAnyday;

    public function __construct(
        array $args = array()
    ) {
        parent::__construct($args);
        $this->helperAnyday = Mage::helper('adpayment/config');
    }

    /**
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getCustomSettings()
    {
        $customSettings = [];
        $customSettings['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
        $customSettings['tagcode']  = Mage::helper('adpayment/config')->getTagToken();
        $customSettings['script']   = Mage::helper('adpayment/config')->getLinkJsFile();
        $customSettings['inlinecss']= $this->getInlineCss();
        $customSettings['fullprice']= $this->helperAnyday->getEnableFullPriceIntoTag();
        return $customSettings;
    }

    /**
     * @return bool
     */
    abstract public function isEnableBlock();

    /**
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    abstract public function getSettingsPayment();

    /**
     * @return string
     */
    abstract public function getInlineCss();

    /**
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getSettingsPaymentJson()
    {
        return Mage::helper('core')->jsonEncode($this->getSettingsPayment());
    }
}