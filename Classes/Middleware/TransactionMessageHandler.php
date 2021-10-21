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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $eID = $request->getParsedBody()['eID'] ?? $request->getQueryParams()['eID'] ?? null;
        $transactor = $request->getParsedBody()['transactor'] ?? $request->getQueryParams()['transactor'] ?? null;

        // Do not use any more eID for Transactor!
        if ($eID != null || $transactor === null) {
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
            return $response->withStatus(404, 'transactor has not been registered for ' . $transactor . '!');
        }
        $configuration = $GLOBALS['TYPO3_CONF_VARS']['FE']['transactor_include'][$transactor];

        // Simple check to make sure that it is not an absolute file (to use the fallback)
        if (strpos($configuration, '::') !== false || is_callable($configuration)) {
            if (
                defined('TYPO3_version') &&
                version_compare(TYPO3_version, '10.4.0', '>=')
            ) {
                $container = GeneralUtility::getContainer();
                /** @var Dispatcher $dispatcher */
                $dispatcher = GeneralUtility::makeInstance(Dispatcher::class, $container);
            } else {
                /** @var Dispatcher $dispatcher */
                $dispatcher = GeneralUtility::makeInstance(Dispatcher::class);
            }
            $request = $request->withAttribute('target', $configuration);
            return $dispatcher->dispatch($request, $response) ?? new NullResponse();
        }
        trigger_error(
            'transactor "' . $transactor . '" is registered with a script to the file "' . GeneralUtility::getFileAbsFileName($configuration) . '".'
            . ' Register transactor with a class::method syntax like "\MyVendor\MyExtension\Controller\MyTransactorController::myMethod" instead.',
            E_USER_ERROR
        );

        return new NullResponse();
    }
}
