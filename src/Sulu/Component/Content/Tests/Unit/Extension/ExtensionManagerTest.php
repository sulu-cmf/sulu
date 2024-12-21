<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Extension;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManager;

class ExtensionManagerTest extends TestCase
{
    use ProphecyTrait;

    private static function createExtension(string $name): ExtensionInterface
    {
        return new class($name) extends AbstractExtension {
            public function __construct(string $name)
            {
                $this->name = $name;
            }

            /**
             * save data to node.
             *
             * @param array<string, mixed> $data
             * @param string $webspaceKey
             * @param string $languageCode
             */
            public function save(NodeInterface $node, $data, $webspaceKey, $languageCode): void
            {
            }

            /**
             * load data from node.
             *
             * @param string $webspaceKey
             * @param string $languageCode
             *
             * @return mixed data of extension
             */
            public function load(NodeInterface $node, $webspaceKey, $languageCode): mixed
            {
                return null;
            }
        };
    }

    public static function addProvider()
    {
        $instances = [
            self::createExtension('test1'),
            self::createExtension('test2'),
            self::createExtension('test3'),
            self::createExtension('test4'),
        ];

        $extensions = [
            ['instance' => $instances[0], 'type' => 'all'],
            ['instance' => $instances[1], 'type' => 'page'],
            ['instance' => $instances[2], 'type' => 'snippet'],
            ['instance' => $instances[3], 'type' => 'page'],
        ];

        return [
            [$extensions, 'page', ['test1' => $instances[0], 'test2' => $instances[1], 'test4' => $instances[3]]],
            [$extensions, 'snippet', ['test1' => $instances[0], 'test3' => $instances[2]]],
            [$extensions, 'home', ['test1' => $instances[0]]],
        ];
    }

    /**
     * @param array<array{
     *     instance: ExtensionInterface,
     *     type: string,
     * }> $extensions
     * @param array<string, ExtensionInterface> $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('addProvider')]
    public function testAdd(array $extensions, string $type, array $expected): void
    {
        $manager = new ExtensionManager();

        foreach ($extensions as $extension) {
            $manager->addExtension($extension['instance'], $extension['type']);
        }

        $this->assertEquals($expected, $manager->getExtensions($type));
    }

    public static function hasProvider()
    {
        $instances = [
            self::createExtension('test1'),
            self::createExtension('test2'),
            self::createExtension('test3'),
            self::createExtension('test4'),
        ];

        $extensions = [
            ['instance' => $instances[0], 'type' => 'all'],
            ['instance' => $instances[1], 'type' => 'page'],
            ['instance' => $instances[2], 'type' => 'snippet'],
            ['instance' => $instances[3], 'type' => 'page'],
        ];

        return [
            [$extensions, 'page', 'test1', true],
            [$extensions, 'page', 'test2', true],
            [$extensions, 'page', 'test3', false],
            [$extensions, 'page', 'test4', true],
            [$extensions, 'snippet', 'test1', true],
            [$extensions, 'snippet', 'test2', false],
            [$extensions, 'snippet', 'test3', true],
            [$extensions, 'snippet', 'test4', false],
            [$extensions, 'home', 'test1', true],
            [$extensions, 'home', 'test2', false],
            [$extensions, 'home', 'test3', false],
            [$extensions, 'home', 'test4', false],
        ];
    }

    /**
     * @param array<array{
     *     instance: ExtensionInterface,
     *     type: string,
     * }> $extensions
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('hasProvider')]
    public function testHas(array $extensions, string $type, string $name, bool $expected): void
    {
        $manager = new ExtensionManager();

        foreach ($extensions as $extension) {
            $manager->addExtension($extension['instance'], $extension['type']);
        }

        $this->assertEquals($expected, $manager->hasExtension($type, $name));
    }

    /**
     * @return iterable<array{
     *     0: array<array{
     *         instance: ExtensionInterface,
     *         type: string,
     *     }>,
     *     1: string,
     *     2: string,
     *     3: ExtensionInterface,
     * }>
     */
    public static function getProvider()
    {
        $instances = [
            self::createExtension('test1'),
            self::createExtension('test2'),
            self::createExtension('test3'),
            self::createExtension('test4'),
        ];

        $extensions = [
            ['instance' => $instances[0], 'type' => 'all'],
            ['instance' => $instances[1], 'type' => 'page'],
            ['instance' => $instances[2], 'type' => 'snippet'],
            ['instance' => $instances[3], 'type' => 'page'],
        ];

        return [
            [$extensions, 'page', 'test1', $instances[0]],
            [$extensions, 'page', 'test2', $instances[1]],
            [$extensions, 'page', 'test4', $instances[3]],
            [$extensions, 'snippet', 'test1', $instances[0]],
            [$extensions, 'snippet', 'test3', $instances[2]],
            [$extensions, 'home', 'test1', $instances[0]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getProvider')]
    public function testGet($extensions, $type, $name, $expected): void
    {
        $manager = new ExtensionManager();

        foreach ($extensions as $extension) {
            $manager->addExtension($extension['instance'], $extension['type']);
        }

        $this->assertEquals($expected, $manager->getExtension($type, $name));
    }

    /**
     * @return iterable<array{
     *     0: array<array{
     *         instance: ExtensionInterface,
     *         type: string,
     *     }>,
     *     1: string,
     *     2: string,
     *     3: class-string<\Throwable>
     * }>
     */
    public static function getExceptionProvider()
    {
        $instances = [
            self::createExtension('test1'),
            self::createExtension('test2'),
            self::createExtension('test3'),
            self::createExtension('test4'),
        ];

        $extensions = [
            ['instance' => $instances[0], 'type' => 'all'],
            ['instance' => $instances[1], 'type' => 'page'],
            ['instance' => $instances[2], 'type' => 'snippet'],
            ['instance' => $instances[3], 'type' => 'page'],
        ];

        return [
            [$extensions, 'page', 'test3', \InvalidArgumentException::class],
            [$extensions, 'snippet', 'test2', \InvalidArgumentException::class],
            [$extensions, 'snippet', 'test4', \InvalidArgumentException::class],
            [$extensions, 'home', 'test2', \InvalidArgumentException::class],
            [$extensions, 'home', 'test3', \InvalidArgumentException::class],
            [$extensions, 'home', 'test4', \InvalidArgumentException::class],
        ];
    }

    /**
     * @param array<array{
     *      instance: ExtensionInterface,
     *      type: string,
     * }> $extensions
     * @param class-string<\Throwable> $exxceptionName
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getExceptionProvider')]
    public function testGetException($extensions, string $type, string $name, string $exxceptionName): void
    {
        $this->expectException($exxceptionName);

        $manager = new ExtensionManager();

        foreach ($extensions as $extension) {
            $manager->addExtension($extension['instance'], $extension['type']);
        }

        $manager->getExtension($type, $name);
    }
}
