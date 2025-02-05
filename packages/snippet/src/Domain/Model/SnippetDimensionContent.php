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

namespace Sulu\Snippet\Domain\Model;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentTrait;
use Sulu\Content\Domain\Model\ExcerptTrait;
use Sulu\Content\Domain\Model\TemplateTrait;
//use Sulu\Content\Domain\Model\WebspaceTrait;
use Sulu\Content\Domain\Model\WorkflowTrait;

/**
 * @experimental
 */
class SnippetDimensionContent implements SnippetDimensionContentInterface
{
    use DimensionContentTrait;
    use TemplateTrait {
        setTemplateData as parentSetTemplateData;
    }
    use WorkflowTrait;
    use ExcerptTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var SnippetInterface
     */
    protected $snippet;

    /**
     * @var string|null
     */
    protected $title;

    public function __construct(SnippetInterface $snippet)
    {
        $this->snippet = $snippet;
    }

    /**
     * @return SnippetInterface
     */
    public function getResource(): ContentRichEntityInterface
    {
        return $this->snippet;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTemplateData(array $templateData): void
    {
        if (\array_key_exists('title', $templateData)
            && \is_string($templateData['title'])
        ) {
            $this->title = $templateData['title'];
        }

        $this->parentSetTemplateData($templateData);
    }

    public static function getTemplateType(): string
    {
        return SnippetInterface::TEMPLATE_TYPE;
    }

    public static function getResourceKey(): string
    {
        return SnippetInterface::RESOURCE_KEY;
    }
}
