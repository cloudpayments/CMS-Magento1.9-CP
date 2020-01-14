<?php

class CloudPayments_Widget_Model_System_Config_Source_Lang
{
    /**
     * widget_lang values
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('cloudpayments');

        return [
            ['value' => 'ru-Ru', 'label' => $helper->__('Русский')],
            ['value' => 'en-US', 'label' => $helper->__('English')],
            ['value' => 'uk', 'label' => $helper->__('Український')],
            ['value' => 'lv', 'label' => $helper->__('Latviešu')],
            ['value' => 'az', 'label' => $helper->__('Azərbaycan')],
            ['value' => 'kk', 'label' => $helper->__('Русский (часовой пояс ALMT)')],
            ['value' => 'kk-KZ', 'label' => $helper->__('Қазақ')],
            ['value' => 'pl', 'label' => $helper->__('Polski')],
            ['value' => 'pt', 'label' => $helper->__('Português')],
        ];
    }
}