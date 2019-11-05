<?php

namespace JambageCom\Transactor\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger <franz@ttproducts.de>
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

class PaymentPreviousApi {

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
            $confScript['extName'] != '' &&
            $confScript['paymentMethod'] != ''
        ) {
            $gatewayExtKey = $confScript['extName'];

            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($gatewayExtKey)) {
                $gatewayFactoryObj = \tx_transactor_gatewayfactory::getInstance();
                $gatewayFactoryObj->registerGatewayExt($gatewayExtKey);
                $paymentMethod = $confScript['paymentMethod'];
                $result =
                    $gatewayFactoryObj->getGatewayProxyObjectByPaymentMethod(
                        $paymentMethod
                    );
            }
        }

        return $result;
    }

    /**
    * Calculates the payment costs
    *
    * @param    array       configuration
    * @param    float       total amount to pay
    * @param    string      ISO3 code of seller
    * @param    string      ISO3 code of buyer
    * @return   float       payment costs
    * @access   public
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
        $extKey = ''
    )
    {
        $PLAINContent = 'The TYPO3 Transactor extension transfers to you an error message coming from extension "' . $extKey . '".';
        $PLAINContent .= chr(13) . implode('|', $fields);
        $HTMLContent = '';

        $result = \JambageCom\Div2007\Utility\MailUtility::send(
            $toEMail,
            $subject,
            $PLAINContent,
            $HTMLContent,
            $fromEMail,
            $fromName
        );

        return $result;
    }
}

