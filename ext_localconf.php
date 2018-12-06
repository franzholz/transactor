<?php
defined('TYPO3_MODE') or die('Access denied.');

define('TRANSACTOR_EXT', $_EXTKEY);

if (!defined ('TRANSACTOR_LANGUAGE_PATH')) {
    define('TRANSACTOR_LANGUAGE_PATH', 'EXT:' . $_EXTKEY . '/Resources/Private/Language/');
}

define('TRANSACTOR_LANGUAGE_PATH_LL', 'LLL:' . TRANSACTOR_LANGUAGE_PATH);

