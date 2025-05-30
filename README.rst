TYPO3 extension transactor
==========================

The extension transactor has the purpose to enable online payments
connecting to various gateways.

Installation Requirement
------------------------

Version 0.14.0 has a request parameter for the Api::Start class which is supported by tt_products 2.16.2+ and 3.5.2 and later.

Some extensions might need the old API which has however been removed in version 0.11.0.

The transactor extension contains a middleware,
which payment extensions can use for dealing with a gateway instant
message after the payment.

Use the transactor parameter as transactor=mygateway in the backend url
to your payment extension page.

Your extension`s ext_localconf.php file must configure the middleware in
TYPO3 10+:

::

   $GLOBALS['TYPO3_CONF_VARS']['FE']['transactor_include']['mygateway'] =  \Foo\Bar\Controller\ServerResponseController::class . '::processRequest';

Then your class ServerResponseController can receive the calls from the
instant messages from your gateway.


