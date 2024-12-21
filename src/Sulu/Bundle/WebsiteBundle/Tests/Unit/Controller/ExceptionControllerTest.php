<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Controller\ExceptionController;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ExceptionControllerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var ExceptionController
     */
    private $exceptionController;

    /**
     * @var BaseExceptionController
     */
    private $innerExceptionController;

    /**
     * @var ObjectProphecy<Environment>
     */
    private $twig;

    /**
     * @var ObjectProphecy<FilesystemLoader>
     */
    private $loader;

    /**
     * @var ObjectProphecy<ParameterResolverInterface>
     */
    private $parameterResolver;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    public function setUp(): void
    {
        if (!\class_exists(BaseExceptionController::class)) {
            $this->markTestSkipped();
        }

        $this->twig = $this->prophesize(Environment::class);
        $this->loader = $this->prophesize(FilesystemLoader::class);
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

    #[\PHPUnit\Framework\Attributes\DataProvider('provideShowAction')]
    public function testShowActionFormat($retrievedFormat, $templateAvailable, $expectExceptionFormat): void
    {
        $request = new Request();
        $request->setRequestFormat($retrievedFormat);
        $exception = $this->createFlattenException(new \Exception(), 400);

        $webspace = new Webspace();
        $webspace->addTemplate('error-400', 'error400');
        $webspace->setTheme('test');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->twig->render(Argument::containingString($expectExceptionFormat), Argument::any())->shouldBeCalled();
        $this->loader->exists(Argument::any())->willReturn($templateAvailable);

        if ($templateAvailable) {
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
                    'error-404' => 'error404',
                ],
                404,
                'error404',
            ],
            [
                [
                    'error-404' => 'error404',
                    'error-500' => 'error500',
                ],
                500,
                'error500',
            ],
            [
                [
                    'error-404' => 'error404',
                    'error' => 'error',
                ],
                400,
                'error',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideShowActionErrorTemplate')]
    public function testShowActionErrorTemplate($templates, $errorCode, $expectedTemplate): void
    {
        $request = new Request();
        $exception = $this->createFlattenException(new \Exception(), $errorCode);

        $webspace = new Webspace();
        foreach ($templates as $type => $template) {
            $webspace->addTemplate($type, $template);
        }
        $webspace->setTheme('test');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->twig->render($expectedTemplate . '.html.twig', Argument::any())->shouldBeCalled();
        $this->loader->exists(Argument::any())->willReturn(true);

        $this->parameterResolver->resolve(Argument::cetera())->willReturn([]);

        // Required to leave one ob_level left, test will be marked otherwise as risky by PHPUnit
        $request->headers->add(['X-Php-Ob-Level' => 1]);

        $this->exceptionController->showAction($request, $exception);
    }

    private function createFlattenException($exception, $statusCode)
    {
        return FlattenException::createFromThrowable($exception, $statusCode);
    }
}
