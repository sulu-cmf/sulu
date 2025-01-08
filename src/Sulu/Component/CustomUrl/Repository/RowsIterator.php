<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Repository;

use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;

/**
 * Converts rows into simple data-arrays.
 *
 * @extends \ArrayIterator<CustomUrl>
 */
class RowsIterator extends \ArrayIterator
{
    /**
     * @var array<int, Content>
     */
    private $targets;

    /**
     * @param array<Content> $results
     */
    public function __construct(
        array $results,
        array $targets,
        private GeneratorInterface $generator,
    ) {
        parent::__construct($results);

        $this->targets = [];
        foreach ($targets as $target) {
            $this->targets[$target->getId()] = $target;
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        /** @var CustomUrl $row */
        $row = parent::current();
        $result = $row->toArray();

        $result['targetTitle'] = '';
        if (\array_key_exists($result['targetDocument'], $this->targets)) {
            $result['targetTitle'] = $this->targets[$result['targetDocument']]['title'];
        }
        $result['customUrl'] = $this->generator->generate($result['baseDomain'], $result['domainParts']);

        $result['creatorFullName'] = $row->getCreator()?->getFullName();
        $result['changerFullName'] = $row->getChanger()?->getFullName();

        return $result;
    }
}
