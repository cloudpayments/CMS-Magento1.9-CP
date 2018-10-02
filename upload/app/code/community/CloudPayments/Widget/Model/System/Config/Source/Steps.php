<?php

class CloudPayments_Widget_Model_System_Config_Source_Steps
{
    /**
     * One/Two factor payment
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('cloudpayments');

        return [
            ['value' => 1, 'label' => $helper->__('One-step')],
            ['value' => 2, 'label' => $helper->__('Two-step')]
        ];
    }
}
