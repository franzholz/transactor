<?php
defined('TYPO3') || die('Access denied.');

if (!defined ('TRANSACTOR_EXT')) {
    define('TRANSACTOR_EXT', 'transactor');
}

if (!defined ('TRANSACTOR_LANGUAGE_PATH')) {
    define('TRANSACTOR_LANGUAGE_PATH', 'EXT:' . TRANSACTOR_EXT . '/Resources/Private/Language/');
}

if (!defined('TRANSACTOR_LANGUAGE_PATH_LL')) {
    define('TRANSACTOR_LANGUAGE_PATH_LL', 'LLL:' . TRANSACTOR_LANGUAGE_PATH);
}

