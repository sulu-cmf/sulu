<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Reference\Provider;

use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * @internal
 */
interface DocumentReferenceProviderInterface
{
    /**
     * @param UuidBehavior&TitleBehavior&StructureBehavior $document
     */
    public function updateReferences($document, string $locale, string $context): void;

    public function removeReferences(UuidBehavior $document, ?string $locale, string $context): void;
}
