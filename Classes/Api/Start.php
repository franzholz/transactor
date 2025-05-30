<?php

declare(strict_types=1);

namespace JambageCom\Transactor\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2025 Franz Holzinger <franz@ttproducts.de>
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

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\HtmlUtility;

use JambageCom\Transactor\Api\Address;
use JambageCom\Transactor\Api\Localization;
use JambageCom\Transactor\Api\PaymentApi;

use JambageCom\Transactor\Constants\Action;
use JambageCom\Transactor\Constants\Feature;
use JambageCom\Transactor\Constants\Field;
use JambageCom\Transactor\Constants\GatewayMode;
use JambageCom\Transactor\Constants\Message;


class Start implements \TYPO3\CMS\Core\SingletonInterface
{
    private static $hasActionParameters = false;
    private static $request = null;
    private static int $id;

    static public function init (
        $pLangObj,
        ServerRequestInterface $request,
        array $conf,
        $keepLanguageSettings = true
    )
    {
        $extensionKey = 'transactor';
        $languageSubpath = '/Resources/Private/Language/';
        $languagePath = 'EXT:' . $extensionKey . $languageSubpath;
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $languageObj->init1(
            $pLangObj,
            $conf['_LOCAL_LANG.'] ?? '',
            $request,
            $keepLanguageSettings
        );
        $languageObj->loadLocalLang(
            $languagePath . 'locallang.xlf'
        );
        static::$request = $request;
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());
        $typo3VersionMain = $typo3VersionArray['version_main'];

