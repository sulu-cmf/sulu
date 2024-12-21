<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;

class S3StorageTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        $this->expectException(\RuntimeException::class);

        $adapter = $this->prophesize(AdapterInterface::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        new S3Storage($flysystem->reveal(), 1);
    }

    public function testSave(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $flysystem->has('1/test.jpg')->wilLReturn(false);
        $flysystem->has('1')->wilLReturn(false);
        $flysystem->writeStream('1/test.jpg', Argument::any(), ['visibility' => AdapterInterface::VISIBILITY_PUBLIC])
            ->shouldBeCalled();

        $flysystem->createDir('1')->shouldBeCalled();

        $storageOptions = $storage->save(\tempnam(\sys_get_temp_dir(), 'test'), 'test.jpg');
        $this->assertEquals(['segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
    }

    public function testSaveDirectoryExists(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $flysystem->has('1/test.jpg')->wilLReturn(false);
        $flysystem->has('1')->wilLReturn(true);
        $flysystem->writeStream('1/test.jpg', Argument::any(), ['visibility' => AdapterInterface::VISIBILITY_PUBLIC])
            ->shouldBeCalled();

        $flysystem->createDir(Argument::any())->shouldNotBeCalled();

        $storageOptions = $storage->save(\tempnam(\sys_get_temp_dir(), 'test'), 'test.jpg');
        $this->assertEquals(['segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
    }

    public function testSaveUniqueFileName(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $flysystem->has('1/test.jpg')->wilLReturn(true);
        $flysystem->has('1/test-1.jpg')->wilLReturn(false);
        $flysystem->has('1')->wilLReturn(false);
        $flysystem->writeStream('1/test-1.jpg', Argument::any(), ['visibility' => AdapterInterface::VISIBILITY_PUBLIC])
            ->shouldBeCalled();

        $flysystem->createDir('1')->shouldBeCalled();

        $storageOptions = $storage->save(\tempnam(\sys_get_temp_dir(), 'test'), 'test.jpg');
        $this->assertEquals(['segment' => '1', 'fileName' => 'test-1.jpg'], $storageOptions);
    }

    public function testLoad(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $handle = \tmpfile();
        $flysystem->readStream('1/test.jpg')->willReturn($handle)->shouldBeCalled();

        $result = $storage->load(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals($handle, $result);
    }

    public function testLoadWithDirectory(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $handle = \tmpfile();
        $flysystem->readStream('trash/1/test.jpg')->willReturn($handle)->shouldBeCalled();

        $result = $storage->load(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals($handle, $result);
    }

    public function testLoadNotFound(): void
    {
        $this->expectException(ImageProxyMediaNotFoundException::class);

        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $handle = \tmpfile();
        $flysystem->readStream('1/test.jpg')->willThrow(new FileNotFoundException('1/test.jpg'))->shouldBeCalled();

        $result = $storage->load(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals($handle, $result);
    }

    public function testRemove(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $flysystem->delete('1/test.jpg')->shouldBeCalled();

        $storage->remove(['segment' => '1', 'fileName' => 'test.jpg']);
    }

    public function testRemoveWithDirectory(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $flysystem->delete('trash/1/test.jpg')->shouldBeCalled();

        $storage->remove(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']);
    }

    public function testRemoveNotFound(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new S3Storage($flysystem->reveal(), 1);

        $flysystem->delete('1/test.jpg')->willThrow(new FileNotFoundException('1/test.jpg'))->shouldBeCalled();

        $storage->remove(['segment' => '1', 'fileName' => 'test.jpg']);
    }

    public function testMove(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $flysystem->has('trash')->wilLReturn(false);
        $flysystem->createDir('trash')->shouldBeCalled();

        $flysystem->has('trash/1')->wilLReturn(false);
        $flysystem->createDir('trash/1')->shouldBeCalled();

        $flysystem->has('trash/1/test.jpg')->wilLReturn(false);
        $flysystem->rename('1/test.jpg', 'trash/1/test.jpg')->shouldBeCalled();

        $result = $storage->move(
            ['segment' => '1', 'fileName' => 'test.jpg'],
            ['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']
        );

        $this->assertSame(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'], $result);
    }

    public function testMoveTargetDirectoryExists(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $flysystem->has('trash')->wilLReturn(true);
        $flysystem->has('trash/1')->wilLReturn(true);

        $flysystem->has('trash/1/test.jpg')->wilLReturn(false);
        $flysystem->rename('1/test.jpg', 'trash/1/test.jpg')->shouldBeCalled();

        $result = $storage->move(
            ['segment' => '1', 'fileName' => 'test.jpg'],
            ['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']
        );

        $this->assertSame(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'], $result);
    }

    public function testMoveTargetFileExists(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $flysystem->has('trash')->wilLReturn(true);
        $flysystem->has('trash/1')->wilLReturn(true);

        $flysystem->has('trash/1/test.jpg')->wilLReturn(true);
        $flysystem->has('trash/1/test-1.jpg')->wilLReturn(true);
        $flysystem->has('trash/1/test-2.jpg')->wilLReturn(false);
        $flysystem->rename('1/test.jpg', 'trash/1/test-2.jpg')->shouldBeCalled();

        $result = $storage->move(
            ['segment' => '1', 'fileName' => 'test.jpg'],
            ['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']
        );

        $this->assertSame(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test-2.jpg'], $result);
    }

    public function testGetPathWithoutPublicUrl(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');
        $adapter->applyPathPrefix('1/test.jpg')->willReturn('xxx/1/test.jpg');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $path = $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('http://aws.com/test/xxx/1/test.jpg', $path);
    }

    public function testGetPathWithPublicUrl(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');
        $adapter->applyPathPrefix('1/test.jpg')->willReturn('xxx/1/test.jpg');

        $storage = new S3Storage($flysystem->reveal(), 1, 'https://example.org/some');

        $path = $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('https://example.org/some/xxx/1/test.jpg', $path);
    }

    public function testGetPathWithDirectory(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');
        $adapter->applyPathPrefix('trash/1/test.jpg')->willReturn('xxx/trash/1/test.jpg');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $path = $storage->getPath(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('http://aws.com/test/xxx/trash/1/test.jpg', $path);
    }

    public function testGetType(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $type = $storage->getType(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals(StorageInterface::TYPE_REMOTE, $type);
    }
}
