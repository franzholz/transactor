<?php

declare(strict_types=1);

namespace JambageCom\Transactor\SessionHandler;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JambageCom\Div2007\SessionHandler\Typo3SessionHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SessionHandler extends Typo3SessionHandler implements SingletonInterface
{
    protected $sessionKey = TT_PRODUCTS_SESSION;

    public static function storeSession($internalKey, $value)
    {
    debug ($internalKey, 'storeSession $internalKey');
    debug ($value, 'storeSession $value');
        if ($internalKey == '') {
            return false;
        }
        $session = GeneralUtility::makeInstance(static::class);
        $data = $session->getSessionData();
        $data[$internalKey] = $value;
        $session->setSessionData($data);
        $data = $session->getSessionData();
    }

    public static function readSession($internalKey = '')
    {
    debug ($internalKey, 'readSession $internalKey');
        $result = [];
        $session = GeneralUtility::makeInstance(static::class);
        $result = $session->getSessionData($internalKey);
    debug ($result, 'readSession $result');

        return $result;
    }
}
