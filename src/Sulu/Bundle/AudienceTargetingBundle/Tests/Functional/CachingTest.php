<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpFoundation\Cookie;

require_once __DIR__ . '/../app/AppCache.php';

class CachingTest extends SuluTestCase
{
    public function testFirstRequestIsACacheMiss()
    {
        $this->purgeDatabase();
        $cacheKernel = new \AppCache($this->getKernel(['sulu_context' => 'website']), true);
        $cookieJar = new CookieJar();
        $client = new Client($cacheKernel, [], null, $cookieJar);

        $client->request('PURGE', '/');

        $cookieJar->clear();
        $targetGroup = $this->createTargetGroup(
            3,
            'locale',
            ['locale' => 'en'],
            TargetGroupRuleInterface::FREQUENCY_VISITOR
        );

        // first request should be cache miss
        $client->request('GET', '/');
        $response = $client->getResponse();
        $this->assertContains('X-Sulu-Target-Group', $response->getVary());
        $this->assertContains('miss', $response->headers->get('x-symfony-cache'));
        $this->assertCount(2, $response->headers->getCookies());
        /** @var Cookie $visitorTargetGroupCookie */
        $visitorTargetGroupCookie = $response->headers->getCookies()[0];
        $this->assertEquals('_svtg', $visitorTargetGroupCookie->getName());
        $this->assertEquals($targetGroup->getId(), $visitorTargetGroupCookie->getValue());
        $visitorSessionCookie = $response->headers->getCookies()[1];
        $this->assertEquals('_svs', $visitorSessionCookie->getName());
        $this->assertEquals(0, $response->headers->getCacheControlDirective('max-age'));
        $this->assertEquals(0, $response->headers->getCacheControlDirective('s-maxage'));

        return [$client, $cookieJar];
    }

    /**
     * @depends testFirstRequestIsACacheMiss
     */
    public function testSecondRequestIsACacheHit($arguments)
    {
        list($client, $cookieJar) = $arguments;

        $client->request('GET', '/');
        $response = $client->getResponse();
        $this->assertContains('fresh', $response->headers->get('x-symfony-cache'));
        $this->assertCount(0, $response->headers->getCookies());

        return [$client, $cookieJar];
    }

    /**
     * @depends testSecondRequestIsACacheHit
     */
    public function testRequestFromOtherClientIsACacheMiss($arguments)
    {
        list($client, $cookieJar) = $arguments;

        $cookieJar->clear(); // new client does not have any cookies yet
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de']);
        $response = $client->getResponse();
        $this->assertContains('miss', $response->headers->get('x-symfony-cache'));
        $this->assertCount(2, $response->headers->getCookies());
        /** @var Cookie $cookie */
        $visitorTargetGroupCookie = $response->headers->getCookies()[0];
        $this->assertEquals('_svtg', $visitorTargetGroupCookie->getName());
        $this->assertEquals(0, $visitorTargetGroupCookie->getValue());
        $visitorSessionCookie = $response->headers->getCookies()[1];
        $this->assertEquals('_svs', $visitorSessionCookie->getName());

        return [$client, $cookieJar];
    }

    /**
     * @depends testRequestFromOtherClientIsACacheMiss
     */
    public function testRequestWithoutSessionCookieTriggersNoRules($arguments)
    {
        /** @var Client $client */
        /** @var CookieJar $cookieJar */
        list($client, $cookieJar) = $arguments;

        $cookieJar->expire('_svs');

        $client->request('GET', '/');
        $response = $client->getResponse();
        $this->assertContains('fresh', $response->headers->get('x-symfony-cache'));
        $this->assertCount(2, $response->headers->getCookies());

        /** @var Cookie $visitorTargetGroupCookie */
        $visitorTargetGroupCookie = $response->headers->getCookies()[0];
        $this->assertEquals('_svtg', $visitorTargetGroupCookie->getName());
        $visitorSessionCookie = $response->headers->getCookies()[1];
        $this->assertEquals('_svs', $visitorSessionCookie->getName());

        return [$client, $cookieJar];
    }

    /**
     * @depends testRequestWithoutSessionCookieTriggersNoRules
     */
    public function testRequestWithoutSessionCookieTriggersARule($arguments)
    {
        /** @var Client $client */
        /** @var CookieJar $cookieJar */
        list($client, $cookieJar) = $arguments;

        $cookieJar->expire('_svs');

        $targetGroup1 = $this->createTargetGroup(
            5,
            'locale',
            ['locale' => 'en'],
            TargetGroupRuleInterface::FREQUENCY_VISITOR
        );

        $targetGroup2 = $this->createTargetGroup(
            4,
            'locale',
            ['locale' => 'en'],
            TargetGroupRuleInterface::FREQUENCY_SESSION
        );

        $client->request('GET', '/');
        $response = $client->getResponse();
        $this->assertContains('miss', $response->headers->get('x-symfony-cache'));
        $this->assertCount(2, $response->headers->getCookies());

        /** @var Cookie $visitorTargetGroupCookie */
        $visitorTargetGroupCookie = $response->headers->getCookies()[0];
        $this->assertEquals('_svtg', $visitorTargetGroupCookie->getName());
        $this->assertEquals($targetGroup2->getId(), $visitorTargetGroupCookie->getValue());
        $visitorSessionCookie = $response->headers->getCookies()[1];
        $this->assertEquals('_svs', $visitorSessionCookie->getName());
    }

    /**
     * @return TargetGroupInterface
     */
    private function createTargetGroup($priority, $rule, $condition, $frequency)
    {
        /** @var TargetGroupRepositoryInterface $targetGroupRepository */
        $targetGroupRepository = $this->getContainer()->get('sulu.repository.target_group');
        /** @var TargetGroupWebspaceRepositoryInterface $targetGroupWebspaceRepository */
        $targetGroupWebspaceRepository = $this->getContainer()->get('sulu.repository.target_group_webspace');
        /** @var TargetGroupRuleRepositoryInterface $targetGroupRuleRepository */
        $targetGroupRuleRepository = $this->getContainer()->get('sulu.repository.target_group_rule');
        /** @var TargetGroupConditionInterface $targetGroupConditionRepository */
        $targetGroupConditionRepository = $this->getContainer()->get('sulu.repository.target_group_condition');

        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $targetGroupRepository->createNew();
        $targetGroup->setTitle('Test');
        $targetGroup->setPriority($priority);
        $targetGroup->setActive(true);
        /** @var TargetGroupWebspaceInterface $targetGroupWebspace */
        $targetGroupWebspace = $targetGroupWebspaceRepository->createNew();
        $targetGroupWebspace->setWebspaceKey('sulu_io');
        $targetGroup->addWebspace($targetGroupWebspace);
        /** @var TargetGroupRuleInterface $targetGroupRule */
        $targetGroupRule = $targetGroupRuleRepository->createNew();
        $targetGroupRule->setTitle('Test');
        $targetGroupRule->setFrequency($frequency);
        /** @var TargetGroupConditionInterface $targetGroupCondition */
        $targetGroupCondition = $targetGroupConditionRepository->createNew();
        $targetGroupCondition->setType($rule);
        $targetGroupCondition->setCondition($condition);
        $targetGroupRule->addCondition($targetGroupCondition);
        $targetGroup->addRule($targetGroupRule);
        $targetGroup = $targetGroupRepository->save($targetGroup);
        $this->getEntityManager()->flush();

        return $targetGroup;
    }
}
