<?php

class CloudPayments_Widget_CallbackController extends Mage_Core_Controller_Front_Action
{

    const RESULT_SUCCESS = 0;
    const RESULT_ERROR_INVALID_ORDER = 10;
    const RESULT_ERROR_INVALID_COST = 11;
    const RESULT_ERROR_NOT_ACCEPTED = 13;
    const RESULT_ERROR_EXPIRED = 20;

    /** @var CloudPayments_Widget_Helper_Data */
    private $_helper;
    /** @var CloudPayments_Widget_Helper_Transaction */
    private $_transHelper;

    public function preDispatch()
    {
        $this->_helper = Mage::helper('cloudpayments');
        $this->_transHelper = Mage::helper('cloudpayments/transaction');

        return parent::preDispatch();
    }

    public function checkAction()
    {
        $this->_processNotifyRequest('check');
    }

    public function payAction()
    {
        $this->_processNotifyRequest('pay');
    }

    public function confirmAction()
    {
        $this->_processNotifyRequest('confirm');
    }

    public function failAction()
    {
        $this->_processNotifyRequest('fail');
    }

    public function refundAction()
    {
        $this->_processNotifyRequest('refund');
    }

    public function cancelAction()
    {
        $this->_processNotifyRequest('cancel');
    }

    public function receiptAction()
    {
        $this->_helper->addLog('Proceed callback: ' . $this->getRequest()->getActionName());
        $this->_helper->addLog($this->getRequest()->getPost());

        /** @var CloudPayments_Widget_Model_Method_Widget $payment */
        $payment = Mage::getSingleton('cloudpayments/method_widget');

        if (!$this->_validateSignature($payment->getConfigData('secret_key'))) {
            $this->_exitWithError(self::RESULT_ERROR_NOT_ACCEPTED, 'Invalid signature');
        }

        $order = $this->_retrieveOrderFromRequest();
        if (!$order) {
            $this->_exitWithError(self::RESULT_ERROR_INVALID_ORDER, 'Order not found');
        }

        $postData = $this->getRequest()->getPost();
        $postData['Receipt'] = json_decode($postData['Receipt'], true);
        $postData['ReceiptEmail'] = $postData['Receipt']['Email'];
        $postData['ReceiptPhone'] = $postData['Receipt']['Phone'];

        $i = 1;
        foreach ($postData['Receipt']['Items'] as $item) {
            $postData['ReceiptItem'.$i] = json_encode($item);
        }
        unset($postData['Receipt']);

        try {
            if ($postData['Type'] == 'Income') {
                $this->_transHelper->proceedReceipt($order, $postData['Id'], $postData['TransactionId'], $postData);
                $this->_helper->addLog('receipt income');
            }

            if ($postData['Type'] == 'IncomeReturn') {
                $this->_transHelper->proceedRefundReceipt($order, $postData['Id'], $postData['TransactionId'], $postData);
                $this->_helper->addLog('receipt refund');
            }
        } catch (\Exception $e) {
            $this->_helper->addLog($e->getMessage());
            $this->_exitWithError(self::RESULT_ERROR_NOT_ACCEPTED, $e->getMessage());
        }

        $this->_printCallbackResponse(self::RESULT_SUCCESS);
    }

    protected function _printCallbackResponse($code, $message = '')
    {
        header('Content-Type: application/json');
        echo json_encode(array('code' => $code, 'message' => $message));
    }

