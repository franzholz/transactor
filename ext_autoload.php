<?php

$key = 'transactor';

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($key, $script);

return array(
    'tx_transactor_gateway_int' => $extensionPath . 'interfaces/interface.tx_transactor_gateway_int.php',
    'tx_transactor_api' => $extensionPath . 'lib/class.tx_transactor_api.php',
    'tx_transactor_listener' => $extensionPath . 'lib/class.tx_transactor_listener.php',
    'tx_transactor_gateway' => $extensionPath . 'model/class.tx_transactor_gateway.php',
    'tx_transactor_gatewayfactory' => $extensionPath . 'model/class.tx_transactor_gatewayfactory.php',
    'tx_transactor_gatewayproxy' => $extensionPath . 'model/class.tx_transactor_gatewayproxy.php',
    'tx_transactor_language' => $extensionPath . 'model/class.tx_transactor_language.php',
    'tx_transactor_model_control' => $extensionPath . 'model/class.tx_transactor_model_control.php',
);

