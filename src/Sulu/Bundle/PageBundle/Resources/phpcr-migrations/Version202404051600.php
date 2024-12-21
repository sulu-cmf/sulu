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
use Sulu\Component\Content\Document\Subscriber\ShadowCopyPropertiesSubscriber;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version202404051600 implements VersionInterface, ContainerAwareInterface
{
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

    public function up(SessionInterface $session): void
    {
        $webspaceManager = $this->container->get('sulu_core.webspace.webspace_manager');
        foreach ($webspaceManager->getWebspaceCollection() as $webspace) {
            $this->upgradeWebspace($webspace);
        }
    }

    public function down(SessionInterface $session): void
    {
    }

    /**
     * Upgrade a single webspace.
     */
    private function upgradeWebspace(Webspace $webspace): void
    {
        $liveSession = $this->container->get('sulu_document_manager.live_session');
        $defaultSession = $this->container->get('sulu_document_manager.default_session');
        $sessionManager = $this->container->get('sulu.phpcr.session');
        $node = $sessionManager->getContentNode($webspace->getKey());

        $liveNode = $liveSession->getNode($node->getPath());

        foreach ($webspace->getAllLocalizations() as $localization) {
            $locale = $localization->getLocale();
            $propertyName = $this->getPropertyName(ShadowCopyPropertiesSubscriber::SHADOW_ON_PROPERTY, $locale);

            $this->upgradeNode($node, $propertyName, $locale);
            $this->upgradeNode($liveNode, $propertyName, $locale);
        }

        $liveSession->save();
        $defaultSession->save();
    }

    private function upgradeNode(NodeInterface $node, string $propertyName, string $locale): void
    {
        foreach ($node->getNodes() as $child) {
            $this->upgradeNode($child, $propertyName, $locale);
        }

        if (false === $node->getPropertyValueWithDefault($propertyName, false)) {
            return;
        }

        /** @var string $shadowLocale */
        $shadowLocale = $node->getPropertyValue($this->getPropertyName(
            \str_replace('*', '%s', ShadowCopyPropertiesSubscriber::SHADOW_BASE_PROPERTY),
            $locale
        ));

        $tags = $this->getTags($node, $shadowLocale);
        $categories = $this->getCategories($node, $shadowLocale);
        $navigationContext = $this->getNavigationContext($node, $shadowLocale);
        $author = $this->getAuthor($node, $shadowLocale);
        $authored = $this->getAuthored($node, $shadowLocale);
        $template = $this->getTemplate($node, $shadowLocale);

        $node->setProperty(\sprintf(ShadowCopyPropertiesSubscriber::TAGS_PROPERTY, $locale), $tags);
        $node->setProperty(\sprintf(ShadowCopyPropertiesSubscriber::CATEGORIES_PROPERTY, $locale), $categories);
        $node->setProperty(\sprintf(ShadowCopyPropertiesSubscriber::NAVIGATION_CONTEXT_PROPERTY, $locale), $navigationContext);
        $node->setProperty(\sprintf(ShadowCopyPropertiesSubscriber::AUTHOR_PROPERTY, $locale), $author);
        $node->setProperty(\sprintf(ShadowCopyPropertiesSubscriber::AUTHORED_PROPERTY, $locale), $authored);
        $node->setProperty(\sprintf(ShadowCopyPropertiesSubscriber::TEMPLATE_PROPERTY, $locale), $template);
    }

    private function getPropertyName(string $pattern, string $locale): string
    {
        return \sprintf($pattern, $locale);
    }

    /**
     * @return int[]
     */
    private function getTags(NodeInterface $node, string $locale): array
    {
        /** @var int[] $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::TAGS_PROPERTY, $locale),
            []
        );

        return $result;
    }

    /**
     * @return int[]
     */
    private function getCategories(NodeInterface $node, string $locale): array
    {
        /** @var int[] $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::CATEGORIES_PROPERTY, $locale),
            []
        );

        return $result;
    }

    /**
     * @return string[]
     */
    private function getNavigationContext(NodeInterface $node, string $locale): array
    {
        /** @var string[] $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::NAVIGATION_CONTEXT_PROPERTY, $locale),
            []
        );

        return $result;
    }

    private function getAuthor(NodeInterface $node, string $locale): ?string
    {
        /** @var string|null $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::AUTHOR_PROPERTY, $locale),
            null
        );

        return $result;
    }

    private function getAuthored(NodeInterface $node, string $locale): ?\DateTimeInterface
    {
        /** @var \DateTimeInterface|null $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::AUTHORED_PROPERTY, $locale),
            null
        );

        return $result;
    }

    private function getTemplate(NodeInterface $node, string $locale): ?string
    {
        /** @var string|null $result */
        $result = $node->getPropertyValueWithDefault(
            \sprintf(ShadowCopyPropertiesSubscriber::TEMPLATE_PROPERTY, $locale),
            null
        );

        return $result;
    }
}
