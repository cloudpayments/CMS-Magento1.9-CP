<?php

/**
 * Class CloudPayments_Widget_Model_System_Config_Source_Tax_System
 */
class CloudPayments_Widget_Model_System_Config_Source_Tax_System
{

    /**
     * Taxation system values
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('cloudpayments');

        return [
            ['value' => 0, 'label' => $helper->__('General taxation system')],
            ['value' => 1, 'label' => $helper->__('Simplified taxation system (Income)')],
            ['value' => 2, 'label' => $helper->__('Simplified taxation system (Income minus Expenditure)')],
            ['value' => 3, 'label' => $helper->__('A single tax on imputed income')],
            ['value' => 4, 'label' => $helper->__('Unified agricultural tax')],
            ['value' => 5, 'label' => $helper->__('Patent system of taxation')],
        ];
    }
}