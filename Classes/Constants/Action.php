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
 * Constants for the action
 */
class Action
{
    const AUTHORIZE_TRANSFER = 200;       // TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER
    const AUTHORIZE = 201;                // TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZE
    const TRANSFER = 202;                 // TX_TRANSACTOR_TRANSACTION_ACTION_TRANSFER
    const REAUTHORIZE_TRANSFER = 203;     // TX_TRANSACTOR_TRANSACTION_ACTION_REAUTHORIZEANDTRANSFER
    const REAUTHORIZE = 204;              // TX_TRANSACTOR_TRANSACTION_ACTION_REAUTHORIZE
    const CANCELAUTHORIZED = 205;         // TX_TRANSACTOR_TRANSACTION_ACTION_CANCELAUTHORIZED

    const AUTHORIZEREFUND = 210;          // TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZEREFUND
    const AUTHORIZE_TRANSFERREFUND = 211; // TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZEANDTRANSFERREFUND
}


