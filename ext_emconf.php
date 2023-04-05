<?php

/***************************************************************
* Extension Manager/Repository config file for ext "transactor".
***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Payment Transactor API',
    'description' => 'This is a basic API to develop extensions which connect to different payment transaction gateways.',
    'category' => 'misc',
    'version' => '0.10.0',
    'state' => 'stable',
    'clearcacheonload' => 0,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.1.99',
            'typo3' => '8.7.0-11.5.99',
            'div2007' => '1.12.0-0.0.0',
        ],
        'conflicts' => [
        ],
		'suggests' => [
            'typo3db_legacy' => '1.0.0-1.1.99',
		]
    ],
];

