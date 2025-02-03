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
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\SaltGenerator;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

#[AsCommand(name: 'sulu:security:user:create', description: 'Create a user.')]
class CreateUserCommand extends Command
{
    /**
     * @param string[] $locales
     * @param PasswordHasherFactoryInterface|EncoderFactoryInterface $passwordHasherFactory
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private RoleRepositoryInterface $roleRepository,
        private ContactRepositoryInterface $contactRepository,
        private LocalizationManagerInterface $localizationManager,
        private SaltGenerator $saltGenerator,
        private $passwordHasherFactory,
        private array $locales
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition(
                [
                    new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                    new InputArgument('firstName', InputArgument::REQUIRED, 'The FirstName'),
                    new InputArgument('lastName', InputArgument::REQUIRED, 'The LastName'),
                    new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                    new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
                    new InputArgument('role', InputArgument::REQUIRED, 'The role'),
                    new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                ]
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $localizations = $this->localizationManager->getLocalizations();
        $locales = [];

        foreach ($localizations as $localization) {
            /* @var Localization $localization */
            $locales[] = $localization->getLocale();
        }

        $username = $input->getArgument('username');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $email = $input->getArgument('email');
        $locale = $input->getArgument('locale');
        $roleName = $input->getArgument('role');
        $password = $input->getArgument('password');

        $user = $this->getUser();

        $existing = $this->userRepository->findOneBy(['username' => $username]);

        if ($existing) {
            $output->writeln(\sprintf('<error>User "%s" already exists</error>',
                $username
            ));

            return 1;
        }

        if (!\in_array($locale, $this->locales)) {
            $output->writeln(\sprintf(
                'Given locale "%s" is invalid, must be one of "%s"',
                $locale, \implode('", "', $this->locales)
            ));

            return 1;
        }

        /** @var ContactInterface $contact */
        $contact = $this->contactRepository->createNew();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        $user->setContact($contact);
        $user->setUsername($username);
        $user->setSalt($this->generateSalt());
        $user->setPassword($this->encodePassword($user, $password, $user->getSalt()));
        $user->setLocale($locale);
        $user->setEmail($email);

        /** @var RoleInterface $role */
        $role = $this->roleRepository->findOneBy(['name' => $roleName]);

        if (!$role) {
            $output->writeln(\sprintf('<error>Role "%s" not found. The following roles are available: "%s"</error>',
                $roleName,
                \implode('", "', $this->getRoleNames())
            ));

            return 1;
        }

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale(\json_encode($locales)); // set all locales
        $this->entityManager->persist($userRole);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln(
            \sprintf('Created user "<comment>%s</comment>" in role "<comment>%s</comment>"', $username, $roleName)
        );

        return 0;
    }

    /**
     * Returns a new instance of the user.
     * Can be overwritten to use a different implementation.
     *
     * @return UserInterface
     */
    protected function getUser()
    {
        return $this->userRepository->createNew();
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $roleNames = $this->getRoleNames();
        $helper = $this->getHelper('question');

        $userRepository = $this->userRepository;

        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username: ');
            $question->setValidator(
                function($username) use ($userRepository) {
                    if (empty($username)) {
                        throw new \InvalidArgumentException('Username can not be empty');
                    }

                    $users = $userRepository->findBy(['username' => $username]);
                    if (\count($users) > 0) {
                        throw new \InvalidArgumentException(\sprintf('Username "%s" is not unique', $username));
                    }

                    return $username;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('username', $value);
        }

        if (!$input->getArgument('firstName')) {
            $question = new Question('Please choose a FirstName: ');
            $question->setValidator(
                function($firstName) {
                    if (empty($firstName)) {
                        throw new \InvalidArgumentException('FirstName can not be empty');
                    }

                    return $firstName;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('firstName', $value);
        }

        if (!$input->getArgument('lastName')) {
            $question = new Question('Please choose a LastName: ');
            $question->setValidator(
                function($lastName) {
                    if (empty($lastName)) {
                        throw new \InvalidArgumentException('LastName can not be empty');
                    }

                    return $lastName;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('lastName', $value);
        }

        if (!$input->getArgument('email')) {
            $question = new Question('Please choose a Email: ');
            $question->setValidator(
                function($email) use ($userRepository) {
                    if (empty($email)) {
                        $email = null;
                    }
                    if (null !== $email) {
                        $users = $userRepository->findBy(['email' => $email]);
                        if (\count($users) > 0) {
                            throw new \InvalidArgumentException(\sprintf('Email "%s" is not unique', $email));
                        }
                    }

                    return $email;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('email', $value);
        }

        if (!$input->getArgument('locale')) {
            $question = new ChoiceQuestion('Please choose a locale', $this->locales);
            $value = $helper->ask($input, $output, $question);
            $input->setArgument('locale', $value);
        }

        if (!$input->getArgument('role')) {
            $question = new ChoiceQuestion(
                'Please choose a role: ',
                $roleNames,
                0
            );
            $value = $helper->ask($input, $output, $question);
            $input->setArgument('role', $value);
        }

        if (!$input->getArgument('password')) {
            $question = new Question('Please choose a Password: ');
            $question->setHidden(true);
            $question->setValidator(
                function($password) {
                    if (empty($password)) {
                        throw new \InvalidArgumentException('Password can not be empty');
                    }

                    return $password;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('password', $value);
        }
    }

    /**
     * Generates a random salt for the password.
     *
     * @return string
     */
    private function generateSalt()
    {
        return $this->saltGenerator->getRandomSalt();
    }

    /**
     * Encodes the given password, for the given password, with he given salt and returns the result.
     */
    private function encodePassword($user, $password, $salt)
    {
        if ($this->passwordHasherFactory instanceof PasswordHasherFactoryInterface) {
            $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
            $password = $hasher->hash($password);
        } else {
            $encoder = $this->passwordHasherFactory->getEncoder($user);
            $password = $encoder->encodePassword($password, $salt);
        }

        return $password;
    }

    /**
     * Return the names of all the roles.
     *
     * @return array<string>
     *
     * @throws \RuntimeException If no roles exist
     */
    private function getRoleNames(): array
    {
        $roles = $this->roleRepository->findAllRoles(['anonymous' => false]);
        $roleNames = [];

        foreach ($roles as $role) {
            $roleNames[] = $role->getName();
        }

        if (empty($roleNames)) {
            throw new \RuntimeException(
                'The system currently has no roles. Use the "sulu:security:role:create" command to create roles.'
            );
        }

        return $roleNames;
    }
}
