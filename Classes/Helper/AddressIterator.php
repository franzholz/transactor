<?php

declare(strict_types=1);

namespace JambageCom\Transactor\Helper;


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

use JambageCom\Transactor\Api\Address;


class AddressIterator extends \FilterIterator {

    public function accept(): bool
    {
        $key = trim(parent::key());
        $function = 'set' . ucwords($key, '_');
        $function = str_replace('_', '', $function);
        $result = method_exists(Address::class, $function);
        return $result;
    }


    public function getObject(Address $object): Address
    {
        foreach ($this as $key => $value) {
            $key = trim($key);
            $function = 'set' . ucwords($key, '_');
            $function = str_replace('_', '', $function);
            $object->{$function}($value);
        }
        return $object;
    }
}

