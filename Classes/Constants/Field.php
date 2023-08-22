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
    const ITEMNUMBER     = 'transactor_itemnumber';     // item number of a given record
    const PRICE_TAX      = 'transactor_price_tax';      // price including tax of a given record
    const PRICE_NOTAX    = 'transactor_price_notax';    // price without tax of a given record
    const PRICE_ONLYTAX  = 'transactor_price_onlytax';  // price of only the tax of a given record
    const PRICE_TOTAL_ONLYTAX = 'transactor_price_total_onlytax';  // total price of only the tax of a given record
    const QUANTITY           = 'transactor_quantity';       // number of a given record
    const TAX_PERCENTAGE     = 'transactor_tax_percentage'; // tax percentage of a given record
    const VARIANT            = 'transactor_variant';        // variants separated by blanks
    const GOODS_TAX          = 'transactor_goods_tax';      // goods price including tax
    const GOODS_NOTAX        = 'transactor_goods_notax';    // goods price without tax
    const GOODSVOUCHER_TAX   = 'transactor_goodsvoucher_tax';   // goods voucher price including tax
    const GOODSVOUCHER_NOTAX = 'transactor_goodsvoucher_notax'; // goods voucher price without tax
    const PAYMENT_TAX        = 'transactor_payment_tax';    // payment price including tax
    const PAYMENT_NOTAX      = 'transactor_payment_notax';  // payment price without tax
    const SHIPPING_TAX       = 'transactor_shipping_tax';   // shipping price including tax
    const SHIPPING_NOTAX     = 'transactor_shipping_notax'; // shipping price without tax
    const HANDLING_TAX       = 'transactor_handling_tax';   // handling price including tax
    const HANDLING_NOTAX     = 'transactor_handling_notax'; // handling price without tax
}

