<?php
class Anyday_Paymentmethod_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action
{
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

    public function captureAction()
    {
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        if ($orderId) {
            /** @var $currentOrder Mage_Sales_Model_Order */
            $currentOrder = Mage::getModel('sales/order')->load($orderId);
            if ($this->helperAnyday->isPaymentAnyday($currentOrder->getPayment())) {
                /** @var $transactions Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection */
                $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection');
                $listTransaction = $transactions->addOrderIdFilter($currentOrder->getId())
                                    ->addAttributeToSelect('*');
                /**
                 * @var $oneTransaction Mage_Sales_Model_Order_Payment_Transaction
                 */
                foreach ($listTransaction as $oneTransaction) {
                    if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
                        $transactionId = $oneTransaction->getTxnId();
                        $payment = $currentOrder->getPayment();
                        $this->helperAnyday->captureTransaction(
                            $transactionId,
                            (double)$currentOrder->getGrandTotal(),
                            $currentOrder,
                            $currentOrder->getStore()->getId()
                        );
                        $oneTransaction->delete();
                        $currentOrder->save();
                        $payment->setTransactionId($transactionId);
                        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
                            null,
                            false,
                            'ANYDAY transaction');
                        $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                            array('Context'=>'Payment',
                                'Amount'=>$currentOrder->getGrandTotal(),
                                'Status'=>0,
                            ));
                        $transaction->setIsTransactionClosed(true);
                        $transaction->save();

                        if (!$currentOrder->getInvoiceCollection()->count()) {
                            $invoice = Mage::getModel('sales/service_order', $currentOrder)->prepareInvoice();
                            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                            $invoice->register();
                            $invoice->getOrder()->setIsInProcess(true);
                            $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder());
                            $transactionSave->save();
                        }

                        $currentOrder->save();
                        break;
                    }
                }
                $this->_getSession()->addSuccess($this->__('Order Captured'));
            } else {
                Mage::throwException(Mage::helper('payment')->__('Order not Payment ANYDAY.'));
            }
        }
        $this->_redirect('*/sales_order/view', array(
            'order_id'=>$orderId
        ));
    }

    public function refundAction()
    {
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        if ($orderId) {
            /** @var $currentOrder Mage_Sales_Model_Order */
            $currentOrder = Mage::getModel('sales/order')->load($orderId);
            if ($this->helperAnyday->isPaymentAnyday($currentOrder->getPayment())) {
                /** @var $transactions Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection */
                $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection');
                $listTransaction = $transactions->addOrderIdFilter($currentOrder->getId());
                /**
                 * @var $oneTransaction Mage_Sales_Model_Order_Payment_Transaction
                 */
                foreach ($listTransaction as $oneTransaction) {
                    if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
                        $transactionId = $oneTransaction->getTxnId();
                        $result = $this->helperAnyday->refundTransaction(
                            $transactionId,
                            (double)$currentOrder->getGrandTotal(),
                            $currentOrder->getStore()->getId()
                        );
                        if ($result == '') {
                            $this->_getSession()->addError('ANYDAY Error');
                        } else {
                            if (isset($result['errorMessage'])) {
                                $this->_getSession()->addError($this->__($result['errorMessage']));
                            } else {
                                foreach ($currentOrder->getInvoiceCollection() as $oneInvoce) {
                                    /** @var $oneInvoice Mage_Sales_Model_Order_Invoice */
                                    $oneInvoce->cancel();
                                    $oneInvoce->save();
                                }
                                $this->_getSession()->addSuccess($this->__('Order Refunded'));
                                Mage::register('order_refund', true);
                                $currentOrder->cancel()->save();
                            }
                        }
                        break;
                    }
                }
            }
        }
        $this->_redirect('*/sales_order/view', array(
            'order_id'=>$orderId
        ));
    }
}