<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Focus;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Focus\Focus;

class FocusTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var Focus
     */
    private $focus;

    public function setUp(): void
    {
        $this->focus = new Focus();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFocus')]
    public function testFocus($imageWidth, $imageHeight, $x, $y, $width, $height, $cropX, $cropY, $cropWidth, $cropHeight): void
    {
        $image = $this->prophesize(ImageInterface::class);
        $image->getSize()->willReturn(new Box($imageWidth, $imageHeight));

        $image->crop(new Point($cropX, $cropY), new Box($cropWidth, $cropHeight))->shouldBeCalled();

        $this->focus->focus($image->reveal(), $x, $y, $width, $height);
    }

    public static function provideFocus()
    {
        return [
            [800, 800, 0, 0, 400, 400, 0, 0, 800, 800],
            [800, 800, 2, 2, 400, 400, 0, 0, 800, 800],
            [1200, 800, 0, 0, 400, 400, 0, 0, 800, 800],
            [1200, 800, 1, 1, 400, 400, 200, 0, 800, 800],
            [1200, 800, 2, 1, 400, 400, 400, 0, 800, 800],
            [800, 1200, 0, 0, 400, 400, 0, 0, 800, 800],
            [800, 1200, 0, 1, 400, 400, 0, 200, 800, 800],
            [800, 1200, 0, 2, 400, 400, 0, 400, 800, 800],
            [1200, 800, null, null, 400, 400, 200, 0, 800, 800],
            [300, 220, null, null, 300, 220, 0, 0, 300, 220],
            [250, 60, null, null, 250, 60, 0, 0, 250, 60],
            [60, 250, null, null, 60, 250, 0, 0, 60, 250],
        ];
    }
}
