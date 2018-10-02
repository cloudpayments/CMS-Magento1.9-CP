<?php

class Cloudpayments_Widget_Block_Widget extends Mage_Core_Block_Template
{

    /** @var Mage_Sales_Model_Order */
    private $_order;

    /** @var CloudPayments_Widget_Model_Method_Widget */
    private $_payment;

    public function __construct(array $args = [])
    {

        parent::__construct($args);
        $this->setTemplate('cloudpayments/widget.phtml');
    }

    protected function _beforeToHtml()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if ($orderId) {
            /** @var Mage_Sales_Model_Order $order */
            $this->_order = Mage::getModel('sales/order')->load($orderId);
            if ($this->_order) {
                $this->_payment = $this->_order->getPayment()->getMethodInstance();
            }
        }

        return parent::_beforeToHtml(); // TODO: Change the autogenerated stub
    }

    /**
     * @return array
     * @throws Mage_Core_Exception
     */
    public function getWidgetParams()
    {
        if (!$this->_order || !$this->_payment) {
            return [];
        }

        $widgetParams = [
            "publicId" => $this->_payment->getConfigData('public_id'),
            "description" => Mage::helper('cms')->getBlockTemplateProcessor()->filter($this->_payment->getConfigData('order_desc')),
            "amount" => floatval($this->_order->getBaseGrandTotal()),
            "currency" => $this->_order->getOrderCurrencyCode(),
            "invoiceId" => $this->_order->getIncrementId(),
            "accountId" => $this->_order->getCustomerEmail(),
            "email" => $this->_order->getCustomerEmail(),
            "data" => [
                "name" => $this->_order->getCustomerName(),
                "phone" => $this->_order->getShippingAddress()->getTelephone(),
                "cloudPayments" => [],
            ]
        ];

        if ($this->_payment->getConfigData('send_receipt')) {
            $widgetParams["data"]["cloudPayments"]["customerReceipt"] = $this->_getReceiptData($this->_order,
                $this->_payment);
        }

        return $widgetParams;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param CloudPayments_Widget_Model_Method_Widget $payment
     * @return array
     */
    protected function _getReceiptData($order, $payment)
    {
        $receiptData = [
            'Items' => [],
            'taxationSystem' => $payment->getConfigData('taxation_system'),
            'email' => $order->getCustomerEmail(),
            'phone' => $order->getShippingAddress()->getTelephone()
        ];

        $vatProduct = '';
        $vatAll = $payment->getConfigData('vat_all');
        $vatAttr = $payment->getConfigData('vat_product_attr');
        if ($vatAll) {
            $vatProduct = str_replace('vat_', '', $payment->getConfigData('vat'));
            if ($vatProduct == 'none') {
                $vatProduct = '';
            }
        }

        $items = [];
        /** @var Mage_Sales_Model_Order_Item $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = $orderItem->getProduct();
            if ($orderItem->getParentItem()) {
                // Don't process children
                continue;
            }
            if ($vatAll) {
                $itemVat = $vatProduct;
            } else {
                $itemVat = $product->getData($vatAttr);
            }

            $items[] = [
                "label" => $product->getName(),
                "quantity" => floatval($orderItem->getQtyOrdered()),
                "price" => floatval($orderItem->getPrice()),
                "amount" => floatval($orderItem->getPrice()) * floatval($orderItem->getQtyOrdered()) - $orderItem->getDiscountAmount(),
                "vat" => $itemVat,
            ];
        }

        if ($order->getShippingAmount()) {
            $vatDelivery = str_replace('vat_', '', $payment->getConfigData('vat_delivery'));
            if ($vatDelivery == 'none') {
                $vatDelivery = '';
            }
            $name = $payment->getConfigData('default_shipping_name') ? $order->getShippingDescription() : $payment->getConfigData('custom_shipping_name');

            $items[] = [
                "label" => $name,
                "quantity" => 1,
                "price" => floatval($order->getShippingAmount()),
                "amount" => floatval($order->getShippingAmount()),
                "vat" => $vatDelivery,
            ];
        }

        $receiptData['Items'] = $items;

        return $receiptData;

    }

    /**
     * @return null|string
     */
    public function getOrderId()
    {
        return $this->_order ? $this->_order->getIncrementId() : null;
    }

    /**
     * @return string
     */
    public function getStepMethod()
    {
        return $this->_payment->getConfigData('step') == '2' ? 'auth' : 'charge';
    }

    /**
     * @return string
     */
    public function getLang()
    {
        $map = array(
            'ru_RU' => 'ru-RU', //Russian
            'en_GB' => 'en-US', //English
            'en_US' => 'en-US', //English
            'lv_LV' => 'lv', //Latvian
            'az_AZ' => 'az', //Azerbaijani
            //'' => 'kk', //Kazakh (Russian)
            //'' => 'kk-KZ', //Kazakh
            'uk_UA' => 'uk', //Ukrainian
            'pl_PL' => 'pl', //Polish
            'pt_BR' => 'pt', //Portuguese
            'pt_PT' => 'pt', //Portuguese
        );
        $curLocale = Mage::app()->getLocale()->getLocaleCode();

        return isset($map[$curLocale]) ? $map[$curLocale] : 'en-US';
    }

    /**
     * @return mixed|string
     */
    public function getSuccessPage()
    {
        $url = $this->_payment ? $this->_payment->getConfigData('success_page') : '';
        if (!$url) {
            $url = Mage::getUrl('checkout/onepage/success');
        }

        return $url;
    }

    /**
     * @return mixed|string
     */
    public function getErrorPage()
    {
        $url = $this->_payment ? $this->_payment->getConfigData('error_page') : '';
        if (!$url) {
            $url = Mage::getUrl('checkout/onepage/failure');
        }

        return $url;
    }

}