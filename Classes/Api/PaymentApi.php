<?php

declare(strict_types=1);

namespace JambageCom\Transactor\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2023 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the transactor (Transactor Payment) extension.
 *
 * Transactor API functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage transactor
 *
 */

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

use JambageCom\Div2007\Api\PhpHelper;
use JambageCom\Div2007\Utility\MailUtility;

use JambageCom\Transactor\Constants\Action;
use JambageCom\Transactor\Constants\Field;
use JambageCom\Transactor\Domain\GatewayFactory;
use JambageCom\Transactor\Domain\GatewayProxy;

class PaymentApi
{
    static public function getTransactorConf ($gatewayExtensionKey, $key = '')
    {
        $transactorConf = [];
        $result = '';

        $transactorConf = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get($gatewayExtensionKey);

        if (
            $key != ''
        ) {
            if (isset($transactorConf[$key])) {
                $result = $transactorConf[$key];
            }
        } else {
            $result = $transactorConf;
        }

        return $result;
    }

    /**
    * @param        string      $extensionKey: Extension key
    * @param        boolean     $mergeConf: if the conf of the extension shall be merged
    * @param        array       $conf: configuration array of the extension
    * returns the configuration array
    */
    static public function getConf (
        $extensionKey = '',
        $mergeConf = true,
        array $conf = []
    )
    {
        $result = [];
        $result = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('transactor');

        if (
            $extensionKey != ''
        ) {
            $extManagerConf = static::getTransactorConf($extensionKey);
        }

        if ($mergeConf && is_array($conf)) {
            if (
                isset($extManagerConf) &&
                is_array($extManagerConf)
            ) {
                if (empty($conf)) {
                    $result = array_merge($result, $extManagerConf);
                } else {
                    $result = array_merge($result, $conf, $extManagerConf);
                }
            }
        } else if (
            isset($extManagerConf) &&
            is_array($extManagerConf)
        ) {
            $result = $extManagerConf;
        }
        return $result;
    }

    /**
    * returns the gateway proxy object
    */
    static public function getGatewayProxyObject (
        ServerRequestInterface $request,
        $confScript
    )
    {
        $result = false;

        if (
            is_array($confScript) &&
            !empty($confScript['extName']) &&
            !empty($confScript['paymentMethod'])
        ) {
            $gatewayExtensionKey = $confScript['extName'];

            if (
                ExtensionManagementUtility::isLoaded(
                    $gatewayExtensionKey
                )
            ) {
                $gatewayFactoryObj =
                    GatewayFactory::getInstance();
                $gatewayFactoryObj->registerGatewayExtension($request, $gatewayExtensionKey);
                $paymentMethod = $confScript['paymentMethod'];
                $gatewayProxyObj =
                    $gatewayFactoryObj->getGatewayProxyObject(
                        $paymentMethod
                    );

                if (is_object($gatewayProxyObj)) {
                    if (
                        $gatewayProxyObj instanceof GatewayProxy
                    ) {
                        $gatewayProxyObj->init($request, $gatewayExtensionKey);
                        $result = $gatewayProxyObj;
                    } else {
                        throw new \RuntimeException(
                            'Error in transactor: Gateway object class "' .
                            get_class($gatewayProxyObj) .
                            '" must be an instance of  "JambageCom\Transactor\Domain\GatewayProxy"',
                            50200
                        );
                    }
                }
            }
        }
        return $result;
    }

    /**
    * returns the gateway proxy object
    */
    static public function getGatewayProxyObjectForExtension (
        ServerRequestInterface $request,
        $gatewayExtensionKey,
        $paymentMethod
    )
    {
        $gatewayProxyObj = null;

        if (
            ExtensionManagementUtility::isLoaded(
                $gatewayExtensionKey
            )
        ) {
            $gatewayFactoryObj =
                GatewayFactory::getInstance();
            $gatewayFactoryObj->
                registerGatewayExtension(
                    $request,
                    $gatewayExtensionKey
                );
            $gatewayProxyObj =
                $gatewayFactoryObj->getGatewayProxyObject(
                    $paymentMethod
                );
        }

        return $gatewayProxyObj;
    }


