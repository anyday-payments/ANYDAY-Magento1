<?php
require_once(Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales'.DS.'Order'.DS.'CreditmemoController.php');

class Anyday_Paymentmethod_Adminhtml_Sales_Order_CreditmemoController extends Mage_Adminhtml_Sales_Order_CreditmemoController
{
    /**
     * @var Anyday_Paymentmethod_Helper_Anyday|null
     */
    private $helperAnyday;

    /**
     * Anyday_Paymentmethod_Adminhtml_Sales_Order_CreditmemoController constructor.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs
     */
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = array()
    ) {
        parent::__construct($request, $response, $invokeArgs);

        $this->helperAnyday = Mage::helper('adpaymentmethod/anyday');
    }

    /**
     * Save creditmemo and related order, invoice in one transaction
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return Mage_Adminhtml_Sales_Order_CreditmemoController
     * @throws Mage_Core_Exception
     */
    protected function _saveCreditmemo($creditmemo)
    {
        if ($this->helperAnyday->isOrderInvoiceOnline($creditmemo->getOrder()) && !$creditmemo->getOfflineRequested()) {
            /** @var $transactions Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection */
            $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection');
            $listTransaction = $transactions->addOrderIdFilter($creditmemo->getOrder()->getId());
            /**
             * @var $oneTransaction Mage_Sales_Model_Order_Payment_Transaction
             */
            foreach ($listTransaction as $oneTransaction) {
                if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER) {
                    $transactionId = $oneTransaction->getTxnId();
                    $result = $this->helperAnyday->refundTransaction(
                        $transactionId,
                        (double)$creditmemo->getGrandTotal(),
                        $creditmemo->getOrder()->getStore()->getId()
                    );
                    if ($result == '') {
                        Mage::throwException(Mage::helper('payment')->__('ANYDAY Error'));
                        $this->_getSession()->addError('ANYDAY Error');
                    } else {
                        if (isset($result['errorMessage'])) {
                            $creditmemo->cancel();
                            $creditmemo->delete();
                            Mage::throwException(Mage::helper('payment')->__($result['errorMessage']));
                        }
                    }
                    break;
                }
            }
        }

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($creditmemo)
            ->addObject($creditmemo->getOrder());
        if ($creditmemo->getInvoice()) {
            $transactionSave->addObject($creditmemo->getInvoice());
        }
        $transactionSave->save();

        return $this;
    }

    /**
     * Initialize requested invoice instance
     * @param unknown_type $order
     */
    protected function _initInvoice($order)
    {
        /**
         * @var Mage_Sales_Model_Order $order
         */
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            $invoice = Mage::getModel('sales/order_invoice')
                ->load($invoiceId)
                ->setOrder($order);
            if ($invoice->getId()) {
                return $invoice;
            }
        } else {
            $invoiceCollection = $order->getInvoiceCollection();
            if (count($invoiceCollection) == 1) {
                return $invoiceCollection->getFirstItem();
            }
        }
        return false;
    }
}
