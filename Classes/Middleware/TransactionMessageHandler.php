<?php

declare(strict_types = 1);

namespace JambageCom\Transactor\Middleware;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Dispatcher;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Transactor\SessionHandler\SessionHandler;


/**
 *
 * @internal
 */
class TransactionMessageHandler implements MiddlewareInterface
{
    /**
     * Dispatches the response message from the server
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $version = $typo3Version->getVersion();
        $queryParams = $request->getQueryParams();
        $postParams = $request->getParsedBody();
        $eID = $request->getParsedBody()['eID'] ?? $request->getQueryParams()['eID'] ?? null;
        $transactor = $request->getParsedBody()['transactor'] ?? $queryParams['transactor'] ?? null;

        $frontendUser = $request->getAttribute('frontend.user');
        // Keep this line: Initialization for the session handler!
        $sessionHandler = GeneralUtility::makeInstance(SessionHandler::class, $frontendUser);

        // Do not use any more eID for Transactor!
        if ($transactor === null || $eID != null) {
            return $handler->handle($request);
        }

        $pageId = \JambageCom\Div2007\Utility\FrontendUtility::getPageId($request);
        if ($pageId) {
            $_REQUEST['id'] = $_GET['id'] = $pageId;
        }

        // Remove any output produced until now
        ob_clean();

        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        if (!isset($GLOBALS['TYPO3_CONF_VARS']['FE']['transactor_include'][$transactor])) {
            return $response->withStatus(404, 'Transactor has not been registered for ' . $transactor . '!');
        }

        $configuration = $GLOBALS['TYPO3_CONF_VARS']['FE']['transactor_include'][$transactor];

        // Simple check to make sure that it is not an absolute file (to use the fallback)
        // Check if the $configuration is a concatenated string of "className::actionMethod"
        if (
            is_string($configuration) &&
            (
                strpos($configuration, '::') !== false ||
                is_callable($configuration)
            )
        ) {
            $container = GeneralUtility::getContainer();
            /** @var Dispatcher $dispatcher */
            $dispatcher = GeneralUtility::makeInstance(Dispatcher::class, $container);
            $request = $request->withAttribute('target', $configuration);
            $response = $dispatcher->dispatch($request) ?? new NullResponse();
            return $response;
        }
        trigger_error(
            'transactor "' . $transactor . '" is registered with a script to the file "' . GeneralUtility::getFileAbsFileName($configuration) . '".'
            . ' Register your extension for transactor with a class::method syntax like "\MyVendor\MyExtension\Controller\MyTransactorController::myMethod" instead!',
            E_USER_ERROR
        );

        return new NullResponse();
    }
}
