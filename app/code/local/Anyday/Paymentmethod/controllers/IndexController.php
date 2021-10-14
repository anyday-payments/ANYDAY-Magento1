<?php

class Anyday_Paymentmethod_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Order instance
     */
    protected $_order;

    /**
     * @var Anyday_Paymentmethod_Helper_Anyday|null
     */
    private $helperAnyday;

    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = array()
    ) {
        parent::__construct($request, $response, $invokeArgs);

        $this->helperAnyday = Mage::helper('adpaymentmethod/anyday');
    }

    /**
     *  Get order
     *
     * @return  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
        }
        return $this->_order;
    }

    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setAdOptins('');
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
            Mage::helper('paypal/checkout')->restoreQuote();
        }
        $this->destroySessionData();
        $this->_redirect('checkout/cart');
    }

    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        if ($session->getLastRealOrderId()) {
            /**
             * @var $order Mage_Sales_Model_Order
             */
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $payment = $order->getPayment();
                if ($adOptions = $session->getAdOptins()) {
                    $payment->setTransactionId($adOptions[Anyday_Paymentmethod_Model_Paymentmethod::NAME_TRANSACTION]);
                    $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER,
                        null,
                        false,
                        'Anyday transaction');
                    $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                        array('Context'=>'Payment',
                            'Amount'=>$order->getGrandTotal(),
                            'Status'=>0,
                        ));
                    $transaction->setIsTransactionClosed(false);
                    $transaction->save();
                    $order->addStatusHistoryComment(Mage::helper('adpaymentmethod')->__('Amount %s has been successfully authorized.', $order->getGrandTotal()));
                    $order->save();
                }
                $this->helperAnyday->addStatusAfterPayment($order);
                $order->sendNewOrderEmail();
            }
            $this->destroySessionData();
        }
        $session->setQuoteId(Mage::app()->getRequest()->getParam('quote'));
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }

    private function destroySessionData()
    {
        Mage::getSingleton('checkout/session')->setAdOptins('');
    }
}
