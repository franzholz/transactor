<?php

$emClass = '\\TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility';

if (
	class_exists($emClass) &&
	method_exists($emClass, 'extPath')
) {
	// nothing
} else {
	$emClass = 't3lib_extMgm';
}

$key = 'transactor';

$extensionPath = call_user_func($emClass . '::extPath', $key, $script);

return array(
	'tx_transactor_basket_int' => $extensionPath . 'interfaces/interface.tx_transactor_basket_int.php',
	'tx_transactor_gateway_int' => $extensionPath . 'interfaces/interface.tx_transactor_gateway_int.php',
	'tx_transactor_api' => $extensionPath . 'lib/class.tx_transactor_api.php',
	'tx_transactor_listener' => $extensionPath . 'lib/class.tx_transactor_listener.php',
	'tx_transactor_gateway' => $extensionPath . 'model/class.tx_transactor_gateway.php',
	'tx_transactor_gatewayfactory' => $extensionPath . 'model/class.tx_transactor_gatewayfactory.php',
	'tx_transactor_gatewayproxy' => $extensionPath . 'model/class.tx_transactor_gatewayproxy.php',
	'tx_transactor_language' => $extensionPath . 'model/class.tx_transactor_language.php',
	'tx_transactor_model_control' => $extensionPath . 'model/class.tx_transactor_model_control.php',
);
