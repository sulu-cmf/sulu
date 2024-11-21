<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Twig;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Twig\UserTwigExtension;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class UserTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var UserTwigExtension
     */
    private $extension;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var ObjectProphecy<UserRepository>
     */
    private $userRepository;

    protected function setUp(): void
    {
        $this->cache = DoctrineProvider::wrap(new ArrayAdapter());
        $this->userRepository = $this->prophesize(UserRepository::class);

        $this->extension = new UserTwigExtension($this->cache, $this->userRepository->reveal());
    }

    public function testResolveUserFunction(): void
    {
        $user1 = new User();
        $user1->setUsername('hikaru');

        $user2 = new User();
        $user2->setUsername('sulu');

        $this->userRepository->findUserById(1)->willReturn($user1);
        $this->userRepository->findUserById(2)->willReturn($user2);

        $user = $this->extension->resolveUserFunction(1);
        $this->assertEquals('hikaru', $user->getUsername());

        $user = $this->extension->resolveUserFunction(2);
        $this->assertEquals('sulu', $user->getUsername());
    }

    public function testResolveUserFunctionNonExisting(): void
    {
        $user = $this->extension->resolveUserFunction(3);
        $this->assertNull($user);
    }
}
