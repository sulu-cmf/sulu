<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Jackalope\Query\Row;
use PHPCR\NodeInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\Content\BreadcrumbItem;
use Sulu\Component\Content\Compat\Property as LegacyProperty;
use Sulu\Component\Content\Compat\Structure as LegacyStructure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedLastModifiedBehavior;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Subscriber\WorkflowStageSubscriber;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Exception\InvalidOrderPositionException;
use Sulu\Component\Content\Exception\TranslatedNodeNotFoundException;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Mapper\Event\ContentNodeEvent;
use Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;

/**
 * Maps content nodes to phpcr nodes with content types and provides utility function to handle content nodes.
 *
 * @deprecated since 1.0-? use the DocumentManager instead
 */
class ContentMapper implements ContentMapperInterface
{
    /**
     * @var Cache
     */
    private $extensionDataCache;

    /**
     * @param mixed[] $permissions
     */
    public function __construct(
        private DocumentManagerInterface $documentManager,
        private WebspaceManagerInterface $webspaceManager,
        private FormFactoryInterface $formFactory,
        private DocumentInspector $inspector,
        private PropertyEncoder $encoder,
        private StructureManagerInterface $structureManager,
        private ExtensionManagerInterface $extensionManager,
        private ContentTypeManagerInterface $contentTypeManager,
        private SessionManagerInterface $sessionManager,
        private EventDispatcherInterface $eventDispatcher,
        private ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
        private NamespaceRegistry $namespaceRegistry,
        private AccessControlManagerInterface $accessControlManager,
        private $permissions,
        private Security|SymfonyCoreSecurity|null $security = null,
    ) {
    }

    /**
     * TODO: Refactor this .. this should be handled in a listener or in the form, or something
     * {@inheritdoc}
     */
    public function saveExtension(
        $uuid,
        $data,
        $extensionName,
        $webspaceKey,
        $locale,
        $userId
    ) {
        $document = $this->loadDocument(
            $uuid,
            $locale,
            [
                'exclude_ghost' => true,
            ]
        );

        if (null === $document) {
            throw new TranslatedNodeNotFoundException($uuid, $locale);
        }

        if (!$document instanceof ExtensionBehavior) {
            throw new \RuntimeException(
                \sprintf(
                    'Document of class "%s" must implement the ExtensionableBehavior if it is to be extended',
                    \get_class($document)
                )
            );
        }

        // save data of extensions
        $extension = $this->extensionManager->getExtension((string) $document->getStructureType(), $extensionName);
        $node = $this->inspector->getNode($document);

        $extension->save($node, $data, $webspaceKey, $locale);
        $extensionData = $extension->load($node, $webspaceKey, $locale);

        $document->setExtension($extension->getName(), $extensionData);

        $this->documentManager->flush();

        $structure = $this->documentToStructure($document);

        $event = new ContentNodeEvent($node, $structure);
        $this->eventDispatcher->dispatch($event, ContentEvents::NODE_POST_SAVE);

        return $structure;
    }

    public function loadByParent(
        $uuid,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $flat = true,
        $ignoreExceptions = false,
        $excludeGhosts = false
    ) {
        $parent = null;
        $options = ['load_ghost_content' => true];
        if ($uuid) {
            $parent = $this->documentManager->find($uuid, $languageCode, $options);
        }

        if (null === $parent) {
            $parent = $this->getContentDocument($webspaceKey, $languageCode, $options);
        }

        $fetchDepth = -1;

        if (false === $flat) {
            $fetchDepth = $depth;
        }

        $children = $this->inspector->getChildren($parent, $options);
        $children = $this->documentsToStructureCollection(
            $children->toArray(),
            [
                'exclude_ghost' => $excludeGhosts,
            ]
        );

        if ($flat) {
            foreach ($children as $child) {
                if (null === $depth || $depth > 1) {
                    $childChildren = $this->loadByParent(
                        $child->getUuid(),
                        $webspaceKey,
                        $languageCode,
                        $depth - 1,
                        $flat,
                        $ignoreExceptions,
                        $excludeGhosts
                    );
                    $children = \array_merge($children, $childChildren);
                }
            }
        }

        return $children;
    }

