<?php
class Anyday_Paymentmethod_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract
{
    const NAME_URL                      = 'url';
    const NAME_TRANSACTION              = 'transactionId';
    const NAME_QUOTE                    = 'quoteId';
    const NAME_AMOUNT                   = 'amount';

    protected $_formBlockType           = 'adpaymentmethod/form_anyday';

    protected $_code                    = Anyday_Paymentmethod_Model_Config::PAYMENT_CODE;
    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;
    protected $_canCapture              = true;
    protected $_canAuthorize            = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;

    /**
     * @var Mage_Core_Model_Abstract|null
     */
    private $sessionCheckout;

    /**
     * @var Anyday_Paymentmethod_Helper_Anyday|null
     */
    private $helperAnyday;

    public function __construct()
    {
        $this->sessionCheckout  = Mage::getSingleton('checkout/session');
        $this->helperAnyday     = Mage::helper('adpaymentmethod/anyday');
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        return parent::authorize($payment, $amount); // TODO: Change the autogenerated stub
    }

    /**
     * Validate payment method information object
     *
     * @return Anyday_Paymentmethod_Model_Paymentmethod
     */
    public function validate()
    {
        parent::validate();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getSingleton('checkout/session')->getAdOptins()['url'];
    }

    /**
     * Set capture transaction ID to invoice for informational purposes
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     * @throws Exception
     */
    public function processInvoice($invoice, $payment)
    {
        $this->helperAnyday->addStatusAfterInvoice($invoice->getOrder());
        return parent::processInvoice($invoice, $payment);
    }

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $currentOrder = $payment->getOrder();
        $currentOrder->setData(Anyday_Paymentmethod_Helper_Settings::NAME_ISONLINE_ORDER_FIELD, true);
        $currentOrder->save();
        /** @var $transactions Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection */
        $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection');
        $listTransaction = $transactions->addOrderIdFilter($currentOrder->getId())
            ->addAttributeToSelect('*');
        /**
         * @var $oneTransaction Mage_Sales_Model_Order_Payment_Transaction
         */
        foreach ($listTransaction as $oneTransaction) {
            if ($oneTransaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER) {
                $transactionId = $oneTransaction->getTxnId();
                $this->helperAnyday->captureTransaction(
                    $transactionId,
                    (double)$amount,
                    $currentOrder,
                    $currentOrder->getStore()->getId()
                );
                Mage::register('anyday_transaction',[
                        'transaction'   => $transactionId,
                        'amount'        => (double)$amount
                    ]
                );
                break;
            }
        }
        return parent::capture($payment, $amount);
    }
}