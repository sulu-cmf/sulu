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
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'sulu:security:role:create', description: 'Create a role.')]
class CreateRoleCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleRepositoryInterface $roleRepository,
        private AdminPool $adminPool
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDefinition(
                [
                    new InputArgument('name', InputArgument::REQUIRED, 'Name of role'),
                    new InputArgument('system', InputArgument::REQUIRED, 'System where role should be valid'),
                ]
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $system = $input->getArgument('system');

        $role = $this->roleRepository->findOneByName($name);

        if ($role) {
            $output->writeln(\sprintf(
                '<error>Role "%s" already exists.</error>',
                $name
            ));

            return 1;
        }

        /** @var RoleInterface $role */
        $role = $this->roleRepository->createNew();
        $role->setName($name);
        $role->setSystem($system);

        $securityContexts = $this->adminPool->getSecurityContexts();

        // flatten contexts
        $securityContextsFlat = [];

        foreach ($securityContexts['Sulu'] as $section => $contexts) {
            foreach ($contexts as $context => $permissionTypes) {
                if (\is_array($permissionTypes)) {
                    $securityContextsFlat[] = $context;
                } else {
                    // FIXME here for BC reasons, because the array used to only contain values without permission types
                    $securityContextsFlat[] = $permissionTypes;
                }
            }
        }

        foreach ($securityContextsFlat as $securityContext) {
            $permission = new Permission();
            $permission->setRole($role);
            $permission->setContext($securityContext);
            $permission->setPermissions(127);
            $role->addPermission($permission);
        }

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        $output->writeln(
            \sprintf(
                'Created role "<comment>%s</comment>" in system "<comment>%s</comment>".',
                $role->getName(),
                $role->getSystem()
            )
        );

        return 0;
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $contexts = $this->adminPool->getSecurityContexts();
        $systems = \array_keys($contexts);

        if (!$input->getArgument('name')) {
            $question = new Question('Please choose a rolename: ');
            $question->setValidator(
                function($name) {
                    if (empty($name)) {
                        throw new \InvalidArgumentException('Rolename cannot be empty');
                    }

                    $roles = $this->roleRepository->findBy(['name' => $name]);
                    if (\count($roles) > 0) {
                        throw new \InvalidArgumentException(\sprintf('Rolename "%s" is not unique', $name));
                    }

                    return $name;
                }
            );

            $name = $helper->ask($input, $output, $question);
            $input->setArgument('name', $name);
        }

        if (!$input->getArgument('system')) {
            $question = new ChoiceQuestion(
                'Please choose a system: ',
                $systems,
                0
            );

            $system = $helper->ask($input, $output, $question);
            $input->setArgument('system', $system);
        }
    }
}
