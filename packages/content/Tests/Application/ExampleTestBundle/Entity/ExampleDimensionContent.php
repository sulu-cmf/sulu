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

use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WebspaceInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WebspaceTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowTrait;

/**
 * @implements DimensionContentInterface<Example>
 */
class ExampleDimensionContent implements DimensionContentInterface, ExcerptInterface, SeoInterface, TemplateInterface, RoutableInterface, WorkflowInterface, AuthorInterface, WebspaceInterface, ShadowInterface
{
    use AuthorTrait;
    use DimensionContentTrait;
    use ExcerptTrait;
    use RoutableTrait;
    use SeoTrait;
    use ShadowTrait;
    use TemplateTrait {
        setTemplateData as parentSetTemplateData;
    }
    use WebspaceTrait;
    use WorkflowTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var Example
     */
    protected $example;

    /**
     * @var string|null
     */
    protected $title;

    public function __construct(Example $example)
    {
        $this->example = $example;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getResource(): ContentRichEntityInterface
    {
        return $this->example;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param array<string, mixed> $templateData
     */
    public function setTemplateData(array $templateData): void
    {
        if (\array_key_exists('title', $templateData)) {
            $this->title = \is_string($templateData['title']) ? $templateData['title'] : null;
        }

        $this->parentSetTemplateData($templateData);
    }

    public static function getTemplateType(): string
    {
        return Example::TEMPLATE_TYPE;
    }

    public static function getResourceKey(): string
    {
        return Example::RESOURCE_KEY;
    }
}
