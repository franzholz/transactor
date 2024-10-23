<?php

namespace JambageCom\Transactor\Domain;

/***************************************************************
*
*  Copyright notice
*
*  (c) 2023 Franz Holzinger (franz@ttproducts.de)
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


use JambageCom\Transactor\Constants\Action;
use JambageCom\Transactor\Constants\Field;
use JambageCom\Transactor\Constants\GatewayMode;
use JambageCom\Transactor\Constants\Message;
use JambageCom\Transactor\Constants\State;

use JambageCom\Transactor\Api\PaymentApi;

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

abstract class GatewayBase implements GatewayInterface, \TYPO3\CMS\Core\SingletonInterface {
    protected $gatewayKey = 'gatewayname';	   // must be overridden
    protected $extensionKey = 'transactor';    // must be overridden
    protected $taxIncluded = true;
    protected $conf = [];
    protected $config = [];
    protected $mergeConf = true;
    protected $basket = [];
    protected $extraData = [];
    protected $basketSum = 0;
    protected $currency = 'EUR';
    protected $orderUid = 0;
    protected $orderNumber = '0';
    protected $sendBasket = false;	// Submit detailed basket informations like single products
    protected $optionsArray;
    protected $resultsArray = [];
    protected $formActionURI = '';	// The action uri for the submit form
    protected $checkoutURI = ''; // relative URI to the checkout action listener of the payment extension
    protected $captureURI = ''; // relative URI to the capture action listener of the payment extension

    protected $gatewayModeArray =
        [
            'form' => GatewayMode::FORM,
            'ajax' => GatewayMode::AJAX,
            'webservice' => GatewayMode::WEBSERVICE
        ];
    private $errorStack;
    private $action;
    private $paymentMethod;
    private $gatewayMode;
    private $templateFilename;
    private $callingExtension;
    private $detailsArray;
    private $transactionId;
    private $reference;
    private $cookieArray = [];
    private $internalArray = []; // internal options
    private $tablename = 'tx_transactor_transactions'; // name of the transactor table


    /**
    * Constructor. Pass the class name of a gateway implementation.
    *
    * @param	string		$gatewayClass: Class name of a gateway implementation acting as the "Real Subject"
    * @return	void
    * @access	public
    */
    public function __construct ()
    {
        $this->clearErrors();
        $conf =
            PaymentApi::getConf(
                $this->getExtensionKey(),
                $this->mergeConf,
                $this->getConf()
            );

        $this->setConf($conf);
        if (isset($_COOKIE['fe_typo_user'])) {
            $this->setCookieArray(
                ['fe_typo_user' => $_COOKIE['fe_typo_user']]
            );
        }
    }

    public function getTablename ()
    {
        return $this->tablename;
    }

    public function getExtensionKey ()
    {
        return $this->extensionKey;
    }

    public function getGatewayKey ()
    {
        return $this->gatewayKey;
    }

    public function setGatewayMode ($gatewayMode)
    {
        $this->gatewayMode = $gatewayMode;
    }

    public function getGatewayMode ()
    {
        return $this->gatewayMode;
    }

    public function setTemplateFilename ($templateFilename)
    {
        $this->templateFilename = $templateFilename;
    }

    public function getTemplateFilename ()
    {
        return $this->templateFilename;
    }

    public function getPaymentMethod ()
    {
        return $this->paymentMethod;
    }

    public function getCallingExtension ()
    {
        return $this->callingExtension;
    }

    public function getConf ()
    {
        return $this->conf;
    }

    public function setConf ($conf)
    {
        $this->conf = $conf;
    }

    public function getConfig ()
    {
        return $this->config;
    }

    public function setConfig ($config, $index = '')
    {
        if ($index != '') {
            $this->config[$index] = $config;
        } else {
            $this->config = $config;
        }
    }

    public function setBasket ($basket)
    {
        $this->basket = $basket;
    }

    public function getBasket ()
    {
        return $this->basket;
    }

    public function setExtraData ($key, $value)
    {
        $this->extraData[$key] = $value;
    }

    public function getExtraData ($key)
    {
        return $this->extraData[$key] ?? null;
    }

    public function setBasketSum ($basketSum)
    {
        $this->basketSum = doubleval($basketSum);
    }

    public function getBasketSum ()
    {
        return $this->basketSum;
    }

    public function setOrderUid ($orderUid)
    {
        $this->orderUid = $orderUid;
    }

    public function getOrderUid ()
    {
        return $this->orderUid;
    }

    public function setOrderNumber ($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    public function getOrderNumber ()
    {
        return $this->orderNumber;
    }

    public function getSendBasket ()
    {
        return $this->sendBasket;
    }

    public function setSendBasket ($sendBasket)
    {
        $this->sendBasket = $sendBasket;
    }

    /**
     * 3 letter currency code as defined by ISO 4217.
     *
     * @param string $currency
     *
     * @return void
     */
    public function setCurrency ($currency)
    {
        $this->currency = $currency;
    }

    /**
     * 3 letter currency code as defined by ISO 4217.
     *
     * @return string
     */
    public function getCurrency ()
    {
        return $this->currency;
    }

    /**
    * Returns true if the payment implementation supports the given gateway mode.
    * All implementations should at least support the mode
    * GatewayMode::FORM.
    *
    * GatewayMode::WEBSERVICE usually requires your webserver and
    * the whole application to be certified if used with certain credit cards.
    *
    * @param	integer		$gatewayMode: The gateway mode to check for. One of the constants GatewayMode::*
    * @return	array / boolean
    *           array of available payment methods and their attributes
    *           false in case of error
    * @access	public
    */
    public function getAvailablePaymentMethods ()
    {
        $result = false;
        $errorIndices = '';
        $filenamebase = 'Resources/Private/Payment/methods.xml';
        $filenamepath = ExtensionManagementUtility::extPath($this->getExtensionKey()) . $filenamebase;

        $xmlContent =
            GeneralUtility::getUrl(
                $filenamepath
            );

        if ($xmlContent) {
            $result = GeneralUtility::xml2array($xmlContent);
            if (!is_array($result)) {
                $errorIndices = ' has invalid xml. ' . $result;
            }
        } else {
            $errorIndices = ' not found';
        }

        if ($errorIndices != '') {
            $this->addError(
                'JambageCom\Transactor\Domain\Gateway::getAvailablePaymentMethods ' . $this->getExtensionKey() . ': file "' . $filenamebase . '"' . $errorIndices
            );
            $result = false;
        }
        return $result;
    }

    /**
    * @param    string       $mode of the gateway: 'form', 'ajax' or 'webservice'
    * @return   integer      type of the transaction, one of the constants GatewayMode::*
    */
    public function convertGatewayMode (
        $mode = 'form'
    )
    {
        $mode = strtolower($mode);
        $gatewayMode = GatewayMode::INVALID;

        if (isset($this->gatewayModeArray[$mode])) {
            $gatewayMode = $this->gatewayModeArray[$mode];
        }

        return $gatewayMode;
    }

    /**
    * Initializes a transaction.
    *
    * @param	integer		$action: Type of the transaction, one of the constants GatewayMode::*
    * @param	string		$paymentMethod: Payment method, one of the values of getSupportedMethods()
    * @param	string		$callingExtensionKey: Extension key of the calling script.
    * @param	integer		$orderUid: order unique id
    * @param	string		$orderNumber: order identifier name which also contains the number
    * @param	string		$currency: 3 letter currency code as defined by ISO 4217.
    * @param	array		$conf: configuration. This will override former configuration from the exension manager.
    * @param	array		$basket: items in the basket
    * @param	array		$extraData: 'return_url', 'cancel_url'
    * @return	boolean     true if the initialization went fine
    * @access	public
    */
    public function transactionInit (
        $action,
        $paymentMethod,
        $callingExtensionKey,
        $templateFilename = '',
        $orderUid = 0,
        $orderNumber = '0',
        $currency = 'EUR',
        $conf = [],
        $basket = [],
        $extraData = []
    )
    {
        $result = true;
        $this->action = $action;
        $this->paymentMethod = $paymentMethod;
        $this->callingExtension = $callingExtensionKey;
        $this->setCurrency($currency);

        $theConf = $this->getConf();

        if (is_array($theConf) && is_array($conf)) {
            $theConf = array_merge($theConf, $conf);
            $this->setConf($theConf);
        }
        $this->setBasket($basket);
        if (
            isset($extraData['return_url']) &&
            isset($extraData['cancel_url'])
        ) {
            $this->setExtraData('return_url', GeneralUtility::locationHeaderUrl($extraData['return_url']));
            $this->setExtraData('cancel_url', GeneralUtility::locationHeaderUrl($extraData['cancel_url']));
        }

        $basketSum = 0;
        foreach ($basket as $record) {
            $recordSum = ($record[Field::PRICE_TAX] ?? 0) * ($record[Field::QUANTITY] ?? 0);
            $basketSum += $recordSum;
        }
        $this->setBasketSum($basketSum);

        if (
            $orderUid &&
            $orderNumber
        ) {
            $this->setOrderUid($orderUid);
            $this->setOrderNumber($orderNumber);
        }

        $paymentMethodsArray = $this->getAvailablePaymentMethods();
        if (isset($paymentMethodsArray[$paymentMethod]['gatewaymode'])) {
            $gatewayModeValue = $paymentMethodsArray[$paymentMethod]['gatewaymode'];
        } else {
            $gatewayModeValue = 'form';
        }
        $gatewayMode = $this->convertGatewayMode($gatewayModeValue);
        if ($gatewayMode == GatewayMode::INVALID) {
            $result = false;
        }
        $this->setGatewayMode($gatewayMode);

        if (empty($templateFilename)) {
            if (isset($paymentMethodsArray[$paymentMethod]['template'])) {
                $templateFilename = $paymentMethodsArray[$paymentMethod]['template'];
            } else {
                $templateFilename = 'EXT:transactor/Resources/Private/Templates/PaymentHtmlTemplate.html';
            }
        }

        $this->setTemplateFilename($templateFilename);

        return $result;
    }

    public function setCookieArray ($cookieArray)
    {
        if (is_array($cookieArray)) {
            $this->cookieArray = array_merge($this->cookieArray, $cookieArray);
        }
    }

    public function getCookies ()
    {
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

    public function getLanguage ()
    {
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
    * chosen / supported gateway mode. GatewayMode::FORM does not
    * allow setting credit card data for example.
    *
    * @param	array		$detailsArray: The payment details array
    * @return	boolean		Returns true if all required details have been set
    * @access	public
    */
    public function transactionSetDetails ($detailsArray)
    {
        $result = true;
        $xmlOptions = '';
        $this->setDetails($detailsArray);
        $reference = $detailsArray['reference'];
        $transaction = $detailsArray['transaction'];
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
                '<?xml version=”1.0” encoding=”utf-8” standalone=”yes” ?>' . LF .
                GeneralUtility::array2xml(
                    $this->getConfig()
                );
        }
        $xmlExtensionConfiguration =
            '<?xml version=”1.0” encoding=”utf-8” standalone=”yes” ?>' . LF .
            GeneralUtility::array2xml(
                $this->getConf()
            );

        // Store order id in database
        $dataArray = [
            'crdate' => time(),
            'ext_key' => $this->callingExtension,
            'reference' => $reference,
            'orderuid' => $transaction['orderuid'] ?? 0,
            'state' => State::IDLE,
            'amount' => $transaction['amount'] ?? 0,
            'currency' => $transaction['currency'] ?? 'EUR',
            'paymethod_key' => $this->getGatewayKey(),
            'paymethod_method' => $this->getPaymentMethod(),
            'message' => Message::NOT_PROCESSED,
            'config' => $xmlOptions,
            'config_ext' => $xmlExtensionConfiguration,
            'user' => json_encode($detailsArray['user'] ?? '')
        ];

        if (($row = $this->getTransaction($reference)) === false) {
            $res =
                $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                    $this->getTablename(),
                    $dataArray
                );
            $dbTransactionUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
            $this->setTransactionUid($dbTransactionUid);
            if (!$dbTransactionUid) {
                $result = false;
            }
        } else {
            $this->setTransactionUid($row['uid']);

            if (
                $row['state'] < State::APPROVE_OK &&
                (
                    abs(
                        round(
                            $row['amount'], 2
                        ) -
                        round(
                            $dataArray['amount'],
                            2
                        ) > 0.1
                    ) ||
                    $row['paymethod_key'] != $dataArray['paymethod_key'] ||
                    $row['paymethod_method'] != $dataArray['paymethod_method'] ||
                    $row['orderuid'] != $dataArray['orderuid']
                )
            ) {
                $res =
                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                        $this->getTablename(),
                        'reference = ' .
                            $GLOBALS['TYPO3_DB']->fullQuoteStr(
                                $reference,
                                $this->getTablename()
                            ),
                        $dataArray
                    );
                if (!$res) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    /**
    * Updates the transactor record with new values.
    * Use this method to change the gateway specific transaction id in the field gatewayid
    *
    * @param    array       $values: key => value pairs of the transactor record
    * @return   boolean     Returns true if all values could be changed
    * @access   public
    */
    public function transactionChangeValues (array $values)
    {
        $result = false;
        $transactionUid = $this->getTransactionUid();
        if ($transactionUid) {
            $res =
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                    $this->getTablename(),
                    'uid = ' .
                        $GLOBALS['TYPO3_DB']->fullQuoteStr(
                            $transactionUid,
                            $this->getTablename()
                        ),
                    $values
                );
            if (!$res) {
                $result = false;
            }
        }

        return $result;
    }

    public function setDetails ($detailsArray)
    {
        $this->detailsArray = $detailsArray;
    }

    public function getDetails ()
    {
        return $this->detailsArray;
    }

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
    public function transactionValidate ($level = 1)
    {
        return false;
    }

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
    public function transactionProcess (&$errorMessage)
    {
        return false;
    }

    /**
    * Displays the form on which the user will finally submit the transaction to the payment gateway
    * Only supported with GatewayMode::AJAX
    *
    * @return	string		HTML form and javascript
    * @access	public
    */
    public function transactionGetForm ()
    {
        return '';
    }

    /**
    * Fetches the details of the last occurred error in a string format.
    *
    * @return   string      details of the last error
    * @access   public
    */  public function transactionGetErrorDetails ()
    {
        $result = '(' . $this->getExtensionKey() . ') No details function transactionGetErrorDetails has been written.';
        return $result;
    }

    /**
    * Returns the form action URI to be used in mode GatewayMode::FORM.
    * This is used by PayPal and DIBS
    * @return	string		Form action URI
    * @access	public
    */
    public function transactionFormGetActionURI ()
    {
        $result = false;

        if ($this->getGatewayMode() ==  GatewayMode::FORM) {
            $conf = $this->getConf();
            if (isset($conf['formActionURI'])) {
                $result = $conf['formActionURI'];
            }
        }

        return $result;
    }

    /**
    * Returns any extra parameter for the form url to be used in mode
    * GatewayMode::FORM.
    * It can also add the form url in the front and set the '?' as separator
    *
    * @return  string      Form tag extra parameters
    * @access  public
    */
    public function transactionFormGetFormParms ()
    {
        return '';
    }

    /**
    * Returns any extra HTML attributes for the form tag to be used in mode
    * GatewayMode::FORM.
    *
    * @return  string      Form submit button extra parameters
    * @access  public
    */
    public function transactionFormGetAttributes ()
    {
        return '';
    }

    /**
    * Returns an array of field names and values which must be included as hidden
    * fields in the form you render. Use mode GatewayMode::FORM.
    *
    * @return	array		Field names and values to be rendered as hidden fields
    * @access	public
    */
    public function transactionFormGetHiddenFields ()
    {
        return false;
    }

    /**
    * Returns an array of field names and values which must be included as script
    * parameters in the form you render. Use mode GatewayMode::FORM.
    * <script src="https://mywebsite.com" data-parameter-1="foo:bar"></script>
    *
    * @return	array		paramter names and values to be rendered as script parameters
    * @access	public
    */
    public function transactionFormGetScriptParameters ()
    {
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
    public function transactionSetOkPage ($uri)
    {
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
    public function transactionIsInitState ($row)
    {
        $result = true;

        if (is_array($row)) {
            if ($row['message'] != Message::NOT_PROCESSED) {
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
    public function transactionSetErrorPage ($uri)
    {
        $this->internalArray['cancel_return'] = $uri;
    }

    /**
    * Returns the results of a processed transaction. You must override this by your method.
    *
    * @param	string		$reference
    * @return	array		Results of a processed transaction
    * @access	public
    */
    public function transactionGetResults ($reference)
    {
        $result =
            self::transactionGetResultsError(
                'internal error in extension "' . $this->getExtensionKey() . '": method "tx_transactor_gateway::transaction_getResults" has not been overwritten'
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
    public function transactionGetResultsError ($message)
    {
        $result =
            self::transactionGetResultsMessage(
                State::INTERNAL_ERROR,
                $message
            );
        return $result;
    }

    /**
    * Returns the success result
    *
    * @param	string		$message ... message to show
    * @return	array		Results of a success
    * @access	public
    */
    public function transactionGetResultsSuccess ($message)
    {
        $result =
            self::transactionGetResultsMessage(
                State::INIT,
                $message
            );
        return $result;
    }

    protected function transactionGetResultsMessage ($state, $message)
    {
        $resultsArray = [];
        $resultsArray['message'] = $message;
        $resultsArray['state'] = $state;
        $this->setResultsArray($resultsArray);
        return $resultsArray;
    }

    /**
    * Returns the parameters of the recently processed transaction
    *
    * @return	array		parameters of the last processed transaction
    * @access	public
    */
    public function transactionGetParameters ()
    {
        $result = [];
        return $result;
    }

    public function setResultsArray ($resultsArray)
    {
        $this->resultsArray = $resultsArray;
    }

    public function getResultsArray ()
    {
        return $this->resultsArray;
    }

    public function getEmptyResultsArray ($reference, $currency)
    {
        $resultsArray = [
            'gatewayid' => '',
            'reference' => $reference,
            'currency' => $currency,
            'amount' => '0.00',
            'state' => State::IDLE,
            'state_time' => time(),
            'message' => Message::NOT_PROCESSED,
            'ext_key' => $this->getCallingExtension(),
            'paymethod_key' => $this->getExtensionKey(),
            'paymethod_method' => $this->getPaymentMethod()
        ];

        return $resultsArray;
    }

    public function transactionSucceeded ($resultsArray)
    {
        $result = false;

        if (
            in_array(
                $resultsArray['state'],
                [
                    State::APPROVE_OK,
                    State::APPROVE_DUPLICATE
                ]
            )
        ) {
            $result = true;
        }
        return $result;
    }

    public function transactionFailed ($resultsArray)
    {
        $result = false;
        if ($resultsArray['state'] == State::APPROVE_NOK) {
            $result = true;
        }
        return $result;
    }

    public function transactionMessage ($resultsArray)
    {
        $result = '';

        if (isset($resultsArray['message'])) {
            $result = $resultsArray['message'];
        } else {
            $result = 'Internal error in extension "' . $this->getExtensionKey() . '": The resultsArray has not been filled inside of method transaction_message';
        }
        return $result;
    }

    public function clearErrors ()
    {
        $this->errorStack = [];
    }

    public function addError ($error)
    {
        $this->errorStack[] = $error;
    }

    public function hasErrors ()
    {
        $result = (count($this->errorStack) > 0);
    }

    public function getErrors ()
    {
        return $this->errorStack;
    }

    public function useBasket ()
    {
        $detailsArray = $this->getDetails();
        $result = (
            $this->sendBasket &&
            isset($detailsArray['basket']) &&
            is_array($detailsArray['basket']) &&
            count($detailsArray['basket'])
        );
        return $result;
    }

    // *****************************************************************************
    // Helpers
    // *****************************************************************************

    public function getTransaction ($reference)
    {
        $result = false;

        if ($reference != '') {
            $result =
                $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                    '*',
                    $this->getTablename(),
                    'reference = ' .
                        $GLOBALS['TYPO3_DB']->fullQuoteStr(
                            $reference,
                            $this->getTablename()
                        )
                );
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
    public function setTaxIncluded ($taxIncluded)
    {
        $this->taxIncluded = $taxIncluded;
    }

    /**
    * Fetches the information that the tax is included in the amount of the transaction
    *
    * @return	void		unique reference
    * @access	public
    */
    public function getTaxIncluded ()
    {
        return $this->taxIncluded;
    }

    public function generateReferenceUid ($orderuid, $callingExtension)
    {
        $result = false;

        if ($orderuid) {
            $result = $this->getGatewayKey() . '#' . md5($callingExtension . '-' . $orderuid);
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
    public function setReferenceUid ($reference)
    {
        $this->reference = $reference;
    }

    /**
    * Fetches the reference of the transaction table
    *
    * @return	void		unique reference
    * @access	public
    */
    public function getReferenceUid ()
    {
        return $this->reference;
    }

    /**
    * Sets the uid of the transaction table
    *
    * @param	integer		unique transaction id
    * @return	void
    * @access	public
    */
    public function setTransactionUid ($transUid)
    {
        $this->transactionId = $transUid;
    }

    /**
    * Fetches the uid of the transaction table
    *
    * @return	string		unique transaction id
    * @access	public
    */
    public function getTransactionUid ()
    {
        return $this->transactionId;
    }

    /**
    * Sets the form action URI
    *
    * @param	string		form action URI
    * @return	void
    * @access	public
    */
    public function setFormActionURI ($formActionURI)
    {
        $this->formActionURI = $formActionURI;
    }

    /**
    * Fetches the form action URI
    *
    * @return	string		form action URI
    * @access	public
    */
    public function getFormActionURI ()
    {
        return $this->formActionURI;
    }

    /**
    * Sets the checkout Transactor URI
    *
    * @param	string		checkout URI
    * @return	void
    * @access	public
    */
    public function setCheckoutURI ($checkoutURI)
    {
        $this->checkoutURI = $checkoutURI;
    }

    /**
    * Fetches the checkout Transactor URI
    *
    * @return	string		checkout URI
    * @access	public
    */
    public function getCheckoutURI ()
    {
        return $this->checkoutURI;
    }

    /**
    * Sets the capture Transactor URI
    *
    * @param	string		capture URI
    * @return	void
    * @access	public
    */
    public function setCaptureURI ($captureURI)
    {
        $this->captureURI = $captureURI;
    }

    /**
    * Fetches the capture Transactor URI
    *
    * @return	string		capture URI
    * @access	public
    */
    public function getCaptureURI ()
    {
        return $this->captureURI;
    }

    /**
    * This gives the information if the order can only processed after a verification message has been received.
    *
    * @return	boolean		true if a verification message needs to be sent
    * @access	public
    */
    public function needsVerificationMessage ()
    {
        return false;
    }

    /**
    * This fetches the class of the controller if a given feature is supported by the gateway.
    *
    * @param	integer		feature of constant \JambageCom\Transactor\Constants\Feature
    * @return	boolean		true if a feature is supported
    * @access	public
    */
    public function getFeatureClass ($feature)
    {
        return false;
    }
}
