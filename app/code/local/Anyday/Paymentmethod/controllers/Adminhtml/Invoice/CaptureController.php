<?php
class Anyday_Paymentmethod_Adminhtml_Invoice_CaptureController extends Mage_Adminhtml_Controller_Action
{
    public function createonlineAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        foreach ($orderIds as $orderId) {
            /** @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($order->canInvoice()) {
                try {
                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                    if (!$invoice->getTotalQty()) {
                        Mage::throwException($this->__('Cannot create an invoice without products.'));
                    }
                    if ($invoice) {
                        if (!empty($data['capture_case'])) {
                            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                        }

                        $invoice->register();
                        $invoice->setEmailSent(true);

                        $invoice->getOrder()->setCustomerNoteNotify(true);
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

                        $invoice->capture();
                        $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder());
                        $transactionSave->save();

                        if (isset($shippingResponse) && $shippingResponse->hasErrors()) {
                            $this->_getSession()->addError($this->__('The invoice and the shipment  have been created. The shipping label cannot be created at the moment.'));
                        } elseif (!empty($data['do_shipment'])) {
                            $this->_getSession()->addSuccess($this->__('The invoice and shipment have been created.'));
                        } else {
                            $this->_getSession()->addSuccess($this->__('The invoice has been created.'));
                        }

                        // send invoice/shipment emails
                        $comment = '';
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
                    }
                } catch (Mage_Core_Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                } catch (Exception $e) {
                    $this->_getSession()->addError($this->__('Unable to save the invoice.'));
                    Mage::logException($e);
                }
            } else {
                $this->_getSession()->addError($this->__('%s order(s) cannot be invoice', $order->getIncrementId()));
            }
        }

        $this->_redirect('*/sales_order/');
    }
}