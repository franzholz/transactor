<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the transactor (Transactor Payment) extension.
 *
 * Transactor API functions
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage transactor
 *
 */



require_once(t3lib_extMgm::extPath('transactor') . 'model/class.tx_transactor_language.php');
require_once(t3lib_extMgm::extPath('div2007') . 'class.tx_div2007.php');
require_once(t3lib_extMgm::extPath('div2007') . 'class.tx_div2007_alpha5.php');


class tx_transactor_api {
	protected static $cObj;

	/**
	$scriptRelPath   Path to the plugin class script relative to extension directory, eg. 'pi1/class.tx_newfaq_pi1.php'
	$extKey  		 Extension key.
	 */
	static public function init (
		$pLangObj,
		$cObj,
		$conf
	) {
		$langObj = &t3lib_div::getUserObj('&tx_transactor_language');
		$langObj->init(
			$pLangObj,
			$cObj,
			$conf,
			'lib/class.tx_transactor_api.php'
		);
		$langObj->scriptRelPath = '';
		tx_div2007_alpha5::loadLL_fh002(
			$langObj,
			'locallang.xml'
		);

		if (!is_object($cObj))	{
			t3lib_div::requireOnce(PATH_tslib.'class.tslib_content.php');
			$cObj = &t3lib_div::makeInstance('tslib_cObj');
		}
		self::$cObj = $cObj;
	}


	static public function getMarkers (
		$cObj,
		$conf,
		&$markerArray
	) {
		$langObj = t3lib_div::getUserObj('tx_transactor_language');
		$langObj->init(
			'',
			$cObj,
			$conf['marks.'],
			'lib/class.tx_transactor_api.php'
		);

		tx_div2007_alpha5::loadLL_fh002(
			$langObj,
			'locallang.xml'
		);

		$locallang = $langObj->getLocallang();
		$LLkey = $langObj->getLLkey();
		$typoVersion =
			class_exists('t3lib_utility_VersionNumber') ?
				t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) :
				t3lib_div::int_from_ver(TYPO3_version);

		if (isset($locallang[$LLkey])) {
			$langArray = array_merge($locallang['default'], $locallang[$LLkey]);
		} else {
			$langArray = $locallang['default'];
		}

		if (is_array($conf['marks.'])) {
				// Substitute Marker Array from TypoScript Setup
			foreach ($conf['marks.'] as $key => $value) {

				if (!is_array($value)) {
					$langArray[$key] = $value;
				}
			}
		}


		$newMarkerArray = array();

