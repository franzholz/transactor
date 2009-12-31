1. INTRO
--------------------------------------------------------------------------------
This is the Typo3 extension paymentLib. With this extension you can enable
online payments in your own extension using a number of different payment
gateways.

The aim is to provide an unified way of handling online payments in Typo3
extensions - extended by an unlimited number payment methods (payment gaways).

PaymentLib was orignially created by Robert Lemke. Since June 2006 the work has
been continued by Tonni Aagesen.



2. QUICK GUIDE TO IMPLEMENTATION IN YOUR EXTENSION
--------------------------------------------------------------------------------
If not already done, download paymentlib from the Extension Manager or
http://typo3.org and one or more payment methods. Setup the extensions as
needed.

The following is an example of the needed steps to setup online payment using
HTML Form (not webservice):


2.a. Include paymentlib in your extension:

----- code snip begin -----
if (t3lib_extMgm::isLoaded ('paymentlib')) {
	require_once(t3lib_extMgm::extPath('paymentlib').'lib/class.tx_paymentlib_providerfactory.php');
}
------ code snip end ------


2.b. Create an instance of provider factory:

----- code snip begin -----
$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
$providerObjectsArr = $providerFactoryObj->getProviderObjects();
if (is_array ($providerObjectsArr)) {
	// continue with step '2.c' and '2.d'
} else {
	// Else no payment methods available
}
------ code snip end ------


2.c. Get all available payment methods:

----- code snip begin -----
foreach ($providerObjectsArr as $providerObj) {
	$tmpArr = $providerObj->getAvailablePaymentMethods();
	$paymentMethodsArr = t3lib_div::array_merge_recursive_overrule($paymentMethodsArr, $tmpArr, 0, 1);
}
------ code snip end ------


2.d. Get allowed payment methods

----- code snip begin -----
foreach ($paymentMethodsArr as $paymentMethodKey => $paymentMethodConf) {
	// In this example, we have defined allowed methods in $this->conf['paymentmethods']
	if (t3lib_div::inList ($this->conf['paymentmethods'], $paymentMethodKey)) {
		// Render a selection of payment methods. Store the selection in eg. a session etc.
	}
}
------ code snip end ------


2.e. Initialize transaction based on selected method

----- code snip begin -----
$selectedPaymentMethod = $sessionData['paymentmethod']; // Selection stored in '2.d.'
$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
$providerObj = $providerFactoryObj->getProviderObjectByPaymentMethod($selectedPaymentMethod);
$ok = $providerObj->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER, $selectedPaymentMethod, TX_PAYMENTLIB_GATEWAYMODE_FORM, 'rlmp_eventdb');
if (!$ok) return 'ERROR: Could not initialize transaction.';
------ code snip end ------


2.f. Set payment details

----- code snip begin -----
$transactionDetailsArr = array (
	'transaction' => array (
		'amount' => $totalPrice,
		'currency' => $parentObj->currency,
	),
	'options' => array (
		'reference' => $sessionData['submissionId'],
	),
);
$ok = $providerObj->transaction_setDetails ($transactionDetailsArr);
if (!$ok) return 'ERROR: Setting details of transaction failed.';
------ code snip end ------

2.g. Check if payment is already paid

----- code snip begin -----
$transactionResultsArr = $providerObj->transaction_getResults();
if (is_array ($transactionResultsArr)) {
	if ($transactionResultsArr['state'] == 1) {
		// tell user that payment was succesful and let them continue if nessecary
	} else {
		// tell user that payment was error and let them try again if nessecary
	}
}
------ code snip end ------

2.h. If no payment detected in step '2.g.', present user with option to pay

----- code snip begin -----
$formAction = $providerObj->transaction_formGetActionURI();
$hiddenFields = '';
$hiddenFieldsArr = $providerObj->transaction_formGetHiddenFields();
foreach ($hiddenFieldsArr as $key => $value) {
	$hiddenFields .= '<input name='.$key.' type="hidden" value="'.htmlspecialchars($value).'" />'.chr(10);
}
$form = '<form method="post" action="'.$formAction.'">'.$hiddenFields.'<input type="submit" value="Pay!" /></form>';
------ code snip end ------

And we are done :)
Inspect the database table 'tx_paymentlib_transactions' to see if your
transaction is registered.



3. CREATING A PAYMENT METHOD
--------------------------------------------------------------------------------
For consistancy please name your extension paymentlib_$myPaymentGateway A few
examples: paymentlib_quickpay, paymentlib_ipayment etc.



4. DATABASE
--------------------------------------------------------------------------------
A database table 'tx_paymentlib_transactions' is created upon installation of
paymentLib. Each record in this table holds information about a transaction.


[field:uid]
The record uniuqe id

[field:pid]
The page id

[field:crdate]
The creation date as unix time

[field:gatewayid]
The payment gateway's unique reference to a transaction (usually named
'transaction id').

[field:orderid]
A unique reference to a transaction by your own choise. This is likely to be a
sequential number (usually named 'order id', 'order number', 'shopper id' etc.).

[field:currency]
A three letter ISO 4217 Type Currency Code (see http://www.xe.com/iso4217.htm)

[field:amount]
The amount of the transaction in the smallest unit (eg. $1 is written '100')

[field:state]
The state of the transaction. The values are defined as constants
in 'TX_PAYMENTLIB_TRANSACTION_STATE_*' class.tx_paymentlib_provider.php

[field:state_time]
The time of the transaction as unix time

[field:ext_key]
The extension key of the extension utilizing paymentlib

[field:paymethod_key]
The payment method used by paymentlib (eg. paymentlib_quickpay, paymentlib_ipayment etc.)

[field:paymethod_method]
The payment method used by paymentlib (eg. paymentlib_offline_giro, paymentlib_quickpay_cc_visa etc.)

[field:user]
Any user defined values that is relevant for the transaction. Can be stored as a
serialized array - Normally an array like:
$user = Array(
		'gateway' => Array (
			// Gateway specific values
		),
		'extras' => Array (
			// Any extra information like customer data etc.
	),
);


5. FUTURE WORK
--------------------------------------------------------------------------------
See TODO.txt :)