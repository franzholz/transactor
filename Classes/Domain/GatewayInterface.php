<?php

namespace JambageCom\Transactor\Domain;

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
* Abstract class defining the interface for gateway implementations.
*
* All implementations must implement this interface but depending on the
* gatway modes they support, methods like transaction_validate won't
* do anything.
*
* @package 	TYPO3
* @subpackage	transactor
* @author		Franz Holzinger <franz@ttproducts.de>
*/
interface GatewayInterface
{

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
    * Initializes a transaction.
    *
    * @param	integer		$action: Type of the transaction, one of the constants TX_TRANSACTOR_TRANSACTION_ACTION_*
    * @param	string		$paymentMethod: Payment method, one of the values of getSupportedMethods()
    * @param	integer		$gatewayMode: Gateway mode for this transaction, one of the constants GatewayMode::*
    * @param	string		$callingExtensionKey: Extension key of the calling script.
    * @return	void
    * @access	public
    */
    public function transactionInit ($action, $paymentMethod, $gatewayMode, $callingExtensionKey);

    /**
    * Sets the payment details. Which fields can be set usually depends on the
    * chosen / supported gateway mode. GatewayMode::FORM does not
    * allow setting credit card data for example.
    *
    * @param	array		$detailsArray: The payment details array
    * @return	boolean		Returns true if all required details have been set
    * @access	public
    */
    public function transactionSetDetails ($detailsArray);

    /**
    * Validates the transaction data which was set by transaction_setDetails().
    * $level determines how strong the check is, 1 only checks if the data is
    * formally correct while level 2 checks if the credit card or bank account
    * really exists.
    *
    * This method is not available in mode GatewayMode::FORM!
    *
    * @param	integer		$level: Level of validation, depends on implementation
    * @return	boolean		Returns true if validation was successful, false if not
    * @access	public
    */
    public function transactionValidate ($level = 1);

    /**
    * Submits the prepared transaction to the payment gateway
    *
    * This method is not available in mode GatewayMode::FORM, you'll have
    * to render and submit a form instead.
    *
    * @param	string		an error message will be provided in case of error
    * @return	boolean		true if transaction was successul, false if not. The result can be accessed via transaction_getResults()
    * @access	public
    */
    public function transactionProcess (&$errorMessage);

    /**
    * Displays the form on which the user will finally submit the transaction to the payment gateway
    *
    *
    * @return	string		HTML form and javascript
    * @access	public
    */
    public function transactionGetForm ();

    /**
    * Returns the form action URI to be used in mode GatewayMode::FORM.
    *
    * @return	string		Form action URI
    * @access	public
    */
    public function transactionFormGetActionURI ();

    /**
    * Returns any extra parameter for the form tag to be used in mode GatewayMode::FORM.
    *
    * @return  string      Form tag extra parameters
    * @access  public
    */
    public function transactionFormGetFormParms ();

    /**
    * Returns any extra HTML attributes for the form tag to be used in mode GatewayMode::FORM.
    *
    * @return  string      Form submit button extra parameters
    * @access  public
    */
    public function transactionFormGetAttributes ();

    /**
    * Returns an array of field names and values which must be included as hidden
    * fields in the form you render use mode GatewayMode::FORM.
    *
    * @return	array		Field names and values to be rendered as hidden fields
    * @access	public
    */
    public function transactionFormGetHiddenFields ();


    /**
    * Sets the URI which the user should be redirected to after a successful payment/transaction
    * If your gateway/gateway implementation only supports one redirect URI, set okpage and
    * errorpage to the same URI
    *
    * @return void
    * @access public
    */
    public function transactionSetOkPage ($uri);

    /**
    * Sets the URI which the user should be redirected to after a failed payment/transaction
    * If your gateway/gateway implementation only supports one redirect URI, set okpage and
    * errorpage to the same URI
    *
    * @param array   transaction record
    * @return void
    * @access public
    */
    public function transactionSetErrorPage ($row);


    /**
    * Return if the transaction is still in the initialization state
    * This is the case if the gateway initialization is called several times before starting the processing of it.
    *
    * @return boolean
    * @access public
    */
    public function transactionIsInitState ($uri);


    /**
    * Returns the results of a processed transaction
    *
    * @param	string		$reference
    * @return	array		Results of a processed transaction
    * @access	public
    */
    public function transactionGetResults ($reference);

    /**
    * Returns the error result
    *
    * @param	string		$message ... message to show
    * @return	array		Results of an internal error
    * @access	public
    */
    public function transactionGetResultsError ($message);

    /**
    * Returns the error result
    *
    * @param	string		$message ... message to show
    * @return	array		Results of an internal error
    * @access	public
    */
    public function transactionGetResultsSuccess ($message);

    /**
    * Returns if the transaction has been successfull
    *
    * @param	array		results from transaction_getResults
    * @return	boolean		true if the transaction went fine
    * @access	public
    */
    public function transactionSucceeded ($resultsArr);

    /**
    * Returns if the transaction has been unsuccessfull
    *
    * @param	array		results from transaction_getResults
    * @return	boolean		true if the transaction went wrong
    * @access	public
    */
    public function transactionFailed ($resultsArr);

    /**
    * Returns if the message of the transaction
    *
    * @param	array		results from transaction_getResults
    * @return	boolean		true if the transaction went wrong
    * @access	public
    */
    public function transactionMessage ($resultsArr);

    public function clearErrors ();

    public function addError ($error);

    public function hasErrors ();

    public function getErrors ();

    public function useBasket ();

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
    * @return	boolean		true if a verification message needs to be sent
    * @access	public
    */
    public function needsVerificationMessage ();
}

