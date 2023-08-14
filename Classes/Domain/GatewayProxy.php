<?php

namespace JambageCom\Transactor\Domain;

/***************************************************************
*
*  Copyright notice
*
*  (c) 2017 Franz Holzinger (franz@ttproducts.de)
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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\Transactor\Domain\Gateway;
use JambageCom\Transactor\Constants\GatewayMode;


/**
* Proxy class implementing the interface for gateway implementations. This
* class hangs between the real gateway implementation and the application
* using it.
*
* @package 	TYPO3
* @subpackage	transactor
* @author	Robert Lemke <robert@typo3.org>
* @author	Franz Holzinger <franz@ttproducts.de>
*/
class GatewayProxy implements \JambageCom\Transactor\Domain\GatewayInterface
{
    private $gatewayExtension = '';
    private $gatewayClass = '';
    protected $extensionManagerConf = [];


    /**
    * Initialization. Pass the class name of a gateway implementation.
    *
    * @param	string		$gatewayClass: Class name of a gateway implementation acting as the "Real Subject"
    * @return	void
    * @access	public
    */
    public function init ($extensionKey)
    {
        $this->gatewayClass = '';
        $this->extensionManagerConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get('transactor');

        $newExtensionManagerConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get($extensionKey);

        if (is_array($this->extensionManagerConf)) {
            if (is_array($newExtensionManagerConf)) {
                $this->extensionManagerConf =
                    array_merge(
                        $this->extensionManagerConf,
                        $newExtensionManagerConf
                    );
            }
        } else {
            $this->extensionManagerConf = $newExtensionManagerConf;
        }

        if (
            isset($this->extensionManagerConf['gatewayClass'])
        ) {
            $this->gatewayClass = $this->extensionManagerConf['gatewayClass'];
        } else {
            $composerFile = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($extensionKey)) . 'composer.json';
            if (file_exists($composerFile)) {
                $content = file_get_contents($composerFile);
                $content = json_decode($content, true);
                if (
                    isset($content['autoload']) &&
                    isset($content['autoload']['psr-4'])
                ) {
                    $keys = array_keys($content['autoload']['psr-4']);

                    if (isset($keys['0'])) {
                        $this->gatewayClass = $keys['0'] . 'Domain\\Gateway';
                    }
                }
            }
        }

