<?php

namespace JambageCom\Transactor\Constants;

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

 /**
 * Constants for the gateway modes
 */
class Field
{
    const NAME           = 'transactor_name';           // name of a given record
    const PRICE_TAX      = 'transactor_price_tax';      // price including tax of a given record
    const QUANTITY       = 'transactor_quantity';      // number of a given recordt
    const TAX_PERCENTAGE = 'transactor_tax_percentage'; // tax percentage of a given record
    const VARIANT        = 'transactor_variant';        // variants separated by blanks
}

