<?php
class Anyday_Paymentmethod_Model_Observer extends Varien_Event_Observer
{
    /**
     * @var Anyday_Paymentmethod_Helper_Anyday|null
     */
    private $helperAnyday;

    public function __construct()
    {
        $this->helperAnyday = Mage::helper('adpaymentmethod/anyday');
    }

    /**
     * @param $observer
     * @throws Mage_Core_Exception
     */
    public function cancelAnydaypayment($observer)
    {
        $payment = $observer->getData('payment');
        if ($this->helperAnyday->isPaymentAnyday($payment) && $this->isCanceled()) {
            $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection');
            $listTransaction = $transactions->addOrderIdFilter($observer->getData('payment')->getOrder()->getId());
            /**
             * @var $oneTransaction Mage_Sales_Model_Order_Payment_Transaction
             */
            foreach ($listTransaction as $oneTransaction) {
                if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
                    $this->helperAnyday->cancelTransaction($oneTransaction->getTxnId(), $payment->getOrder()->getStore()->getId());
                    break;
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function isCanceled()
    {
        if (Mage::registry('order_refund')) {
            return false;
        }

        return true;
    }

    public function addButtonsToOrder($observer)
    {
        $block = $observer->getBlock();
        /**
         * @var $order Mage_Sales_Model_Order
         */
        $order = Mage::registry('current_order');

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View &&
            $this->helperAnyday->isPaymentAnyday($order->getPayment()) && !$order->isCanceled()) {

//            $block->updateButton('order_invoice',null,[
//                'label' => Mage::helper('adpaymentmethod')->__('Invoice/Capture')
//            ]);

            if ($this->viewCaptureButton($order) and false) {
                $message = Mage::helper('adpaymentmethod')->__('Are you sure you want to do this?');
                $block->addButton('capture_order', [
                    'label' => Mage::helper('adpaymentmethod')->__('Capture Order'),
                    'onclick' => "confirmSetLocation('{$message}', '{$block->getUrl('adminhtml/order/capture')}')",
                    'class' => 'go'
                ]);
            }

            if ($this->viewRefundButton($order)) {
                $message = Mage::helper('adpaymentmethod')->__("Please confirm full refund. If you would like a partial or multiple refund, contact ANYDAY support.");
                $block->addButton('refund_order', [
                    'label' => Mage::helper('adpaymentmethod')->__('Refund Order'),
                    'onclick' => "confirmSetLocation('{$message}', '{$block->getUrl('adminhtml/order/refund')}')",
                    'class' => 'go'
                ]);

                $block->removeButton('order_cancel');
            }

            if (!$this->viewInvoiceButton($order) && !$order->isCanceled()) {
                $block->removeButton('order_invoice');
                $this->getSession()->addError('Please authorize payment before invoice');
            }
        }
    }

    /**
     * Retrieve adminhtml session model object
     *
     * @return Mage_Adminhtml_Model_Session
     */
    private function getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    private function viewInvoiceButton(Mage_Sales_Model_Order $order)
    {
        /** @var $transactions Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection */
        $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection');
        $listTransaction = $transactions->addOrderIdFilter($order->getId());
        /**
         * @var $oneTransaction Mage_Sales_Model_Order_Payment_Transaction
         */
        foreach ($listTransaction as $oneTransaction) {
            if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    private function viewCaptureButton(Mage_Sales_Model_Order $order)
    {
        /** @var $transactions Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection */
        $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection');
        $listTransaction = $transactions->addOrderIdFilter($order->getId());
        /**
         * @var $oneTransaction Mage_Sales_Model_Order_Payment_Transaction
         */
        foreach ($listTransaction as $oneTransaction) {
            if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE) {
                return false;
            }
        }
        return true;
    }

    private function viewRefundButton(Mage_Sales_Model_Order $order)
    {
        return false;
        if ($order->getCreditmemosCollection()->count()) {
            return false;
        }
        /** @var $transactions Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection */
        $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection');
        $listTransaction = $transactions->addOrderIdFilter($order->getId());
        /**
         * @var $oneTransaction Mage_Sales_Model_Order_Payment_Transaction
         */
        foreach ($listTransaction as $oneTransaction) {
            if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE) {
                $fullInvoice = 0.0;
                foreach($order->getInvoiceCollection() as $oneInvoce) {
                    /** @var $oneInvoice Mage_Sales_Model_Order_Invoice */
                    $fullInvoice += (float)$oneInvoce->getGrandTotal();
                }
                if ((float)$order->getGrandTotal() == (float)$fullInvoice) {
                    return true;
                }
            }
        }
        return false;
    }

    public function captureInvoiceAnydaypayment($observer)
    {
        return;
        /**
         * @var Mage_Sales_Model_Order_Invoice $invoice
         */
        $invoice = $observer->getdata('invoice');
        $orderId = $invoice->getOrder()->getId();
        $payment = $invoice->getOrder()->getPayment();
        if ($orderId && $payment->getMethod() == Anyday_Paymentmethod_Model_Config::PAYMENT_CODE) {
            /** @var $currentOrder Mage_Sales_Model_Order */
            $currentOrder = $invoice->getOrder();
            $payment = $currentOrder->getPayment();
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
                        $this->helperAnyday->captureTransaction(
                            $transactionId,
                            (double)$currentOrder->getGrandTotal(),
                            $currentOrder
                        );
                        $oneTransaction->delete();
                        $currentOrder->save();
                        $payment->setTransactionId($transactionId);
                        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
                            null,
                            false,
                            'Anyday transaction');
                        $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                            array('Context'=>'Payment',
                                'Amount'=>$currentOrder->getGrandTotal(),
                                'Status'=>0,
                            ));
                        $transaction->setIsTransactionClosed(true);
                        $transaction->save();

                        $currentOrder->save();
                        break;
                    }
                }
            } else {
                Mage::throwException(Mage::helper('payment')->__('Order not Payment Anyday.'));
            }
        }
    }

    public function creditmemoRegister($observer)
    {
        //$args = array('creditmemo' => $creditmemo, 'request' => $this->getRequest());
        $creditMemo = $observer->getData('creditmemo');
        $request = $observer->getData('request');
        $i = 1;
        //Mage::throwException(Mage::helper('payment')->__('Order not Payment Anyday.'));
    }
}