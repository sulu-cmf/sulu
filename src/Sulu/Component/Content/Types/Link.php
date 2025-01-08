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

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * Link selection content type for linking to different providers.
 */
class Link extends SimpleContentType
{
    public const LINK_TYPE_EXTERNAL = 'external';

    public function __construct(private LinkProviderPoolInterface $providerPool)
    {
        parent::__construct('Link');
    }

    /**
     * @param mixed[] $value
     */
    protected function encodeValue($value): string
    {
        return (string) \json_encode($value, \defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0);
    }

    /**
     * @param string|null $value
     *
     * @return mixed[]
     */
    protected function decodeValue($value): array
    {
        if (null === $value) {
            return [];
        }

        return \json_decode($value, true, 512, \defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0);
    }

    /**
     * @return mixed[]
     */
    public function getViewData(PropertyInterface $property): array
    {
        $value = $property->getValue();

        if (!$value) {
            return [];
        }

        $result = [
            'provider' => $value['provider'] ?? null,
            'locale' => $value['locale'],
        ];

        if (isset($value['target'])) {
            $result['target'] = $value['target'];
        }

        if (isset($value['title'])) {
            $result['title'] = $value['title'];
        }

        if (isset($value['rel'])) {
            $result['rel'] = $value['rel'];
        }

        $result['href'] = $value['href'] ?? null;

        return $result;
    }

    public function getContentData(PropertyInterface $property): ?string
    {
        $value = $property->getValue();
        $locale = $property->getStructure()->getLanguageCode();

        if (!$value || !isset($value['provider'])) {
            return null;
        }

        if (self::LINK_TYPE_EXTERNAL === $value['provider']) {
            return $value['href'];
        }

        $provider = $this->providerPool->getProvider($value['provider']);

        $linkItems = $provider->preload([$value['href']], $locale, true);

        if (0 === \count($linkItems)) {
            return null;
        }

        $url = \reset($linkItems)->getUrl();
        if (isset($value['query'])) {
            $url = \sprintf('%s?%s', $url, $value['query']);
        }
        if (isset($value['anchor'])) {
            $url = \sprintf('%s#%s', $url, $value['anchor']);
        }

        return $url;
    }

    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ): void {
        $property->setValue(\json_decode($value, true));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
