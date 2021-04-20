<?php

/***************************************************************
* Extension Manager/Repository config file for ext "transactor".
***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Payment Transactor API',
    'description' => 'This is a basic API to develop extensions which connect to different payment transaction gateways.',
    'category' => 'misc',
    'version' => '0.9.0',
    'state' => 'stable',
    'clearcacheonload' => 0,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => array(
        'depends' => array(
            'php' => '7.1.0-7.4.99',
            'typo3' => '7.6.0-10.4.99',
            'div2007' => '1.11.0-0.0.0',
        ),
        'conflicts' => array(
        ),
		'suggests' => array(
            'typo3db_legacy' => '1.0.0-1.1.99',
		)
    ),
);

