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
 * Constants for the transaction states
 */
class State
{
    const IDLE = 0;                  // TX_TRANSACTOR_TRANSACTION_STATE_NO_PROCESS and
                                     // TX_TRANSACTOR_TRANSACTION_STATE_IDLE
    const INIT_ABORT = 300;
    const INIT = 400;                // TX_TRANSACTOR_TRANSACTION_STATE_INIT
    const APPROVE_OK = 500;          // TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_OK
    const APPROVE_DUPLICATE = 501;   // TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_DUPLICATE
    const CAPTURE_OK = 502;          // TX_TRANSACTOR_TRANSACTION_STATE_CAPTURE_OK
    const REVERSE_OK = 503;          // TX_TRANSACTOR_TRANSACTION_STATE_REVERSE_OK
    const CREDIT_OK = 504;           // TX_TRANSACTOR_TRANSACTION_STATE_CREDIT_OK
    const RENEW_OK = 505;            // TX_TRANSACTOR_TRANSACTION_STATE_RENEW_OK
    const APPROVE_NOK = 601;         // TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK
    const CAPTURE_NOK = 602;         // TX_TRANSACTOR_TRANSACTION_STATE_CAPTURE_NOK
    const REVERSE_NOK = 603;         // TX_TRANSACTOR_TRANSACTION_STATE_REVERSE_NOK
    const CREDIT_NOK = 604;          // TX_TRANSACTOR_TRANSACTION_STATE_CREDIT_NOK
    const RENEW_NOK = 605;           // TX_TRANSACTOR_TRANSACTION_STATE_RENEW_NOK
    const INTERNAL_ERROR = 651;      // TX_TRANSACTOR_TRANSACTION_STATE_INTERNAL_ERROR
}


