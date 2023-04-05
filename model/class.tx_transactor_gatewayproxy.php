<?php
/***************************************************************
*
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


/**
* Proxy class implementing the interface for gateway implementations. This
* class hangs between the real gateway implementation and the application
* using it.
*
* @package 	TYPO3
* @subpackage	tx_transactor
* @author	Robert Lemke <robert@typo3.org>
* @author	Franz Holzinger <franz@ttproducts.de>
*/
class tx_transactor_gatewayproxy implements tx_transactor_gateway_int, \TYPO3\CMS\Core\SingletonInterface {
    private $gatewayExt;
    private $gatewayClass;
    protected $extensionManagerConf;


    /**
    * Initialization. Pass the class name of a gateway implementation.
    *
    * @param	string		$gatewayClass: Class name of a gateway implementation acting as the "Real Subject"
    * @return	void
    * @access	public
    */
    public function init ($extKey) {

        $this->extensionManagerConf = [];
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            $this->extensionManagerConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
            )->get('transactor');
        } else { // before TYPO3 9
            $this->extensionManagerConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['transactor']);
        }

        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey])) {
            $newExtensionManagerConf = [];
            if (
                defined('TYPO3_version') &&
                version_compare(TYPO3_version, '9.0.0', '>=')
            ) {
                $newExtensionManagerConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
                )->get($extKey);
            } else { // before TYPO3 9
                $newExtensionManagerConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey]);
            }

            if (is_array($this->extensionManagerConf)) {
                if (is_array($newExtensionManagerConf)) {
                    $this->extensionManagerConf = array_merge($this->extensionManagerConf, $newExtensionManagerConf);
                }
            } else {
                $this->extensionManagerConf = $newExtensionManagerConf;
            }
        }
        $this->gatewayClass = 'tx_' . str_replace('_', '', $extKey) . '_gateway';
        $this->gatewayExt = $extKey;
    }


    public function getGatewayObj () {
        $result = GeneralUtility::makeInstance($this->gatewayClass);
        if (!is_object($result)) {
            throw new RuntimeException('ERROR in the Payment Transactor API (transactor) used by the extension "' . $this->gatewayExt . '": no object exists for the class "' . $this->gatewayClass . '"', 2020290000);
        }
        return $result;
    }


    /**
    * Returns the gateway key. Each gateway implementation should have such
    * a unique key.
    *
    * @return	array		Gateway key
    * @access	public
    */
    public function getGatewayKey () {
        $result = $this->getGatewayObj()->getGatewayKey();
        return $result;
    }


    public function getConf () {
        $result = $this->getGatewayObj()->getConf();
        return $result;
    }


    public function getConfig () {
        $result = $this->getGatewayObj()->getConfig();
        return $result;
    }


    public function setConfig ($config) {
        $this->getGatewayObj()->setConfig($config);
    }


    /**
    * Returns an array of keys of the supported payment methods
    *
    * @return	array		Supported payment methods
    * @access	public
    */
    public function getAvailablePaymentMethods () {
        $result = $this->getGatewayObj()->getAvailablePaymentMethods();
        return $result;
    }


    /**
    * Returns true if the payment implementation supports the given gateway mode.
    * All implementations should at least support the mode
    * TX_TRANSACTOR_GATEWAYMODE_FORM.
    *
    * TX_TRANSACTOR_GATEWAYMODE_WEBSERVICE usually requires your webserver and
    * the whole application to be certified if used with certain credit cards.
    *
    * @param	integer		$gatewayMode: The gateway mode to check for. One of the constants TX_TRANSACTOR_GATEWAYMODE_*
    * @return	boolean		true if the given gateway mode is supported
    * @access	public
    */
    public function supportsGatewayMode ($gatewayMode) {
        $result = $this->getGatewayObj()->supportsGatewayMode($gatewayMode);
        return $result;
    }


    /**
    * Initializes a transaction.
    *
    * @param	integer		$action: Type of the transaction, one of the constants TX_TRANSACTOR_TRANSACTION_ACTION_*
    * @param	string		$paymentMethod: Payment method, one of the values of getSupportedMethods()
    * @param	integer		$gatewayMode: Gateway mode for this transaction, one of the constants TX_TRANSACTOR_GATEWAYMODE_*
    * @param	string		$extKey: Extension key of the calling script.
    * @param	array		$config: configuration for the extension
    * @return	void
    * @access	public
    */
    public function transaction_init ($action, $method, $gatewaymode, $extKey, $config = []) {
        $this->getGatewayObj()->setTransactionUid(0);
        $result = $this->getGatewayObj()->transaction_init(
            $action,
            $method,
            $gatewaymode,
            $extKey,
            $config
        );
        return $result;
    }


    /**
    * Sets the payment details. Which fields can be set usually depends on the
    * chosen / supported gateway mode. TX_TRANSACTOR_GATEWAYMODE_FORM does not
    * allow setting credit card data for example.
    *
    * @param	array		$detailsArr: The payment details array
    * @return	boolean		Returns true if all required details have been set
    * @access	public
    */
    public function transaction_setDetails ($detailsArr) {
        $result = $this->getGatewayObj()->transaction_setDetails($detailsArr);
        return $result;
    }


    /**
    * Validates the transaction data which was set by transaction_setDetails().
    * $level determines how strong the check is, 1 only checks if the data is
    * formally correct while level 2 checks if the credit card or bank account
    * really exists.
    *
    * This method is not available in mode TX_TRANSACTOR_GATEWAYMODE_FORM!
    *
    * @param	integer		$level: Level of validation, depends on implementation
    * @return	boolean		Returns true if validation was successful, false if not
    * @access	public
    */
    public function transaction_validate ($level = 1) {
        $result = $this->getGatewayObj()->transaction_validate($level);
        return $result;
    }


    /**
    * Returns if the transaction has been successfull
    *
    * @param	array		results from transaction_getResults
    * @return	boolean		true if the transaction went fine
    * @access	public
    */
    public function transaction_succeded ($resultsArr) {
        $result = $this->getGatewayObj()->transaction_succeded($resultsArr);
        return $result;
    }


    /**
    * Returns if the transaction has been unsuccessfull
    *
    * @param	array		results from transaction_getResults
    * @return	boolean		true if the transaction went wrong
    * @access	public
    */
    public function transaction_failed ($resultsArr) {
        $result = $this->getGatewayObj()->transaction_failed($resultsArr);
        return $result;
    }


    /**
    * Returns if the message of the transaction
    *
    * @param	array		results from transaction_getResults
    * @return	boolean		true if the transaction went wrong
    * @access	public
    */
    public function transaction_message ($resultsArr) {
        $result = $this->getGatewayObj()->transaction_message($resultsArr);
        return $result;
    }


    /**
    * Submits the prepared transaction to the payment gateway
    *
    * This method is not available in mode TX_TRANSACTOR_GATEWAYMODE_FORM, you'll have
    * to render and submit a form instead.
    *
    * @param	string		an error message will be provided in case of error
    * @return	boolean		true if transaction was successul, false if not. The result can be accessed via transaction_getResults()
    * @access	public
    */
    public function transaction_process (&$errorMessage) {
        $gatewayObj = $this->getGatewayObj();
        $processResult = $gatewayObj->transaction_process($errorMessage);
        $reference = $this->getReferenceUid();
        $resultsArr = $gatewayObj->transaction_getResults($reference);

        if (is_array($resultsArr)) {
            $fields = $resultsArr;

            if (
                !$fields['uid'] &&
                $fields['reference']
            ) {
                $fields['crdate'] = time();
                $fields['pid'] = intval($this->extensionManagerConf['pid']);
                $fields['message'] = (is_array($fields['message'])) ? serialize($fields['message']) : $fields['message'];

                $dbResult = $GLOBALS['TYPO3_DB']->exec_INSERTquery (
                    'tx_transactor_transactions',
                    $fields
                );
                $dbTransactionUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
                $gatewayObj->setTransactionUid($dbTransactionUid);
            }

            $processResult = true;
        } else {
            $errorMessage = $resultsArr;
            $processResult = false;
        }

        return $processResult;
    }


    /**
    * Displays the form on which the user will finally submit the transaction to the payment gateway
    * Only to be used in mode TX_TRANSACTOR_GATEWAYMODE_AJAX
    *
    * @return	string		HTML form and javascript
    * @access	public
    */	public function transaction_getForm ($lConf) {
        $result = $this->getGatewayObj()->transaction_getForm($lConf);
        return $result;
    }


    /**
    * Returns the form action URI to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
    *
    * @return	string		Form action URI
    * @access	public
    */
    public function transaction_formGetActionURI () {
        $result = $this->getGatewayObj()->transaction_formGetActionURI();
        return $result;
    }


    /**
    * Returns any extra parameter for the form tag to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
    *
    * @return  string      Form tag extra parameters
    * @access  public
    */
    public function transaction_formGetFormParms () {
        $result = '';
        if ($this->getGatewayObj()->getGatewayMode() == TX_TRANSACTOR_GATEWAYMODE_FORM) {
            $result = $this->getGatewayObj()->transaction_formGetFormParms();
        }
        return $result;
    }


    /**
        * Returns any extra HTML attributes for the form tag to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
    *
    * @return  string      Form submit button extra parameters
    * @access  public
    */
    public function transaction_formGetAttributes () {
        $result = '';
        if ($this->getGatewayObj()->getGatewayMode() == TX_TRANSACTOR_GATEWAYMODE_FORM) {
            $result = $this->getGatewayObj()->transaction_formGetAttributes();
        }
        return $result;
    }


    /**
    * Returns an array of field names and values which must be included as hidden
    * fields in the form you render use mode TX_TRANSACTOR_GATEWAYMODE_FORM.
    *
    * @return	array		Field names and values to be rendered as hidden fields
    * @access	public
    */
    public function transaction_formGetHiddenFields () {
        $result = $this->getGatewayObj()->transaction_formGetHiddenFields();
        return $result;
    }


    /**
    * Sets the URI which the user should be redirected to after a successful payment/transaction
    *
    * @return void
    * @access public
    */
    public function transaction_setOkPage ($uri) {
        $this->getGatewayObj()->transaction_setOkPage($uri);
    }


    /**
    * Sets the URI which the user should be redirected to after a failed payment/transaction
    *
    * @return void
    * @access public
    */
    public function transaction_setErrorPage ($uri) {
        $this->getGatewayObj()->transaction_setErrorPage($uri);
    }


    /**
    * Return if the transaction is still in the initialization state
    * This is the case if the gateway initialization is called several times before starting the processing of it.
    *
    * @param array   transaction record
    * @return boolean
    * @access public
    */
    public function transaction_isInitState ($row) {
        $result = $this->getGatewayObj()->transaction_isInitState($row);
        return $result;
    }


    /**
    * Returns the results of a processed transaction
    *
    * @param	string		$orderid
    * @param	boolean		$create  if true the results are inserted into the transactor table
    * @return	array		Results of a processed transaction
    * @access	public
    */
    public function transaction_getResults ($reference, $create = true) {
        $dbResult = false;
        $resultsArr = $this->getGatewayObj()->transaction_getResults($reference);

        if (is_array ($resultsArr)) {
            $dbTransactionUid = $this->getGatewayObj()->getTransactionUid();

            if ($dbTransactionUid) {
                $dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
                    'gatewayid',
                    'tx_transactor_transactions',
                    'uid=' . intval($dbTransactionUid)
                );
            }

            if ($dbResult) {
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
                $GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

                if (
                    is_array ($row) &&
                    $row['gatewayid'] === $resultsArr['gatewayid']
                ) {
                    $resultsArr['internaltransactionuid'] = $dbTransactionUid;
                }
            } else if ($create) {
                    // If the transaction doesn't exist yet in the database, create a transaction record.
                    // Usually the case with unsuccessful orders with gateway mode FORM.
                $fields = $resultsArr;
                $fields['crdate'] = time();
                $fields['pid'] = $this->extensionManagerConf['pid'];

                if (
                    $fields['uid'] &&
                    $fields['reference']
                ) {
                    $dbResult = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                        'tx_transactor_transactions',
                        $fields
                    );
                    $resultsArr = $fields;
                }
            }
        }
        return $resultsArr;
    }


    public function transaction_getResultsError ($message) {
        $result = $this->getGatewayObj()->transaction_getResultsError($message);
        return $result;
    }


    public function transaction_getResultsSuccess ($message) {
        $result = $this->getGatewayObj()->transaction_getResultsSuccess($message);
        return $result;
    }


    /**
    * Methods of the gateway implementation which this proxy class does not know
    * are just passed to the gateway object. This should be mainly used for testing
    * purposes, for other cases you should stick to the official interface which is
    * also supported by the gateway proxy.
    *
    * @param	string		$method:	Method name
    * @param	array		$params:	Parameters
    * @return	mixed		Result
    * @access	public
    */
    public function __call ($method, $params) {
        $result = false;
        if (method_exists($this, $method)) {
            $result = call_user_func_array([$this->getGatewayObj(), $method], $params);
        } else {
            debug ('ERROR: unkown method "' . $method . '" in call of tx_transactor_gatewayproxy object'); // keep this
            throw new RuntimeException('ERROR in transactor: unkown method "' . $method . '" in call of tx_transactor_gatewayproxy object ' . $this->gatewayClass . '"', 2020290001);
        }
        return $result;
    }


    /**
    * Returns the property of the real subject (gateway object).
    *
    * @param	string		$property: Name of the variable
    * @return	mixed		The value.
    * @access	public
    */
    public function __get ($property) {
        $result = $this->getGatewayObj()->$property;
        return $result;
    }


    /**
    * Sets the property of the real subject (gateway object)
    *
    * @param	string		$property: Name of the variable
    * @param	mixed		$value: The new value
    * @return	void
    * @access	public
    */
    public function __set ($property, $value) {
        $this->getGatewayObj()->$property = $value;
    }


    public function clearErrors () {
        $this->getGatewayObj()->clearErrors();
    }


    public function addError ($error) {
        $this->getGatewayObj()->addError($error);
    }


    public function hasErrors () {
        $result = $this->getGatewayObj()->hasErrors();
        return $result;
    }


    public function getErrors () {
        $result = $this->getGatewayObj()->getErrors();
        return $result;
    }


    public function usesBasket () {
        $result = $this->getGatewayObj()->usesBasket();
        return $result;
    }


    public function getTransaction ($reference) {
        $result = $this->getGatewayObj()->getTransaction($reference);
        return $result;
    }


    public function setTaxIncluded ($bTaxIncluded) {
        $this->getGatewayObj()->setTaxIncluded($bTaxIncluded);
    }


    public function getTaxIncluded() {
        return $this->getGatewayObj()->getTaxIncluded();
    }


    public function generateReferenceUid ($orderuid, $callingExtensionKey) {
        $result = $this->getGatewayObj()->generateReferenceUid($orderuid, $callingExtensionKey);
        return $result;
    }


    /**
    * Sets the reference of the transaction table
    *
    * @param	integer		unique transaction id
    * @return	void
    * @access	public
    */
    public function setReferenceUid ($reference) {
        $this->getGatewayObj()->setReferenceUid($reference);
    }


    /**
    * Fetches the reference of the transaction table, which is the reference
    *
    * @return	void		unique reference
    * @access	public
    */
    public function getReferenceUid () {
        $result = $this->getGatewayObj()->getReferenceUid();
        return $result;
    }


    /**
    * Sets the uid of the transaction table
    *
    * @param	integer		unique transaction id
    * @return	void
    * @access	public
    */
    public function setTransactionUid ($transUid) {
        $this->getGatewayObj()->setTransactionUid($transUid);
    }


    /**
    * Fetches the uid of the transaction table, which is the reference
    *
    * @return	void		unique transaction id
    * @access	public
    */
    public function getTransactionUid () {
        $this->getGatewayObj()->getTransactionUid();
    }


    /**
    * Sets the form action URI
    *
    * @param	string		form action URI
    * @return	void
    * @access	public
    */
    public function setFormActionURI ($formActionURI) {
        $this->getGatewayObj()->setFormActionURI($formActionURI);
    }


    /**
    * Fetches the form action URI
    *
    * @return	string		form action URI
    * @access	public
    */
    public function getFormActionURI () {
        $result = $this->getGatewayObj()->getFormActionURI();
        return $result;
    }


    /**
    * This gives the information if the order can only processed after a verification message has been received.
    *
    * @return	boolean		true if a verification message needs to be sent
    * @access	public
    */
    public function needsVerificationMessage () {
        $result = $this->getGatewayObj()->needsVerificationMessage();
        return $result;
    }


    /**
    * Calculates the payment costs
    *
    * @param	array		configuration
    * @param	float		total amount to pay
    * @param	string		ISO3 code of seller
    * @param	string		ISO3 code of buyer
    * @return	float		payment costs
    * @access	public
    */
    public function getCosts (
        $confScript,
        $amount,
        $iso3Seller,
        $iso3Buyer
    ) {
        $result = false;
        $gatewayObject = $this->getGatewayObj();
            // this method is not mandatory
        if (
            method_exists($gatewayObject, 'getCosts')
        ) {
            $result =
                $this->getGatewayObj()->getCosts(
                    $confScript,
                    $amount,
                    $iso3Seller,
                    $iso3Buyer
                );
        }
        return $result;
    }
}

