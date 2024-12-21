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

namespace Sulu\Bundle\DocumentManagerBundle\Command;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\HttpCacheBundle\EventSubscriber\InvalidationSubscriber;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Subscriber\PHPCR\CleanupNode;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
#[AsCommand(name: 'sulu:document:phpcr-cleanup-single-node', description: 'Cleanup a single PHPCR node and remove unused properties.')]
class PHPCRCleanupSingleNodeCommand extends Command
{
    public const IGNORED = 101;

    /**
     * @var string[]
     */
    public const WHITELIST = [
        'state',
        'published',
        'created',
        'creator',
        'changed',
        'changer',
    ];

    private string $languagePrefix;

    /**
     * @var array<string, string>
     */
    private array $aliasMapping = [];

    /**
     * @var array<string, OptionsResolver>
     */
    private array $optionsResolvers = [];

    private OutputInterface $logger;

    /**
     * @param array<array{phpcr_type: string, alias: string}> $mapping
     */
    public function __construct(
        private SessionInterface $liveSession,
        private SessionInterface $session,
        private StructureMetadataFactoryInterface $structureMetaDataFactory,
        NamespaceRegistry $namespaceRegistry,
        private EventDispatcherInterface $documentManagerEventDispatcher,
        private DocumentManagerInterface $documentManager,
        array $mapping,
    ) {
        parent::__construct();

        $this->logger = new NullOutput();

        $this->languagePrefix = $namespaceRegistry->getPrefix('system_localized');
        foreach ($mapping as $item) {
            $this->aliasMapping[$item['phpcr_type']] = $item['alias'];
        }
    }

    protected function configure(): void
    {
        $this->addArgument('node', InputArgument::REQUIRED, 'Node identifier to cleanup.');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not make any changes to the repository.');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Write debug information to a file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $debug = $input->getOption('debug');
        if ($debug) {
            $this->logger = $output;
        }

        $io = new SymfonyStyle($input, $output);

        $dryRun = $input->getOption('dry-run');
        $uuid = $input->getArgument('node');

        $stats = [
            'documents' => 0,
            'properties' => 0,
            'removedProperties' => 0,
        ];

        $node = $this->session->getNodeByIdentifier($uuid);
        $alias = $this->getAliasForNode($node);
        if (null === $alias || !$this->structureMetaDataFactory->hasStructuresFor($alias)) {
            return self::IGNORED;
        }

        $locales = $this->getLocales($node);
        if (0 === \count($locales)) {
            return self::IGNORED;
        }

        foreach ($locales as $locale) {
            ++$stats['documents'];

            $document = $this->documentManager->find($uuid, $locale);

            $workflowStage = WorkflowStage::TEST;
            if ($document instanceof WorkflowStageBehavior) {
                $workflowStage = $document->getWorkflowStage();
            }

            try {
                Assert::isInstanceOf($document, UuidBehavior::class);

                $node = $this->session->getNodeByIdentifier($document->getUuid());
                $defaultCleanupNode = new CleanupNode(clone $node);
                $this->persist($document, $defaultCleanupNode, $locale);

                $wasPublished = WorkflowStage::PUBLISHED === $workflowStage;
                if ($wasPublished) {
                    $liveNode = $this->liveSession->getNodeByIdentifier($document->getUuid());
                    $liveCleanupNode = new CleanupNode(clone $liveNode);
                    $this->publish($document, $liveCleanupNode, $locale);
                }

                $this->documentManager->clear();
            } catch (\Exception $e) {
                return self::INVALID;
            }

            $writtenProperties = $defaultCleanupNode->getWrittenPropertyKeys();
            foreach ($this->cleanupNode($node, $locale, $writtenProperties, $dryRun) as $result) {
                ++$stats['properties'];
                $stats['removedProperties'] += $result ? 1 : 0;
            }

            if (!$dryRun) {
                $this->session->save();
            }

            if ($wasPublished) {
                $liveNode = $this->liveSession->getNode($node->getPath());
                $writtenProperties = $liveCleanupNode->getWrittenPropertyKeys();
                foreach ($this->cleanupNode($liveNode, $locale, $writtenProperties, $dryRun) as $result) {
                    ++$stats['properties'];
                    $stats['removedProperties'] += $result ? 1 : 0;
                }

                if (!$dryRun) {
                    $this->liveSession->save();
                }
            }
        }

        $io->success(\sprintf(
            'Cleaned up node "%s"',
            $node->getPath(),
        ));

        $io->listing([
            'Documents: ' . $stats['documents'],
            'Removed properties: ' . $stats['removedProperties'],
            'Total properties: ' . $stats['properties'],
        ]);

        return self::SUCCESS;
    }

