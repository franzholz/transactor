<?php

/***************************************************************
* Extension Manager/Repository config file for ext "transactor".
***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Payment Transactor API',
    'description' => 'This is a basic API to develop extensions which connect to different payment transaction gateways.',
    'category' => 'misc',
    'version' => '0.10.1',
    'state' => 'stable',
    'clearcacheonload' => 0,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.4.99',
            'typo3' => '10.4.0-12.4.99',
            'div2007' => '1.14.0-0.0.0',
        ],
        'conflicts' => [
        ],
		'suggests' => [
            'typo3db_legacy' => '1.0.0-1.1.99',
		]
    ],
];

