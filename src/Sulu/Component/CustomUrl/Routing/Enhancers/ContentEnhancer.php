<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Routing\Enhancers;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

/**
 * Create a structure from custom-url target.
 */
class ContentEnhancer extends AbstractEnhancer
{
    public function __construct(
        private DocumentInspector $inspector,
        private StructureManagerInterface $structureManager,
    ) {
    }

    protected function doEnhance(
        CustomUrlBehavior $customUrl,
        Webspace $webspace,
        array $defaults,
        Request $request
    ) {
        return ['_structure' => $this->documentToStructure($customUrl->getTargetDocument())];
    }

    protected function supports(CustomUrlBehavior $customUrl)
    {
        return !$customUrl->isRedirect() && null !== $customUrl->getTargetDocument();
    }

    /**
     * Return a structure bridge corresponding to the given document.
     *
     * @return PageBridge
     */
    protected function documentToStructure(BasePageDocument $document)
    {
        $structure = $this->inspector->getStructureMetadata($document);
        $documentAlias = $this->inspector->getMetadata($document)->getAlias();

        $structureBridge = $this->structureManager->wrapStructure($documentAlias, $structure);
        $structureBridge->setDocument($document);

        return $structureBridge;
    }
}
