<?php

namespace Sulu\Route\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\Uid\Uuid;

class RouteTest extends TestCase
{
    public function testGetResourceKey(): void
    {
        $route = $this->createModel(resourceKey: 'test');

        $this->assertEquals('test', $route->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $uuid = Uuid::v7()->toRfc4122();

        $route = $this->createModel(resourceId: $uuid);

        $this->assertEquals($uuid, $route->getResourceId());
    }

    public function testSetGetSlug(): void
    {
        $slug = '/test';
        $route = $this->createModel(slug: $slug);
        $this->assertSame('/test', $route->getSlug());
        $route->setSlug('/test2');
        $this->assertSame('/test2', $route->getSlug());
    }

    public function createModel(
        string $resourceKey = 'resource',
        string $resourceId = '1',
        string $locale = 'en',
        string $slug = '/',
        ?string $site = null,
        ?Route $parentRoute = null,
    ): Route {
        return new Route(
            $resourceKey,
            $resourceId,
            $locale,
            $slug,
            $site,
            $parentRoute,
        );
    }
}
