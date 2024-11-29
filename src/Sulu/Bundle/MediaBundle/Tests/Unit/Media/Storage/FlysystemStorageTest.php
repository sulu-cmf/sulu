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

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemStorage;

class FlysystemStorageTest extends TestCase
{
    use ProphecyTrait;

    private FlysystemStorage $flysystemStorage;

    private FilesystemOperator $flysystem;

    protected function setUp(): void
    {
        $adapter = new InMemoryFilesystemAdapter();
        $this->flysystem = new Filesystem($adapter);

        $this->flysystemStorage = new FlysystemStorage(
            $this->flysystem,
            $adapter,
            100,
            null,
        );
    }

    public function testSave(): void
    {
        $storageOptions = [
            'segment' => '01',
        ];

        self::assertEquals(
            [
                'fileName' => 'image.jpg',
                'segment' => '01',
            ],
            $this->flysystemStorage->save(__FILE__, 'image.jpg', $storageOptions)
        );

        self::assertTrue($this->flysystem->has('01/image.jpg'));
    }

    public function testSaveWithRandomSegment(): void
    {
        $storageOptions = [];

        $newStorageOptions = $this->flysystemStorage->save(__FILE__, 'code.php', $storageOptions);

        self::assertEquals($newStorageOptions['fileName'] ?? '', 'code.php');
        self::assertEquals(3, \strlen((string) ($newStorageOptions['segment'] ?? '')));

        self::assertTrue($this->flysystem->has(($newStorageOptions['segment'] ?? '') . '/code.php'));
    }

    public function testSaveUnableToReadSource(): void
    {
        $storageOptions = [
            'sement' => '01',
        ];

        $this->expectException(FilenameAlreadyExistsException::class);

        $this->flysystemStorage->save(__DIR__ . '/does_not_exist', 'image.jpg', $storageOptions);
    }

    public function testSaveIntoDirectory(): void
    {
        $storageOptions = [
            'directory' => '/tmp/flysystem',
            'segment' => '01',
        ];

        self::assertEquals(
            \array_merge($storageOptions, [
                'fileName' => 'code.php',
            ]),
            $this->flysystemStorage->save(__FILE__, 'code.php', $storageOptions),
        );

        self::assertTrue($this->flysystem->has('tmp/flysystem/01/code.php'));
    }

    public function testSaveNonUniqueFileName(): void
    {
        $storageOptions = [
            'segment' => '08',
        ];

        $this->flysystem->write('08/code.php', 'Some code');

        $newStorageOptions = $this->flysystemStorage->save(__FILE__, 'code.php', $storageOptions);
        self::assertEquals(
            [
                'fileName' => 'code-1.php',
                'segment' => '08',
            ],
            $newStorageOptions
        );
        self::assertTrue($this->flysystem->has('08/code-1.php'));
    }

    public function testLoad(): void
    {
        $storageOptions = [
            'segment' => '08',
            'fileName' => 'code.php',
        ];

        $this->flysystem->write('08/code.php', 'Some code');

        $loadedResource = $this->flysystemStorage->load($storageOptions);

        self::assertEquals('Some code', \stream_get_contents($loadedResource));
    }

    public function testRemove(): void
    {
        $storageOptions = [
            'segment' => '08',
            'fileName' => 'plant.jpg',
        ];

        $this->flysystem->write('08/plant.jpg', 'Flowers and stuff');

        $this->flysystemStorage->remove($storageOptions);

        self::assertFalse($this->flysystem->has('08/plant.jpg'));
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

        $this->flysystem->write('08/test.jpg', 'Some file');
        $outputStorageOptions = $this->flysystemStorage->move($sourceStorageOptions, $targetStorageOptions);

        $this->assertEquals($targetStorageOptions, $outputStorageOptions);

        self::assertTrue($this->flysystem->has('10/hallo.jpg'));
        self::assertFalse($this->flysystem->has('08/test.jpg'));
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

        $this->flysystem->write('08/test.jpg', 'Some file');
        $this->flysystem->write('10/hallo.jpg', 'Already there');
        $outputStorageOptions = $this->flysystemStorage->move($sourceStorageOptions, $targetStorageOptions);

        $targetStorageOptions = [
            'segment' => '10',
            'fileName' => 'hallo-1.jpg',
        ];
        $this->assertEquals($targetStorageOptions, $outputStorageOptions);

        self::assertTrue($this->flysystem->has('10/hallo.jpg'));
        self::assertTrue($this->flysystem->has('10/hallo-1.jpg'));
        self::assertFalse($this->flysystem->has('08/test.jpg'));
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
