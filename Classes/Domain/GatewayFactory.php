<?php

namespace JambageCom\Transactor\Domain;


/***************************************************************
*
*  Copyright notice
*
*  (c) 2017 Franz Holzinger (franz@ttproducts.de)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
*
* @package 	TYPO3
* @subpackage	transactor
* @author	Franz Holzinger <franz@ttproducts.de>
* @author	Robert Lemke <robert@typo3.org>
*/


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


final class GatewayFactory
{
    /**
     * @var \JambageCom\Transactor\Domain\GatewayFactory
     */
    static protected $instance = null;

    /**
     * @var \JambageCom\Transactor\Domain\GatewayProxy
     */
    static private $gatewayProxyObjects = array();		// An array of proxy objects, each pointing to a registered gateway object

    static private $errorMessage = '';
    static private $errorStack;


    /**
     * Return 'this' as singleton
     *
     * @return GatewayFactory
     */
    static public function getInstance ()
    {
        if (is_null(static::$instance)) {
            self::$instance = new static();
        }
        return static::$instance;
    }


    /**
    * Registers the given extension as a payment gateway (concrete product). This method will
    * be called by the gateway implementation itself.
    *
    * @param		string		$extensionKey: Extension key of the payment implementation.
    * @return		mixed		Proxied instance of the given extension or false if an error occurred.
    * @access		public
    */
    static public function registerGatewayExtension ($extensionKey) {

        if (ExtensionManagementUtility::isLoaded($extensionKey)) {
            $gatewayProxy = GeneralUtility::makeInstance(\JambageCom\Transactor\Domain\GatewayProxy::class);
            $gatewayProxy->init($extensionKey);
            self::$gatewayProxyObjects[$extensionKey] = $gatewayProxy;
            $result = self::$gatewayProxyObjects[$extensionKey];
        } else {
            $result = false;
        }
        return $result;
    }


    /**
    * Returns an array of instantiated payment implementations wrapped by a proxy
    * object. We use this proxy as a smart reference: All function calls and access
    * to variables are redirected to the real gateway object but in some cases
    * some additional operation is done.
    *
    * @return		array		Array of payment implementations (objects)
    * @access		public
    */
    static public function getGatewayProxyObjects () {
        return self::$gatewayProxyObjects;
    }


    /**
    * Returns instance of the payment implementations (wrapped by a proxy
    * object) which offers the specified payment method.
    *
    * @param		string		$paymentMethod: Payment method key
    * @return		mixed		Reference to payment proxy object or false if no matching object was found
    * @access		public
    */
    static public function getGatewayProxyObject ($paymentMethod) {
        $result = false;

        if (is_array(self::$gatewayProxyObjects)) {
            foreach (self::$gatewayProxyObjects as $extensionKey => $gatewayProxyObject) {
                $paymentMethodsArray = $gatewayProxyObject->getAvailablePaymentMethods();

                if (
                    isset($paymentMethodsArray) &&
                    is_array($paymentMethodsArray)
                ) {
                    $prefix = substr($extensionKey, strlen(TRANSACTOR_EXT . '_'));
                    $key = $prefix . '_' . $paymentMethod;
                    if (
                        isset($paymentMethodsArray[$key]) ||
                        isset($paymentMethodsArray[$paymentMethod])
                    ) {
                        $result = $gatewayProxyObject;
                        break;
                    }
                } else {
                    $errors = $gatewayProxyObject->getErrors();
                    self::addError(
                        'JambageCom\Transactor\Domain\GatewayFactory::getGatewayProxyObject ' . $extensionKey
                    );
                    if (
                        isset($errors) &&
                        is_array($errors)
                    ) {
                        foreach ($errors as $error) {
                            self::addError($error);
                        }
                    }
                }
            }
        }

        return $result;
    }


    /**
    * Returns an array of transaction records which match the given extension key
    * and optionally the given extension reference string and or booking status.
    * Use this function instead accessing the transaction records directly.
    *
    * @param		string		$extensionKey: Extension key
    * @param		int			$gatewayid: (optional) Filter by gateway id
    * @param		string		$reference: (optional) Filter by reference
    * @param		string		$state: (optional) Filter by transaction state
    * @return		array		Array of transaction records, false if no records where found or an error occurred.
    * @access		public
    */
    static public function getTransactions (
        $extensionKey,
        $gatewayid = null,
        $reference = null,
        $state = null
    ) {
        $transactionsArray = false;

        $additionalWhere = '';
        $additionalWhere .= (isset ($gatewayid)) ? ' AND gatewayid="' . $gatewayid . '"' : '';
        $additionalWhere .= (isset ($invoiceid)) ? ' AND reference="' . $reference . '"' : '';
        $additionalWhere .= (isset ($state)) ? ' AND state="' . $state . '"' : '';

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_transactor_transactions',
            'ext_key="' . $extensionKey . '"' . $additionalWhere,
            '',
            'crdate DESC'
        );

        if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
            $transactionsArray = array();
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                $row['user'] = self::field2array($row['user']);
                $transactionsArray[$row['uid']] = $row;
            }
        }
        return $transactionsArray;
    }


    /**
    * Returns a single transaction record which matches the given uid
    *
    * @param		integer		$uid: UID of the transaction
    * @access		public
    */
    static public function getTransactionByUid ($uid) {

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_transactor_transactions',
            'uid=' . $uid
        );

        if (!$res || !$GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
            return false;
        }

        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $row['user'] = self::field2array($row['user']);

        return $row;
    }


    /**
    * Return an array with either a single value or an unserialized array
    *
    * @param		mixed		$field: some value from a database field
    * @return 	array
    * @access		private
    */
    static private function field2array ($field) {
        if (!$field = @unserialize ($field)) {
            $field = array($field);
        }
        return $field;
    }


    static public function clearErrors () {
        self::$errorStack = array();
    }


    static public function addError ($error) {
        self::$errorStack[] = $error;
    }


    static public function hasErrors () {
        $result = (count(self::$errorStack) > 0);
    }


    static public function getErrors () {
        return self::$errorStack;
    }
}

