<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\SecurityBundle\Controller\ResettingController;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Exception\UserNotInSystemException;
use Sulu\Bundle\SecurityBundle\Security\Exception\TokenEmailsLimitReachedException;
use Sulu\Bundle\SecurityBundle\Util\TokenGeneratorInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

class ResettingControllerTest extends TestCase
{
    use ProphecyTrait;

    /** @var \Prophecy\Prophecy\ObjectProphecy|ResettingController */
    private $resettingController;
    /** @var \Prophecy\Prophecy\ObjectProphecy|ValidatorInterface */
    private $validator;
    /** @var \Prophecy\Prophecy\ObjectProphecy|TranslatorInterface */
    private $translator;
    /** @var \Prophecy\Prophecy\ObjectProphecy|TokenGeneratorInterface */
    private $tokenGenerator;
    /** @var \Prophecy\Prophecy\ObjectProphecy|TwigEnvironment */
    private $twig;
    /** @var \Prophecy\Prophecy\ObjectProphecy|TokenStorageInterface */
    private $tokenStorage;
    /** @var \Prophecy\Prophecy\ObjectProphecy|EventDispatcherInterface */
    private $dispatcher;
    /** @var \Prophecy\Prophecy\ObjectProphecy|MailerInterface */
    private $mailer;
    /** @var \Prophecy\Prophecy\ObjectProphecy|PasswordHasherFactoryInterface */
    private $passwordHasherFactory;
    /** @var \Prophecy\Prophecy\ObjectProphecy|UserRepository */
    private $userRepository;
    /** @var \Prophecy\Prophecy\ObjectProphecy|UrlGeneratorInterface */
    private $router;
    /** @var \Prophecy\Prophecy\ObjectProphecy|EntityManagerInterface */
    private $entityManager;
    /** @var \Prophecy\Prophecy\ObjectProphecy|DomainEventCollectorInterface */
    private $domainEventCollector;
    /** @var \Prophecy\Prophecy\ObjectProphecy|null|LoggerInterface */
    private $logger;

    private string $suluSecuritySystem = 'Sulu';
    private int $tokenSendLimit = 3;

    protected function setUp(): void
    {
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->tokenGenerator = $this->prophesize(TokenGeneratorInterface::class);
        $this->twig = $this->prophesize(TwigEnvironment::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->mailer = $this->prophesize(MailerInterface::class);
        $this->passwordHasherFactory = $this->prophesize(PasswordHasherFactoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepository::class);
        $this->router = $this->prophesize(UrlGeneratorInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->resettingController = new ResettingController(
            $this->validator->reveal(),
            $this->translator->reveal(),
            $this->tokenGenerator->reveal(),
            $this->twig->reveal(),
            $this->tokenStorage->reveal(),
            $this->dispatcher->reveal(),
            $this->mailer->reveal(),
            $this->passwordHasherFactory->reveal(),
            $this->userRepository->reveal(),
            $this->router->reveal(),
            $this->entityManager->reveal(),
            $this->domainEventCollector->reveal(),
            $this->suluSecuritySystem,
            'sulu@example.com',
            'sulu_security.reset_mail_subject',
            'admin',
            '@SuluSecurity/mail_templates/reset_password.html.twig',
            (string) $this->tokenSendLimit,
            'admin@example.com',
            's3cr3t',
            $this->logger->reveal(),
        );
    }

    public function testSendEmailActionLogsDebugOnEntityNotFoundException(): void
    {
        $user = new User();
        $user->setPasswordResetTokenExpiresAt(new \DateTime('+10 minutes'));
        $request = new Request(request: ['user' => 'admin']);
        $exceptionDuringFindUser = new EntityNotFoundException(User::class, 'admin');

        $this->userRepository->findUserByIdentifier('admin')->willThrow(NoResultException::class);
        $this->userRepository->getClassName()->willReturn(User::class);

        $response = $this->resettingController->sendEmailAction($request);

        $this->logger
            ->debug(
                Argument::exact($exceptionDuringFindUser->getMessage()),
                Argument::that(function($argument) {
                    return \is_array($argument) && isset($argument['exception']) && $argument['exception'] instanceof EntityNotFoundException;
                })
            )
            ->shouldHaveBeenCalled();
    }

    public function testSendEmailActionLogsDebugOnUserNotInSystemException(): void
    {
        $user = new User(); // User has no role that belongs to the Sulu system!
        $user->setPasswordResetTokenExpiresAt(new \DateTime('+10 minutes'));
        $request = new Request(request: ['user' => 'admin']);
        $exceptionDuringFindUser = new UserNotInSystemException($this->suluSecuritySystem, 'admin');

        $this->userRepository->findUserByIdentifier('admin')->willReturn($user);

        $response = $this->resettingController->sendEmailAction($request);

        $this->logger
            ->debug(
                Argument::exact($exceptionDuringFindUser->getMessage()),
                Argument::that(function($argument) {
                    return \is_array($argument) && isset($argument['exception']) && $argument['exception'] instanceof UserNotInSystemException;
                })
            )
            ->shouldHaveBeenCalled();
    }

    public function testSendEmailActionLogsDebugOnTokenEmailsLimitReachedException(): void
    {
        $role = new Role();
        $role->setSystem($this->suluSecuritySystem);
        $userRole = new UserRole();
        $userRole->setRole($role);
        $user = new User();
        $user->setPasswordResetTokenExpiresAt(new \DateTime('+10 minutes'));
        $user->setPasswordResetTokenEmailsSent($this->tokenSendLimit);
        $user->addUserRole($userRole);
        $request = new Request(request: ['user' => 'admin']);
        $expectedException = new TokenEmailsLimitReachedException($this->tokenSendLimit, $user);

        $this->userRepository->findUserByIdentifier('admin')->willReturn($user);

        $response = $this->resettingController->sendEmailAction($request);

        $this->logger
            ->debug(
                Argument::exact($expectedException->getMessage()),
                Argument::that(function($argument) {
                    return \is_array($argument) && isset($argument['exception']) && $argument['exception'] instanceof TokenEmailsLimitReachedException;
                })
            )
            ->shouldHaveBeenCalled();
    }

    public function testSendEmailActionLogsErrorOnUnexpectedException(): void
    {
        $role = new Role();
        $role->setSystem($this->suluSecuritySystem);
        $userRole = new UserRole();
        $userRole->setRole($role);
        $user = new User();
        $user->setPasswordResetTokenExpiresAt(new \DateTime('+10 minutes'));
        $user->addUserRole($userRole);
        $request = new Request(request: ['user' => 'admin']);
        $exceptionDuringFlush = new \RuntimeException('database exception');

        $this->userRepository
            ->findUserByIdentifier('admin')
            ->willReturn($user);
        $this->tokenGenerator
            ->generateToken()
            ->willReturn('unique_token');
        $this->userRepository
            ->findUserByToken('unique_token')
            ->willThrow(new NoResultException());
        $this->entityManager
            ->persist($user)
            ->shouldBeCalled();
        $this->entityManager
            ->flush()
            ->willThrow($exceptionDuringFlush);

        $this->resettingController->sendEmailAction($request);

        $this->logger
            ->error(Argument::exact('database exception'), Argument::is(['exception' => $exceptionDuringFlush]))
            ->shouldHaveBeenCalled();
    }
}
