<?php

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

use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;


class PaymentApi
{
    static public function getTransactorConf ($gatewayExtensionKey, $key = '') 
    {
        $transactorConf = [];
        $result = '';

        $transactorConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get($gatewayExtensionKey);

        if (
            $key != '' &&
            isset($transactorConf[$key])
        ) {
            $result = $transactorConf[$key];
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
        $result = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get('transactor');

        if (
            $extensionKey != '' &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey])
        ) {
            $extManagerConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
            )->get($extensionKey);
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
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(
                    $gatewayExtensionKey
                )
            ) {
                $gatewayFactoryObj =
                    \JambageCom\Transactor\Domain\GatewayFactory::getInstance();
                $gatewayFactoryObj->registerGatewayExtension($gatewayExtensionKey);
                $paymentMethod = $confScript['paymentMethod'];
                $gatewayProxyObj =
                    $gatewayFactoryObj->getGatewayProxyObject(
                        $paymentMethod
                    );

                if (is_object($gatewayProxyObj)) {
                    if (
                        $gatewayProxyObj instanceof \JambageCom\Transactor\Domain\GatewayProxy
                    ) {
                        $gatewayProxyObj->init($gatewayExtensionKey);
                        if (!empty($confScript['checkoutUrl'])) {
                            $gatewayProxyObj->setCheckoutURI($confScript['checkoutUrl']);
                        }
                        if (!empty($confScript['captureUrl'])) {
                            $gatewayProxyObj->setCaptureURI($confScript['captureUrl']);
                        }
                        $result = $gatewayProxyObj;
                    } else {
                        throw new \RuntimeException('Error in transactor: Gateway object class "' . get_class($gatewayProxyObj) . '" must be an instance of  "JambageCom\Transactor\Domain\GatewayProxy"', 50200);
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
        $gatewayExtensionKey,
        $paymentMethod
    )
    {
        $gatewayProxyObj = null;

        if (
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded(
                $gatewayExtensionKey
            )
        ) {
            $gatewayFactoryObj =
                \JambageCom\Transactor\Domain\GatewayFactory::getInstance();
            $gatewayFactoryObj->registerGatewayExtension($gatewayExtensionKey);
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
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                $row['user'] = json_decode($row['user']);
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
        $fields['user'] = $GLOBALS['TYPO3_DB']->fullQuoteStr(json_encode($user), $tablename);

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
        $row['user'] = json_decode($row['user']);

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
            isset($row['user'])
        ) {
            $row['user'] = json_decode($row['user']);
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

        \JambageCom\Div2007\Utility\MailUtility::send(
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
        $position = strpos($reference, '#');
        $requestId = substr($reference, $position + 1);
        return $requestId;
    }

    static public function generateReferenceUid ($gatewayKey, $requestId)
    {
        $result = $gatewayKey . '#' . $requestId;
        return $result;
    }

    static public function storeData ($type, $data)
    {
        $key = 'transactor';
        $sessionData = static::getFrontendUser()->getKey('ses', $key);
        $sessionData[$type] = $data;
        static::getFrontendUser()->setKey('ses', $key, $sessionData);
        static::getFrontendUser()->storeSessionData();
    }

    static public function getStoredData ($type)
    {
        $key = 'transactor';
        $sessionData = static::getFrontendUser()->getKey('ses', $key);
        return $sessionData[$type] ?? null;
    }

    static public function storeReferenceUid ($referenceUid)
    {
        static::storeData('referenceUid', $referenceUid);
    }

    static public function getStoredReferenceUid ()
    {
        return static::getStoredData('referenceUid');
    }

    /**
     * @return FrontendUserAuthentication
     */
    static protected function getFrontendUser(): FrontendUserAuthentication
    {
        return static::getTypoScriptFrontendController()->fe_user;
    }

    /**
     * @return TypoScriptFrontendController|null
     */
    static protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'] ?? null;
    }
}

