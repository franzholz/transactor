<?php

if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

if (
    version_compare(TYPO3_version, '6.2.0', '<') &&
    (TYPO3_MODE == 'BE')
) {
    $GLOBALS['TCA']['tx_transactor_transactions'] = array(
        'ctrl' => array(
            'title' => 'LLL:EXT:transactor/Resources/Private/Language/locallang_db.xml:tx_transactor_transactions',
            'label' => 'reference',
            'crdate' => 'crdate',
            'default_sortby' => 'ORDER BY crdate',
            'dividers2tabs' => true,
            'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
            'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif',
            'searchFields' => 'uid,reference,orderuid',
        ),
    );
}

