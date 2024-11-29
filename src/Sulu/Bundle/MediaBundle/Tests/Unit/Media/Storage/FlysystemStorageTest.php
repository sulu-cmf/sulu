<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\Storage;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemStorage;

class FlysystemStorageTest extends TestCase
{
    use ProphecyTrait;

    private FlysystemStorage $flysystemStorage;

    /**
     * @var ObjectProphecy<FilesystemOperator>
     */
    private $flysystem;

    /**
     * @var ObjectProphecy<FilesystemAdapter>
     */
    private ObjectProphecy $flysystemAdapter;

    protected function setUp(): void
    {
        $this->flysystem = $this->prophesize(FilesystemOperator::class);
        $this->flysystemAdapter = $this->prophesize(FilesystemAdapter::class);

        $this->flysystemStorage = new FlysystemStorage(
            $this->flysystem->reveal(),
            $this->flysystemAdapter->reveal(),
            100,
            null,
        );
    }

    public function testSave(): void
    {
        $storageOptions = [
            'segment' => '01',
        ];

        $this->flysystem->has('01/image.jpg')->shouldBeCalled()->willReturn(false);
        $this->flysystem->has('01')->shouldBeCalled()->willReturn(true);

        $this->flysystem->writeStream(
            '01/image.jpg',
            false,
            ['visibility' => 'public']
        )->shouldBeCalled();

        $newStorageOptions = $this->flysystemStorage->save('/tmp/flysystem', 'image.jpg', $storageOptions);
        self::assertEquals(
            [
                'fileName' => 'image.jpg',
                'segment' => '01',
            ],
            $newStorageOptions
        );
    }

    public function testSaveWithRandomSegment(): void
    {
        $storageOptions = [];
        $segment = null;

        $this->flysystem
            ->has(Argument::type('string'))
            ->shouldBeCalled()
            ->will(static function(array $arguments) use (&$segment) {
                $path = $arguments[0];
                if (\str_ends_with($path, 'image.jpg')) {
                    // Asking if the image path exists should return false because duplicate names are tested elsewhere
                    return false;
                }

                // Otherwise it's the randomly generated segment (a zero filled number from 1 to 100)
                self::assertSame(3, \strlen($path));
                if ('1' === $path[0]) {
                    self::assertSame('100', $path);
                } else {
                    self::assertSame('0', $path[0]);
                }
                $segment = $path;

                return true;
            })
        ;

        $newStorageOptions = $this->flysystemStorage->save('/tmp/flysystem', 'image.jpg', $storageOptions);

        $this->flysystem->writeStream(
            $segment . '/image.jpg',
            false,
            ['visibility' => 'public']
        )->shouldHaveBeenCalledOnce();

        self::assertEquals(
            [
                'fileName' => 'image.jpg',
                'segment' => $segment,
            ],
            $newStorageOptions
        );
    }

    public function testSaveIntoDirectory(): void
    {
        $storageOptions = [
            'directory' => '/tmp/flysystem',
            'segment' => '01',
        ];

        $this->flysystem->has('/tmp/flysystem')->shouldBeCalled()->willReturn(true);
        $this->flysystem->has('/tmp/flysystem/01/image.jpg')->shouldBeCalled()->willReturn(false);
        $this->flysystem->has('/tmp/flysystem/01')->shouldBeCalled()->willReturn(true);

        $this->flysystem->writeStream(
            '/tmp/flysystem/01/image.jpg',
            false,
            ['visibility' => 'public']
        )->shouldBeCalled();

        $newStorageOptions = $this->flysystemStorage->save('/tmp/flysystem', 'image.jpg', $storageOptions);
        self::assertEquals(
            [
                'fileName' => 'image.jpg',
                'segment' => '01',
                'directory' => '/tmp/flysystem',
            ],
            $newStorageOptions
        );
    }

