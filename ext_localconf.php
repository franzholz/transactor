<?php
defined('TYPO3_MODE') || die('Access denied.');

define('TRANSACTOR_EXT', 'transactor');

if (!defined ('TRANSACTOR_LANGUAGE_PATH')) {
    define('TRANSACTOR_LANGUAGE_PATH', 'EXT:' . TRANSACTOR_EXT . '/Resources/Private/Language/');
}

define('TRANSACTOR_LANGUAGE_PATH_LL', 'LLL:' . TRANSACTOR_LANGUAGE_PATH);

