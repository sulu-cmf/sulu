<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Router;

class ResettingControllerTest extends SuluTestCase
{
    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var User[]
     */
    private $users = [];

    /**
     * @var Role
     */
    private $role;

    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var ObjectRepository<ActivityInterface>
     */
    private $activityRepository;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->activityRepository = $this->em->getRepository(ActivityInterface::class);
        $this->purgeDatabase();

        $this->role = $this->createRole('Sulu');
        $this->em->persist($this->role);

        // User 1
        $this->users[] = $user = $this->createUser(1, 'user1@test.com');
        $this->em->persist($user);
        $this->em->persist($this->createUserRole($user, $this->role));

        // User 2
        $this->users[] = $user = $this->createUser(2);
        $this->em->persist($user);
        $this->em->persist($this->createUserRole($user, $this->role));

        // User 3
        $this->users[] = $user = $this->createUser(3, 'user3@test.com');
        $user->setPasswordResetToken('thisisasupersecrettoken');
        $user->setPasswordResetTokenExpiresAt((new \DateTime())->add(new \DateInterval('PT24H')));
        $user->setPasswordResetTokenEmailsSent(1);
        $this->em->persist($user);
        $this->em->persist($this->createUserRole($user, $this->role));

        $this->em->flush();
        $this->em->clear();
    }

    public function testSendEmailAction(): void
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[0]->getEmail(),
        ]);

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);

        // asserting user properties
        /** @var User $user */
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
            User::class,
            $this->users[0]->getId()
        );
        $this->assertTrue(\is_string($user->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());

        $messages = $mailCollector->getEvents()->getMessages();
        $this->assertCount(1, $messages);

        $message = $messages[0];
        $this->assertInstanceOf(Email::class, $message);

        // asserting sent mail
        $htmlBody = $message->getHtmlBody();
        $this->assertIsString($htmlBody);
        \preg_match('/forgotPasswordToken=(.*)/', $htmlBody, $regexMatches);
        $this->assertArrayHasKey(1, $regexMatches);
        $token = $regexMatches[1];
        $expectedEmailData = $this->getExpectedEmailData($this->client, $user, $token);

        $this->assertEquals($expectedEmailData['sender'], $message->getFrom()[0]->getAddress());
        $this->assertEquals($user->getEmail(), $message->getTo()[0]->getAddress());
        $this->assertEquals($expectedEmailData['subject'], $message->getSubject());
        $this->assertEquals($expectedEmailData['body'], $htmlBody);
    }

    public function testSendEmailActionWithUsername(): void
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[0]->getUsername(),
        ]);

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);

        // asserting user properties
        /** @var User $user */
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
            User::class,
            $this->users[0]->getId()
        );
        $this->assertTrue(\is_string($user->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());
        $this->assertEquals(1, $user->getPasswordResetTokenEmailsSent());

        $messages = $mailCollector->getEvents()->getMessages();
        $this->assertCount(1, $messages);

        $message = $messages[0];
        $this->assertInstanceOf(Email::class, $message);

        // asserting sent mail
        $htmlBody = $message->getHtmlBody();
        $this->assertIsString($htmlBody);
        \preg_match('/forgotPasswordToken=(.*)/', $htmlBody, $regexMatches);
        $this->assertArrayHasKey(1, $regexMatches);
        $token = $regexMatches[1];
        $expectedEmailData = $this->getExpectedEmailData($this->client, $user, $token);

        $this->assertEquals($expectedEmailData['sender'], $message->getFrom()[0]->getAddress());
        $this->assertEquals($user->getEmail(), $message->getTo()[0]->getAddress());
        $this->assertEquals($expectedEmailData['subject'], $message->getSubject());
        $this->assertEquals($expectedEmailData['body'], $htmlBody);
    }

    public function testSendEmailActionWithUserWithoutEmail(): void
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[1]->getUsername(),
        ]);

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);

        // asserting user properties
        /** @var User $user */
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
            User::class,
            $this->users[1]->getId()
        );
        $this->assertTrue(\is_string($user->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());
        $this->assertEquals(1, $user->getPasswordResetTokenEmailsSent());

        $messages = $mailCollector->getEvents()->getMessages();
        $this->assertCount(1, $messages);

        $message = $messages[0];
        $this->assertInstanceOf(Email::class, $message);

        // asserting sent mail
        $htmlBody = $message->getHtmlBody();
        $this->assertIsString($htmlBody);
        \preg_match('/forgotPasswordToken=(.*)/', $htmlBody, $regexMatches);
        $this->assertArrayHasKey(1, $regexMatches);
        $token = $regexMatches[1];
        $expectedEmailData = $this->getExpectedEmailData($this->client, $user, $token);

        $this->assertEquals($expectedEmailData['sender'], $message->getFrom()[0]->getAddress());
        $this->assertEquals('installation.email@sulu.test', $message->getTo()[0]->getAddress());
        $this->assertEquals($expectedEmailData['subject'], $message->getSubject());
        $this->assertEquals($expectedEmailData['body'], $htmlBody);
    }

    public function testResendEmailActionTooMuch(): void
    {
        $this->client->enableProfiler();

        // these request should all work (starting counter at 1 - because user3 already has one sent email)
        $counter = 1;
        $maxNumberEmails = $this->getContainer()->getParameter('sulu_security.reset_password.mail.token_send_limit');
        for (; $counter < $maxNumberEmails; ++$counter) {
            $this->client->jsonRequest('GET', '/security/reset/email', [
                'user' => $this->users[2]->getEmail(),
            ]);

            /** @var MessageDataCollector $mailCollector */
            $mailCollector = $this->client->getProfile()->getCollector('mailer');
            $response = \json_decode($this->client->getResponse()->getContent());

            $this->assertHttpStatusCode(204, $this->client->getResponse());
            $this->assertEquals(null, $response);
            $this->assertCount(1, $mailCollector->getEvents()->getMessages());
        }

        // now this request should fail
        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[2]->getEmail(),
        ]);

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');
        $response = \json_decode($this->client->getResponse()->getContent());
        /** @var User $user */
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
            User::class,
            $this->users[2]->getId()
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);
        $this->assertCount(0, $mailCollector->getEvents()->getMessages());
        $this->assertEquals($counter, $user->getPasswordResetTokenEmailsSent());
    }

    public function testResetCounterAfterTokenExpiration(): void
    {
        $this->client->enableProfiler();

        // starting counter at 1 - because user3 already has one sent email
        $counter = 1;
        $maxNumberEmails = $this->getContainer()->getParameter('sulu_security.reset_password.mail.token_send_limit');
        for (; $counter < $maxNumberEmails; ++$counter) {
            $this->client->jsonRequest('GET', '/security/reset/email', [
                'user' => $this->users[2]->getEmail(),
            ]);

            /** @var MessageDataCollector $mailCollector */
            $mailCollector = $this->client->getProfile()->getCollector('mailer');
            $response = \json_decode($this->client->getResponse()->getContent());

            $this->assertHttpStatusCode(204, $this->client->getResponse());
            $this->assertEquals(null, $response);
            $this->assertCount(1, $mailCollector->getEvents()->getMessages());
        }

        static::ensureKernelShutdown(); // shutdown to fix lowest test with: `Error: Cannot use object of type Doctrine\ORM\EntityNotFoundException as array` when try to flush the entity manager next
        $this->client = $this->createAuthenticatedClient();

        /** @var User $user */
        $user = $this->em->find(
            User::class,
            $this->users[2]->getId(),
        );

        $user->setPasswordResetTokenExpiresAt(new \DateTime('-1 day'));
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $user->getEmail(),
        ]);

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');
        $response = \json_decode($this->client->getResponse()->getContent());
        /** @var User $user */
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
            User::class,
            $user->getId()
        );

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);
        $this->assertCount(1, $mailCollector->getEvents()->getMessages());
        $this->assertEquals(1, $user->getPasswordResetTokenEmailsSent());
        $this->assertGreaterThanOrEqual(new \DateTime(), $user->getPasswordResetTokenExpiresAt());
    }

    public function testSendEmailActionWithMissingUser(): void
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email');

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);
        $this->assertCount(0, $mailCollector->getEvents()->getMessages());
    }

    public function testSendEmailActionWithNotExistingUser(): void
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => 'lord.voldemort@askab.an',
        ]);

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);
        $this->assertCount(0, $mailCollector->getEvents()->getMessages());
    }

    public function testSendEmailActionMultipleTimes(): void
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[0]->getUsername(),
        ]);
        $response = \json_decode($this->client->getResponse()->getContent());
        // asserting response
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);

        // second request should be blocked
        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[0]->getUsername(),
        ]);
        $response = \json_decode($this->client->getResponse()->getContent());

        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');
        // asserting response
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);
        $this->assertCount(1, $mailCollector->getEvents()->getMessages());
    }

    public function testResetAction(): void
    {
        $newPassword = 'anewpasswordishouldremeber';

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[2]->getUsername(),
        ]);
        /** @var MessageDataCollector $mailCollector */
        $mailCollector = $this->client->getProfile()->getCollector('mailer');
        $message = $mailCollector->getEvents()->getMessages()[0];
        $this->assertInstanceOf(Email::class, $message);

        $htmlBody = $message->getHtmlBody();
        $this->assertIsString($htmlBody);
        \preg_match('/forgotPasswordToken=(.*)/', $htmlBody, $regexMatches);
        $this->assertArrayHasKey(1, $regexMatches);
        $token = $regexMatches[1];

        $this->client->jsonRequest('GET', '/security/reset', [
            'token' => $token,
            'password' => $newPassword,
        ]);

        /** @var User $user */
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
            User::class,
            $this->users[2]->getId()
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'password_resetted']);
        $this->assertSame((string) $this->users[2]->getId(), $activity->getResourceId());

        $passwordHasherFactory = $this->getContainer()->get('security.password_hasher_factory');
        $hasher = $passwordHasherFactory->getPasswordHasher($user);
        $password = $hasher->hash($newPassword);

        $this->assertEquals($password, $user->getPassword());
        $this->assertNull($user->getPasswordResetToken());
        $this->assertNull($user->getPasswordResetTokenExpiresAt());
    }

    public function testResetActionWithoutToken(): void
    {
        $passwordBefore = $this->users[2]->getPassword();

        $this->client->jsonRequest('GET', '/security/reset', [
            'password' => 'thispasswordshouldnotbeapplied',
        ]);
        $response = \json_decode($this->client->getResponse()->getContent());
        /** @var User $user */
        $user = $this->em->find(User::class, $this->users[2]->getId());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(1006, $response->code);
        $this->assertEquals($passwordBefore, $user->getPassword());
    }

    public function testResetActionWithInvalidToken(): void
    {
        $passwordBefore = $this->users[2]->getPassword();

        $this->client->jsonRequest('GET', '/security/reset', [
            'token' => 'thistokendoesnotexist',
            'password' => 'thispasswordshouldnotbeapplied',
        ]);
        $response = \json_decode($this->client->getResponse()->getContent());
        /** @var User $user */
        $user = $this->em->find(User::class, $this->users[2]->getId());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(1005, $response->code);
        $this->assertEquals($passwordBefore, $user->getPassword());
    }

    public function testResetActionNoRole(): void
    {
        $user = $this->createUser(4);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $user->getUsername(),
        ]);
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(null, $response);
    }

    public function testResetActionDifferentSystem(): void
    {
        $role = $this->createRole('Website');
        $this->em->persist($role);

        $user = $this->createUser(4);
        $this->em->persist($user);

        $userRole = $this->createUserRole($user, $role);
        $this->em->persist($userRole);

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $user->getUsername(),
        ]);
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(null, $response);
    }

    /**
     * @return array{subject: string, body: string, sender: string}
     */
    protected function getExpectedEmailData($client, User $user, string $token): array
    {
        $sender = $this->getContainer()->getParameter('sulu_security.reset_password.mail.sender');
        $template = $this->getContainer()->getParameter('sulu_security.reset_password.mail.template');
        $resetUrl = $this->getContainer()->get('router')->generate(
            'sulu_admin',
            [],
            Router::ABSOLUTE_URL
        );
        $body = $this->getContainer()->get('twig')->render($template, [
            'user' => $user,
            'reset_url' => $resetUrl . '#/?forgotPasswordToken=' . $token,
            'translation_domain' => $this->getContainer()->getParameter('sulu_security.reset_password.mail.translation_domain'),
        ]);

        return [
            'subject' => 'Reset your Sulu password',
            'body' => \trim($body),
            'sender' => $sender ? $sender : 'no-reply@' . $client->getRequest()->getHost(),
        ];
    }

    protected function createRole($system): Role
    {
        $role = new Role();
        $role->setName($system);
        $role->setSystem($system);

        return $role;
    }

    protected function createUser($index, $email = null): User
    {
        $user = new User();
        $user->setUsername('user' . $index);
        $user->setEmail($email);
        $user->setPassword('securepassword');
        $user->setSalt('salt');
        $user->setLocale('en');

        $contact = new Contact();
        $contact->setFirstName('User' . $index);
        $contact->setLastName('Test');
        $user->setContact($contact);
        $this->em->persist($contact);

        return $user;
    }

    protected function createUserRole(User $user, Role $role): UserRole
    {
        $userRole = new UserRole();
        $userRole->setLocale(\json_encode(['de']));
        $userRole->setRole($role);
        $userRole->setUser($user);

        return $userRole;
    }
}
