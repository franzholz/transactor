<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "transactor".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Payment Transactor API',
	'description' => 'This is a basic API to develop extensions which connect to different payment transaction gateways.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.4.2',
	'dependencies' => 'div2007',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
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
			'php' => '5.3.0-7.99.99',
			'typo3' => '4.5.0-7.99.99',
			'div2007' => '1.0.3-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:52:{s:9:"Changelog";s:4:"b9d8";s:16:"ext_autoload.php";s:4:"81f8";s:21:"ext_conf_template.txt";s:4:"5238";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"4ec5";s:14:"ext_tables.php";s:4:"59c7";s:14:"ext_tables.sql";s:4:"274a";s:13:"locallang.xml";s:4:"00af";s:16:"locallang_db.xml";s:4:"8622";s:10:"README.txt";s:4:"3887";s:7:"tca.php";s:4:"4ef4";s:8:"TODO.txt";s:4:"deaf";s:14:"doc/manual.sxw";s:4:"e756";s:49:"interfaces/interface.tx_transactor_basket_int.php";s:4:"899c";s:50:"interfaces/interface.tx_transactor_gateway_int.php";s:4:"4e85";s:31:"lib/class.tx_transactor_api.php";s:4:"6d76";s:17:"lib/locallang.xml";s:4:"56d9";s:37:"model/class.tx_transactor_gateway.php";s:4:"5ff0";s:44:"model/class.tx_transactor_gatewayfactory.php";s:4:"d4f5";s:42:"model/class.tx_transactor_gatewayproxy.php";s:4:"e86e";s:38:"model/class.tx_transactor_language.php";s:4:"6de3";s:43:"model/class.tx_transactor_model_control.php";s:4:"fdf6";s:25:"res/icons/aktia_large.gif";s:4:"0031";s:24:"res/icons/amex_large.gif";s:4:"e426";s:26:"res/icons/BankAxess_03.gif";s:4:"3578";s:24:"res/icons/card6_l_03.gif";s:4:"42c7";s:24:"res/icons/card8_l_03.gif";s:4:"4e0c";s:25:"res/icons/dan53-35_01.gif";s:4:"b318";s:17:"res/icons/ddb.gif";s:4:"cdc9";s:23:"res/icons/edk_large.gif";s:4:"781f";s:23:"res/icons/elec_stor.gif";s:4:"c000";s:18:"res/icons/eolv.gif";s:4:"3419";s:23:"res/icons/ewi_large.gif";s:4:"0065";s:22:"res/icons/fk_large.gif";s:4:"8e7e";s:30:"res/icons/getitcard2_53x33.gif";s:4:"b70e";s:30:"res/icons/handelsbanken_01.gif";s:4:"0aa7";s:25:"res/icons/ideal_large.jpg";s:4:"b04d";s:26:"res/icons/jcb-large_01.gif";s:4:"3b57";s:27:"res/icons/jsecure_large.gif";s:4:"1c68";s:21:"res/icons/mc_3d_l.gif";s:4:"7307";s:25:"res/icons/mc_large_01.gif";s:4:"093e";s:22:"res/icons/mstro_01.gif";s:4:"fd6c";s:26:"res/icons/nordea_large.gif";s:4:"951d";s:23:"res/icons/oko_large.gif";s:4:"3b69";s:25:"res/icons/sampo_large.gif";s:4:"6227";s:20:"res/icons/seb_02.gif";s:4:"58c6";s:22:"res/icons/swedbank.gif";s:4:"d6da";s:19:"res/icons/valus.gif";s:4:"6add";s:29:"res/icons/ver_visa2_large.gif";s:4:"5171";s:29:"res/icons/ver_visa_3d_l_1.gif";s:4:"3044";s:23:"res/icons/visa_stor.gif";s:4:"58a5";s:24:"template/transactor.tmpl";s:4:"3fbf";}',
	'suggests' => array(
	),
);

