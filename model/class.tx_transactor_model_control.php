<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
* Part of the transactor (Payment Transactor API) extension.
*
* @author	Franz Holzinger <franz@ttproducts.de>
* @maintainer	Franz Holzinger <franz@ttproducts.de>
* @package TYPO3
* @subpackage transactor
*
*/

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_transactor_model_control {

    static private $prefixId = '';
    static private $piVars = [];
    static private $callingExtensionVar = 'calling_extension';
    static private $orderVar = 'order';
    static private $returiVar = 'returi';
    static private $faillinkVar = 'faillink';
    static private $successlinkVar = 'successlink';


    static public function getCallingExtensionVar () {
        return self::$callingExtensionVar;
    }

    static public function getOrderVar () {
        return self::$orderVar;
    }

    static public function getReturiVar () {
        return self::$returiVar;
    }

    static public function getFaillinkVar () {
        return self::$faillinkVar;
    }

    static public function getSuccesslinkVar () {
        return self::$successlinkVar;
    }

    static public function setPrefixId ($prefixId) {
        self::$prefixId = $prefixId;
    }

    static public function getPrefixId () {
        return self::$prefixId;
    }

    static public function getPiVars () {
        if (
            self::$prefixId &&
            !isset(self::$piVars[self::$prefixId])
        ) {
            self::$piVars = GeneralUtility::_GPmerged(self::$prefixId);
        }
        $result = self::$piVars;
        return $result;
    }
}

