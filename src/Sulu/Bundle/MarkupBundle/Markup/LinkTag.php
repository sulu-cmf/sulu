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

    /**
     * @var LinkProviderPoolInterface
     */
    private $linkProviderPool;

    /**
     * @var bool
     */
    private $isPreview;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    public function __construct(
        LinkProviderPoolInterface $linkProviderPool,
        bool $isPreview = false,
        ?UrlHelper $urlHelper = null
    ) {
        $this->linkProviderPool = $linkProviderPool;
        $this->isPreview = $isPreview;
        $this->urlHelper = $urlHelper;

        if (null === $this->urlHelper) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.3',
                'Instantiating the LinkTag class without the $urlHelper argument is deprecated.'
            );
        }
    }

    public function parseAll(array $attributesByTag, $locale)
    {
        $contents = $this->preload($attributesByTag, $locale);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            $provider = $this->getValue($attributes, 'provider', self::DEFAULT_PROVIDER);
            $validationState = $attributes['sulu-validation-state'] ?? null;

            $hrefParts = $this->getUuidAndAnchorFromHref($attributes['href'] ?? null);
            $uuid = $hrefParts['uuid'];
            $anchor = $hrefParts['anchor'];

            if ($uuid && \array_key_exists($provider . '-' . $uuid, $contents)) {
                $item = $contents[$provider . '-' . $uuid];

                $url = $item->getUrl();
                if ($this->urlHelper) {
                    $url = $this->urlHelper->getAbsoluteUrl($url);
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
                // only render text instead of anchor to prevent dead links on website
                $result[$tag] = $this->getContent($attributes);

                continue;
            }

            $htmlAttributes = \array_map(
                function($value, $name) {
                    if (\in_array($name, ['provider', 'content', 'sulu-validation-state']) || empty($value)) {
                        return null;
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
            $uuid = $this->getUuidAndAnchorFromHref($attributes['href'] ?? null)['uuid'];

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

            $uuid = $this->getUuidAndAnchorFromHref($attributes['href'] ?? null)['uuid'];

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
     * @return array{uuid: string|null, anchor: string|null}
     */
    private function getUuidAndAnchorFromHref($href): array
    {
        $href = (string) $href ?: null;

        /** @var string[] $hrefParts */
        $hrefParts = $href ? \explode('#', $href, 2) : [];

        $uuid = $hrefParts[0] ?? null;
        $anchor = $hrefParts[1] ?? null;

        return [
            'uuid' => $uuid,
            'anchor' => $anchor,
        ];
    }
}
