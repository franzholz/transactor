The extension transactor has the purpose to enable online payments connecting to various gateways.

You must configure it in the Extension Configuration of the Settings backend module.
Set the compatibility mode (basic.compatibility) to 0 for the new API.
Older extension versions might need the old API and a 1 here.

The old API is deprecated and will be removed in 2024.

Since version 0.9.0 the transactor extension contains a middleware, which payment extensions can use for dealing with a gateway instant message after the payment.

Use the transactor parameter as
transactor=mygateway
in the backend url to your payment extension page.

Your extension`s ext_localconf.php file must configure the middleware in TYPO3 10+:

    $GLOBALS['TYPO3_CONF_VARS']['FE']['transactor_include']['mygateway'] =  \Foo\Bar\Controller\ServerResponseController::class . '::processRequest';

Then your class ServerResponseController can receive the calls from the instant messages from your gateway.


