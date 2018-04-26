<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Controller\ExceptionController;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;

class ExceptionControllerTest extends TestCase
{
    /**
     * @var ExceptionController
     */
    private $exceptionController;

    /**
     * @var BaseExceptionController
     */
    private $innerExceptionController;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var \Twig_ExistsLoaderInterface
     */
    private $loader;

    /**
     * @var ParameterResolverInterface
     */
    private $parameterResolver;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function setUp()
    {
        $this->twig = $this->prophesize(\Twig_Environment::class);
        $this->loader = $this->prophesize(\Twig_ExistsLoaderInterface::class);
        $this->twig->getLoader()->willReturn($this->loader->reveal());

        $this->parameterResolver = $this->prophesize(ParameterResolverInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->innerExceptionController = new BaseExceptionController($this->twig->reveal(), false);
        $this->exceptionController = new ExceptionController(
            $this->innerExceptionController, $this->requestAnalyzer->reveal(),
            $this->parameterResolver->reveal(), $this->twig->reveal(), false
        );
    }

    public static function provideShowAction()
    {
        return [
            ['html', true, 'html'],
            ['xml', true, 'xml'],
            ['json', true, 'json'],
            ['aspx', false, 'html'],
        ];
    }

    /**
     * @dataProvider provideShowAction
     */
    public function testShowActionFormat($retrievedFormat, $templateAvailable, $expectExceptionFormat)
    {
        $request = new Request();
        $request->setRequestFormat($retrievedFormat);
        $exception = FlattenException::create(new \Exception(), 400);

        $webspace = new Webspace();
        $webspace->addTemplate('error-400', 'error400.html.twig');
        $webspace->setTheme('test');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->twig->render(Argument::containingString($expectExceptionFormat), Argument::any())->shouldBeCalled();
        $this->loader->exists(Argument::any())->willReturn($templateAvailable);

        if ('html' === $retrievedFormat) {
            $this->parameterResolver->resolve(Argument::cetera())->shouldBeCalled()->willReturn([]);
        } else {
            $this->parameterResolver->resolve(Argument::cetera())->shouldNotBeCalled();
        }

        // Required to leave one ob_level left, test will be marked otherwise as risky by PHPUnit
        $request->headers->add(['X-Php-Ob-Level' => 1]);

        $this->exceptionController->showAction($request, $exception);
    }

    public static function provideShowActionErrorTemplate()
    {
        return [
            [
                [
                    'error-404' => 'error404.html.twig',
                ],
                404,
                'error404.html.twig',
            ],
            [
                [
                    'error-404' => 'error404.html.twig',
                    'error-500' => 'error500.html.twig',
                ],
                500,
                'error500.html.twig',
            ],
            [
                [
                    'error-404' => 'error404.html.twig',
                    'error' => 'error.html.twig',
                ],
                400,
                'error.html.twig',
            ],
        ];
    }

    /**
     * @dataProvider provideShowActionErrorTemplate
     */
    public function testShowActionErrorTemplate($templates, $errorCode, $expectedTemplate)
    {
        $request = new Request();
        $exception = FlattenException::create(new \Exception(), $errorCode);

        $webspace = new Webspace();
        foreach ($templates as $type => $template) {
            $webspace->addTemplate($type, $template);
        }
        $webspace->setTheme('test');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->twig->render($expectedTemplate, Argument::any())->shouldBeCalled();
        $this->loader->exists(Argument::any())->willReturn(true);

        $this->parameterResolver->resolve(Argument::cetera())->willReturn([]);

        // Required to leave one ob_level left, test will be marked otherwise as risky by PHPUnit
        $request->headers->add(['X-Php-Ob-Level' => 1]);

        $this->exceptionController->showAction($request, $exception);
    }
}
