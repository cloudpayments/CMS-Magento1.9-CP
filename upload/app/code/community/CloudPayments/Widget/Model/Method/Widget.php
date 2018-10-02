<?php

class CloudPayments_Widget_Model_Method_Widget extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'cloudpayments';
    const API_URL = 'https://api.cloudpayments.ru/';

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCaptureOnce = true;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_isInitializeNeeded = true;
    protected $_canReviewPayment = true;
    protected $_canUseForMultishipping = false;
    protected $_canManageRecurringProfiles = false;

    /**
     * Instantiate state and set it to state object
     * @param string $paymentAction
     * @param Varien_Object
     * @return CloudPayments_Widget_Model_Method_Widget
     */
    public function initialize($paymentAction, $stateObject)
    {
        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE:
            case self::ACTION_AUTHORIZE_CAPTURE:
                $payment = $this->getInfoInstance();
                $order = $payment->getOrder();

                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'pending_payment', '', false);

                $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
                $stateObject->setStatus('pending_payment');
//                $stateObject->setIsNotified(false);
                break;
            default:
                break;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigPaymentAction()
    {
        // Magento won't update settings from default, so return direct value
        return 'authorize';
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        return in_array($currencyCode, [
            'RUB',
            'EUR',
            'USD',
            'GBP',
            'UAH',
            'BYR',
            'BYN',
            'KZT',
            'AZN',
            'CHF',
            'CZK',
            'CAD',
            'PLN',
            'SEK',
            'TRY',
            'CNY',
            'INR',
            'BRL',
            'ZAL',
            'UZS',
        ]);
    }

    /**
     * Redirect URL to robokassa controller after order place
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('cloudpayments/process/pay');
    }

    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);

        $transactionId = $this->_getLastTransactionId($payment, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        if ($transactionId) {
            $this->_makeRequest('payments/confirm', [
                'TransactionId' => $transactionId,
                'Amount'        => floatval($amount),
            ]);
        }

        return $this;
    }

    public function void(Varien_Object $payment)
    {
        parent::void($payment);

        $transactionId = $this->_getLastTransactionId($payment, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        if ($transactionId) {
            $this->_makeRequest('payments/void', [
                'TransactionId' => $transactionId,
            ]);
        }

        return $this;
    }

    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        $transactionId = $this->_getLastTransactionId($payment, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        if ($transactionId) {
            $this->_makeRequest('payments/refund', [
                'TransactionId' => $transactionId,
                'Amount'        => floatval($amount),
            ]);
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment|Varien_Object $payment
     * @param $txnType
     * @return bool|string
     */
    private function _getLastTransactionId($payment, $txnType)
    {
        $transaction = $payment->lookupTransaction('', $txnType);
        if (!$transaction) {
            return false;
        }

        return $transaction->getParentTxnId() ? $transaction->getParentTxnId() : $transaction->getTxnId();
    }
    /**
     * @param string $location
     * @param array $request
     * @return bool|array
     */
    private function _makeRequest($location, $request = array())
    {
        /** @var CloudPayments_Widget_Helper_Data $helper */
        $helper = Mage::helper('cloudpayments');
        $helper->addLog('API Request ' . $location);
        $helper->addLog($request);

        try {
            $client = new Varien_Http_Client();
            $client->setUri(self::API_URL . $location);
            $client->setConfig([
                'maxredirects' => 0,
                'timeout' => 30,
                'verifyhost' => 2,
                'verifypeer' => true,
            ]);
            $client->setAuth($this->getConfigData('public_id'), $this->getConfigData('secret_key'));
            $client->setRawData(json_encode($request));
            $client->setEncType('application/json');
            $client->setMethod(Zend_Http_Client::POST);
            $response = $client->request();
        } catch (Exception $e) {
            $helper->addLog('Failed API request' .
                ' Location: ' . $location .
                ' Request: ' . print_r($request, true) .
                ' Error: ' . $e->getMessage()
            );

            return false;
        }
        $response = json_decode($response->getBody(), true);
        if (!isset($response['Success']) || !$response['Success']) {
            $helper->addLog('Failed API request' .
                ' Location: ' . $location .
                ' Request: ' . print_r($request, true) .
                ' Response: ' . print_r($response, true)
            );

            return false;
        }
        $helper->addLog('API Response: ' . print_r($response, true));

        return $response;
    }
}