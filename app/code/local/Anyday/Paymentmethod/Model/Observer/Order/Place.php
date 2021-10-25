<?php


class Anyday_Paymentmethod_Model_Observer_Order_Place
{
    /**
     * @var Mage_Core_Model_Abstract|null
     */
    private $sessionCheckout;

    /**
     * Anyday_Paymentmethod_Model_Observer_Order_Place constructor.
     */
    public function __construct()
    {
        $this->sessionCheckout  = Mage::getSingleton('checkout/session');
    }

    /**
     * @param $observer
     * @throws Mage_Core_Exception
     */
    public function start($observer)
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $observer->getData('payment');
        if ($this->isValidatePayment($payment)) {
            $grandTotal = (double)$payment->getOrder()->getGrandTotal();
            $sendParam = [
                'Amount' => $grandTotal,
                'Currency' => 'DKK',
                'OrderId' => (string)$payment->getOrder()->getIncrementId(),
                'SuccessRedirectUrl' => Mage::getUrl('adpaymentmethodfront/index/success',
                    array('quote'=>$payment->getOrder()->getQuoteId())
                ),
                'CancelPaymentRedirectUrl' => Mage::getUrl('adpaymentmethodfront/index/cancel',
                    array('quote'=>$payment->getOrder()->getQuoteId())
                ),
            ];

            $url = 'https://my.anyday.io/v1/payments';
            $data_string = json_encode($sendParam);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            $authorization = "Authorization: Bearer " . Mage::helper('adpaymentmethod/settings')->getApiKey();
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', $authorization]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            if (isset($result->errorCode) && !$result->errorCode && isset($result->transactionId)) {
                $this->sessionCheckout->setAdOptins([
                    Anyday_Paymentmethod_Model_Paymentmethod::NAME_URL          => 'https://my.anyday.io' . $result->authorizeUrl,
                    Anyday_Paymentmethod_Model_Paymentmethod::NAME_TRANSACTION  => $result->transactionId,
                    Anyday_Paymentmethod_Model_Paymentmethod::NAME_QUOTE        => (int)$payment->getOrder()->getQuoteId(),
                    Anyday_Paymentmethod_Model_Paymentmethod::NAME_AMOUNT       => $grandTotal
                ]);
            } else {
                $errorText = Mage::helper('payment')->__('Anyday payment Error.');
                if (isset($result->errorMessage)) {
                    $errorText = Mage::helper('payment')->__($result->errorMessage);
                }
                throw new Anyday_Paymentmethod_Exception($errorText);
            }
        }
    }

    /**
     * Validate payment
     *
     * @return bool
     * @throws Mage_Core_Exception
     *  @var Mage_Sales_Model_Order_Payment $payment
     */
    private function isValidatePayment(Mage_Sales_Model_Order_Payment $payment)
    {
        if ($payment->getMethodInstance()->getCode() == Anyday_Paymentmethod_Model_Config::PAYMENT_CODE) {
            if ($adOptions = $this->sessionCheckout->getAdOptins()) {
                $grandTotal = (double)$payment->getOrder()->getGrandTotal();
                if (isset($adOptions[Anyday_Paymentmethod_Model_Paymentmethod::NAME_QUOTE]) &&
                    $adOptions[Anyday_Paymentmethod_Model_Paymentmethod::NAME_QUOTE] == (int)$payment->getOrder()->getQuoteId() &&
                    isset($adOptions[Anyday_Paymentmethod_Model_Paymentmethod::NAME_AMOUNT]) &&
                    $adOptions[Anyday_Paymentmethod_Model_Paymentmethod::NAME_AMOUNT] == $grandTotal) {
                    return false;
                } else {
                    $this->sessionCheckout->setAdOptins('');
                    return true;
                }
            }
            return true;
        }
        return false;
    }
}