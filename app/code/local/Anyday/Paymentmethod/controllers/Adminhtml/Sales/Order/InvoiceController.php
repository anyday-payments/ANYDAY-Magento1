<?php
require_once(Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales'.DS.'Order'.DS.'InvoiceController.php');
class Anyday_Paymentmethod_Adminhtml_Sales_Order_InvoiceController extends Mage_Adminhtml_Sales_Order_InvoiceController
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

    /**
     * Initialize invoice model instance
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function _initInvoice($update = false)
    {
        $this->_title($this->__('Sales'))->_title($this->__('Invoices'));

        $invoice = false;
        $itemsToInvoice = 0;
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        $orderId = $this->getRequest()->getParam('order_id');
        if ($invoiceId) {
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
            if (!$invoice->getId()) {
                $this->_getSession()->addError($this->__('The invoice no longer exists.'));
                return false;
            }
        } elseif ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            /**
             * Check order existing
             */
            if (!$order->getId()) {
                $this->_getSession()->addError($this->__('The order no longer exists.'));
                return false;
            }
            /**
             * Check invoice create availability
             */
            if (!$order->canInvoice()) {
                $this->_getSession()->addError($this->__('The order does not allow creating an invoice.'));
                return false;
            }
            $savedQtys = $this->_getItemQtys();
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($savedQtys);
            $data = $this->getRequest()->getPost('invoice');
            if (Mage::registry('save_invoice')) {
                Mage::unregister('save_invoice');
                if ($this->helperAnyday->isPaymentAnyday($order->getPayment())) {
                    /** @var $transactions Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection */
                    $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection');
                    $listTransaction = $transactions->addOrderIdFilter($order->getId())
                        ->addAttributeToSelect('*');
                    /**
                     * @var $oneTransaction Mage_Sales_Model_Order_Payment_Transaction
                     */
                    foreach ($listTransaction as $oneTransaction) {
                        if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH or true) {
                            $transactionId = $oneTransaction->getTxnId();
                            $this->helperAnyday->captureTransaction(
                                $transactionId,
                                (double)$invoice->getGrandTotal(),
                                $order,
                                $order->getStore()->getId()
                            );
                            Mage::register('anyday_transaction',[
                                'transaction'   => $transactionId,
                                'amount'        => (double)$invoice->getGrandTotal()
                                ]
                            );
//                            $order->getPayment()->setTransactionId( $transactionId . $invoice->getId() );
//                            $transaction = $order->getPayment()->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
//                                null,
//                                false,
//                                'Anyday transaction');
//                            $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
//                                array('Context'=>'Payment from invoice '. $invoice->getId(),
//                                    'Amount'=>$invoice->getGrandTotal(),
//                                    'Status'=>0,
//                                ));
//                            $transaction->setIsTransactionClosed(true);
//                            $transaction->save();
//
//                            $order->save();
                            break;
                        }
                    }
                } else {
                    //Mage::throwException(Mage::helper('payment')->__('Order not Payment ANYDAY.'));
                }
            }
            //Mage::throwException($this->__('Cannot create an invoice without products.'));
            if (!$invoice->getTotalQty()) {
                Mage::throwException($this->__('Cannot create an invoice without products.'));
            }
        }

        Mage::register('current_invoice', $invoice);
        return $invoice;
    }

    public function saveAction()
    {
        $data = $this->getRequest()->getPost('invoice');
        $orderId = $this->getRequest()->getParam('order_id');

        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try {
            $currentOrder = Mage::getModel('sales/order')->load($orderId);
            if ($data['capture_case'] == 'online'
                && $this->helperAnyday->isOnlineInvoice($currentOrder)) {
                Mage::register('save_invoice', true);
                $currentOrder->setData(Anyday_Paymentmethod_Helper_Settings::NAME_ISONLINE_ORDER_FIELD, true);
                $currentOrder->save();
            }
            $invoice = $this->_initInvoice();
            if ($invoice) {

                if (!empty($data['capture_case'])) {
                    $invoice->setRequestedCaptureCase($data['capture_case']);
                }

                if (!empty($data['comment_text'])) {
                    $invoice->addComment(
                        $data['comment_text'],
                        isset($data['comment_customer_notify']),
                        isset($data['is_visible_on_front'])
                    );
                }

                $invoice->register();

                if (!empty($data['send_email'])) {
                    $invoice->setEmailSent(true);
                }

                $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                $invoice->getOrder()->setIsInProcess(true);

                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $shipment = false;
                if (!empty($data['do_shipment']) || (int) $invoice->getOrder()->getForcedDoShipmentWithInvoice()) {
                    $shipment = $this->_prepareShipment($invoice);
                    if ($shipment) {
                        $shipment->setEmailSent($invoice->getEmailSent());
                        $transactionSave->addObject($shipment);
                    }
                }
                $transactionSave->save();

                if (isset($shippingResponse) && $shippingResponse->hasErrors()) {
                    $this->_getSession()->addError($this->__('The invoice and the shipment  have been created. The shipping label cannot be created at the moment.'));
                } elseif (!empty($data['do_shipment'])) {
                    if ($invoice->getRequestedCaptureCase() == 'online') {
                        $this->helperAnyday->addStatusAfterInvoice($invoice->getOrder());
                        //$this->helperAnyday->saveTransaction($invoice->getOrder(), $invoice->getIncrementId());
                    }
                    $this->_getSession()->addSuccess($this->__('The invoice and shipment have been created.'));
                } else {
                    if ($invoice->getRequestedCaptureCase() == 'online') {
                        $this->helperAnyday->addStatusAfterInvoice($invoice->getOrder());
                        //$this->helperAnyday->saveTransaction($invoice->getOrder(), $invoice->getIncrementId());
                    }
                    $this->_getSession()->addSuccess($this->__('The invoice has been created.'));
                }

                // send invoice/shipment emails
                $comment = '';
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
                try {
                    $invoice->sendEmail(!empty($data['send_email']), $comment);
                } catch (Exception $e) {
                    Mage::logException($e);
                    $this->_getSession()->addError($this->__('Unable to send the invoice email.'));
                }
                if ($shipment) {
                    try {
                        $shipment->sendEmail(!empty($data['send_email']));
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $this->_getSession()->addError($this->__('Unable to send the shipment email.'));
                    }
                }
                Mage::getSingleton('adminhtml/session')->getCommentText(true);
                $this->_redirect('*/sales_order/view', array('order_id' => $orderId));
            } else {
                $this->_redirect('*/*/new', array('order_id' => $orderId));
            }
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Unable to save the invoice.'));
            Mage::logException($e);
        }
        $this->_redirect('*/*/new', array('order_id' => $orderId));
    }
}