<?php
class Anyday_Paymentmethod_Block_Form_Anyday extends Mage_Payment_Block_Form
{
    public function getMethodLabelAfterHtml()
    {
        $imageSrc = '<image src="' . $this->getSkinUrl('images/ANYDAY_Split_Logo.svg') . '" style="float: right;width: 100px;" />';
        $jQueryText = "<script>
            var label = jQuery('label[for=\"p_method_adpaymentmethod\"]');
            if (label.length) {
                title = label.html();
                label.html('" . $imageSrc . "' + ' ' + title);
            }
            </script>";
        return $imageSrc;
    }
}