<?php
class Anyday_Paymentmethod_Block_Adminhtml_Sales_Order_Creditmemo_Create_Items extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Create_Items
{
    /**
     * @var Anyday_Paymentmethod_Helper_Anyday|null
     */
    private $helperAnyday;

    public function __construct(
        array $args = array()
    ) {
        parent::__construct($args);
        $this->helperAnyday = Mage::helper('adpaymentmethod/anyday');
    }

    protected function _prepareLayout()
    {
        $onclick = "submitAndReloadArea($('creditmemo_item_container'),'".$this->getUpdateUrl()."')";
        $this->setChild(
            'update_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                'label'     => Mage::helper('sales')->__('Update Qty\'s'),
                'class'     => 'update-button',
                'onclick'   => $onclick,
            ))
        );

        $refundName = 'Refund';
        $isAnyday = false;
        $isAnydayOnline = false;
        if ($this->helperAnyday->isPaymentAnyday($this->getCreditmemo()->getOrder()->getPayment())) {
            $this->anydayButton();
        } else {
            if ($this->getCreditmemo()->canRefund()) {
                if ($this->getCreditmemo()->getInvoice() && $this->getCreditmemo()->getInvoice()->getTransactionId()) {
                    $this->setChild(
                        'submit_button',
                        $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                            'label' => Mage::helper('sales')->__('Refund'),
                            'class' => 'save submit-button',
                            'onclick' => 'disableElements(\'submit-button\');submitCreditMemo()',
                        ))
                    );
                }
                $this->setChild(
                    'submit_offline',
                    $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                        'label' => Mage::helper('sales')->__('Refund Offline'),
                        'class' => 'save submit-button',
                        'onclick' => 'disableElements(\'submit-button\');submitCreditMemoOffline()',
                    ))
                );

            } else {
                $this->setChild(
                    'submit_button',
                    $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                        'label' => Mage::helper('sales')->__('Refund Offline'),
                        'class' => 'save submit-button',
                        'onclick' => 'disableElements(\'submit-button\');submitCreditMemoOffline()',
                    ))
                );
            }
        }

        return Mage_Adminhtml_Block_Sales_Items_Abstract::_prepareLayout();
    }

    private function anydayButton()
    {
        if ($this->getCreditmemo()->getOrder()->getAnydayIsonline()) {
            $this->setChild(
                'submit_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                    'label' => Mage::helper('sales')->__('Refund online'),
                    'class' => 'save submit-button',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemo()',
                ))
            );
        }
        if (!$this->getCreditmemo()->getOrder()->getAnydayIsonline()) {
            $this->setChild(
                'submit_offline',
                $this->getLayout()->createBlock('adminhtml/widget_button')->setData(array(
                    'label' => Mage::helper('sales')->__('Refund Offline'),
                    'class' => 'save submit-button',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemoOffline()',
                ))
            );
        }
    }
}