<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class FOSJSRoutingControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
    }

    public function testExposedRoutes(): void
    {
        $this->client->jsonRequest('GET', '/admin/js/routing.json');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $content = $response->getContent();
        $this->assertIsString($content);

        $json = \json_decode($content, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('routes', $json);

        $routes = \array_keys($json['routes']);
        \sort($routes);

        $this->assertSame([
            'sulu_activity.get_activities',
            'sulu_admin.metadata',
            'sulu_admin.put_collaborations',
            'sulu_audience_targeting.get_target-group',
            'sulu_audience_targeting.get_target-groups',
            'sulu_category.get_categories',
            'sulu_category.get_category',
            'sulu_category.get_category_keyword',
            'sulu_category.get_category_keywords',
            'sulu_contact.cget_account_medias',
            'sulu_contact.cget_contact_medias',
            'sulu_contact.delete_account_contacts',
            'sulu_contact.delete_account_medias',
            'sulu_contact.delete_contact_medias',
            'sulu_contact.get_account',
            'sulu_contact.get_account_addresses',
            'sulu_contact.get_account_contacts',
            'sulu_contact.get_account_deleteinfo',
            'sulu_contact.get_account_medias',
            'sulu_contact.get_accounts',
            'sulu_contact.get_contact',
            'sulu_contact.get_contact-position',
            'sulu_contact.get_contact-positions',
            'sulu_contact.get_contact-title',
            'sulu_contact.get_contact-titles',
            'sulu_contact.get_contact_medias',
            'sulu_contact.get_contacts',
            'sulu_core.get_localizations',
            'sulu_custom_url.cget_webspace_custom-urls',
            'sulu_custom_url.get_webspace_custom-urls',
            'sulu_custom_url.get_webspace_custom-urls_routes',
            'sulu_location.geolocator_query',
            'sulu_media.cget_media',
            'sulu_media.delete_media_version',
            'sulu_media.get_collection',
            'sulu_media.get_collections',
            'sulu_media.get_formats',
            'sulu_media.get_media',
            'sulu_media.get_media_formats',
            'sulu_media.post_media_preview',
            'sulu_media.put_media_format',
            'sulu_media.redirect',
            'sulu_page.get_icons',
            'sulu_page.get_items',
            'sulu_page.get_page',
            'sulu_page.get_page_resourcelocators',
            'sulu_page.get_pages',
            'sulu_page.get_teasers',
            'sulu_page.get_webspace',
            'sulu_page.get_webspace_localizations',
            'sulu_page.get_webspaces',
            'sulu_preview.get_preview-link',
            'sulu_routes.get_routes',
            'sulu_search_indexes',
            'sulu_search_search',
            'sulu_security.cget_security-contexts',
            'sulu_security.get_group',
            'sulu_security.get_groups',
            'sulu_security.get_permissions',
            'sulu_security.get_profile',
            'sulu_security.get_role',
            'sulu_security.get_role_setting',
            'sulu_security.get_roles',
            'sulu_security.get_security-contexts',
            'sulu_security.get_user',
            'sulu_security.get_users',
            'sulu_snippet.get_languages',
            'sulu_snippet.get_snippet',
            'sulu_snippet.get_snippet-areas',
            'sulu_snippet.get_snippets',
            'sulu_snippet.put_snippet-area',
            'sulu_tag.get_tag',
            'sulu_tag.get_tags',
            'sulu_trash.get_trash-item',
            'sulu_trash.get_trash-items',
            'sulu_website.cget_webspace_analytics',
            'sulu_website.get_webspace_analytics',
        ], $routes);
    }
}
