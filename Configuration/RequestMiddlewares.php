<?php

return [
    'frontend' => [
        'jambagecom/transactor/preprocessing' => [
            'target' => \JambageCom\Transactor\Middleware\TransactionMessageHandler::class,
            'description' => 'The payment gateways used to pay the bills may send a payment message. This will especially happen in a case when a user closes the browser window immediately after he paid the bill on the server of the payment organization. So he did not return into the shop. In this case the finalization of the order must be done on the TYPO3 website with a hidden frontend.',
            'after' => [
                'typo3/cms-frontend/tsfe'
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ]
    ]
];

