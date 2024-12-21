<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter;

use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Palette\RGB;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropper\CropperInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Focus\FocusInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImagineImageConverter;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\MediaImageExtractorInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Scaler\ScalerInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\TransformationPoolInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

class ImagineImageConverterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ImagineInterface>
     */
    private $imagine;

    /**
     * @var ObjectProphecy<ImagineInterface>
     */
    private $svgImagine;

    /**
     * @var ObjectProphecy<StorageInterface>
     */
    private $storage;

    /**
     * @var ObjectProphecy<MediaImageExtractorInterface>
     */
    private $mediaImageExtractor;

    /**
     * @var ObjectProphecy<TransformationPoolInterface>
     */
    private $transformationPool;

    /**
     * @var ObjectProphecy<FocusInterface>
     */
    private $focus;

    /**
     * @var ObjectProphecy<ScalerInterface>
     */
    private $scaler;

    /**
     * @var ObjectProphecy<CropperInterface>
     */
    private $cropper;

    /**
     * @var ImageConverterInterface
     */
    private $imagineImageConverter;

    public function setUp(): void
    {
        $this->imagine = $this->prophesize(ImagineInterface::class);
        $this->svgImagine = $this->prophesize(ImagineInterface::class);
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->mediaImageExtractor = $this->prophesize(MediaImageExtractorInterface::class);
        $this->transformationPool = $this->prophesize(TransformationPoolInterface::class);
        $this->focus = $this->prophesize(FocusInterface::class);
        $this->scaler = $this->prophesize(ScalerInterface::class);
        $this->cropper = $this->prophesize(CropperInterface::class);

        $this->imagineImageConverter = new ImagineImageConverter(
            $this->imagine->reveal(),
            $this->storage->reveal(),
            $this->mediaImageExtractor->reveal(),
            $this->transformationPool->reveal(),
            $this->focus->reveal(),
            $this->scaler->reveal(),
            $this->cropper->reveal(),
            [
                '640x480' => [
                    'options' => [],
                ],
            ],
            [
                'image/*',
                'application/pdf',
                'video/*',
            ],
            $this->svgImagine->reveal()
        );
    }

    public function testConvert(): void
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions(['option' => 'value']);
        $fileVersion->setMimeType('image/jpeg');

        $this->storage->load(['option' => 'value'])->willReturn('image-resource');
        $this->mediaImageExtractor->extract('image-resource', 'image/jpeg')->willReturn('image-resource');
        $this->imagine->read('image-resource')->willReturn($imagineImage->reveal());

        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->metadata()->willReturn(['']);

        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('jpg', [])->willReturn('new-image-resource');

        $this->focus->focus(Argument::any())->shouldNotBeCalled();

        $this->assertEquals('new-image-resource', $this->imagineImageConverter->convert($fileVersion, '640x480', 'jpg'));
    }

    public function testConvertSvg(): void
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.svg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions(['option' => 'value']);
        $fileVersion->setMimeType('image/svg+xml');

        $this->storage->load(['option' => 'value'])->willReturn('image-resource');
        $this->mediaImageExtractor->extract('image-resource', 'image/svg+xml')->willReturn('image-resource');
        $this->svgImagine->read('image-resource')->willReturn($imagineImage->reveal());

        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->metadata()->willReturn(['']);

        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('svg', [])->willReturn('new-image-resource');

        $this->focus->focus(Argument::any())->shouldNotBeCalled();

        $this->assertEquals('new-image-resource', $this->imagineImageConverter->convert($fileVersion, '640x480', 'svg'));
    }

    public function testConvertNoFocusOnInset(): void
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions(['option' => 'value']);
        $fileVersion->setMimeType('image/jpeg');

        $this->storage->load(['option' => 'value'])->willReturn('image-resource');
        $this->mediaImageExtractor->extract('image-resource', 'image/jpeg')->willReturn('image-resource');
        $this->imagine->read('image-resource')->willReturn($imagineImage->reveal());

        $imagineImage->metadata()->willReturn(['']);
        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('jpg', [])->willReturn('new-image-resource');

        $this->focus->focus(Argument::any())->shouldNotBeCalled();

        $this->assertEquals('new-image-resource', $this->imagineImageConverter->convert($fileVersion, '640x480', 'jpg'));
    }

    public function testConvertWithImageExtension(): void
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.svg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions(['option' => 'value']);
        $fileVersion->setMimeType('image/svg+xml');

        $this->storage->load(['option' => 'value'])->willReturn('image-resource');
        $this->mediaImageExtractor->extract('image-resource', 'image/svg+xml')->willReturn('image-resource');
        $this->imagine->read('image-resource')->willReturn($imagineImage->reveal());

        $imagineImage->metadata()->willReturn(['']);
        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('png', [])->willReturn('new-image-resource');

        $this->assertEquals('new-image-resource', $this->imagineImageConverter->convert($fileVersion, '640x480', 'png'));
    }

    public function testConvertCmykToRgb(): void
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);
        $palette->name()->willReturn('cmyk');

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions(['option' => 'value']);
        $fileVersion->setMimeType('image/jpeg');

        $this->storage->load(['option' => 'value'])->willReturn('image-resource');
        $this->mediaImageExtractor->extract('image-resource', 'image/jpeg')->willReturn('image-resource');
        $this->imagine->read('image-resource')->willReturn($imagineImage->reveal());

        $imagineImage->metadata()->willReturn(['']);
        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->usePalette(Argument::type(RGB::class))->shouldBeCalled();
        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('jpg', [])->willReturn('new-image-resource');

        $this->assertEquals('new-image-resource', $this->imagineImageConverter->convert($fileVersion, '640x480', 'jpg'));
    }

    public function testConvertNotExistingMedia(): void
    {
        $this->expectException(ImageProxyMediaNotFoundException::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions(['option' => 'value']);

        $this->storage->load(['option' => 'value'])->willThrow(ImageProxyMediaNotFoundException::class);

        $this->imagineImageConverter->convert($fileVersion, '640x480', 'jpg');
    }

    public function testConvertAutorotate(): void
    {
        $imagineImage = $this->prophesize(ImageInterface::class);
        $palette = $this->prophesize(PaletteInterface::class);

        $fileVersion = new FileVersion();
        $fileVersion->setName('test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setStorageOptions([]);
        $fileVersion->setMimeType('image/jpeg');

        $this->storage->load([])->willReturn('image-content');
        $this->mediaImageExtractor->extract('image-content', 'image/jpeg')->willReturn('image-content');
        $this->imagine->read('image-content')->willReturn($imagineImage->reveal());

        $imagineImage->palette()->willReturn($palette->reveal());
        $imagineImage->strip()->shouldBeCalled();
        $imagineImage->layers()->willReturn(['']);
        $imagineImage->metadata()->willReturn(['ifd0.Orientation' => 3]);

        $imagineImage->rotate(180, Argument::any())->shouldBeCalled();
        $imagineImage->interlace(ImageInterface::INTERLACE_PLANE)->shouldBeCalled();

        $imagineImage->get('jpg', [])->willReturn('new-image-content');

        $this->assertEquals('new-image-content', $this->imagineImageConverter->convert($fileVersion, '640x480', 'jpg'));
    }

    public function testSupportedOutputImageFormatWithoutMimeType(): void
    {
        $this->assertEquals([], $this->imagineImageConverter->getSupportedOutputImageFormats(null));
        $this->assertEquals([], $this->imagineImageConverter->getSupportedOutputImageFormats(''));
    }

    public function testSupportedOutputFormatsWithInvalidMimeType(): void
    {
        $this->assertEquals([], $this->imagineImageConverter->getSupportedOutputImageFormats('wrong'));
    }

    public function testPreferredExtension(): void
    {
        $this->assertEquals('jpg', $this->imagineImageConverter->getSupportedOutputImageFormats('image/ico')[0]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getSimpleExtensionsByMimeTypes')]
    public function testSupportedOutputFormatsWithSimpleValidMimeType(string $mimeType, string $extension): void
    {
        $result = \array_unique([
            $extension,
            'jpg',
            'gif',
            'png',
            'webp',
            'avif',
        ]);

        $this->assertEquals($result, $this->imagineImageConverter->getSupportedOutputImageFormats($mimeType));
    }

    public static function getSimpleExtensionsByMimeTypes(): \Generator
    {
        yield ['image/png', 'png'];
        yield ['image/webp', 'webp'];
        yield ['image/gif', 'gif'];
        yield ['image/avif', 'avif'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getSvgMimeTypes')]
    public function testSupportedOutputFormatsWithValidSvgMimeType(string $mimeType): void
    {
        $this->assertEquals('svg', $this->imagineImageConverter->getSupportedOutputImageFormats($mimeType)[0]);
    }

    /**
     * @throws \ReflectionException
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getSvgMimeTypes')]
    public function testSupportedOutputFormatsWithValidSvgMimeTypeWithoutSvhImagine(string $mimeType): void
    {
        $reflection = new \ReflectionClass($this->imagineImageConverter);
        $property = $reflection->getProperty('svgImagine');
        $property->setAccessible(true);
        $property->setValue($this->imagineImageConverter, null);
        $this->assertEquals('png', $this->imagineImageConverter->getSupportedOutputImageFormats($mimeType)[0]);
    }

    public static function getSvgMimeTypes(): \Generator
    {
        yield ['image/svg+xml'];
        yield ['image/svg'];
    }
}
