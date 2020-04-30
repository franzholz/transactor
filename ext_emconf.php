<?php

/***************************************************************
* Extension Manager/Repository config file for ext "transactor".
***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Payment Transactor API',
    'description' => 'This is a basic API to develop extensions which connect to different payment transaction gateways.',
    'category' => 'misc',
    'version' => '0.8.1',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearcacheonload' => 0,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => array(
        'depends' => array(
            'php' => '5.6.0-7.3.99',
            'typo3' => '6.2.1-9.5.99',
            'div2007' => '1.10.20-0.0.0',
        ),
        'conflicts' => array(
        ),
    ),
);

