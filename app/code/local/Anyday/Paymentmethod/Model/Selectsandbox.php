<?php
class Anyday_Paymentmethod_Model_Selectsandbox
{
    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0, 'label' => Mage::helper('adpayment')->__('Select Test Mode')
            ],
            [
                'value' => 1, 'label' => Mage::helper('adpayment')->__('Select Live')
            ]
        ];
    }
}