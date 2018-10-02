<?php

class CloudPayments_Widget_Model_Order_Payment_Transaction extends Mage_Sales_Model_Order_Payment_Transaction
{
    const TYPE_FISCAL = 'fiscal_receipt';
    const TYPE_FISCAL_REFUND = 'fiscal_refund';

    public function getTransactionTypes()
    {
        $helper = Mage::helper('cloudpayments');
        $result = array_merge(parent::getTransactionTypes(), array(
            CloudPayments_Widget_Model_Order_Payment_Transaction::TYPE_FISCAL => $helper->__('Fiscal Receipt'),
            CloudPayments_Widget_Model_Order_Payment_Transaction::TYPE_FISCAL_REFUND => $helper->__('Fiscal Receipt Refund'),
        ));

        return $result;
    }


    protected function _verifyTxnType($txnType = null)
    {
        if (null === $txnType) {
            $txnType = $this->getTxnType();
        }
        switch ($txnType) {
            case self::TYPE_PAYMENT:
            case self::TYPE_ORDER:
            case self::TYPE_AUTH:
            case self::TYPE_CAPTURE:
            case self::TYPE_VOID:
            case self::TYPE_REFUND:
            case self::TYPE_FISCAL:
            case self::TYPE_FISCAL_REFUND:
                break;
            default:
                Mage::throwException(Mage::helper('sales')->__('Unsupported transaction type "%s".', $txnType));
        }
    }


}