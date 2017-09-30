<?php

namespace JambageCom\Transactor\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger (franz@ttproducts.de)
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
 *
 * language object
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage transactor
 *
 *
 */



class Localization extends \JambageCom\Div2007\Base\LocalisationBase implements \TYPO3\CMS\Core\SingletonInterface
{
    public function init1 ($pObj, $cObj, $conf, $scriptRelPath) {

        $this->init(
            $cObj,
            TRANSACTOR_EXT,
            $conf,
            $scriptRelPath
        );
        $this->scriptRelPath = '';

        // keep previsous language settings if available
        if (
            isset($pObj->LOCAL_LANG) &&
            is_array($pObj->LOCAL_LANG)
        ) {
            $this->setLocallang($pObj->LOCAL_LANG);
        }

        if (
            isset($pObj->LOCAL_LANG_charset) &&
            is_array($pObj->LOCAL_LANG_charset)
        ) {
            $this->setLocallangCharset($pObj->LOCAL_LANG_charset);
        }

        if (isset($pObj->LOCAL_LANG_loaded)) {
            $this->setLocallangLoaded($pObj->LOCAL_LANG_loaded);
        }

        return true;
    }
}

