<?php
class Anyday_Paymentmethod_Helper_Anyday extends Mage_Core_Helper_Abstract
{
    const URL_CANCEL    = '/v1/payments/{id}/cancel';
    const URL_CAPTURE   = '/v1/payments/{id}/capture';
    const URL_REFUND    = '/v1/payments/{id}/refund';
    /**
     * @var Anyday_Paymentmethod_Helper_Curl|null
     */
    private $helperCurl;

    /**
     * @var Anyday_Paymentmethod_Helper_Settings|null
     */
    private $helperSettings;

    /**
     * @var Anyday_Paymentmethod_Helper_Data|null
     */
    private $helper;

    public function __construct()
    {
        $this->helperCurl       = Mage::helper('adpaymentmethod/curl');
        $this->helperSettings   = Mage::helper('adpaymentmethod/settings');
        $this->helper           = Mage::helper('adpaymentmethod');
    }

    /**
     * @param $idTransaction
     * @param $storeId
     * @throws Mage_Core_Exception
     */
    public function cancelTransaction($idTransaction, $storeId = null)
    {
        $returnCancel = $this->helperCurl->sendPostRequest(
            Anyday_Paymentmethod_Helper_Settings::URL_ANYDAY_PAYMENT .
            str_replace('{id}', $idTransaction, self::URL_CANCEL),
            true,
            [],
            $storeId
        );
        if (isset($returnCancel['errorMessage'])) {
            Mage::throwException($this->__($returnCancel['errorMessage']));
        }
    }

    /**
     * @param string $idTransaction
     * @param double $amount
     * @param Mage_Sales_Model_Order $order
     * @param null $storeId
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function captureTransaction($idTransaction,$amount,Mage_Sales_Model_Order $order,$storeId = null)
    {
        $returnCancel = $this->helperCurl->sendPostRequest(
            Anyday_Paymentmethod_Helper_Settings::URL_ANYDAY_PAYMENT .
            str_replace('{id}', $idTransaction, self::URL_CAPTURE),
            true,
            [
                'amount' => $amount
            ],
            $storeId
        );
        if ($returnCancel == '') {
            Mage::throwException($this->__('ANYDAY payment Error'));
            $this->printOrderError($order,$this->__('Anyday empty result'));
        }
        if (isset($returnCancel['errorMessage'])) {
            Mage::throwException($this->__(implode(',',$returnCancel['errorMessage'])));
            $this->printOrderError($order,$this->__(implode(',',$returnCancel['errorMessage'])));
        }

        return $returnCancel;
    }

    /**
     * @param string $idTransaction
     * @param double $amount
     * @param $storeId
     * @return mixed
     */
    public function refundTransaction($idTransaction, $amount, $storeId) {
        $returnRefund = $this->helperCurl->sendPostRequest(
            Anyday_Paymentmethod_Helper_Settings::URL_ANYDAY_PAYMENT .
            str_replace('{id}', $idTransaction, self::URL_REFUND),
            true,
            [
                'amount' => $amount
            ],
            $storeId
        );

        return $returnRefund;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     */
    public function isPaymentAnyday(Mage_Sales_Model_Order_Payment $payment)
    {
        if ($payment->getMethod() == Anyday_Paymentmethod_Model_Config::PAYMENT_CODE) {
            return true;
        }

        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $invoiceId
     * @throws Mage_Core_Exception
     */
    public function saveTransaction(Mage_Sales_Model_Order $order, $invoiceId = '')
    {
        if ($anydayTransaction = Mage::registry('anyday_transaction')) {
            $payment = $order->getPayment();
            $invoiceId = $invoiceId ? '/' . $invoiceId : '';
            $payment->setTransactionId($anydayTransaction['transaction'] . $invoiceId);
            $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
                null,
                false,
                'Anyday transaction');
            $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                array('Context' => 'Payment',
                    'Amount' => $anydayTransaction['amount'],
                    'Status' => 0,
                ));
            $transaction->setIsTransactionClosed(true);
            $transaction->save();
            $order->save();

            $this->addStatusAfterInvoice($order);
            $order->save();
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @throws Exception
     */
    public function addStatusAfterPayment(Mage_Sales_Model_Order $order)
    {
        $status = $this->helperSettings->getAfterPaymentStatus($order->getStore()->getId());
        if ($status) {
            if ($status->getData('status') == Mage_Sales_Model_Order::STATE_COMPLETE &&
                $status->getData('state') == Mage_Sales_Model_Order::STATE_COMPLETE){
                $order->addStatusHistoryComment(
                    $this->__('Create Order with ANYDAY Payment'),
                    Mage_Sales_Model_Order::STATE_COMPLETE
                );
            } else {
                $order->setState($status->getData('state'), $status->getData('status'));
            }
            $order->save();
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @throws Exception
     */
    public function addStatusAfterInvoice(Mage_Sales_Model_Order $order)
    {
        $status = $this->helperSettings->getAfterInvoiceStatus($order->getStore()->getId());
        if ($this->verifyChangeStatus($order) && $status) {
            if ($status->getData('status') == Mage_Sales_Model_Order::STATE_COMPLETE &&
                $status->getData('state') == Mage_Sales_Model_Order::STATE_COMPLETE){
                $order->addStatusHistoryComment(
                    $this->__('Create Invoice and Capture with ANYDAY Payment'),
                    Mage_Sales_Model_Order::STATE_COMPLETE
                );
            } else {
                $order->setState($status->getData('state'), $status->getData('status'));
            }
            $order->save();
        }
    }

    /**
     * Validate Change status
     *
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    private function verifyChangeStatus(Mage_Sales_Model_Order $order)
    {
        if ($order->canInvoice()) {
            return false;
        }
        return true;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function isOrderInvoiceOnline(Mage_Sales_Model_Order $order)
    {
        if ($this->isPaymentAnyday($order->getPayment())) {
            foreach ($order->getInvoiceCollection() as $oneInvoice) {
                /** @var $oneInvoice Mage_Sales_Model_Order_Invoice */
                if (!$oneInvoice->isCanceled()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function isOnlineInvoice(Mage_Sales_Model_Order $order)
    {
        if ($this->isPaymentAnyday($order->getPayment())) {
            $orderIsonline = $order->getData(Anyday_Paymentmethod_Helper_Settings::NAME_ISONLINE_ORDER_FIELD);
            if (is_null($orderIsonline)) {
                return true;
            }
            return (bool)$orderIsonline;
        }
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function isOfflineInvoice(Mage_Sales_Model_Order $order)
    {
        if ($this->isPaymentAnyday($order->getPayment())) {
            $orderIsonline = $order->getData(Anyday_Paymentmethod_Helper_Settings::NAME_ISONLINE_ORDER_FIELD);
            if (is_null($orderIsonline)) {
                return true;
            }
            return !(bool)$orderIsonline;
        }
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function isFirstInvoice(Mage_Sales_Model_Order $order)
    {
        if ($this->isPaymentAnyday($order->getPayment())) {
            if (!$order->getInvoiceCollection()->getSize()) {
                return true;
            }
            foreach ($order->getInvoiceCollection() as $oneInvoice) {
                /** @var $oneInvoice Mage_Sales_Model_Order_Invoice */
                if ($oneInvoice->isCanceled()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string
     */
    public function printOrderError(Mage_Sales_Model_Order $order, $message)
    {
        $this->helper->printLogFile(date('D M j G:i:s T Y'). ' order number ' . $order->getId() . ' ' . $message);
    }
}