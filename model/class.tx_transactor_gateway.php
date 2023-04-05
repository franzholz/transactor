<?php
/***************************************************************
*
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


/**
* Abstract class defining the interface for gateway implementations.
*
* All implementations must implement this interface but depending on the
* gatway modes they support, methods like transaction_validate won't
* do anything.
*
* @author	Franz Holzinger <franz@ttproducts.de>
* @package 	TYPO3
* @subpackage	tx_transactor
**/
abstract class tx_transactor_gateway implements tx_transactor_gateway_int, \TYPO3\CMS\Core\SingletonInterface {
    protected $gatewayKey = 'transactor';	// must be overridden
    protected $extKey = 'transactor';		// must be overridden
    protected $supportedGatewayArray = [];	// must be overridden
    protected $bTaxIncluded = false; 	// can be overridden
    protected $conf;
    protected $bSendBasket = false;	// Submit detailled basket informations like single products
    protected $optionsArray;
    protected $resultsArray = [];
    protected $config = [];
    protected $bMergeConf = true;
    protected $formActionURI = '';	// The action uri for the submit form

    private $errorStack;
    private $action;
    private $paymentMethod;
    private $gatewayMode;
    private $callingExtension;
    private $detailsArray;
    private $transactionId;
    private $reference;
    private $cookieArray = [];
    private $internalArray = []; // internal options


