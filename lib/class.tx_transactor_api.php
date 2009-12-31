<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2009 Franz Holzinger <franz@ttproducts.de>
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
 * Payment Library extra functions
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage transactor
 *
 */



require_once (t3lib_extMgm::extPath('transactor') . 'model/class.tx_transactor_language.php');


class tx_transactor_api {

	/**
	$scriptRelPath   Path to the plugin class script relative to extension directory, eg. 'pi1/class.tx_newfaq_pi1.php'
	$extKey  		 Extension key.
	 */
	public static function init ($pLangObj, $cObj, $conf)	{
		$langObj = &t3lib_div::getUserObj('&tx_transactor_language');
		$langObj->init($pLangObj, $cObj, $conf);
		tx_div2007_alpha::loadLL_fh001($langObj, 'locallang.xml');
	}


	/**
	 * returns the gateway mode from the settings
	 */
	public static function getGatewayMode ($handleLib, $confScript) 	{

		$gatewayModeArray = array('form' => TX_TRANSACTOR_GATEWAYMODE_FORM, 'webservice' => TX_TRANSACTOR_GATEWAYMODE_WEBSERVICE);
		$gatewayMode = $gatewayModeArray[$confScript['gatewaymode']];
		if (!$gatewayMode)	{
			$gatewayMode = $gatewayModeArray['form'];
		}
		return $gatewayMode;
	}


	public static function getReferenceUid ($gatewayProxyObject, $extKey)	{
		$referenceId = FALSE;

		if (is_object($gatewayProxyObject))	{
			$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

			$orderObj = &$tablesObj->get('sys_products_orders');
			$orderUid = $orderObj->getUid();

			if (!$orderUid)	{
				$orderUid = $orderObj->getBlankUid();
			}
			if (method_exists($gatewayProxyObject, 'generateReferenceUid'))	{
				$referenceId = $gatewayProxyObject->generateReferenceUid($orderUid, TT_PRODUCTS_EXTkey);
			}
		}
		return $referenceId;
	}