    public function testSaveNonUniqueFileName(): void
    {
        $storageOptions = [
            'segment' => '08',
        ];

        $this->flysystem->has('08')->shouldBeCalled()->willReturn(true);
        $this->flysystem->has('08/image.jpg')->shouldBeCalled()->willReturn(true);
        $this->flysystem->has('08/image-1.jpg')->shouldBeCalled()->willReturn(false);

        $this->flysystem->writeStream(
            '08/image-1.jpg',
            Argument::type('resource'),
            ['visibility' => 'public']
        )->shouldBeCalled();

        $newStorageOptions = $this->flysystemStorage->save('/', 'image.jpg', $storageOptions);
        self::assertEquals(
            [
                'fileName' => 'image-1.jpg',
                'segment' => '08',
            ],
            $newStorageOptions
        );
    }

    public function testLoad(): void
    {
        $storageOptions = [
            'segment' => '08',
            'fileName' => 'test.jpg',
        ];

        $resource = @\opendir(__DIR__);

        $this->flysystem
            ->readStream('08/test.jpg')
            ->shouldBeCalled()
            ->willReturn($resource)
        ;

        $loadedResource = $this->flysystemStorage->load($storageOptions);

        $this->assertSame($resource, $loadedResource);
    }

    public function testRemove(): void
    {
        $storageOptions = [
            'segment' => '08',
            'fileName' => 'test.jpg',
        ];

        $this->flysystem->delete('08/test.jpg')->shouldBeCalled();

        $this->flysystemStorage->remove($storageOptions);
    }

    public function testMove(): void
    {
        $sourceStorageOptions = [
            'segment' => '08',
            'fileName' => 'test.jpg',
        ];
        $targetStorageOptions = [
            'segment' => '10',
            'fileName' => 'hallo.jpg',
        ];

        $this->flysystem->has('10')->shouldBeCalled()->willReturn(false);
        $this->flysystem->has('10/hallo.jpg')->shouldBeCalled()->willReturn(false);

        $this->flysystem->createDirectory('10')->shouldBeCalled();

        $this->flysystem
            ->move('08/test.jpg', '10/hallo.jpg')
            ->shouldBeCalled();

        $outputStorageOptions = $this->flysystemStorage->move($sourceStorageOptions, $targetStorageOptions);

        $this->assertEquals($targetStorageOptions, $outputStorageOptions);
    }

    public function testMoveToExistingFile(): void
    {
        $sourceStorageOptions = [
            'segment' => '08',
            'fileName' => 'test.jpg',
        ];
        $targetStorageOptions = [
            'segment' => '10',
            'fileName' => 'hallo.jpg',
        ];

        $this->flysystem->has('10')->shouldBeCalled()->willReturn(false);
        $this->flysystem->has('10/hallo.jpg')->shouldBeCalled()->willReturn(true);
        $this->flysystem->has('10/hallo-1.jpg')->shouldBeCalled()->willReturn(false);

        $this->flysystem->createDirectory('10')->shouldBeCalled();

        $this->flysystem
            ->move('08/test.jpg', '10/hallo-1.jpg')
            ->shouldBeCalled();

        $outputStorageOptions = $this->flysystemStorage->move($sourceStorageOptions, $targetStorageOptions);

        $targetStorageOptions = [
            'segment' => '10',
            'fileName' => 'hallo-1.jpg',
        ];
        $this->assertEquals($targetStorageOptions, $outputStorageOptions);
    }

    public function testGetPath(): void
    {
        $storageOptions = [
            'segment' => '10',
            'fileName' => 'hallo.jpg',
        ];

        $filePath = $this->flysystemStorage->getPath($storageOptions);

        $this->assertEquals('10/hallo.jpg', $filePath);
    }

    public function testGetType(): void
    {
        $storageOptions = [
            'segment' => '10',
            'fileName' => 'hallo.jpg',
        ];

        $filePath = $this->flysystemStorage->getType($storageOptions);

        $this->assertEquals('remote', $filePath);
    }
}
