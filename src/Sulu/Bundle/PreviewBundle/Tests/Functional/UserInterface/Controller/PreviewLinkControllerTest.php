<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Functional\UserInterface\Controller;

use Gedmo\Sluggable\Util\Urlizer;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PreviewLinkControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var BasePageDocument
     */
    private $homePage;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var string
     */
    private $resourceKey = 'pages';

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_io';

    /**
     * @var string
     */
    private $locale = 'en';

    public function setUp(): void
    {
        $client = static::createAuthenticatedClient();

        $this->client = $client;

        static::initPhpcr();
        $this->documentManager = static::getContainer()->get('sulu_document_manager.document_manager');

        /** @var BasePageDocument $document */
        $document = $this->documentManager->find(\sprintf('/cmf/%s/contents', $this->webspaceKey), $this->locale);
        $this->homePage = $document;
    }

    public function testGetAction(): void
    {
        $page = $this->createPage(__METHOD__);
        $resourceId = $page->getUuid();

        $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/preview-links/%s?resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            )
        );

        static::assertHttpStatusCode(200, $this->client->getResponse());
        $json = \json_decode((string) $this->client->getResponse()->getContent(), true);

        static::assertEquals($this->resourceKey, $json['resourceKey']);
        static::assertEquals($resourceId, $json['resourceId']);
        static::assertEquals($this->locale, $json['locale']);
        static::assertEquals(['webspaceKey' => $this->webspaceKey], $json['options']);
        static::assertIsString($json['token']);
        static::assertNotNull($json['lastVisit']);
        static::assertSame(1, $json['visitCount']);
    }

    public function testGetActionNotFound(): void
    {
        $page = $this->createPage(__METHOD__);
        $resourceId = $page->getUuid();

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/preview-links/%s?resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            )
        );

        static::assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGenerate(): void
    {
        $page = $this->createPage(__METHOD__);
        $resourceId = $page->getUuid();

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=generate&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            )
        );

        static::assertHttpStatusCode(201, $this->client->getResponse());
        $json = \json_decode((string) $this->client->getResponse()->getContent(), true);

        static::assertEquals($this->resourceKey, $json['resourceKey']);
        static::assertEquals($resourceId, $json['resourceId']);
        static::assertEquals($this->locale, $json['locale']);
        static::assertEquals(['webspaceKey' => $this->webspaceKey], $json['options']);
        static::assertIsString($json['token']);
        static::assertNull($json['lastVisit']);
        static::assertSame(0, $json['visitCount']);
    }

    public function testRevoke(): void
    {
        $page = $this->createPage(__METHOD__);
        $resourceId = $page->getUuid();

        $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=revoke&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            )
        );

        static::assertHttpStatusCode(204, $this->client->getResponse());
    }

    protected function createPreviewLink(
        string $resourceKey,
        string $resourceId,
        string $locale,
        string $webspaceKey
    ): PreviewLinkInterface {
        $repository = static::getContainer()->get('sulu_preview.preview_link_repository');
        $previewLink = $repository->create($resourceKey, $resourceId, $locale, ['webspaceKey' => $webspaceKey]);
        $previewLink->increaseVisitCount();
        $repository->add($previewLink);
        $repository->commit();

        return $previewLink;
    }

    private function createPage(string $title): BasePageDocument
    {
        $page = new PageDocument();
        $page->setTitle($title);
        $page->setResourceSegment('/' . Urlizer::urlize($title));
        $page->setParent($this->homePage);
        $page->setStructureType('default');
        $page->getStructure()->bind(
            [
                'title' => 'World',
            ],
            true
        );

        $this->documentManager->persist($page, $this->locale);
        $this->documentManager->flush();

        return $page;
    }
}
