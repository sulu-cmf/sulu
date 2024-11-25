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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\FormViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\PreviewFormViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ShadowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

/**
 * @final
 */
class ContentViewBuilderFactory implements ContentViewBuilderFactoryInterface
{
    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var PreviewObjectProviderRegistryInterface
     */
    private $objectProviderRegistry;

    /**
     * @var ContentMetadataInspectorInterface
     */
    private $contentMetadataInspector;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var array<string, array{instanceOf: class-string}>
     */
    private $settingsForms;

    /**
     * @param array<string, array{instanceOf: class-string}> $settingsForms
     */
    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        PreviewObjectProviderRegistryInterface $objectProviderRegistry,
        ContentMetadataInspectorInterface $contentMetadataInspector,
        SecurityCheckerInterface $securityChecker,
        array $settingsForms
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->objectProviderRegistry = $objectProviderRegistry;
        $this->contentMetadataInspector = $contentMetadataInspector;
        $this->securityChecker = $securityChecker;
        $this->settingsForms = $settingsForms;
    }

    public function getDefaultToolbarActions(
        string $contentRichEntityClass
    ): array {
        $dimensionContentClass = $this->contentMetadataInspector->getDimensionContentClass($contentRichEntityClass);

        $toolbarActions = [];

        if (\is_subclass_of($dimensionContentClass, WorkflowInterface::class)) {
            $toolbarActions['save'] = new ToolbarAction(
                'sulu_admin.save_with_publishing',
                [
                    'publish_visible_condition' => '(!_permissions || _permissions.live)',
                    'save_visible_condition' => '(!_permissions || _permissions.edit)',
                ]
            );
        } else {
            $toolbarActions['save'] = new ToolbarAction(
                'sulu_admin.save'
            );
        }

        if (\is_subclass_of($dimensionContentClass, TemplateInterface::class)) {
            $toolbarActions['type'] = new ToolbarAction(
                'sulu_admin.type',
                [
                    'disabled_condition' => '(_permissions && !_permissions.edit)',
                ]
            );
        }

        $toolbarActions['delete'] = new ToolbarAction(
            'sulu_admin.delete',
            [
                'visible_condition' => '(!_permissions || _permissions.delete) && url != "/"',
            ]
        );

        if (\is_subclass_of($dimensionContentClass, WorkflowInterface::class)) {
            $toolbarActions['edit'] = new DropdownToolbarAction(
                'sulu_admin.edit',
                'su-pen',
                [
                    new ToolbarAction(
                        'sulu_admin.delete_draft',
                        [
                            'visible_condition' => '(!_permissions || _permissions.live)',
                        ]
                    ),
                    new ToolbarAction(
                        'sulu_admin.set_unpublished',
                        [
                            'visible_condition' => '(!_permissions || _permissions.live)',
                        ]
                    ),
                ]
            );
        }

        return $toolbarActions;
    }

    public function createViews(
        string $contentRichEntityClass,
        string $editParentView,
        ?string $addParentView = null,
        ?string $securityContext = null,
        ?array $toolbarActions = null
    ): array {
        $dimensionContentClass = $this->contentMetadataInspector->getDimensionContentClass($contentRichEntityClass);

        $resourceKey = $dimensionContentClass::getResourceKey();
        $previewEnabled = $this->objectProviderRegistry->hasPreviewObjectProvider($resourceKey);

        $toolbarActions = $toolbarActions ?: $this->getDefaultToolbarActions($contentRichEntityClass);
        $addToolbarActions = $toolbarActions;

        $settingsToolbarActions = [];
        $seoAndExcerptToolbarActions = [];
        if (isset($toolbarActions['save'])) {
            $seoAndExcerptToolbarActions = ['save' => $toolbarActions['save']];
            $settingsToolbarActions = ['save' => $toolbarActions['save']];
        }
        if (isset($toolbarActions['edit'])) {
            $seoAndExcerptToolbarActions['edit'] = $toolbarActions['edit'];
            $settingsToolbarActions['edit'] = $toolbarActions['edit'];
        }

        if (!$this->hasPermission($securityContext, PermissionTypes::EDIT)) {
            unset($toolbarActions['save'], $seoAndExcerptToolbarActions['save'], $settingsToolbarActions['save']);
        }

        if (!$this->hasPermission($securityContext, PermissionTypes::LIVE)) {
            unset($toolbarActions['edit'], $addToolbarActions['edit']);
        }

        if (!$this->hasPermission($securityContext, PermissionTypes::DELETE)) {
            unset($toolbarActions['delete'], $addToolbarActions['delete']);
        }

        $views = [];

        if ($this->hasPermission($securityContext, PermissionTypes::ADD)) {
            if ($addParentView) {
                if (\is_subclass_of($dimensionContentClass, TemplateInterface::class)) {
                    /** @var FormViewBuilderInterface|PreviewFormViewBuilderInterface $templateFormView */
                    $templateFormView = $this->createTemplateFormView(
                        $addParentView,
                        false,
                        $resourceKey,
                        $dimensionContentClass::getTemplateType(),
                        $addToolbarActions
                    );

                    $templateFormView->setEditView($editParentView);

                    $views[] = $templateFormView;
                }
            }
        }

        if ($this->hasPermission($securityContext, PermissionTypes::EDIT)) {
            if (\is_subclass_of($dimensionContentClass, TemplateInterface::class)) {
                $views[] = $this->createTemplateFormView(
                    $editParentView,
                    $previewEnabled,
                    $resourceKey,
                    $dimensionContentClass::getTemplateType(),
                    $toolbarActions
                );
            }

            if (\is_subclass_of($dimensionContentClass, SeoInterface::class)) {
                $views[] = $this->createSeoFormView(
                    $editParentView,
                    $previewEnabled,
                    $resourceKey,
                    $seoAndExcerptToolbarActions
                );
            }

            if (\is_subclass_of($dimensionContentClass, ExcerptInterface::class)) {
                $views[] = $this->createExcerptFormView(
                    $editParentView,
                    $previewEnabled,
                    $resourceKey,
                    $seoAndExcerptToolbarActions
                );
            }

            if (\is_subclass_of($dimensionContentClass, ShadowInterface::class)) {
                foreach ($views as $view) {
                    if ($view instanceof FormViewBuilderInterface || $view instanceof PreviewFormViewBuilderInterface) {
                        $view->setTabCondition('shadowOn != true');
                    }
                }
            }

            $views[] = $this->createSettingsFormView(
                $editParentView,
                $previewEnabled,
                $resourceKey,
                $settingsToolbarActions,
                $dimensionContentClass
            );
        }

        return $views;
    }

    /**
     * @param array<string, ToolbarAction> $toolbarActions
     */
    private function createTemplateFormView(
        string $parentView,
        bool $previewEnabled,
        string $resourceKey,
        string $formKey,
        array $toolbarActions
    ): ViewBuilderInterface {
        return $this->createFormViewBuilder($parentView . '.content', '/content', $previewEnabled)
            ->setResourceKey($resourceKey)
            ->setFormKey($formKey)
            ->setTabTitle('sulu_content.content')
            ->addToolbarActions(\array_values($toolbarActions))
            ->setTabOrder(20)
            ->setParent($parentView);
    }

    /**
     * @param array<string, ToolbarAction> $toolbarActions
     */
    private function createSeoFormView(
        string $parentView,
        bool $previewEnabled,
        string $resourceKey,
        array $toolbarActions
    ): ViewBuilderInterface {
        return $this->createFormViewBuilder($parentView . '.seo', '/seo', $previewEnabled)
            ->setResourceKey($resourceKey)
            ->setFormKey('content_seo')
            ->setTabTitle('sulu_content.seo')
            ->setTitleVisible(true)
            ->addToolbarActions(\array_values($toolbarActions))
            ->setTabOrder(30)
            ->setParent($parentView);
    }

    /**
     * @param array<string, ToolbarAction> $toolbarActions
     */
    private function createExcerptFormView(
        string $parentView,
        bool $previewEnabled,
        string $resourceKey,
        array $toolbarActions
    ): ViewBuilderInterface {
        return $this->createFormViewBuilder($parentView . '.excerpt', '/excerpt', $previewEnabled)
            ->setResourceKey($resourceKey)
            ->setFormKey('content_excerpt')
            ->setTabTitle('sulu_content.excerpt')
            ->setTitleVisible(true)
            ->addToolbarActions(\array_values($toolbarActions))
            ->setTabOrder(40)
            ->setParent($parentView);
    }

    /**
     * @param array<string, ToolbarAction> $toolbarActions
     */
    private function createSettingsFormView(
        string $parentView,
        bool $previewEnabled,
        string $resourceKey,
        array $toolbarActions,
        string $dimensionContentClass
    ): ViewBuilderInterface {
        $forms = [];
        foreach ($this->settingsForms as $key => $tag) {
            if (\is_subclass_of($dimensionContentClass, $tag['instanceOf'])) {
                $forms[] = $key;
            }
        }

        return $this->createFormViewBuilder($parentView . '.settings', '/settings', $previewEnabled)
            ->addMetadataRequestParameters(['forms' => $forms])
            ->setResourceKey($resourceKey)
            ->setFormKey('content_settings')
            ->setTabTitle('sulu_page.settings')
            ->setTitleVisible(true)
            ->addToolbarActions(\array_values($toolbarActions))
            ->setTabOrder(50)
            ->setParent($parentView);
    }

    /**
     * @return PreviewFormViewBuilderInterface|FormViewBuilderInterface
     */
    private function createFormViewBuilder(string $name, string $path, bool $previewEnabled): ViewBuilderInterface
    {
        if ($previewEnabled) {
            return $this->viewBuilderFactory->createPreviewFormViewBuilder($name, $path);
        }

        return $this->viewBuilderFactory->createFormViewBuilder($name, $path);
    }

    private function hasPermission(?string $securityContext, string $permissionType): bool
    {
        if (!$securityContext) {
            return true;
        }

        return $this->securityChecker->hasPermission($securityContext, $permissionType);
    }
}
