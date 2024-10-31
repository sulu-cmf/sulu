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

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Controller;

use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AdminControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
    }

    public function testGetConfig(): void
    {
        $this->initPhpcr();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent() ?: '');

        $this->assertTrue(\property_exists($response, 'sulu_admin'));
        $this->assertTrue(\property_exists($response->sulu_admin, 'navigation'));
        $this->assertTrue(\property_exists($response->sulu_admin, 'resources'));
        $this->assertTrue(\property_exists($response->sulu_admin, 'routes'));
        $this->assertTrue(\property_exists($response->sulu_admin, 'fieldTypeOptions'));
        $this->assertIsArray($response->sulu_admin->navigation);
        $this->assertIsArray($response->sulu_admin->routes);
        $this->assertIsObject($response->sulu_admin->resources);
        $this->assertTrue(\property_exists($response, 'sulu_preview'));

        $this->assertSame('en', $response->sulu_admin->localizations[0]->localization);
        $this->assertSame('en_us', $response->sulu_admin->localizations[1]->localization);
        $this->assertSame('de', $response->sulu_admin->localizations[2]->localization);
        $this->assertSame('de_at', $response->sulu_admin->localizations[3]->localization);
    }

    public function testGetConfigWithFallbackNonExistUserLocale(): void
    {
        $this->initPhpcr();

        $this->getTestUser()->setLocale('not-exist');

        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent() ?: '');

        $this->assertTrue(\property_exists($response, 'sulu_admin'));
        $this->assertTrue(\property_exists($response->sulu_admin, 'navigation'));
        $this->assertTrue(\property_exists($response->sulu_admin, 'resources'));
        $this->assertTrue(\property_exists($response->sulu_admin, 'routes'));
        $this->assertTrue(\property_exists($response->sulu_admin, 'fieldTypeOptions'));
        $this->assertIsArray($response->sulu_admin->navigation);
        $this->assertIsArray($response->sulu_admin->routes);
        $this->assertIsObject($response->sulu_admin->resources);
        $this->assertTrue(\property_exists($response, 'sulu_preview'));

        $this->assertSame('en', $response->sulu_admin->localizations[0]->localization);
        $this->assertSame('en_us', $response->sulu_admin->localizations[1]->localization);
        $this->assertSame('de', $response->sulu_admin->localizations[2]->localization);
        $this->assertSame('de_at', $response->sulu_admin->localizations[3]->localization);
    }

    public function testTemplateConfig(): void
    {
        $this->initPhpcr();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $crawler = $this->client->request('GET', '/admin/');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $html = $response->getContent();
        $this->assertIsString($html);

        // extract json from html
        $applicationElement = $crawler->filter('#application')->first();
        $this->assertCount(1, $applicationElement, 'Failed getting the application element');
        $configJson = $applicationElement->attr('data-config');
        $this->assertIsString($configJson, 'Failed to retrieve the configuration json');
        $config = \json_decode($configJson, true);

        // test config object
        $this->assertIsArray($config, 'Extracted config is not a valid json object.');
        $this->assertSame([
            'initialLoginState' => true,
            'translations' => [
                'de',
                'en',
            ],
            'fallbackLocale' => 'en',
            'endpoints' => [
                'config' => '/admin/config',
                'items' => '/admin/api/items',
                'loginCheck' => '/admin/login',
                'logout' => '/admin/logout',
                'profileSettings' => '/admin/api/profile/settings',
                'forgotPasswordReset' => '/admin/security/reset/email',
                'resetPassword' => '/admin/security/reset',
                'translations' => '/admin/translations',
                'generateUrl' => '/admin/api/resourcelocators?action=generate',
                'routing' => '/admin/js/routing',
                'has_single_sign_on' => false,
            ],
            'suluVersion' => '_._._',
            'appVersion' => null,
            'passwordPattern' => null,
            'passwordInfoTranslationKey' => null,
            'hasSingleSignOn' => false,
        ], $config);
    }

    public function testGetNotExistingMetdata(): void
    {
        $this->client->jsonRequest('GET', '/admin/metadata/test1/test');

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGetMetaDataKeysOnly(): void
    {
        $this->initPhpcr();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->request('GET', '/admin/metadata/form/page?onlyKeys=true');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $json = $response->getContent();
        $this->assertIsString($json);

        /** @var array{defaultType: string|null, types: array<string, array<mixed>>} $metaData */
        $metaData = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertSame($metaData['defaultType'], null);
        foreach ($metaData['types'] as $type) {
            $this->assertSame($type, []);
        }
    }

    public function testGetMetaData(): void
    {
        $this->initPhpcr();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->request('GET', '/admin/metadata/form/page');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $json = $response->getContent();
        $this->assertIsString($json);

        /** @var array{types: array<string, mixed>} $metaData */
        $metaData = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        $defaultMetadata = $metaData['types']['default'];
        $overviewMetadata = $metaData['types']['overview'];
        $this->assertSame(
            \json_decode((string) \file_get_contents(__DIR__ . '/fixtures/default.json'), true),
            $defaultMetadata);
        $this->assertSame(
            \json_decode((string) \file_get_contents(__DIR__ . '/fixtures/overview.json'), true),
            $overviewMetadata);
    }

    public function testGetMetaDataGhostLocale(): void
    {
        $this->initPhpcr();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->request('GET', '/admin/metadata/form/ghost_copy_locale?locales[]=de&locales[]=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $json = $response->getContent();
        $this->assertIsString($json);

        /**
         * @var array{
         *     form: array{
         *         locale: array{
         *             options: array{
         *                 default_value: array{
         *                     value: string,
         *                 },
         *                 values: array{
         *                     value: array<array{
         *                         name: string,
         *                         value: string,
         *                         title: string,
         *                     }>,
         *                 },
         *             },
         *         },
         *     }
         * } $metaData
         */
        $metaData = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        $form = $metaData['form'];

        $this->assertArrayHasKey('locale', $form);
        $this->assertSame('de', $form['locale']['options']['default_value']['value']);
        $this->assertCount(2, $form['locale']['options']['values']['value']);
        $this->assertSame('de', $form['locale']['options']['values']['value'][0]['name']);
        $this->assertSame('de', $form['locale']['options']['values']['value'][0]['value']);
        $this->assertSame('de', $form['locale']['options']['values']['value'][0]['title']);
        $this->assertSame('en', $form['locale']['options']['values']['value'][1]['name']);
        $this->assertSame('en', $form['locale']['options']['values']['value'][1]['value']);
        $this->assertSame('en', $form['locale']['options']['values']['value'][1]['title']);
    }

    public function testGetMetaDataCopyLocale(): void
    {
        $this->initPhpcr();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->request('GET', '/admin/metadata/form/copy_locale?locales[]=de&locales[]=en&locales[]=fr');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $json = $response->getContent();
        $this->assertIsString($json);

        /**
         * @var array{
         *     form: array{
         *         locales: array{
         *             options: array{
         *                 values: array{
         *                     value: array<array{
         *                         name: string,
         *                         value: string,
         *                         title: string,
         *                     }>,
         *                 },
         *             },
         *         },
         *     },
         * } $metaData
         */
        $metaData = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        $form = $metaData['form'];

        $this->assertArrayHasKey('locales', $form);
        $this->assertCount(3, $form['locales']['options']['values']['value']);
        $this->assertSame('de', $form['locales']['options']['values']['value'][0]['name']);
        $this->assertSame('de', $form['locales']['options']['values']['value'][0]['value']);
        $this->assertSame('de', $form['locales']['options']['values']['value'][0]['title']);
        $this->assertSame('en', $form['locales']['options']['values']['value'][1]['name']);
        $this->assertSame('en', $form['locales']['options']['values']['value'][1]['value']);
        $this->assertSame('en', $form['locales']['options']['values']['value'][1]['title']);
        $this->assertSame('fr', $form['locales']['options']['values']['value'][2]['name']);
        $this->assertSame('fr', $form['locales']['options']['values']['value'][2]['value']);
        $this->assertSame('fr', $form['locales']['options']['values']['value'][2]['title']);
    }
}
