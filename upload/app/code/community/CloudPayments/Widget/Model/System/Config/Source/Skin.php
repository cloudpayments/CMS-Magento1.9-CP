<?php

class CloudPayments_Widget_Model_System_Config_Source_Skin
{
    /**
     * Skin values
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('cloudpayments');

        return [
            ['value' => 'classic', 'label' => $helper->__('Classic')],
            ['value' => 'modern', 'label' => $helper->__('Modern')],
            ['value' => 'mini', 'label' => $helper->__('Mini')],
        ];
    }
}