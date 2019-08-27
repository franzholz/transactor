<?php

/***************************************************************
* Extension Manager/Repository config file for ext "transactor".
***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Payment Transactor API',
    'description' => 'This is a basic API to develop extensions which connect to different payment transaction gateways.',
    'category' => 'misc',
    'version' => '0.7.3',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 0,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => array(
        'depends' => array(
            'php' => '5.6.0-7.99.99',
            'typo3' => '6.2.1-8.99.99',
            'div2007' => '1.10.16-0.0.0',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
            'migration_core' => '0.0.0-0.99.99',
        ),
    ),
);