    /**
    * Constructor. Pass the class name of a gateway implementation.
    *
    * @param	string		$gatewayClass: Class name of a gateway implementation acting as the "Real Subject"
    * @return	void
    * @access	public
    */
    public function __construct () {
        $this->clearErrors();
        $conf = [];
        $extManagerConf = [];
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            $conf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
            )->get('transactor');
        } else { // before TYPO3 9
            $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['transactor']);
        }

        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            $extManagerConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
            )->get($this->getExtKey());
        } else { // before TYPO3 9
            $extManagerConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->getExtKey()]);
        }

        if ($this->bMergeConf && is_array($this->conf)) {
            if (is_array($extManagerConf)) {
                $conf = array_merge($this->conf, $extManagerConf);
            }
        } else if (is_array($extManagerConf)) {
            $conf = $extManagerConf;
        }

        $this->setConf($conf);
        $this->setCookieArray(
            ['fe_typo_user' => $_COOKIE['fe_typo_user']]
        );
    }


    public function getExtKey () {
        return $this->extKey;
    }


    public function getGatewayKey () {
        return $this->gatewayKey;
    }


    public function getConf () {
        return $this->conf;
    }


    public function setConf ($conf) {
        $this->conf = $conf;
    }


    public function getConfig () {
        return $this->config;
    }


    public function setConfig ($config, $index = '') {
        if ($index != '') {
            $this->config[$index] = $config;
        } else {
            $this->config = $config;
        }
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
    public function getAvailablePaymentMethods () {

        $filename = ExtensionManagementUtility::extPath($this->getExtKey()) . 'paymentmethods.xml';
        $filenamepath = GeneralUtility::getUrl(ExtensionManagementUtility::extPath($this->getExtKey()) . 'paymentmethods.xml');

        if ($filenamepath) {
            $result = GeneralUtility::xml2array($filenamepath);
            $errorIndices = $filenamepath;
        } else {
            $errorIndices = $filename . ' not found';
        }

        if (!is_array($result)) {
            $this->addError(
                'tx_transactor_gateway::getAvailablePaymentMethods "' . $this->getExtKey() . ':' . $errorIndices . ':' .  $result . '"'
            );
            $result = false;
        }
        return $result;
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
    public function supportsGatewayMode ($gatewayMode) {
        $result = in_array($gatewayMode, $this->supportedGatewayArray);
        return $result;
    }


    /**
    * Initializes a transaction.
    *
    * @param	integer		$action: Type of the transaction, one of the constants TX_TRANSACTOR_GATEWAYMODE_*
    * @param	string		$paymentMethod: Payment method, one of the values of getSupportedMethods()
    * @param	integer		$gatewayMode: Gateway mode for this transaction, one of the constants TX_TRANSACTOR_GATEWAYMODE_*
    * @param	string		$callingExtKey: Extension key of the calling script.
    * @param	array		$conf: configuration. This will override former configuration from the exension manager.
    * @return	void
    * @access	public
    */
    public function transaction_init (
        $action,
        $paymentMethod,
        $gatewayMode,
        $callingExtKey,
        $conf = []
    ) {
        if ($this->supportsGatewayMode($gatewayMode)) {
            $this->action = $action;
            $this->paymentMethod = $paymentMethod;
            $this->gatewayMode = $gatewayMode;
            $this->callingExtension = $callingExtKey;
            $theConf = $this->getConf();
            if (is_array($theConf) && is_array($conf)) {
                $theConf = array_merge($theConf, $conf);
                $this->setConf($theConf);
            }
            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }


    public function setCookieArray ($cookieArray) {
        if (is_array($cookieArray)) {
            $this->cookieArray = array_merge($this->cookieArray, $cookieArray);
        }
    }


    public function getCookies () {
        $result = '';
        if (count($this->cookieArray)) {
            $tmpArray = [];
            foreach ($this->cookieArray as $k => $v) {
                $tmpArray[] = $k . '=' . $v;
            }
            $result = implode('; ', $tmpArray);
        }
        return $result;
    }


    public function getLanguage () {
        $result = (
            isset($GLOBALS['TSFE']->config['config']) &&
            is_array($GLOBALS['TSFE']->config['config']) &&
            $GLOBALS['TSFE']->config['config']['language'] ?
                $GLOBALS['TSFE']->config['config']['language'] :
                'en'
        );

        return $result;
    }


    /**
    * Sets the payment details. Which fields can be set usually depends on the
    * chosen / supported gateway mode. TX_TRANSACTOR_GATEWAYMODE_FORM does not
    * allow setting credit card data for example.
    *
    * @param	array		$detailsArray: The payment details array
    * @return	boolean		Returns true if all required details have been set
    * @access	public
    */
    public function transaction_setDetails ($detailsArray) {
        $result = true;
        $this->detailsArray = $detailsArray;
        $reference = $detailsArray['reference'];
        $this->setReferenceUid($reference);

        if (
            isset($detailsArray['options']) &&
            is_array($detailsArray['options']) &&
            isset($this->optionsArray) &&
            is_array($this->optionsArray)
        ) {
            foreach ($detailsArray['options'] as $k => $v) {
                if (in_array($k, $this->optionsArray)) {
                    $this->setConfig($v, $k);
                }
            }
            $xmlOptions =
                GeneralUtility::array2xml(
                    $this->getConfig(),
                    '',
                    0,
                    'phparray',
                    [],
                    'utf-8'
                );
        }

        // Store order id in database
        $dataArray = [
            'crdate' => time(),
            'gatewayid' => '',
            'ext_key' => $this->callingExtension,
            'reference' => $reference,
            'orderuid' => $detailsArray['transaction']['orderuid'],
            'state' => TX_TRANSACTOR_TRANSACTION_STATE_NO_PROCESS,
            'amount' => $detailsArray['transaction']['amount'],
            'currency' => $detailsArray['transaction']['currency'],
            'paymethod_key' => $this->gatewayKey,
            'paymethod_method' => $this->paymentMethod,
            'message' => TX_TRANSACTOR_TRANSACTION_MESSAGE_NOT_PROCESSED,
            'config' => $xmlOptions,
            'user' => $detailsArray['user']
        ];
        $res =
            $GLOBALS['TYPO3_DB']->exec_DELETEquery(
                'tx_transactor_transactions',
                'paymethod_key =' .
                    $GLOBALS['TYPO3_DB']->fullQuoteStr(
                        $this->getGatewayKey(),
                        'tx_transactor_transactions'
                    ) .
                    ' AND amount LIKE "0.00"' .
                    ' AND message LIKE "' . TX_TRANSACTOR_TRANSACTION_MESSAGE_NOT_PROCESSED . '"'
            );

        if (($row = $this->getTransaction($reference)) === false) {
            $res =
                $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                    'tx_transactor_transactions',
                    $dataArray
                );
            $dbTransactionUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
            $this->setTransactionUid($dbTransactionUid);
        } else {
            $this->setTransactionUid($row['uid']);

            if (
                $row['state'] < TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_OK &&
                (
                    abs(round($row['amount'], 2) - round($dataArray['amount'], 2) > 0.1) ||
                    $row['gatewayid'] != $dataArray['gatewayid'] ||
                    $row['paymethod_key'] != $dataArray['paymethod_key'] ||
                    $row['paymethod_method'] != $dataArray['paymethod_method'] ||
                    $row['orderuid'] != $dataArray['orderuid']
                )
            ) {
                $res =
                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                        'tx_transactor_transactions',
                        'reference = ' .
                            $GLOBALS['TYPO3_DB']->fullQuoteStr(
                                $reference,
                                'tx_transactor_transactions'
                            ),
                        $dataArray
                    );
            }
        }

        if (!$res) {
            $result = false;
        }
        return $result;
    }


    public function setDetails ($detailsArray) {
        $this->detailsArray = $detailsArray;
    }


    public function getDetails () {
        return $this->detailsArray;
    }


    public function getGatewayMode () {
        return $this->gatewayMode;
    }


    public function getPaymentMethod () {
        return $this->paymentMethod;
    }


    public function getCallingExtension () {
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
    * @return	boolean		Returns true if validation was successful, false if not
    * @access	public
    */
    public function transaction_validate ($level=1) {
        return false;
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
        return false;
    }


    /**
    * Displays the form on which the user will finally submit the transaction to the payment gateway
    * Only supported with TX_TRANSACTOR_GATEWAYMODE_AJAX
    *
    * @return	string		HTML form and javascript
    * @access	public
    */
    public function transaction_getForm ($lConf) {
        return '';
    }


    /**
    * Returns the form action URI to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
    * This is used by PayPal and DIBS
    * @return	string		Form action URI
    * @access	public
    */
    public function transaction_formGetActionURI () {
        $result = false;

        if ($this->getGatewayMode() == TX_TRANSACTOR_GATEWAYMODE_FORM) {
            $conf = $this->getConf();
            if (isset($conf['formActionURI'])) {
                $result = $conf['formActionURI'];
            }
        }

        return $result;
    }


    /**
    * Returns any extra parameter for the form url to be used in mode
    * TX_TRANSACTOR_GATEWAYMODE_FORM.
    * It can also add the form url in the front and set the '?' as separator
    *
    * @return  string      Form tag extra parameters
    * @access  public
    */
    public function transaction_formGetFormParms () {
        return '';
    }


    /**
    * Returns any extra HTML attributes for the form tag to be used in mode
    * TX_TRANSACTOR_GATEWAYMODE_FORM.
    *
    * @return  string      Form submit button extra parameters
    * @access  public
    */
    public function transaction_formGetAttributes () {
        return '';
    }


    /**
    * Returns an array of field names and values which must be included as hidden
    * fields in the form you render use mode TX_TRANSACTOR_GATEWAYMODE_FORM.
    *
    * @return	array		Field names and values to be rendered as hidden fields
    * @access	public
    */
    public function transaction_formGetHiddenFields () {
        return false;
    }


    /**
    * Sets the URI which the user should be redirected to after a successful payment/transaction
    * If your gateway/gateway implementation only supports one redirect URI, set okpage and
    * errorpage to the same URI
    *
    * @return void
    * @access public
    */
    public function transaction_setOkPage ($uri) {
        $this->internalArray['return'] = $uri;
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
        $result = true;

        if (is_array($row)) {
            if ($row['message'] != TX_TRANSACTOR_TRANSACTION_MESSAGE_NOT_PROCESSED) {
                $result = false;
            }
        }

        return $result;
    }


    /**
    * Sets the URI which the user should be redirected to after a failed payment/transaction
    * If your gateway/gateway implementation only supports one redirect URI, set okpage and
    * errorpage to the same URI
    *
    * @return void
    * @access public
    */
    public function transaction_setErrorPage ($uri) {
        $this->internalArray['cancel_return'] = $uri;
    }


    /**
    * Returns the results of a processed transaction. You must override this by your method.
    *
    * @param	string		$reference
    * @return	array		Results of a processed transaction
    * @access	public
    */
    public function transaction_getResults ($reference) {
        $result =
            self::transaction_getResultsError(
                'internal error in extension "' . $this->getExtKey() . '": method "tx_transactor_gateway::transaction_getResults" has not been overwritten'
            );
        return $result;
    }


    /**
    * Returns the error result
    *
    * @param	string		$message ... message to show
    * @return	array		Results of an internal error
    * @access	public
    */
    public function transaction_getResultsError ($message) {
        $result =
            self::transaction_getResultsMessage(
                TX_TRANSACTOR_TRANSACTION_STATE_INTERNAL_ERROR,
                $message
            );
        return $result;
    }


    /**
    * Returns the error result
    *
    * @param	string		$message ... message to show
    * @return	array		Results of an internal error
    * @access	public
    */
    public function transaction_getResultsSuccess ($message) {
        $result =
            self::transaction_getResultsMessage(
                TX_TRANSACTOR_TRANSACTION_STATE_INIT,
                $message
            );
        return $result;
    }


    protected function transaction_getResultsMessage ($state, $message) {
        $resultsArray = [];
        $resultsArray['message'] = $message;
        $resultsArray['state'] = $state;
        $this->setResultsArray($resultsArray);
        return $resultsArray;
    }


    public function setResultsArray ($resultsArray) {
        $this->resultsArray = $resultsArray;
    }


    public function getResultsArray () {
        return $this->resultsArray;
    }


    public function transaction_succeded ($resultsArray) {

        if (
            in_array(
                $resultsArray['state'],
                [
                    TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_OK,
                    TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_DUPLICATE
                ]
            )
        ) {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }


    public function transaction_failed ($resultsArray) {

        if ($resultsArray['state'] == TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK) {
            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }


    public function transaction_message ($resultsArray) {

        if (isset($resultsArray['message'])) {
            $result = $resultsArray['message'];
        } else {
            $result = 'Internal error in extension "' . $this->getExtKey() . '": The resultsArray has not been filled inside of method transaction_message';
        }
        return $result;
    }


    public function clearErrors () {
        $this->errorStack = [];
    }


    public function addError ($error) {
        $this->errorStack[] = $error;
    }


    public function hasErrors () {
        $result = (count($this->errorStack) > 0);
    }


    public function getErrors () {
        return $this->errorStack;
    }


    public function usesBasket () {

        $detailsArray = $this->getDetails();
        $result = (
            intval($this->bSendBasket) > 0) &&
            isset($detailsArray['basket']) &&
            is_array($detailsArray['basket']) &&
            count($detailsArray['basket']
        );
        return $result;
    }


    // *****************************************************************************
    // Helpers
    // *****************************************************************************

    public function getTransaction ($reference) {
        $result = false;

        if ($reference != '') {
            $res =
                $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                    '*',
                    'tx_transactor_transactions',
                    'reference = ' .
                        $GLOBALS['TYPO3_DB']->fullQuoteStr(
                            $reference,
                            'tx_transactor_transactions'
                        )
                );

            if ($res) {
                $result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                $GLOBALS['TYPO3_DB']->sql_free_result($res);
            }
        }
        return $result;
    }


    /**
    * Sets the information that the tax is included in the amount of the transaction
    *
    * @param	boolean		if the tax is included
    * @return	void
    * @access	public
    */
    public function setTaxIncluded ($bTaxIncluded) {
        $this->bTaxIncluded = $bTaxIncluded;
    }


    /**
    * Fetches the information that the tax is included in the amount of the transaction
    *
    * @return	void		unique reference
    * @access	public
    */
    public function getTaxIncluded () {
        return $this->bTaxIncluded;
    }


    public function generateReferenceUid ($orderuid, $callingExtension) {
        $result = false;

        if ($orderuid) {
            $result = $this->gatewayKey . '#' . md5($callingExtension . '-' . $orderuid);
        }
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
        $this->reference = $reference;
    }


    /**
    * Fetches the reference of the transaction table
    *
    * @return	void		unique reference
    * @access	public
    */
    public function getReferenceUid () {
        return $this->reference;
    }


    /**
    * Sets the uid of the transaction table
    *
    * @param	integer		unique transaction id
    * @return	void
    * @access	public
    */
    public function setTransactionUid ($transUid) {
        $this->transactionId = $transUid;
    }


    /**
    * Fetches the uid of the transaction table
    *
    * @return	string		unique transaction id
    * @access	public
    */
    public function getTransactionUid () {
        return $this->transactionId;
    }


    /**
    * Sets the form action URI
    *
    * @param	string		form action URI
    * @return	void
    * @access	public
    */
    public function setFormActionURI ($formActionURI) {
        $this->formActionURI = $formActionURI;
    }


    /**
    * Fetches the form action URI
    *
    * @return	string		form action URI
    * @access	public
    */
    public function getFormActionURI () {
        return $this->formActionURI;
    }


    /**
    * This gives the information if the order can only processed after a verification message has been received.
    *
    * @return	boolean		true if a verification message needs to be sent
    * @access	public
    */
    public function needsVerificationMessage () {
        return false;
    }
}

