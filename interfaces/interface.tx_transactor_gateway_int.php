<?php
/***************************************************************
* $Id$
*
*  Copyright notice
*
*  (c) 2009 Franz Holzinger (franz@ttproducts.de)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

define('TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER', 200);
define('TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZE', 201);
define('TX_TRANSACTOR_TRANSACTION_ACTION_TRANSFER', 202);
define('TX_TRANSACTOR_TRANSACTION_ACTION_REAUTHORIZEANDTRANSFER', 203);
define('TX_TRANSACTOR_TRANSACTION_ACTION_REAUTHORIZE', 204);
define('TX_TRANSACTOR_TRANSACTION_ACTION_CANCELAUTHORIZED', 205);
define('TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZEREFUND', 210);
define('TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZEANDTRANSFERREFUND', 211);

define('TX_TRANSACTOR_GATEWAYMODE_FORM', 400);
define('TX_TRANSACTOR_GATEWAYMODE_WEBSERVICE', 401);
define('TX_TRANSACTOR_GATEWAYMODE_AJAX', 402);


define('TX_TRANSACTOR_TRANSACTION_LIMIT_STATE_OK', 500);
define('TX_TRANSACTOR_TRANSACTION_LIMIT_STATE_NOK', 600);
define('TX_TRANSACTOR_TRANSACTION_LIMIT_STATE_INTERNAL_NOK', 650);

define('TX_TRANSACTOR_TRANSACTION_STATE_NO_PROCESS', 0);

// ok constants
// @see the TX_TRANSACTOR_TRANSACTION_LIMIT constants
define('TX_TRANSACTOR_TRANSACTION_STATE_IDLE', 0);
define('TX_TRANSACTOR_TRANSACTION_STATE_INIT', 400);
define('TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_OK', 500);
define('TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_DUPLICATE', 501);
define('TX_TRANSACTOR_TRANSACTION_STATE_CAPTURE_OK', 502);
define('TX_TRANSACTOR_TRANSACTION_STATE_REVERSE_OK', 503);
define('TX_TRANSACTOR_TRANSACTION_STATE_CREDIT_OK', 504);
define('TX_TRANSACTOR_TRANSACTION_STATE_RENEW_OK', 505);

// nok constants
// @see the TX_TRANSACTOR_TRANSACTION_LIMIT constants
define('TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK', 601);
define('TX_TRANSACTOR_TRANSACTION_STATE_CAPTURE_NOK', 602);
define('TX_TRANSACTOR_TRANSACTION_STATE_REVERSE_NOK', 603);
define('TX_TRANSACTOR_TRANSACTION_STATE_CREDIT_NOK', 604);
define('TX_TRANSACTOR_TRANSACTION_STATE_RENEW_NOK', 605);
define('TX_TRANSACTOR_TRANSACTION_STATE_INTERNAL_ERROR', 651);


define('TX_TRANSACTOR_TRANSACTION_MESSAGE_NOT_PROCESSED', '-');



/**
 * Abstract class defining the interface for gateway implementations.
 *
 * All implementations must implement this interface but depending on the
 * gatway modes they support, methods like transaction_validate won't
 * do anything.
 *
 * @package 	TYPO3
 * @subpackage	tx_transactor
 * @author		Franz Holzinger <franz@ttproducts.de>
 */
interface tx_transactor_gateway_int {

	/**
	 * Returns the gateway key. Each gateway implementation should have such
	 * a unique key.
	 *
	 * @return	array		Gateway key
	 * @access	public
	 */
	public function getGatewayKey ();


	public function getConf ();


	public function getConfig ();


	public function setConfig ($config);


	/**
	 * Returns an array of keys of the supported payment methods
	 *
	 * @return	array		Supported payment methods
	 * @access	public
	 */
	public function getAvailablePaymentMethods ();

	/**
	 * Returns TRUE if the payment implementation supports the given gateway mode.
	 * All implementations should at least support the mode
	 * TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * TX_TRANSACTOR_GATEWAYMODE_WEBSERVICE usually requires your webserver and
	 * the whole application to be certified if used with certain credit cards.
	 *
	 * @param	integer		$gatewayMode: The gateway mode to check for. One of the constants TX_TRANSACTOR_GATEWAYMODE_*
	 * @return	boolean		TRUE if the given gateway mode is supported
	 * @access	public
	 */
	public function supportsGatewayMode ($gatewayMode);

	/**
	 * Initializes a transaction.
	 *
	 * @param	integer		$action: Type of the transaction, one of the constants TX_TRANSACTOR_TRANSACTION_ACTION_*
	 * @param	string		$paymentMethod: Payment method, one of the values of getSupportedMethods()
	 * @param	integer		$gatewayMode: Gateway mode for this transaction, one of the constants TX_TRANSACTOR_GATEWAYMODE_*
	 * @param	string		$callingExtKey: Extension key of the calling script.
	 * @return	void
	 * @access	public
	 */
	public function transaction_init ($action, $paymentMethod, $gatewayMode, $callingExtKey);

	/**
	 * Sets the payment details. Which fields can be set usually depends on the
	 * chosen / supported gateway mode. TX_TRANSACTOR_GATEWAYMODE_FORM does not
	 * allow setting credit card data for example.
	 *
	 * @param	array		$detailsArray: The payment details array
	 * @return	boolean		Returns TRUE if all required details have been set
	 * @access	public
	 */
	 public function transaction_setDetails ($detailsArray);

