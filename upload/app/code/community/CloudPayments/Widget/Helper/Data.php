<?php

class CloudPayments_Widget_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Writes information to log file
     *
     * @param $message - string or array of strings
     * @return bool
     */
    public function addLog($message)
    {
        if ($this->isLogEnabled()) {
            if (is_array($message)) {
                $message = print_r($message, true);
            }
            Mage::log($message, Zend_Log::DEBUG, 'cloudpayments.log', true);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isLogEnabled()
    {
        return (bool)Mage::getStoreConfig('payment/cloudpayments/debug');
    }
}