    public function load($uuid, $webspaceKey, $locale, $loadGhostContent = false)
    {
        $document = $this->documentManager->find(
            $uuid,
            $locale,
            [
                'load_ghost_content' => $loadGhostContent,
            ]
        );

        if ($document instanceof UnknownDocument) {
            throw new DocumentNotFoundException();
        }

        return $this->documentToStructure($document);
    }

    public function loadStartPage($webspaceKey, $locale)
    {
        $startPage = $this->getContentDocument($webspaceKey, $locale);
        $startPage->setWorkflowStage(WorkflowStage::PUBLISHED);
        $startPage->setNavigationContexts([]);

        return $this->documentToStructure($startPage);
    }

    public function loadBySql2($sql2, $locale, $webspaceKey, $limit = null)
    {
        $query = $this->documentManager->createQuery($sql2, $locale);
        if (null !== $limit) {
            $query->setMaxResults($limit);
        }

        $documents = $query->execute();

        return $this->documentsToStructureCollection($documents, null);
    }

    public function loadByQuery(
        QueryInterface $query,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        $options = [
            'exclude_ghost' => $excludeGhost,
            'load_ghost_content' => $loadGhostContent,
        ];

        $documents = $this->documentManager->createQuery($query, $locale, $options)->execute();

        return $this->documentsToStructureCollection($documents, $options);
    }

    public function loadNodeAndAncestors(
        $uuid,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $excludeShadow = true
    ) {
        $document = $this->loadDocument(
            $uuid,
            $locale,
            $options = [
                'load_ghost_content' => true,
                'exclude_ghost' => $excludeGhost,
                'exclude_shadow' => $excludeShadow,
            ],
            false
        );

        if (null === $document) {
            return [];
        }

        $documents = [];
        if (!$this->optionsShouldExcludeDocument($document, $options)) {
            $documents[] = $document;
        }

        if ($document instanceof HomeDocument) {
            return $this->documentsToStructureCollection($documents, $options);
        }

        while ($document) {
            $parentDocument = $this->inspector->getParent($document);
            $documents[] = $parentDocument;
            if ($parentDocument instanceof HomeDocument) {
                return $this->documentsToStructureCollection($documents, $options);
            }
            $document = $parentDocument;
        }

        throw new \RuntimeException(
            \sprintf(
                'Did not traverse an instance of HomeDocument when searching for desendants of document "%s"',
                $uuid
            )
        );
    }

    /**
     * Load/hydrate a shalow structure with the given node.
     * Shallow structures do not have content properties / extensions
     * hydrated.
     *
     * @param string $localization
     * @param string $webspaceKey
     *
     * @return StructureInterface
     */
    public function loadShallowStructureByNode(NodeInterface $contentNode, $localization, $webspaceKey)
    {
        $document = $this->documentManager->find($contentNode->getPath(), $localization);

        return $this->documentToStructure($document);
    }

    public function loadByNode(
        NodeInterface $node,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false,
        $excludeShadow = true
    ) {
        $document = $this->loadDocument(
            $node->getIdentifier(),
            $locale,
            [
                'load_ghost_content' => $loadGhostContent,
                'exclude_ghost' => $excludeGhost,
                'exclude_shadow' => $excludeShadow,
            ]
        );

        return $this->documentToStructure($document);
    }

    public function loadBreadcrumb($uuid, $locale, $webspaceKey)
    {
        $document = $this->documentManager->find($uuid, $locale);

        $documents = [];
        $contentDocument = $this->getContentDocument($webspaceKey, $locale);
        $contentDepth = $this->inspector->getDepth($contentDocument);
        $document = $this->inspector->getParent($document);
        $documentDepth = $this->inspector->getDepth($document);

        while ($document instanceof StructureBehavior && $documentDepth >= $contentDepth) {
            $documents[] = $document;

            $document = $this->inspector->getParent($document);
            $documentDepth = $this->inspector->getDepth($document);
        }

        $items = [];
        foreach ($documents as $document) {
            $items[] = new BreadcrumbItem(
                $this->inspector->getDepth($document) - $contentDepth,
                $this->inspector->getUuid($document),
                $document->getTitle()
            );
        }

        $items = \array_reverse($items);

        return $items;
    }

