<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

define('TRANSACTOR_EXT', $_EXTKEY);

if (!defined ('TRANSACTOR_LANGUAGE_PATH')) {
    define('TRANSACTOR_LANGUAGE_PATH', 'EXT:' . $_EXTKEY . '/Resources/Private/Language/');
}

define('TRANSACTOR_LANGUAGE_PATH_LL', 'LLL:' . TRANSACTOR_LANGUAGE_PATH);

if (
    version_compare(TYPO3_version, '6.2.0', '<') &&
    TYPO3_MODE == 'BE' &&
    !defined($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tx_transactor_transactions']['MENU'])
) {
    $tableArray = array('tx_transactor_transactions');

    foreach ($tableArray as $theTable) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['LLFile'][$theTable] = TRANSACTOR_LANGUAGE_PATH . 'locallang.xml';
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['page0'][$theTable] = true;
    }

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tx_transactor_transactions'] = array (
        'default' => array(
            'MENU' => 'm_default',
            'fList' =>  'reference,crdate,gatewayid,currency,amount,state,state_time,message,ext_key',
            'icon' => true
        ),
        'ext' => array(
            'MENU' => 'm_ext',
            'fList' =>  'reference,paymethod_key,paymethod_method,config,user',
            'icon' => true
        )
    );
}

if (
    isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']) &&
    is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'])
) {
    // TYPO3 = 4.5 with livesearch
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'] = array_merge(
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'],
        array(
            'tx_transactor_transactions' => 'tx_transactor_transactions'
        )
    );
}

