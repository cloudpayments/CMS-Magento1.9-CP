<?php
$installer = $this;

$installer->startSetup();

if (version_compare(Mage::getVersion(), '1.4.2') > 0) {

// Required tables
    $statusTable = $installer->getTable('sales/order_status');
    $statusStateTable = $installer->getTable('sales/order_status_state');

// Insert statuses
    $installer->getConnection()->insertArray(
        $statusTable,
        array(
            'status',
            'label'
        ),
        array(
            array('status' => 'authorized_cloudpayments', 'label' => 'Authorized CloudPayments'),
        )
    );

// Insert states and mapping of statuses to states
    $installer->getConnection()->insertArray(
        $statusStateTable,
        array(
            'status',
            'state',
            'is_default'
        ),
        array(
            array(
                'status' => 'authorized_cloudpayments',
                'state' => 'new',
                'is_default' => 0
            ),
        )
    );
}

$installer->endSetup();