<?php

class Anyday_Payment_Model_Observer
{
    public function addPriceTag($observer)
    {
        $block = $observer->getEvent()->getBlock();

        if ($block instanceof Mage_Catalog_Block_Product_Price &&
            (Mage::app()->getFrontController()->getAction()->getFullActionName() == 'catalog_category_view' ||
            Mage::app()->getFrontController()->getAction()->getFullActionName() == 'catalogsearch_result_index')
        ) {
            $html = $observer->getEvent()->getTransport()->getHtml();
            $priceTagBlock = $block->getLayout()->createBlock('core/template')
                ->setProduct($block->getProduct())
                ->setTemplate('adpayment/catalog/product/price_tag.phtml');
            $observer->getEvent()->getTransport()->setHtml($html . $priceTagBlock->toHtml());
        } elseif ($block instanceof Mage_Tax_Block_Checkout_Grandtotal) {
            $html = $observer->getEvent()->getTransport()->getHtml();
            $priceTagBlock = $block->getLayout()->createBlock('core/template')
                ->setTemplate('adpayment/tax/checkout/grandtotal.phtml');
            $observer->getEvent()->getTransport()->setHtml($html . $priceTagBlock->toHtml());
        }
    }
}
