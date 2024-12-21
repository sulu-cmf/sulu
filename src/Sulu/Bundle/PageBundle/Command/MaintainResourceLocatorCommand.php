<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Command;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Writes the current resource locator on the cached property of the node in the live workspace.
 * The default workspace should not be touched, because these might represent changes already entered by a user.
 */
#[AsCommand(name: 'sulu:content:resource-locator:maintain', description: 'Resets the cached url value on every node in the live workspace')]
class MaintainResourceLocatorCommand extends Command
{
    public function __construct(
        private WebspaceManagerInterface $webspaceManager,
        private SessionManagerInterface $sessionManager,
        private SessionInterface $liveSession,
        private MetadataFactoryInterface $metadataFactory,
        private StructureMetadataFactoryInterface $structureMetadataFactory,
        private PropertyEncoder $propertyEncoder
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $this->upgradeWebspace($webspace, $output);
        }

        $this->liveSession->save();

        return 0;
    }

    private function upgradeWebspace(Webspace $webspace, OutputInterface $output)
    {
        $output->writeln('<info>> Upgrade Webspace: ' . $webspace->getName() . '</info>');
        foreach ($webspace->getAllLocalizations() as $localization) {
            $this->upgradeLocale($webspace, $localization, $output);
        }
    }

    private function upgradeLocale(Webspace $webspace, Localization $localization, OutputInterface $output)
    {
        $output->writeln('  > Upgrade Locale: ' . $localization->getLocale(Localization::DASH));

        $contentNode = $this->liveSession->getNode($this->sessionManager->getContentPath($webspace->getKey()));

        $this->upgradeNode($contentNode, $webspace, $localization, $output);
        $this->upgradeByParent($contentNode, $webspace, $localization, $output);
    }

    private function upgradeByParent(
        NodeInterface $parentNode,
        Webspace $webspace,
        Localization $localization,
        OutputInterface $output
    ) {
        foreach ($parentNode->getNodes() as $childNode) {
            $this->upgradeNode($childNode, $webspace, $localization, $output, \substr_count($childNode->getPath(), '/'));
            $this->upgradeByParent($childNode, $webspace, $localization, $output);
        }
    }

    private function upgradeNode(
        NodeInterface $node,
        Webspace $webspace,
        Localization $localization,
        OutputInterface $output,
        $depth = 0
    ) {
        $locale = $localization->getLocale();

        $localizedTemplatePropertyName = $this->propertyEncoder->localizedSystemName('template', $locale);
        if (!$node->hasProperty($localizedTemplatePropertyName)) {
            return;
        }

        $structureMetadata = $this->structureMetadataFactory->getStructureMetadata(
            $this->metadataFactory->getMetadataForPhpcrNode($node)->getAlias(),
            $node->getPropertyValue($localizedTemplatePropertyName)
        );

        $property = $structureMetadata->getPropertyByTagName('sulu.rlp');
        if (!$property) {
            return;
        }

        $nodeType = $node->getPropertyValue($this->propertyEncoder->localizedSystemName('nodeType', $locale));
        if ('resource_locator' !== $property->getContentTypeName() && Structure::NODE_TYPE_CONTENT !== $nodeType) {
            return;
        }

        $baseRoutePath = $this->sessionManager->getRoutePath($webspace->getKey(), $localization->getLocale());
        foreach ($node->getReferences('sulu:content') as $routeProperty) {
            if (0 !== \strpos($routeProperty->getPath(), $baseRoutePath)) {
                continue;
            }

            $routeNode = $routeProperty->getParent();
            if (true === $routeNode->getPropertyValue('sulu:history')) {
                continue;
            }

            $resourceLocator = \substr($routeNode->getPath(), \strlen($baseRoutePath));

            if ($resourceLocator) {
                // only set if resource locator is not empty
                // if the resource locator is empty it is the homepage, whose url should not be changed
                $node->setProperty(
                    $this->propertyEncoder->localizedContentName($property->getName(), $locale),
                    $resourceLocator
                );

                $prefix = '   ';
                for ($i = 0; $i < $depth; ++$i) {
                    $prefix .= '-';
                }

                $title = $node->getPropertyValue($this->propertyEncoder->localizedContentName('title', $locale));
                $output->writeln($prefix . '> "' . $title . '": ' . $resourceLocator);
            }

            break;
        }
    }
}