    public function delete($uuid, $webspaceKey/*, bool $forceRemoveChildren = false*/)
    {
        $forceRemoveChildren = \func_num_args() >= 3 ? (bool) \func_get_arg(2) : false;

        $document = $this->documentManager->find($uuid);
        $this->documentManager->remove($document, [
            'force_remove_children' => $forceRemoveChildren,
        ]);
        $this->documentManager->flush();
    }

    public function copyLanguage(
        $uuid,
        $userId,
        $webspaceKey,
        $srcLocale,
        $destLocales,
        $structureType = LegacyStructure::TYPE_PAGE
    ) {
        @trigger_deprecation(
            'sulu/sulu',
            '2.3',
            'The ContentMapperInterface::copyLanguage method is deprecated and will be removed in the future.'
            . ' Use DocumentManagerInterface::copyLocale instead.'
        );

        if (!\is_array($destLocales)) {
            $destLocales = [$destLocales];
        }

        $document = $this->documentManager->find($uuid, $srcLocale);

        $resourceLocatorStrategy = null;
        if ($document instanceof ResourceSegmentBehavior) {
            $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);
        }

        $parentUuid = null;
        if ($document instanceof ParentBehavior) {
            $parentDocument = $this->inspector->getParent($document);
            $parentUuid = $this->inspector->getUuid($parentDocument);
        }

        foreach ($destLocales as $destLocale) {
            $destDocument = $this->documentManager->find(
                $uuid,
                $destLocale
            );
            $destDocument->setLocale($destLocale);
            $destDocument->setTitle($document->getTitle());
            $destDocument->setStructureType($document->getStructureType());
            $destDocument->getStructure()->bind($document->getStructure()->toArray());

            if ($document instanceof WorkflowStageBehavior) {
                $documentAccessor = new DocumentAccessor($destDocument);
                $documentAccessor->set(WorkflowStageSubscriber::PUBLISHED_FIELD, null);
            }

            if ($document instanceof ExtensionBehavior) {
                $destDocument->setExtensionsData($document->getExtensionsData());
            }

            // TODO: This can be removed if RoutingAuto replaces the ResourceLocator code.
            if ($destDocument instanceof ResourceSegmentBehavior) {
                $resourceLocator = $resourceLocatorStrategy->generate(
                    $destDocument->getTitle(),
                    $parentUuid,
                    $webspaceKey,
                    $destLocale
                );

                $destDocument->setResourceSegment($resourceLocator);
            }

            $this->documentManager->persist($destDocument, $destLocale, ['omit_modified_domain_event' => true]);
        }
        $this->documentManager->flush();

