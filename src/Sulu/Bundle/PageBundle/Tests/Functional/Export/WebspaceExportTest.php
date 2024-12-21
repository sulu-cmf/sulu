<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Export;

use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Export\WebspaceExportInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\Exception\MetadataNotFoundException;

/**
 * Tests for the Webspace Export class.
 */
class WebspaceExportTest extends SuluTestCase
{
    /**
     * @var WebspaceExportInterface
     */
    private $webspaceExporter;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var int
     */
    private $creator;

    protected function setUp(): void
    {
        parent::initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->extensionManager = $this->getContainer()->get('sulu_page.extension.manager');
        $this->webspaceExporter = $this->getContainer()->get('sulu_page.export.webspace');
    }

    public function test12Xliff(): void
    {
        $documents = $this->prepareData();
        $exportData = $this->webspaceExporter->getExportData('sulu_io');

        $expectedResult = [
            'webspaceKey' => 'sulu_io',
            'locale' => 'en',
            'format' => '1.2.xliff',
            'documents' => $this->getExportResultData($documents),
        ];

        // FIXME ignore home site for testcase
        unset($exportData['documents'][0]);
        $exportData['documents'] = \array_values($exportData['documents']);

        // Ignore uuids
        unset($exportData['documents'][0]['uuid']);
        unset($exportData['documents'][1]['uuid']);

        $this->assertEquals(
            \array_keys($expectedResult),
            \array_keys($exportData)
        );

        $this->assertEquals(
            $expectedResult['webspaceKey'],
            $exportData['webspaceKey']
        );

        $this->assertEquals(
            $expectedResult['format'],
            $exportData['format']
        );

        $this->assertEquals(
            $expectedResult['locale'],
            $exportData['locale']
        );

        $this->assertEquals(
            $expectedResult['documents'],
            $exportData['documents']
        );
    }

    /**
     * @return StructureInterface[]
     *
     * @throws DocumentManagerException
     * @throws MetadataNotFoundException
     */
    private function prepareData()
    {
        /** @var PageBridge[] $data */
        $data = $this->getRawData();
        $extensionDataList = $this->getExtensionDataArray();
        $data[0]['ext'] = $extensionDataList[0];
        $data[1]['ext'] = $extensionDataList[1];

        $documents = [];

        $documents[0] = $this->save($data[0], 'overview', 'sulu_io', 'en');
        $documents[1] = $this->save($data[1], 'overview', 'sulu_io', 'en');

        return $documents;
    }