        if ($typo3VersionMain >= 13) {
            static::$id = $request->getAttribute('frontend.page.information')->getId();
        } else {
            static::$id = $GLOBALS['TSFE']->id;
        }

    }

    static public function getMarkers (
        array $conf,
        &$markerArray
    )
    {
        $extensionKey = 'transactor';
        $languageSubpath = '/Resources/Private/Language/';
        $languagePath = 'EXT:' . $extensionKey . $languageSubpath;
        $cObj = FrontendUtility::getContentObjectRenderer();
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $languageObj->init1(
            '',
            $conf['_LOCAL_LANG.'] ?? '',
            static::$request
        );
        $languageObj->loadLocalLang(
            $languagePath . 'locallang_marker.xlf'
        );
        $locallang = $languageObj->getLocalLang();
        $localLangKey = $languageObj->getLocalLangkey();

        if (isset($locallang[$localLangKey])) {
            $langArray = array_merge($locallang['default'], $locallang[$localLangKey]);
        } else {
            $langArray = $locallang['default'];
        }
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        if (
            isset($conf['marks.']) &&
            is_array($conf['marks.'])
        ) {
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
                if (
                    is_array($value) &&
                    isset($value[0])
                ) {
                    if (!empty($value[0]['target'])) {
                        $value = $value[0]['target'];
                    } else {
                        $value = $value[0]['source'];
                    }
                    $newMarkerArray['###' . strtoupper($key) . '###'] =
                        $templateService->substituteMarkerArray($value, $markerArray);
                }
            }
        } else {
            $langArray = [];
        }
        $markerArray = array_merge($markerArray, $newMarkerArray);
    }

    static public function getItemMarkerSubpartArrays (
        $confScript,
        array &$subpartArray,
        array &$wrappedSubpartArray
    ): bool
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

        return $bUseTransactor;
    }

    static public function getReferenceUid (
        $handleLib,
        $confScript,
        $callingExtensionKey,
        $orderUid
    )
    {
        $referenceUid = false;
        $gatewayProxyObject =
            PaymentApi::getGatewayProxyObject(
                static::$request,
                $confScript
            );

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

    // This method is implemented to test function calls.
    static public function test (
    )
    {
        debug('', 'Start::test'); // keep this
    }

    static public function checkLoaded (
        &$errorMessage,
        Localization $languageObj,
        $gatewayExtKey
    ) {
        $result = false;
        if (
            $gatewayExtKey != '' &&
            ExtensionManagementUtility::isLoaded($gatewayExtKey)
        ) {
            $result = true;
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

        return $result;
    }

    /**
    * deprecated old API
    * use the render method instead
    *
    * Include handle extension library
    */
    static public function includeHandleLib (
        &$finalize,
        &$finalVerify,
        &$gatewayStatus,
        &$markerArray,
        &$templateFilename,
        &$localTemplateCode,
        &$errorMessage,
        $handleLib,
        $confScript,
        $extensionKey,
        array $itemArray,
        array $calculatedArray,
        $deliveryNote,
        $paymentActivity,
        $currentPaymentActivity,
        array $infoArray,
        $pidArray,
        $linkParams,
        string $trackingCode,
        int $orderUid,
        string $orderNumber, // text string of the order number
        $notificationEmail,
        $cardRow,
        $variantFields
    )
    {
        $gatewayStatus = '';
        if (!is_array($confScript)) {
            return false;
        }

        $result = static::render(
            $finalize,
            $finalVerify,
            $gatewayStatus,
            $markerArray,
            $templateFilename,
            $localTemplateCode,
            $errorMessage,
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
            $orderNumber,
            $notificationEmail,
            $cardRow,
            $variantFields
        );
        return $result;
    }

    static public function render (
        &$finalize,
        &$finalVerify,
        &$gatewayStatus,
        array &$markerArray,
        &$templateFilename,
        &$localTemplateCode,
        &$errorMessage,
        $handleLib,
        array $confScript,
        $shippingTitle,
        $extensionKey,
        array $itemArray,
        array $calculatedArray,
        $deliveryNote,
        $paymentActivity,
        $currentPaymentActivity,
        array $infoArray,
        $pidArray,
        $linkParams,
        string $trackingCode,
        int $orderUid,
        string $orderNumber, // text string of the order number
        $notificationEmail,
        $cardRow,
        ...$options
    )
    {
        $variantFields = $options[0] ?? [];
        if (!is_array($variantFields)) {
            throw new \RuntimeException('Error in transactor: Render method parameter option[0] "' .json_decode($variantFields) . '" must be an array.  "JambageCom\Transactor\Api\Start"', 1711197986
);
        }
        $extraData = $options[1] ?? '';
        $markerArray['###HIDDENFIELDS###'] = $markerArray['###HIDDENFIELDS###'] ?? '';

// TODO:
// siehe getTransactionDetails
//     $returnUrl,
//     $cancelUrl
// aus $pidArray ermitteln

        $gatewayStatus = [];
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $cObj = FrontendUtility::getContentObjectRenderer();
        $finalize = false;
        $finalVerify = false;
        $isError = false;
        $gatewayExtKey = '';
        $result = '';
        $emConf = '';
        $xhtmlFix = HtmlUtility::generateXhtmlFix();

        if (
            !is_array($itemArray) ||
            !is_array($calculatedArray)
        ) {
            $paramsValid = false;
        } else {
            $paramsValid = true;
        }

        if ($paramsValid) {
            $lConf = $confScript;
            $gatewayExtKey = $confScript['extName'] ?? '';
            $ok = static::checkLoaded($errorMessage, $languageObj, $gatewayExtKey);
            $paymentMethod = $confScript['paymentMethod'] ?? '';

            if (
                $ok &&
                $errorMessage == '' &&
                $paymentMethod != ''
            ) {
                $gatewayProxyObject =
                    PaymentApi::getGatewayProxyObject(
                        static::$request,
                        $confScript
                    );
                if (is_object($gatewayProxyObject)) {
                    $paymentBasket =
                        PaymentApi::convertToTransactorBasket(
                            $itemArray,
                            $variantFields
                        );

                    $ok = $gatewayProxyObject->transactionInit(
                        Action::AUTHORIZE_TRANSFER,
                        $paymentMethod,
                        $extensionKey,
                        $confScript['templateFile'] ?? '',
                        $orderUid,
                        $orderNumber,
                        $confScript['currency'] ? $confScript['currency'] : 'EUR',
                        $confScript['conf.'] ?? []
                    );

                    PaymentApi::storeInit(
                        static::$request->getAttribute('frontend.user'),
                        Action::AUTHORIZE_TRANSFER,
                        $paymentMethod,
                        $extensionKey,
                        $confScript['templateFile'] ?? '',
                        $orderUid,
                        $orderNumber,
                        $confScript['currency'] ? $confScript['currency'] : 'EUR',
                        $confScript['conf.'] ?? []
                    );

                    $gatewayMode = $gatewayProxyObject->getGatewayMode();

                    if (!$ok) {
                        $errorMessage =
                            $languageObj->getLabel(
                                'error_transaction_init'
                            );
                        return '';
                    }

                    $gatewayConf = $gatewayProxyObject->getConf();
                    $emConf = $gatewayProxyObject->getExtensionManagerConf();
                    if (isset($confScript['em.'])) {
                        $emConf = array_replace_recursive($emConf, $confScript['em.']);
                    }

                    if ($emConf) {
                        $gatewayConf = array_replace_recursive($gatewayConf, $emConf);
                        $gatewayProxyObject->setConf($gatewayConf);
                    }

                    static::getPaymentBasket(
                        $paymentBasketArray,
                        $totalArray,
                        $addressArray,
                        $itemArray,
                        $calculatedArray,
                        $infoArray,
                        $deliveryNote,
                        $gatewayConf
                    );
                    $gatewayProxyObject->setBasket($paymentBasketArray);
                    $gatewayProxyObject->setTotals($totalArray);
                    $gatewayProxyObject->setAddresses($addressArray);
                    $gatewayProxyObject->setShippingTitle($shippingTitle);

                    $referenceId =
                        static::getReferenceUid(
                            $handleLib,
                            $confScript,
                            $extensionKey,
                            $orderUid
                        ); // in the case of a callback, a former order than the current would have been read in

                    if (!$referenceId) {
                        $errorMessage =
                            $languageObj->getLabel(
                                'error_reference_id'
                            );
                        return '';
                    }
                    PaymentApi::storeReferenceUid(
                        static::$request->getAttribute('frontend.user'),
                        $referenceId
                    );
                    $gatewayKey = $gatewayProxyObject->getGatewayKey();
                    $transactionDetailsArray =
                        static::getTransactionDetails(
                            $referenceId,
                            $handleLib,
                            $confScript,
                            $extensionKey,
                            $gatewayExtKey,
                            $gatewayKey,
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
                            $languageObj->getLabel(
                                'error_transaction_details'
                            );
                        return '';
                    }

                    // neu FHO
                    $transactionResults = static::fetchTransactionResults($gatewayProxyObject, $referenceId);

                    if (is_array($transactionResults)) {
                        if (
                            $gatewayProxyObject->transactionSucceeded(
                                $transactionResults
                            )
                        ) {
                            $finalize = true;
                            $finalVerify = $gatewayProxyObject->needsVerificationMessage();
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
                                    $resultsArray = $gatewayProxyObject->transactionGetResults($referenceId); // Array holen mit allen daten

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
                                        $finalize = true;
                                    }
                                } else if ($errorMessage == '') {
                                    $errorMessage =
                                        $languageObj->getLabel(
                                            'error_gateway_unknown'
                                        );
                                }
                            } else if (
                                $gatewayMode == GatewayMode::AJAX
                            ) {
                                $result = $gatewayProxyObject->transactionGetForm();
                                if (!$result) {
                                    $errorDetails = $gatewayProxyObject->transactionGetErrorDetails();
                                    $errorMessage = 'ERROR: ' . $errorDetails;
                                }
                            } else if (
                                $gatewayMode == GatewayMode::FORM
                            ) {
                                if (!$templateFilename) {
                                    if ($lConf['templateFile'] != '') {
                                        $templateFilename = $lConf['templateFile'];
                                    } else {
                                        $templateFilename = $gatewayProxyObject->getTemplateFilename();
                                    }
                                }
                                $localTemplateCode = FrontendUtility::fileResource($templateFilename);

                                if (
                                    !$localTemplateCode &&
                                    $templateFilename != ''
                                ) {
                                    $errorMessage =
                                        $languageObj->getLabel(
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
                                        $hiddenFields .=
                                            '<input type="hidden" name="' . htmlspecialchars((string) $key) .
                                            '" value="' . htmlspecialchars((string) $value) . '"' . $xhtmlFix . '>' .
                                            chr(10);
                                    }
                                }
                                $scriptParametersArray =
                                    $gatewayProxyObject->transactionFormGetScriptParameters();
                                $script = '';
                                if (is_array($scriptParametersArray)) {
                                    $script = '<script ';
                                    $scriptLines = [];
                                    foreach ($scriptParametersArray as $key => $value) {
                                        $scriptLines[] = htmlspecialchars((string) $key) . '="' . htmlspecialchars($value) . '"';
                                    }
                                    $script .= implode(chr(10), $scriptLines) . '></script>';
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
                                    !$formuri ||
                                    stripos($formuri, 'ERROR') !== false
                                ) {
                                    $isError = true;
                                }

                                if (
                                    $formuri != '' &&
                                    !$isError
                                ) {
                                    $markerArray['###HIDDENFIELDS###'] .= $hiddenFields;
                                    $markerArray['###SCRIPT###'] = $script;
                                    $markerArray['###REDIRECT_URL###'] = htmlspecialchars($formuri);
                                    $markerArray['###XHTML_SLASH###'] = $xhtmlFix;
                                    $markerArray['###TRANSACTOR_TITLE###'] = $lConf['extTitle'];
                                    $markerArray['###TRANSACTOR_INFO###'] = $lConf['extInfo'];
                                    $returnUrlArray = parse_url($transactionDetailsArray['transaction']['returi']);
                                    $markerArray['###HOST###'] = $returnUrlArray['host'];

                                    if (
                                        $lConf['extImage'] != '' &&
                                        isset($lConf['extImage.']) &&
                                        is_array($lConf['extImage.'])
                                    ) {
                                        $imageOut = $cObj->getContentObject($lConf['extImage'])->render($lConf['extImage.']);
                                    } else {
                                        $imageOut = FrontendUtility::fileResource($lConf['extImage']);
                                    }
                                    $markerArray['###TRANSACTOR_IMAGE###'] = $imageOut;
                                    $markerArray['###TRANSACTOR_WWW###'] = $lConf['extWww'];
                                    static::getMarkers(
                                        $lConf,
                                        $markerArray
                                    );
                                } else {
                                    if ($isError) {
                                        if (
                                            $formuri != '' &&
                                            stripos($formuri, 'ERROR'
                                        ) !== false) {
                                            $errorMessage = $formuri;
                                        } else {
                                            $errorDetails = $gatewayProxyObject->transactionGetErrorDetails();
                                            $errorMessage = 'ERROR: ' . $errorDetails;
                                        }
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
                        $finalize = $transactionResults;
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

        if ($errorMessage == Message::NOT_PROCESSED) {
            $errorMessage =
                $languageObj->getLabel(
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
                    $errorMessage .= '<br' . $xhtmlFix . '>' . $error;
                }
            }
        }

        if ($finalize) {
            // add the markers for a processed transaction
            $parameters = $gatewayProxyObject->transactionGetParameters();
            foreach ($parameters as $key => $parameter) {
                \JambageCom\Div2007\Utility\MarkerUtility::addMarkers(
                    $markerArray,
                    'TRANSACTOR',
                    '_',
                    $key,
                    $parameter
                );
            }
        }

        $gatewayStatus = [];
        if (
            isset($transactionResults) &&
            is_array($transactionResults)
        ) {
            $gatewayStatus = [];
            $gatewayStatus['result'] = $transactionResults;
        }

        return $result;
    } // render


    static protected function fetchTransactionResults($gatewayProxyObject, $referenceId)
    {
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

        return $transactionResults;
    }

    /**
    * Checks if required fields for credit cards and bank accounts are filled in correctly
    */
    static public function checkRequired (
        string $referenceId,
        string $handleLib,
        array $confScript,
        string $extensionKey,
        array $calculatedArray,
        string $paymentActivity,
        array $pidArray,
        $linkParams,
        string $trackingCode,
        int $orderUid,
        string $orderNumber,
        string $notificationEmail,
        array $cardRow
    )
    {
        $gatewayExtKey = $confScript['extName'] ?? '';

        if (strpos($handleLib, 'transactor') !== false) {
            $gatewayProxyObject =
                PaymentApi::getGatewayProxyObject(
                    static::$request,
                    $confScript
                );

            if (is_object($gatewayProxyObject)) {
                $paymentBasketArray = [];
                $addressArray = [];
                $totalArray = [];
                $paymentMethod = $confScript['paymentMethod'];

                $ok = $gatewayProxyObject->transactionInit(
                    Action::INIT,
                    $paymentMethod,
                    $extensionKey,
                    $confScript['templateFile'] ?? '',
                    $orderUid,
                    $orderNumber,
                    $confScript['currency'] ? $confScript['currency'] : 'EUR',
                    $confScript['conf.'] ?? []
                );

                if (!$ok) {
                    $languageObj = GeneralUtility::makeInstance(Localization::class);

                    $errorMessage =
                        $languageObj->getLabel(
                            'error_transaction_init'
                        );

                    return $errorMessage;
                }
                $gatewayKey = $gatewayProxyObject->getGatewayKey();
                $transactionDetailsArray =
                    static::getTransactionDetails(
                        $referenceId,
                        $handleLib,
                        $confScript,
                        $extensionKey,
                        $gatewayExtKey,
                        $gatewayKey,
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

                $set =
                    $gatewayProxyObject->transactionSetDetails(
                        $transactionDetailsArray
                    );
                $ok =
                    $gatewayProxyObject->transactionValidate();

                if (!$ok) {
                    $languageObj = GeneralUtility::makeInstance(Localization::class);

                    $errorMessage =
                        $languageObj->getLabel(
                            'error_invalid_data'
                        );

                    return $errorMessage;
                }
                $transactionResults =
                    static::fetchTransactionResults(
                        $gatewayProxyObject,
                        $referenceId
                    );

                if ($gatewayProxyObject->transactionSucceeded($transactionResults) == false) {
                    $result =
                        htmlspecialchars(
                            $gatewayProxyObject->transactionMessage(
                                $transactionResults
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
        $cObj = FrontendUtility::getContentObjectRenderer();
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
            FrontendUtility::getTypoLink_URL(
                $cObj,
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

    /**
    * Gets all the data needed for the transaction or the verification check
    */
    static protected function getTransactionDetails (
        $referenceId,
        $handleLib,
        array $confScript,
        $extensionKey,
        $gatewayExtKey,
        $gatewayKey,
        array $calculatedArray,
        $paymentActivity,
        array $pidArray,
        $linkParams,
        $trackingCode,
        $orderUid,
        $orderNumber,
        $notificationEmail,
        $cardRow,
        array $totalArray,
        array $addressArray,
        array $paymentBasketArray
    )
    {
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $paramNameActivity = $extensionKey . '[activity][' . $paymentActivity . ']';
        $failLinkParams = [$paramNameActivity => '0'];

        if (isset($linkParams) && is_array($linkParams)) {
            $failLinkParams = array_merge($failLinkParams, $linkParams);
        }

        $successLinkParams = [$paramNameActivity => '1'];

        if (isset($linkParams) && is_array($linkParams)) {
            $successLinkParams = array_merge($successLinkParams, $linkParams);
        }
        $notifyUrlParams = [];
        $notifyUrlParams['transactor'] = $gatewayKey;
        $notifyUrlParams['ref'] = PaymentApi::getRequestId($referenceId);

        if (isset($linkParams) && is_array($linkParams)) {
            $notifyUrlParams = array_merge($notifyUrlParams, $linkParams);
        }

        $paramReturi = '';
        $successPid = 0;
        $failPid = 0;
        $value = 0;
        $priceTotalField = 'vouchertotal';
        if (!isset($calculatedArray['priceTax'][$priceTotalField])) {
            $priceTotalField = 'total';
        }

        if (
            isset($calculatedArray['priceTax']) &&
            is_array($calculatedArray['priceTax']) &&
            isset($calculatedArray['priceTax'][$priceTotalField])
        ) {
            if (
                is_array($calculatedArray['priceTax'][$priceTotalField])
            ) {
                if (isset($calculatedArray['priceTax'][$priceTotalField]['ALL'])) {
                    $value = $calculatedArray['priceTax'][$priceTotalField]['ALL'];
                }
            } else {
                $value = $calculatedArray['priceTax'][$priceTotalField];
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
                $successPid = static::$id;
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
                $failPid = static::$id;
            }
        }

        $conf = ['returnLast' => 'url'];
        $urlDir = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR');
        $retlink = $urlDir . static::getUrl($conf, static::$id, $linkParams);
        $returi = $retlink . $paramReturi;
        $faillink = $urlDir . static::getUrl($conf, $failPid, $failLinkParams);
        $successlink = $urlDir . static::getUrl($conf, $successPid, $successLinkParams);
        $notifyurl = $urlDir . static::getUrl($conf, static::$id, $notifyUrlParams);

        $extensions = [
            'calling' => $extensionKey,
            'gateway' => $gatewayExtKey,
            'library' => 'transactor'
        ];
        $extensionInfo = [];
        foreach ($extensions as $type => $extension) {
            $info = \JambageCom\Div2007\Utility\ExtensionUtility::getExtensionInfo($extension);
            $extensionInfo[$type] = [
                'key' => $extension,
                'version' => $info['version'],
                'title' => $info['title'],
                'author' => $info['author']
            ];
        }

        $transactionDetailsArray = [
            'transaction' => [
                'amount' => $totalPrice,
                'currency' => $confScript['currency'] ? $confScript['currency'] : $confScript['Currency'],
                'orderuid' => $orderUid,
                'returi' => $returi,
                'faillink' => $faillink,
                'successlink' => $successlink,
                'notifyurl' => $notifyurl,
            ],
            'total' => $totalArray,
            'tracking' => $trackingCode,
            'address' => $addressArray,
            'basket' => $paymentBasketArray,
            'cc' => $cardRow,
            'language' => $languageObj->getLanguage(),
            'extension' => $extensionInfo,
            'confScript' => $confScript
        ];

        if ($paymentActivity == 'verify') {
            $verifyLink =
                $urlDir .
                static::getUrl(
                    $conf,
                    static::$id,
                    $successLinkParams
                );
            $transactionDetailsArray['transaction']['verifylink'] = $verifyLink;
        }

        if (isset($confScript['conf.']) && is_array($confScript['conf.'])) {
            $transactionDetailsArray['options'] = $confScript['conf.'];
        }
        $transactionDetailsArray['reference'] = $referenceId;
        $transactionDetailsArray['order'] = [];
        $transactionDetailsArray['order']['orderNumber'] = $orderNumber;
        $transactionDetailsArray['order']['notificationEmail'] = [$notificationEmail];

        return $transactionDetailsArray;
    }

    static protected function getPaymentBasket (
        &$basketArray,
        &$totalArray,
        &$addressArray,
        array $itemArray,
        array $calculatedArray,
        array $infoArray,
        $deliveryNote,
        $gatewayConf
    )
    {
        $bUseStaticInfo = false;
        $languageObj = GeneralUtility::makeInstance(Localization::class);

        if (ExtensionManagementUtility::isLoaded('static_info_tables')) {
            $bUseStaticInfo = true;
        }

        // Setting up total values
        $totalArray = [];
        $goodsTotalTax = 0;
        $goodsTotalNoTax = 0;
        $goodsTotalVoucherTax = 0;
        $goodsTotalVoucherNoTax = 0;
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
                    $goodsTotalVoucherTax = $goodsTotalTax;
                    $goodsTotalVoucherNoTax = $goodsTotalNoTax;

                    if (
                        isset($calculatedArray['deposittax']) &&
                        isset($calculatedArray['deposittax']['goodstotal']) &&
                        isset($calculatedArray['deposittax']['goodstotal']['ALL'])
                    ) {
                        $goodsTotalDepositTax = $calculatedArray['deposittax']['goodstotal']['ALL'];
                        $goodsTotalDepositNoTax = $calculatedArray['depositnotax']['goodstotal']['ALL'];
                    }

                    if (
                        isset($calculatedArray['priceTax']['vouchertotal']) &&
                        isset($calculatedArray['priceTax']['vouchertotal']['ALL'])
                    ) {
                        $goodsTotalVoucherTax = $calculatedArray['priceTax']['vouchertotal']['ALL'];
                        $goodsTotalVoucherNoTax = $calculatedArray['priceNoTax']['vouchertotal']['ALL'];
                    }
                }
            } else {
                $goodsTotalTax = $calculatedArray['priceTax']['goodstotal'];
                $goodsTotalNoTax = $calculatedArray['priceNoTax']['goodstotal'];
                $goodsTotalVoucherTax = $goodsTotalTax;
                $goodsTotalVoucherNoTax = $goodsTotalNoTax;
                if (
                    isset($calculatedArray['deposittax']) &&
                    isset($calculatedArray['deposittax']['goodstotal'])
                ) {
                    $goodsTotalDepositTax = $calculatedArray['deposittax']['goodstotal'];
                    $goodsTotalDepositNoTax = $calculatedArray['depositnotax']['goodstotal'];
                }
                if (
                    isset($calculatedArray['priceTax']['vouchertotal'])
                ) {
                    $goodsTotalVoucherTax = $calculatedArray['priceTax']['vouchertotal'];
                    $goodsTotalVoucherNoTax = $calculatedArray['priceNoTax']['vouchertotal'];
                }
            }
        }

        $totalArray[Field::GOODS_NOTAX] = static::fFloat($goodsTotalNoTax);
        $totalArray[Field::GOODS_TAX] = static::fFloat($goodsTotalTax);
        $totalArray[Field::GOODSVOUCHER_NOTAX] = static::fFloat($goodsTotalVoucherNoTax);
        $totalArray[Field::GOODSVOUCHER_TAX] = static::fFloat($goodsTotalVoucherTax);
        $totalArray[Field::HANDLING_NOTAX] = 0;
        $totalArray[Field::HANDLING_TAX] = 0;

        // is the new calculatedArray format used?
        if (
            isset($calculatedArray['shipping']) &&
            is_array($calculatedArray['shipping'])
        ) {
            $totalArray[Field::PAYMENT_NOTAX] = static::fFloat($calculatedArray['payment']['priceNoTax']);
            $totalArray[Field::PAYMENT_TAX] = static::fFloat($calculatedArray['payment']['priceTax']);
            $totalArray[Field::SHIPPING_NOTAX] = static::fFloat($calculatedArray['shipping']['priceNoTax']);
            $totalArray[Field::SHIPPING_TAX] = static::fFloat($calculatedArray['shipping']['priceTax']);
            if (
                isset($calculatedArray['handling']) &&
                is_array($calculatedArray['handling'])
            ) {
                foreach ($calculatedArray['handling'] as $key => $priceArray) {
                    $totalArray[Field::HANDLING_NOTAX] += static::fFloat($priceArray['priceNoTax']);
                    $totalArray[Field::HANDLING_TAX] += static::fFloat($priceArray['priceTax']);
                }
            }
        } else {
            $totalArray[Field::PAYMENT_NOTAX] = static::fFloat($calculatedArray['priceNoTax']['payment']);
            $totalArray[Field::PAYMENT_TAX] = static::fFloat($calculatedArray['priceTax']['payment']);
            $totalArray[Field::SHIPPING_NOTAX] = static::fFloat($calculatedArray['priceNoTax']['shipping']);
            $totalArray[Field::SHIPPING_TAX] = static::fFloat($calculatedArray['priceTax']['shipping']);
            $totalArray[Field::HANDLING_NOTAX] = static::fFloat($calculatedArray['priceNoTax']['handling']);
            $totalArray[Field::HANDLING_TAX] = static::fFloat($calculatedArray['priceTax']['handling']);
        }

        $totalArray[Field::PRICE_NOTAX] = static::fFloat($calculatedArray['priceNoTax']['vouchertotal']);
        $totalArray[Field::PRICE_TAX] = static::fFloat($calculatedArray['priceTax']['vouchertotal']);
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

        $totalArray[Field::TAX_PERCENTAGE] = $maxTax;
        $totalArray[Field::PRICE_TOTAL_ONLYTAX] = static::fFloat($totalArray[Field::PRICE_TAX] - $totalArray[Field::PRICE_NOTAX]);

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
            Address::PAYER => $infoArray['billing'],
            Address::SHIPPING => $infoArray['delivery']
        ];
        $addressArray = [];

        foreach($tmpAddrArray as $key => $basketAddressArray) {
            $addressArray[$key] = [];

            // Correct firstname- and lastname-field if they have no value
            if (
                empty($basketAddressArray['first_name']) &&
                empty($basketAddressArray['last_name'])
            ) {
                $tmpNameArr = explode(' ', $basketAddressArray['name'], 2);
                $basketAddressArray['first_name'] = $tmpNameArr[0];
                $basketAddressArray['last_name'] = $tmpNameArr[1];
            }

            // Map address fields
            foreach ($basketAddressArray as $mapKey => $value) {
                $paymentLibKey = $mapAddrFields[$mapKey] ?? '';
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
                        $addressArray[$key]['country'] ?? '',
                        $addressArray[$key]['countryISO2'] ?? '',
                        $addressArray[$key]['countryISO3'] ?? '',
                        $addressArray[$key]['countryISONr'] ?? ''
                    );
                $countryRow = $countryArray[0];

                if (
                    is_array($countryRow) &&
                    count($countryRow)
                ) {
                    $addressArray[$key]['country'] = $countryRow['cn_iso_2'];
                }
            }
        }
        $addressArray[Address::SHIPPING]['note'] = $deliveryNote;

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
                Field::PAYMENT_TAX  => '0',
                Field::SHIPPING_TAX => '0',
                Field::HANDLING_TAX => '0'
            ];
        $lastSort = '';
        $lastKey = 0;

        foreach ($itemArray as $sort => $actItemArray) {
            foreach ($actItemArray as $key => $actItem) {
                $row = $actItem['rec'] ?? [];
                // $tax = $row['tax']; NEU
                $tax = $actItem['tax'] ?? '';
                $count = intval($actItem['count'] ?? 0);

                $extArray = $row['ext'] ?? '';
                if (isset($extArray) && is_array($extArray)) {
                    $mergeRow = $extArray['mergeArticles'] ?? '';
                    if (is_array($mergeRow)) {
                        foreach ($mergeRow as $field => $value) {
                            if ($value) {
                                $row[$field] = $value;
                            }
                        }
                    }
                }

                $payment = static::fFloat($count * $totalArray[Field::PAYMENT_TAX] / $totalCount, 2);
                $newTotalArray[Field::PAYMENT_TAX] += $payment;
                $shipping = static::fFloat($count * $totalArray[Field::SHIPPING_TAX] / $totalCount, 2);
                $newTotalArray[Field::SHIPPING_TAX] += $shipping;
                $handling = static::fFloat($actItem['handling'] ?? 0, 2);
                $newTotalArray[Field::HANDLING_TAX] += $handling;

                $basketRow = [
                    Field::NAME        => $row['title'],
                    Field::QUANTITY    => $count,
                    Field::PRICE_NOTAX => static::fFloat($actItem['priceNoTax'] ?? 0),
                    Field::PRICE_TAX   => static::fFloat($actItem['priceTax'] ?? 0),
                    Field::PAYMENT_TAX => $payment,
                    Field::SHIPPING_TAX => $shipping,
                    Field::HANDLING_TAX => $handling,
                    Field::TAX_PERCENTAGE => $tax,
                    Field::PRICE_ONLYTAX => (
                            isset($actItem['priceTax']) && isset($actItem['totalNoTax']) ?
                            (static::fFloat($actItem['priceTax'] - $actItem['priceNoTax'])) :
                            0
                        ),
                    Field::PRICE_TOTAL_ONLYTAX => (
                        isset($actItem['totalTax']) && isset($actItem['totalNoTax']) ? (static::fFloat($actItem['totalTax'] - $actItem['totalNoTax'])) :
                        0
                    ),
                    Field::ITEMNUMBER => $row['itemnumber'] ?? '',
                    Field::DESCRIPTION => (
                        isset($row['note']) ?
                        substr(strip_tags(preg_replace('/(\s|&nbsp;)+/', ' ', $row['note'])), 0, 2) /* Test. ändern auf 1024! +++ */ :
                        ''
                    ),
                ];

                for ($i = 0; $i <= 7; ++$i) {

                    if (isset($gatewayConf['on' . $i . 'n'])) {
                        $fieldName = $gatewayConf['on' . $i . 'n'];
                        $value = '';

                        if ($fieldName == 'note' || $fieldName == 'note2') {
                            $value = strip_tags(nl2br($row[$fieldName] ?? ''));
                            $value = str_replace ('&nbsp;', ' ', $value);
                            $value = str_replace ('&amp;', '&', $value);
                        } else if (
                            $fieldName != '' &&
                            isset($row[$fieldName])
                        ) {
                            $value = $row[$fieldName];
                        }

                        if ($value != '') {
                            if (strlen($value) > $gatewayConf['maximumCharacters']) {
                                $value = substr($value, 0, intval($gatewayConf['maximumCharacters']));
                                $value .= '...';
                            }
                            $basketRow['on' . $i] = $gatewayConf['on' . $i . 'l'];
                            $basketRow['os' . $i] = $value;
                        }
                    }
                }
//                 $basketArray[$sort][$key] = $basketRow;
                $basketArray[] = $basketRow;
                $lastKey = $key;
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
//             $basketArray['VOUCHER'][] =
            $basketArray['VOUCHER'] =
                [
                    Field::NAME => $voucherText,
                    'on0' => $voucherText,
                    Field::QUANTITY => 1,
                    Field::PRICE_NOTAX => $voucherAmount,
                    Field::TAX_PERCENTAGE => 0,
                    Field::ITEMNUMBER => 'VOUCHER',
                    Field::DESCRIPTION => 'Voucher'
                ];

            $totalArray[Field::GOODS_NOTAX] = static::fFloat($goodsTotalNoTax + $voucherAmount);

            if (isset($calculatedArray['depositnotax'])) {
                $totalArray[Field::GOODS_NOTAX] = static::fFloat($goodsTotalNoTax + $goodsTotalDepositNoTax);
            }
            $totalArray[Field::GOODS_TAX] = static::fFloat($goodsTotalTax + $voucherAmount);
            if (isset($calculatedArray['deposittax'])) {
                $totalArray[Field::GOODS_TAX] = static::fFloat($goodsTotalTax + $goodsTotalDepositTax);
            }
        }
    }

    static public function fFloat (
        $value = 0,
        $level = 2
    )
    {
        $float = floatval($value);

        return round($float, intval($level));
    }

    static public function hasActionParameters(
    ) {
        return static::$hasActionParameters;
    }

    static public function readActionParameters (
        &$errorMessage,
        array $confScript
    ): bool {
        $result = false;
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $gatewayExtKey = $confScript['extName'] ?? '';
        $ok = static::checkLoaded($errorMessage, $languageObj, $gatewayExtKey);
        if ($ok) {
            $gatewayProxyObject =
                PaymentApi::getGatewayProxyObject(
                    static::$request,
                    $confScript
                );

            if (
                is_object($gatewayProxyObject)
            ) {
                $result = $gatewayProxyObject->readActionParameters();
            }
            if ($result) {
                static::$hasActionParameters = true;
            }
        }
        return $result;
    }

    static public function addMainWindowJavascript (
        &$errorMessage,
        array $confScript
    ): bool
    {
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $accountFeatureClass = false;
        $gatewayExtKey = $confScript['extName'] ?? '';
        $ok = static::checkLoaded($errorMessage, $languageObj, $gatewayExtKey);
        $result = false;

        if ($ok) {
            $gatewayProxyObject =
                PaymentApi::getGatewayProxyObject(
                    static::$request,
                    $confScript
                );

            if (is_object($gatewayProxyObject)) {
                $accountFeatureClass = $gatewayProxyObject->getFeatureClass(Feature::ACCOUNT);
                if (
                    $accountFeatureClass != '' &&
                    $errorMessage == ''
                ) {
                    $parameters = [
                        &$errorMessage,
                        $confScript,
                        static::$request,
                    ];
                    $result = call_user_func_array(
                        $accountFeatureClass . '::addMainWindowJavascript',
                        $parameters
                    );
                }
            }
        }
        return $result;
    }

    static public function readStoredAccountData (
        &$errorMessage,
        array $confScript,
    ): mixed {
        if (
            !empty($confScript['login'])
        ) {
            $gatewayProxyObject =
                PaymentApi::getGatewayProxyObject(
                    static::$request,
                    $confScript
                );

            if (is_object($gatewayProxyObject)) {
                $accountFeatureClass = $gatewayProxyObject->getFeatureClass(Feature::ACCOUNT);
            }

            if (class_exists($accountFeatureClass)) {
                if (
                    method_exists($accountFeatureClass, 'init') &&
                    method_exists($accountFeatureClass, 'read')
                ) {
                    $conf = $gatewayProxyObject->getConf();
                    $geoapifyKey = $conf['geoapifyKey'] ?? '';
                    $account =
                        GeneralUtility::makeInstance(
                            $accountFeatureClass
                        );
                    $account->init(
                        $gatewayProxyObject->getGatewayObj(),
                        $confScript
                    );

                    // read the login box from the Payment Gateway
                    $result = $account->read($geoapifyKey);
                } else {
                    $labelAdress =
                        $languageObj->getLabel(
                            'feature_address_gatewayaccount'
                        );
                    $errorMessage =
                        sprintf(
                            $languageObj->getLabel(
                                'error_feature_class_interface'
                            ),
                            $accountFeatureClass,
                            $labelAdress
                        );
                }
            }
        }
        return $result;
    }


    static public function storeAccountData (
        &$errorMessage,
        Address $addressModel,
        array $confScript,
    ): bool {
        $result = false;

        if (
            !empty($confScript['login'])
        ) {
            $gatewayProxyObject =
                PaymentApi::getGatewayProxyObject(
                    static::$request,
                    $confScript
                );

            if (is_object($gatewayProxyObject)) {
                $accountFeatureClass = $gatewayProxyObject->getFeatureClass(Feature::ACCOUNT);
            }

            if (class_exists($accountFeatureClass)) {
                if (
                    method_exists($accountFeatureClass, 'store')
                ) {
                    $account =
                        GeneralUtility::makeInstance(
                            $accountFeatureClass
                        );
                    // read the login box from the Payment Gateway
                    $account->store($addressModel);
                    $result = true;
                } else {
                    $labelAdress =
                        $languageObj->getLabel(
                            'feature_address_gatewayaccount'
                        );
                    $errorMessage =
                        sprintf(
                            $languageObj->getLabel(
                                'error_feature_class_interface'
                            ),
                            $accountFeatureClass,
                            $labelAdress
                        );
                }
            }
        }
        return $result;
    }


    static public function readAccountData (
        &$errorMessage,
        Address &$addressModel,
        array $confScript,
    )
    {
        $accountFeatureClass = false;
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $gatewayExtKey = $confScript['extName'] ?? '';
        $ok = static::checkLoaded($errorMessage, $languageObj, $gatewayExtKey);
        $result = false;

        if (
            !empty($confScript['login']) &&
            $ok
        ) {
            $gatewayProxyObject =
                PaymentApi::getGatewayProxyObject(
                    static::$request,
                    $confScript
                );

            if (is_object($gatewayProxyObject)) {
                $accountFeatureClass = $gatewayProxyObject->getFeatureClass(Feature::ACCOUNT);
            } else {
                $paymentMethod = $confScript['paymentMethod'];
                $message =
                    $languageObj->getLabel(
                        'error_gateway_missing'
                    );
                $messageArray =  explode('|', $message);
                $errorMessage = $messageArray[0] . $paymentMethod . $messageArray[1];
            }

            if (
                $accountFeatureClass != ''
            ) {
                if (
                    $errorMessage == ''
                ) {

                }

                if (class_exists($accountFeatureClass)) {
                    if (
                        method_exists($accountFeatureClass, 'init') &&
                        method_exists($accountFeatureClass, 'fetch')
                    ) {
                        $account =
                            GeneralUtility::makeInstance(
                                $accountFeatureClass
                            );
                        $account->init(
                            $gatewayProxyObject->getGatewayObj(),
                            $confScript
                        );

                        // read the login box from the Payment Gateway
                        $result = $account->fetch($errorMessage, $addressModel);
                    } else {
                        $labelAdress =
                            $languageObj->getLabel(
                                'feature_address_gatewayaccount'
                            );
                        $errorMessage =
                            sprintf(
                                $languageObj->getLabel(
                                    'error_feature_class_interface'
                                ),
                                $accountFeatureClass,
                                $labelAdress
                            );
                    }
                }
            }
        }
        return $result;
    }

    /**
    * Render data entry forms for the user billing and shipping address
    */
    static public function renderLoginBox (
        &$errorMessage,
        &$accountModel,
        array $confScript,
        $extensionKey,
        array $basket,
        $orderUid,
        $orderNumber, // text string of the order number
        $currency,
        array $extraData
    ): string {
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $accountFeatureClass = false;
        $gatewayExtKey = $confScript['extName'] ?? '';
        $ok = static::checkLoaded($errorMessage, $languageObj, $gatewayExtKey);
        $result = false;
        $labelAdress =
            $languageObj->getLabel(
                'feature_address_gatewayaccount'
            );

        if (
            !empty($confScript['login']) &&
            $ok
        ) {
            $gatewayProxyObject =
                PaymentApi::getGatewayProxyObject(
                    static::$request,
                    $confScript
                );

            if (is_object($gatewayProxyObject)) {
                $accountFeatureClass = $gatewayProxyObject->getFeatureClass(Feature::ACCOUNT);
            } else {
                $paymentMethod = $confScript['paymentMethod'];
                $message =
                    $languageObj->getLabel(
                        'error_gateway_missing'
                    );
                $messageArray =  explode('|', $message);
                $errorMessage = $messageArray[0] . $paymentMethod . $messageArray[1];
            }

            if (
                $accountFeatureClass != ''
            ) {
                if (
                    $errorMessage == ''
                ) {
                    $ok = $gatewayProxyObject->transactionInit(
                        Action::AUTHORIZE_TRANSFER,
                        $confScript['paymentMethod'],
                        $extensionKey,
                        $confScript['templateFile'] ?? '',
                        $orderUid,
                        $orderNumber,
                        $currency,
                        $confScript['conf.'] ?? null,
                        $basket,
                        $extraData
                    );
                }

                if ($ok) {
                    PaymentApi::storeInit(
                        static::$request->getAttribute('frontend.user'),
                        Action::AUTHORIZE_TRANSFER,
                        $confScript['paymentMethod'],
                        $extensionKey,
                        $confScript['templateFile'] ?? '',
                        $orderUid,
                        $orderNumber,
                        $currency,
                        $confScript['conf.'] ?? null,
                        $basket,
                        $extraData
                    );

                    if (class_exists($accountFeatureClass)) {
                        if (
                            method_exists($accountFeatureClass, 'init') &&
                            method_exists($accountFeatureClass, 'render')
                        ) {
                            $account =
                                GeneralUtility::makeInstance(
                                    $accountFeatureClass
                                );
                            $account->init(
                                $gatewayProxyObject->getGatewayObj(),
                                $confScript
                            );

                            // read the login box from the Payment Gateway
                            $result =
                                $account->render(
                                    $errorMessage,
                                    $accountModel,
                                    static::$request,
                                );
                        } else {
                            $errorMessage =
                                sprintf(
                                    $languageObj->getLabel(
                                        'error_feature_class_interface'
                                    ),
                                    $accountFeatureClass,
                                    $labelAdress
                                );
                        }
                    } else {
                        $errorMessage =
                            sprintf(
                                $languageObj->getLabel(
                                    'error_feature_class'
                                ),
                                $accountFeatureClass,
                                $labelAdress
                            );
                    }
                } else {
                    $errorMessage =
                        $languageObj->getLabel(
                            'error_transaction_init'
                        );
                }
            }
        }
        return $result;
    }
}

