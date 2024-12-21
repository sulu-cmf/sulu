<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

#[AsCommand(name: 'sulu:route:update', description: 'Update the routes for all entities.')]
class UpdateRouteCommand extends Command
{
    public function __construct(
        private LocaleAwareInterface $translator,
        private EntityManagerInterface $entityManager,
        private RouteManagerInterface $routeManager
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('entity', InputArgument::REQUIRED)
            ->addArgument('locale', InputArgument::REQUIRED)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, '', '1000')
            ->setHelp(
                <<<'EOT'
Update the routes for all entities which will be returned by the repository of given entity service:

    $ %command.full_name% sulu.repository.example
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->translator->setLocale($input->getArgument('locale'));

        $batchSize = (int) $input->getOption('batch-size');

        /** @var EntityRepository $repository */
        $repository = $this->entityManager->getRepository($input->getArgument('entity'));

        $query = $repository->createQueryBuilder('entity')->select('count(entity.id)')->getQuery();
        $result = $query->getResult();
        $count = (int) $result[0][1];

        $query = $repository->createQueryBuilder('entity')->getQuery();
        $output->writeln(
            \sprintf(
                '<comment>updating route for "%s" instances of "%s"</comment>',
                $count,
                $input->getArgument('entity')
            )
        );
        $progressBar = new ProgressBar($output, $count);
        $progressBar->setFormat('debug');
        $progressBar->display();

        $index = 0;
        foreach ($query->iterate() as $item) {
            $entity = $item[0];

            if (null !== $entity->getRoute()) {
                $this->routeManager->update($entity);
            } else {
                $this->entityManager->persist($this->routeManager->create($entity));
            }

            $progressBar->advance();
            $entity = null;

            if (0 === ($index++ % $batchSize)) {
                $this->entityManager->flush();

                // trigger garbage collect
                $this->entityManager->clear();
            }
        }

        // flush the rest of the entities
        $this->entityManager->flush();

        //$progressBar->finish();
        $output->writeln('');

        return 0;
    }
}
