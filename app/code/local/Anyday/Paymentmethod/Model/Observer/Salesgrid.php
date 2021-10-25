<?php
class Anyday_Paymentmethod_Model_Observer_Salesgrid
{
    const NAME_OPTION_GROUP = 'optionAnyday';

    private $messageSend = false;

    public function addMassAction($observer)
    {
        $this->addMessage();
        $router = Mage::app()->getRequest()->getRouteName();
        $controller = Mage::app()->getRequest()->getControllerName();
        $action = Mage::app()->getRequest()->getActionName();
        if ($observer->getData('block') instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction &&
            $router == 'adminhtml' && $controller == 'sales_order' && $action == 'index') {
            $massActionBlock = $observer->getData('block');

            $massActionBlock->addItem(
                'anyday_invoicerefundonline',
                [
                    'label' => Mage::helper('adpaymentmethod')->__('Invoice And Capture Online'),
                    'url' => Mage::getUrl('*/invoice_capture/createonline'),
                    'additional' => 'adpaymentmethod/adminhtml_widget_grid_groupmassaction',
                ]
            );
        }
    }

    private function addMessage()
    {
        $router = Mage::app()->getRequest()->getRouteName();
        $controller = Mage::app()->getRequest()->getControllerName();
        $action = Mage::app()->getRequest()->getActionName();
        $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'));
        if ($router == 'adminhtml' && $controller == 'sales_order' && $action == 'view' && !$this->messageSend
            && $order->getId()
            && $order->getPayment()->getMethodInstance()->getCode() == Anyday_Paymentmethod_Model_Config::PAYMENT_CODE
            && $this->isPaymentAuthorise($order))
        {

            $this->messageSend = true;
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'));
            if ($order->getId() && $order->canInvoice()) {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adpaymentmethod')->__('Payment has been authorized, you can proceed with invoice and capture.')
                );
            }
        }

        if ($router == 'adminhtml' && $controller == 'sales_order' && $action == 'view' && !$this->messageSend
            && $order->getId()
            && $order->getPayment()->getMethodInstance()->getCode() == Anyday_Paymentmethod_Model_Config::PAYMENT_CODE
            && !$this->isPaymentAuthorise($order))
        {

            $this->messageSend = true;
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load(Mage::app()->getRequest()->getParam('order_id'));
            if ($order->getId() && $order->canInvoice()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('adpaymentmethod')->__('Please authorize payment before invoice.')
                );
            }
        }
    }

    /**
     * Verify is order payment Authorize
     *
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    private function isPaymentAuthorise(Mage_Sales_Model_Order $order)
    {
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
}