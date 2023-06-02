<?php
defined('TYPO3') || die('Access denied.');

// ******************************************************************
// transactions table
// ******************************************************************
$result = [
    'ctrl' => [
        'title' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xlf:tx_transactor_transactions',
        'label' => 'reference',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY crdate',
        'dividers2tabs' => true,
        'iconfile' => 'EXT:' . TRANSACTOR_EXT . '/ext_icon.gif',
        'searchFields' => 'uid,reference,orderuid,message,user',
    ],
    'interface' => [
        'showRecordFieldList' => 'uid,reference,gatewayid,orderuid,currency,amount,state,state_time,message,ext_key,paymethod_key,paymethod_method,config,config_ext,user'
    ],
    'columns' => [
        'uid' => [
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xlf:tx_transactor_transactions_uid',
            'config' => [
                'type' => 'none',
            ]
        ],
        'crdate' => [
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xlf:crdate',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0'
            ]
        ],
        'reference' => [
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.reference',
            'config' => [
                'type' => 'input',
                'size' => '100',
                'max' => '255'
            ]
        ],
        'gatewayid' => [ // gateway internal transaction id
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.gatewayid',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255'
            ]
        ],
        'orderuid' => [
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.orderuid',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '255',
                'readOnly' => '1'
            ]
        ],
        'currency' => [
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xlf:tx_transactor_transactions.currency',
            'config' => [
                'type' => 'input',
                'size' => '3',
                'max' => '3'
            ]
        ],
        'amount' => [
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.amount',
            'config' => [
                'type' => 'input',
                'size' => '12',
                'eval' => 'trim,double2',
                'max' => '20'
            ]
        ],
        'state' => [
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xlf:tx_transactor_transactions.state',
            'config' => [
                'type' => 'input',
                'size' => '3',
                'eval' => 'int',
                'max' => '3'
            ]
        ],
        'state_time' => [
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.state_time',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0'
            ]
        ],
        'message' => [
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.message',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '255'
            ]
        ],
        'ext_key' => [
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.ext_key',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '100'
            ]
        ],
        'paymethod_key' => [
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.paymethod_key',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '100'
            ]
        ],
        'paymethod_method' => [
            'exclude' => 0,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.paymethod_method',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'max' => '100'
            ]
        ],
        'config' => [
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.config',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '15'
            ]
        ],
        'config_ext' => [
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL .  'locallang_db.xlf:tx_transactor_transactions.config_ext',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '15'
            ]
        ],
        'user' => [
            'exclude' => 1,
            'label' => TRANSACTOR_LANGUAGE_PATH_LL . 'locallang_db.xlf:tx_transactor_transactions.user',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5'
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'reference,gatewayid,orderuid,currency,amount,state,state_time,message,ext_key,paymethod_key,paymethod_method, config, config_ext,user']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];


return $result;
