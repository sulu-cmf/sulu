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
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\BlurTransformation;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * Test the blur transformation.
 */
class BlurTransformationTest extends SuluTestCase
{
    /**
     * @var BlurTransformation
     */
    protected $transformation;

    public function setUp()
    {
        $this->transformation = new BlurTransformation();

        parent::setUp();
    }

    public function testBlur()
    {
        $image = $this->prophesize(ImageInterface::class);
        $effects = $this->prophesize(EffectsInterface::class);
        $effects->blur(0.75)->shouldBeCalled();
        $image->effects()->willReturn($effects);

        $returnImage = $this->transformation->execute(
            $image->reveal(),
            [
                'sigma' => '0.75',
            ]
        );

        $this->assertInstanceOf(ImageInterface::class, $returnImage);
    }
}
