<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Preview;

use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentInspector;

/**
 * Admin route defaults provider for home and page documents.
 *
 * Will be used to find the controller for this document types.
 */
class PageRouteDefaultsProvider implements RouteDefaultsProviderInterface
{
    public function __construct(
        private StructureMetadataFactoryInterface $structureMetadataFactory,
        private DocumentInspector $inspector,
        private StructureManagerInterface $structureManager,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * This function wont work for website mode.
     * To enable this the object would have to loaded in case the argument $object is null.
     */
    public function getByEntity($entityClass, $id, $locale, $object = null)
    {
        $metadata = $this->structureMetadataFactory->getStructureMetadata('page', $object->getStructureType());

        return [
            '_controller' => $metadata->getController(),
            'view' => $metadata->getView(),
            'object' => $object,
            'structure' => $this->documentToStructure($object),
        ];
    }

    public function isPublished($entityClass, $id, $locale)
    {
        return true;
    }

    public function supports($entityClass)
    {
        return HomeDocument::class === $entityClass
            || PageDocument::class === $entityClass
            || \is_subclass_of($entityClass, HomeDocument::class)
            || \is_subclass_of($entityClass, PageDocument::class);
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
