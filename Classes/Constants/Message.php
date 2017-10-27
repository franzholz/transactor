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
 * Constants for the internal messages
 */
class Message
{
    const NOT_PROCESSED = '-';     // TX_TRANSACTOR_TRANSACTION_MESSAGE_NOT_PROCESSED
    const WRONG_TRANSACTION = 'Received wrong transaction';     // WRONG_TRANSACTION_MSG
    const WRONG_AMOUNT =      'Received wrong amount';     // WRONG_TRANSACTION_MSG
    const SUCCESS = 'OK';       // success
}


