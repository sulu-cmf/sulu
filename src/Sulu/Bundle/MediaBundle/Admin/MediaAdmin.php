<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Admin;

use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactoryInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MediaAdmin extends Admin
{
    public const SECURITY_CONTEXT = 'sulu.media.collections';

    public const SECURITY_CONTEXT_GROUP = 'Media';

    public const MEDIA_OVERVIEW_VIEW = 'sulu_media.overview';

    public const EDIT_FORM_VIEW = 'sulu_media.form';

    public const EDIT_FORM_DETAILS_VIEW = 'sulu_media.form.details';

    public const EDIT_FORM_FORMATS_VIEW = 'sulu_media.form.formats';

    public const EDIT_FORM_HISTORY_VIEW = 'sulu_media.form.history';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
        private LocalizationManagerInterface $localizationManager,
        private UrlGeneratorInterface $urlGenerator,
        private WebspaceManagerInterface $webspaceManager,
        private ActivityViewBuilderFactoryInterface $activityViewBuilderFactory,
        private ReferenceViewBuilderFactoryInterface $referenceViewBuilderFactory
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $media = new NavigationItem('sulu_media.media');
            $media->setPosition(30);
            $media->setIcon('su-image');
            $media->setView(static::MEDIA_OVERVIEW_VIEW);
            $media->addChildView(static::EDIT_FORM_VIEW);
            $media->addChildView(static::EDIT_FORM_DETAILS_VIEW);
            $media->addChildView(static::EDIT_FORM_FORMATS_VIEW);
            $media->addChildView(static::EDIT_FORM_HISTORY_VIEW);

            $navigationItemCollection->add($media);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $mediaLocales = $this->localizationManager->getLocales();

        $toolbarActions = [
            new ToolbarAction('sulu_admin.save', [
                'visible_condition' => '(_permissions && _permissions.edit)',
            ]),
            new ToolbarAction('sulu_admin.delete', [
                'visible_condition' => '(!_permissions || _permissions.delete)',
            ]),
        ];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createViewBuilder(
                        static::MEDIA_OVERVIEW_VIEW,
                        '/collections/:locale/:id?',
                        'sulu_media.overview'
                    )
                    ->setOption('locales', $mediaLocales)
                    ->setOption('permissions', [
                        'add' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD),
                        'delete' => $this->securityChecker->hasPermission(
                            static::SECURITY_CONTEXT,
                            PermissionTypes::DELETE
                        ),
                        'edit' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT),
                    ])
                    ->setAttributeDefault('locale', $mediaLocales[0])
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::EDIT_FORM_VIEW, '/media/:locale/:id')
                    ->setResourceKey(MediaInterface::RESOURCE_KEY)
                    ->addLocales($mediaLocales)
                    ->setTitleProperty('title')
                    ->setBackView(static::MEDIA_OVERVIEW_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder(static::EDIT_FORM_DETAILS_VIEW, '/details')
                    ->setResourceKey(MediaInterface::RESOURCE_KEY)
                    ->setFormKey('media_details')
                    ->setTabTitle('sulu_media.information_taxonomy')
                    ->addToolbarActions($toolbarActions)
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createViewBuilder(static::EDIT_FORM_FORMATS_VIEW, '/formats', 'sulu_media.formats')
                    ->setOption('tabTitle', 'sulu_media.formats')
                    ->setParent(static::EDIT_FORM_VIEW)
            );

            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createViewBuilder(static::EDIT_FORM_HISTORY_VIEW, '/history', 'sulu_media.history')
                    ->setOption('tabTitle', 'sulu_media.history')
                    ->setParent(static::EDIT_FORM_VIEW)
            );

            if ($this->activityViewBuilderFactory->hasActivityListPermission() || $this->referenceViewBuilderFactory->hasReferenceListPermission()) {
                $insightsResourceTabViewName = MediaAdmin::EDIT_FORM_VIEW . '.insights';

                $viewCollection->add(
                    $this->viewBuilderFactory
                        ->createResourceTabViewBuilder($insightsResourceTabViewName, '/insights')
                        ->setResourceKey(MediaInterface::RESOURCE_KEY)
                        ->setTabOrder(6144)
                        ->setTabTitle('sulu_admin.insights')
                        ->setTitleProperty('')
                        ->setParent(MediaAdmin::EDIT_FORM_VIEW)
                );

                if ($this->activityViewBuilderFactory->hasActivityListPermission()) {
                    $viewCollection->add(
                        $this->activityViewBuilderFactory
                            ->createActivityListViewBuilder(
                                $insightsResourceTabViewName . '.activity',
                                '/activities',
                                MediaInterface::RESOURCE_KEY
                            )
                            ->setParent($insightsResourceTabViewName)
                    );
                }

                if ($this->referenceViewBuilderFactory->hasReferenceListPermission()) {
                    $viewCollection->add(
                        $this->referenceViewBuilderFactory
                            ->createReferenceListViewBuilder(
                                $insightsResourceTabViewName . '.reference',
                                '/references',
                                MediaInterface::RESOURCE_KEY
                            )
                            ->setParent($insightsResourceTabViewName)
                    );
                }
            }
        }
    }

    public function getSecurityContexts()
    {
        $securityContexts = [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                self::SECURITY_CONTEXT_GROUP => [
                    self::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                        PermissionTypes::SECURITY,
                    ],
                    'sulu.media.system_collections' => [
                        PermissionTypes::VIEW,
                    ],
                ],
            ],
        ];

        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $webspaceSecurity = $webspace->getSecurity();
            if (!$webspaceSecurity) {
                continue;
            }

            $webspaceSystem = $webspaceSecurity->getSystem();
            if (!$webspaceSystem) {
                continue;
            }

            $securityContexts[$webspaceSystem] = [
                self::SECURITY_CONTEXT_GROUP => [
                    self::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                    ],
                ],
            ];
        }

        return $securityContexts;
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_media';
    }

    public function getConfig(): ?array
    {
        return [
            'endpoints' => [
                'image_format' => $this->urlGenerator->generate(
                    'sulu_media.redirect',
                    ['id' => ':id']
                ),
            ],
            'media_permissions' => [
                'add' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD),
                'delete' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE),
                'edit' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT),
                'security' => $this->securityChecker->hasPermission(
                    static::SECURITY_CONTEXT,
                    PermissionTypes::SECURITY
                ),
            ],
        ];
    }
}
