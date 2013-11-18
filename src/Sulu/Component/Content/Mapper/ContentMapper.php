<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;

class ContentMapper extends ContainerAware implements ContentMapperInterface
{
    /**
     * base path to save the content
     * @var string
     */
    private $contentBasePath = '/cmf/contents';
    /**
     * base path to load the route
     * @var string
     */
    private $routesBasePath = '/cmf/routes';

    public function __construct($contentBasePath, $routesBasePath)
    {
        $this->contentBasePath = $contentBasePath;
        $this->routesBasePath = $routesBasePath;
    }

    /**
     * Saves the given data in the content storage
     * @param $data array The data to be saved
     * @param $templateKey string name of template
     * @param string $portalKey key of portal
     * @param $languageCode string Save data for given language
     * @param $userId int The id of the user who saves
     * @return StructureInterface
     */
    public function save($data, $templateKey, $portalKey, $languageCode, $userId)
    {
        // TODO localize
        $structure = $this->getStructure($templateKey);
        $session = $this->getSession();
        $root = $session->getRootNode();
        /** @var NodeInterface $node */
        $node = $root->addNode(
            ltrim($this->getContentBasePath(), '/') . '/' . $data['title']
        ); //TODO check better way to generate title, tree?
        $node->addMixin('sulu:content');
        $node->setProperty('sulu:template', $templateKey);

        $dateTime = new \DateTime();

        // if is new node
        if ($node->getIdentifier() == null) {

            $node->setProperty('sulu:creator', $userId);
            $node->setProperty('sulu:created', $dateTime);
        }

        $node->setProperty('sulu:changer', $userId);
        $node->setProperty('sulu:changed', $dateTime);

        $postSave = array();

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties() as $property) {
            // allow null values in data
            if (isset($data[$property->getName()])) {
                $type = $this->getContentType($property->getContentTypeName());
                $value = $data[$property->getName()];
                $property->setValue($value);
                if ($type->getType() == ContentTypeInterface::POST_SAVE) {
                    $postSave[] = array(
                        'type' => $type,
                        'property' => $property
                    );
                } else {
                    $type->set($node, $property);
                }
            }
        }

        // save node now
        $session->save();

        // set post save content types properties
        foreach ($postSave as $post) {
            try {
                /** @var ContentTypeInterface $type */
                $type = $post['type'];
                /** @var PropertyInterface $property */
                $property = $post['property'];

                $type->set($node, $property);
            } catch (Exception $ex) {
                // FIXME message for user or log entry
            }
        }

        $session->save();

        $structure->setUuid($node->getPropertyValue('jcr:uuid'));
        $structure->setCreator($node->getPropertyValue('sulu:creator'));
        $structure->setChanger($node->getPropertyValue('sulu:changer'));
        $structure->setCreated($node->getPropertyValue('sulu:created'));
        $structure->setChanged($node->getPropertyValue('sulu:changed'));

        return $structure;
    }

    /**
     * returns the data from the given id
     * @param $uuid string uuid or path to the content
     * @param string $portalKey key of portal
     * @param $languageCode string read data for given language
     * @return StructureInterface
     */
    public function load($uuid, $portalKey, $languageCode)
    {
        // TODO portal
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        return $this->loadByNode($contentNode, $languageCode);
    }

    /**
     * returns data from given path
     * @param string $resourceLocator resource locator
     * @param string $portalKey key of portal
     * @param string $languageCode
     * @return StructureInterface
     */
    public function loadByResourceLocator($resourceLocator, $portalKey, $languageCode)
    {
        // TODO portal
        $session = $this->getSession();
        $uuid = $this->getResourceLocator()->loadContentNode($resourceLocator);
        $contentNode = $session->getNodeByIdentifier($uuid);

        return $this->loadByNode($contentNode, $languageCode);
    }

    /**
     * returns data from given node
     * @param NodeInterface $contentNode
     * @param string $language
     * @return StructureInterface
     */
    private function loadByNode(NodeInterface $contentNode, $language)
    {
        $templateKey = $contentNode->getPropertyValue('sulu:template');

        // TODO localize
        $structure = $this->getStructure($templateKey);

        $structure->setUuid($contentNode->getPropertyValue('jcr:uuid'));
        $structure->setCreator($contentNode->getPropertyValue('sulu:creator'));
        $structure->setChanger($contentNode->getPropertyValue('sulu:changer'));
        $structure->setCreated($contentNode->getPropertyValue('sulu:created'));
        $structure->setChanged($contentNode->getPropertyValue('sulu:changed'));

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties() as $property) {
            $type = $this->getContentType($property->getContentTypeName());
            $type->get($contentNode, $property);
        }

        return $structure;
    }

    /**
     * returns a structure with given key
     * @param string $key key of content type
     * @return StructureInterface
     */
    protected function getStructure($key)
    {
        return $this->container->get('sulu.content.structure_manager')->getStructure($key);
    }

    /**
     * @return ResourceLocatorInterface
     */
    public function getResourceLocator()
    {
        return $this->getContentType('resource_locator');
    }

    /**
     * returns a type with given name
     * @param $name
     * @return ContentTypeInterface
     */
    protected function getContentType($name)
    {
        return $this->container->get('sulu.content.type.' . $name);
    }

    /**
     * @return SessionInterface
     */
    protected function getSession()
    {
        return $this->container->get('sulu.phpcr.session')->getSession();
    }

    /**
     * @return string
     */
    protected function getContentBasePath()
    {
        return $this->contentBasePath;
    }

    /**
     * @return string
     */
    protected function getRouteBasePath()
    {
        return $this->routesBasePath;
    }
}