    /**
    * Returns an array of transaction records which match the given extension key
    * and optionally the given extension reference string and or booking status.
    * Use this function instead accessing the transaction records directly.
    *
    * @param        string      $extensionKey: Extension key of extension
    *                           which calls the transactor library
    * @param        int         $gatewayid: (optional) Filter by gateway id
    * @param        string      $reference: (optional) Filter by reference
    * @param        string      $state: (optional) Filter by transaction state
    * @param        string      $tablename: (optional) Name of the transactor table
    * @return       array       Array of transaction records, false if no records where found or an error occurred.
    * @access       public
    */
    static public function getTransactions (
        $extensionKey = null,
        $gatewayid = null,
        $reference = null,
        $state = null,
        $tablename = 'tx_transactor_transactions'
    )
    {
        $transactionsArray = false;

        $where = '1=1';
        $where .=
            (
                !empty($extensionKey) ?
                    ' AND ext_key=' .
                    $GLOBALS['TYPO3_DB']->fullQuoteStr(
                        $extensionKey,
                        $tablename
                    ) :
                    ''
            );

        $where .=
            (
                !empty($gatewayid) ?
                    ' AND gatewayid=' .
                    $GLOBALS['TYPO3_DB']->fullQuoteStr(
                        $gatewayid,
                        $tablename
                    ) :
                    ''
            );

        $where .=
            (
                !empty($reference) ?
                    ' AND reference=' .
                $GLOBALS['TYPO3_DB']->fullQuoteStr(
                    $reference,
                    $tablename
                ) :
                ''
            );

        $where .=
            (
                !empty($state) ?
                    ' AND state=' .
                $GLOBALS['TYPO3_DB']->fullQuoteStr(
                    $state,
                    $tablename
                ) :
                ''
            );
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            $tablename,
            $where,
            '',
            'crdate DESC'
        );