    private function persist(object $document, CleanupNode $cleanupNode, string $locale): void
    {
        $options = $this->getOptionsResolver(Events::PERSIST)->resolve();
        $event = new Event\PersistEvent($document, $locale, $options);
        $event->setNode($cleanupNode);
        $this->documentManagerEventDispatcher->dispatch($event, Events::PERSIST);
    }

    private function publish(object $document, CleanupNode $cleanupNode, string $locale): void
    {
        $options = $this->getOptionsResolver(Events::PUBLISH)->resolve();
        $options[InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION] = false;
        $event = new Event\PublishEvent($document, $locale, $options);
        $event->setNode($cleanupNode);
        $this->documentManagerEventDispatcher->dispatch($event, Events::PUBLISH);
    }

    private function cleanupNode(NodeInterface $node, string $locale, array $writtenProperties, bool $dryRun): \Generator
    {
        $this->logger->writeln(\sprintf("# Cleaning up node \"%s\" for locale \"%s\" in workspace \"%s\"\n", $node->getPath(), $locale, $node->getSession()->getWorkspace()->getName()));

        $whiteList = \array_map(fn ($property) => $this->languagePrefix . ':' . $locale . '-' . $property, self::WHITELIST);

        $removedProperties = [];
        foreach ($node->getProperties() as $property) {
            if (!\str_starts_with($property->getName(), $this->languagePrefix . ':' . $locale . '-')) {
                yield false;

                continue;
            }

            if (\in_array($property->getName(), $writtenProperties, true)
                || \in_array($property->getName(), $whiteList, true)
            ) {
                yield false;

                continue;
            }

            $removedProperties[] = $property->getName();
            if (!$dryRun) {
                $property->remove();
            }

            yield true;
        }

        $this->logger->writeln(\sprintf("Removed:\n* %s\n", \implode("\n* ", $removedProperties)));
    }

    private function getOptionsResolver(string $eventName): OptionsResolver
    {
        if (isset($this->optionsResolvers[$eventName])) {
            return $this->optionsResolvers[$eventName];
        }

        $resolver = new OptionsResolver();
        $resolver->setDefault('locale', null);

        $event = new Event\ConfigureOptionsEvent($resolver);
        $this->documentManagerEventDispatcher->dispatch($event, Events::CONFIGURE_OPTIONS);

        $this->optionsResolvers[$eventName] = $resolver;

        return $resolver;
    }

    private function getLocales(NodeInterface $node): array
    {
        $locales = [];

        foreach ($node->getProperties() as $property) {
            \preg_match(
                \sprintf('/^%s:([a-zA-Z_]*?)-(template|title)/', $this->languagePrefix),
                $property->getName(),
                $matches,
            );

            if ($matches) {
                $locales[$matches[1]] = $matches[1];
            }
        }

        return \array_keys($locales);
    }

    private function getAliasForNode(NodeInterface $node): ?string
    {
        foreach ($node->getMixinNodeTypes() as $mixinNodeType) {
            if (isset($this->aliasMapping[$mixinNodeType->getName()])) {
                return $this->aliasMapping[$mixinNodeType->getName()];
            }
        }

        return null;
    }
}
