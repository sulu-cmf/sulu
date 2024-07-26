<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Document\Initializer;

use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Output\OutputInterface;

class WebspaceInitializer implements InitializerInterface
{
    public function __construct(
        private WebspaceManagerInterface $webspaceManager,
        private DocumentManagerInterface $documentManager,
        private DocumentInspector $inspector,
        private PathBuilder $pathBuilder,
        private NodeManager $nodeManager,
    ) {
    }

    public function initialize(OutputInterface $output, $purge = false)
    {
        $webspaces = $this->webspaceManager->getWebspaceCollection();
        foreach ($webspaces as $webspace) {
            $this->initializeWebspace($output, $webspace);
        }

        $this->documentManager->flush();
    }

    private function initializeWebspace(OutputInterface $output, Webspace $webspace)
    {
        $homePath = $this->pathBuilder->build(['%base%', $webspace->getKey(), '%content%']);
        $routesPath = $this->pathBuilder->build(['%base%', $webspace->getKey(), '%route%']);

        $webspaceLocales = [];
        foreach ($webspace->getAllLocalizations() as $localization) {
            $webspaceLocales[] = $localization->getLocale();
        }

        $homeType = $webspace->getDefaultTemplate('home');
        $existingLocales = [];
        $homeDocument = null;
        if ($this->nodeManager->has($homePath)) {
            $homeDocument = $this->documentManager->find($homePath, null, [
                'load_ghost_content' => false,
                'auto_create' => true,
                'path' => $homePath,
            ]);
            $existingLocales = $this->inspector->getLocales($homeDocument);
        }

        foreach ($webspaceLocales as $webspaceLocale) {
            if (\in_array($webspaceLocale, $existingLocales)) {
                $output->writeln(\sprintf('  [ ] <info>homepage</info>: %s (%s)', $homePath, $webspaceLocale));

                continue;
            }

            $output->writeln(\sprintf('  [+] <info>homepage</info>: [%s] %s (%s)', $homeType, $homePath, $webspaceLocale));

            $persistOptions = ['ignore_required' => true];
            $publishOptions = ['ignore_required' => true];
            if (!$homeDocument) {
                /** @var HomeDocument $homeDocument */
                $homeDocument = $this->documentManager->create('home');
                $persistOptions['path'] = $homePath;
                $persistOptions['auto_create'] = true;
            } else {
                $homeDocument = $this->documentManager->find($homePath, $webspaceLocale, [
                    'load_ghost_content' => false,
                ]);
            }

            $homeDocument->setTitle('Homepage');
            $homeDocument->setStructureType($homeType);

            $this->documentManager->persist($homeDocument, $webspaceLocale, $persistOptions);
            $this->documentManager->publish($homeDocument, $webspaceLocale, $publishOptions);
        }

        foreach ($webspaceLocales as $webspaceLocale) {
            $routePath = $routesPath . '/' . $webspaceLocale;

            try {
                $routeDocument = $this->documentManager->find($routePath);

                if ($routeDocument->getTargetDocument()
                    && $routeDocument->getTargetDocument()->getUuid() === $homeDocument->getUuid()
                ) {
                    $output->writeln(\sprintf('  [ ] <info>route</info>: %s (%s)', $routePath, $webspaceLocale));

                    continue;
                }
            } catch (DocumentNotFoundException $e) {
                $routeDocument = $this->documentManager->create('route');
            }

            $output->writeln(\sprintf('  [+] <info>route</info>: %s (%s)', $routePath, $webspaceLocale));

            $routeDocument->setTargetDocument($homeDocument);
            $this->documentManager->persist($routeDocument, $webspaceLocale, [
                'path' => $routePath,
                'auto_create' => true,
            ]);

            $this->documentManager->publish($routeDocument, $webspaceLocale);
        }

        $this->documentManager->flush();
    }
}
