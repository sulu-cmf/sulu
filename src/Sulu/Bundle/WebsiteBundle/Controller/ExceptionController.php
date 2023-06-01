<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Twig\Environment;

/**
 * Custom exception controller.
 *
 * @deprecated the "ExceptionController" is deprecated use the "ErrorController" instead
 */
class ExceptionController
{
    /**
     * @var BaseExceptionController
     */
    private $exceptionController;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ParameterResolverInterface
     */
    private $parameterResolver;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param bool $debug
     */
    public function __construct(
        BaseExceptionController $exceptionController,
        RequestAnalyzerInterface $requestAnalyzer,
        ParameterResolverInterface $parameterResolver,
        Environment $twig,
        $debug
    ) {
        @trigger_deprecation('sulu/sulu', '2.0', __CLASS__ . ' is deprecated and will be removed in 3.0. Use the ErrorController instead.');

        $this->exceptionController = $exceptionController;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->parameterResolver = $parameterResolver;
        $this->twig = $twig;
        $this->debug = $debug;
    }

    /**
     * {@see BaseExceptionController::showAction()}.
     *
     * @param FlattenException $exception
     */
    public function showAction(
        Request $request,
        $exception,
        ?DebugLoggerInterface $logger = null
    ) {
        $code = $exception->getStatusCode();
        $template = null;
        if ($webspace = $this->requestAnalyzer->getWebspace()) {
            $template = $webspace->getTemplate('error-' . $code, $request->getRequestFormat());

            if (null === $template) {
                $template = $webspace->getTemplate('error', $request->getRequestFormat());
            }
        }

        $showException = $request->attributes->get('showException', $this->debug);
        if ($showException || null === $template || !$this->twig->getLoader()->exists($template)) {
            return $this->exceptionController->showAction($request, $exception, $logger);
        }

        $context = $this->parameterResolver->resolve(
            [
                'status_code' => $code,
                'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception' => $exception,
                'currentContent' => $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1)),
            ],
            $this->requestAnalyzer
        );

        return new Response(
            $this->twig->render(
                $template,
                $context
            ),
            $code
        );
    }

    /**
     * Returns and cleans output-buffer.
     *
     * @param int $startObLevel
     *
     * @return string
     */
    protected function getAndCleanOutputBuffering($startObLevel)
    {
        if (\ob_get_level() <= $startObLevel) {
            return '';
        }

        Response::closeOutputBuffers($startObLevel + 1, true);

        return \ob_get_clean();
    }
}
