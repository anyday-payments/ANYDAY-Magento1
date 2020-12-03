<?php
class Anyday_Paymentmethod_Helper_Curl
{
    /**
     * @var Mage_Core_Helper_Data|null
     */
    private $helperCore;

    public function __construct()
    {
        $this->helperCore = Mage::helper('core');
    }

    /**
     * @param string $urlString
     * @param bool $authorization
     * @param array $data
     * @return mixed
     */
    public function sendPostRequest($urlString, $authorization = false, $data = [], $storeId = null)
    {
        $ch = curl_init($urlString);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headerCurl = ['Content-Type:application/json'];
        if ($authorization) {
            $authorization = "Authorization: Bearer " . Mage::helper('adpaymentmethod/settings')->getApiKey($storeId);
            $headerCurl[] = $authorization;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerCurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $this->helperCore->jsonDecode($result);
    }

}