<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\MediaBundle\Api\Media as MediaApi;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class MediaDataProviderRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TagInterface[]
     */
    private $tags = [];

    /**
     * @var Media[]
     */
    private $medias = [];

    /**
     * @var array<string>
     */
    private $tagData = [
        'Tag-0',
        'Tag-1',
        'Tag-2',
        'Tag-3',
    ];

    /**
     * @var array<array{
     *     0: string,
     *     1: int,
     *     2: string,
     *     3: string,
     *     4: array<int>,
     * }>
     */
    private static $mediaData = [
        ['Bild 1', 1, 'image/jpg', 'image', [0, 1, 2]],
        ['Bild 2', 2, 'image/jpg', 'image', [0, 1, 3]],
        ['Bild 3', 4, 'image/png', 'image', [0, 1]],
        ['Bild 4', 3, 'video/mov', 'video', [0, 1, 2]],
        ['Bild 5', 0, 'video/mkv', 'video', [0]],
        ['Bild 6', 0, 'application/pdf', 'document', [0]],
        ['Bild 7', 0, 'application/pdf', 'document', [0]],
        ['Bild 8', 0, 'application/pdf', 'document', []],
    ];

    /**
     * @var array<string>
     */
    private $mediaTypeData = ['image', 'video', 'document'];

    /**
     * @var MediaType[]
     */
    private $mediaTypes = [];

    /**
     * @var array<array{
     *     0: string,
     *     1: ?int,
     * }>
     */
    private $collectionData = [
        ['Test-2', null],
        ['Test-1', null],
        ['Test-1.1', 1],
        ['Test-1.1.1', 2],
        ['Test-1.2', 1],
    ];

    /**
     * @var Collection[]
     */
    private $collections;

    /**
     * @var SystemStoreInterface
     */
    private $systemStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->systemStore = $this->getContainer()->get('sulu_security.system_store');

        $this->purgeDatabase();
        $this->em = $this->getEntityManager();

        foreach ($this->mediaTypeData as $type) {
            $this->mediaTypes[$type] = $this->createType($type);
        }

        $this->em->flush();
    }

    private function createCollection($name, $parent = null): Collection
    {
        $collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName($name);
        $collectionType->setDescription('Default Collection Type');
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');

        $collection->setType($collectionType);
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        if (null !== $parent) {
            $collection->setParent($parent);
        }

        $this->em->persist($collection);
        $this->em->persist($collectionMeta);
        $this->em->persist($collectionType);

        return $collection;
    }

    /**
     * @param string $name
     */
    public function createTargetGroup($name): TargetGroup
    {
        $targetGroup = new TargetGroup();
        $targetGroup->setTitle($name);
        $targetGroup->setPriority(1);

        $this->em->persist($targetGroup);

        return $targetGroup;
    }

    private function createType($name): MediaType
    {
        $type = new MediaType();
        $type->setName($name);

        $this->em->persist($type);

        return $type;
    }

    private function createTag($name): TagInterface
    {
        $tag = $this->em->getRepository(TagInterface::class)->createNew();
        $tag->setName($name);

        $this->em->persist($tag);

        return $tag;
    }

    private function createMedia($title, $collection, $mimeType, $type, $tags = [], $targetGroups = []): Media
    {
        $media = new Media();
        $file = new File();
        $fileVersion = new FileVersion();
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setTitle($title);
        $fileVersionMeta->setLocale('de');
        $fileVersionMeta->setFileVersion($fileVersion);
        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setVersion(1);
        $fileVersion->setName($title);
        $fileVersion->setSize(0);
        $fileVersion->setMimeType($mimeType);
        $fileVersion->setFile($file);
        $file->setVersion(1);
        $file->addFileVersion($fileVersion);
        $file->setMedia($media);
        $media->addFile($file);
        $media->setType($this->mediaTypes[$type]);
        $media->setCollection($collection);

        foreach ($tags as $tag) {
            $fileVersion->addTag($this->tags[$tag]);
        }

        foreach ($targetGroups as $targetGroup) {
            $fileVersion->addTargetGroup($targetGroup);
        }

        $this->em->persist($media);

        return $media;
    }

    private function createRole($name, $system): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setSystem($system);
        $role->setAnonymous(true);
        $this->em->persist($role);

        return $role;
    }

    private function createAccessControl($entityClass, $id, $permissions, Role $role): AccessControl
    {
        $accessControl = new AccessControl();
        $accessControl->setPermissions($permissions);
        $accessControl->setEntityId($id);
        $accessControl->setEntityClass($entityClass);
        $accessControl->setRole($role);
        $this->em->persist($accessControl);

        return $accessControl;
    }

    /**
     * @return iterable<array{
     *     0: array{
     *         dataSource?: mixed,
     *         tags?: array<int>,
     *         tagOperator?: string,
     *         websiteTags?: array<int>,
     *         websiteTagsOperator?: string,
     *         sortBy?: string,
     *         sortMethod?: string,
     *         includeSubFolders?: bool|string
     *     },
     *     1: ?int,
     *     2: ?int,
     *     3: ?int,
     *     4: array<array{
     *         0: string,
     *         1: int,
     *         2: string,
     *         3: string,
     *         4: array<int>,
     *     }>,
     *     5?: array<int>,
     *     6?: array<string, mixed>,
     * }>
     */
    public static function findByProvider(): iterable
    {
        // when pagination is active the result count is pageSize + 1 to determine has next page

        return [
            // no data-source
            [[], null, 0, null, []],
            // no pagination
            [['dataSource' => 'root'], null, 0, null, self::$mediaData],
            // page 1, no limit
            [['dataSource' => 'root'], 1, 3, null, \array_slice(self::$mediaData, 0, 4)],
            // page 2, no limit
            [['dataSource' => 'root'], 2, 3, null, \array_slice(self::$mediaData, 3, 4)],
            // page 3, no limit
            [['dataSource' => 'root'], 3, 3, null, \array_slice(self::$mediaData, 6, 2)],
            // no pagination, limit 3
            [['dataSource' => 'root'], null, 0, 3, \array_slice(self::$mediaData, 0, 3)],
            // page 1, limit 5
            [['dataSource' => 'root'], 1, 3, 5, \array_slice(self::$mediaData, 0, 4)],
            // page 2, limit 5
            [['dataSource' => 'root'], 2, 3, 5, \array_slice(self::$mediaData, 3, 2)],
            // page 3, limit 5
            [['dataSource' => 'root'], 3, 3, 5, []],
            // no pagination, tag 0
            [
                ['dataSource' => 'root', 'tags' => [0], 'tagOperator' => 'or'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 7),
                [0],
            ],
            // no pagination, tag 0 or 1
            [
                ['dataSource' => 'root', 'tags' => [0, 1], 'tagOperator' => 'or'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 7),
            ],
            // no pagination, tag 0 and 1
            [
                ['dataSource' => 'root', 'tags' => [0, 1], 'tagOperator' => 'and'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 4),
                [0, 1],
            ],
            // no pagination, tag 0 and 3
            [
                ['dataSource' => 'root', 'tags' => [0, 3], 'tagOperator' => 'and'],
                null,
                0,
                null,
                [self::$mediaData[1]],
                [0, 3],
            ],
            // page 1, no limit, tag 0
            [
                ['dataSource' => 'root', 'tags' => [0], 'tagOperator' => 'or'],
                1,
                3,
                null,
                \array_slice(self::$mediaData, 0, 4),
                [0],
            ],
            // page 2, no limit, tag 0
            [
                ['dataSource' => 'root', 'tags' => [0], 'tagOperator' => 'or'],
                2,
                3,
                null,
                \array_slice(self::$mediaData, 3, 4),
                [0],
            ],
            // page 3, no limit, tag 0
            [
                ['dataSource' => 'root', 'tags' => [0], 'tagOperator' => 'or'],
                3,
                3,
                null,
                \array_slice(self::$mediaData, 6, 1),
                [0],
            ],
            // no pagination, website-tag 0
            [
                ['dataSource' => 'root', 'websiteTags' => [0], 'websiteTagsOperator' => 'or'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 7),
                [0],
            ],
            // no pagination, website-tag 0 or 1
            [
                ['dataSource' => 'root', 'websiteTags' => [0, 1], 'websiteTagsOperator' => 'or'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 7),
            ],
            // no pagination, website-tag 0 and 1
            [
                ['dataSource' => 'root', 'websiteTags' => [0, 1], 'websiteTagsOperator' => 'and'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 4),
                [0, 1],
            ],
            // no pagination, website-tag 1, tags 3
            [
                [
                    'dataSource' => 'root',
                    'websiteTags' => [1],
                    'websiteTagsOperator' => 'or',
                    'tags' => [3],
                    'tagOperator' => 'or',
                ],
                null,
                0,
                null,
                [self::$mediaData[1]],
                [0, 3],
            ],
            // no pagination, website-tag 2 or 3, tags 1
            [
                [
                    'dataSource' => 'root',
                    'websiteTags' => [2, 3],
                    'websiteTagsOperator' => 'or',
                    'tags' => [1],
                    'tagOperator' => 'or',
                ],
                null,
                0,
                null,
                [self::$mediaData[0], self::$mediaData[1], self::$mediaData[3]],
                [0, 1],
            ],
            // no pagination, website-tag 1, tags 2 or 3
            [
                [
                    'dataSource' => 'root',
                    'websiteTags' => [1],
                    'websiteTagsOperator' => 'or',
                    'tags' => [2, 3],
                    'tagOperator' => 'or',
                ],
                null,
                0,
                null,
                [self::$mediaData[0], self::$mediaData[1], self::$mediaData[3]],
                [0, 1],
            ],
            // combination website/admin-tag
            [
                [
                    'dataSource' => 'root',
                    'tags' => [0],
                    'tagOperator' => 'or',
                    'websiteTags' => [1],
                    'websiteTagsOperator' => 'or',
                ],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 4),
                [0, 1],
            ],
            // options mimetype
            [
                ['dataSource' => 'root'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 2),
                [],
                ['mimetype' => 'image/jpg'],
            ],
            // options mimetype and admin tags
            [
                ['dataSource' => 'root', 'tags' => [0], 'tagOperator' => 'or'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 5, 2),
                [],
                ['mimetype' => 'application/pdf'],
            ],
            // options mimetype and website tags
            [
                ['dataSource' => 'root', 'websiteTags' => [0], 'websiteTagsOperator' => 'or'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 5, 2),
                [],
                ['mimetype' => 'application/pdf'],
            ],
            // options type and admin tags
            [
                ['dataSource' => 'root', 'tags' => [0], 'tagOperator' => 'or'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 3),
                [],
                ['type' => 'image'],
            ],
            // options mimetype and website tags
            [
                ['dataSource' => 'root', 'websiteTags' => [0], 'websiteTagsOperator' => 'or'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 5, 2),
                [],
                ['type' => 'document'],
            ],
            // options mimetype/type
            [
                ['dataSource' => 'root'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 5, 3),
                [],
                ['mimetype' => 'application/pdf', 'type' => 'document'],
            ],
            // datasource no sub folder
            [
                ['dataSource' => 1, 'includeSubFolders' => 'false'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 1),
                [],
                [],
            ],
            // datasource no sub folder
            [
                ['dataSource' => 1, 'includeSubFolders' => false],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 1),
                [],
                [],
            ],
            // datasource sub folder
            [
                ['dataSource' => 1, 'includeSubFolders' => 'true'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 4),
                [],
                [],
            ],
            // datasource sub folder
            [
                ['dataSource' => 1, 'includeSubFolders' => true],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 4),
                [],
                [],
            ],
            // datasource sub folder without includeSubFolders
            [
                ['dataSource' => 1],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 0, 1),
                [],
                [],
            ],
            // datasource sub folder with tags
            [
                ['dataSource' => 0, 'includeSubFolders' => true, 'tags' => [0], 'tagOperator' => 'or'],
                null,
                0,
                null,
                \array_slice(self::$mediaData, 4, 3),
                [],
                [],
            ],
            // sort-by default sortMethod
            [
                ['dataSource' => 'root', 'sortBy' => 'fileVersionMeta.title'],
                1,
                null,
                null,
                self::$mediaData,
            ],
            // sort-by asc
            [
                ['dataSource' => 'root', 'sortBy' => 'fileVersionMeta.title', 'sortMethod' => 'asc'],
                1,
                null,
                null,
                self::$mediaData,
            ],
            // sort-by desc
            [
                ['dataSource' => 'root', 'sortBy' => 'fileVersionMeta.title', 'sortMethod' => 'desc'],
                1,
                null,
                null,
                \array_reverse(self::$mediaData),
            ],
            // sort-by asc and limit
            [
                ['dataSource' => 'root', 'sortBy' => 'fileVersionMeta.title', 'sortMethod' => 'asc'],
                1,
                null,
                3,
                \array_slice(self::$mediaData, 0, 3),
            ],
            // sort-by desc and limit
            [
                ['dataSource' => 'root', 'sortBy' => 'fileVersionMeta.title', 'sortMethod' => 'desc'],
                1,
                null,
                3,
                \array_slice(\array_reverse(self::$mediaData), 0, 3),
            ],
        ];
    }

    /**
     * @param array{
     *     dataSource: mixed,
     *     tags?: array<int>,
     *     tagOperator?: string,
     *     websiteTags?: array<int>,
     *     websiteTagsOperator?: string,
     *     sortBy?: string,
     *     sortMethod?: string,
     *     includeSubFolders?: bool|string
     * } $filters
     * @param int|null $page
     * @param int|null $pageSize
     * @param int|null $limit
     * @param array<array{
     *      0: string,
     *      1: int,
     *      2: string,
     *      3: string,
     *      4: array<int>,
     * }> $expected
     * @param array<int> $tags
     * @param array<string, mixed> $options
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('findByProvider')]
    public function testFindByFilters($filters, $page, $pageSize, $limit, $expected, $tags = [], $options = []): void
    {
        foreach ($this->collectionData as $collection) {
            $this->collections[] = $this->createCollection(
                $collection[0],
                $collection[1] ? $this->collections[$collection[1]] : null
            );
        }

        $this->em->flush();

        foreach ($this->tagData as $tag) {
            $this->tags[] = $this->createTag($tag);
        }

        foreach (self::$mediaData as $media) {
            $this->medias[] = $this->createMedia(
                $media[0],
                $this->collections[$media[1]],
                $media[2],
                $media[3],
                $media[4]
            );
        }
        $this->em->flush();

        $repository = $this->getContainer()->get('sulu_media_test.smart_content.data_provider.media.repository');

        // if data-source isset replace the index with the id
        if (\array_key_exists('dataSource', $filters) && 'root' !== $filters['dataSource']) {
            $filters['dataSource'] = $this->collections[$filters['dataSource']]->getId();
        }

        // if tags isset replace the array indexes with database id
        if (\array_key_exists('tags', $filters)) {
            $filters['tags'] = \array_map(
                function($tag) {
                    return $this->tags[$tag]->getId();
                },
                $filters['tags']
            );
        }

        // if tags isset replace the array indexes with database id
        if (\array_key_exists('websiteTags', $filters)) {
            $filters['websiteTags'] = \array_map(
                function($tag) {
                    return $this->tags[$tag]->getId();
                },
                $filters['websiteTags']
            );
        }

        $result = $repository->findByFilters($filters, $page, $pageSize, $limit, 'de', $options);

        $length = \count($expected);
        $this->assertCount($length, $result);

        for ($i = 0; $i < $length; ++$i) {
            $this->assertEquals($expected[$i][0], $result[$i]->getTitle(), $i);

            $existingTags = $result[$i]->getTags();
            foreach ($tags as $tag) {
                $this->assertContains($this->tags[$tag]->getName(), $existingTags);
            }
        }
    }

    /**
     * @return iterable<array{
     *     0: int|null,
     *     1: int[],
     * }>
     */
    public static function provideFindByFiltersWithAudienceTargeting(): iterable
    {
        return [
            [null, [0, 1, 2, 3]],
            [0, [0, 2]],
            [1, [1, 3]],
        ];
    }

    /**
     * @param int[] $expectedIndexes
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideFindByFiltersWithAudienceTargeting')]
    public function testFindByFiltersWithAudienceTargeting(?int $targetGroupIndex, array $expectedIndexes): void
    {
        /** @var TargetGroupInterface[] $targetGroups */
        $targetGroups = [];
        $targetGroups[] = $this->createTargetGroup('Target Group 1');
        $targetGroups[] = $this->createTargetGroup('Target Group 2');

        $collection = $this->createCollection('Collection 1');
        $this->em->flush();

        $medias = [];
        $medias[] = $this->createMedia('Media 1', $collection, 'image/jpg', 'image', [], [$targetGroups[0]]);
        $medias[] = $this->createMedia('Media 2', $collection, 'image/jpg', 'image', [], [$targetGroups[1]]);
        $medias[] = $this->createMedia('Media 3', $collection, 'image/jpg', 'image', [], [$targetGroups[0]]);
        $medias[] = $this->createMedia('Media 4', $collection, 'image/jpg', 'image', [], [$targetGroups[1]]);

        $this->em->flush();

        $filters = [
            'dataSource' => $collection->getId(),
            'includeSubFolders' => true,
        ];

        if (null !== $targetGroupIndex) {
            $filters['targetGroupId'] = $targetGroups[$targetGroupIndex]->getId();
        }

        $mediaResults = $this->getContainer()
            ->get('sulu_media_test.smart_content.data_provider.media.repository')
            ->findByFilters($filters, 1, 100, 100, 'de');

        $mediaIds = \array_map(function(MediaApi $media) {
            return $media->getId();
        }, $mediaResults);

        $expectedMediaIds = \array_map(function($expectedIndex) use ($medias) {
            return $medias[$expectedIndex]->getId();
        }, $expectedIndexes);

        $this->assertEquals($expectedMediaIds, $mediaIds);
    }

    public function testFindByFiltersWithSecurityAllowed(): void
    {
        $collection = $this->createCollection('Collection 1');
        $this->em->flush();

        $role = $this->createRole('Role', 'Website');
        $this->createAccessControl(\get_class($collection), $collection->getId(), 64, $role);

        $medias = [];
        $medias[] = $this->createMedia('Media 1', $collection, 'image/jpg', 'image');
        $medias[] = $this->createMedia('Media 2', $collection, 'image/jpg', 'image');

        $this->em->flush();

        $filters = [
            'dataSource' => $collection->getId(),
        ];

        $mediaResults = $this->getContainer()
            ->get('sulu_media_test.smart_content.data_provider.media.repository')
            ->findByFilters($filters, 1, 100, 100, 'de');

        $this->assertCount(2, $mediaResults);
        $this->assertEquals('Media 1', $mediaResults[0]->getName());
        $this->assertEquals('Media 2', $mediaResults[1]->getName());
    }

    public function testFindByFiltersWithSecurityDenied(): void
    {
        $this->systemStore->setSystem('Website');
        $collection = $this->createCollection('Collection 1');
        $this->em->flush();

        $role = $this->createRole('Role', 'Website');
        $this->createAccessControl(\get_class($collection), $collection->getId(), 0, $role);

        $medias = [];
        $medias[] = $this->createMedia('Media 1', $collection, 'image/jpg', 'image');

        $this->em->flush();

        $filters = [
            'dataSource' => $collection->getId(),
        ];

        $mediaResults = $this->getContainer()
            ->get('sulu_media_test.smart_content.data_provider.media.repository')
            ->findByFilters($filters, 1, 100, 100, 'de', [], null, 64);

        $this->assertCount(0, $mediaResults);
    }

    public function testFindByFiltersWithSecurityMixedPermissions(): void
    {
        $this->systemStore->setSystem('Website');
        $collection = $this->createCollection('Collection');
        $this->em->flush();

        $collection1 = $this->createCollection('Collection 1', $collection);
        $collection2 = $this->createCollection('Collection 2', $collection);
        $this->em->flush();

        $role = $this->createRole('Role', 'Website');
        $this->createAccessControl(\get_class($collection1), $collection1->getId(), 64, $role);
        $this->createAccessControl(\get_class($collection2), $collection2->getId(), 0, $role);

        $medias = [];
        $medias[] = $this->createMedia('Media 1', $collection1, 'image/jpg', 'image');
        $medias[] = $this->createMedia('Media 2', $collection1, 'image/jpg', 'image');
        $medias[] = $this->createMedia('Media 3', $collection2, 'image/jpg', 'image');
        $medias[] = $this->createMedia('Media 4', $collection2, 'image/jpg', 'image');

        $this->em->flush();

        $filters = [
            'dataSource' => $collection->getId(),
            'includeSubFolders' => true,
        ];

        $mediaResults = $this->getContainer()
            ->get('sulu_media_test.smart_content.data_provider.media.repository')
            ->findByFilters($filters, 1, 100, 100, 'de', [], null, 64);

        $this->assertCount(2, $mediaResults);
        $this->assertEquals('Media 1', $mediaResults[0]->getName());
        $this->assertEquals('Media 2', $mediaResults[1]->getName());
    }

    public function testFindByFiltersWithSecurityDeniedAndOtherSystem(): void
    {
        $this->systemStore->setSystem('Website');
        $collection = $this->createCollection('Collection 1');
        $this->em->flush();

        $websiteRole = $this->createRole('Website Role', 'Website');
        $suluRole = $this->createRole('Sulu Role', 'Sulu');
        $this->createAccessControl(\get_class($collection), $collection->getId(), 0, $websiteRole);
        $this->createAccessControl(\get_class($collection), $collection->getId(), 64, $suluRole);

        $medias = [];
        $medias[] = $this->createMedia('Media 1', $collection, 'image/jpg', 'image');

        $this->em->flush();

        $filters = [
            'dataSource' => $collection->getId(),
        ];

        $mediaResults = $this->getContainer()
            ->get('sulu_media_test.smart_content.data_provider.media.repository')
            ->findByFilters($filters, 1, 100, 100, 'de', [], null, 64);

        $this->assertCount(0, $mediaResults);
    }
}
