<?php
class Anyday_Payment_Model_Selecttags
{
    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value'=>0, 'label'=>Mage::helper('adpayment')->__('Select option')
            ],
            [
                'value'=>1, 'label'=>Mage::helper('adpayment')->__('Tag')
            ],
            [
                'value'=>2, 'label'=>Mage::helper('adpayment')->__('Name Element')
            ],
            [
                'value'=>3, 'label'=>Mage::helper('adpayment')->__('Class Element')
            ]
        ];
    }
}