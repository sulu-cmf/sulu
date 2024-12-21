<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\UserInterface\Command;

use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal your code should not depend on anything in this class but the `sulu:reference:refresh` command can be used by your project to refresh references
 *
 * @final
 */
#[AsCommand(name: 'sulu:reference:refresh')]
class RefreshCommand extends Command
{
    /**
     * @param iterable<ReferenceRefresherInterface> $referenceRefreshers
     */
    public function __construct(
        private iterable $referenceRefreshers,
        private ReferenceRepositoryInterface $referenceRepository,
        private string $suluContext,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Refresh references of all resources');
        $this->addArgument('resource-key', InputArgument::OPTIONAL, 'The resource key which should be refreshed');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $resourceKeyFilter = $input->getArgument('resource-key');

        $ui = new SymfonyStyle($input, $output);

        $referenceRefresherPerResourceKey = [];

        foreach ($this->referenceRefreshers as $referenceRefresher) {
            $resourceKey = $referenceRefresher::getResourceKey();
            if (null !== $resourceKeyFilter && $resourceKeyFilter !== $resourceKey) {
                continue;
            }

            $referenceRefresherPerResourceKey[$resourceKey][] = $referenceRefresher;
        }

        foreach ($referenceRefresherPerResourceKey as $resourceKey => $referenceRefreshers) {
            $ui->section('Refresh ' . $resourceKey);
            $ui->progressStart();
            $now = new \DateTimeImmutable();

            $counter = 0;
            foreach ($referenceRefreshers as $referenceRefresher) {
                foreach ($referenceRefresher->refresh() as $object) {
                    if (0 === $counter % 100) {
                        $this->referenceRepository->flush();
                    }

                    $ui->progressAdvance();
                }
            }

            $this->referenceRepository->flush();
            $this->referenceRepository->removeBy([
                'referenceResourceKey' => $resourceKey,
                'referenceContext' => $this->suluContext,
                'changedOlderThan' => $now,
            ]);
            $ui->progressFinish();
        }

        return Command::SUCCESS;
    }
}
