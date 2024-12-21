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

use Sulu\Bundle\PreviewBundle\Preview\Events;
use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\StructureProvider\WebspaceStructureProvider;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

#[AsCommand(name: 'sulu:content:validate:webspaces', description: 'Dumps webspaces and will show an error when template could not be loaded')]
class ValidateWebspacesCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array<string>
     */
    private $errors = [];

    public function __construct(
        private Environment $twig,
        private StructureMetadataFactoryInterface $structureMetadataFactory,
        private ?ControllerNameParser $controllerNameConverter,
        private StructureManagerInterface $structureManager,
        private WebspaceStructureProvider $structureProvider,
        private WebspaceManagerInterface $webspaceManager,
        private EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        /** @var Webspace[] $webspaces */
        $webspaces = $this->webspaceManager->getWebspaceCollection();

        $messages = '';

        foreach ($webspaces as $webspace) {
            $this->eventDispatcher->dispatch(new PreRenderEvent(new RequestAttributes([
                'webspace' => $webspace,
            ])), Events::PRE_RENDER);

            $this->outputWebspace($webspace);
        }

        $output->writeln($messages);

        if (\count($this->errors)) {
            $this->output->writeln(\sprintf('<error>%s Errors found.</error>', \count($this->errors)));

            return 1;
        }

        return 0;
    }

    /**
     * Output webspace.
     */
    private function outputWebspace(Webspace $webspace)
    {
        $this->output->writeln(
            \sprintf(
                '<info>%s</info> - <info>%s</info>',
                $webspace->getKey(),
                $webspace->getName()
            )
        );

        $this->outputWebspaceDefaultTemplates($webspace);
        $this->outputWebspacePageTemplates($webspace);
        $this->outputWebspaceTemplates($webspace);
        $this->outputWebspaceLocalizations($webspace);
    }

    /**
     * Output webspace default templates.
     */
    private function outputWebspaceDefaultTemplates(Webspace $webspace)
    {
        $this->output->writeln('Default Templates:');

        foreach ($webspace->getDefaultTemplates() as $type => $template) {
            $this->validatePageTemplate($type, $template);
        }
    }

    /**
     * Output webspace page templates.
     */
    private function outputWebspacePageTemplates(Webspace $webspace)
    {
        $this->output->writeln('Page Templates:');

        $structures = $this->structureManager->getStructures();

        $checkedTemplates = [];

        foreach ($webspace->getDefaultTemplates() as $template) {
            $checkedTemplates[] = $template;
        }

        foreach ($structures as $structure) {
            $template = $structure->getKey();

            if (!$structure->getInternal() && !\in_array($template, $checkedTemplates)) {
                $this->validatePageTemplate('page', $structure->getKey());
            }
        }
    }

    /**
     * Output webspace default templates.
     */
    private function outputWebspaceTemplates(Webspace $webspace)
    {
        $this->output->writeln('Templates:');

        foreach ($webspace->getTemplates() as $type => $template) {
            $this->validateTemplate($type, $template . '.html.twig');
        }
    }

    /**
     * Output webspace localizations.
     */
    private function outputWebspaceLocalizations(Webspace $webspace)
    {
        $this->output->writeln('Localizations:');

        foreach ($webspace->getAllLocalizations() as $localization) {
            $this->output->writeln(
                \sprintf(
                    '    %s',
                    $localization->getLocale()
                )
            );
        }
    }

    /**
     * Validate page templates.
     *
     * @param string $type
     * @param string $template
     */
    private function validatePageTemplate($type, $template)
    {
        $status = '<info>ok</info>';

        try {
            $this->validateStructure($type, $template);
        } catch (\Exception $e) {
            $status = \sprintf('<error>failed: %s</error>', $e->getMessage());
            $this->errors[] = $e->getMessage();
        }

        $this->output->writeln(
            \sprintf(
                '    %s: %s -> %s',
                $type,
                $template,
                $status
            )
        );
    }

    /**
     * Is template valid.
     *
     * @param string $type
     * @param string $template
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function validateStructure($type, $template)
    {
        $valid = true;

        $metadata = $this->structureMetadataFactory->getStructureMetadata($type, $template);

        if (!$metadata) {
            throw new \RuntimeException(
                \sprintf(
                    'Structure meta data not found for type "%s" and template "%s".',
                    $type,
                    $template
                )
            );
        }

        foreach (['title', 'url'] as $property) {
            if (!$metadata->hasProperty($property)) {
                throw new \RuntimeException(
                    \sprintf(
                        'No property "%s" found in "%s" template.',
                        $property,
                        $metadata->getName()
                    )
                );
            }
        }

        $this->validateTwigTemplate($metadata->getView() . '.html.twig');
        $this->validateControllerAction($metadata->getController());

        return $valid;
    }

    /**
     * Validate template.
     *
     * @param string $type
     * @param string $template
     */
    private function validateTemplate($type, $template)
    {
        $status = '<info>ok</info>';

        try {
            $this->validateTwigTemplate($template);
        } catch (\Exception $e) {
            $status = \sprintf('<error>failed: %s</error>', $e->getMessage());
            $this->errors[] = $e->getMessage();
        }

        $this->output->writeln(
            \sprintf(
                '    %s: %s -> %s',
                $type,
                $template,
                $status
            )
        );
    }

    /**
     * Validate twig template.
     *
     * @param string $template
     *
     * @throws \Exception
     */
    private function validateTwigTemplate($template)
    {
        $loader = $this->twig->getLoader();
        if (!$loader->exists($template)) {
            throw new \Exception(\sprintf(
                'Unable to find template "%s".',
                $template
            ));
        }
    }

    /**
     * Validate controller action.
     *
     * @param string $controllerAction
     *
     * @throws \Exception
     */
    private function validateControllerAction($controllerAction)
    {
        try {
            if ($this->controllerNameConverter) {
                $controllerAction = $this->controllerNameConverter->parse($controllerAction);
            }
        } catch (\InvalidArgumentException $e) {
        }

        list($class, $method) = \explode('::', $controllerAction);

        if (!\method_exists($class, $method)) {
            $reflector = new \ReflectionClass($class);

            throw new \Exception(\sprintf(
                'Controller Action "%s" not exist in "%s" (looked into: %s).',
                $method,
                $class,
                $reflector->getFileName()
            ));
        }
    }
}
