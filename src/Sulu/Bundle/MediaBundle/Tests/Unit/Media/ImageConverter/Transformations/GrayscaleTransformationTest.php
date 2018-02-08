<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Transformations;

use Imagine\Effects\EffectsInterface;
use Imagine\Image\ImageInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\GrayscaleTransformation;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * Test the grayscale transformation.
 */
class GrayscaleTransformationTest extends SuluTestCase
{
    /**
     * @var GrayscaleTransformation
     */
    protected $transformation;

    public function setUp()
    {
        $this->transformation = new GrayscaleTransformation();

        parent::setUp();
    }

    public function testGrayscale()
    {
        $image = $this->prophesize(ImageInterface::class);
        $effects = $this->prophesize(EffectsInterface::class);
        $effects->grayscale()->shouldBeCalled();
        $image->effects()->willReturn($effects);

        $returnImage = $this->transformation->execute($image->reveal(), []);

        $this->assertInstanceOf(ImageInterface::class, $returnImage);
    }
}