	/**
	 * Include handle extension library
	 */
	public static function includeHandleLib (
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

		$lConf = $confScript;
		$langObj = &t3lib_div::getUserObj('&tx_transactor_language');
		$content = '';
		if (is_array($confScript))	{
			$gatewayExtName = $confScript['extName'];
		}

		if ($gatewayExtName != '' && t3lib_extMgm::isLoaded($gatewayExtName))	{
			// everything is ok
		} else {
			if ($gatewayExtName == '')	{
				$message = tx_div2007_alpha::getLL($langObj,'extension_payment_missing');
			} else {
				$message = tx_div2007_alpha::getLL($langObj,'extension_missing');
				$messageArray =  explode('|', $message);
				$errorMessage = $messageArray[0] . $gatewayExtName . $messageArray[1];
			}
		}

		if (t3lib_extMgm::isLoaded($handleLib))	{
			require_once(t3lib_extMgm::extPath($handleLib) . 'model/class.tx_' . $handleLib . '_gatewayfactory.php');
		}
		$gatewayFactoryObj = tx_transactor_gatewayfactory::getInstance();
		$gatewayFactoryObj->registerGatewayExt($gatewayExtName);

		$paymentMethod = $confScript['paymentMethod'];
		$gatewayProxyObject = &$gatewayFactoryObj->getGatewayProxyObjectByPaymentMethod($paymentMethod);

		if (is_object($gatewayProxyObject))	{
			$gatewayKey = $gatewayProxyObject->getGatewayKey();
			$gatewayMode = self::getGatewayMode($handleLib, $confScript);
			$ok = $gatewayProxyObject->transaction_init(
				TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER,
				$paymentMethod,
				$gatewayMode,
				$extKey,
				$confScript['conf.']
			);
			if (!$ok)	{
				$rc = tx_div2007_alpha::getLL($langObj,'error_transaction_init');
				return $rc;
			}
			self::getPaymentBasket(
				$itemArray,
				$calculatedArray,
				$infoArray,
				$deliveryNote,
				$totalArr,
				$addrArr,
				$paymentBasketArray
			);
			$referenceId = self::getReferenceUid($gatewayProxyObject, $extKey); // in the case of a callback, a former order than the current would have been read in
			if (!$referenceId)	{
				$rc = tx_div2007_alpha::getLL($langObj,'error_reference_id');
				return $rc;
			}

				// Get results of a possible earlier submit and display messages:
			$transactionResultsArr = $gatewayProxyObject->transaction_getResults($referenceId);
			if ($gatewayProxyObject->transaction_succeded($transactionResultsArr)) {
				$bFinalize = TRUE;
			} else if ($gatewayProxyObject->transaction_failed($transactionResultsArr))	{
				$content = '<span style="color:red;">'.htmlspecialchars($gatewayProxyObject->transaction_message($transactionResultsArr)).'</span><br />';
				$content .= '<br />';
			} else {
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
					$totalArr,
					$addrArr,
					$paymentBasketArray
				);

					// Set payment details and get the form data:
				$ok = $gatewayProxyObject->transaction_setDetails($transactionDetailsArray);

				if (!$ok) {
					$rc = tx_div2007_alpha::getLL($langObj,'error_transaction_details');
					return $rc;
				}
				$gatewayProxyObject->transaction_setOkPage($transactionDetailsArray['transaction']['successlink']);
				$gatewayProxyObject->transaction_setErrorPage($transactionDetailsArray['transaction']['faillink']);

				$compGatewayForm = ($handleLib == 'transactor' ? TX_TRANSACTOR_GATEWAYMODE_FORM : TX_TRANSACTOR2_GATEWAYMODE_FORM);
				$compGatewayWebservice = ($handleLib == 'transactor' ? TX_TRANSACTOR_GATEWAYMODE_WEBSERVICE : TX_TRANSACTOR2_GATEWAYMODE_WEBSERVICE);

				if ($gatewayMode == $compGatewayForm && $currentPaymentActivity != 'verify')	{

					if (!$templateFilename)	{
						$templateFilename = ($lConf['templateFile'] ? $lConf['templateFile'] : 'EXT:transactor/template/transactor.tmpl');
					}
					$langObj = &t3lib_div::getUserObj('&tx_transactor_language');
					$localTemplateCode = $langObj->cObj->fileResource($templateFilename);

						// Render hidden fields:
					$hiddenFields = '';
					$hiddenFieldsArray = $gatewayProxyObject->transaction_formGetHiddenFields();

					foreach ($hiddenFieldsArray as $key => $value) {
						$hiddenFields .= '<input name="' . $key . '" type="hidden" value="' . htmlspecialchars($value) . '" />' . chr(10);
					}

					$formuri = $gatewayProxyObject->transaction_formGetActionURI();
					if (strstr ($formuri, 'ERROR') != FALSE)	{
						$bError = TRUE;
					}

					if ($formuri && !$bError) {
						$markerArray['###HIDDENFIELDS###'] = $markerArray['###HIDDEN_FIELDS###'] = $hiddenFields;
						$markerArray['###REDIRECT_URL###'] = $formuri;
						$markerArray['###TRANSACTOR_TITLE###'] = $lConf['extTitle'];
						$markerArray['###TRANSACTOR_INFO###'] = $lConf['extInfo'];
						$markerArray['###TRANSACTOR_IMAGE###'] = ($lConf['extImage'] == 'IMAGE' && isset($lConf['extImage.']) && is_array($lConf['extImage.']) ? $langObj->cObj->IMAGE($lConf['extImage.']) : $lConf['extImage']);
						$markerArray['###TRANSACTOR_WWW###'] = $lConf['extWww'];
					} else {
						if ($bError)	{
							$errorMessage = $formuri;
						} else {
							$errorMessage = tx_div2007_alpha::getLL($langObj,'error_relay_url');
						}
					}
				} else if ($gatewayMode == $compGatewayWebservice || $currentPaymentActivity == 'verify')	{
					$rc = $gatewayProxyObject->transaction_process();
					$resultsArray = $gatewayProxyObject->transaction_getResults($referenceId); //array holen mit allen daten

					if ($gatewayProxyObject->transaction_succeded($resultsArray) == FALSE) 	{
						$content = $gatewayProxyObject->transaction_message($resultsArray); // message auslesen
					} else {
						$bFinalize = TRUE;
					}
					$contentArray = array();
				}
			}
		} else {
			$message = tx_div2007_alpha::getLL($langObj,'error_gateway_missing');
			$messageArray =  explode('|', $message);
			$errorMessage = $messageArray[0] . $paymentMethod . $messageArray[1];
		}
		return $content;
	} // includeHandleLib


	/**
	 * Checks if required fields for credit cards and bank accounts are filled in correctly
	 */
	public static function checkRequired (
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
	)	{
		$rc = '';

		if (strpos($handleLib,'transactor') !== FALSE)	{
			$gatewayFactoryObj = ($handleLib == 'transactor' ? tx_transactor_gatewayfactory::getInstance() : tx_transactor2_gatewayfactory::getInstance());
			$paymentMethod = $confScript['paymentMethod'];
			$gatewayProxyObject = $gatewayFactoryObj->getGatewayProxyObjectByPaymentMethod($paymentMethod);
			if (is_object($gatewayProxyObject))	{
				$gatewayKey = $gatewayProxyObject->getGatewayKey();
				$paymentBasketArray = array();
				$addrArr = array();
				$totalArr = array();
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
						$totalArr,
						$addrArr,
						$paymentBasketArray
					);

				echo "<br><br>ausgabe details: ";
				print_r ($transactionDetailsArray);
				echo "<br><br>";
				$set = $gatewayProxyObject->transaction_setDetails($transactionDetailsArray);
				$ok = $gatewayProxyObject->transaction_validate();

				if (!$ok)	{
					return 'ERROR: invalide data.';
				}
				if ($gatewayProxyObject->transaction_succeded() == FALSE) 	{
					$rc = $gatewayProxyObject->transaction_message();
				}
			}
		}
		return $rc;
	} // checkRequired


	public static function getUrl ($conf,$pid,$linkParams)	{
		global $TSFE;

		if (!$pid)	{
			$pid = $TSFE->id;
		}
		$target = '';
		$langObj = &t3lib_div::getUserObj('&tx_transactor_language');
		$url = tx_div2007_alpha::getTypoLink_URL_fh001($langObj,$pid,$linkParams,$target,$conf);
		return $url;
	}


	/**
	 * Gets all the data needed for the transaction or the verification check
	 */
	public static function &getTransactionDetails (
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
		&$totalArr,
		&$addrArr,
		&$paymentBasketArray
	)	{
		global $TSFE;

		$param = '';
		$paramNameActivity = '&' . $extKey . '[activity][' . $paymentActivity . ']';
		$paramFailLink = $paramNameActivity . '=0' . $param;
		$paramSuccessLink = $paramNameActivity . '=1' . $param;
		$paramReturi = $param;

			// Prepare some values for the form fields:
		$totalPrice = $calculatedArray['priceNoTax']['total'];

		if ($paymentActivity == 'finalize' && $confScript['returnPID'])	{
			$successPid = $confScript['returnPID'];
		} else {
			$successPid = ($paymentActivity == 'payment' || $paymentActivity == 'verify' ? ($pidArray['PIDthanks'] ? $pidArray['PIDthanks'] : $pidArray['PIDfinalize']) : $TSFE->id);
		}
		$conf = array('returnLast' => 'url');
		$urlDir = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR');
		$retlink = $urlDir . self::getUrl($conf, $TSFE->id, $linkParams);
		$returi = $retlink . $paramReturi;
		$faillink = $urlDir . self::getUrl($conf, $pidArray['PIDpayment'], $linkParams) . $paramFailLink;
		$successlink = $urlDir . self::getUrl($conf, $successPid, $linkParams) . $paramSuccessLink;
		$transactionDetailsArray = array (
			'transaction' => array (
				'amount' => $totalPrice,
				'currency' => $confScript['currency'] ? $confScript['currency'] : $confScript['Currency'],
				'orderuid' => $orderUid,
				'returi' => $returi,
				'faillink' => $faillink,
				'successlink' => $successlink
			),
			'total' => $totalArr,
			'tracking' => $trackingCode,
			'address' => $addrArr,
			'basket' => $paymentBasketArray,
			'cc' => $cardRow
		);
		if ($paymentActivity == 'verify')	{
			$transactionDetailsArray['transaction']['verifylink'] = $retlink . $paramNameActivity . '=1';
		}

		if (isset($confScript['conf.']) && is_array($confScript['conf.']))	{
			$transactionDetailsArray['options'] = $confScript['conf.'];
		}
		$transactionDetailsArray['options']['reference'] = $referenceId;

		return $transactionDetailsArray;
	}


	public static function &getPaymentBasket (
		$itemArray,
		$calculatedArray,
		$infoArray,
		$deliveryNote,
		&$totalArr,
		&$addrArr,
		&$basketArray
	) {
		global $TYPO3_DB;

		$bUseStaticInfo = FALSE;

		if (t3lib_extMgm::isLoaded('static_info_tables'))	{
			$eInfo = tx_div2007_alpha::getExtensionInfo_fh001('static_info_tables');
			$sitVersion = $eInfo['version'];
			if (version_compare($sitVersion, '2.0.5', '>='))	{
				$bUseStaticInfo = TRUE;
			}
		}

		if ($bUseStaticInfo)	{
			$path = t3lib_extMgm::extPath('static_info_tables');
			include_once($path.'class.tx_staticinfotables_div.php');
		}

		// Setting up total values
		$totalArr = array();
		$totalArr['goodsnotax'] = self::fFloat($calculatedArray['priceNoTax']['goodstotal']);
		$totalArr['goodstax'] = self::fFloat($calculatedArray['priceTax']['goodstotal']);
		$totalArr['paymentnotax'] = self::fFloat($calculatedArray['priceNoTax']['payment']);
		$totalArr['paymenttax'] = self::fFloat($calculatedArray['priceTax']['payment']);
		$totalArr['shippingnotax'] = self::fFloat($calculatedArray['shipping']['priceNoTax']);
		$totalArr['shippingtax'] = self::fFloat($calculatedArray['shipping']['priceTax']);
		$totalArr['handlingnotax'] = self::fFloat($calculatedArray['handling']['0']['priceNoTax']);
		$totalArr['handlingtax'] = self::fFloat($calculatedArray['handling']['0']['priceTax']['handling']);
		$totalArr['amountnotax'] = self::fFloat($calculatedArray['priceNoTax']['total']);
		$totalArr['amounttax'] = self::fFloat($calculatedArray['priceTax']['total']);
		$totalArr['taxrate'] = $calculatedArray['maxtax']['goodstotal'];
		$totalArr['totaltax'] = self::fFloat($totalArr['amounttax'] - $totalArr['amountnotax']);
		$totalArr['totalamountnotax'] = self::fFloat($totalArr['amountnotax'] + $totalArr['shippingnotax'] + $totalArr['handlingnotax']);
		$totalArr['totalamount'] = self::fFloat($totalArr['amounttax'] + $totalArr['shippingtax'] + $totalArr['handlingtax']);

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
		$tmpAddrArr = array(
			'person' => $infoArray['billing'],
			'delivery' => $infoArray['delivery']
		);
		$addrArr = array();

		foreach($tmpAddrArr as $key => $basketAddrArr)	{
			$addrArr[$key] = array();

			// Correct firstname- and lastname-field if they have no value
			if ($basketAddrArr['first_name'] == '' && $basketAddrArr['last_name'] == '')	{
				$tmpNameArr = explode(" ", $basketAddrArr['name'], 2);
				$basketAddrArr['first_name'] = $tmpNameArr[0];
				$basketAddrArr['last_name'] = $tmpNameArr[1];
			}

			// Map address fields
			foreach ($basketAddrArr as $mapKey => $value)	{
				$paymentLibKey = $mapAddrFields[$mapKey];
				if ($paymentLibKey != '')	{
					$addrArr[$key][$paymentLibKey] = $value;
				}
			}

			// guess country and language settings for invoice address. One of these vars has to be set: country, countryISO2, $countryISO3 or countryISONr
			// you can also set 2 or more of these codes. The codes will be joined with 'OR' in the select-statement and only the first
			// record which is found will be returned. If there is no record at all, the codes will be returned untouched

			if ($bUseStaticInfo)	{
				$countryArray = tx_staticinfotables_div::fetchCountries($addrArr[$key]['country'], $addrArr[$key]['countryISO2'], $addrArr[$key]['countryISO3'], $addrArr[$key]['countryISONr']);
				$countryRow = $countryArray[0];

				if (count($countryRow))	{
					$addrArr[$key]['country'] = $countryRow['cn_iso_2'];
				}
			}
		}
		$addrArr['delivery']['note'] = $deliveryNote;

		$totalCount = 0;
		foreach ($itemArray as $sort => $actItemArray) {
			foreach ($actItemArray as $k1 => $actItem) {
				$totalCount += intval($actItem['count']);
			}
		}

		// Fill the basket array
		$basketArray = array();

		foreach ($itemArray as $sort => $actItemArray) {
			$basketArr[$sort] = array();
			foreach ($actItemArray as $k1 => $actItem) {
				$row = $actItem['rec'];
				$tax = $row['tax'];
				$count = intval($actItem['count']);
				$basketRow = array(
					'item_name' => $row['title'],
					'on0' => $row['title'],
					'os0' => $row['note'],
					'on1' => $row['www'],
					'os2' => $row['note2'],
					'quantity' => $count,
// 					'singlepricenotax' => $this->fFloat($actItem['priceNoTax']),
// 					'singleprice' =>  $this->fFloat($actItem['priceTax']),
					'amount' => self::fFloat($actItem['priceNoTax']),
					'shipping' => $count * $totalArr['shippingtax'] / $totalCount,
					'handling' => self::fFloat($actItem['handling']),
					'taxpercent' => $tax,
					'tax' => self::fFloat($actItem['priceTax'] - $actItem['priceNoTax']),
					'totaltax' => self::fFloat($actItem['totalTax']) - self::fFloat($row['totalNoTax']),
					'item_number' => $row['itemnumber'],
				);
				$basketArray[$sort][] = $basketRow;
			}
		}
	}


	public static function fFloat ($value=0)	{
		if (is_float($value))	{
			$float = $value;
		} else {
			$float = floatval($value);
		}

		return round($float,2);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/transactor/lib/class.tx_transactor_api.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/transactor/lib/class.tx_transactor_api.php']);
}

?>