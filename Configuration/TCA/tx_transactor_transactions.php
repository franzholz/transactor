<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

// ******************************************************************
// transactions table
// ******************************************************************
$result = array (
    'ctrl' => array (
        'title' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xml:tx_transactor_transactions',
        'label' => 'reference',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY crdate',
        'dividers2tabs' => true,
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('transactor') . 'ext_icon.gif',
        'searchFields' => 'uid,reference,orderuid,message,user',
    ),
    'interface' => array (
        'showRecordFieldList' => 'uid,reference,gatewayid,orderuid,currency,amount,state,state_time,message,ext_key,paymethod_key,paymethod_method,config,user'
    ),
    'columns' => array (
        'uid' => array (
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xml:tx_transactor_transactions_uid',
            'config' => array (
                'type' => 'none',
            )
        ),
        'crdate' => array (
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xml:crdate',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0'
            )
        ),
        'reference' => array (
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.reference',
            'config' => array (
                'type' => 'input',
                'size' => '100',
                'max' => '256'
            )
        ),
        'gatewayid' => array ( // gateway internal transaction id
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.gatewayid',
            'config' => array (
                'type' => 'input',
                'size' => '40',
                'max' => '256'
            )
        ),
        'orderuid' => array (
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.orderuid',
            'config' => array (
                'type' => 'input',
                'size' => '20',
                'readOnly' => '1',
            )
        ),
        'currency' => array (
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xml:tx_transactor_transactions.currency',
            'config' => array (
                'type' => 'input',
                'size' => '3',
                'max' => '3'
            )
        ),
        'amount' => array (
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.amount',
            'config' => array (
                'type' => 'input',
                'size' => '12',
                'eval' => 'trim,double2',
                'max' => '20'
            )
        ),
        'state' => array (
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xml:tx_transactor_transactions.state',
            'config' => array (
                'type' => 'input',
                'size' => '3',
                'eval' => 'int',
                'max' => '3'
            )
        ),
        'state_time' => array (
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.state_time',
            'config' => array (
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0'
            )
        ),
        'message' => array (
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.message',
            'config' => array (
                'type' => 'input',
                'size' => '40',
                'max' => '256'
            )
        ),
        'ext_key' => array (
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.ext_key',
            'config' => array (
                'type' => 'input',
                'size' => '40',
                'max' => '100'
            )
        ),
        'paymethod_key' => array (
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.paymethod_key',
            'config' => array (
                'type' => 'input',
                'size' => '40',
                'max' => '100'
            )
        ),
        'paymethod_method' => array (
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.paymethod_method',
            'config' => array (
                'type' => 'input',
                'size' => '40',
                'max' => '100'
            )
        ),
        'config' => array (
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xml:tx_transactor_transactions.config',
            'config' => array (
                'type' => 'text',
                'cols' => '48',
                'rows' => '15'
            )
        ),
        'user' => array (
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xml:tx_transactor_transactions.user',
            'config' => array (
                'type' => 'text',
                'cols' => '48',
                'rows' => '5'
            )
        ),
    ),
    'types' => array (
        '0' => array('showitem' => 'reference,gatewayid,orderuid,currency,amount,state,state_time,message,ext_key,paymethod_key,paymethod_method,config,user')
    ),
    'palettes' => array (
        '1' => array('showitem' => '')
    )
);


return $result;
