<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper\Event;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is thrown when a node is deleted.
 *
 * @deprecated use events of DocumentManager instead
 */
class ContentNodeDeleteEvent extends Event
{
    /**
     * @param string $webspace
     */
    public function __construct(
        private ContentMapperInterface $contentMapper,
        private SuluNodeHelper $nodeHelper,
        private NodeInterface $node,
        private $webspace,
    ) {
    }

    /**
     * Return the structure which was deleted.
     *
     * @return StructureInterface
     */
    public function getStructure($locale)
    {
        return $this->contentMapper->loadShallowStructureByNode($this->node, $locale, $this->webspace);
    }

    /**
     * Return all structures (i.e. for for each language).
     *
     * @return MetadataInterface[]
     */
    public function getStructures()
    {
        $structures = [];
        foreach ($this->nodeHelper->getLanguagesForNode($this->node) as $locale) {
            $structures[] = $this->getStructure($locale);
        }

        return $structures;
    }

    /**
     * Return the PHPCR node for the structure that was deleted.
     *
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }
}
