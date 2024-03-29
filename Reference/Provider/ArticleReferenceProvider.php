<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Reference\Provider;

use Sulu\Bundle\ArticleBundle\Controller\ArticleController;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\AbstractDocumentReferenceProvider;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;

/**
 * @final
 *
 * @internal
 */
class ArticleReferenceProvider extends AbstractDocumentReferenceProvider
{
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ReferenceRepositoryInterface $referenceRepository,
    ) {
        parent::__construct(
            $contentTypeManager,
            $structureManager,
            $extensionManager,
            $referenceRepository,
            ArticleController::DOCUMENT_TYPE
        );
    }

    public static function getResourceKey(): string
    {
        return ArticleDocument::RESOURCE_KEY;
    }

    protected function getReferenceRouterAttributes($document, string $locale): array
    {
        $referenceRouterAttributes = parent::getReferenceRouterAttributes($document, $locale);

        if (!$document instanceof WebspaceBehavior) {
            return $referenceRouterAttributes;
        }

        return \array_merge($referenceRouterAttributes, [
            'webspace' => $document->getWebspaceName(),
        ]);
    }
}
