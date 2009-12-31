<?php

########################################################################
# Extension Manager/Repository config file for ext: "transactor"
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Payment Transactor API',
	'description' => 'This is a basic API to develop extensions which connect to different payment transaction gateways.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.0.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'div2007' => '0.2.1-',
			'php' => '5.0.4-0.0.0',
			'typo3' => '3.8.2-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:18:{s:9:"Changelog";s:4:"1d6a";s:10:"README.txt";s:4:"18e4";s:8:"TODO.txt";s:4:"deaf";s:32:"class.tx_paymentlib_tceforms.php";s:4:"7666";s:21:"ext_conf_template.txt";s:4:"7494";s:12:"ext_icon.gif";s:4:"1bdc";s:14:"ext_tables.php";s:4:"0fea";s:14:"ext_tables.sql";s:4:"36cd";s:13:"locallang.php";s:4:"b58a";s:16:"locallang_db.php";s:4:"24c3";s:7:"tca.php";s:4:"d851";s:36:"lib/class.tx_paymentlib_provider.php";s:4:"cfd2";s:43:"lib/class.tx_paymentlib_providerfactory.php";s:4:"f117";s:41:"lib/class.tx_paymentlib_providerproxy.php";s:4:"fb2f";s:21:"res/icons/DIBS.tar.gz";s:4:"d44a";s:48:"tests/tx_paymentlib_providerfactory_testcase.php";s:4:"f0b6";s:46:"tests/tx_paymentlib_providerproxy_testcase.php";s:4:"66d2";s:49:"tests/fixtures/tx_paymentlib_provider_fixture.php";s:4:"c6ec";}',
);

?>