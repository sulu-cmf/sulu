<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use Symfony\Component\Form\FormEvent;

/**
 * Normalizes the legacy Sulu request data.
 * Listens to the form framework on the PRE_SUBMIT event.
 */
class DataNormalizer
{
    /**
     * Normalize incoming data from the legacy node controller.
     */
    public static function normalize(FormEvent $event)
    {
        $data = $event->getData();

        unset(
            $data['type'],
            $data['creator'],
            $data['linked'],
            $data['changer'],
            $data['breadcrumb'],
            $data['originTemplate'],
            $data['changed'],
            $data['changer'],
            $data['path'],
            $data['nodeState'],
            $data['internal'],
            $data['availableLocales'],
            $data['contentLocales'],
            $data['hasSub'],
            $data['published'],
            $data['shadowLocales'],
            $data['shadowEnabled'],
            $data['publishedState'],
            $data['created'],
            $data['_embedded'],
            $data['_links'],
            $data['navigation'],
            $data['id'],
            $data['parentUuid']
        );

        $normalized = [
            'title' => self::getAndUnsetValue($data, 'title'),
            'resourceSegment' => isset($data['url']) ? $data['url'] : null,
            'redirectType' => self::getAndUnsetRedirectType($data),
            'extensions' => self::getAndUnsetValue($data, 'ext'),
            'redirectTarget' => self::getAndUnsetValue($data, 'internal_link'),
            'redirectExternal' => self::getAndUnsetValue($data, 'external'),
            'navigationContexts' => self::getAndUnsetValue($data, 'navContexts'),
            'shadowLocale' => self::getAndUnsetValue($data, 'shadowBaseLanguage'),
            'structureType' => self::getAndUnsetValue($data, 'template'),
            'shadowLocaleEnabled' => self::getAndUnsetValue($data, 'shadowOn') ? true : false,
            'parent' => self::getAndUnsetValue($data, 'parent'),
            'workflowStage' => self::getAndUnsetValue($data, 'workflowStage'),
            'lastModified' => $data['lastModifiedEnabled'] ?? false ? self::getAndUnsetValue($data, 'lastModified') : null,
            'authored' => self::getAndUnsetValue($data, 'authored'),
            'author' => self::getAndUnsetValue($data, 'author'),
            'structure' => $data,
        ];

        // Set to false if lastModifiedEnabled exists but should be null, because if set to null it will be unset.
        if (\array_key_exists('lastModifiedEnabled', $data) && false === $data['lastModifiedEnabled']) {
            $normalized['lastModified'] = false;
        }

        foreach ($normalized as $key => $value) {
            if (null === $value) {
                unset($normalized[$key]);
            }
        }

        $event->setData($normalized);
    }

    private static function getAndUnsetValue(&$data, $key)
    {
        $value = null;

        if (isset($data[$key])) {
            $value = $data[$key];
            unset($data[$key]);
        }

        return $value;
    }

    private static function getAndUnsetRedirectType(&$data)
    {
        if (!isset($data['nodeType'])) {
            return;
        }

        $nodeType = $data['nodeType'];
        unset($data['nodeType']);

        return $nodeType;
    }
}
