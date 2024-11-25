<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentRichEntityTraitTest extends TestCase
{
    protected function getContentRichEntityInstance(): Example
    {
        return new class() extends Example {
            public function createDimensionContent(): DimensionContentInterface
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }

            public function getId()
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }
        };
    }

    public function testGetAddRemoveDimension(): void
    {
        $model = $this->getContentRichEntityInstance();

        $this->assertEmpty($model->getDimensionContents());

        $modelDimension1 = new ExampleDimensionContent($model);
        $modelDimension2 = new ExampleDimensionContent($model);

        $model->addDimensionContent($modelDimension1);
        $model->addDimensionContent($modelDimension2);

        $this->assertSame([
            $modelDimension1,
            $modelDimension2,
        ], \iterator_to_array($model->getDimensionContents()));

        $model->removeDimensionContent($modelDimension2);

        $this->assertSame([
            $modelDimension1,
        ], \iterator_to_array($model->getDimensionContents()));
    }
}
