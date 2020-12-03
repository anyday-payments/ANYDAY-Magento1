<?php
class Anyday_Paymentmethod_Helper_Settings
{
    const URL_ANYDAY_PAYMENT        = 'https://my.anyday.io';
    const URL_MODULE_SANDBOX        = 'payment/adpaymentmethod/select_type_sandbox';
    const URL_AFTERPAYMENT_STATUS   = 'payment/adpaymentmethod/order_status_payment';
    const URL_AFTERINVOICE_STATUS   = 'payment/adpaymentmethod/order_status_invoice';
    const NAME_ISONLINE_ORDER_FIELD = 'anyday_isonline';

    /**
     * @var Mage_Sales_Model_Resource_Order_Status_Collection
     */
    private $resourceStatusCollection;

    public function __construct()
    {
        $this->resourceStatusCollection = Mage::getModel('sales/resource_order_status_collection');
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getApiKey($storeId = null)
    {
        if ((int)Mage::getStoreConfig(self::URL_MODULE_SANDBOX)) {
            return Mage::getStoreConfig('payment/adpaymentmethod/tokenlive', $storeId);
        } else {
            return Mage::getStoreConfig('payment/adpaymentmethod/tokensandbox', $storeId);
        }
    }

    /**
     * @param null $storeId
     * @return false|Mage_Core_Model_Abstract
     */
    public function getAfterPaymentStatus($storeId = null)
    {
        $status = Mage::getStoreConfig(self::URL_AFTERPAYMENT_STATUS, $storeId);
        if ($status) {
            $collectionStatuses = $this->resourceStatusCollection->addStatusFilter($status)
                ->joinStates();
            if ($collectionStatuses->count()) {
                return $collectionStatuses->getFirstItem();
            }
        }

        return false;
    }

    /**
     * @param null $storeId
     * @return false|Mage_Core_Model_Abstract
     */
    public function getAfterInvoiceStatus($storeId = null)
    {
        $status = Mage::getStoreConfig(self::URL_AFTERINVOICE_STATUS, $storeId);
        if ($status) {
            $collectionStatuses = $this->resourceStatusCollection->addStatusFilter($status)
                ->joinStates();
            if ($collectionStatuses->count()) {
                return $collectionStatuses->getFirstItem();
            }
        }

        return false;
    }
}