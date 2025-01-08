<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\StructureProvider;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Webspace\StructureProvider\WebspaceStructureProvider;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class WebspaceStructureProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetStructures(): void
    {
        $cache = DoctrineProvider::wrap(new ArrayAdapter());

        $structures = [
            $this->generateStructure('t1', 'MyBundle:default:t1'),
            $this->generateStructure('t2', 'MyBundle:default:t2'),
            $this->generateStructure('t3', 'MyBundle:default:t3'),
        ];

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getTheme()->willReturn('test');

        $twigLoader = $this->prophesize(FilesystemLoader::class);
        $twigLoader->exists('MyBundle:default:t1.html.twig')->willReturn(false);
        $twigLoader->exists('MyBundle:default:t2.html.twig')->shouldBeCalled()->willReturn(true);
        $twigLoader->exists('MyBundle:default:t3.html.twig')->willReturn(false);

        $twig = $this->prophesize(Environment::class);
        $twig->getLoader()->willReturn($twigLoader->reveal());

        $structureManager = $this->prophesize(StructureManagerInterface::class);
        $structureManager->getStructures()->willReturn($structures);

        $structureProvider = new WebspaceStructureProvider(
            $twig->reveal(),
            $structureManager->reveal(),
            $cache
        );

        $result = $structureProvider->getStructures('sulu_io');

        $this->assertCount(1, $result);
        $this->assertEquals($structures[1], $result[0]);

        $this->assertTrue($cache->contains('sulu_io'));
        $this->assertEquals(['t2'], $cache->fetch('sulu_io'));
    }

    public function testGetStructuresCached(): void
    {
        $cache = DoctrineProvider::wrap(new ArrayAdapter());
        $cache->save('sulu_io', ['t1', 't3']);

        $structures = [
            $this->generateStructure('t1', 'MyBundle:default:t1'),
            $this->generateStructure('t2', 'MyBundle:default:t2'),
            $this->generateStructure('t3', 'MyBundle:default:t3'),
        ];

        $twig = $this->prophesize(Environment::class);
        $twig->getLoader()->shouldNotBeCalled();

        $structureManager = $this->prophesize(StructureManagerInterface::class);
        $structureManager->getStructures()->shouldNotBeCalled();
        $structureManager->getStructure('t1')->willReturn($structures[0]);
        $structureManager->getStructure('t2')->shouldNotBeCalled();
        $structureManager->getStructure('t3')->willReturn($structures[2]);

        $structureProvider = new WebspaceStructureProvider(
            $twig->reveal(),
            $structureManager->reveal(),
            $cache
        );

        $result = $structureProvider->getStructures('sulu_io');

        $this->assertCount(2, $result);
        $this->assertEquals($structures[0]->getKey(), $result[0]->getKey());
        $this->assertEquals($structures[2]->getKey(), $result[1]->getKey());
    }

    /**
     * @return StructureInterface
     */
    private function generateStructure(string $key, string $view)
    {
        $mock = $this->prophesize(PageBridge::class);

        $mock->getKey()->willReturn($key);
        $mock->getView()->willReturn($view);

        return $mock->reveal();
    }
}
