<?php

class CloudPayments_Widget_Helper_Transaction extends Mage_Core_Helper_Abstract
{
    /**
     * @var CloudPayments_Widget_Helper_Data
     */
    protected $_helper;

    public function __construct()
    {
        $this->_helper = Mage::helper('cloudpayments');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $transactionId
     * @param $amount
     * @param array $transData
     * @throws Exception
     */
    public function proceedAuthorize($order, $transactionId, $amount, $transData = [])
    {
        $this->_helper->addLog('proceed authorize: ' . $transactionId . ' ' . $amount);
        $this->_helper->addLog($transData);

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(0);
        $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transData);
        $payment->registerAuthorizationNotification($amount);
        $payment->setAmountAuthorized($amount);
        $order->setStatus('authorized_cloudpayments');
        $order->save();

        // notify customer
        $message = Mage::helper('cloudpayments')->__('Notified customer about new order');
        $order->queueNewOrderEmail()->addStatusHistoryComment($message)
            ->setIsCustomerNotified(true)
            ->save();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $transactionId
     * @param $amount
     * @param bool $isConfirm
     * @param array $transData
     * @throws Exception
     */
    public function proceedCapture($order, $transactionId, $amount, $isConfirm = false, $transData = [])
    {
        $this->_helper->addLog('proceed capture: ' . $transactionId . ' ' . $amount);
        $this->_helper->addLog($transData);

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        if ($isConfirm) {
            $payment->setParentTransactionId($transactionId);
            //$payment->setTransactionId($transactionId . '-capture');
        } else {
            $payment->setTransactionId($transactionId);
        }
        $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transData);
        $payment->setIsTransactionClosed(0);
        $payment->registerCaptureNotification($amount, true);
        $order->save();

        // notify customer
        if ($isConfirm) {
            $message = Mage::helper('cloudpayments')->__('Notified customer about confirm order');
            $order->queueOrderUpdateEmail(true)->addStatusHistoryComment($message)
                ->setIsCustomerNotified(true)
                ->save();
        } else {
            $message = Mage::helper('cloudpayments')->__('Notified customer about new order');
            $order->queueNewOrderEmail()->addStatusHistoryComment($message)
                ->setIsCustomerNotified(true)
                ->save();
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $transactionId
     * @param $parentTransactionId
     * @param $amount
     * @throws Exception
     */
    public function proceedRefund($order, $transactionId, $parentTransactionId, $amount)
    {
        $this->_helper->addLog('proceed refund: ' . $transactionId .
            ' ' . $parentTransactionId . ' ' . $amount);

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment
            //->setTransactionId($transactionId . '-refund')
            ->setParentTransactionId($parentTransactionId)
            ->setIsTransactionClosed(true);
        $payment->registerRefundNotification($amount);
        $order->save();

        $order->queueOrderUpdateEmail(true)
            ->setIsCustomerNotified(true)
            ->save();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $transactionId
     * @param $parentTransactionId
     * @param $amount
     * @throws Exception
     */
    public function proceedVoid($order, $transactionId, $parentTransactionId, $amount)
    {
        $this->_helper->addLog('proceed void: ' . $transactionId
            . ' ' . $parentTransactionId . ' ' . $amount);
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment->registerVoidNotification($amount);
        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
        $order->save();

        $order->queueOrderUpdateEmail(true)
            ->setIsCustomerNotified(true)
            ->save();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $transactionId
     * @param $reason
     * @param array $transData
     * @throws Mage_Core_Exception
     */
    public function proceedFail($order, $transactionId, $reason, $transData = [])
    {
        $this->_helper->addLog('proceed payment fail: ' . $transactionId);
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(true);
        $transaction = $payment->addTransaction(
            Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID,
            $order
        );
        $transaction->setAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $transData
        );
        $transaction->save();

        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true,
            $this->__('Failed payment, reason: %s', $reason));
        $order->save();

        $order->queueOrderUpdateEmail(true)
            ->setIsCustomerNotified(true)
            ->save();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $transactionId
     * @param $parentTransactionId
     * @param $transData
     * @throws Mage_Core_Exception
     */
    public function proceedReceipt($order, $transactionId, $parentTransactionId, $transData)
    {
        $this->_helper->addLog('proceed receipt: ' . $transactionId);
        $this->_helper->addLog($transData);

        if ($order->getPayment()->lookupTransaction($transactionId)) {
            $this->_helper->addLog('transaction %1 already exists', $transactionId);
            return;
        }

        $invoice = $this->getInvoiceForTransactionId($order, $parentTransactionId);


        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment
            ->setTransactionId($transactionId)
            ->setParentTransactionId($parentTransactionId)
            ->setIsTransactionClosed(true);
        $transaction = $payment->addTransaction(
            CloudPayments_Widget_Model_Order_Payment_Transaction::TYPE_FISCAL,
            $invoice ? $invoice : $order
        );
        $transaction->setAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $transData
        );
        $transaction->save();
        $order->addStatusHistoryComment(
            $this->__('Got Fiscal Receipt for transaction %1', $parentTransactionId),
            false
        );
        $order->save();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $transactionId
     * @param $parentTransactionId
     * @param $transData
     * @throws Mage_Core_Exception
     */
    public function proceedRefundReceipt($order, $transactionId, $parentTransactionId, $transData)
    {
        $this->_helper->addLog('Proceed receipt refund: ' . $transactionId);
        $this->_helper->addLog($transData);

        if ($order->getPayment()->lookupTransaction($transactionId)) {
            $this->_helper->addLog('transaction %1 already exists', $transactionId);
            return;
        }

        $memo = $this->getCreditMemoForTransactionId($order, $parentTransactionId);

        $payment = $order->getPayment();
        $payment
            ->setTransactionId($transactionId)
            ->setParentTransactionId($parentTransactionId)
            ->setIsTransactionClosed(true);
        $transaction = $payment->addTransaction(
            CloudPayments_Widget_Model_Order_Payment_Transaction::TYPE_FISCAL_REFUND,
            $memo ? $memo : $order
        );
        $transaction->setAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $transData
        );
        $transaction->save();
        $order->addStatusHistoryComment(
            $this->__('Got Fiscal Refund Receipt for transaction %s', $parentTransactionId),
            false
        );
        $order->save();
    }

    /**
     * Return invoice model for transaction
     *
     * @param string $transactionId
     * @return Mage_Sales_Model_Order_Invoice|false
     */
    protected function getInvoiceForTransactionId($order, $transactionId)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->getTransactionId() == $transactionId) {
                $invoice->load($invoice->getId());
                return $invoice;
            }
        }

        return false;
    }

    /**
     * Return invoice model for transaction
     *
     * @param string $transactionId
     * @return Mage_Sales_Block_Order_Creditmemo|false
     */
    protected function getCreditMemoForTransactionId($order, $transactionId)
    {
        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            if ($creditmemo->getTransactionId() == $transactionId) {
                $creditmemo->load($creditmemo->getId());
                return $creditmemo;
            }
        }

        return false;
    }
}