    /**
     * @return array
     */
    private function getRawData()
    {
        $data = $this->getDataArray();

        return [
            [
                'title' => $data[0]['title'],
                'subtitle' => $data[0]['subtitle'],
                'url' => $data[0]['url'],
                'article' => $data[0]['article'],
                'tags' => $data[0]['tags'],
                'external_url' => $data[0]['external_url'],
                'block' => [
                    [
                        'type' => $data[0]['block'][0]['type']['value'],
                        'title' => $data[0]['block'][0]['title']['value'],
                        'article' => $data[0]['block'][0]['article']['value'],
                    ],
                    [
                        'type' => $data[0]['block'][1]['type']['value'],
                        'title' => $data[0]['block'][1]['title']['value'],
                        'article' => $data[0]['block'][1]['article']['value'],
                    ],
                ],
            ],
            [
                'title' => $data[1]['title'],
                'subtitle' => $data[1]['subtitle'],
                'url' => $data[1]['url'],
                'article' => $data[1]['article'],
                'tags' => $data[1]['tags'],
                'external_url' => $data[1]['external_url'],
                'block' => [
                    [
                        'type' => $data[1]['block'][0]['type']['value'],
                        'title' => $data[1]['block'][0]['title']['value'],
                        'article' => $data[1]['block'][0]['article']['value'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getDataArray()
    {
        return [
            [
                'title' => 'Test1',
                'subtitle' => 'subtitle',
                'url' => '/test-1',
                'article' => 'Lorem Ipsum dolorem apsum',
                'tags' => 'Test',
                'external_url' => 'http://www.google.com',
                'block' => [
                    [
                        'type' => [
                            'name' => 'type',
                            'type' => 'block_type',
                            'options' => [
                                'translate' => false,
                            ],
                            'value' => 'type1',
                        ],
                        'title' => [
                            'name' => 'title',
                            'type' => 'text_line',
                            'options' => [
                                'translate' => true,
                            ],
                            'value' => 'Block-Title-1',
                        ],
                        'article' => [
                            'name' => 'article',
                            'type' => 'text_line',
                            'options' => [
                                'translate' => true,
                            ],
                            'value' => 'Block-Article-1-1',
                        ],
                    ],
                    [
                        'type' => [
                            'name' => 'type',
                            'type' => 'block_type',
                            'options' => [
                                'translate' => false,
                            ],
                            'value' => 'type1',
                        ],
                        'title' => [
                            'name' => 'title',
                            'type' => 'text_line',
                            'options' => [
                                'translate' => true,
                            ],
                            'value' => 'Block-Title-1',
                        ],
                        'article' => [
                            'name' => 'article',
                            'type' => 'text_line',
                            'options' => [
                                'translate' => true,
                            ],
                            'value' => 'Block-Article-1-2',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Test2',
                'subtitle' => 'subtitle',
                'url' => '/test-2',
                'article' => 'asdfasdf',
                'tags' => 'Sulu CMS Symfony',
                'external_url' => 'https://sulu.io',
                'block' => [
                    [
                        'type' => [
                            'name' => 'type',
                            'type' => 'block_type',
                            'options' => [
                                'translate' => false,
                            ],
                            'value' => 'type1',
                        ],
                        'title' => [
                            'name' => 'title',
                            'type' => 'text_line',
                            'options' => [
                                'translate' => true,
                            ],
                            'value' => 'Block-Title-2',
                        ],
                        'article' => [
                            'name' => 'article',
                            'type' => 'text_line',
                            'options' => [
                                'translate' => true,
                            ],
                            'value' => 'Block-Article-2-1',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getExtensionDataArray()
    {
        return [
            [
                'seo' => [
                    'title' => 'Seo Test1',
                    'description' => 'Seo Description1',
                    'keywords' => 'Seo keywords 1',
                    'canonicalUrl' => '/url-1-1',
                    'noIndex' => '0',
                    'noFollow' => '1',
                    'hideInSitemap' => '1',
                ],
                'excerpt' => [
                    'title' => 'Excerpt Test1',
                    'more' => 'more Test1',
                    'description' => '<p>Test 1</p>',
                    'categories' => [],
                    'tags' => [],
                    'icon' => '',
                    'images' => '',
                    'audience_targeting_groups' => [],
                ],
            ],
            [
                'seo' => [
                    'title' => 'Seo Test2',
                    'description' => 'Seo Description2',
                    'keywords' => 'Seo keywords 2',
                    'canonicalUrl' => '/url-2-1',
                    'noIndex' => '1',
                    'noFollow' => '0',
                    'hideInSitemap' => '1',
                    'audience_targeting_groups' => [],
                ],
                'excerpt' => [
                    'title' => 'Excerpt Test2',
                    'more' => 'more Test2',
                    'description' => '<p>Test 2</p>',
                    'categories' => [],
                    'tags' => [],
                    'icon' => '',
                    'images' => '',
                    'audience_targeting_groups' => [],
                ],
            ],
        ];
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    private function getTypeOptionsByName($name, $prefix = '')
    {
        $type = null;
        $translate = null;

        switch ($name) {
            case 'title':
                $type = 'text_line';
                $translate = true;
                break;
            case 'subtitle':
                $type = 'text_line';
                $translate = true;
                break;
            case 'url':
                $type = 'resource_locator';
                $translate = false;
                break;
            case 'type':
                $type = $prefix . '_type';
                $translate = false;
                break;
            case 'article':
                $type = 'text_line';
                $translate = true;
                break;
            case 'tags':
                $type = 'text_line';
                $translate = true;
                break;
            case 'external_url':
                $type = 'url';
                $translate = false;
                break;
            case 'block':
                $type = 'block';
                $translate = false;
                break;
        }

        $options = [
            'translate' => $translate,
        ];

        return [$type, $options];
    }

    /**
     * @param PageDocument[] $documents
     *
     * @return array
     */
    private function getExportResultData($documents)
    {
        $pages = $this->getDataArray();
        $extensionDataList = $this->getExtensionDataArray();

        $data = [];
        foreach ($pages as $key => $page) {
            $contentData = [];
            $extensionData = $extensionDataList[$key];

            foreach ($page as $name => $property) {
                if (
                    false === \strpos($name, 'seo')
                    && false === \strpos($name, 'excerpt')
                ) {
                    if ('block' === $name) {
                        $blockChildren = [];
                        foreach ($property as $block) {
                            $blockPropertyData = [];
                            foreach ($block as $blockName => $blockProperty) {
                                list($type, $options) = $this->getTypeOptionsByName($blockName, $name);
                                $blockPropertyData[$blockName] = $blockProperty;
                            }
                            $blockChildren[] = $blockPropertyData;
                        }

                        list($type, $options) = $this->getTypeOptionsByName($name);
                        $propertyData = $this->createItemArray($name, $type, $options, null, $blockChildren);
                    } else {
                        list($type, $options) = $this->getTypeOptionsByName($name);
                        $propertyData = $this->createItemArray($name, $type, $options, $property);
                    }

                    $contentData[$name] = $propertyData;
                }
            }

            $extensionData = [
                'seo' => [
                    'title' => $this->createItemArray(
                        'title',
                        '',
                        true,
                        $extensionData['seo']['title']
                    ),
                    'description' => $this->createItemArray(
                        'description',
                        '',
                        true,
                        $extensionData['seo']['description']
                    ),
                    'keywords' => $this->createItemArray(
                        'keywords',
                        '',
                        true,
                        $extensionData['seo']['keywords']
                    ),
                    'canonicalUrl' => $this->createItemArray(
                        'canonicalUrl',
                        '',
                        false,
                        $extensionData['seo']['canonicalUrl']
                    ),
                    'noIndex' => $this->createItemArray('noIndex', '', false, $extensionData['seo']['noIndex']),
                    'noFollow' => $this->createItemArray('noFollow', '', false, $extensionData['seo']['noFollow']),
                    'hideInSitemap' => $this->createItemArray(
                        'hideInSitemap',
                        '',
                        false,
                        $extensionData['seo']['hideInSitemap']
                    ),
                ],
                'excerpt' => [
                    'title' => $this->createItemArray('title', 'text_line', true, $extensionData['excerpt']['title']),
                    'more' => $this->createItemArray('more', 'text_line', true, $extensionData['excerpt']['more']),
                    'description' => $this->createItemArray(
                        'description',
                        'text_editor',
                        true,
                        $extensionData['excerpt']['description']
                    ),
                    'categories' => $this->createItemArray(
                        'categories',
                        'category_selection',
                        false,
                        !empty($extensionData['excerpt']['categories']) ? \json_encode(
                            $extensionData['excerpt']['categories']
                        ) : ''
                    ),
                    'tags' => $this->createItemArray(
                        'tags',
                        'tag_selection',
                        false,
                        !empty($extensionData['excerpt']['tags']) ? \json_encode($extensionData['excerpt']['tags']) : ''
                    ),
                    'icon' => $this->createItemArray(
                        'icon',
                        'media_selection',
                        false,
                        $extensionData['excerpt']['icon']
                    ),
                    'images' => $this->createItemArray(
                        'images',
                        'media_selection',
                        false,
                        $extensionData['excerpt']['icon']
                    ),
                    'audience_targeting_groups' => $this->createItemArray(
                        'audience_targeting_groups',
                        'target_group_selection',
                        false,
                        \json_encode($extensionData['excerpt']['audience_targeting_groups'])
                    ),
                ],
            ];

            $data[] = [
                'locale' => 'en',
                'content' => $contentData,
                'settings' => [
                    'structureType' => $this->createItemArray('structureType', '', false, 'overview'),
                    'created' => $this->createItemArray(
                        'created',
                        '',
                        false,
                        $documents[$key]->getCreated()->format('c')
                    ),
                    'changed' => $this->createItemArray(
                        'changed',
                        '',
                        false,
                        $documents[$key]->getChanged()->format('c')
                    ),
                    'creator' => $this->createItemArray('creator', '', false, $this->creator, null, true),
                    'changer' => $this->createItemArray('changer', '', false, $this->creator, null, true),
                    'published' => $this->createItemArray(
                        'published',
                        '',
                        false,
                        $documents[$key]->getPublished()->format('c')
                    ),
                    'shadowLocale' => $this->createItemArray('shadowLocale', '', false, ''),
                    'originalLocale' => $this->createItemArray('originalLocale', '', false, 'en'),
                    'locale' => $this->createItemArray('locale', '', false, 'en'),
                    'path' => $this->createItemArray('path', '', false, '/cmf/sulu_io/contents/' . \strtolower($page['title'])),
                    'resourceSegment' => $this->createItemArray('resourceSegment', '', false, $page['url']),
                    'redirectExternal' => $this->createItemArray('redirectExternal', '', false, ''),
                    'redirectTarget' => $this->createItemArray('redirectTarget', '', false, ''),
                    'redirectType' => $this->createItemArray('redirectType', '', false, 1),
                    'workflowStage' => $this->createItemArray('workflowStage', '', false, WorkflowStage::PUBLISHED),
                    'navigationContexts' => $this->createItemArray('navigationContexts', '', false, '[]'),
                    'permissions' => $this->createItemArray('permissions', '', false, '[]'),
                    'webspaceName' => $this->createItemArray('webspaceName', '', false, 'sulu_io'),
                ],
                'extensions' => $extensionData,
            ];
        }

        return $data;
    }

    /**
     * @return array
     */
    private function createItemArray(
        $name,
        $type,
        $options,
        $value = null,
        $children = null,
        $forceValue = false
    ) {
        if (\is_bool($options)) {
            $options = [
                'translate' => $options,
            ];
        }

        $data = [
            'name' => $name,
            'type' => $type,
            'options' => $options,
        ];

        if ($forceValue || null !== $value) {
            $data['value'] = $value;
        }

        if (null !== $children) {
            $data['children'] = $children;
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string $structureType
     * @param string $webspaceKey
     * @param string $locale
     * @param string $uuid
     * @param string $parentUuid
     * @param int $state
     * @param bool $isShadow
     * @param string $shadowBaseLanguage
     * @param string $documentAlias
     *
     * @return object
     *
     * @throws DocumentManagerException
     * @throws MetadataNotFoundException
     */
    private function save(
        $data,
        $structureType,
        $webspaceKey,
        $locale,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $isShadow = null,
        $shadowBaseLanguage = null,
        $documentAlias = Structure::TYPE_PAGE
    ) {
        /* @var PageDocument $document */
        try {
            $document = $uuid
                ? $this->documentManager->find($uuid, $locale)
                : $this->documentManager->create($documentAlias);
        } catch (DocumentNotFoundException $e) {
            $document = $this->documentManager->create($documentAlias);
        }
        $document->setTitle($data['title']);
        $document->getStructure()->bind($data);
        $document->setStructureType($structureType);

        if ($document instanceof ShadowLocaleBehavior) {
            $document->setShadowLocale($shadowBaseLanguage);
            $document->setShadowLocaleEnabled($isShadow);
        }

        if (null === $state) {
            $state = WorkflowStage::PUBLISHED;
        }
        $document->setWorkflowStage($state);

        if (isset($data['url']) && $document instanceof ResourceSegmentBehavior) {
            $document->setResourceSegment($data['url']);
        }

        if (isset($data['navContexts'])) {
            $document->setNavigationContexts($data['navContexts']);
        }

        if (isset($data['nodeType'])) {
            $document->setRedirectType($data['nodeType']);
        }

        if (isset($data['internal_link'])) {
            $document->setRedirectTarget($this->documentManager->find($data['internal_link'], $locale));
        }

        if (isset($data['external'])) {
            $document->setRedirectExternal($data['external']);
        }

        if ($document instanceof ExtensionBehavior) {
            if (isset($data['ext'])) {
                $document->setExtensionsData($data['ext']);
            } else {
                $document->setExtensionsData([]);
            }
        }

        $persistOptions = [];
        if ($parentUuid) {
            $document->setParent($this->documentManager->find($parentUuid, $locale));
        } elseif (!$document->getParent()) {
            $persistOptions['parent_path'] = '/cmf/' . $webspaceKey . '/contents';
        }

        $this->documentManager->persist($document, $locale, $persistOptions);
        $this->documentManager->publish($document, $locale);
        $this->documentManager->flush();

        return $document;
    }
}
