<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Controller;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\View as SuluView;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\SmartContent\DataProviderPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Twig\Environment;

class AdminControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<UrlGeneratorInterface>
     */
    private $urlGenerator;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    /**
     * @var ObjectProphecy<TokenInterface>
     */
    private $token;

    /**
     * @var ObjectProphecy<User>
     */
    private $user;

    /**
     * @var ObjectProphecy<AdminPool>
     */
    private $adminPool;

    /**
     * @var ObjectProphecy<SerializerInterface>
     */
    private $serializer;

    /**
     * @var ObjectProphecy<ViewHandlerInterface>
     */
    private $viewHandler;

    /**
     * @var ObjectProphecy<Environment>
     */
    private $engine;

    /**
     * @var ObjectProphecy<TranslatorBagInterface>
     */
    private $translatorBag;

    /**
     * @var ObjectProphecy<MetadataProviderRegistry>
     */
    private $metadataProviderRegistry;

    /**
     * @var ObjectProphecy<ViewRegistry>
     */
    private $viewRegistry;

    /**
     * @var ObjectProphecy<NavigationRegistry>
     */
    private $navigationRegistry;

    /**
     * @var ObjectProphecy<FieldTypeOptionRegistryInterface>
     */
    private $fieldTypeOptionRegistry;

    /**
     * @var ObjectProphecy<ContactManagerInterface>
     */
    private $contactManager;

    /**
     * @var ObjectProphecy<DataProviderPoolInterface>
     */
    private $dataProviderPool;

    /**
     * @var ObjectProphecy<LinkProviderPoolInterface>
     */
    private $linkProviderPool;

    /**
     * @var ObjectProphecy<LocalizationManagerInterface>
     */
    private $localizationManager;

    /**
     * @var string
     */
    private $environment = 'prod';

    /**
     * @var string
     */
    private $suluVersion = '2.0.0-RC1';

    /**
     * @var string
     */
    private $appVersion = '666';

    private $resources = [
        'tags' => [
            'endpoint' => [
                'list' => 'sulu_tag.get_tags',
                'detail' => 'sulu_tag.get_tag',
            ],
        ],
    ];

    /**
     * @var array
     */
    private $locales = ['de', 'en'];

    /**
     * @var array
     */
    private $translations = ['de', 'en'];

    /**
     * @var string
     */
    private $fallbackLocale = 'de';

    /**
     * @var AdminController
     */
    private $adminController;

    public function setUp(): void
    {
        parent::setUp();

        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(User::class);
        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $this->engine = $this->prophesize(Environment::class);
        $this->translatorBag = $this->prophesize(TranslatorBagInterface::class);
        $this->metadataProviderRegistry = $this->prophesize(MetadataProviderRegistry::class);
        $this->viewRegistry = $this->prophesize(ViewRegistry::class);
        $this->navigationRegistry = $this->prophesize(NavigationRegistry::class);
        $this->fieldTypeOptionRegistry = $this->prophesize(FieldTypeOptionRegistryInterface::class);
        $this->contactManager = $this->prophesize(ContactManagerInterface::class);
        $this->dataProviderPool = $this->prophesize(DataProviderPoolInterface::class);
        $this->linkProviderPool = $this->prophesize(LinkProviderPoolInterface::class);
        $this->localizationManager = $this->prophesize(LocalizationManagerInterface::class);

        $this->localizationManager->getLocalizations()->willReturn([]);
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->adminController = new AdminController(
            $this->urlGenerator->reveal(),
            $this->tokenStorage->reveal(),
            $this->adminPool->reveal(),
            $this->serializer->reveal(),
            $this->viewHandler->reveal(),
            $this->engine->reveal(),
            $this->translatorBag->reveal(),
            $this->metadataProviderRegistry->reveal(),
            $this->viewRegistry->reveal(),
            $this->navigationRegistry->reveal(),
            $this->fieldTypeOptionRegistry->reveal(),
            $this->contactManager->reveal(),
            $this->dataProviderPool->reveal(),
            $this->linkProviderPool->reveal(),
            $this->localizationManager->reveal(),
            $this->environment,
            $this->suluVersion,
            $this->appVersion,
            $this->resources,
            $this->locales,
            $this->translations,
            $this->fallbackLocale,
            10,
            true
        );
    }

    public function testConfigAction(): void
    {
        $views = [
            new SuluView('sulu_snippet.list', '/snippets', 'sulu_admin.list'),
        ];
        $this->viewRegistry->getViews()->willReturn($views);

        $navigationItem1 = new NavigationItem('navigation_item1');
        $navigationItem2 = new NavigationItem('navigation_item2');
        $this->navigationRegistry->getNavigationItems()->willReturn([$navigationItem1, $navigationItem2]);

        $this->urlGenerator->generate('view_id_1')->willReturn('/path1');
        $this->urlGenerator->generate('view_id_2')->willReturn('/path2');
        $this->urlGenerator->generate('sulu_admin.metadata', ['type' => ':type', 'key' => ':key'])
            ->willReturn('/admin/metadata');
        $this->urlGenerator->generate('sulu_preview.start')->willReturn('/preview/start');
        $this->urlGenerator->generate('sulu_preview.render')->willReturn('/preview/render');
        $this->urlGenerator->generate('sulu_preview.update')->willReturn('/preview/update');
        $this->urlGenerator->generate('sulu_preview.update-context')->willReturn('/preview/update-context');
        $this->urlGenerator->generate('sulu_preview.stop')->willReturn('/preview/stop');
        $this->urlGenerator->generate('sulu_security.cget_security-contexts')->willReturn('/api/security-contexts');
        $this->urlGenerator->generate('sulu_website.cache.remove')->willReturn('/admin/website/cache');
        $this->urlGenerator->generate('sulu_media.redirect', ['id' => ':id'])->willReturn('/media/redirect');

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $fieldTypeOptions = ['selection' => []];
        $this->fieldTypeOptionRegistry->toArray()->willReturn($fieldTypeOptions);

        $dataProviders = [];
        $this->dataProviderPool->getAll()->willReturn($dataProviders);

        $admin1 = $this->prophesize(Admin::class);
        $admin1Config = ['test1' => 'value1'];
        $admin1->getConfig()->willReturn($admin1Config);
        $admin1->getConfigKey()->willReturn('admin1');

        $admin2 = $this->prophesize(Admin::class);
        $admin2Config = ['test2' => 'value2'];
        $admin2->getConfig()->willReturn($admin2Config);
        $admin2->getConfigKey()->willReturn('admin2');

        $admin3 = $this->prophesize(Admin::class);
        $admin3->getConfig()->shouldBeCalled();
        $admin3->getConfigKey()->shouldBeCalled();

        $this->adminPool->getAdmins()->willReturn([$admin1, $admin2, $admin3]);

        $this->viewHandler->handle(
            Argument::that(
                function(View $view) use ($dataProviders, $fieldTypeOptions, $views, $admin1Config, $admin2Config) {
                    $data = $view->getData();

                    return 'json' === $view->getFormat()
                        && $data['sulu_admin']['fieldTypeOptions'] === $fieldTypeOptions
                        && $data['sulu_admin']['smartContent'] === $dataProviders
                        && $data['sulu_admin']['routes'] === $views
                        && 'navigation_item1' === $data['sulu_admin']['navigation'][0]['title']
                        && 'navigation_item2' === $data['sulu_admin']['navigation'][1]['title']
                        && $data['sulu_admin']['resources'] === $this->resources
                        && true === $data['sulu_admin']['collaborationEnabled']
                        && 10000 === $data['sulu_admin']['collaborationInterval']
                        && $data['admin1'] === $admin1Config
                        && $data['admin2'] === $admin2Config;
                }
            )
        )->shouldBeCalled()->willReturn(new Response());

        $this->adminController->configAction();
    }

    public function testMetadataAction(): void
    {
        $form = new FormMetadata();

        $this->user->getLocale()->willReturn('en');

        $metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $metadataProvider->getMetadata('pages', 'en', [])->willReturn($form);
        $this->metadataProviderRegistry->getMetadataProvider('form')->willReturn($metadataProvider);

        $this->viewHandler->handle(Argument::that(function(View $view) use ($form) {
            return $form === $view->getData();
        }))->shouldBeCalled()->willReturn(new Response());

        $this->adminController->metadataAction('form', 'pages', new Request());
    }

    public function testMetadataActionWithOptions(): void
    {
        $form = new FormMetadata();

        $this->user->getLocale()->willReturn('en');

        $metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $metadataProvider->getMetadata('pages', 'en', ['id' => 1])->willReturn($form);
        $this->metadataProviderRegistry->getMetadataProvider('form')->willReturn($metadataProvider);

        $this->viewHandler->handle(Argument::that(function(View $view) use ($form) {
            return $form === $view->getData();
        }))->shouldBeCalled()->willReturn(new Response());

        $request = new Request();
        $request->query->add(['id' => 1]);
        $this->adminController->metadataAction('form', 'pages', $request);
    }

    public static function provideTranslationsAction()
    {
        return [
            [
                'en',
                ['save' => 'Save'],
                [],
                '{"save":"Save"}',
            ],
            [
                'de',
                ['save' => 'Speichern'],
                [],
                '{"save":"Speichern"}',
            ],
            [
                'de',
                ['save' => 'Speichern'],
                ['save' => 'Save', 'delete' => 'Delete'],
                '{"save":"Speichern","delete":"Delete"}',
            ],
            [
                'bg',
                [],
                [],
                '{}',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTranslationsAction')]
    public function testTranslationsAction($locale, $translations, $fallbackTranslations, $resultTranslations): void
    {
        $request = new Request(['locale' => $locale]);

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $catalogue = $this->prophesize(MessageCatalogueInterface::class);
        $catalogue->all('admin')->willReturn($translations);
        $fallbackCatalogue = $this->prophesize(MessageCatalogueInterface::class);
        $fallbackCatalogue->all('admin')->willReturn($fallbackTranslations);
        $catalogue->getFallbackCatalogue()->willReturn($fallbackCatalogue);
        $this->translatorBag->getCatalogue($locale)->willReturn($catalogue->reveal());

        $response = $this->adminController->translationsAction($request);
        $this->assertEquals($resultTranslations, $response->getContent());
    }

    public function testTranslationActionWithoutFallback(): void
    {
        $request = new Request(['locale' => 'en']);

        $contact = $this->prophesize(ContactInterface::class);
        $contact->getId()->willReturn(5);

        $this->user->getContact()->willReturn($contact->reveal());
        $this->user->getLocale()->willReturn('en');

        $catalogue = $this->prophesize(MessageCatalogueInterface::class);
        $catalogue->all('admin')->willReturn(['save' => 'Save']);
        $catalogue->getFallbackCatalogue()->willReturn(null);
        $this->translatorBag->getCatalogue('en')->willReturn($catalogue->reveal());

        $response = $this->adminController->translationsAction($request);
        $this->assertEquals(['save' => 'Save'], \json_decode($response->getContent(), true));
    }
}
