<?php
class Anyday_Payment_Block_Settings_Product extends Anyday_Payment_Block_Settings_Abstractsettings
{
    const POSITION_ADPAYMENT_TAG        = 'adpayment_options/product_page/select_type_tag_product_page';
    const TAG_POSITION_ADPAYMENT_TAG    = 'adpayment_options/product_page/element_tag';

    /**
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getSettingsPayment()
    {
        $retArray = [];
        $position = Mage::getStoreConfig(self::POSITION_ADPAYMENT_TAG);
        $tagPosition = Mage::getStoreConfig(self::TAG_POSITION_ADPAYMENT_TAG);
        if ((int)$position && $tagPosition && $this->isEnableBlock()) {
            $retArray['position']       = $position;
            $retArray['tagposition']    = $tagPosition;
        }
        if (Mage::registry('current_product')) {
            $retArray['priceproduct'] = Mage::registry('current_product')->getFinalPrice();
        }
        $retArray                   = array_merge($retArray, $this->getCustomSettings());
        return $retArray;
    }

    /**
     * @return bool
     */
    public function isEnableBlock()
    {
        return Mage::helper('adpayment/config')->isEnableTracker() &&
            Mage::helper('adpayment/config')->isEnableTrackInProduct();
    }

    /**
     * @return string|null
     */
    public function getInlineCss()
    {
        return $this->helperAnyday->getInlineCssProduct();
    }
}