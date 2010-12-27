<?php
// $Id$

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_transactor_transactions'] = Array (
	'ctrl' => $TCA['tx_transactor_transactions']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'uid,reference,gatewayid,currency,amount,state,state_time,message,ext_key,paymethod_key,paymethod_method,config,user'
	),
	'columns' => Array (
		'uid' => Array (
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions_uid',
			'config' => Array (
				'type' => 'none',
			)
		),
		'crdate' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:crdate',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'gatewayid' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.gatewayid',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'reference' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.reference',
			'config' => Array (
				'type' => 'input',
				'size' => '100',
				'max' => '256'
			)
		),
		'currency' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.currency',
			'config' => Array (
				'type' => 'input',
				'size' => '3',
				'max' => '3'
			)
		),
		'amount' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.amount',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'int',
				'max' => '64'
			)
		),
		'state' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.state',
			'config' => Array (
				'type' => 'input',
				'size' => '3',
				'eval' => 'int',
				'max' => '3'
			)
		),
		'state_time' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.state_time',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'message' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.message',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'ext_key' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.ext_key',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '100'
			)
		),
		'paymethod_key' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.paymethod_key',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '100'
			)
		),
		'paymethod_method' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.paymethod_method',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '100'
			)
		),
		'config' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.config',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '15'
			)
		),
		'user' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:transactor/locallang_db.xml:tx_transactor_transactions.user',
			'config' => Array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5'
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'reference,gatewayid,currency,amount,state,state_time,message,ext_key,paymethod_key,paymethod_method,config,user')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);

?>