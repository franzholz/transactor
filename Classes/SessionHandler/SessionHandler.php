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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\SessionHandler\Typo3SessionHandler;

class SessionHandler extends Typo3SessionHandler implements SingletonInterface
{
    protected $sessionKey = 'TX_TRANSACTOR';

    public static function storeSession($internalKey, $value)
    {
        if ($internalKey == '') {
            return false;
        }
        $session = GeneralUtility::makeInstance(static::class);
        $data = $session->getSessionData();
        $data[$internalKey] = $value;
        $session->setSessionData($data);
        return true;
    }

    public static function readSession($internalKey = '')
    {
        $session = GeneralUtility::makeInstance(static::class);
        $result = $session->getSessionData($internalKey);

        return $result;
    }
}
