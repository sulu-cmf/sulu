<?php

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Compat\Structure\Page;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;

/**
 * Class that "resolves" the view data for a given structure.
 */
class StructureResolver implements StructureResolverInterface
{
    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param StructureManagerInterface $structureManager
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        StructureManagerInterface $structureManager
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(StructureInterface $structure)
    {
        $data = array(
            'view' => array(),
            'content' => array(),
            'uuid' => $structure->getUuid(),
            'creator' => $structure->getCreator(),
            'changer' => $structure->getChanger(),
            'created' => $structure->getCreated(),
            'changed' => $structure->getChanged(),
            'template' => $structure->getKey(),
            'path' => $structure->getPath(),
        );

        if ($structure instanceof PageBridge) {
            $data['extension'] = $structure->getExt()->toArray();
            $data['urls'] = $structure->getUrls();
            $data['published'] = $structure->getPublished();

            foreach ($data['extension'] as $name => $value) {
                $extension = $this->structureManager->getExtension($structure->getKey(), $name);
                $data['extension'][$name] = $extension->getContentData($value);
            }
        }

        foreach ($structure->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $data['view'][$property->getName()] = $contentType->getViewData($property);
            $data['content'][$property->getName()] = $contentType->getContentData($property);
        }


        return $data;
    }
}
