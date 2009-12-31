<?php
// $Id$

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_transactor_transactions'] = Array (
	'ctrl' => $TCA['tx_transactor_transactions']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'uid'
	),
	'columns' => Array (
		'uid' => Array (
			'label' => 'LLL:EXT:transactor/locallang_db.php:tx_transactor_transactions_uid',
			'config' => Array (
				'type' => 'none',
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => '')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);

?>