        return $this->documentToStructure($document);
    }

    public function orderBefore($uuid, $beforeUuid, $userId, $webspaceKey, $locale)
    {
        $document = $this->documentManager->find($uuid, $locale);
        $this->documentManager->reorder($document, $beforeUuid);
        $this->documentManager->persist(
            $document,
            $locale,
            [
                'user' => $userId,
            ]
        );

        return $this->documentToStructure($document);
    }

    /**
     * TODO: Move this logic to the DocumentManager
     * {@inheritdoc}
     */
    public function orderAt($uuid, $position, $userId, $webspaceKey, $locale)
    {
        $document = $this->documentManager->find($uuid, $locale);

        $parentDocument = $this->inspector->getParent($document);
        $siblingDocuments = $this->inspector->getChildren($parentDocument);

        $siblings = \array_values($siblingDocuments->toArray()); // get indexed array
        $countSiblings = \count($siblings);
        $currentPosition = \array_search($document, $siblings) + 1;

        if ($countSiblings < $position || $position <= 0) {
            throw new InvalidOrderPositionException(
                \sprintf(
                    'Cannot order node "%s" at out-of-range position "%s", must be >= 0 && < %d"',
                    $this->inspector->getPath($document),
                    $position,
                    $countSiblings
                )
            );
        }

        if ($position === $countSiblings) {
            // move to the end
            $this->documentManager->reorder($document, null);
        } else {
            if ($currentPosition < $position) {
                $targetSibling = $siblings[$position];
                $this->documentManager->reorder($document, $targetSibling->getUuid());
            } elseif ($currentPosition > $position) {
                $targetSibling = $siblings[$position - 1];
                $this->documentManager->reorder($document, $targetSibling->getUuid());
            }
        }

        $this->documentManager->flush();

        return $this->documentToStructure($document);
    }

    /**
     * Return the resource locator content type.
     *
     * @return ResourceLocator
     */
    public function getResourceLocator()
    {
        return $this->contentTypeManager->get('resource_locator');
    }

    /**
     * Return the content document (aka the home page).
     *
     * @param string $webspaceKey
     *
     * @return BasePageDocument|SnippetDocument
     */
    private function getContentDocument($webspaceKey, $locale, array $options = [])
    {
        return $this->documentManager->find(
            $this->sessionManager->getContentPath($webspaceKey),
            $locale,
            $options
        );
    }

    /**
     * Return the node in the content repository which contains all of the routes.
     *
     * @param string $webspaceKey
     * @param string $locale
     * @param string $segment
     *
     * @return NodeInterface
     */
    protected function getRootRouteNode($webspaceKey, $locale, $segment)
    {
        return $this->documentManager->find(
            $this->sessionManager->getRoutePath($webspaceKey, $locale, $segment)
        );
    }

    public function convertQueryResultToArray(
        QueryResultInterface $queryResult,
        $webspaceKey,
        $locales,
        $fields,
        $maxDepth,
        $onlyPublished = true,
        $permission = null
    ) {
        $rootDepth = \substr_count($this->sessionManager->getContentPath($webspaceKey), '/');

        $result = [];
        foreach ($locales as $locale) {
            foreach ($queryResult->getRows() as $row) {
                $pageDepth = \substr_count($row->getPath('page'), '/') - $rootDepth;

                if (null === $maxDepth || $maxDepth < 0 || ($maxDepth > 0 && $pageDepth <= $maxDepth)) {
                    $item = $this->rowToArray($row, $locale, $webspaceKey, $fields, $onlyPublished, $permission);

                    if (false === $item || \in_array($item, $result)) {
                        continue;
                    }

                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * converts a query row to an array.
     */
    private function rowToArray(
        Row $row,
        $locale,
        $webspaceKey,
        $fields,
        $onlyPublished = true,
        $permission = null
    ) {
        // reset cache
        $this->initializeExtensionCache();
        $templateName = $this->encoder->localizedSystemName('template', $locale);

        // check and determine shadow-nodes
        $node = $row->getNode('page');

        try {
            $document = $this->documentManager->find($node->getIdentifier(), $locale);
        } catch (StructureTypeNotFoundException $e) {
            return false;
        }

        $originalDocument = $document;

        if (!$node->hasProperty($templateName) && !$node->hasProperty('template')) {
            return false;
        }

        if ($document instanceof SecurityBehavior
            && $this->security
            && $permission
        ) {
            $permissionKey = \array_search($permission, $this->permissions);
            $documentWebspaceKey = $document->getWebspaceName();
            $documentWebspace = $this->webspaceManager->findWebspaceByKey($documentWebspaceKey);

            if ($documentWebspace && $documentWebspace->hasWebsiteSecurity()) {
                $documentWebspaceSecurity = $documentWebspace->getSecurity();
                $permissions = $this->accessControlManager->getUserPermissionByArray(
                    $document->getLocale(),
                    PageAdmin::SECURITY_CONTEXT_PREFIX . $documentWebspaceKey,
                    $document->getPermissions(),
                    $this->security->getUser(),
                    $documentWebspaceSecurity->getSystem()
                );

                if (isset($permissions[$permissionKey]) && !$permissions[$permissionKey]) {
                    return false;
                }
            }
        }

        $redirectType = null;
        $url = null;
        if ($document instanceof RedirectTypeBehavior) {
            $redirectType = $document->getRedirectType();

            if (RedirectType::INTERNAL === $redirectType) {
                $target = $document->getRedirectTarget();

                if (!$target instanceof ResourceSegmentBehavior) {
                    return false;
                }

                $url = $target->getResourceSegment();

                $document = $target;
                $node = $this->inspector->getNode($document);
            } elseif (RedirectType::EXTERNAL === $redirectType) {
                $url = $document->getRedirectExternal();
            }
        }

        $originLocale = $locale;
        if ($document instanceof ShadowLocaleBehavior) {
            $locale = $document->isShadowLocaleEnabled() ? $document->getShadowLocale() : $originLocale;
        }

        $nodeState = null;
        if ($document instanceof WorkflowStageBehavior) {
            $nodeState = $document->getWorkflowStage();
        }

        // if page is not piblished ignore it
        if ($onlyPublished && WorkflowStage::PUBLISHED !== $nodeState) {
            return false;
        }

        if ($document instanceof ResourceSegmentBehavior && !isset($url)) {
            $url = $document->getResourceSegment();
        }

        // generate field data
        $fieldsData = $this->getFieldsData(
            $row,
            $node,
            $document,
            $fields[$originLocale],
            $document->getStructureType(),
            $webspaceKey,
            $locale
        );

        $structureType = $document->getStructureType();
        $shortPath = $this->inspector->getContentPath($originalDocument);

        $documentData = [
            'id' => $originalDocument->getUuid(),
            'uuid' => $originalDocument->getUuid(),
            'nodeType' => $redirectType,
            'path' => $shortPath,
            'changed' => $document->getChanged(),
            'changer' => $document->getChanger(),
            'created' => $document->getCreated(),
            'publishedState' => WorkflowStage::PUBLISHED === $nodeState,
            'published' => $document->getPublished(),
            'creator' => $document->getCreator(),
            'title' => $originalDocument->getTitle(),
            'locale' => $locale,
            'webspaceKey' => $this->inspector->getWebspace($document),
            'template' => $structureType,
            'parent' => $this->inspector->getParent($document)->getUuid(),
        ];

        if (isset($url)) {
            $documentData['url'] = $url;
            $documentData['urls'] = $this->inspector->getLocalizedUrlsForPage($document);
        }

        if ($document instanceof LocalizedLastModifiedBehavior) {
            $documentData['lastModified'] = $document->getLastModified();
        }

        if ($document instanceof LocalizedAuthorBehavior) {
            $documentData['author'] = $document->getAuthor();
            $documentData['authored'] = $document->getAuthored();
        }

        if ($document instanceof OrderBehavior) {
            $documentData['order'] = $document->getSuluOrder();
        }

        return \array_merge($documentData, $fieldsData);
    }

    /**
     * Return extracted data (configured by fields array) from node.
     */
    private function getFieldsData(
        Row $row,
        NodeInterface $node,
        $document,
        $fields,
        $templateKey,
        $webspaceKey,
        $locale
    ) {
        $fieldsData = [];
        foreach ($fields as $field) {
            // determine target for data in result array
            if (isset($field['target'])) {
                if (!isset($fieldsData[$field['target']])) {
                    $fieldsData[$field['target']] = [];
                }
                $target = &$fieldsData[$field['target']];
            } else {
                $target = &$fieldsData;
            }

            // create target
            if (!isset($target[$field['name']])) {
                $target[$field['name']] = '';
            }
            if (null !== ($data = $this->getFieldData(
                $field,
                $row,
                $node,
                $document,
                $templateKey,
                $webspaceKey,
                $locale
            ))
            ) {
                $target[$field['name']] = $data;
            }
        }

        return $fieldsData;
    }

    /**
     * Return data for one field.
     */
    private function getFieldData($field, Row $row, NodeInterface $node, $document, $templateKey, $webspaceKey, $locale)
    {
        if (isset($field['column'])) {
            // normal data from node property
            return $row->getValue($field['column']);
        } elseif (isset($field['extension'])) {
            // data from extension
            return $this->getExtensionData(
                $node,
                $field['extension'],
                $field['property'],
                $webspaceKey,
                $locale
            );
        } elseif (isset($field['property'])
            && (!isset($field['templateKey']) || $field['templateKey'] === $templateKey)
        ) {
            // not extension data but property of node
            return $this->getPropertyData($document, $field['property']);
        }

        return;
    }

    /**
     * Returns data for property.
     */
    private function getPropertyData($document, LegacyProperty $property)
    {
        return $document->getStructure()->getContentViewProperty($property->getName())->getValue();
    }

    /**
     * Returns data for extension and property name.
     */
    private function getExtensionData(
        NodeInterface $node,
        ExtensionInterface $extension,
        $propertyName,
        $webspaceKey,
        $locale
    ) {
        // extension data: load ones
        if (!$this->extensionDataCache->contains($extension->getName())) {
            $this->extensionDataCache->save(
                $extension->getName(),
                $this->loadExtensionData(
                    $node,
                    $extension,
                    $webspaceKey,
                    $locale
                )
            );
        }

        // get extension data from cache
        $data = $this->extensionDataCache->fetch($extension->getName());

        return isset($data[$propertyName]) ? $data[$propertyName] : null;
    }

    /**
     * load data from extension.
     */
    private function loadExtensionData(NodeInterface $node, ExtensionInterface $extension, $webspaceKey, $locale)
    {
        $extension->setLanguageCode($locale, $this->namespaceRegistry->getPrefix('extension_localized'), '');
        $data = $extension->load(
            $node,
            $webspaceKey,
            $locale
        );

        return $extension->getContentData($data);
    }

    private function loadDocument($pathOrUuid, $locale, $options, $shouldExclude = true)
    {
        $document = $this->documentManager->find(
            $pathOrUuid,
            $locale,
            [
                'load_ghost_content' => isset($options['load_ghost_content']) ? $options['load_ghost_content'] : true,
            ]
        );

        if ($shouldExclude && $this->optionsShouldExcludeDocument($document, $options)) {
            return;
        }

        return $document;
    }

    private function optionsShouldExcludeDocument($document, ?array $options = null)
    {
        if (null === $options) {
            return false;
        }

        $options = \array_merge(
            [
                'exclude_ghost' => true,
                'exclude_shadow' => true,
            ],
            $options
        );

        $state = $this->inspector->getLocalizationState($document);

        if ($options['exclude_ghost'] && LocalizationState::GHOST == $state) {
            return true;
        }

        if ($options['exclude_ghost'] && $options['exclude_shadow'] && LocalizationState::SHADOW == $state) {
            return true;
        }

        return false;
    }

    /**
     * Initializes cache of extension data.
     */
    private function initializeExtensionCache()
    {
        $this->extensionDataCache = DoctrineProvider::wrap(new ArrayAdapter());
    }

    /**
     * Return a structure bridge corresponding to the given document.
     *
     * @param StructureBehavior $document
     *
     * @return StructureInterface
     */
    private function documentToStructure($document)
    {
        if (null === $document) {
            return;
        }
        $structure = $this->inspector->getStructureMetadata($document);
        $documentAlias = $this->inspector->getMetadata($document)->getAlias();

        $structureBridge = $this->structureManager->wrapStructure($documentAlias, $structure);
        $structureBridge->setDocument($document);

        return $structureBridge;
    }

    /**
     * Return a collection of structures for the given documents, optionally filtering according
     * to the given options (as defined in optionsShouldExcludeDocument).
     *
     * @param object[] $documents
     * @param array|null $filterOptions
     */
    private function documentsToStructureCollection($documents, $filterOptions = null)
    {
        $collection = [];
        foreach ($documents as $document) {
            if (!$document instanceof StructureBehavior) {
                continue;
            }

            if ($this->optionsShouldExcludeDocument($document, $filterOptions)) {
                continue;
            }

            $collection[] = $this->documentToStructure($document);
        }

        return $collection;
    }
}
