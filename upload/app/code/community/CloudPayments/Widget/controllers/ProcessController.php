<?php

class CloudPayments_Widget_ProcessController extends Mage_Core_Controller_Front_Action
{
    /**
     * Pay action
     */
    public function payAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

}