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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\StructureInterface;

/**
 * @internal
 */
class DecoratedStructureResolver implements StructureResolverInterface
{
    public function __construct(private StructureResolverInterface $inner)
    {
    }

    /**
     * @return mixed[]
     */
    public function resolve(StructureInterface $structure, bool $loadExcerpt = true): array
    {
        $data = $this->inner->resolve($structure, $loadExcerpt);

        if (!$structure instanceof ContentStructureBridge) {
            return $data;
        }

        /** @var ContentDocument $document */
        $document = $structure->getDocument();
        $content = $document->getContent();

        if ($content instanceof AuthorInterface) {
            $data['authored'] = $content->getAuthored();
            $data['author'] = $content->getAuthor()?->getId();
        }

        return $data;
    }
}
