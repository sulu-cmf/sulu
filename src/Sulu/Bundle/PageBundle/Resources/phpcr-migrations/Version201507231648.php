<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle;

use PHPCR\Migrations\VersionInterface;
use PHPCR\NodeInterface;
use PHPCR\PhpcrMigrationsBundle\ContainerAwareInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version201507231648 implements VersionInterface, ContainerAwareInterface
{
    public const SHADOW_ON_PROPERTY = 'i18n:%s-shadow-on';

    public const SHADOW_BASE_PROPERTY = 'i18n:%s-shadow-base';

    public const TAGS_PROPERTY = 'i18n:%s-excerpt-tags';

    public const CATEGORIES_PROPERTY = 'i18n:%s-excerpt-categories';

    public const NAVIGATION_CONTEXT_PROPERTY = 'i18n:%s-navContexts';

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(?ContainerInterface $container = null): void
    {
        if (null === $container) {
            throw new \RuntimeException('Container is required to run this migration.');
        }

        $this->container = $container;
    }

    /**
     * @return void
     */
    public function up(SessionInterface $session)
    {
        $webspaceManager = $this->container->get('sulu_core.webspace.webspace_manager');
        foreach ($webspaceManager->getWebspaceCollection() as $webspace) {
            $this->upgradeWebspace($webspace);
        }
    }

    /**
     * @return void
     */
    public function down(SessionInterface $session)
    {
    }

    /**
     * Upgrade a single webspace.
     */
    private function upgradeWebspace(Webspace $webspace)
    {
        $sessionManager = $this->container->get('sulu.phpcr.session');
        $node = $sessionManager->getContentNode($webspace->getKey());

        foreach ($webspace->getAllLocalizations() as $localization) {
            $locale = $localization->getLocale();
            $propertyName = $this->getPropertyName(self::SHADOW_ON_PROPERTY, $locale);

            $this->upgradeNode($node, $propertyName, $locale);
        }
    }

    /**
     * Upgrade a single node.
     *
     * @param string $propertyName
     * @param string $locale
     */
    private function upgradeNode(NodeInterface $node, $propertyName, $locale)
    {
        foreach ($node->getNodes() as $child) {
            $this->upgradeNode($child, $propertyName, $locale);
        }

        if (false === $node->getPropertyValueWithDefault($propertyName, false)) {
            return;
        }

        $shadowLocale = $node->getPropertyValue($this->getPropertyName(self::SHADOW_BASE_PROPERTY, $locale));

        $tags = $this->getTags($node, $shadowLocale);
        $categories = $this->getCategories($node, $shadowLocale);
        $navigationContext = $this->getNavigationContext($node, $shadowLocale);

        $node->setProperty(\sprintf(self::TAGS_PROPERTY, $locale), $tags);
        $node->setProperty(\sprintf(self::CATEGORIES_PROPERTY, $locale), $categories);
        $node->setProperty(\sprintf(self::NAVIGATION_CONTEXT_PROPERTY, $locale), $navigationContext);
    }

    /**
     * Returns property-name for given pattern and locale.
     *
     * @param string $pattern
     * @param string $locale
     *
     * @return string
     */
    private function getPropertyName($pattern, $locale)
    {
        return \sprintf($pattern, $locale);
    }

    /**
     * Returns tags of given node and locale.
     *
     * @param string $locale
     *
     * @return array
     */
    private function getTags(NodeInterface $node, $locale)
    {
        return $node->getPropertyValueWithDefault(
            \sprintf(self::TAGS_PROPERTY, $locale),
            []
        );
    }

    /**
     * Returns categories of given node and locale.
     *
     * @param string $locale
     *
     * @return array
     */
    private function getCategories(NodeInterface $node, $locale)
    {
        return $node->getPropertyValueWithDefault(
            \sprintf(self::CATEGORIES_PROPERTY, $locale),
            []
        );
    }

    /**
     * Returns navigation context of given node and locale.
     *
     * @param string $locale
     *
     * @return array
     */
    private function getNavigationContext(NodeInterface $node, $locale)
    {
        return $node->getPropertyValueWithDefault(
            \sprintf(self::NAVIGATION_CONTEXT_PROPERTY, $locale),
            []
        );
    }
}
