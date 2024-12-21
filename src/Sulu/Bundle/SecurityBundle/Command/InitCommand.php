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
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand(name: 'sulu:security:init', description: 'Create required sulu security entities.')]
final class InitCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleRepositoryInterface $roleRepository,
        private AdminPool $adminPool
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $systems = \array_keys($this->adminPool->getSecurityContexts());
        $roles = $this->roleRepository->findAllRoles();
        $ui = new SymfonyStyle($input, $output);

        $existingAnonymousRoles = [];
        foreach ($roles as $role) {
            if ($role->getAnonymous()) {
                $existingAnonymousRoles[$role->getSystem()] = $role;
            }
        }

        $addedCount = 0;
        $updatedCount = 0;
        foreach ($systems as $system) {
            if (Admin::SULU_ADMIN_SECURITY_SYSTEM === $system) {
                continue;
            }

            /** @var RoleInterface $role */
            $role = $existingAnonymousRoles[$system] ?? $this->roleRepository->createNew();
            $role->setName('Anonymous User ' . $system);
            $role->setAnonymous(true);
            $role->setSystem($system);

            $securityContexts = $this->adminPool->getSecurityContexts();
            $securityContextsFlat = [];
            foreach ($securityContexts[$system] as $section => $contexts) {
                foreach ($contexts as $context => $permissionTypes) {
                    if (\is_array($permissionTypes)) {
                        $securityContextsFlat[] = $context;
                    } else {
                        // FIXME here for BC reasons, because the array used to only contain values without permission types
                        $securityContextsFlat[] = $permissionTypes;
                    }
                }
            }

            $permissionAdded = false;
            $existingSecurityContexts = [];

            foreach ($role->getPermissions() as $permission) {
                $existingSecurityContexts[] = $permission->getContext();
            }

            foreach ($securityContextsFlat as $securityContext) {
                if (\in_array($securityContext, $existingSecurityContexts)) {
                    continue;
                }

                $permission = new Permission();
                $permission->setRole($role);
                $permission->setContext($securityContext);
                $permission->setPermissions(127);
                $role->addPermission($permission);
                $permissionAdded = true;
            }

            if ($role->getId()) {
                if (!$permissionAdded) {
                    $ui->text(\sprintf(
                        '[ ] Anonymous role named "%s" exists in system "%s" already.',
                        $existingAnonymousRoles[$system]->getName(),
                        $system
                    ));

                    continue;
                }

                $ui->text(\sprintf(
                    '[*] Anonymous role named "%s" in system "%s" was updated.',
                    $existingAnonymousRoles[$system]->getName(),
                    $system
                ));
                ++$updatedCount;
            } else {
                $ui->text(\sprintf('[+] Create anonymous role in system "%s" as "%s".', $system, $role->getName()));
                ++$addedCount;
            }

            $this->entityManager->persist($role);
        }

        $output->writeln('');
        $output->writeln('<comment>*</comment> Legend: [+] Added [*] Updated [-] Purged [ ] No change');

        if ($addedCount) {
            $ui->success(\sprintf('Created "%s" new anonymous roles.', $addedCount));
        }

        if ($updatedCount) {
            $ui->success(\sprintf('Updated "%s" anonymous roles.', $updatedCount));
        }

        $this->entityManager->flush();

        return 0;
    }
}
