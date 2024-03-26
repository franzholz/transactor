TYPO3 extension transactor
==========================

The extension transactor has the purpose to enable online payments
connecting to various gateways.

Installation Requirement
------------------------

Some extensions might need the old API which has however been removed in version 0.11.0.

Since version 0.9.0 the transactor extension contains a middleware,
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


New Development
---------------

`Website Payment Standard (WPS) buttons <https://www.sandbox.paypal.com/buttons/>`_ are a very old integration of PayPal and have been removed in version 0.11.0 due to some security issues. PayPal, however, has announced a new product/service replacing the WPS buttons called `Pay Links & Buttons <https://developer.paypal.com/docs/checkout/copy-paste/>`_ .
