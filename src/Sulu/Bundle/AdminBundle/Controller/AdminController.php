<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistryInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Twig\Environment;

class AdminController
{
    public const TRANSLATION_DOMAIN = 'admin';

    /**
     * @var bool
     */
    private $collaborationEnabled;

    /**
     * @param array<mixed> $resources
     * @param array<string> $locales
     * @param array<string> $translations
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private TokenStorageInterface $tokenStorage,
        private AdminPool $adminPool,
        private SerializerInterface $serializer,
        private ViewHandlerInterface $viewHandler,
        private Environment $engine,
        private TranslatorBagInterface $translatorBag,
        private MetadataProviderRegistry $metadataProviderRegistry,
        private ViewRegistry $viewRegistry,
        private NavigationRegistry $navigationRegistry,
        private FieldTypeOptionRegistryInterface $fieldTypeOptionRegistry,
        private ContactManagerInterface $contactManager,
        private DataProviderPoolInterface $dataProviderPool,
        private LinkProviderPoolInterface $linkProviderPool,
        private LocalizationManagerInterface $localizationManager,
        private string $environment,
        private string $suluVersion,
        private ?string $appVersion,
        private array $resources,
        private array $locales,
        private array $translations,
        private string $fallbackLocale,
        private int $collaborationInterval,
        ?bool $collaborationEnabled = null,
        private ?string $passwordPattern = null,
        private ?string $passwordInfoTranslationKey = null
    ) {
        if (null === $collaborationEnabled) {
            @trigger_deprecation('sulu/sulu', '2.3', 'Instantiating the AdminController without the $collaborationEnabled argument is deprecated!');
        }
        $this->collaborationEnabled = $collaborationEnabled ?? true;
    }

    /**
     * @return Response
     */
    public function indexAction()
    {
        $endpoints = [
            'config' => $this->urlGenerator->generate('sulu_admin.config'),
            'items' => $this->urlGenerator->generate('sulu_page.get_items'),
            'loginCheck' => $this->urlGenerator->generate('sulu_admin.login_check'),
            'logout' => $this->urlGenerator->generate('sulu_admin.logout'),
            'profileSettings' => $this->urlGenerator->generate('sulu_security.patch_profile_settings'),
            'forgotPasswordReset' => $this->urlGenerator->generate('sulu_security.reset_password.email'),
            'resetPassword' => $this->urlGenerator->generate('sulu_security.reset_password.reset'),
            'translations' => $this->urlGenerator->generate('sulu_admin.translation'),
            'generateUrl' => $this->urlGenerator->generate('sulu_page.post_resourcelocator', ['action' => 'generate']),
            'routing' => $this->urlGenerator->generate('fos_js_routing_js'),
        ];

        try {
            $endpoints['twoFactorLoginCheck'] = $this->urlGenerator->generate('2fa_login_check_admin');
        } catch (RouteNotFoundException $e) {
            // @ignoreException ignore if no 2fa_login_check_admin exist
        }

        return new Response($this->engine->render(
            '@SuluAdmin/Admin/main.html.twig',
            [
                'translations' => $this->translations,
                'fallback_locale' => $this->fallbackLocale,
                'endpoints' => $endpoints,
                'password_pattern' => $this->passwordPattern,
                'password_info_translation_key' => $this->passwordInfoTranslationKey,
                'sulu_version' => $this->suluVersion,
                'app_version' => $this->appVersion,
            ]
        ));
    }

    /**
     * Returns all the configuration for the admin interface.
     */
    public function configAction(): Response
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $locale = $user->getLocale();
        $contact = $this->contactManager->getById($user->getContact()->getId(), $locale);

        $config = [
            'sulu_admin' => [
                'fieldTypeOptions' => $this->fieldTypeOptionRegistry->toArray(),
                'internalLinkTypes' => $this->linkProviderPool->getConfiguration(),
                'localizations' => \array_values($this->localizationManager->getLocalizations()),
                'navigation' => \array_map(function(NavigationItem $navigationItem) {
                    return $navigationItem->toArray();
                }, \array_values($this->navigationRegistry->getNavigationItems())),
                'routes' => $this->viewRegistry->getViews(),
                'resources' => $this->resources,
                'smartContent' => \array_map(function(DataProviderInterface $dataProvider) {
                    return $dataProvider->getConfiguration();
                }, $this->dataProviderPool->getAll()),
                'user' => $user,
                'contact' => $contact,
                'collaborationEnabled' => $this->collaborationEnabled,
                'collaborationInterval' => $this->collaborationInterval * 1000,
            ],
        ];

        foreach ($this->adminPool->getAdmins() as $admin) {
            $adminConfigKey = $admin->getConfigKey();
            $adminConfig = $admin->getConfig();

            if ($adminConfigKey && $adminConfig) {
                $config[$adminConfigKey] = $adminConfig;
            }
        }

        $view = View::create($config);

        $context = new Context();
        $context->setGroups(['frontend', 'partialContact', 'fullView']);
        $context->setAttribute('locale', $locale);

        $view->setContext($context);
        $view->setFormat('json');

        return $this->viewHandler->handle($view);
    }

    public function translationsAction(Request $request): Response
    {
        $catalogue = $this->translatorBag->getCatalogue($request->query->get('locale'));
        $fallbackCatalogue = $catalogue->getFallbackCatalogue();

        $translations = $catalogue->all(static::TRANSLATION_DOMAIN);
        if ($fallbackCatalogue) {
            $translations = \array_replace($fallbackCatalogue->all(static::TRANSLATION_DOMAIN), $translations);
        }

        if (0 === \count($translations)) {
            $translations = new \stdClass();
        }

        return new JsonResponse($translations);
    }

    public function metadataAction(string $type, string $key, Request $request): Response
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $metadataOptions = $request->query->all();
        $metadata = $this->metadataProviderRegistry->getMetadataProvider($type)
            ->getMetadata($key, $user->getLocale(), $metadataOptions);

        $context = new Context();
        $context->addGroup('Default');
        if (true === \filter_var($metadataOptions['onlyKeys'] ?? 'false', \FILTER_VALIDATE_BOOLEAN)) {
            $context->addGroup('admin_form_metadata_keys_only');
        }

        $view = View::create($metadata);
        $view->setFormat('json');
        $view->setContext($context);

        $response = $this->viewHandler->handle($view);

        if (!$metadata->isCacheable()) {
            $response->headers->addCacheControlDirective('no-store', true);
        }

        return $response;
    }
}