		if(isset($langArray) && is_array($langArray)) {
			foreach ($langArray as $key => $value) {
				if ($typoVersion >= 4006000 && is_array($value)) {
					$value = $value[0]['target'];
				}
				$newMarkerArray['###' . strtoupper($key) . '###'] = $cObj->substituteMarkerArray($value, $markerArray);
			}
		} else {
			$langArray = array();
		}
		$markerArray = array_merge($markerArray, $newMarkerArray);
	}


	/**
	 * returns the gateway mode from the settings
	 */
	static public function getGatewayMode (
		$handleLib,
		$confScript
	) {
		$gatewayModeArray =
			array(
				'form' => TX_TRANSACTOR_GATEWAYMODE_FORM,
				'webservice' => TX_TRANSACTOR_GATEWAYMODE_WEBSERVICE
			);
		$gatewayMode = $gatewayModeArray[$confScript['gatewaymode']];
		if (!$gatewayMode) {
			$gatewayMode = $gatewayModeArray['form'];
		}
		return $gatewayMode;
	}


	/**
	 * returns the gateway proxy object
	 */
	static public function getGatewayProxyObject (
		$handleLib,
		$confScript
	) {
		t3lib_div::requireOnce(t3lib_extMgm::extPath($handleLib) . 'model/class.tx_' . $handleLib . '_gatewayfactory.php');

		if (is_array($confScript)) {
			$gatewayExtName = $confScript['extName'];
			$gatewayFactoryObj = ($handleLib == 'transactor' ? tx_transactor_gatewayfactory::getInstance() : tx_transactor2_gatewayfactory::getInstance());
			$gatewayFactoryObj->registerGatewayExt($gatewayExtName);

			$paymentMethod = $confScript['paymentMethod'];
			$gatewayProxyObject = &$gatewayFactoryObj->getGatewayProxyObjectByPaymentMethod($paymentMethod);
		}

		return $gatewayProxyObject;
	}


	static public function getItemMarkerSubpartArrays (
		$confScript,
		&$subpartArray,
		&$wrappedSubpartArray
	)	{
		$bUseTransactor = FALSE;
		if (is_array($confScript)) {
			$extKey = $confScript['extName'];
			if (t3lib_extMgm::isLoaded($extKey)) {
				$bUseTransactor = TRUE;
			}
		}

		if ($bUseTransactor) {
			$wrappedSubpartArray['###MESSAGE_PAYMENT_TRANSACTOR_YES###'] = '';
			$subpartArray['###MESSAGE_PAYMENT_TRANSACTOR_NO###'] = '';
		} else {
			$wrappedSubpartArray['###MESSAGE_PAYMENT_TRANSACTOR_NO###'] = '';
			$subpartArray['###MESSAGE_PAYMENT_TRANSACTOR_YES###'] = '';
		}
	}


	static public function getReferenceUid (
		$handleLib,
		$confScript,
		$extKey,
		$orderUid
	) {
		$referenceId = FALSE;
		$gatewayProxyObject = self::getGatewayProxyObject($handleLib, $confScript);
		if (method_exists($gatewayProxyObject, 'generateReferenceUid')) {
			$referenceId = $gatewayProxyObject->generateReferenceUid($orderUid, $extKey);
		}
		return $referenceId;
	}


	/**
	 * Include handle extension library
	 */
	static public function includeHandleLib (
		$handleLib,
		$confScript,
		$extKey,
		$itemArray,
		$calculatedArray,
		$deliveryNote,
		$paymentActivity,
		$currentPaymentActivity,
		$infoArray,
		$pidArray,
		$linkParams,
		$trackingCode,
		$orderUid,
		$cardRow,
		&$bFinalize,
		&$markerArray,
		&$templateFilename,
		&$localTemplateCode,
		&$errorMessage
	)	{
		global $TSFE;

		$langObj = &t3lib_div::getUserObj('&tx_transactor_language');
		$bFinalize = FALSE;
		$content = '';

		if (!is_array($itemArray) || !is_array($calculatedArray)) {
			$bValidParams = FALSE;
		} else {
			$bValidParams = TRUE;
		}

		if ($bValidParams) {
			$lConf = $confScript;
			if (is_array($confScript)) {
				$gatewayExtName = $confScript['extName'];
			}

			if ($gatewayExtName != '' && t3lib_extMgm::isLoaded($gatewayExtName)) {
				// everything is ok
			} else {
				if ($gatewayExtName == '') {
					$errorMessage =
						tx_div2007_alpha5::getLL_fh002(
							$langObj,
							'extension_payment_missing'
						);
				} else {
					$message =
						tx_div2007_alpha5::getLL_fh002(
							$langObj,
							'extension_missing'
						);
					$messageArray =  explode('|', $message);
					$errorMessage = $messageArray[0] . $gatewayExtName . $messageArray[1];
				}
			}

			$paymentMethod = $confScript['paymentMethod'];
			$gatewayProxyObject = self::getGatewayProxyObject($handleLib, $confScript);

			if ($errorMessage == '') {
				if (is_object($gatewayProxyObject)) {
					$gatewayKey = $gatewayProxyObject->getGatewayKey();
					$gatewayMode = self::getGatewayMode($handleLib, $confScript);
					$ok = $gatewayProxyObject->transaction_init(
						TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER,
						$paymentMethod,
						$gatewayMode,
						$extKey,
						$confScript['conf.']
					);

					if (!$ok) {
						$errorMessage =
							tx_div2007_alpha5::getLL_fh002(
								$langObj,
								'error_transaction_init'
							);
						return '';
					}

					$gatewayConf = $gatewayProxyObject->getConf();
					self::getPaymentBasket(
						$itemArray,
						$calculatedArray,
						$infoArray,
						$deliveryNote,
						$gatewayConf,
						$totalArray,
						$addressArray,
						$paymentBasketArray
					);
					$referenceId = self::getReferenceUid($handleLib, $confScript, $extKey, $orderUid); // in the case of a callback, a former order than the current would have been read in

					if (!$referenceId) {
						$errorMessage =
							tx_div2007_alpha5::getLL_fh002(
								$langObj,
								'error_reference_id'
							);

						return '';
					}

					$transactionDetailsArray = self::getTransactionDetails(
						$referenceId,
						$handleLib,
						$confScript,
						$extKey,
						$calculatedArray,
						$paymentActivity,
						$pidArray,
						$linkParams,
						$trackingCode,
						$orderUid,
						$cardRow,
						$totalArray,
						$addressArray,
						$paymentBasketArray
					);

						// Set payment details and get the form data:
					$ok = $gatewayProxyObject->transaction_setDetails($transactionDetailsArray);

					if (!$ok) {
						$errorMessage =
							tx_div2007_alpha5::getLL_fh002(
								$langObj,
								'error_transaction_details'
							);
						return '';
					}

						// Get results of a possible earlier submit and display messages:
					$transactionResults = $gatewayProxyObject->transaction_getResults($referenceId);

					if (!is_array($transactionResults)) {
						$row = $gatewayProxyObject->getTransaction($referenceId);

						if (is_array($row)) {
							if ($gatewayProxyObject->transaction_isInitState($row)) {
								$transactionResults = $row;
							}
						} else if (!is_array($row)) {
							$transactionResults = $gatewayProxyObject->transaction_getResultsSuccess('first trial');
						}
					}

					if (is_array($transactionResults)) {
						if ($gatewayProxyObject->transaction_succeded($transactionResults)) {
							$bFinalize = TRUE;
						} else if ($gatewayProxyObject->transaction_failed($transactionResults)) {
							$errorMessage = $gatewayProxyObject->transaction_message($transactionResults);
						} else {
							$gatewayProxyObject->transaction_setOkPage($transactionDetailsArray['transaction']['successlink']);
							$gatewayProxyObject->transaction_setErrorPage($transactionDetailsArray['transaction']['faillink']);

							$compGatewayForm = ($handleLib == 'transactor' ? TX_TRANSACTOR_GATEWAYMODE_FORM : TX_TRANSACTOR2_GATEWAYMODE_FORM);
							$compGatewayWebservice = ($handleLib == 'transactor' ? TX_TRANSACTOR_GATEWAYMODE_WEBSERVICE : TX_TRANSACTOR2_GATEWAYMODE_WEBSERVICE);

							if (
								$gatewayMode == $compGatewayWebservice ||
								$currentPaymentActivity == 'verify' ||
								$currentPaymentActivity == 'finalize'
							) {
								$rc = $gatewayProxyObject->transaction_process();
								$resultsArray = $gatewayProxyObject->transaction_getResults($referenceId); //array holen mit allen daten

								if ($paymentActivity == 'verify' && $gatewayProxyObject->transaction_succeded($resultsArray) == FALSE) {
									$errorMessage = htmlspecialchars($gatewayProxyObject->transaction_message($resultsArray)); // message auslesen
								} else {
									$bFinalize = TRUE;
								}
							} else if ($gatewayMode == $compGatewayForm && $currentPaymentActivity != 'verify') {

								if (!$templateFilename) {
									$templateFilename = ($lConf['templateFile'] ? $lConf['templateFile'] : 'EXT:transactor/template/transactor.tmpl');
								}
								$localTemplateCode = self::$cObj->fileResource($templateFilename);

								if (!$localTemplateCode && $templateFilename != '') {
									$errorMessage =
										tx_div2007_alpha5::getLL_fh002(
											$langObj,
											'error_no_template'
										);
									$errorMessage = sprintf($errorMessage, $templateFilename);
									return '';
								}

									// Render hidden fields:
								$hiddenFields = '';
								$hiddenFieldsArray = $gatewayProxyObject->transaction_formGetHiddenFields();

								if (is_array($hiddenFieldsArray)) {
									foreach ($hiddenFieldsArray as $key => $value) {
										$hiddenFields .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '" />' . chr(10);
									}
								}
								$formuri = $gatewayProxyObject->transaction_formGetActionURI();
								$formAttributes = $gatewayProxyObject->transaction_formGetAttributes();

								if ($formAttributes) {
									$formuri .= '?' . $formAttributes;
								}

								if (strstr ($formuri, 'ERROR') != FALSE) {
									$bError = TRUE;
								}

								if ($formuri && !$bError) {

									$markerArray['###HIDDENFIELDS###'] .= $hiddenFields;
									$markerArray['###REDIRECT_URL###'] = htmlspecialchars($formuri);
									$markerArray['###TRANSACTOR_TITLE###'] = $lConf['extTitle'];
									$markerArray['###TRANSACTOR_INFO###'] = $lConf['extInfo'];
									$returnUrlArray = parse_url($transactionDetailsArray['transaction']['returi']);
									$markerArray['###HOST###'] = $returnUrlArray['host'];

									if ($lConf['extImage'] == 'IMAGE' && isset($lConf['extImage.']) && is_array($lConf['extImage.'])) {
										$imageOut = self::$cObj->IMAGE($lConf['extImage.']);
									} else {
										$imageOut = tx_div2007::resolvePathWithExtPrefix($lConf['extImage']);
									}
									$markerArray['###TRANSACTOR_IMAGE###'] = $imageOut;
									$markerArray['###TRANSACTOR_WWW###'] = $lConf['extWww'];

									self::getMarkers(
										$langObj->getCObj(),
										$langObj->getConf(),
										$markerArray
									);
								} else {
									if ($bError) {
										$errorMessage = $formuri;
									} else {
										$errorMessage =
											tx_div2007_alpha5::getLL_fh002(
												$langObj,
												'error_relay_url'
											);
									}
								}
							}
						}
					} else {
						$bFinalize = $transactionResults;
					}
				} else {
					$message =
						tx_div2007_alpha5::getLL_fh002(
							$langObj,
							'error_gateway_missing'
						);
					$messageArray =  explode('|', $message);
					$errorMessage = $messageArray[0] . $paymentMethod . $messageArray[1];
				}
			}
		} else {
			$message =
				tx_div2007_alpha5::getLL_fh002(
					$langObj,
					'error_api_parameters'
				);
			$messageArray =  explode('|', $message);
			$errorMessage = $messageArray[0] . $paymentMethod . $messageArray[1];
		}

		if ($errorMessage == TX_TRANSACTOR_TRANSACTION_MESSAGE_NOT_PROCESSED) {
			$errorMessage =
				tx_div2007_alpha5::getLL_fh002(
					$langObj,
					'error_transaction_no'
				);
		}

		return $content;
	} // includeHandleLib


	/**
	 * Checks if required fields for credit cards and bank accounts are filled in correctly
	 */
	static public function checkRequired (
		$referenceId,
		$handleLib,
		$confScript,
		$extKey,
		$calculatedArray,
		$paymentActivity,
		$pidArray,
		$linkParams,
		$trackingCode,
		$orderUid,
		$cardRow
	) {
		$rc = '';

		if (strpos($handleLib,'transactor') !== FALSE) {

			$gatewayProxyObject = self::getGatewayProxyObject($handleLib, $confScript);

			if (is_object($gatewayProxyObject)) {
				$gatewayKey = $gatewayProxyObject->getGatewayKey();
				$paymentBasketArray = array();
				$addressArray = array();
				$totalArray = array();
				$transactionDetailsArray =
					self::getTransactionDetails(
						$referenceId,
						$handleLib,
						$confScript,
						$extKey,
						$calculatedArray,
						$paymentActivity,
						$pidArray,
						$linkParams,
						$trackingCode,
						$orderUid,
						$cardRow,
						$totalArray,
						$addressArray,
						$paymentBasketArray
					);
				$set = $gatewayProxyObject->transaction_setDetails($transactionDetailsArray);
				$ok = $gatewayProxyObject->transaction_validate();

				if (!$ok) {
					$langObj = &t3lib_div::getUserObj('&tx_transactor_language');
					$errorMessage =
						tx_div2007_alpha5::getLL_fh002(
							$langObj,
							'error_invalid_data'
						);
					return $errorMessage;
				}

				if ($gatewayProxyObject->transaction_succeded() == FALSE) {
					$rc = htmlspecialchars($gatewayProxyObject->transaction_message(array()));
				}
			}
		}
		return $rc;
	} // checkRequired


	static public function getUrl (
		$conf,
		$pid,
		$linkParamArray
	) {
		global $TSFE;

		if (!$pid) {
			$pid = $TSFE->id;
		}
		$target = '';
		$linkParams = '';
		$linkArray = array();
		if (isset($linkParamArray) && is_array($linkParamArray)) {
			foreach ($linkParamArray as $k => $v) {
				$linkArray[] = $k . '=' . $v;
			}
		}
		$linkParams = implode('&', $linkArray);
		$url =
			tx_div2007_alpha::getTypoLink_URL_fh002(
				self::$cObj,
				$pid,
				$linkParamArray,
				'',
				$conf
			);
		return $url;
	}


	static public function getLanguage () {
		global $TSFE;

		if (isset($TSFE->config) && is_array($TSFE->config) && isset($TSFE->config['config']) && is_array($TSFE->config['config'])) {
			$rc = strtolower($TSFE->config['config']['language']);
		} else {
			$rc = 'default';
		}
		return $rc;
	}


	/**
	 * Gets all the data needed for the transaction or the verification check
	 */
	static public function &getTransactionDetails (
		$referenceId,
		$handleLib,
		$confScript,
		$extKey,
		$calculatedArray,
		$paymentActivity,
		$pidArray,
		$linkParams,
		$trackingCode,
		$orderUid,
		$cardRow,
		&$totalArray,
		&$addressArray,
		&$paymentBasketArray
	) {
		global $TSFE;

		$paramNameActivity = $extKey . '[activity][' . $paymentActivity . ']';

		$failLinkParams = array($paramNameActivity => '0');

		if (isset($linkParams) && is_array($linkParams)) {
			$failLinkParams = array_merge($failLinkParams, $linkParams);
		}

		$successLinkParams = array($paramNameActivity => '1');

		if (isset($linkParams) && is_array($linkParams)) {
			$successLinkParams = array_merge($successLinkParams, $linkParams);
		}

		$paramReturi = '';

			// Prepare some values for the form fields:
		$totalPrice = $calculatedArray['priceNoTax']['total'];

		if ($paymentActivity == 'finalize' && $confScript['returnPID']) {
			$successPid = $confScript['returnPID'];
		} else {
			$successPid = ($paymentActivity == 'payment' || $paymentActivity == 'verify' ? ($pidArray['PIDthanks'] ? $pidArray['PIDthanks'] : $pidArray['PIDfinalize']) : $TSFE->id);
		}
		$conf = array('returnLast' => 'url');
		$urlDir = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR');
		$retlink = $urlDir . self::getUrl($conf, $TSFE->id, $linkParams);
		$returi = $retlink . $paramReturi;

		$faillink = $urlDir . self::getUrl($conf, $pidArray['PIDpayment'], $failLinkParams);
		$successlink = $urlDir . self::getUrl($conf, $successPid, $successLinkParams);

		$transactionDetailsArray = array (
			'transaction' => array (
				'amount' => $totalPrice,
				'currency' => $confScript['currency'] ? $confScript['currency'] : $confScript['Currency'],
				'orderuid' => $orderUid,
				'returi' => $returi,
				'faillink' => $faillink,
				'successlink' => $successlink
			),
			'total' => $totalArray,
			'tracking' => $trackingCode,
			'address' => $addressArray,
			'basket' => $paymentBasketArray,
			'cc' => $cardRow,
			'language' => self::getLanguage()
		);

		if ($paymentActivity == 'verify') {
			$verifyLink = $urlDir . self::getUrl($conf, $TSFE->id, $successLinkParams);
			$transactionDetailsArray['transaction']['verifylink'] = $verifyLink;
		}

		if (isset($confScript['conf.']) && is_array($confScript['conf.'])) {
			$transactionDetailsArray['options'] = $confScript['conf.'];
		}
		$transactionDetailsArray['reference'] = $referenceId;

		return $transactionDetailsArray;
	}


	static public function &getPaymentBasket (
		$itemArray,
		$calculatedArray,
		$infoArray,
		$deliveryNote,
		$gatewayConf,
		&$totalArray,
		&$addressArray,
		&$basketArray
	) {
		global $TYPO3_DB;

		$bUseStaticInfo = FALSE;
		$langObj = &t3lib_div::getUserObj('&tx_transactor_language');

		if (t3lib_extMgm::isLoaded('static_info_tables')) {
			$eInfo = tx_div2007_alpha::getExtensionInfo_fh002('static_info_tables');
			$sitVersion = $eInfo['version'];
			if (version_compare($sitVersion, '2.0.5', '>=')) {
				$bUseStaticInfo = TRUE;
			}
		}

		if ($bUseStaticInfo) {
			$path = t3lib_extMgm::extPath('static_info_tables');
			include_once($path.'class.tx_staticinfotables_div.php');
		}

		// Setting up total values
		$totalArray = array();

		$totalArray['goodsnotax'] = self::fFloat($calculatedArray['priceNoTax']['goodstotal']);
		$totalArray['goodstax'] = self::fFloat($calculatedArray['priceTax']['goodstotal']);

		// new calculatedArray format?
		if (isset($calculatedArray['shipping']) && is_array($calculatedArray['shipping'])) {

			$totalArray['paymentnotax'] = self::fFloat($calculatedArray['payment']['priceNoTax']);
			$totalArray['paymenttax'] = self::fFloat($calculatedArray['payment']['priceTax']);
			$totalArray['shippingnotax'] = self::fFloat($calculatedArray['shipping']['priceNoTax']);
			$totalArray['shippingtax'] = self::fFloat($calculatedArray['shipping']['priceTax']);
			$totalArray['handlingnotax'] = self::fFloat($calculatedArray['handling']['0']['priceNoTax']);
			$totalArray['handlingtax'] = self::fFloat($calculatedArray['handling']['0']['priceTax']['handling']);
		} else {
			$totalArray['paymentnotax'] = self::fFloat($calculatedArray['priceNoTax']['payment']);
			$totalArray['paymenttax'] = self::fFloat($calculatedArray['priceTax']['payment']);
			$totalArray['shippingnotax'] = self::fFloat($calculatedArray['priceNoTax']['shipping']);
			$totalArray['shippingtax'] = self::fFloat($calculatedArray['priceTax']['shipping']);
			$totalArray['handlingnotax'] = self::fFloat($calculatedArray['priceNoTax']['handling']);
			$totalArray['handlingtax'] = self::fFloat($calculatedArray['priceTax']['handling']);
		}

		$totalArray['amountnotax'] = self::fFloat($calculatedArray['priceNoTax']['vouchertotal']);
		$totalArray['amounttax'] = self::fFloat($calculatedArray['priceTax']['vouchertotal']);
		$totalArray['taxrate'] = $calculatedArray['maxtax']['goodstotal'];
		$totalArray['totaltax'] = self::fFloat($totalArray['amounttax'] - $totalArray['amountnotax']);

		// Setting up address info values
		$mapAddrFields = array(
			'first_name' => 'first_name',
			'last_name' => 'last_name',
			'address' => 'address1',
			'zip' => 'zip',
			'city' => 'city',
			'telephone' => 'phone',
			'email' => 'email',
			'country' => 'country'
		);
		$tmpAddrArray = array(
			'person' => $infoArray['billing'],
			'delivery' => $infoArray['delivery']
		);
		$addressArray = array();

		foreach($tmpAddrArray as $key => $basketAddressArray) {
			$addressArray[$key] = array();

			// Correct firstname- and lastname-field if they have no value
			if ($basketAddressArray['first_name'] == '' && $basketAddressArray['last_name'] == '') {
				$tmpNameArr = explode(" ", $basketAddressArray['name'], 2);
				$basketAddressArray['first_name'] = $tmpNameArr[0];
				$basketAddressArray['last_name'] = $tmpNameArr[1];
			}

			// Map address fields
			foreach ($basketAddressArray as $mapKey => $value) {
				$paymentLibKey = $mapAddrFields[$mapKey];
				if ($paymentLibKey != '') {
					$addressArray[$key][$paymentLibKey] = $value;
				}
			}

			if ($bUseStaticInfo) {
				// guess country and language settings for invoice address. One of these vars has to be set: country, countryISO2, $countryISO3 or countryISONr
				// you can also set 2 or more of these codes. The codes will be joined with 'OR' in the select-statement and only the first
				// record which is found will be returned. If there is no record at all, the codes will be returned untouched
				$countryArray = tx_staticinfotables_div::fetchCountries(
					$addressArray[$key]['country'],
					$addressArray[$key]['countryISO2'],
					$addressArray[$key]['countryISO3'],
					$addressArray[$key]['countryISONr']
				);
				$countryRow = $countryArray[0];

				if (count($countryRow)) {
					$addressArray[$key]['country'] = $countryRow['cn_iso_2'];
				}
			}
		}
		$addressArray['delivery']['note'] = $deliveryNote;

		$totalCount = 0;
		foreach ($itemArray as $sort => $actItemArray) {
			foreach ($actItemArray as $k1 => $actItem) {
				$totalCount += intval($actItem['count']);
			}
		}

		// Fill the basket array
		$basketArray = array();
		$newTotalArray = array('handling' => '0', 'shipping' => '0');
		$lastSort = '';
		$lastKey = 0;

		foreach ($itemArray as $sort => $actItemArray) {
			if ($sort == '') {
				$sort = 'basketsort';
			}
			$lastSort = $sort;
			$basketArray[$sort] = array();

			foreach ($actItemArray as $key => $actItem) {
				$row = $actItem['rec'];
				$tax = $row['tax'];

				$extArray = $row['ext'];
				if (isset($extArray) && is_array($extArray)) {
					$mergeRow = $extArray['mergeArticles'];
					if (isset($mergeRow) && is_array($mergeRow)) {
						foreach ($mergeRow as $field => $value) {
							if ($value) {
								$row[$field] = $value;
							}
						}
					}
				}

				$shipping = self::fFloat($count * $totalArray['shippingtax'] / $totalCount, 2);
				$newTotalArray['shipping'] += $shipping;
				$handling = self::fFloat($actItem['handling'], 2);
				$newTotalArray['handling'] += $handling;

				$count = intval($actItem['count']);

				$basketRow = array(
					'item_name' => $row['title'],
					'quantity' => $count,
					'amount' => self::fFloat($actItem['priceNoTax']),
					'shipping' => $shipping,
					'handling' => $handling,
					'taxpercent' => $tax,
					'tax' => self::fFloat($actItem['priceTax'] - $actItem['priceNoTax']),
					'totaltax' => self::fFloat($actItem['totalTax']) - self::fFloat($row['totalNoTax']),
					'item_number' => $row['itemnumber'],
				);

				for ($i = 0; $i <= 7; ++$i) {

					$fieldName = $gatewayConf['on' . $i . 'n'];

					if ($fieldName == 'note' || $fieldName == 'note2') {
						$value = strip_tags(nl2br($row[$fieldName]));
						$value = str_replace ('&nbsp;', ' ', $value);
					} else {
						$value = $row[$fieldName];
					}
					if ($value != '') {
						$basketRow['on' . $i] = $gatewayConf['on' . $i . 'l'];;
						$basketRow['os' . $i] = $value;
					}
				}

				$basketArray[$sort][$key] = $basketRow;
				$lastKey = $key;
			}
		}

		// fix rounding errors
		if ($newTotalArray['shipping'] != $totalArray['shippingtax']) {
			$basketArray[$lastSort][$lastKey]['shipping'] += $totalArray['shippingtax'] - $newTotalArray['shipping'];
		}
		// fix rounding errors
		if ($newTotalArray['handling'] != $totalArray['handling']) {
			$basketArray[$lastSort][$lastKey]['handling'] += $totalArray['handlingtax'] - $newTotalArray['handling'];
		}

		if (
			$calculatedArray['priceTax']['vouchertotal'] > 0 &&
			$calculatedArray['priceTax']['vouchertotal'] != $calculatedArray['priceTax']['total']
		)	{
			$voucherAmount = $calculatedArray['priceTax']['vouchertotal'] - $calculatedArray['priceTax']['total'];
			$voucherText =
				tx_div2007_alpha5::getLL_fh002(
					$langObj,
					'voucher_payment_article'
				);
			$basketArray['VOUCHER'][] =
				array(
					'item_name' => $voucherText,
					'on0' => $voucherText,
					'quantity' => 1,
					'amount' => $voucherAmount,
					'taxpercent' => 0,
					'item_number' => 'VOUCHER'
				);

			$totalArray['goodsnotax'] = self::fFloat($calculatedArray['priceNoTax']['goodstotal'] + $voucherAmount);
			$totalArray['goodstax'] = self::fFloat($calculatedArray['priceTax']['goodstotal'] + $voucherAmount);
		}
	}


	static public function fFloat (
		$value = 0,
		$level = 2
	) {
		if (is_float($value))	{
			$float = $value;
		} else {
			$float = floatval($value);
		}

		return round($float, $level);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/transactor/lib/class.tx_transactor_api.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/transactor/lib/class.tx_transactor_api.php']);
}

?>