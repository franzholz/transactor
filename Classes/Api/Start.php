<?php

namespace JambageCom\Transactor\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger <franz@ttproducts.de>
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

use JambageCom\Transactor\Api\Localization;
use JambageCom\Transactor\Constants\GatewayMode;
use JambageCom\Transactor\Constants\Action;
use JambageCom\Transactor\Constants\Message;


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


class Start implements \TYPO3\CMS\Core\SingletonInterface
{
    protected static $cObj;

    static public function init (
        $pLangObj,
        $cObj,
        $conf
    )
    {
        if (!is_object($cObj)) {
            $cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();
        }

        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $languageObj->init1(
            $pLangObj,
            $cObj,
            $conf,
            'Classes/Api/Start.php'
        );
        \tx_div2007_alpha5::loadLL_fh002(
            $languageObj,
            TRANSACTOR_LANGUAGE_PATH . 'locallang.xml'
        );

        self::$cObj = $cObj;
    }


    static public function getMarkers (
        $cObj,
        $conf,
        &$markerArray
    )
    {
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $languageObj->init1(
            '',
            $cObj,
            $conf['marks.'],
            'Classes/Api/Start.php'
        );

        \tx_div2007_alpha5::loadLL_fh002(
            $languageObj,
            TRANSACTOR_LANGUAGE_PATH . 'locallang_marker.xml'
        );

        $locallang = $languageObj->getLocallang();
        $LLkey = $languageObj->getLLkey();

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
                if (is_array($value)) {
                    $value = $value[0]['target'];
                }
                $newMarkerArray['###' . strtoupper($key) . '###'] =
                    $cObj->substituteMarkerArray($value, $markerArray);
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
                'form' => GatewayMode::FORM,
                'ajax' => GatewayMode::AJAX,
                'webservice' => GatewayMode::WEBSERVICE
            );
        $gatewayMode = $gatewayModeArray[$confScript['gatewaymode']];
        if (!$gatewayMode) {
            $gatewayMode = $gatewayModeArray['form'];
        }
        return $gatewayMode;
    }


    static public function getItemMarkerSubpartArrays (
        $confScript,
        &$subpartArray,
        &$wrappedSubpartArray
    )
    {
        $bUseTransactor = false;
        if (
            isset($confScript) &&
            is_array($confScript) &&
            isset($confScript['extName'])
        ) {
            $extensionKey = $confScript['extName'];
            if (ExtensionManagementUtility::isLoaded($extensionKey)) {
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
    )
    {
        $referenceUid = false;
        $gatewayProxyObject = \JambageCom\Transactor\Api\PaymentApi::getGatewayProxyObject($confScript);
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


    static public function test (
    )
    {
        debug('', 'Start::test'); // keep this
    }


    /**
    * Include handle extension library
    */
    static public function includeHandleLib (
        $handleLib,
        $confScript,
        $extensionKey,
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
        $orderNumber, // text string of the order number
        $notificationEmail,
        $cardRow,
        &$bFinalize,
        &$bFinalVerify,
        &$markerArray,
        &$templateFilename,
        &$localTemplateCode,
        &$errorMessage
    )
    {
        $languageObj = GeneralUtility::makeInstance(Localization::class);
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
                        \tx_div2007_alpha5::getLL_fh003(
                            $languageObj,
                            'extension_payment_missing'
                        );
                } else {
                    $message =
                        \tx_div2007_alpha5::getLL_fh003(
                            $languageObj,
                            'extension_missing'
                        );

                    $messageArray =  explode('|', $message);
                    $errorMessage = $messageArray[0] . $gatewayExtKey . $messageArray[1];
                }
            }

            $paymentMethod = $confScript['paymentMethod'];

            if ($errorMessage == '') {
                $gatewayProxyObject =
                    \JambageCom\Transactor\Api\PaymentApi::getGatewayProxyObject(
                        $confScript
                    );
                if (is_object($gatewayProxyObject)) {
                    $gatewayKey = $gatewayProxyObject->getGatewayKey();
                    $gatewayMode =
                        self::getGatewayMode(
                            $handleLib,
                            $confScript
                        );
                    $ok = $gatewayProxyObject->transactionInit(
                        Action::AUTHORIZE_TRANSFER,
                        $paymentMethod,
                        $gatewayMode,
                        $extensionKey,
                        $confScript['conf.']
                    );

                    if (!$ok) {
                        $errorMessage =
                            \tx_div2007_alpha5::getLL_fh003(
                                $languageObj,
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
                            $extensionKey,
                            $orderUid
                        ); // in the case of a callback, a former order than the current would have been read in

                    if (!$referenceId) {
                        $errorMessage =
                            \tx_div2007_alpha5::getLL_fh003(
                                $languageObj,
                                'error_reference_id'
                            );
                        return '';
                    }

                    $transactionDetailsArray = self::getTransactionDetails(
                        $referenceId,
                        $handleLib,
                        $confScript,
                        $extensionKey,
                        $gatewayExtKey,
                        $calculatedArray,
                        $paymentActivity,
                        $pidArray,
                        $linkParams,
                        $trackingCode,
                        $orderUid,
                        $orderNumber,
                        $notificationEmail,
                        $cardRow,
                        $totalArray,
                        $addressArray,
                        $paymentBasketArray
                    );

                        // Set payment details:
                    $ok =
                        $gatewayProxyObject->transactionSetDetails(
                            $transactionDetailsArray
                        );

                    if (!$ok) {
                        $errorMessage =
                            \tx_div2007_alpha5::getLL_fh003(
                                $languageObj,
                                'error_transaction_details'
                            );
                        return '';
                    }

                        // Get results of a possible earlier submit and display messages:
                    $transactionResults =
                        $gatewayProxyObject->transactionGetResults(
                            $referenceId
                        );

                    if (!is_array($transactionResults)) {
                        $row =
                            $gatewayProxyObject->getTransaction(
                                $referenceId
                            );

                        if (is_array($row)) {
                            if ($gatewayProxyObject->transactionIsInitState($row)) {
                                $transactionResults = $row;
                            }
                        } else if (!is_array($row)) {
                            $transactionResults =
                                $gatewayProxyObject->transactionGetResultsSuccess(
                                    'first trial'
                                );
                        }
                    }

                    if (is_array($transactionResults)) {
                        if (
                            $gatewayProxyObject->transactionSucceeded(
                                $transactionResults
                            )
                        ) {
                            $bFinalize = true;
                            $bFinalVerify = $gatewayProxyObject->needsVerificationMessage();
                        } else if (
                            $gatewayProxyObject->transactionFailed(
                                $transactionResults
                            )
                        ) {
                            $errorMessage = $gatewayProxyObject->transactionMessage($transactionResults);
                        } else {
                            $gatewayProxyObject->transactionSetOkPage(
                                $transactionDetailsArray['transaction']['successlink']
                            );
                            $gatewayProxyObject->transactionSetErrorPage(
                                $transactionDetailsArray['transaction']['faillink']
                            );

                            if (
                                $gatewayMode == GatewayMode::WEBSERVICE ||
                                $currentPaymentActivity == 'verify' ||
                                $currentPaymentActivity == 'finalize'
                            ) {
                                $result = $gatewayProxyObject->transactionProcess($errorMessage);

                                if ($result) {
                                    $resultsArray = $gatewayProxyObject->transactionGetResults($referenceId); //array holen mit allen daten

                                    if (
                                        $paymentActivity == 'verify' &&
                                        $gatewayProxyObject->transactionSucceeded($resultsArray) == false
                                    ) {
                                        $errorMessage =
                                            htmlspecialchars(
                                                $gatewayProxyObject->transactionMessage(
                                                    $resultsArray
                                                )
                                            ); // message auslesen
                                    } else {
                                        $bFinalize = true;
                                    }
                                } else if ($errorMessage == '') {
                                    $errorMessage =
                                        \tx_div2007_alpha5::getLL_fh003(
                                            $languageObj,
                                            'error_gateway_unknown'
                                        );
                                }
                            } else if (
                                $gatewayMode == GatewayMode::AJAX
                            ) {
                                $result = $gatewayProxyObject->transactionGetForm();
                            } else if (
                                $gatewayMode == GatewayMode::FORM
                            ) {
                                if (!$templateFilename) {
                                    $templateFilename = ($lConf['templateFile'] ? $lConf['templateFile'] : 'EXT:' . TRANSACTOR_EXT . '/template/transactor.tmpl');
                                }
                                $localTemplateCode = self::$cObj->fileResource($templateFilename);

                                if (
                                    !$localTemplateCode &&
                                    $templateFilename != ''
                                ) {
                                    $errorMessage =
                                        \tx_div2007_alpha5::getLL_fh003(
                                            $languageObj,
                                            'error_no_template'
                                        );
                                    $errorMessage = sprintf($errorMessage, $templateFilename);
                                    return '';
                                }

                                    // Render hidden fields:
                                $hiddenFields = '';
                                $hiddenFieldsArray =
                                    $gatewayProxyObject->transactionFormGetHiddenFields();

                                if (is_array($hiddenFieldsArray)) {
                                    foreach ($hiddenFieldsArray as $key => $value) {
                                        $hiddenFields .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '" />' . chr(10);
                                    }
                                }
                                $formuri = $gatewayProxyObject->transactionFormGetActionURI();
                                $gatewayProxyObject->setFormActionURI($formuri);
                                $formParams = $gatewayProxyObject->transactionFormGetFormParms();

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
                                        $imageOut = self::$cObj->fileResource($lConf['extImage']);
                                    }
                                    $markerArray['###TRANSACTOR_IMAGE###'] = $imageOut;
                                    $markerArray['###TRANSACTOR_WWW###'] = $lConf['extWww'];
                                    self::getMarkers(
                                        $languageObj->getCObj(),
                                        $languageObj->getConf(),
                                        $markerArray
                                    );
                                } else {
                                    if ($bError) {
                                        $errorMessage = $formuri;
                                    } else {
                                        $errorMessage =
                                            \tx_div2007_alpha5::getLL_fh003(
                                                $languageObj,
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
                        \tx_div2007_alpha5::getLL_fh003(
                            $languageObj,
                            'error_gateway_missing'
                        );
                    $messageArray =  explode('|', $message);
                    $errorMessage = $messageArray[0] . $paymentMethod . $messageArray[1];
                }
            }
        } else {
            $message =
                \tx_div2007_alpha5::getLL_fh003(
                    $languageObj,
                    'error_api_parameters'
                );
            $messageArray =  explode('|', $message);
            $errorMessage = $messageArray[0] . $paymentMethod . $messageArray[1];
        }

        if ($errorMessage == Message::NOT_PROCESSED) {
            $errorMessage =
                \tx_div2007_alpha5::getLL_fh003(
                    $languageObj,
                    'error_transaction_no'
                );
        }

        if ($errorMessage != '') {
            $gatewayFactoryObj =
                \JambageCom\Transactor\Domain\GatewayFactory::getInstance();
            $errors = $gatewayFactoryObj->getErrors();

            if (
                isset($errors) &&
                is_array($errors)
            ) {
                foreach ($errors as $error) {
                    $errorMessage .= '<br>' . $error;
                }
            }
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
        $extensionKey,
        $calculatedArray,
        $paymentActivity,
        $pidArray,
        $linkParams,
        $trackingCode,
        $orderUid,
        $orderNumber,
        $notificationEmail,
        $cardRow
    )
    {
        $result = '';

        if (strpos($handleLib, 'transactor') !== false) {
            $gatewayProxyObject =
                \JambageCom\Transactor\Api\PaymentApi::getGatewayProxyObject(
                    $confScript
                );

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
                        $extensionKey,
                        $calculatedArray,
                        $paymentActivity,
                        $pidArray,
                        $linkParams,
                        $trackingCode,
                        $orderUid,
                        $orderNumber,
                        $notificationEmail,
                        $cardRow,
                        $totalArray,
                        $addressArray,
                        $paymentBasketArray
                    );
                $set = $gatewayProxyObject->transactionSetDetails($transactionDetailsArray);
                $ok = $gatewayProxyObject->transactionValidate();

                if (!$ok) {
                    $languageObj = GeneralUtility::makeInstance(Localization::class);

                    $errorMessage =
                        \tx_div2007_alpha5::getLL_fh003(
                            $languageObj,
                            'error_invalid_data'
                        );

                    return $errorMessage;
                }

                if ($gatewayProxyObject->transactionSucceeded() == false) {
                    $result =
                        htmlspecialchars(
                            $gatewayProxyObject->transactionMessage(
                                array()
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
    )
    {
        if (!$pid) {
            $pid = $GLOBLAS['TSFE']->id;
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
            \tx_div2007_alpha5::getTypoLink_URL_fh003(
                self::$cObj,
                $pid,
                $linkParamArray,
                '',
                $conf
            );

        if (strpos($url, '/') === 0) {
            $url = substr($url, 1);
        }

        if (($position = strrpos($url, '/')) === strlen($url) - 1) {
            $url = substr($url, 0, -1);
        }

        return $url;
    }


    static public function getLanguage ()
    {
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
        $extensionKey,
        $gatewayExtKey,
        $calculatedArray,
        $paymentActivity,
        $pidArray,
        $linkParams,
        $trackingCode,
        $orderUid,
        $orderNumber,
        $notificationEmail,
        $cardRow,
        &$totalArray,
        &$addressArray,
        &$paymentBasketArray
    )
    {
        $paramNameActivity = $extensionKey . '[activity][' . $paymentActivity . ']';
        $failLinkParams = array($paramNameActivity => '0');

        if (isset($linkParams) && is_array($linkParams)) {
            $failLinkParams = array_merge($failLinkParams, $linkParams);
        }

        $successLinkParams = array($paramNameActivity => '1');

        if (isset($linkParams) && is_array($linkParams)) {
            $successLinkParams = array_merge($successLinkParams, $linkParams);
        }

        $notifyUrlParams = array('eID' => str_replace('transactor_', '', $gatewayExtKey));

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

        $conf = array('returnLast' => 'url');
        $urlDir = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR');
        $retlink = $urlDir . self::getUrl($conf, $GLOBALS['TSFE']->id, $linkParams);
        $returi = $retlink . $paramReturi;

        $faillink = $urlDir . self::getUrl($conf, $failPid, $failLinkParams);
        $successlink = $urlDir . self::getUrl($conf, $successPid, $successLinkParams);
        $notifyurl = $urlDir . self::getUrl($conf, $GLOBALS['TSFE']->id, $notifyUrlParams);

        $extensions = array(
            'calling' => $extensionKey,
            'gateway' => $gatewayExtKey,
            'library' => TRANSACTOR_EXT
        );
        $extensionInfo = array();
        foreach ($extensions as $type => $extension) {
            $info = \JambageCom\Div2007\Utility\ExtensionUtility::getExtensionInfo($extension);
            $extensionInfo[$type] = array(
                'key' => $extension,
                'version' => $info['version'],
                'title' => $info['title'],
                'author' => $info['author']
            );
        }

        $transactionDetailsArray = array(
            'transaction' => array(
                'amount' => $totalPrice,
                'currency' => $confScript['currency'] ? $confScript['currency'] : $confScript['Currency'],
                'orderuid' => $orderUid,
                'returi' => $returi,
                'faillink' => $faillink,
                'successlink' => $successlink,
                'notifyurl' => $notifyurl
            ),
            'total' => $totalArray,
            'tracking' => $trackingCode,
            'address' => $addressArray,
            'basket' => $paymentBasketArray,
            'cc' => $cardRow,
            'language' => self::getLanguage(),
            'extension' => $extensionInfo,
            'confScript' => $confScript
        );

        if ($paymentActivity == 'verify') {
            $verifyLink =
                $urlDir .
                self::getUrl(
                    $conf,
                    $GLOBALS['TSFE']->id,
                    $successLinkParams
                );
            $transactionDetailsArray['transaction']['verifylink'] = $verifyLink;
        }

        if (isset($confScript['conf.']) && is_array($confScript['conf.'])) {
            $transactionDetailsArray['options'] = $confScript['conf.'];
        }
        $transactionDetailsArray['reference'] = $referenceId;
        $transactionDetailsArray['order'] = array();
        $transactionDetailsArray['order']['orderNumber'] = $orderNumber;
        $transactionDetailsArray['order']['notificationEmail'] = array($notificationEmail);

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
    )
    {
        $bUseStaticInfo = false;
        $languageObj = GeneralUtility::makeInstance(Localization::class);

        if (ExtensionManagementUtility::isLoaded('static_info_tables')) {
            $bUseStaticInfo = true;
        }

        // Setting up total values
        $totalArray = array();
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
                $countryArray =
                    \JambageCom\Div2007\Utility\StaticInfoTablesUtility::fetchCountries(
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
        $newTotalArray =
            array(
                'payment'  => '0',
                'shipping' => '0',
                'handling' => '0'
            );
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

                $basketRow = array(
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
                );

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
                \tx_div2007_alpha5::getLL_fh003(
                    $languageObj,
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
    )
    {
        $float = floatval($value);

        return round($float, $level);
    }


    static public function getListenerExtKey ()
    {
        $result = '';

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TRANSACTOR_EXT]['listener']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TRANSACTOR_EXT]['listener'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TRANSACTOR_EXT]['listener'] as $extensionKey => $classRef) {
                if ($extensionKey != '') {
                    $result = $extensionKey;
                    // Todo: Determine the extension key from the plugins of the current page and by Typoscript settings

                    break;
                }
            }
        }

        return $result;
    }
}

