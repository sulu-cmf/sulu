<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup;

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagInterface;
use Symfony\Component\HttpFoundation\UrlHelper;

class LinkTag implements TagInterface
{
    public const VALIDATE_UNPUBLISHED = 'unpublished';

    public const VALIDATE_REMOVED = 'removed';

    public const DEFAULT_PROVIDER = 'page';

    public function __construct(
        private LinkProviderPoolInterface $linkProviderPool,
        private bool $isPreview = false,
        private UrlHelper $urlHelper,
        private ?string $providerAttribute = null
    ) {
    }

    public function parseAll(array $attributesByTag, $locale)
    {
        $contents = $this->preload($attributesByTag, $locale);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            $provider = $this->getValue($attributes, 'provider', self::DEFAULT_PROVIDER);
            $validationState = $attributes['sulu-validation-state'] ?? null;

            $hrefParts = $this->getPartsFromHref($attributes['href'] ?? null);
            $uuid = $hrefParts['uuid'];
            $anchor = $hrefParts['anchor'];
            $query = $hrefParts['query'];

            if ($uuid && \array_key_exists($provider . '-' . $uuid, $contents)) {
                $item = $contents[$provider . '-' . $uuid];

                $url = $this->urlHelper->getAbsoluteUrl($item->getUrl());

                if ($query) {
                    $url .= '?' . $query;
                }

                if ($anchor) {
                    $url .= '#' . $anchor;
                }

                $title = $item->getTitle();
                $attributes['href'] = $url;
            } elseif ($this->isPreview && self::VALIDATE_UNPUBLISHED === $validationState) {
                // render anchor without href to keep styling even if target is not published in preview
                $title = $this->getContent($attributes);
                $attributes['href'] = null;
            } else {
                // Completely remove the tag if this attribute is set
                if ($attributes['remove-if-not-exists'] ?? false) {
                    $result[$tag] = '';
                } else {
                    // only render text instead of anchor to prevent dead links on website
                    $result[$tag] = $this->getContent($attributes);
                }

                continue;
            }

            $htmlAttributes = \array_map(
                function($value, $name) use ($attributes) {
                    if (empty($value) || \in_array($name, ['content', 'sulu-validation-state', 'remove-if-not-exists'])) {
                        return null;
                    }

                    if ('provider' === $name) {
                        if (null === $this->providerAttribute || \array_key_exists($this->providerAttribute, $attributes)) {
                            return null;
                        }

                        return \sprintf('%s="%s"', $this->providerAttribute, $value);
                    }

                    return \sprintf('%s="%s"', $name, $value);
                },
                $attributes,
                \array_keys($attributes)
            );

            $result[$tag] = \sprintf(
                '<a %s>%s</a>',
                \implode(' ', \array_filter($htmlAttributes)),
                $this->getValue($attributes, 'content', $title)
            );
        }

        return $result;
    }

    public function validateAll(array $attributesByTag, $locale)
    {
        $items = $this->preload($attributesByTag, $locale, false);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            $provider = $this->getValue($attributes, 'provider', self::DEFAULT_PROVIDER);
            $uuid = $this->getPartsFromHref($attributes['href'] ?? null)['uuid'];

            if (!$uuid || !\array_key_exists($provider . '-' . $uuid, $items)) {
                $result[$tag] = self::VALIDATE_REMOVED;
            } elseif (!$items[$provider . '-' . $uuid]->isPublished()) {
                $result[$tag] = self::VALIDATE_UNPUBLISHED;
            }
        }

        return $result;
    }

    /**
     * Return items for given attributes.
     *
     * @param array $attributesByTag
     * @param string $locale
     * @param bool $published
     *
     * @return LinkItem[]
     */
    private function preload($attributesByTag, $locale, $published = true)
    {
        $uuidsByType = [];
        foreach ($attributesByTag as $attributes) {
            $provider = $this->getValue($attributes, 'provider', self::DEFAULT_PROVIDER);
            if (!\array_key_exists($provider, $uuidsByType)) {
                $uuidsByType[$provider] = [];
            }

            $uuid = $this->getPartsFromHref($attributes['href'] ?? null)['uuid'];

            if ($uuid) {
                $uuidsByType[$provider][] = $uuid;
            }
        }

        $result = [];
        foreach ($uuidsByType as $provider => $uuids) {
            $items = $this->linkProviderPool->getProvider($provider)->preload(
                \array_unique($uuids),
                $locale,
                $published
            );

            foreach ($items as $item) {
                $result[$provider . '-' . $item->getId()] = $item;
            }
        }

        return $result;
    }

    /**
     * Returns attribute identified by name or default if not exists.
     *
     * @param string $name
     */
    private function getValue(array $attributes, $name, $default = null)
    {
        if (\array_key_exists($name, $attributes) && !empty($attributes[$name])) {
            return $attributes[$name];
        }

        return $default;
    }

    /**
     * Returns content or title of given attributes.
     *
     * @return string
     */
    private function getContent(array $attributes)
    {
        if (\array_key_exists('content', $attributes)) {
            return $attributes['content'];
        }

        return $this->getValue($attributes, 'title', '');
    }

    /**
     * @param mixed $href
     *
     * @return array{uuid: string|null, query: string|null, anchor: string|null}
     */
    private function getPartsFromHref($href): array
    {
        $href = (string) $href ?: null;

        /** @var string[] $hrefParts */
        $hrefParts = $href ? \explode('#', $href, 2) : [];
        $anchor = $hrefParts[1] ?? null;

        $hrefParts = $hrefParts ? \explode('?', $hrefParts[0], 2) : [];
        $uuid = $hrefParts[0] ?? null;
        $query = $hrefParts[1] ?? null;

        return [
            'uuid' => $uuid,
            'anchor' => $anchor,
            'query' => $query,
        ];
    }
}
