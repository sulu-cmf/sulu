<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Search;

use Massive\Bundle\SearchBundle\Search\Converter\ConverterInterface;
use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\Field;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;

/**
 * Converts tag names into id array.
 */
class TagsConverter implements ConverterInterface
{
    public function __construct(private TagManagerInterface $tagManager)
    {
    }

    public function convert($value/*, Document $document = null*/)
    {
        if (null === $value) {
            return null;
        }

        if (\func_num_args() < 2 || !(\func_get_arg(1) instanceof Document)) {
            // Preserve backward compatibility
            return $this->tagManager->resolveTagNames($value);
        }

        $resultValue = null;
        $fields = [];

        if (\is_string($value)) {
            $tag = $this->tagManager->findByName($value);
            $resultValue = $tag->getId();

            $fields = [
                new Field('id', $tag->getId()),
                new Field('name', $tag->getName()),
            ];
        }

        if (\is_array($value)) {
            $ids = $this->tagManager->resolveTagNames($value);
            $resultValue = $ids;
            $tags = \array_combine($ids, $value);

            if (false !== $tags) {
                $index = 0;
                foreach ($tags as $id => $tagName) {
                    $fields[] = new Field($index . '#id', $id);
                    $fields[] = new Field($index . '#name', $tagName);
                    ++$index;
                }
            }
        }

        return [
            'value' => $resultValue,
            'fields' => $fields,
        ];
    }
}
