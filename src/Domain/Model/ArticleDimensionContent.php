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

namespace Sulu\Article\Domain\Model;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateTrait;
//use Sulu\Bundle\ContentBundle\Content\Domain\Model\WebspaceTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowTrait;

/**
 * @experimental
 */
class ArticleDimensionContent implements ArticleDimensionContentInterface
{
    use AuthorTrait;
    use DimensionContentTrait;
    use ExcerptTrait;
    use RoutableTrait;
    use SeoTrait;
    use TemplateTrait {
        setTemplateData as parentSetTemplateData;
    }
    //use WebspaceTrait;
    use WorkflowTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var ArticleInterface
     */
    protected $article;

    /**
     * @var string|null
     */
    protected $title;

    public function __construct(ArticleInterface $article)
    {
        $this->article = $article;
    }

    /**
     * @return ArticleInterface
     */
    public function getResource(): ContentRichEntityInterface
    {
        return $this->article;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTemplateData(array $templateData): void
    {
        if (\array_key_exists('title', $templateData)) {
            $this->title = $templateData['title'];
        }

        $this->parentSetTemplateData($templateData);
    }

    public static function getTemplateType(): string
    {
        return ArticleInterface::TEMPLATE_TYPE;
    }

    public static function getResourceKey(): string
    {
        return ArticleInterface::RESOURCE_KEY;
    }
}
