<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE' &&
!defined($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tx_transactor_transactions']['MENU'])) {
	$tableArray = array('tx_transactor_transactions');

	foreach ($tableArray as $theTable)	{
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['LLFile'][$theTable] = 'EXT:transactor/locallang.xml';
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['page0'][$theTable] = TRUE;
	}

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tx_transactor_transactions'] = array (
		'default' => array(
			'MENU' => 'm_default',
			'fList' =>  'reference,crdate,gatewayid,currency,amount,state,state_time,message,ext_key',
			'icon' => TRUE
		),
		'ext' => array(
			'MENU' => 'm_ext',
			'fList' =>  'reference,paymethod_key,paymethod_method,config,user',
			'icon' => TRUE
		)
	);
}


?>