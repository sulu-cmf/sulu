<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Command;

use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentExecutor;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureGroupInterface;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

#[AsCommand(name: 'sulu:document:fixtures:load', description: 'Loads data fixtures services tagged with "sulu.document_manager_fixture".')]
class FixturesLoadCommand extends Command
{
    /**
     * @var \Traversable<DocumentFixtureInterface>
     */
    private $fixtures;

    public function __construct(
        private DocumentExecutor $executor,
        ?\Traversable $fixtures = null
    ) {
        parent::__construct();

        $this->fixtures = $fixtures ?: new \ArrayObject([]);
    }

    protected function configure()
    {
        $this
            ->addOption('group', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The group which should be loaded.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures to the existing data - will not purge the workspace.')
            ->addOption('no-initialize', null, InputOption::VALUE_NONE, 'Do not run the repository initializers after purging the repository.')
            ->setHelp(<<<'EOT'
The <info>sulu:document:fixtures:load</info> command loads data fixtures services
tagged with "sulu.document_manager_fixture":

  <info>%command.full_name%</info>

You can also optionally specify the group of fixtures to load
<info>--group</info> option:

  <info>%command.full_name% --group=GROUP1 --group=MyFixture</info>

If you want to append the fixtures instead of flushing the database first you
can use the <info>--append</info> option:

  <info>%command.full_name% --append</info>

This command will also execute any registered Initializer classes after
purging.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $noInitialize = $input->getOption('no-initialize');
        $append = $input->getOption('append');

        if ($input->isInteractive() && !$append) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion(
                '<question>Careful, database will be purged. Do you want to continue y/n ?</question>',
                false
            );

            $confirmed = $helper->ask($input, $output, $question);

            if (!$confirmed) {
                return 0;
            }
        }

        $groups = $input->getOption('group');

        if (empty($groups)) {
            $fixtures = \iterator_to_array($this->fixtures);
        } else {
            $fixtures = $this->getFixturesByGroups($groups);
        }

        if (empty($fixtures)) {
            $output->writeln('<info>Could not find any fixtures.</info>');

            if ($input->getOption('verbose')) {
                $output->writeln(\sprintf(
                    'Found fixtures: <comment>"</comment>%s<comment>"</comment>',
                    \implode('<comment>", "</comment>', \array_map(function($fixture) {
                        return \get_class($fixture);
                    }, \iterator_to_array($this->fixtures)))
                ));
            }

            return 0;
        }

        // check for deprecated document fixtures using the container directly
        foreach ($fixtures as $fixture) {
            if ($fixture instanceof ContainerAwareInterface) {
                @trigger_deprecation(
                    'sulu/sulu',
                    '2.1',
                    \sprintf(
                        'Document fixtures with the "%s" are deprecated,' . \PHP_EOL .
                        'use dependency injection for the "%s" service instead.',
                        ContainerAwareInterface::class,
                        \get_class($fixture)
                    )
                );
            }
        }

        $this->executor->execute($fixtures, false === $append, false === $noInitialize, $output);

        $output->writeln('');
        $output->writeln(\sprintf(
            '<info>Done. Executed </info>%s</info><info> fixtures.</info>',
            \count($fixtures)
        ));

        return 0;
    }

    /**
     * @param string[] $groups
     *
     * @return DocumentFixtureInterface[]
     */
    private function getFixturesByGroups(array $groups): array
    {
        $fixtures = [];

        foreach ($this->fixtures as $fixture) {
            // similar to the doctrine fixture bundle the class name is used also as a group
            $fixtureGroups = [$this->getClassGroup($fixture)];
            if ($fixture instanceof DocumentFixtureGroupInterface) {
                $fixtureGroups = \array_merge($fixtureGroups, $fixture->getGroups());
            }

            // add to fixtures when one of the provided groups match
            if (\count(\array_intersect($groups, $fixtureGroups))) {
                $fixtures[] = $fixture;

                continue;
            }
        }

        return $fixtures;
    }

    private function getClassGroup(DocumentFixtureInterface $fixture): string
    {
        $path = \explode('\\', \get_class($fixture));

        return \array_pop($path);
    }
}
