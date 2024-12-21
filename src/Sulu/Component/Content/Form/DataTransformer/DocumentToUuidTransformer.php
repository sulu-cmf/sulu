<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Form\DataTransformer;

use PHPCR\Util\UUIDHelper;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @final
 */
class DocumentToUuidTransformer implements DataTransformerInterface
{
    public function __construct(private DocumentManagerInterface $documentManager)
    {
    }

    public function transform($document): ?string
    {
        if (null === $document) {
            return null;
        }

        // TODO: Use the document inspector instead of the UUID behavior
        if (!$document instanceof UuidBehavior) {
            throw new \RuntimeException(\sprintf(
                'Document must implement UuuidBehavior to be used in a form. Got "%s"',
                \is_object($document) ? \get_class($document) : \gettype($document)
            ));
        }

        return $document->getUuid();
    }

    public function reverseTransform($uuid): ?object
    {
        if (!$uuid) {
            return null;
        }

        if (!UUIDHelper::isUuid($uuid)) {
            throw new TransformationFailedException(\sprintf(
                'Given UUID is not a UUID, given: "%s"',
                $uuid
            ));
        }

        $document = $this->documentManager->find($uuid);
        if (null === $document) {
            throw new TransformationFailedException(\sprintf(
                'Could not find document with UUID "%s"', $uuid
            ));
        }

        return $document;
    }
}
