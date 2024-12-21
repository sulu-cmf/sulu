<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for recovering categories.
 * This command is fixing wrong left/right and depths (see -d) assignments of the categories tree.
 */
#[AsCommand(name: 'sulu:categories:recover')]
class RecoverCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryRepositoryInterface $categoryRepository,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force recovery of tree. Without it, an analysis of the tree is performed'
            )
            ->addOption(
                'fix-depth',
                'd',
                InputOption::VALUE_NONE,
                'Analyse depths of categories and fixes depth gaps as well as depths > 0 without a parent'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;
        $force = $input->getOption('force');
        $fixDepth = $input->getOption('fix-depth');

        $repo = $this->categoryRepository;
        $verify = $repo->verify();

        $success = false;

        // fix nested tree
        if (true !== $verify) {
            $output->writeln(\sprintf('<comment>%s errors were found.</comment>', \count($verify)));

            if ($force) {
                $repo->recover();
                $em->flush();
                $success = true;
            }
        } else {
            $output->writeln('<info>Your categories are fine. No errors found.<info>');
        }

        // fix depths if -depth is defined
        if ($fixDepth) {
            // fix categories without parents
            $numberParentLess = $this->findCategoriesWithoutParents();
            if ($numberParentLess > 0) {
                $output->writeln(
                    \sprintf('<comment>%s categories without parent were found.</comment>', $numberParentLess)
                );
                if ($force) {
                    $this->fixCategoriesWithoutParents();
                    $success = true;
                    $em->flush();
                }
            } else {
                $output->writeln('<info>No wrong depth gaps detected<info>');
            }

            // fix depth gaps
            $numberWrongDepth = $this->findInitialWrongDepthGap();
            if ($numberWrongDepth > 0) {
                $output->writeln(\sprintf('<comment>%s wrong depths were found.</comment>', $numberWrongDepth));
                if ($force) {
                    // update depths
                    $affected = 1;
                    while ($affected > 0) {
                        $affected = $this->fixWrongDepthGap();
                    }
                    $success = true;
                    $em->flush();
                }
            } else {
                $output->writeln('<info>No categories without parents detected<info>');
            }
        }

        if (!$force) {
            $output->writeln(\sprintf('Call this command with <info>--force</info> option to perform recovery.'));
        }

        if (true === $success) {
            $output->writeln('<info>Recovery complete<info>');
        }

        return 0;
    }

    /**
     * Find number of categories where difference to parents depth > 1.
     *
     * @return int Number of affected rows
     */
    private function findInitialWrongDepthGap()
    {
        // get categories where difference to parents depth > 1
        $qb = $this->categoryRepository->createQueryBuilder('c2')
            ->select('count(c2.id) as results')
            ->join('c2.parent', 'c1')
            ->where('(c2.depth - 1) <> c1.depth');
        $depthGapResult = $qb->getQuery()->getSingleScalarResult();

        return $depthGapResult;
    }

    /**
     * Find number of categories that have no parent but depth > 0.
     *
     * @return int Number of categories without a parent
     */
    private function findCategoriesWithoutParents()
    {
        // get categories that have no parent but depth > 0
        $qb = $this->categoryRepository->createQueryBuilder('c2')
            ->select('count(c2.id)')
            ->leftJoin('c2.parent', 'c1')
            ->where('c2.depth <> 0 AND c2.parent IS NULL');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Fix categories where difference to parents depth.
     *
     * @return int|bool Number of affected rows
     */
    private function fixWrongDepthGap()
    {
        // FIXME: convert this native query to DQL (once its possible to join within UPDATE statement)
        // fix categories where difference to parents depth > 1
        $sql = 'UPDATE ca_categories c2
                JOIN ca_categories c1 ON c2.idCategoriesParent = c1.id
                SET c2.depth = (c1.depth + 1)
                WHERE ( c2.depth - 1 ) <> c1.depth';

        $statement = $this->entityManager->getConnection()->prepare($sql);
        $result = $statement->execute();

        if ($result) {
            // DBAL 2 to 3 BC Layer
            return \method_exists($statement, 'rowCount') ? $statement->rowCount() : $result->rowCount();
        }

        return false;
    }

    /**
     * Set every category where depth > 0 and has no parents to depth 0.
     */
    private function fixCategoriesWithoutParents()
    {
        // fix categories that have no parent but depth > 0
        $qb = $this->categoryRepository->createQueryBuilder('c2')
            ->update()
            ->set('c2.depth', 0)
            ->where('c2.parent IS NULL AND c2.depth != 0');

        $qb->getQuery()->execute();
    }
}
