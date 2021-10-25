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
                    if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER) {
                        $transactionId = $oneTransaction->getTxnId();
                        $result = $this->helperAnyday->refundTransaction(
                            $transactionId,
                            (double)$currentOrder->getGrandTotal(),
                            $currentOrder->getStore()->getId()
                        );
                        if ($result == '') {
                            $this->_getSession()->addError('Anyday Error');
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