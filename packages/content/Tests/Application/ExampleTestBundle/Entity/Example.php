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

namespace Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

/**
 * @implements ContentRichEntityInterface<ExampleDimensionContent>
 */
class Example implements ContentRichEntityInterface
{
    /**
     * @phpstan-use ContentRichEntityTrait<ExampleDimensionContent>
     */
    use ContentRichEntityTrait;

    public const RESOURCE_KEY = 'examples';
    public const TEMPLATE_TYPE = 'example';

    /**
     * @var int
     */
    public $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ExampleDimensionContent
     */
    public function createDimensionContent(): DimensionContentInterface
    {
        return new ExampleDimensionContent($this);
    }
}
