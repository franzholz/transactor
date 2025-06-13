<?php

/***************************************************************
* Extension Manager/Repository config file for ext "transactor".
***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Payment Transactor API',
    'description' => 'This is a basic API to develop extensions which connect to different payment transaction gateways.',
    'category' => 'misc',
    'version' => '0.14.2',
    'state' => 'stable',
    'clearcacheonload' => 0,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '8.0.0-8.4.99',
            'typo3' => '12.4.0-13.4.99',
            'div2007' => '2.3.0-2.3.99',
        ],
        'conflicts' => [
        ],
		'suggests' => [
            'typo3db_legacy' => '1.0.0-1.1.99',
		]
    ],
];

