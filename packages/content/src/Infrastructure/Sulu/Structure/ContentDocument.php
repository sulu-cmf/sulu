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

namespace Sulu\Content\Infrastructure\Sulu\Structure;

use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Persistence\Model\UserBlameInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Sulu\Content\Domain\Model\TemplateInterface;

class ContentDocument implements ExtensionBehavior, LocalizedAuthorBehavior
{
    /**
     * @var TemplateInterface
     */
    private $content;

    /**
     * @var string
     */
    private $locale;

    public function __construct(TemplateInterface $content, string $locale)
    {
        $this->content = $content;
        $this->locale = $locale;
    }

    public function getContent(): TemplateInterface
    {
        return $this->content;
    }

    /**
     * @return mixed[]
     */
    public function getExtensionsData(): array
    {
        $seo = [];
        if ($this->content instanceof SeoInterface) {
            $seo = [
                'title' => $this->content->getSeoTitle(),
                'description' => $this->content->getSeoDescription(),
                'keywords' => $this->content->getSeoKeywords(),
                'canonicalUrl' => $this->content->getSeoCanonicalUrl(),
                'noIndex' => $this->content->getSeoNoIndex(),
                'noFollow' => $this->content->getSeoNoFollow(),
                'hideInSitemap' => $this->content->getSeoHideInSitemap(),
            ];
        }

        $excerpt = [];
        if ($this->content instanceof ExcerptInterface) {
            $image = $this->content->getExcerptImage();
            $icon = $this->content->getExcerptIcon();

            $excerpt = [
                'title' => $this->content->getExcerptTitle(),
                'description' => $this->content->getExcerptDescription(),
                'more' => $this->content->getExcerptMore(),
                'categories' => $this->content->getExcerptCategoryIds(),
                'tags' => $this->content->getExcerptTagNames(),
                'images' => [
                    'ids' => $image ? [
                        $image['id'],
                    ] : [],
                ],
                'icon' => [
                    'ids' => $icon ? [
                        $icon['id'],
                    ] : [],
                ],
                'audience_targeting_groups' => [],
            ];
        }

        return [
            'seo' => $seo,
            'excerpt' => $excerpt,
        ];
    }

    /**
     * @param mixed[] $extensionData
     */
    public function setExtensionsData($extensionData): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    /**
     * @param string $name
     * @param mixed[] $data
     */
    public function setExtension($name, $data): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale($locale): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getOriginalLocale(): string
    {
        return $this->locale;
    }

    public function setOriginalLocale($locale): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getStructureType(): ?string
    {
        return $this->content->getTemplateKey();
    }

    public function setStructureType($structureType): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getStructure()
    {
        return null; // @phpstan-ignore-line
    }

    protected function createReadOnlyException(string $method): \BadMethodCallException
    {
        return new \BadMethodCallException(
            \sprintf(
                'Compatibility layer ContentDocument instances are readonly. Tried to call "%s"',
                $method
            )
        );
    }

    public function getLastModifiedEnabled(): ?bool
    {
        if ($this->content instanceof AuthorInterface) {
            return $this->content->getLastModifiedEnabled();
        }

        return null;
    }

    public function getLastModified(): ?\DateTimeImmutable
    {
        if ($this->content instanceof AuthorInterface) {
            return $this->content->getLastModified();
        }

        return null;
    }

    /**
     * @param \DateTimeImmutable|null $lastModified
     */
    public function setLastModified($lastModified): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getAuthored(): ?\DateTimeImmutable
    {
        if ($this->content instanceof AuthorInterface) {
            return $this->content->getAuthored();
        }

        return null;
    }

    public function setAuthored($authored): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getAuthor(): ?ContactInterface
    {
        if ($this->content instanceof AuthorInterface) {
            return $this->content->getAuthor();
        }

        return null;
    }

    /**
     * @param ContactInterface|null $contactId
     */
    public function setAuthor($contactId): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getCreator(): ?UserInterface
    {
        if ($this->content instanceof UserBlameInterface) {
            return $this->content->getCreator();
        }

        return null;
    }

    public function getChanger(): ?UserInterface
    {
        if ($this->content instanceof UserBlameInterface) {
            return $this->content->getChanger();
        }

        return null;
    }
}
