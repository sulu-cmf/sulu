<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\EventListener\TargetGroupSubscriber;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

class TargetGroupSubscriberTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<Environment>
     */
    private $twig;

    /**
     * @var ObjectProphecy<TargetGroupStoreInterface>
     */
    private $targetGroupStore;

    /**
     * @var ObjectProphecy<TargetGroupEvaluatorInterface>
     */
    private $targetGroupEvaluator;

    /**
     * @var ObjectProphecy<TargetGroupRepositoryInterface>
     */
    private $targetGroupRepository;

    /**
     * @var ObjectProphecy<HttpKernelInterface>
     */
    private $kernel;

    public function setUp(): void
    {
        $this->kernel = $this->prophesize(HttpKernelInterface::class);
        $this->twig = $this->prophesize(Environment::class);
        $this->targetGroupStore = $this->prophesize(TargetGroupStoreInterface::class);
        $this->targetGroupEvaluator = $this->prophesize(TargetGroupEvaluatorInterface::class);
        $this->targetGroupRepository = $this->prophesize(TargetGroupRepositoryInterface::class);
    }

    public function testSetTargetGroupWithHeaderAndCookie(): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = new Request();
        $request->headers->set('X-Sulu-Target-Group', '1');
        $request->cookies->set('sulu-visitor-target-group', '2');

        $event = $this->createRequestEvent($request);

        $this->targetGroupStore->setTargetGroupId('1')->shouldBeCalled();
        $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();

        $targetGroupSubscriber->setTargetGroup($event);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSetTargetGroupFromHeader')]
    public function testSetTargetGroupFromHeader(
        $targetGroupHeader,
        $headerTargetGroup,
        $result
    ): void {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            $targetGroupHeader,
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = new Request();

        if ($headerTargetGroup) {
            $request->headers->set($targetGroupHeader, $headerTargetGroup);
        }

        $event = $this->createRequestEvent($request);

        $this->targetGroupStore->setTargetGroupId($result)->shouldBeCalled();
        $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();

        $targetGroupSubscriber->setTargetGroup($event);
    }

    public static function provideSetTargetGroupFromHeader()
    {
        return [
            ['X-Sulu-Target-Group', '1', '1'],
            ['X-Target-Group', '2', '2'],
            ['X-Sulu-Target-Group', '1', '1'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSetTargetGroupFromCookie')]
    public function testSetTargetGroupFromCookie(
        $targetGroupCookie,
        $visitorSessionCookie,
        $cookieTargetGroup,
        $cookieVisitorSession,
        $evaluationResult,
        $result,
        $resultUpdate
    ): void {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            $targetGroupCookie,
            $visitorSessionCookie
        );

        $request = new Request();

        if ($cookieTargetGroup) {
            $request->cookies->set($targetGroupCookie, $cookieTargetGroup);
        }

        if ($cookieVisitorSession) {
            $request->cookies->set($visitorSessionCookie, $cookieVisitorSession);
        }

        if ($evaluationResult) {
            $targetGroup = $this->prophesize(TargetGroupInterface::class);
            $targetGroup->getId()->willReturn($evaluationResult);
            $this->targetGroupRepository->find($cookieTargetGroup)->willReturn($targetGroup->reveal());
            $this->targetGroupEvaluator->evaluate(TargetGroupRuleInterface::FREQUENCY_SESSION, $targetGroup->reveal())
                ->willReturn($targetGroup->reveal());
        }

        $event = $this->createRequestEvent($request);

        if ($resultUpdate) {
            $this->targetGroupStore->setTargetGroupId(Argument::any())->shouldNotBeCalled();
            $this->targetGroupStore->updateTargetGroupId($result)->shouldBeCalled();
        } else {
            $this->targetGroupStore->setTargetGroupId($result)->shouldBeCalled();
            $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();
        }

        $targetGroupSubscriber->setTargetGroup($event);
    }

    public static function provideSetTargetGroupFromCookie()
    {
        return [
            ['sulu-visitor-target-group', 'visitor-session', '1', true, null, '1', false],
            ['target-group', 'session', '3', true, null, '3', false],
            ['sulu-visitor-target-group', 'visitor-session', '1', null, '2', '2', true],
            ['sulu-visitor-target-group', 'visitor-session', '1', true, '2', '1', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSetTargetGroupFromEvaluation')]
    public function testSetTargetGroupFromEvaluation($evaluatedTargetGroup, $result): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = new Request();
        $event = $this->createRequestEvent($request);

        $this->targetGroupEvaluator->evaluate()->willReturn($evaluatedTargetGroup);

        $this->targetGroupStore->setTargetGroupId(Argument::any())->shouldNotBeCalled();
        $this->targetGroupStore->updateTargetGroupId($result)->shouldBeCalled();

        $targetGroupSubscriber->setTargetGroup($event);

        $this->assertCount(0, $request->headers->all());
    }

    public static function provideSetTargetGroupFromEvaluation()
    {
        $targetGroup1 = new TargetGroup();
        self::setPrivateProperty($targetGroup1, 'id', 1);

        $targetGroup2 = new TargetGroup();
        self::setPrivateProperty($targetGroup2, 'id', 3);

        return [
            [$targetGroup1, 1],
            [$targetGroup2, 3],
            [null, 0],
        ];
    }

    public function testSetTargetGroupFromEvaluationOnTargetHitUrl(): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = Request::create('/_target_group');
        $event = $this->createRequestEvent($request);

        $this->targetGroupEvaluator->evaluate()->shouldNotBeCalled();

        $this->targetGroupStore->setTargetGroupId(Argument::any())->shouldNotBeCalled();
        $this->targetGroupStore->updateTargetGroupId(Argument::any())->shouldNotBeCalled();

        $targetGroupSubscriber->setTargetGroup($event);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAddVaryHeader')]
    public function testAddVaryHeader($targetGroupUrl, $requestUrl, $hasInfluencedContent, $header, $varyHeaders): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            $targetGroupUrl,
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            $header,
            'sulu-visitor-target-group',
            'visitor-session'
        );
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $requestUrl]);
        $response = new Response();

        $event = $this->createResponseEvent($request, $response);
        $this->targetGroupStore->hasInfluencedContent()->willReturn($hasInfluencedContent);

        $targetGroupSubscriber->addVaryHeader($event);

        $this->assertEquals($varyHeaders, $response->getVary());
    }

    /**
     * @return iterable<array{
     *     0: string,
     *     1: string,
     *     2: bool,
     *     3: string,
     *     4: string[],
     * }>
     */
    public static function provideAddVaryHeader()
    {
        return [
            ['/_target_group', '/test', true, 'X-Sulu-Target-Group-Hash', ['X-Sulu-Target-Group-Hash']],
            ['/_target_group', '/test', true, 'X-Sulu-Target-Group', ['X-Sulu-Target-Group']],
            ['/_target_group', '/test', false, 'X-Sulu-Target-Group', []],
            ['/_target_group', '/_target_group', true, 'X-Sulu-Target-Group-Hash', []],
            ['/_visitor', '/_visitor', true, 'X-Sulu-Target-Group-Hash', []],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAddSetCookieHeader')]
    public function testAddSetCookieHeader(string $targetGroupCookie, string $visitorSession, bool $hasChanged, string $url, ?int $cookieValue): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-URL',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            $targetGroupCookie,
            $visitorSession
        );

        $this->targetGroupStore->hasChangedTargetGroup()->willReturn($hasChanged);
        $this->targetGroupStore->getTargetGroupId(true)->willReturn($cookieValue);

        $request = Request::create($url);
        $response = new Response();
        $event = $this->createResponseEvent($request, $response);

        $targetGroupSubscriber->addSetCookieHeader($event);

        if ($cookieValue) {
            $targetGroupResponseCookie = $response->headers->getCookies()[0];
            $this->assertEquals($targetGroupCookie, $targetGroupResponseCookie->getName());
            $this->assertEquals($cookieValue, $targetGroupResponseCookie->getValue());
            $visitorSessionResponseCookie = $response->headers->getCookies()[1];
            $this->assertEquals($visitorSession, $visitorSessionResponseCookie->getName());
            $this->assertEquals(0, $visitorSessionResponseCookie->getExpiresTime());
        } else {
            $this->assertCount(0, $response->headers->getCookies());
        }
    }

    /**
     * @return iterable<array{
     *     0: string,
     *     1: string,
     *     2: bool,
     *     3: string,
     *     4: int|null,
     * }>
     */
    public static function provideAddSetCookieHeader(): iterable
    {
        return [
            ['sulu-visitor-target-group', 'visitor-session', false, '/_target_group_hit', null],
            ['target-group', 'session', true, '/_target_group_hit', 1],
            ['sulu-visitor-target-group', 'visitor-session', true, '/_tgh', 2],
            ['sulu-visitor-target-group', 'visitor-session', true, '/_target_group', null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAddTargetGroupHitScript')]
    public function testAddTargetGroupHitScript(
        string $targetGroupHitUrl,
        string $forwardedUrlHeader,
        string $forwardedRefererHeader,
        string $forwardedUuidHeader,
        ?string $uuid
    ): void {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            $targetGroupHitUrl,
            $forwardedUrlHeader,
            $forwardedRefererHeader,
            $forwardedUuidHeader,
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );
        $request = new Request();
        if (null !== $uuid) {
            $structureBridge = $this->prophesize(StructureBridge::class);
            $structureBridge->getUuid()->willReturn($uuid);
            $request->attributes->set('structure', $structureBridge->reveal());
        }

        $response = new Response('<body></body>');
        $response->headers->set('Content-Type', 'text/html');

        $event = $this->createResponseEvent($request, $response);

        $this->twig->render('@SuluAudienceTargeting/Template/hit-script.html.twig', [
            'url' => $targetGroupHitUrl,
            'urlHeader' => $forwardedUrlHeader,
            'refererHeader' => $forwardedRefererHeader,
            'uuidHeader' => $forwardedUuidHeader,
            'uuid' => $uuid,
        ])->willReturn('<script></script>');

        $targetGroupSubscriber->addTargetGroupHitScript($event);

        $this->assertEquals('<body><script></script></body>', $response->getContent());
    }

    /**
     * @return iterable<array{
     *     0: string,
     *     1: string,
     *     2: string,
     *     3: string,
     *     4: string|null,
     * }>
     */
    public static function provideAddTargetGroupHitScript(): iterable
    {
        return [
            ['/_target_group_hit', 'X-Forwarded-URL', 'X-Fowarded-Referer', 'X-Forwarded-UUID', 'some-uuid'],
            ['/_target_group_hit', 'X-Forwarded-URL', 'X-Fowarded-Referer', 'X-Forwarded-UUID', null],
            ['/_group_hit', 'X-Other-URL', 'X-Other-Referer', 'X-Uuid', 'some-other-uuid'],
        ];
    }

    public function testAddTargetGroupHitScriptInPreview(): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            true,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');
        $event = $this->createResponseEvent($request, $response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $targetGroupSubscriber->addTargetGroupHitScript($event);

        $this->assertEquals('', $response->getContent());
    }

    public function testAddTargetGroupHitScriptOnBinaryFileResponse(): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $response = new BinaryFileResponse(__FILE__);
        $response->headers->set('Content-Type', 'text/html');
        $event = $this->createResponseEvent($request, $response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $targetGroupSubscriber->addTargetGroupHitScript($event);

        $this->assertEquals('', $response->getContent());
    }

    public function testAddTargetGroupHitScriptOnStreamedResponse(): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $response = new StreamedResponse(function() {});
        $response->headers->set('Content-Type', 'text/html');
        $event = $this->createResponseEvent($request, $response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $targetGroupSubscriber->addTargetGroupHitScript($event);

        $this->assertEquals('', $response->getContent());
    }

    public function testAddTargetGroupHitScriptNonHtml(): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwarded-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $response = new JsonResponse();
        $event = $this->createResponseEvent($request, $response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $targetGroupSubscriber->addTargetGroupHitScript($event);

        $this->assertEquals('{}', $response->getContent());
    }

    public function testAddTargetGroupHitScriptHtmlUtf8(): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwared-Referer',
            'X-Fowarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $response = new Response('<body></body>');
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $event = $this->createResponseEvent($request, $response);

        $this->twig->render(Argument::cetera())->willReturn('<script></script>');

        $targetGroupSubscriber->addTargetGroupHitScript($event);

        $this->assertEquals('<body><script></script></body>', $response->getContent());
    }

    public function testAddTargetGroupHitScriptNonGet(): void
    {
        $targetGroupSubscriber = new TargetGroupSubscriber(
            $this->twig->reveal(),
            false,
            $this->targetGroupStore->reveal(),
            $this->targetGroupEvaluator->reveal(),
            $this->targetGroupRepository->reveal(),
            '/_target_group',
            '/_target_group_hit',
            'X-Forwarded-Url',
            'X-Forwared-Referer',
            'X-Forwarded-UUID',
            'X-Sulu-Target-Group',
            'sulu-visitor-target-group',
            'visitor-session'
        );

        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');
        $event = $this->createResponseEvent($request, $response);

        $this->twig->render(Argument::cetera())->shouldNotBeCalled();

        $targetGroupSubscriber->addTargetGroupHitScript($event);

        $this->assertEquals('', $response->getContent());
    }

    private function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        return new ResponseEvent(
            $this->kernel->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->kernel->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
