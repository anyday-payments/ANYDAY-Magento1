<?php
class Anyday_Paymentmethod_Block_Adminhtml_Widget_Grid_Groupmassaction extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('adpaymentmethod/widget/grid/groupmassaction.phtml');
    }
}