	/**
	 * Validates the transaction data which was set by transaction_setDetails().
	 * $level determines how strong the check is, 1 only checks if the data is
	 * formally correct while level 2 checks if the credit card or bank account
	 * really exists.
	 *
	 * This method is not available in mode TX_TRANSACTOR_GATEWAYMODE_FORM!
	 *
	 * @param	integer		$level: Level of validation, depends on implementation
	 * @return	boolean		Returns TRUE if validation was successful, FALSE if not
	 * @access	public
	 */
	 public function transaction_validate ($level = 1);

	/**
	 * Submits the prepared transaction to the payment gateway
	 *
	 * This method is not available in mode TX_TRANSACTOR_GATEWAYMODE_FORM, you'll have
	 * to render and submit a form instead.
	 *
	 * @param	string		an error message will be provided in case of error
	 * @return	boolean		TRUE if transaction was successul, FALSE if not. The result can be accessed via transaction_getResults()
	 * @access	public
	 */
	public function transaction_process (&$errorMessage);

	/**
	 * Displays the form on which the user will finally submit the transaction to the payment gateway
	 *
	 *
	 * @return	string		HTML form and javascript
	 * @access	public
	 */
	public function transaction_getForm ($lConf);

	/**
	 * Returns the form action URI to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * @return	string		Form action URI
	 * @access	public
	 */
	public function transaction_formGetActionURI ();

	/**
	* Returns any extra parameter for the form tag to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	*
	* @return  string      Form tag extra parameters
	* @access  public
	*/
	public function transaction_formGetFormParms ();

	/**
	* Returns any extra HTML attributes for the form tag to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	*
	* @return  string      Form submit button extra parameters
	* @access  public
	*/
	public function transaction_formGetAttributes ();

	/**
	 * Returns an array of field names and values which must be included as hidden
	 * fields in the form you render use mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * @return	array		Field names and values to be rendered as hidden fields
	 * @access	public
	 */
	public function transaction_formGetHiddenFields ();


	/**
	 * Sets the URI which the user should be redirected to after a successful payment/transaction
	 * If your gateway/gateway implementation only supports one redirect URI, set okpage and
	 * errorpage to the same URI
	 *
	 * @return void
	 * @access public
	 */
	public function transaction_setOkPage ($uri);

	/**
	 * Sets the URI which the user should be redirected to after a failed payment/transaction
	 * If your gateway/gateway implementation only supports one redirect URI, set okpage and
	 * errorpage to the same URI
	 *
	 * @param array   transaction record
	 * @return void
	 * @access public
	 */
	public function transaction_setErrorPage ($row);


	/**
	 * Return if the transaction is still in the initialization state
	 * This is the case if the gateway initialization is called several times before starting the processing of it.
	 *
	 * @return boolean
	 * @access public
	 */
	public function transaction_isInitState ($uri);


	/**
	 * Returns the results of a processed transaction
	 *
	 * @param	string		$reference
	 * @return	array		Results of a processed transaction
	 * @access	public
	 */
	public function transaction_getResults ($reference);

	/**
	 * Returns the error result
	 *
	 * @param	string		$message ... message to show
	 * @return	array		Results of an internal error
	 * @access	public
	 */
	public function transaction_getResultsError ($message);

	/**
	 * Returns the error result
	 *
	 * @param	string		$message ... message to show
	 * @return	array		Results of an internal error
	 * @access	public
	 */
	public function transaction_getResultsSuccess ($message);

	/**
	 * Returns if the transaction has been successfull
	 *
	 * @param	array		results from transaction_getResults
	 * @return	boolean		TRUE if the transaction went fine
	 * @access	public
	 */
	public function transaction_succeded ($resultsArr);

	/**
	 * Returns if the transaction has been unsuccessfull
	 *
	 * @param	array		results from transaction_getResults
	 * @return	boolean		TRUE if the transaction went wrong
	 * @access	public
	 */
	public function transaction_failed ($resultsArr);

	/**
	 * Returns if the message of the transaction
	 *
	 * @param	array		results from transaction_getResults
	 * @return	boolean		TRUE if the transaction went wrong
	 * @access	public
	 */
	public function transaction_message ($resultsArr);

	public function clearErrors ();

	public function addError ($error);

	public function hasErrors ();

	public function getErrors ();

	public function usesBasket ();

	public function getTransaction ($reference);

	public function setTaxIncluded ($bTaxIncluded);

	public function getTaxIncluded();

	public function generateReferenceUid ($orderuid, $callingExtension);

	/**
	 * Sets the uid of the transaction table
	 *
	 * @param	integer		unique transaction id
	 * @return	void
	 * @access	public
	 */
	public function setTransactionUid ($transUid);

	/**
	 * Fetches the uid of the transaction table, which is the reference
	 *
	 * @return	void		unique transaction id
	 * @access	public
	 */
	public function getTransactionUid ();

	/**
	 * Sets the form action URI
	 *
	 * @param	string		form action URI
	 * @return	void
	 * @access	public
	 */
	public function setFormActionURI ($formActionURI);

	/**
	 * Fetches the form action URI
	 *
	 * @return	string		form action URI
	 * @access	public
	 */
	public function getFormActionURI ();


	/**
	 * This gives the information if the order can only processed after a verification message has been received.
	 *
	 * @return	boolean		TRUE if a verification message needs to be sent
	 * @access	public
	 */
	public function needsVerificationMessage ();
}

?>