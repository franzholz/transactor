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


use JambageCom\Transactor\Constants\GatewayMode;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


final class GatewayFactory
{
    /**
     * @var \JambageCom\Transactor\Domain\GatewayFactory
     */
    static protected $_instance = null;

    /**
     * @var \JambageCom\Transactor\Domain\GatewayProxy
     */
    static private $gatewayProxyObjects = [];		// An array of proxy objects, each pointing to a registered gateway object

    static private $errorMessage = '';
    static private $errorStack;


    /**
     * Return 'this' as singleton
     *
     * @return GatewayFactory
     */
    static public function getInstance ()
    {
       if (null === self::$_instance)
       {
           self::$_instance = new self;
       }
       return self::$_instance;
    }

    /**
    * clone
    *
    * Kopieren der Instanz von aussen ebenfalls verbieten
    */
    protected function __clone() {}

    /**
    * constructor
    *
    * externe Instanzierung verbieten
    */
    protected function __construct() {}

    /**
    * Registers the given extension as a payment gateway (concrete product). This method will
    * be called by the gateway implementation itself.
    *
    * @param		string		$extensionKey: Extension key of the payment implementation.
    * @return		mixed		Proxied instance of the given extension or false if an error occurred.
    * @access		public
    */
    static public function registerGatewayExtension ($extensionKey)
    {

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
    static public function getGatewayProxyObjects ()
    {
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
    static public function getGatewayProxyObject ($paymentMethod)
    {
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

    static public function clearErrors ()
    {
        self::$errorStack = [];
    }

    static public function addError ($error)
    {
        self::$errorStack[] = $error;
    }

    static public function hasErrors ()
    {
        $result = (count(self::$errorStack) > 0);
    }

    static public function getErrors ()
    {
        return self::$errorStack;
    }
}