    protected function _exitWithError($code, $message = '')
    {
        $this->_helper->addLog($message);
        $this->_printCallbackResponse($code, $message);
        die();
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function _getPostData($key)
    {
        return $this->getRequest()->getPost($key);
    }

    protected function _processNotifyRequest($notify)
    {
        $this->_helper->addLog('Proceed callback: ' . $this->getRequest()->getActionName());
        $this->_helper->addLog($this->getRequest()->getPost());

        /** @var CloudPayments_Widget_Model_Method_Widget $payment */
        $payment = Mage::getSingleton('cloudpayments/method_widget');

        if (!$this->_validateSignature($payment->getConfigData('secret_key'))) {
            $this->_exitWithError(self::RESULT_ERROR_NOT_ACCEPTED, 'Invalid signature');
        }

        $order = $this->_retrieveOrderFromRequest();
        if (!$order) {
            $this->_exitWithError(self::RESULT_ERROR_INVALID_ORDER, 'Order not found');
        }

        if (in_array($notify, ['check', 'pay', 'confirm'])) {
            if ($order->getBaseCurrencyCode() !== $this->_getPostData('Currency')) {
                $this->_exitWithError(self::RESULT_ERROR_INVALID_COST, 'Invalid order currency');
            }
            if (floatval($order->getBaseGrandTotal()) != floatval($this->_getPostData('Amount'))) {
                $this->_exitWithError(self::RESULT_ERROR_INVALID_COST, 'Invalid order cost');
            }
        }
        if (in_array($notify, ['check']) && $order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            $this->_exitWithError(self::RESULT_ERROR_NOT_ACCEPTED, 'Invalid order state, possibly already payed');
        }

        try {
            switch ($notify) {
                case 'pay':
                    if ($this->_getPostData('Status') == 'Authorized') {
                        $this->_transHelper->proceedAuthorize(
                            $order,
                            $this->_getPostData('TransactionId'),
                            $this->_getPostData('Amount'),
                            $this->getRequest()->getPost()
                        );
                    } else {
                        if ($this->_getPostData('Status') == 'Completed') {
                            $this->_transHelper->proceedCapture(
                                $order,
                                $this->_getPostData('TransactionId'),
                                $this->_getPostData('Amount'),
                                false,
                                $this->getRequest()->getPost()
                            );
                        }
                    }
                    break;
                case 'confirm':
                    $this->_transHelper->proceedCapture(
                        $order,
                        $this->_getPostData('TransactionId'),
                        $this->_getPostData('Amount'),
                        true,
                        $this->getRequest()->getPost()
                    );
                    break;
                case 'fail':
                    $this->_transHelper->proceedFail($order,
                        $this->_getPostData('TransactionId'),
                        $this->_getPostData('Reason'),
                        $this->getRequest()->getPost()
                    );
                    break;
                case 'refund':
                    if ($order->getState() == Mage_Sales_Model_Order::STATE_CLOSED) {
                        $this->_helper->addLog('Skip refund order, already closed');
                        break;
                    }
                    if (!$order->canCreditmemo()) {
                        $this->_exitWithError(self::RESULT_ERROR_NOT_ACCEPTED, 'Order can not be refunded');
                    }
                    $this->_transHelper->proceedRefund(
                        $order,
                        $this->_getPostData('TransactionId'),
                        $this->_getPostData('PaymentTransactionId'),
                        $this->_getPostData('Amount')
                    );
                    break;
                case 'cancel':
                    $this->_transHelper->proceedVoid(
                        $order,
                        $this->_getPostData('TransactionId'),
                        $this->_getPostData('PaymentTransactionId'),
                        $this->_getPostData('Amount')
                    );
                    break;
            }
        } catch (Exception $e) {
            $this->_helper->addLog('Exception: ' . $e->getMessage());
            $this->_exitWithError(self::RESULT_ERROR_NOT_ACCEPTED, $e->getMessage());
        }

        $this->_printCallbackResponse(self::RESULT_SUCCESS);
    }

    /**
     * @param $secretKey
     * @return bool
     */
    private function _validateSignature($secretKey)
    {
        //Check HMAC sign
        $postData = file_get_contents('php://input');
        $checkSign = base64_encode(hash_hmac('SHA256', $postData, $secretKey, true));
        $requestSign = $this->getRequest()->getServer('HTTP_CONTENT_HMAC');
        $this->_helper->addLog('signature ' . $requestSign . ', calculated ' . $checkSign);

        return $checkSign === $requestSign;
    }

    /**
     * @return Mage_Sales_Model_Order|false
     */
    private function _retrieveOrderFromRequest()
    {
        $orderId = $this->_getPostData('InvoiceId');
        if (!$orderId) {
            return false;
        }

        return Mage::getModel('sales/order')->loadByIncrementId($orderId);
    }
}