<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Transformations;

/**
 * Class CropTransformationTest
 * Test the crop transformation service.
 */
class CropTransformationTest extends AbstractTransformation
{
    protected $transformationServiceName = 'sulu_media.image.transformation.crop';

    /**
     * @return array<array{
     *     options: array{
     *         x: int,
     *         y: int,
     *         w: int,
     *         h: int,
     *     },
     *     width: int,
     *     height: int,
     * }>
     */
    protected function getDataList(): array
    {
        return [
            [
                // Transformation Options
                'options' => [
                    'x' => 100,
                    'y' => 100,
                    'w' => 150,
                    'h' => 200,
                ],
                // Tested Result
                'width' => 150,
                'height' => 200,
            ],
        ];
    }
}
