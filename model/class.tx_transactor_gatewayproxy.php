<?php
/***************************************************************
*
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
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


require_once (t3lib_extMgM::extPath('transactor') . 'interfaces/interface.tx_transactor_gateway_int.php');

require_once (t3lib_extMgM::extPath('transactor') . 'model/class.tx_transactor_gateway.php');

/**
 * Proxy class implementing the interface for gateway implementations. This
 * class hangs between the real gateway implementation and the application
 * using it.
 *
 * $Id$
 *
 *
 * @package 	TYPO3
 * @subpackage	tx_transactor
 * @author	Robert Lemke <robert@typo3.org>
 * @author	Franz Holzinger <franz@ttproducts.de>
 */
class tx_transactor_gatewayproxy implements tx_transactor_gateway_int {
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

		$this->extensionManagerConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['transactor']);
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey])) {
			$newExtensionManagerConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey]);
			if (is_array($this->extensionManagerConf)) {
				if (is_array($newExtensionManagerConf)) {
					$this->extensionManagerConf = array_merge($this->extensionManagerConf, $newExtensionManagerConf);
				}
			} else {
				$this->extensionManagerConf = $newExtensionManagerConf;
			}
		}
		$this->gatewayClass = 'tx_' . str_replace('_','',$extKey) . '_gateway';
		$this->gatewayExt = $extKey;
		require_once(t3lib_extMgm::extPath($extKey) . 'model/class.' . $this->gatewayClass . '.php');
	}


	public function getGatewayObj () {
		$rc = t3lib_div::getUserObj('&' . $this->gatewayClass);
		if (!is_object($rc)) {
			exit('internal ERROR in the usage of the Payment Transactor API (transactor) by the extension "' . $this->gatewayExt . '": no object exists for the class "' . $this->gatewayClass . '"');
		}
		return $rc;
	}


	/**
	 * Returns the gateway key. Each gateway implementation should have such
	 * a unique key.
	 *
	 * @return	array		Gateway key
	 * @access	public
	 */
	public function getGatewayKey () {
		return $this->getGatewayObj()->getGatewayKey();
	}


	public function getConf () {
		return $this->getGatewayObj()->getConf();
	}


	public function getConfig () {
		return $this->getGatewayObj()->getConfig();
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
		return $this->getGatewayObj()->getAvailablePaymentMethods();
	}


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
	public function supportsGatewayMode ($gatewayMode) {
		return $this->getGatewayObj()->supportsGatewayMode($gatewayMode);
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
	public function transaction_init ($action, $method, $gatewaymode, $extKey, $config=array()) {
		$this->getGatewayObj()->setTransactionUid(0);
		$rc = $this->getGatewayObj()->transaction_init(
			$action,
			$method,
			$gatewaymode,
			$extKey,
			$config
		);
		return $rc;
	}


	/**
	 * Sets the payment details. Which fields can be set usually depends on the
	 * chosen / supported gateway mode. TX_TRANSACTOR_GATEWAYMODE_FORM does not
	 * allow setting credit card data for example.
	 *
	 * @param	array		$detailsArr: The payment details array
	 * @return	boolean		Returns TRUE if all required details have been set
	 * @access	public
	 */
	public function transaction_setDetails ($detailsArr) {
		return $this->getGatewayObj()->transaction_setDetails($detailsArr);
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
	 * @return	boolean		Returns TRUE if validation was successful, FALSE if not
	 * @access	public
	 */
	public function transaction_validate ($level=1) {
		return $this->getGatewayObj()->transaction_validate($level);
	}


	/**
	 * Returns if the transaction has been successfull
	 *
	 * @param	array		results from transaction_getResults
	 * @return	boolean		TRUE if the transaction went fine
	 * @access	public
	 */
	public function transaction_succeded ($resultsArr) {
		return $this->getGatewayObj()->transaction_succeded($resultsArr);
	}


	/**
	 * Returns if the transaction has been unsuccessfull
	 *
	 * @param	array		results from transaction_getResults
	 * @return	boolean		TRUE if the transaction went wrong
	 * @access	public
	 */
	public function transaction_failed ($resultsArr) {
		return $this->getGatewayObj()->transaction_failed($resultsArr);
	}


	/**
	 * Returns if the message of the transaction
	 *
	 * @param	array		results from transaction_getResults
	 * @return	boolean		TRUE if the transaction went wrong
	 * @access	public
	 */
	public function transaction_message ($resultsArr) {
		return $this->getGatewayObj()->transaction_message($resultsArr);
	}


	/**
	 * Submits the prepared transaction to the payment gateway
	 *
	 * This method is not available in mode TX_TRANSACTOR_GATEWAYMODE_FORM, you'll have
	 * to render and submit a form instead.
	 *
	 * @return	boolean		TRUE if transaction was successul, FALSE if not. The result can be accessed via transaction_getResults()
	 * @access	public
	 */
	public function transaction_process () {
		global $TYPO3_DB;

		$gatewayObj = $this->getGatewayObj();
		$processResult = $gatewayObj->transaction_process();
		$referenceId = $this->getReferenceUid();
		$resultsArr = $gatewayObj->transaction_getResults($referenceId);

		if (is_array ($resultsArr)) {
			$fields = $resultsArr;
			$fields['crdate'] = time();
			$fields['pid'] = intval($this->extensionManagerConf['pid']);
			$fields['message'] = (is_array ($fields['message'])) ? serialize($fields['message']) : $fields['message'];
			if ($fields['uid'] && $fields['reference']) {
				$dbResult = $TYPO3_DB->exec_INSERTquery (
					'tx_transactor_transactions',
					$fields
				);
				$dbTransactionUid = $TYPO3_DB->sql_insert_id();
				$gatewayObj->getTransactionUid($dbTransactionUid);
			}
		}
		return $processResult;
	}


	/**
	 * Returns the form action URI to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * @return	string		Form action URI
	 * @access	public
	 */
	public function transaction_formGetActionURI () {
		return $this->getGatewayObj()->transaction_formGetActionURI();
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
		return $this->getGatewayObj()->transaction_formGetHiddenFields();
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
		return $this->getGatewayObj()->transaction_isInitState($row);
	}


	/**
	 * Returns the results of a processed transaction
	 *
	 * @param	string		$orderid
	 * @return	array		Results of a processed transaction
	 * @access	public
	 */
	public function transaction_getResults ($reference) {
		global $TYPO3_DB;

		$resultsArr = $this->getGatewayObj()->transaction_getResults($reference);

		if (is_array ($resultsArr)) {
			$dbTransactionUid = $this->getGatewayObj()->getTransactionUid();
			if ($dbTransactionUid)	{
				$dbResult = $TYPO3_DB->exec_SELECTquery (
					'gatewayid',
					'tx_transactor_transactions',
					'uid='.intval($dbTransactionUid)
				);
			}

			if ($dbResult) {
				$row = $TYPO3_DB->sql_fetch_assoc($dbResult);

				if (is_array ($row) && $row['gatewayid'] === $resultsArr['gatewayid']) {
					$resultsArr['internaltransactionuid'] = $dbTransactionUid;
				} else {
						// If the transaction doesn't exist yet in the database, create a transaction record.
						// Usually the case with unsuccessful orders with gateway mode FORM.
					$fields = $resultsArr;
					$fields['crdate'] = time();
					$fields['pid'] = $this->extensionManagerConf['pid'];
					$TYPO3_DB->sql_free_result($dbResult);

					if ($fields['uid'] && $fields['reference']) {
						$dbResult = $TYPO3_DB->exec_INSERTquery(
							'tx_transactor_transactions',
							$fields
						);
						$resultsArr = $fields;
					}
				}
			}
		}
		return $resultsArr;
	}


	public function transaction_getResultsError ($message) {
		return $this->getGatewayObj()->transaction_getResultsError($message);
	}


	public function transaction_getResultsSuccess ($message) {
		return $this->getGatewayObj()->transaction_getResultsSuccess($message);
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
		if (method_exists($this, $method)) {
			$rc = call_user_func_array(array($this->getGatewayObj(), $method), $params);
		} else {
			debug ('ERROR: unkown method "' . $method . '" in call of tx_transactor_gatewayproxy object');
			$rc = FALSE;
		}
		return $rc;
	}


	/**
	 * Returns the property of the real subject (gateway object).
	 *
	 * @param	string		$property: Name of the variable
	 * @return	mixed		The value.
	 * @access	public
	 */
	public function __get ($property) {
		return $this->getGatewayObj()->$property;
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
		$rc = $this->getGatewayObj()->hasErrors();
		return $rc;
	}


	public function getErrors () {
		$rc = $this->getGatewayObj()->getErrors();
		return $rc;
	}


	public function usesBasket () {
		$rc = $this->getGatewayObj()->usesBasket();
		return $rc;
	}


	public function getTransaction ($referenceId) {
		$rc = $this->getGatewayObj()->getTransaction($referenceId);
		return $rc;
	}


	public function generateReferenceUid ($orderuid, $callingExtension) {
		$rc = $this->getGatewayObj()->generateReferenceUid($orderuid, $callingExtension);
		return $rc;
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
		$rc = $this->getGatewayObj()->getReferenceUid();
		return $rc;
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
}


?>