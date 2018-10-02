<?php

/**
 * Class CloudPayments_Widget_Block_Adminhtml_System_Config_Form_Field_CallbackUrls
 */
class CloudPayments_Widget_Block_Adminhtml_System_Config_Field_CallbackUrls
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = Mage::helper('cloudpayments');

        return <<<HTML
            <tr>
                <td class="comment" colspan="2">{$helper->__('URLs for settings in Cloudpayments Control Panel')}</td>
            </tr>
            <tr>
                <td class="label">{$helper->__('Check Url')}</td>
                <td class="value">{$this->getSiteUrl('cloudpayments/callback/check')}</td>
            </tr>
            <tr>
                <td class="label">{$helper->__('Pay Url')}</td>
                <td class="value">{$this->getSiteUrl('cloudpayments/callback/pay')}</td>
            </tr>
            <tr>
                <td class="label">{$helper->__('Confirm Url')}</td>
                <td class="value">{$this->getSiteUrl('cloudpayments/callback/confirm')}</td>
            </tr>
            <tr>
                <td class="label">{$helper->__('Fail Url')}</td>
                <td class="value">{$this->getSiteUrl('cloudpayments/callback/fail')}</td>
            </tr>
            <tr>
                <td class="label">{$helper->__('Cancel Url')}</td>
                <td class="value">{$this->getSiteUrl('cloudpayments/callback/cancel')}</td>
            </tr>
            <tr>
                <td class="label">{$helper->__('Refund Url')}</td>
                <td class="value">{$this->getSiteUrl('cloudpayments/callback/refund')}</td>
            </tr>
            <tr>
                <td class="label">{$helper->__('Receipt Url')}</td>
                <td class="value">{$this->getSiteUrl('cloudpayments/callback/receipt')}</td>
            </tr>
HTML;
    }

    public function getSiteUrl($path)
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $path;
    }
}