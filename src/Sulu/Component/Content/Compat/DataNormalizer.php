<?php

namespace Sulu\Component\Content\Compat;

use Sulu\Component\Content\Metadata as LegacyStructure;
use PHPCR\Util\PathHelper;
use Sulu\Component\Content\Document\WorkflowStage;
use Symfony\Component\Form\FormEvent;

/**
 * Normalizes the legacy Sulu request data.
 * Listens to the form framework on the PRE_SUBMIT event.
 */
class DataNormalizer
{
    /**
     * Normalize incoming data from the legacy node controller
     *
     * @param mixed $data
     * @param mixed $state Translates to the workflow state
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
            $data['concreteLanguages'],
            $data['hasSub'],
            $data['published'],
            $data['enabledShadowLanguages'],
            $data['shadowEnabled'],
            $data['publishedState'],
            $data['created'],
            $data['_embedded'],
            $data['_links'],
            $data['navigation'],
            $data['id']
        );

        $normalized = array(
            'title' => self::getAndUnsetValue($data, 'title'),
            'resourceSegment' => isset($data['url']) ? $data['url'] : null,
            'redirectType' => self::getAndUnsetRedirectType($data),
            'extensions' => self::getAndUnsetValue($data, 'ext'),
            'redirectTarget' => self::getAndUnsetValue($data, 'internal_link'),
            'redirectExternal' => self::getAndUnsetValue($data, 'external'),
            'navigationContexts' => self::getAndUnsetValue($data, 'navContexts'),
            'shadowLocaleEnabled' => self::getAndUnsetValue($data, 'shadowOn'),
            'shadowLocale' => self::getAndUnsetValue($data, 'shadowBaseLanguage'),
            'structureType' => self::getAndUnsetValue($data, 'structureType'),
            'shadowLocaleEnabled' => self::getAndUnsetValue($data, 'shadowLocaleEnabled'),
            'shadowLocale' => self::getAndUnsetValue($data, 'shadowLocale'),
            'parent' => self::getAndUnsetValue($data, 'parent'),
            'workflowStage' => self::getAndUnsetValue($data, 'workflowStage'),
            'structure' => $data,
        );

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
            return null;
        }

        $nodeType = $data['nodeType'];
        unset($data['nodeType']);

        return $nodeType;
    }
}
