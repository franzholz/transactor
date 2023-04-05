<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger <franz@ttproducts.de>
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
* @author  Franz Holzinger <franz@ttproducts.de>
* @package TYPO3
* @subpackage transactor
*
*/


use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_transactor_api {
    protected static $cObj;


    static public function init (
        $pLangObj,
        $cObj,
        $conf
    ) {
        if (!is_object($cObj)) {
            $cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();
        }

        $languageObj = GeneralUtility::makeInstance(\JambageCom\Transactor\Api\Localization::class);
        $languageObj->init1(
            $pLangObj,
            $conf['_LOCAL_LANG.'],
            TRANSACTOR_LANGUAGE_PATH
        );
        $languageObj->loadLocalLang(
            TRANSACTOR_LANGUAGE_PATH . 'locallang.xlf'
        );

        self::$cObj = $cObj;
    }


    static public function getMarkers (
        $conf,
        &$markerArray
    ) {
        $cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();
        $languageObj = GeneralUtility::makeInstance(\JambageCom\Transactor\Api\Localization::class);
        $languageObj->init1(
            '',
            $conf['marks.']
        );

        $languageObj->loadLocalLang(
            TRANSACTOR_LANGUAGE_PATH . 'locallang_marker.xlf'
        );

        $locallang = $languageObj->getLocalLang();
        $languageKey = $languageObj->getLocalLangKey();

        if (isset($locallang[$languageKey])) {
            $langArray = array_merge($locallang['default'], $locallang[$languageKey]);
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

        $newMarkerArray = [];

        if(isset($langArray) && is_array($langArray)) {
            foreach ($langArray as $key => $value) {
                if (is_array($value)) {
                    $value = $value[0]['target'];
                }
                $newMarkerArray['###' . strtoupper($key) . '###'] =
                    tx_div2007_core::substituteMarkerArray($value, $markerArray);
            }
        } else {
            $langArray = [];
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
            [
                'form' => TX_TRANSACTOR_GATEWAYMODE_FORM,
                'ajax' => TX_TRANSACTOR_GATEWAYMODE_AJAX,
                'webservice' => TX_TRANSACTOR_GATEWAYMODE_WEBSERVICE
            ];
        $gatewayMode = $gatewayModeArray[$confScript['gatewaymode']];
        if (!$gatewayMode) {
            $gatewayMode = $gatewayModeArray['form'];
        }
        return $gatewayMode;
    }


    /**
    * deprecated: use \JambageCom\Transactor\Api\PaymentPreviousApi::getGatewayProxyObject instead
    *
    * returns the gateway proxy object
    */
    static public function getGatewayProxyObject (
        $handleLib,
        $confScript
    ) {
        require_once(ExtensionManagementUtility::extPath($handleLib) . 'model/class.tx_' . $handleLib . '_gatewayfactory.php');

        if (is_array($confScript)) {
            $gatewayExtKey = $confScript['extName'];
            $gatewayFactoryObj = tx_transactor_gatewayfactory::getInstance();
            $gatewayFactoryObj->registerGatewayExt($gatewayExtKey);
            $paymentMethod = $confScript['paymentMethod'];
            $gatewayProxyObject =
                $gatewayFactoryObj->getGatewayProxyObjectByPaymentMethod(
                    $paymentMethod
                );
        }

        return $gatewayProxyObject;
    }


    static public function getItemMarkerSubpartArrays (
        $confScript,
        &$subpartArray,
        &$wrappedSubpartArray
    ) {
        $bUseTransactor = false;
        if (
            isset($confScript) &&
            is_array($confScript) &&
            isset($confScript['extName'])
        ) {
            $extKey = $confScript['extName'];
            if (ExtensionManagementUtility::isLoaded($extKey)) {
                $bUseTransactor = true;
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
        $callingExtensionKey,
        $orderUid
    ) {
        $referenceUid = false;
        $gatewayProxyObject = \JambageCom\Transactor\Api\PaymentPreviousApi::getGatewayProxyObject($confScript);
        if (
            $orderUid &&
            method_exists($gatewayProxyObject, 'generateReferenceUid')
        ) {
            $referenceUid =
                $gatewayProxyObject->generateReferenceUid(
                    $orderUid,
                    $callingExtensionKey
                );
        }
        return $referenceUid;
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
        &$bFinalVerify,
        &$markerArray,
        &$templateFilename,
        &$localTemplateCode,
        &$errorMessage
    ) {
        $languageObj = GeneralUtility::makeInstance(\JambageCom\Transactor\Api\Localization::class);
        $bFinalize = false;
        $bFinalVerify = false;
        $gatewayExtKey = '';
        $result = '';

        if (
            !is_array($itemArray) ||
            !is_array($calculatedArray)
        ) {
            $bValidParams = false;
        } else {
            $bValidParams = true;
        }

        if ($bValidParams) {
            $lConf = $confScript;
            if (is_array($confScript)) {
                $gatewayExtKey = $confScript['extName'];
            }

            if (
                $gatewayExtKey != '' &&
                ExtensionManagementUtility::isLoaded($gatewayExtKey)
            ) {
                // everything is ok
            } else {
                if ($gatewayExtKey == '') {
                    $errorMessage =
                        $languageObj->getLabel(
                            'extension_payment_missing'
                        );
                } else {
                    $message =
                        $languageObj->getLabel(
                            'extension_missing'
                        );

                    $messageArray =  explode('|', $message);
                    $errorMessage = $messageArray[0] . $gatewayExtKey . $messageArray[1];
                }
            }

            $paymentMethod = $confScript['paymentMethod'];
            $gatewayProxyObject =
                \JambageCom\Transactor\Api\PaymentPreviousApi::getGatewayProxyObject(
                    $confScript
                );

            if ($errorMessage == '') {
                if (is_object($gatewayProxyObject)) {
                    $gatewayKey = $gatewayProxyObject->getGatewayKey();
                    $gatewayMode =
                        self::getGatewayMode(
                            $handleLib,
                            $confScript
                        );
                    $ok = $gatewayProxyObject->transaction_init(
                        TX_TRANSACTOR_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER,
                        $paymentMethod,
                        $gatewayMode,
                        $extKey,
                        $confScript['conf.']
                    );

                    if (!$ok) {
                        $errorMessage =
                            $languageObj->getLabel(
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

                    $referenceId =
                        self::getReferenceUid(
                            $handleLib,
                            $confScript,
                            $extKey,
                            $orderUid
                        ); // in the case of a callback, a former order than the current would have been read in

                    if (!$referenceId) {
                        $errorMessage =
                            $languageObj->getLabel(
                                'error_reference_id'
                            );
                        return '';
                    }

                    $transactionDetailsArray = self::getTransactionDetails(
                        $referenceId,
                        $handleLib,
                        $confScript,
                        $extKey,
                        $gatewayExtKey,
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

                        // Set payment details:
                    $ok =
                        $gatewayProxyObject->transaction_setDetails(
                            $transactionDetailsArray
                        );

                    if (!$ok) {
                        $errorMessage =
                            $languageObj->getLabel(
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
                            $bFinalize = true;
                            $bFinalVerify = $gatewayProxyObject->needsVerificationMessage();
                        } else if ($gatewayProxyObject->transaction_failed($transactionResults)) {
                            $errorMessage = $gatewayProxyObject->transaction_message($transactionResults);
                        } else {
                            $gatewayProxyObject->transaction_setOkPage($transactionDetailsArray['transaction']['successlink']);
                            $gatewayProxyObject->transaction_setErrorPage($transactionDetailsArray['transaction']['faillink']);

                            if (
                                $gatewayMode == TX_TRANSACTOR_GATEWAYMODE_WEBSERVICE ||
                                $currentPaymentActivity == 'verify' ||
                                $currentPaymentActivity == 'finalize'
                            ) {
                                $result = $gatewayProxyObject->transaction_process($errorMessage);

                                if ($result) {
                                    $resultsArray = $gatewayProxyObject->transaction_getResults($referenceId); //array holen mit allen daten

                                    if (
                                        $paymentActivity == 'verify' &&
                                        $gatewayProxyObject->transaction_succeded($resultsArray) == false
                                    ) {
                                        $errorMessage = htmlspecialchars($gatewayProxyObject->transaction_message($resultsArray)); // message auslesen
                                    } else {
                                        $bFinalize = true;
                                    }
                                } else if ($errorMessage == '') {
                                    $errorMessage =
                                        $languageObj->getLabel(
                                            'error_gateway_unknown'
                                        );
                                }
                            } else if (
                                $gatewayMode == TX_TRANSACTOR_GATEWAYMODE_AJAX
                            ) {
                                $result = $gatewayProxyObject->transaction_getForm($lConf);
                            } else if (
                                $gatewayMode == TX_TRANSACTOR_GATEWAYMODE_FORM
                            ) {
                                if (!$templateFilename) {
                                    $templateFilename = ($lConf['templateFile'] ? $lConf['templateFile'] : 'EXT:transactor/template/transactor.tmpl');
                                }
                                $localTemplateCode = \JambageCom\Div2007\Utility\FrontendUtility::fileResource($templateFilename);

                                if (!$localTemplateCode && $templateFilename != '') {
                                    $errorMessage =
                                        $languageObj->getLabel(
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
                                $gatewayProxyObject->setFormActionURI($formuri);
                                $formParams = $gatewayProxyObject->transaction_formGetFormParms();

                                if (
                                    $formParams != ''
                                ) {
                                    if (
                                        $formuri != '' &&
                                        strpos($formParams, 'https://') === false &&
                                        strpos($formParams, 'http://') === false
                                    ) {

                                            $formuri .= '?' . $formParams;
                                    } else {
                                        $formuri = $formParams;
                                        $formParams = '';
                                    }
                                }

                                if (
                                    stripos($formuri, 'ERROR') !== false
                                ) {
                                    $bError = true;
                                }

                                if ($formuri && !$bError) {
                                    $markerArray['###HIDDENFIELDS###'] .= $hiddenFields;
                                    $markerArray['###REDIRECT_URL###'] = htmlspecialchars($formuri);
                                    $markerArray['###XHTML_SLASH###'] = '';
                                    $markerArray['###TRANSACTOR_TITLE###'] = $lConf['extTitle'];
                                    $markerArray['###TRANSACTOR_INFO###'] = $lConf['extInfo'];
                                    $returnUrlArray = parse_url($transactionDetailsArray['transaction']['returi']);
                                    $markerArray['###HOST###'] = $returnUrlArray['host'];

                                    if (
                                        $lConf['extImage'] != '' &&
                                        isset($lConf['extImage.']) &&
                                        is_array($lConf['extImage.'])
                                    ) {
                                        $imageOut = self::$cObj->getContentObject($lConf['extImage'])->render($lConf['extImage.']);
                                    } else {
                                        $imageOut = \JambageCom\Div2007\Utility\FrontendUtility::fileResource($lConf['extImage']);
                                    }
                                    $markerArray['###TRANSACTOR_IMAGE###'] = $imageOut;
                                    $markerArray['###TRANSACTOR_WWW###'] = $lConf['extWww'];
                                    self::getMarkers(
                                        $confScript, //  $languageObj->getConf(),
                                        $markerArray
                                    );
                                } else {
                                    if ($bError) {
                                        $errorMessage = $formuri;
                                    } else {
                                        $errorMessage =
                                            $languageObj->getLabel(
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
                        $languageObj->getLabel(
                            'error_gateway_missing'
                        );
                    $messageArray =  explode('|', $message);
                    $errorMessage = $messageArray[0] . $paymentMethod . $messageArray[1];
                }
            }
        } else {
            $message =
                $languageObj->getLabel(
                    'error_api_parameters'
                );
            $messageArray =  explode('|', $message);
            $errorMessage = $messageArray[0] . $paymentMethod . $messageArray[1];
        }

        if ($errorMessage == TX_TRANSACTOR_TRANSACTION_MESSAGE_NOT_PROCESSED) {
            $errorMessage =
                $languageObj->getLabel(
                    'error_transaction_no'
                );
        }

        return $result;
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
        $result = '';

        if (strpos($handleLib, 'transactor') !== false) {
            $gatewayProxyObject =
                \JambageCom\Transactor\Api\PaymentPreviousApi::getGatewayProxyObject(
                    $confScript
                );

            if (is_object($gatewayProxyObject)) {
                $gatewayKey = $gatewayProxyObject->getGatewayKey();
                $paymentBasketArray = [];
                $addressArray = [];
                $totalArray = [];

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
                    $languageObj = GeneralUtility::makeInstance(\JambageCom\Transactor\Api\Localization::class);

                    $errorMessage =
                        $languageObj->getLabel(
                            'error_invalid_data'
                        );

                    return $errorMessage;
                }

                if ($gatewayProxyObject->transaction_succeded() == false) {
                    $result =
                        htmlspecialchars(
                            $gatewayProxyObject->transaction_message(
                                []
                            )
                        );
                }
            }
        }
        return $result;
    } // checkRequired


    static public function getUrl (
        $conf,
        $pid,
        $linkParamArray
    ) {
        if (!$pid) {
            $pid = $GLOBLAS['TSFE']->id;
        }
        $target = '';
        $linkParams = '';
        $linkArray = [];
        if (isset($linkParamArray) && is_array($linkParamArray)) {
            foreach ($linkParamArray as $k => $v) {
                $linkArray[] = $k . '=' . $v;
            }
        }
        $linkParams = implode('&', $linkArray);
        $url =
            \JambageCom\Div2007\Utility\FrontendUtility::getTypoLink_URL(
                self::$cObj,
                $pid,
                $linkParamArray,
                '',
                $conf
            );
        return $url;
    }


    static public function getLanguage () {

        if (
            isset($GLOBALS['TSFE']->config) &&
            is_array($GLOBALS['TSFE']->config) &&
            isset($GLOBALS['TSFE']->config['config']) &&
            is_array($GLOBALS['TSFE']->config['config'])
        ) {
            $result = strtolower($GLOBALS['TSFE']->config['config']['language']);
        } else {
            $result = 'default';
        }
        return $result;
    }


    /**
    * Gets all the data needed for the transaction or the verification check
    */
    static protected function getTransactionDetails (
        $referenceId,
        $handleLib,
        $confScript,
        $extKey,
        $gatewayExtKey,
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
        $paramNameActivity = $extKey . '[activity][' . $paymentActivity . ']';
        $failLinkParams = [$paramNameActivity => '0'];

        if (isset($linkParams) && is_array($linkParams)) {
            $failLinkParams = array_merge($failLinkParams, $linkParams);
        }

        $successLinkParams = [$paramNameActivity => '1'];

        if (isset($linkParams) && is_array($linkParams)) {
            $successLinkParams = array_merge($successLinkParams, $linkParams);
        }

        $notifyUrlParams = ['eID' => str_replace('transactor_', '', $gatewayExtKey)];

        if (isset($linkParams) && is_array($linkParams)) {
            $notifyUrlParams = array_merge($notifyUrlParams, $linkParams);
        }

        $paramReturi = '';
        $successPid = 0;
        $failPid = 0;
        $value = 0;

        if (
            isset($calculatedArray['priceTax']) &&
            is_array($calculatedArray['priceTax']) &&
            isset($calculatedArray['priceTax']['total'])
        ) {
            if (
                is_array($calculatedArray['priceTax']['total'])
            ) {
                if (isset($calculatedArray['priceTax']['total']['ALL'])) {
                    $value = $calculatedArray['priceTax']['total']['ALL'];
                }
            } else {
                $value = $calculatedArray['priceTax']['total'];
            }
        }

            // Prepare some values for the form fields:
        $totalPrice = round($value + 0.001, 2);

        if (
            (
                $paymentActivity == 'finalize' ||
                $paymentActivity == 'verify'
            ) &&
            $confScript['returnPID']
        ) {
            $successPid = $confScript['returnPID'];
        } else {
            $successPid = ($pidArray['PIDthanks'] ? $pidArray['PIDthanks'] : $pidArray['PIDfinalize']);
            if (!$successPid) {
                $successPid = $GLOBALS['TSFE']->id;
            }
        }

        if (
            (
                $paymentActivity == 'finalize' ||
                $paymentActivity == 'verify'
            ) &&
            $confScript['cancelPID']
        ) {
            $failPid = $confScript['cancelPID'];
        } else {
            $failPid = ($pidArray['PIDpayment'] ? $pidArray['PIDpayment'] : $pidArray['PIDbasket']);

            if (!$failPid) {
                $failPid = $GLOBALS['TSFE']->id;
            }
        }

        $conf = ['returnLast' => 'url'];
        $urlDir = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR');
        $retlink = $urlDir . self::getUrl($conf, $GLOBALS['TSFE']->id, $linkParams);
        $returi = $retlink . $paramReturi;

        $faillink = $urlDir . self::getUrl($conf, $failPid, $failLinkParams);
        $successlink = $urlDir . self::getUrl($conf, $successPid, $successLinkParams);
        $notifyurl = $urlDir . self::getUrl($conf, $GLOBALS['TSFE']->id, $notifyUrlParams);

        $transactionDetailsArray = [
            'transaction' => [
                'amount' => $totalPrice,
                'currency' => $confScript['currency'] ? $confScript['currency'] : $confScript['Currency'],
                'orderuid' => $orderUid,
                'returi' => $returi,
                'faillink' => $faillink,
                'successlink' => $successlink,
                'notifyurl' => $notifyurl
            ],
            'total' => $totalArray,
            'tracking' => $trackingCode,
            'address' => $addressArray,
            'basket' => $paymentBasketArray,
            'cc' => $cardRow,
            'language' => self::getLanguage(),
            'calling_extension' => $extKey,
            'confScript' => $confScript
        ];

        if ($paymentActivity == 'verify') {
            $verifyLink = $urlDir . self::getUrl($conf, $GLOBALS['TSFE']->id, $successLinkParams);
            $transactionDetailsArray['transaction']['verifylink'] = $verifyLink;
        }

        if (isset($confScript['conf.']) && is_array($confScript['conf.'])) {
            $transactionDetailsArray['options'] = $confScript['conf.'];
        }
        $transactionDetailsArray['reference'] = $referenceId;

        return $transactionDetailsArray;
    }


    static protected function getPaymentBasket (
        $itemArray,
        $calculatedArray,
        $infoArray,
        $deliveryNote,
        $gatewayConf,
        &$totalArray,
        &$addressArray,
        &$basketArray
    ) {
        $bUseStaticInfo = false;
        $languageObj = GeneralUtility::makeInstance(\JambageCom\Transactor\Api\Localization::class);

        if (ExtensionManagementUtility::isLoaded('static_info_tables')) {
            $bUseStaticInfo = true;
        }

        // Setting up total values
        $totalArray = [];
        $goodsTotalTax = 0;
        $goodsTotalNoTax = 0;
        $goodsTotalDepositTax = 0;
        $goodsTotalDepositNoTax = 0;

        if (
            isset($calculatedArray['priceTax']) &&
            is_array($calculatedArray['priceTax']) &&
            isset($calculatedArray['priceTax']['goodstotal'])
        ) {
            if (
                is_array($calculatedArray['priceTax']['goodstotal'])
            ) {
                if (isset($calculatedArray['priceTax']['goodstotal']['ALL'])) {
                    $goodsTotalTax = $calculatedArray['priceTax']['goodstotal']['ALL'];
                    $goodsTotalNoTax = $calculatedArray['priceNoTax']['goodstotal']['ALL'];
                    $goodsTotalDepositTax = $calculatedArray['deposittax']['goodstotal']['ALL'];
                    $goodsTotalDepositNoTax = $calculatedArray['depositnotax']['goodstotal']['ALL'];
                }
            } else {
                $goodsTotalTax = $calculatedArray['priceTax']['goodstotal'];
                $goodsTotalNoTax = $calculatedArray['priceNoTax']['goodstotal'];
                $goodsTotalDepositTax = $calculatedArray['deposittax']['goodstotal'];
                $goodsTotalDepositNoTax = $calculatedArray['depositnotax']['goodstotal'];
            }
        }

        $totalArray['goodsnotax'] = self::fFloat($goodsTotalNoTax);
        $totalArray['goodstax'] = self::fFloat($goodsTotalTax);

        // is the new calculatedArray format used?
        if (
            isset($calculatedArray['shipping']) &&
            is_array($calculatedArray['shipping'])
        ) {
            $totalArray['paymentnotax'] = self::fFloat($calculatedArray['payment']['priceNoTax']);
            $totalArray['paymenttax'] = self::fFloat($calculatedArray['payment']['priceTax']);
            $totalArray['shippingnotax'] = self::fFloat($calculatedArray['shipping']['priceNoTax']);
            $totalArray['shippingtax'] = self::fFloat($calculatedArray['shipping']['priceTax']);
            if (
                isset($calculatedArray['handling']) &&
                is_array($calculatedArray['handling'])
            ) {
                $totalArray['handlingnotax'] = 0;
                $totalArray['handlingtax'] = 0;
                foreach ($calculatedArray['handling'] as $key => $priceArray) {
                    $totalArray['handlingnotax'] += self::fFloat($priceArray['priceNoTax']);
                    $totalArray['handlingtax'] += self::fFloat($priceArray['priceTax']);
                }
            }
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


        $maxTax = 0;
        if (
            isset($calculatedArray['maxtax']) &&
            is_array($calculatedArray['maxtax']) &&
            isset($calculatedArray['maxtax']['goodstotal'])
        ) {
            if (
                is_array($calculatedArray['maxtax']['goodstotal'])
            ) {
                if (isset($calculatedArray['maxtax']['goodstotal']['ALL'])) {
                    $maxTax = $calculatedArray['maxtax']['goodstotal']['ALL'];
                }
            } else {
                $maxTax = $calculatedArray['maxtax']['goodstotal'];
            }
        }

        $totalArray['taxrate'] = $maxTax;
        $totalArray['totaltax'] = self::fFloat($totalArray['amounttax'] - $totalArray['amountnotax']);

        // Setting up address info values
        $mapAddrFields = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'address' => 'address1',
            'zip' => 'zip',
            'city' => 'city',
            'telephone' => 'phone',
            'email' => 'email',
            'country' => 'country'
        ];
        $tmpAddrArray = [
            'person' => $infoArray['billing'],
            'delivery' => $infoArray['delivery']
        ];
        $addressArray = [];

        foreach($tmpAddrArray as $key => $basketAddressArray) {
            $addressArray[$key] = [];

            // Correct firstname- and lastname-field if they have no value
            if (
                $basketAddressArray['first_name'] == '' &&
                $basketAddressArray['last_name'] == ''
            ) {
                $tmpNameArr = explode(' ', $basketAddressArray['name'], 2);
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
                $countryArray = \JambageCom\Div2007\Utility\StaticInfoTablesUtility::fetchCountries(
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
        $basketArray = [];
        $newTotalArray =
            [
                'payment'  => '0',
                'shipping' => '0',
                'handling' => '0'
            ];
        $lastSort = '';
        $lastKey = 0;

        foreach ($itemArray as $sort => $actItemArray) {
            if ($sort == '') {
                $sort = 'basketsort';
            }
            $lastSort = $sort;
            $basketArray[$sort] = [];

            foreach ($actItemArray as $key => $actItem) {
                $row = $actItem['rec'];
                // $tax = $row['tax']; NEU
                $tax = $actItem['tax'];
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

                $payment = self::fFloat($count * $totalArray['paymenttax'] / $totalCount, 2);
                $newTotalArray['payment'] += $payment;
                $shipping = self::fFloat($count * $totalArray['shippingtax'] / $totalCount, 2);
                $newTotalArray['shipping'] += $shipping;
                $handling = self::fFloat($actItem['handling'], 2);
                $newTotalArray['handling'] += $handling;

                $count = intval($actItem['count']);

                $basketRow = [
                    'item_name'  => $row['title'],
                    'quantity'   => $count,
                    'amount'     => self::fFloat($actItem['priceNoTax']),
                    'payment'    => $payment,
                    'shipping'   => $shipping,
                    'handling'   => $handling,
                    'taxpercent' => $tax,
                    'tax' => self::fFloat($actItem['priceTax'] - $actItem['priceNoTax']),
                    'totaltax' => self::fFloat($actItem['totalTax'] - $actItem['totalNoTax']),
                    'item_number' => $row['itemnumber'],
                ];

                for ($i = 0; $i <= 7; ++$i) {

                    if (isset($gatewayConf['on' . $i . 'n'])) {
                        $fieldName = $gatewayConf['on' . $i . 'n'];

                        if ($fieldName == 'note' || $fieldName == 'note2') {
                            $value = strip_tags(nl2br($row[$fieldName]));
                            $value = str_replace ('&nbsp;', ' ', $value);
                        } else if (
                            $fieldName != '' &&
                            isset($row[$fieldName])
                        ) {
                            $value = $row[$fieldName];
                        }

                        if ($value != '') {
                            $basketRow['on' . $i] = $gatewayConf['on' . $i . 'l'];
                            $basketRow['os' . $i] = $value;
                        }
                    }
                }
                $basketArray[$sort][$key] = $basketRow;
                $lastKey = $key;
            }
        }

        // fix rounding errors
        foreach ($newTotalArray as $newType => $newAmount) {
            if ($newTotalArray[$newType] != $totalArray[$newType . 'tax']) {
                $basketArray[$lastSort][$lastKey][$newType] += $totalArray[$newType . 'tax'] - $newTotalArray[$newType];
            }
        }

        $value1 = 0;
        $value2 = 0;

        if (
            isset($calculatedArray['priceTax']['vouchertotal']) &&
            is_array($calculatedArray['priceTax']['vouchertotal'])
        ) {
            if (isset($calculatedArray['priceTax']['vouchertotal']['ALL'])) {
                $value1 = $calculatedArray['priceTax']['vouchertotal']['ALL'];
                $value2 = $calculatedArray['priceTax']['total']['ALL'];
            }
        } else if (
            isset($calculatedArray['priceTax']['vouchertotal']) &&
            isset($calculatedArray['priceTax']['total'])
        ) {
            $value1 = $calculatedArray['priceTax']['vouchertotal'];
            $value2 = $calculatedArray['priceTax']['total'];
        }

        if (
            $value1 > 0 &&
            $value1 != $value2
        ) {
            $voucherAmount = $value1 - $value2;
            $voucherText =
                $languageObj->getLabel(
                    'voucher_payment_article'
                );
            $basketArray['VOUCHER'][] =
                [
                    'item_name' => $voucherText,
                    'on0' => $voucherText,
                    'quantity' => 1,
                    'amount' => $voucherAmount,
                    'taxpercent' => 0,
                    'item_number' => 'VOUCHER'
                ];

            $totalArray['goodsnotax'] = self::fFloat($goodsTotalNoTax + $voucherAmount);
            if (isset($calculatedArray['depositnotax'])) {
                $totalArray['goodsnotax'] = self::fFloat($goodsTotalNoTax + $goodsTotalDepositNoTax);
            }
            $totalArray['goodstax'] = self::fFloat($goodsTotalTax + $voucherAmount);
            if (isset($calculatedArray['deposittax'])) {
                $totalArray['goodstax'] = self::fFloat($goodsTotalTax + $goodsTotalDepositTax);
            }
        }
    }


    static public function fFloat (
        $value = 0,
        $level = 2
    ) {
        $float = floatval($value);

        return round($float, $level);
    }


    static public function getListenerExtKey (
    ) {
        $result = '';

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['transactor']['listener']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['transactor']['listener'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['transactor']['listener'] as $extKey => $classRef) {
                if ($extKey != '') {
                    $result = $extKey;
                    // Todo: Determine the extension key from the plugins of the current page and by Typoscript settings

                    break;
                }
            }
        }

        return $result;
    }
}