        if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
            $transactionsArray = [];
            $phpHelper = GeneralUtility::makeInstance(PhpHelper::class);
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if (!empty($row['user'])) {
                    $userObj =
                        $phpHelper->json_decode_special(
                            stripslashes($row['user']),
                            null,
                            12,
                            JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
                        );
                    $row['user'] = (array) $userObj;
                }
                $transactionsArray[$row['uid']] = $row;
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
        }

        return $transactionsArray;
    }

    /**
    * Returns an array of transaction records which match the given extension key
    * and optionally the given extension reference string and or booking status.
    * Use this function instead accessing the transaction records directly.
    *
    * @param        int         $uid: uid of the transaction record
    * @param        string      $message: Message to write
    * @param        string      $state: transaction state
    * @param        integer     $time: current unix time
    * @param        string      $user: (optional) gateway specific texts
    * @param        string      $tablename: (optional) Name of the transactor table
    * @return       reference to the database result
    * @access       public
    */
    static public function updateMessageState (
        $uid,
        $message,
        $state,
        $time,
        $user = '',
        $tablename = 'tx_transactor_transactions'
    )
    {
        $fields = [];
        $fields['message'] = $message;
        $fields['state'] = $state;
        $fields['state_time'] = $time;
        if (!empty($user)) {
            $fields['user'] =
                trim(
                    $GLOBALS['TYPO3_DB']->fullQuoteStr(
                        json_encode($user),
                        $tablename
                    ),
                    "\n\r\'"
                );

                // $fields['user'] = json_encode($user);
        }

        $dbResult =
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                $tablename,
                'uid=' . $uid,
                $fields
            );
        return $dbResult;
    }

    /**
    * Returns a single transaction record which matches the given uid
    *
    * @param        integer     $uid: UID of the transaction
    * @param        string      $tablename: (optional) Name of the transactor table
    * @access       public
    */
    static public function getTransactionByUid (
        $uid,
        $tablename = 'tx_transactor_transactions'
    )
    {
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            $tablename,
            'uid=' . $uid
        );

        if (!$res || !$GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
            return false;
        }

        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        // $row['user'] = json_decode($row['user']);
        if (!empty($row['user'])) {
            $phpHelper = GeneralUtility::makeInstance(PhpHelper::class);
            $row['user'] =
                $phpHelper->json_decode_special(
                    stripslashes($row['user']),
                    null,
                    12,
                    JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
                );
        }

        return $row;
    }

    /**
    * Returns a single transaction record which matches the given gateway id
    *
    * @param        integer     $uid: UID of the transaction
    * @param        string      $tablename: (optional) Name of the transactor table
    * @access       public
    */
    static public function getTransactionByGatewayId (
        $id,
        $tablename = 'tx_transactor_transactions'
    )
    {
        $row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            '*',
            $tablename,
            'gatewayid LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($id, $tablename)
        );

        if (
            is_array($row) &&
            !empty($row['user'])
        ) {
            $phpHelper = GeneralUtility::makeInstance(PhpHelper::class);
            $row['user'] =
                $phpHelper->json_decode_special(
                    stripslashes($row['user']),
                    null,
                    12,
                    JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
                );
        }

        return $row;
    }


    /**
    * Calculates the payment costs
    *
    * @param	array		configuration
    * @param	float		total amount to pay
    * @param	string		ISO3 code of seller
    * @param	string		ISO3 code of buyer
    * @return	float		payment costs
    * @access	public
    */
    static public function getCosts (
        $confScript,
        $amount,
        $iso3Seller,
        $iso3Buyer
    )
    {
        $gatewayProxyObject = self::getGatewayProxyObject($confScript);
        $costs = $gatewayProxyObject->getCosts(
            $confScript,
            $amount,
            $iso3Seller,
            $iso3Buyer
        );
        return $costs;
    }

    static public function sendErrorEmail (
        $fromEMail,
        $fromName,
        $toEMail,
        $subject,
        array $fields,
        $extensionKey = ''
    )
    {
        $PLAINContent = 'The TYPO3 Transactor extension sends you an error message coming from extension "' . $extensionKey . '".';
        $PLAINContent .= chr(13) . implode('|', $fields);
        $HTMLContent = '';

        MailUtility::send(
            $toEMail,
            $subject,
            $PLAINContent,
            $HTMLContent,
            $fromEMail,
            $fromName
        );
    }

    static public function getRequestId ($reference)
    {
        $requestId = 0;
        if (!empty($reference)) {
            $position = strpos($reference, '#');
            $requestId = substr($reference, $position + 1);
        }
        return $requestId;
    }

    static public function generateReferenceUid ($gatewayKey, $requestId)
    {
        $result = $gatewayKey . '#' . $requestId;
        return $result;
    }

    static public function storeData (
        FrontendUserAuthentication $frontendUserAuthentification,
        $type,
        $data
    )
    {
        $key = 'transactor';
        $sessionData = $frontendUserAuthentification->getKey('ses', $key);
        $sessionData[$type] = $data;
        $frontendUserAuthentification->setKey('ses', $key, $sessionData);
        $frontendUserAuthentification->storeSessionData();
    }

    static public function getStoredData (
        FrontendUserAuthentication $frontendUserAuthentification,
        $type
    )
    {
        $key = 'transactor';
        $sessionData = $frontendUserAuthentification->getKey('ses', $key);
        return $sessionData[$type] ?? null;
    }

    static public function storeReferenceUid (
        FrontendUserAuthentication $frontendUserAuthentification,
        string $referenceUid
    )
    {
        static::storeData(
            $frontendUserAuthentification,
            'referenceUid',
            $referenceUid
        );
    }

    static public function getStoredReferenceUid (
        FrontendUserAuthentication $frontendUserAuthentification
    )
    {
        return static::getStoredData($frontendUserAuthentification, 'referenceUid');
    }

    static public function storeInit (
        FrontendUserAuthentication $frontendUserAuthentification,
        int $action, // Action constant
        string $paymentMethod,
        string $callingExtensionKey,
        string $templateFilename = '',
        int $orderUid = 0,
        string $orderNumber = '0',
        string $currency = 'EUR',
        array $conf = [],
        array $basket = [],
        array $extraData = []
    )
    {
        $data = [
            'action' => $action,
            'paymentMethod' => $paymentMethod,
            'callingExtensionKey' => $callingExtensionKey,
            'templateFilename' => $templateFilename,
            'orderUid' => $orderUid,
            'orderNumber' => $orderNumber,
            'currency' => $currency,
            'conf' => $conf,
            'basket' => $basket,
            'extraData' => $extraData
        ];

        foreach ($data as $key => $value) {
            static::storeData($frontendUserAuthentification, $key, $value);
        }
    }

    static public function getStoredInit (
        &$action, // Action constant
        &$paymentMethod,
        &$callingExtensionKey,
        &$templateFilename,
        &$orderUid,
        &$orderNumber,
        &$currency,
        &$conf,
        &$basket,
        &$extraData,
        FrontendUserAuthentication $frontendUserAuthentification
    ) {
        $data = [
            'action' => &$action,
            'paymentMethod' => &$paymentMethod,
            'callingExtensionKey' => &$callingExtensionKey,
            'templateFilename' => &$templateFilename,
            'orderUid' => &$orderUid,
            'orderNumber' => &$orderNumber,
            'currency' => &$currency,
            'conf' => &$conf,
            'basket' => &$basket,
            'extraData' => &$extraData
        ];


        foreach ($data as $key => &$value) {
            $value = static::getStoredData($frontendUserAuthentification, $key);
        }
    }

    static public function convertToTransactorBasket (array $itemArray, array $variantFields)
    {
        $result = [];
        // loop over all items in the basket indexed by sorting text
        foreach ($itemArray as $sort => $actItemArray) {
            foreach ($actItemArray as $k1 => $actItem) {
                $row = $actItem['rec'];
                if (!$row) {	// avoid bug with missing row
                    continue;
                }
                $record = [];
                $variants = [];
                foreach ($variantFields as $field) {
                    if (
                        isset($row[$field]) &&
                        $row[$field] != ''
                    ) {
                        $variants[] = $row[$field];
                    }
                }

                $record = $row;
                $record[Field::VARIANT] = implode(' ', $variants);
                $record[Field::QUANTITY] = $actItem['count'];
                $record[Field::PRICE_TAX] = $row['pricetax'];
                $record[Field::PRICE_NOTAX] = $row['pricenotax'];

                $record[Field::TAX_PERCENTAGE] = $row['taxperc'];
                $record[Field::NAME] = $row['title'];
                $result[] = $record;
            }
        }

        return $result;
    }
}

