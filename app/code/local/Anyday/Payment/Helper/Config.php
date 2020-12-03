<?php
class Anyday_Payment_Helper_Config extends Mage_Core_Helper_Abstract
{
    const ENABLE_TRACKER            = 'adpayment_options/section_one/enable_price_tag';
    const PRICE_TAG_TOKEN           = 'adpayment_options/section_one/price_tag_tiken';
    const CUSTOM_CSS                = 'adpayment_options/custom_options/inline_css';
    const ENABLE_TRACKER_CATEGORY   = 'adpayment_options/category_page/enable_price_tag';
    const ENABLE_TRACKER_PRODUCT    = 'adpayment_options/product_page/enable_price_tag';
    const ENABLE_TRACKER_CART       = 'adpayment_options/cart_page/enable_price_tag';
    const ENABLE_TRACKER_CHECKOUT   = 'adpayment_options/checkout_page/enable_price_tag';
    const ENABLE_FULL_PRICE_INTO_TAG= 'adpayment_options/section_one/enable_full_price_tag';
    const CUSTOM_CSS_CATEGORY       = 'adpayment_options/category_page/inline_css';
    const CUSTOM_CSS_PRODUCT        = 'adpayment_options/product_page/inline_css';
    const CUSTOM_CSS_CART           = 'adpayment_options/cart_page/inline_css';
    const CUSTOM_CSS_CHECKOUT       = 'adpayment_options/checkout_page/inline_css';

    /**
     * @return bool
     */
    public function isEnableTracker()
    {
        if (Mage::getStoreConfig(self::ENABLE_TRACKER) == '1') {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getTagToken()
    {
        return Mage::getStoreConfig(self::PRICE_TAG_TOKEN);
    }

    /**
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getLinkJsFile()
    {
        $code = Mage::app()->getLocale()->getLocaleCode();
        if (strpos($code,'da') !== false) {
            return 'https://my.anyday.io/webshopPriceTag/anyday-price-tag-da-es2015.js';
        }

        return 'https://my.anyday.io/webshopPriceTag/anyday-price-tag-en-es2015.js';
    }

    /**
     * @return mixed
     */
    public function getInlineCss()
    {
        return Mage::getStoreConfig(self::CUSTOM_CSS);
    }

    /**
     * @return string|null
     */
    public function getInlineCssCategory()
    {
        return Mage::getStoreConfig(self::CUSTOM_CSS_CATEGORY);
    }

    /**
     * @return string|null
     */
    public function getInlineCssProduct()
    {
        return Mage::getStoreConfig(self::CUSTOM_CSS_PRODUCT);
    }

    /**
     * @return string|null
     */
    public function getInlineCssCart()
    {
        return Mage::getStoreConfig(self::CUSTOM_CSS_CART);
    }

    /**
     * @return string|null
     */
    public function getInlineCssCheckout()
    {
        return Mage::getStoreConfig(self::CUSTOM_CSS_CHECKOUT);
    }

    /**
     * @return bool
     */
    public function getEnableFullPriceIntoTag()
    {
        if (Mage::getStoreConfig(self::ENABLE_FULL_PRICE_INTO_TAG) == '1') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEnableTrackInList()
    {
        if (Mage::getStoreConfig(self::ENABLE_TRACKER_CATEGORY) == '1') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEnableTrackInProduct()
    {
        if (Mage::getStoreConfig(self::ENABLE_TRACKER_PRODUCT) == '1') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEnableTrackInCart()
    {
        if (Mage::getStoreConfig(self::ENABLE_TRACKER_CART) == '1') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEnableTrackInCheckout()
    {
        if (Mage::getStoreConfig(self::ENABLE_TRACKER_CHECKOUT) == '1') {
            return true;
        }

        return false;
    }
}