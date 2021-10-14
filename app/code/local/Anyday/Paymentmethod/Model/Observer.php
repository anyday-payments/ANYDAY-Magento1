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
                if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER) {
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

            if (!$this->viewInvoiceButton($order) && !$order->isCanceled()) {
                $block->removeButton('order_invoice');
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
            if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER) {
                return true;
            }
        }
        return false;
    }

    public function removeCapture($observer)
    {
        $block = $observer->getEvent()->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Invoice_Create_Items) {
            $html = $observer->getEvent()->getTransport()->getHtml();
            $itemsBlock = $block->getLayout()->createBlock('adminhtml/template')
                ->setOrder($block->getOrder())
                ->setTemplate('adpaymentmethod/sales/order/invoice/create/items.phtml');
            $observer->getEvent()->getTransport()->setHtml($html . $itemsBlock->toHtml());
        }
    }

    public function removeRefund($observer)
    {
        $block = $observer->getEvent()->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Creditmemo_Create_Items) {
            if ($this->helperAnyday->isPaymentAnyday($block->getCreditmemo()->getOrder()->getPayment())) {
                $block->unsetChild('submit_offline');
                $block->unsetChild('submit_button');
                $this->anydayButton($block);
            }
        }
    }

    private function anydayButton($block)
    {
        if ($block->getCreditmemo()->getOrder()->getAnydayIsonline()) {
            $block->setChild(
                'submit_button',
                $block->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                    'label' => Mage::helper('sales')->__('Refund online'),
                    'class' => 'save submit-button',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemo()',
                ))
            );
        }
        if (!$block->getCreditmemo()->getOrder()->getAnydayIsonline()) {
            $block->setChild(
                'submit_offline',
                $block->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                    'label' => Mage::helper('sales')->__('Refund Offline'),
                    'class' => 'save submit-button',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemoOffline()',
                ))
            );
        }
    }
}