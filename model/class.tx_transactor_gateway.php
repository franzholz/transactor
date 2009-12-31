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

require_once(t3lib_extMgm::extPath('transactor') . 'interfaces/interface.tx_transactor_gateway_int.php');


/**
 * Abstract class defining the interface for gateway implementations.
 *
 * All implementations must implement this interface but depending on the
 * gatway modes they support, methods like transaction_validate won't
 * do anything.
 *
 * @package 	TYPO3
 * @subpackage	tx_transactor
 * @author	Franz Holzinger <franz@ttproducts.de>
**/
abstract class tx_transactor_gateway implements tx_transactor_gateway_int {
	protected $gatewayKey = "transactor";	// must be overridden
	protected $extKey = "transactor";		// must be overridden
	protected $supportedGatewayArray = array();	// must be overridden
	protected $conf;
	protected $bSendBasket;
	protected $optionsArray;
	protected $resultsArray = array();
	protected $config = array();
	private $errorStack;
	private	$action;
	private	$paymentMethod;
	private	$gatewayMode;
	private	$callingExtension;
	private $detailsArr;
	private $transactionId;
	private $referenceId;
	private $cookieArray = array();


	/**
	 * Constructor. Pass the class name of a gateway implementation.
	 *
	 * @param	string		$gatewayClass: Class name of a gateway implementation acting as the "Real Subject"
	 * @return	void
	 * @access	public
	 */
	public function __construct () {
		global $TSFE;

		$this->clearErrors();
		$this->conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['transactor']);
		$extManagerConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);

		if (is_array($this->conf))	{
			if (is_array($extManagerConf))	{
				$this->conf = array_merge($this->conf, $extManagerConf);
			}
		} else if (is_array($extManagerConf))	{
			$this->conf = $extManagerConf;
		}

		$this->setCookieArray(
			array('fe_typo_user' => $_COOKIE['fe_typo_user'])
		);
	}


	public function getGatewayKey ()	{
			return $this->gatewayKey;
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
	 public function getAvailablePaymentMethods () {

		$filename = t3lib_extMgm::extPath($this->extKey) . 'paymentmethods.xml';
		$filenamepath = t3lib_div::getUrl(t3lib_extMgm::extPath($this->extKey) . 'paymentmethods.xml');

		if ($filenamepath)	{
			$rc = t3lib_div::xml2array($filenamepath);
			$errorIndices = $filenamepath;
		} else {
			$errorIndices = $filename . ' not found';
		}

		if (!is_array($rc))	{
			$this->addError('tx_transactor_gateway::getAvailablePaymentMethods "' . $this->extKey . ':' . $errorIndices . ':' .  $rc . '"');
			$rc = FALSE;
		}
		return $rc;
	}


	/**
	 * Initializes a transaction.
	 *
	 * @param	integer		$action: Type of the transaction, one of the constants TX_TRANSACTOR_GATEWAYMODE_*
	 * @param	string		$paymentMethod: Payment method, one of the values of getSupportedMethods()
	 * @param	integer		$gatewayMode: Gateway mode for this transaction, one of the constants TX_TRANSACTOR_GATEWAYMODE_*
	 * @param	string		$callingExtKey: Extension key of the calling script.
	 * @return	void
	 * @access	public
	 */
	public function supportsGatewayMode ($gatewayMode)	{
		$rc = in_array($gatewayMode, $this->supportedGatewayArray);
		return $rc;
	}


	/**
	 * Initializes a transaction.
	 *
	 * @param	integer		$action: Type of the transaction, one of the constants TX_TRANSACTOR_GATEWAYMODE_*
	 * @param	string		$paymentMethod: Payment method, one of the values of getSupportedMethods()
	 * @param	integer		$gatewayMode: Gateway mode for this transaction, one of the constants TX_TRANSACTOR_GATEWAYMODE_*
	 * @param	string		$callingExtKey: Extension key of the calling script.
	 * @param	array		$config: configuration. This will override former configuration from the exension manager.
	 * @return	void
	 * @access	public
	 */
	public function transaction_init ($action, $paymentMethod, $gatewayMode, $callingExtKey, $config=array())	{

		if ($this->supportsGatewayMode($gatewayMode))	{
			$this->action = $action;
			$this->paymentMethod = $paymentMethod;
			$this->gatewayMode = $gatewayMode;
			$this->callingExtension = $callingExtKey;
			if (is_array($this->config) && is_array($config))	{
				$this->config = array_merge($this->config, $config);
			}
			$rc = TRUE;
		} else {
			$rc = FALSE;
		}
		return $rc;
	}


	public function getConf ()	{
		return $this->conf;
	}


	public function getConfig ()	{
		return $this->config;
	}


	public function setConfig (&$config)	{
		$this->config = $config;
	}


	public function setCookieArray ($cookieArray)	{
		if (is_array($cookieArray))	{
			$this->cookieArray = array_merge($this->cookieArray, $cookieArray);
		}
	}


	public function getCookies ()	{
		$rc = '';
		if (count($this->cookieArray))	{
			$tmpArray = array();
			foreach ($this->cookieArray as $k => $v)	{
				$tmpArray[] = $k . '=' . $v;
			}
			$rc = implode('; ',$tmpArray);
		}
		return $rc;
	}


	public function getLanguage ()	{
		global $TSFE;

		$rc = (isset($TSFE->config['config']) && is_array($TSFE->config['config']) && $TSFE->config['config']['language'] ? $TSFE->config['config']['language'] : 'en');
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
	public function transaction_setDetails ($detailsArr)	{
		global $TYPO3_DB;

		$rc = TRUE;
		$this->detailsArr = $detailsArr;

		$referenceId = $detailsArr['options']['reference'];

		$this->setReferenceUid($referenceId);
		$this->config = array();
		$this->config['currency_code'] = $detailsArr['transaction']['currency'];
		if (ord($this->config['currency_code']) == 128)	{ // 'euro symbol'
			$this->config['currency_code'] = 'EUR';
		}
		$this->config['return'] = ($detailsArr['transaction']['successlink'] ? $detailsArr['transaction']['successlink'] : $this->conf['return']);
		$this->config['cancel_return'] = ($detailsArr['transaction']['returi'] ? $detailsArr['transaction']['returi'] : $this->conf['cancel_return']);

		if (isset($detailsArr['options']) && is_array($detailsArr['options']) && isset($this->optionsArray) && is_array($this->optionsArray))	{

			foreach ($detailsArr['options'] as $k => $v)	{
				if (in_array($k, $this->optionsArray))	{
					$this->config[$k] = $v;
				}
			}
			$xmlOptions = t3lib_div::array2xml_cs($this->config,'phparray',array(),'utf-8');
		}

		// Store order id in database
		$dataArr = array(
			'crdate' => time(),
			'gatewayid' => $this->gatewayKey,
			'ext_key' => $this->callingExtension,
			'reference' => $referenceId,
			'state' => TX_TRANSACTOR_TRANSACTION_STATE_NO_PROCESS,
			'amount' => $detailsArr['transaction']['amount'],
			'currency' => $detailsArr['transaction']['currency'],
			'paymethod_key' => $this->gatewayKey,
			'paymethod_method' => $this->paymentMethod,
			'message' => TX_TRANSACTOR_TRANSACTION_MESSAGE_NOT_PROCESSED,
			'config' => $xmlOptions,
			'user' => $detailsArr['user']
		);

		$res = $TYPO3_DB->exec_DELETEquery('tx_transactor_transactions', 'gatewayid =' . $TYPO3_DB->fullQuoteStr($this->getGatewayKey(), 'tx_transactor_transactions') . ' AND amount LIKE "0.00" AND message LIKE "s:25:\"Transaction not processed\";"');

		if ($this->getTransaction($referenceId) === FALSE)	{
			$res = $TYPO3_DB->exec_INSERTquery('tx_transactor_transactions', $dataArr);
			$dbTransactionUid = $TYPO3_DB->sql_insert_id();
			$this->setTransactionUid($dbTransactionUid);
		} else {
			$dbResult = $TYPO3_DB->exec_SELECTquery('*', 'tx_transactor_transactions', 'reference = ' . $TYPO3_DB->fullQuoteStr($referenceId, 'tx_transactor_transactions'));
			if ($dbResult) {
				$row = $TYPO3_DB->sql_fetch_assoc($dbResult);
				$this->setTransactionUid($row['uid']);

				$res = $TYPO3_DB->exec_UPDATEquery('tx_transactor_transactions', 'reference = ' . $TYPO3_DB->fullQuoteStr($referenceId, 'tx_transactor_transactions'), $dataArr);
			}
		}

		if (!$res)	{
			$rc = FALSE;
		}

		return $rc;
	}


	public function getDetails ()	{
		return $this->detailsArr;
	}


	public function getGatewayMode ()	{
		return $this->gatewayMode;
	}


	public function getPaymentMethod ()	{
		return $this->paymentMethod;
	}


	public function getCallingExtension ()	{
		return $this->callingExtension;
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
	public function transaction_validate ($level=1) 	{
		return FALSE;
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
	public function transaction_process ()	{
		return FALSE;
	}


	/**
	 * Returns the form action URI to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * @return	string		Form action URI
	 * @access	public
	 */
	public function transaction_formGetActionURI ()	{
		if ($this->getGatewayMode() == TX_TRANSACTOR_GATEWAYMODE_FORM)	{
			$rc = $this->conf['formActionURI'];
		} else {
			$rc = FALSE;
		}
		return $rc;
	}


	/**
	* Returns any extra parameter for the form tag to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	*
	* @return  string      Form tag extra parameters
	* @access  public
	*/
	public function transaction_formGetFormParms ()	{
		return '';
	}


	/**
	* Returns any extra parameter for the form submit button to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	*
	* @return  string      Form submit button extra parameters
	* @access  public
	*/
	public function transaction_formGetSubmitParms ()	{
		return '';
	}


	/**
	 * Returns an array of field names and values which must be included as hidden
	 * fields in the form you render use mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * @return	array		Field names and values to be rendered as hidden fields
	 * @access	public
	 */
	public function transaction_formGetHiddenFields ()	{
		return FALSE;
	}


	public function transaction_formGetVisibleFields () 	{
		return FALSE;
	}


	/**
	 * Sets the URI which the user should be redirected to after a successful payment/transaction
	 * If your gateway/gateway implementation only supports one redirect URI, set okpage and
	 * errorpage to the same URI
	 *
	 * @return void
	 * @access public
	 */
	public function transaction_setOkPage ($uri)	{
		$this->config['return'] = $uri;
	}


	/**
	 * Sets the URI which the user should be redirected to after a failed payment/transaction
	 * If your gateway/gateway implementation only supports one redirect URI, set okpage and
	 * errorpage to the same URI
	 *
	 * @return void
	 * @access public
	 */
	public function transaction_setErrorPage ($uri)	{
		$this->config['cancel_return'] = $uri;
	}


	/**
	 * Returns the results of a processed transaction. You must override this by your method.
	 *
	 * @param	string		$reference
	 * @return	array		Results of a processed transaction
	 * @access	public
	 */
	public function transaction_getResults ($reference)	{
		$resultsArray = array();
		$resultsArray['message'] = 'internal error in extension "' . $this->extKey . '": method "tx_transactor_gateway::transaction_getResults" has not been overwritten';
		$resultsArray['state'] = TX_TRANSACTOR_TRANSACTION_STATE_INTERNAL_ERROR;
		$this->setResultsArray($resultsArray);
		return $resultsArray;
	}


	public function setResultsArray ($resultsArray)	{
		$this->resultsArray = $resultsArray;
	}


	public function getResultsArray ()	{
		return $this->resultsArray;
	}


	public function transaction_succeded ($resultsArray)	{
		if ($resultsArray['state'] == TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_OK || $resultsArray['state'] == TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_DUPLICATE)	{
			$rc = TRUE;
		} else {
			$rc = FALSE;
		}
		return $rc;
	}


	public function transaction_failed ($resultsArray)	{

		if ($resultsArray['state'] == TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK)	{
			$rc = TRUE;
		} else {
			$rc = FALSE;
		}

		return $rc;
	}


	public function transaction_message ($resultsArray)	{

		if (isset($resultsArray['message']))	{
			$rc = $resultsArray['message'];
		} else {
			$rc = 'internal error in extension "' . $this->extKey . '": The resultsArray has not been filled for method transaction_message';
		}
		return $rc;
	}


	public function clearErrors ()	{
		$this->errorStack = array();
	}


	public function addError ($error)	{
		$this->errorStack[] = $error;
	}


	public function hasErrors ()	{
		$rc = (count($this->errorStack) > 0);
	}


	public function getErrors ()	{
		return $this->errorStack;
	}


	public function usesBasket ()	{

		$detailsArray = $this->getDetails();
		$rc = (intval($this->bSendBasket) > 0) && isset($detailsArray['basket']) && is_array($detailsArray['basket']) && count($detailsArray['basket']);
		return $rc;
	}


	// *****************************************************************************
	// Helpers
	// *****************************************************************************

	public function getTransaction ($referenceId)	{
		global $TYPO3_DB;

		$rc = FALSE;
		$res = $TYPO3_DB->exec_SELECTquery('*', 'tx_transactor_transactions', 'reference = "' . $referenceId . '"');

		if ($referenceId !='' && $res)	{
			$rc = $TYPO3_DB->sql_fetch_assoc($res);
		}
		return $rc;
	}


	public function generateReferenceUid ($orderuid, $callingExtension)	{
		$rc = $this->gatewayKey . '#' . md5($callingExtension . '-' . $orderuid);
		return $rc;
	}


	/**
	 * Sets the reference of the transaction table
	 *
	 * @param	integer		unique transaction id
	 * @return	void
	 * @access	public
	 */
	public function setReferenceUid ($reference)	{
		$this->referenceId = $reference;
	}


	/**
	 * Fetches the reference of the transaction table
	 *
	 * @return	void		unique reference
	 * @access	public
	 */
	public function getReferenceUid ()	{
		return $this->referenceId;
	}


	/**
	 * Sets the uid of the transaction table
	 *
	 * @param	integer		unique transaction id
	 * @return	void
	 * @access	public
	 */
	public function setTransactionUid ($transUid)	{
		$this->transactionId = $transUid;
	}


	/**
	 * Fetches the uid of the transaction table
	 *
	 * @return	void		unique transaction id
	 * @access	public
	 */
	public function getTransactionUid ()	{
		return $this->transactionId;
	}
}

?>