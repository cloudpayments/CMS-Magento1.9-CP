<?php

class CloudPayments_Widget_Model_System_Config_Source_Vat
{
    /**
     * Vat values
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('cloudpayments');

        //Something wrong between no vat and 0%, so add prefix "vat_" for all values
        return [
            ['value' => 'vat_none', 'label' => $helper->__('No Vat')],
            ['value' => 'vat_0', 'label' => $helper->__('Vat 0%')],
            ['value' => 'vat_10', 'label' => $helper->__('Vat 10%')],
            ['value' => 'vat_20', 'label' => $helper->__('Vat 20%')],
            ['value' => 'vat_110', 'label' => $helper->__('Calculated vat 10/110')],
            ['value' => 'vat_120', 'label' => $helper->__('Calculated vat 20/120')],
        ];
    }
}