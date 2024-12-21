<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authorization\AccessControl\DoctrineAccessControlProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sulu:security:sync-phpcr-permissions', description: 'Sync existing object permissions from phpcr into database to make them usable in database queries')]
class SyncPhpcrPermissionsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentManagerInterface $documentManager,
        private DoctrineAccessControlProvider $doctrineAccessControlProvider
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setHelp(
            'The <info>%command.name%</info> command syncs the object permissions of phpcr documents into the database.'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $roleIds = $this->getRoleIds();

        if (0 === \count($roleIds)) {
            $io->success('Nothing to do');

            return 0;
        }

        $documents = $this->findDocuments($roleIds);
        $amountDocuments = $documents->count();

        if (0 === $amountDocuments) {
            $io->success('Nothing to do');

            return 0;
        }

        $io->progressStart($amountDocuments);

        $count = 0;
        foreach ($documents as $document) {
            $io->progressAdvance();

            if (!$document instanceof SecurityBehavior || !$document instanceof UuidBehavior) {
                continue;
            }

            $this->doctrineAccessControlProvider->setPermissions(
                SecurityBehavior::class,
                $document->getUuid(),
                $document->getPermissions()
            );

            ++$count;
        }

        $io->progressFinish();

        $io->success(\sprintf('Successfully synced %d documents', $count));

        return 0;
    }

    /**
     * @return int[]
     */
    private function getRoleIds(): array
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('role.id')
            ->from(RoleInterface::class, 'role')
            ->getQuery()
            ->getArrayResult();

        return \array_column($result, 'id');
    }

    /**
     * Find documents with permissions for at least one of the role ids.
     *
     * @param int[] $roleIds
     *
     * @return QueryResultCollection<object>
     */
    protected function findDocuments(array $roleIds): QueryResultCollection
    {
        $roleIdsConstraint = \implode(' OR ', \array_map(function($roleId) {
            return \sprintf('[%s%s] IS NOT NULL', SecuritySubscriber::SECURITY_PROPERTY_PREFIX, $roleId);
        }, $roleIds));

        $sql2 = \sprintf(
            'SELECT * FROM [nt:unstructured] AS a WHERE (%s)',
            $roleIdsConstraint
        );

        return $this->documentManager->createQuery($sql2)->execute();
    }
}
