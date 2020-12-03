<?php
class Anyday_Paymentmethod_Adminhtml_TokenController extends Mage_Adminhtml_Controller_Action
{
    public function createAction()
    {
        $params['username'] = Mage::app()->getRequest()->getParam('email');
        $params['password'] = Mage::app()->getRequest()->getParam('pass');
        $store = (int)Mage::app()->getRequest()->getParam('store');
        $website = (int)Mage::app()->getRequest()->getParam('website');
        $sendParam = [
            "grant_type" => "password",
            //"username" => "luke@anyday.io",
            //"password" => "l@X3#uPN!*RO",
            "userType" => "merchant"
        ];

        $sendParam = array_merge($sendParam, $params);

        $url = 'https://my.anyday.io/api/v1/authentication/login';
        $data_string = json_encode($sendParam);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if (isset($result->errors)) {
            $returnArr['code'] = 'error';
            $returnArr['result'] = implode(' ', $result->errors);
        } else {
            $returnArr['code'] = 'ok';
            $returnArr['token'] = $result->access_token;
            $url = 'https://my.anyday.io/api/v1/webshop/mine';
            $ch = curl_init($url);
            $authorization = "Authorization: Bearer " . (string)$result->access_token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', $authorization]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            if (isset($result->data[0]->apiKey) && isset($result->data[0]->testAPIKey)) {
                $returnArr['live'] = $result->data[0]->apiKey;
                $returnArr['sandbox'] = $result->data[0]->testAPIKey;
                $scope = 'default';
                $scopeId = $store;
                if ($website != -1) {
                    $scope = 'websites';
                    $scopeId = $website;
                }
                Mage::getConfig()->saveConfig(
                    'payment/adpaymentmethod/tokensandbox',
                    $result->data[0]->testAPIKey,
                    $scope,
                    $scopeId
                );
                Mage::getConfig()->saveConfig(
                    'payment/adpaymentmethod/tokenlive',
                    $result->data[0]->apiKey,
                    $scope,
                    $scopeId
                );
                Mage::getConfig()->saveConfig(
                    'adpayment_options/section_one/price_tag_tiken',
                    $result->data[0]->priceTagToken,
                    $scope,
                    $scopeId
                );
            } else {
                $returnArr['code'] = 'error';
                $returnArr['result'] = implode(' ', $result->errors);
            }
        }
        echo (Mage::helper('core')->jsonEncode($returnArr));
    }
}