        $this->gatewayExtension = $extensionKey;
    }

    public function getGatewayExtension ()
    {
        return $this->gatewayExtension;
    }

    public function getGatewayClass ()
    {
        return $this->gatewayClass;
    }

    public function getGatewayObj ()
    {
        $result = false;
        if (
            $this->getGatewayClass() != ''/* &&
            class_exists($this->getGatewayClass())*/
        ) {
            $result = GeneralUtility::makeInstance($this->getGatewayClass());
        }

        if (!is_object($result)) {
            throw new \RuntimeException('ERROR in the Payment Transactor API (transactor) used by the extension "' . $this->getGatewayExtension() . '": no object can be created for the class "' . $this->getGatewayClass() . '"', 2020290000);
        }
        return $result;
    }

    public function getTablename ()
    {
        $result = $this->getGatewayObj()->getTablename();
        return $result;
    }

    public function getExtensionKey ()
    {
        $result = $this->getGatewayObj()->getExtensionKey();
        return $result;
    }

    /**
    * Returns the gateway key. Each gateway implementation should have such
    * a unique key.
    *
    * @return	array		Gateway key
    * @access	public
    */
    public function getGatewayKey ()
    {
        $result = $this->getGatewayObj()->getGatewayKey();
        return $result;
    }

    public function getGatewayMode ()
    {
        $result = $this->getGatewayObj()->getGatewayMode();
        return $result;
    }

    public function setGatewayMode ($gatewayMode)
    {
        $this->getGatewayObj()->setGatewayMode($gatewayMode);
    }

    public function getTemplateFilename ()
    {
        return $this->getGatewayObj()->getTemplateFilename();
    }

    public function setTemplateFilename ($templateFilename)
    {
        $this->getGatewayObj()->setTemplateFilename($templateFilename);
    }

    public function getExtensionManagerConf ()
    {
        $result = $this->extensionManagerConf;
        return $result;
    }

    public function getConf ()
    {
        $result = $this->getGatewayObj()->getConf();
        return $result;
    }

    public function setConf ($conf)
    {
        $this->getGatewayObj()->setConf($conf);
    }

    public function getConfig ()
    {
        $result = $this->getGatewayObj()->getConfig();
        return $result;
    }

    public function setConfig ($config)
    {
        $this->getGatewayObj()->setConfig($config);
    }

    public function getBasket () 
    {
        $result = $this->getGatewayObj()->getBasket();
        return $result;
    }

    public function setBasket ($basket)
    {
        $this->getGatewayObj()->setBasket($basket);
    }

    public function getBasketSum () 
    {
        $result = $this->getGatewayObj()->getBasketSum();
        return $result;
    }

    public function setBasketSum ($basketSum)
    {
        $this->getGatewayObj()->setBasketSum($basketSum);
    }

    public function getOrderUid () 
    {
        return $this->getGatewayObj()->getOrderUid();
    }

    public function setOrderUid ($orderUid)
    {
        $this->getGatewayObj()->setOrderUid($orderUid);
    }

    public function getOrderNumber () 
    {
        return $this->getGatewayObj()->getOrderNumber();
    }

    public function setOrderNumber ($orderNumber)
    {
        $this->getGatewayObj()->setOrderNumber($orderNumber);
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
    * Initializes a transaction.
    *
    * @param	integer		$action: Type of the transaction, one of the constants TX_TRANSACTOR_TRANSACTION_ACTION_*
    * @param	string		$paymentMethod: Payment method, one of the values of getSupportedMethods()
    * @param	string		$extensionKey: Extension key of the calling script.
    * @param	integer		$orderUid: order unique id
    * @param	string		$orderNumber: order identifier name which also contains the number
    * @param	array		$config: configuration for the extension
    * @param	array		$basket: items in the basket
    * @param	array		$extraData: 'return_url', 'cancel_url'
    * @return	void
    * @access	public
    */
    public function transactionInit (
        $action,
        $method,
        $callingExtensionKey,
        $templateFilename = '',
        $orderUid = 0,
        $orderNumber = '0',
        $currency = 'EUR',
        $config = [],
        $basket = [],
        $extraData = []
    )
    {
        $this->getGatewayObj()->setTransactionUid(0);
        $result = $this->getGatewayObj()->transactionInit(
            $action,
            $method,
            $callingExtensionKey,
            $templateFilename,
            $orderUid,
            $orderNumber,
            $currency,
            $config,
            $basket,
            $extraData
        );
        return $result;
    }

    /**
    * Sets the payment details. Which fields can be set usually depends on the
    * chosen / supported gateway mode. GatewayMode::FORM does not
    * allow setting credit card data for example.
    *
    * @param	array		$detailsArr: The payment details array
    * @return	boolean		Returns true if all required details have been set
    * @access	public
    */
    public function transactionSetDetails ($detailsArr)
    {
        $result = $this->getGatewayObj()->transactionSetDetails($detailsArr);
        return $result;
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
        $result = $this->getGatewayObj()->transactionValidate($level);
        return $result;
    }

    /**
    * Returns if the transaction has been successfull
    *
    * @param	array		results from transaction_getResults
    * @return	boolean		true if the transaction went fine
    * @access	public
    */
    public function transactionSucceeded ($resultsArr)
    {
        $result = $this->getGatewayObj()->transactionSucceeded($resultsArr);
        return $result;
    }

    /**
    * Returns if the transaction has been unsuccessfull
    *
    * @param	array		results from transaction_getResults
    * @return	boolean		true if the transaction went wrong
    * @access	public
    */
    public function transactionFailed ($resultsArr)
    {
        $result = $this->getGatewayObj()->transactionFailed($resultsArr);
        return $result;
    }

    /**
    * Returns if the message of the transaction
    *
    * @param	array		results from transaction_getResults
    * @return	boolean		true if the transaction went wrong
    * @access	public
    */
    public function transactionMessage ($resultsArr)
    {
        $result = $this->getGatewayObj()->transactionMessage($resultsArr);
        return $result;
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
        $gatewayObj = $this->getGatewayObj();
        $processResult = $gatewayObj->transactionProcess($errorMessage);
        $reference = $this->getReferenceUid();
        $resultsArr = $gatewayObj->transactionGetResults($reference);

        if (is_array($resultsArr)) {
            $fields = $resultsArr;

            if (
                !$fields['uid'] &&
                $fields['reference']
            ) {
                $fields['crdate'] = time();
                $fields['pid'] = intval($this->extensionManagerConf['pid']);
                $fields['message'] = (is_array($fields['message'])) ? serialize($fields['message']) : $fields['message'];

                $dbResult = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                    $this->getGatewayObj()->getTablename(),
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
    * Only to be used in mode GatewayMode::AJAX
    *
    * @return	string		HTML form and javascript
    * @access	public
    */
    public function transactionGetForm ()
    {
        $result = $this->getGatewayObj()->transactionGetForm();
        return $result;
    }

    /**
    * Fetches the details of the last occurred error in a string format.
    *
    * @return   string      details of the last error
    * @access   public
    */
    public function transactionGetErrorDetails ()
    {
        $result = $this->getGatewayObj()->transactionGetErrorDetails();
        return $result;
    }

    /**
    * Returns the form action URI to be used in mode GatewayMode::FORM.
    *
    * @return	string		Form action URI
    * @access	public
    */
    public function transactionFormGetActionURI ()
    {
        $result = $this->getGatewayObj()->transactionFormGetActionURI();
        return $result;
    }

    /**
    * Returns any extra parameter for the form tag to be used in mode GatewayMode::FORM.
    *
    * @return  string      Form tag extra parameters
    * @access  public
    */
    public function transactionFormGetFormParms ()
    {
        $result = '';
        if ($this->getGatewayObj()->getGatewayMode() == GatewayMode::FORM) {
            $result = $this->getGatewayObj()->transactionFormGetFormParms();
        }
        return $result;
    }

    /**
    * Returns any extra HTML attributes for the form tag to be used in mode GatewayMode::FORM.
    *
    * @return  string      Form submit button extra parameters
    * @access  public
    */
    public function transactionFormGetAttributes ()
    {
        $result = '';
        if ($this->getGatewayObj()->getGatewayMode() == GatewayMode::FORM) {
            $result = $this->getGatewayObj()->transactionFormGetAttributes();
        }
        return $result;
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
        $result = $this->getGatewayObj()->transactionFormGetHiddenFields();
        return $result;
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
        $result = $this->getGatewayObj()->transactionFormGetScriptParameters();
        return $result;
    }

    /**
    * Sets the URI which the user should be redirected to after a successful payment/transaction
    *
    * @return void
    * @access public
    */
    public function transactionSetOkPage ($uri)
    {
        $this->getGatewayObj()->transactionSetOkPage($uri);
    }

    /**
    * Sets the URI which the user should be redirected to after a failed payment/transaction
    *
    * @return void
    * @access public
    */
    public function transactionSetErrorPage ($uri)
    {
        $this->getGatewayObj()->transactionSetErrorPage($uri);
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
        $result = $this->getGatewayObj()->transactionIsInitState($row);
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
    public function transactionGetResults ($reference, $create = true)
    {
        $dbResult = false;
        $resultsArr = $this->getGatewayObj()->transactionGetResults($reference);

        if (is_array ($resultsArr)) {
            $dbTransactionUid = $this->getGatewayObj()->getTransactionUid();

            if ($dbTransactionUid) {
                $dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                    'gatewayid',
                    $this->getGatewayObj()->getTablename(),
                    'uid=' . intval($dbTransactionUid)
                );
            }

            if (
                !$dbResult &&
                $create
            ) {
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
                        $this->getGatewayObj()->getTablename(),
                        $fields
                    );
                    $resultsArr = $fields;
                }
            }
        }
        return $resultsArr;
    }

    public function transactionGetResultsError ($message)
    {
        $result = $this->getGatewayObj()->transactionGetResultsError($message);
        return $result;
    }

    public function transactionGetResultsSuccess ($message)
    {
        $result = $this->getGatewayObj()->transactionGetResultsSuccess($message);
        return $result;
    }

    /**
    * Returns the parameters of the recently processed transaction
    *
    * @return	array		parameters of the last processed transaction
    * @access	public
    */
    public function transactionGetParameters ()
    {
        $result = $this->getGatewayObj()->transactionGetParameters();
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
    public function __call ($method, $params)
    {
        $result = false;
        if (method_exists($this, $method)) {
            $result = call_user_func_array([$this->getGatewayObj(), $method], $params);
        } else {
            debug ('ERROR: unkown method "' . $method . '" in call of transactor GatewayProxy object'); // keep this
            throw new \RuntimeException('ERROR in transactor: unkown method "' . $method . '" in call of transactor GatewayProxy object ' . $this->getGatewayClass() . '"', 2020290001);
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
    public function __get ($property)
    {
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
    public function __set ($property, $value)
    {
        $this->getGatewayObj()->$property = $value;
    }

    public function clearErrors ()
    {
        $this->getGatewayObj()->clearErrors();
    }

    public function addError ($error)
    {
        $this->getGatewayObj()->addError($error);
    }

    public function hasErrors ()
    {
        $result = $this->getGatewayObj()->hasErrors();
        return $result;
    }

    public function getErrors ()
    {
        $result = $this->getGatewayObj()->getErrors();
        return $result;
    }

    public function useBasket ()
    {
        $result = $this->getGatewayObj()->useBasket();
        return $result;
    }

    public function getTransaction ($reference)
    {
        $result = $this->getGatewayObj()->getTransaction($reference);
        return $result;
    }

    public function setTaxIncluded ($bTaxIncluded)
    {
        $this->getGatewayObj()->setTaxIncluded($bTaxIncluded);
    }

    public function getTaxIncluded()
    {
        return $this->getGatewayObj()->getTaxIncluded();
    }

    public function generateReferenceUid ($orderuid, $callingExtensionKey)
    {
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
    public function setReferenceUid ($reference)
    {
        $this->getGatewayObj()->setReferenceUid($reference);
    }

    /**
    * Fetches the reference of the transaction table, which is the reference
    *
    * @return	void		unique reference
    * @access	public
    */
    public function getReferenceUid ()
    {
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
    public function setTransactionUid ($transUid)
    {
        $this->getGatewayObj()->setTransactionUid($transUid);
    }

    /**
    * Fetches the uid of the transaction table, which is the reference
    *
    * @return	void		unique transaction id
    * @access	public
    */
    public function getTransactionUid ()
    {
        $this->getGatewayObj()->getTransactionUid();
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
        $this->getGatewayObj()->setFormActionURI($formActionURI);
    }

    /**
    * Fetches the form action URI
    *
    * @return	string		form action URI
    * @access	public
    */
    public function getFormActionURI ()
    {
        $result = $this->getGatewayObj()->getFormActionURI();
        return $result;
    }

    /**
    * This gives the information if the order can only processed after a verification message has been received.
    *
    * @return	boolean		true if a verification message needs to be sent
    * @access	public
    */
    public function needsVerificationMessage ()
    {
        $result = $this->getGatewayObj()->needsVerificationMessage();
        return $result;
    }

    /**
    * Returns the parameters which can lead into an action started by the calling application
    * Such parameters are added by the gateway to the redirection link. 
    *
    * @access	public
    */
    public function readActionParameters (ContentObjectRenderer $cObj) {
        $result = $this->getGatewayObj()->readActionParameters($cObj);
        return $result;
    }

    /**
    * This fetches the class of the controller if a given feature is supported by the gateway.
    *
    *
    * @param	integer		feature of constant \JambageCom\Transactor\Constants\Feature
    * @return	boolean		true if a feature is supported
    * @access	public
    */
    public function getFeatureClass ($feature)
    {
        $result = $this->getGatewayObj()->getFeatureClass($feature);
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
    )
    {
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

