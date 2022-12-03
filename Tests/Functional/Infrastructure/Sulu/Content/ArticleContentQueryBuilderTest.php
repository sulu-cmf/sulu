<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Functional\Infrastructe\Sulu\Content;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Infrastructure\Sulu\Content\ArticleContentQueryBuilder;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class ArticleContentQueryBuilderTest extends SuluTestCase
{
    /**
     * @var ContentQueryExecutor
     */
    private $contentQuery;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var string
     */
    private $languageNamespace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->initPhpcr();

        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->extensionManager = $this->getContainer()->get('sulu_page.extension.manager');
        $this->contentQuery = $this->getContainer()->get('sulu.content.query_executor');

        $this->languageNamespace = $this->getContainer()->getParameter('sulu.content.language.namespace');
    }

    /**
     * @return mixed[]
     */
    public function propertiesProvider(): array
    {
        $documents = [];
        $max = 15;
        for ($i = 0; $i < $max; ++$i) {
            $data = [
                'title' => 'News ' . $i,
                'routePath' => '/news/news-' . $i,
                'ext' => [
                    'excerpt' => [
                        'title' => 'Excerpt Title ' . $i,
                        'tags' => [],
                    ],
                ],
            ];
            $template = 'simple_with_route';

            if ($i > 2 * $max / 3) {
                $template = 'block';
                $data['article'] = [
                    [
                        'title' => 'Block Title ' . $i,
                        'article' => 'Blockarticle ' . $i,
                        'type' => 'test',
                        'settings' => [],
                    ],
                    [
                        'title' => 'Block Title 2 ' . $i,
                        'article' => 'Blockarticle2 ' . $i,
                        'type' => 'test',
                        'settings' => [],
                    ],
                ];
            } elseif ($i > $max / 3) {
                $template = 'article';
                $data['article'] = 'Text article ' . $i;
            }

            /** @var ArticleDocument $document */
            $document = $this->documentManager->create('article');
            $document->setTitle($data['title']);
            $document->setRoutePath($data['routePath']);
            $document->getStructure()->bind($data);
            $document->setStructureType($template);
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);

            $this->documentManager->persist($document, 'en');
            $this->documentManager->publish($document, 'en');

            $documents[$document->getUuid()] = $document;
        }

        $this->documentManager->flush();

        return $documents;
    }

    public function testProperties(): void
    {
        $documents = $this->propertiesProvider();

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $request->headers->add(['Accept-Language' => 'en']);
        $this->getContainer()->get('request_stack')->push($request);

        $builder = new ArticleContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->languageNamespace,
        );
        $builder->init(
            [
                'ids' => \array_keys($documents),
                'properties' => [
                    'my_article' => new PropertyParameter('my_article', 'article'),
                    'my_title' => new PropertyParameter('my_title', 'title'),
                    'ext_title' => new PropertyParameter('ext_title', 'excerpt.title'),
                    'ext_tags' => new PropertyParameter('ext_tags', 'excerpt.tags'),
                ],
            ],
        );

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        foreach ($result as $item) {
            /** @var ArticleDocument $expectedDocument */
            $expectedDocument = $documents[$item['id']];

            $this->assertSame($expectedDocument->getUuid(), $item['id']);
            $this->assertSame($expectedDocument->getChanged(), $item['changed']);
            $this->assertSame($expectedDocument->getChanger(), $item['changer']);
            $this->assertSame($expectedDocument->getCreated(), $item['created']);
            $this->assertSame($expectedDocument->getCreator(), $item['creator']);
            $this->assertSame($expectedDocument->getLocale(), $item['locale']);
            $this->assertSame($expectedDocument->getStructureType(), $item['template']);

            $this->assertSame($expectedDocument->getTitle(), $item['title']);
            $this->assertSame($expectedDocument->getRoutePath(), $item['routePath']);

            if ($expectedDocument->getStructure()->hasProperty('article')) {
                $this->assertSame(
                    $expectedDocument->getStructure()->getProperty('article')->getValue(),
                    $item['my_article'],
                );
            }

            $this->assertSame($expectedDocument->getTitle(), $item['my_title']);
            $this->assertSame($expectedDocument->getExtensionsData()['excerpt']['title'], $item['ext_title']);
            $this->assertSame($expectedDocument->getExtensionsData()['excerpt']['tags'], $item['ext_tags']);
        }
    }

    /**
     * @return mixed[]
     */
    private function shadowProvider(): array
    {
        $nodesEn = [];
        $nodesDe = [];
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Team',
                    'routePath' => '/team',
                ],
                'en',
            ),
        );
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Thomas',
                    'routePath' => '/team/thomas',
                ],
                'en',
                null,
                false,
                null,
                Structure::STATE_TEST,
            ),
        );
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Daniel',
                    'routePath' => '/team/daniel',
                ],
                'en',
            ),
        );
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Johannes',
                    'routePath' => '/team/johannes',
                ],
                'en',
                null,
                false,
                null,
                Structure::STATE_TEST,
            ),
        );
        $nodesEn = \array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Alex',
                    'routePath' => '/team/alex',
                ],
                'en',
                null,
                false,
                null,
            ),
        );

        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'Team',
                    'routePath' => '/team',
                ],
                'de',
                $nodesEn['/team']->getUuid(),
                true,
                'en',
            ),
        );
        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'not-important',
                    'routePath' => '/team/thomas',
                ],
                'de',
                $nodesEn['/team/thomas']->getUuid(),
                true,
                'en',
                Structure::STATE_TEST,
            ),
        );
        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'not-important',
                    'routePath' => '/team/daniel',
                ],
                'de',
                $nodesEn['/team/daniel']->getUuid(),
                true,
                'en',
            ),
        );
        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'Johannes DE',
                    'routePath' => '/team/johannes',
                ],
                'de',
                $nodesEn['/team/johannes']->getUuid(),
            ),
        );
        $nodesDe = \array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'not-important-2',
                    'routePath' => '/team/alex',
                ],
                'de',
                $nodesEn['/team/alex']->getUuid(),
                true,
                'en',
                Structure::STATE_TEST,
            ),
        );

        return ['en' => $nodesEn, 'de' => $nodesDe];
    }

    public function testShadow(): void
    {
        $data = $this->shadowProvider();

        $builder = new ArticleContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->languageNamespace,
        );
        $builder->init(
            [
                'ids' => [
                    $data['en']['/team/thomas']->getUuid(),
                    $data['en']['/team/daniel']->getUuid(),
                    $data['en']['/team/johannes']->getUuid(),
                    $data['en']['/team/alex']->getUuid(),
                ],
            ],
        );

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertCount(4, $result);

        $items = [];
        foreach ($result as $item) {
            $items[$item['routePath']] = $item;
        }

        $this->assertSame('/team/thomas', $items['/team/thomas']['routePath']);
        $this->assertSame('Thomas', $items['/team/thomas']['title']);
        $this->assertFalse($items['/team/thomas']['publishedState']);
        $this->assertNull($items['/team/thomas']['published']);

        $this->assertSame('/team/daniel', $items['/team/daniel']['routePath']);
        $this->assertSame('Daniel', $items['/team/daniel']['title']);
        $this->assertTrue($items['/team/daniel']['publishedState']);
        $this->assertNotNull($items['/team/daniel']['published']);

        $this->assertSame('/team/johannes', $items['/team/johannes']['routePath']);
        $this->assertSame('Johannes', $items['/team/johannes']['title']);
        $this->assertFalse($items['/team/johannes']['publishedState']);
        $this->assertNull($items['/team/johannes']['published']);

        $this->assertSame('/team/alex', $items['/team/alex']['routePath']);
        $this->assertSame('Alex', $items['/team/alex']['title']);
        $this->assertTrue($items['/team/alex']['publishedState']);
        $this->assertNotNull($items['/team/alex']['published']);

        $result = $this->contentQuery->execute('sulu_io', ['de'], $builder);
        $this->assertCount(4, $result);

        $items = [];
        foreach ($result as $item) {
            $items[$item['routePath']] = $item;
        }

        $this->assertSame('/team/thomas', $items['/team/thomas']['routePath']);
        $this->assertSame('Thomas', $items['/team/thomas']['title']);
        $this->assertFalse($items['/team/thomas']['publishedState']);
        $this->assertNull($items['/team/thomas']['published']);

        $this->assertSame('/team/daniel', $items['/team/daniel']['routePath']);
        $this->assertSame('Daniel', $items['/team/daniel']['title']);
        $this->assertTrue($items['/team/daniel']['publishedState']);
        $this->assertNotNull($items['/team/daniel']['published']);

        $this->assertSame('/team/johannes', $items['/team/johannes']['routePath']);
        $this->assertSame('Johannes DE', $items['/team/johannes']['title']);
        $this->assertTrue($items['/team/johannes']['publishedState']);
        $this->assertNotNull($items['/team/johannes']['published']);

        $this->assertSame('/team/alex', $items['/team/alex']['routePath']);
        $this->assertSame('Alex', $items['/team/alex']['title']);
        $this->assertFalse($items['/team/alex']['publishedState']);
        $this->assertNull($items['/team/alex']['published']);
    }

    private function save(
        $data,
        $locale,
        $uuid = null,
        $isShadow = false,
        $shadowLocale = '',
        $state = WorkflowStage::PUBLISHED
    ) {
        if (!$isShadow) {
            /* @var ArticleDocument $document */
            try {
                $document = $uuid
                    ? $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false])
                    : $this->documentManager->create('article');
            } catch (DocumentNotFoundException $e) {
                $document = $this->documentManager->create('article');
            }
            $document->getStructure()->bind($data);
            $document->setTitle($data['title']);
            $document->setRoutePath($data['routePath']);
            $document->setStructureType('simple_with_route');
            $document->setWorkflowStage($state);
            $this->documentManager->persist($document, $locale);
        } else {
            $document = $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false]);
            $document->setShadowLocaleEnabled(true);
            $document->setShadowLocale($shadowLocale);
            $document->setLocale($locale);
            $document->setStructureType('simple_with_route');
            $this->documentManager->persist($document, $locale);
        }

        if (WorkflowStage::PUBLISHED === $state) {
            $this->documentManager->publish($document, $locale);
        }

        $this->documentManager->flush();

        return [$document->getRoutePath() => $document];
    }
}
