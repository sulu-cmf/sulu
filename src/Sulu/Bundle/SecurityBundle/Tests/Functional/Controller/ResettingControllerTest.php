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
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecord;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

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
     * @var ObjectRepository<EventRecord>
     */
    private $eventRepository;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->eventRepository = $this->em->getRepository(EventRecord::class);
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

    public function testSendEmailAction()
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[0]->getEmail(),
        ]);

        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);

        // asserting user properties
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
            'SuluSecurityBundle:User',
            $this->users[0]->getId()
        );
        $this->assertTrue(\is_string($user->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());

        $this->assertEquals(1, $mailCollector->getMessageCount());
        $message = $mailCollector->getMessages()[0];

        // asserting sent mail
        \preg_match('/forgotPasswordToken=(.*)/', $message->getBody(), $regexMatches);
        $token = $regexMatches[1];
        $expectedEmailData = $this->getExpectedEmailData($this->client, $user, $token);
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($expectedEmailData['sender'], \key($message->getFrom()));
        $this->assertEquals($user->getEmail(), \key($message->getTo()));
        $this->assertEquals($expectedEmailData['subject'], $message->getSubject());
        $this->assertEquals($expectedEmailData['body'], $message->getBody());
    }

    public function testSendEmailActionWithUsername()
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[0]->getUsername(),
        ]);

        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);

        // asserting user properties
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
                'SuluSecurityBundle:User',
                $this->users[0]->getId()
            );
        $this->assertTrue(\is_string($user->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());
        $this->assertEquals(1, $user->getPasswordResetTokenEmailsSent());

        $message = $mailCollector->getMessages()[0];
        // asserting sent mail
        \preg_match('/forgotPasswordToken=(.*)/', $message->getBody(), $regexMatches);
        $token = $regexMatches[1];
        $expectedEmailData = $this->getExpectedEmailData($this->client, $user, $token);

        $this->assertEquals(1, $mailCollector->getMessageCount());
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($expectedEmailData['sender'], \key($message->getFrom()));
        $this->assertEquals($user->getEmail(), \key($message->getTo()));
        $this->assertEquals($expectedEmailData['subject'], $message->getSubject());
        $this->assertEquals($expectedEmailData['body'], $message->getBody());
    }

    public function testSendEmailActionWithUserWithoutEmail()
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[1]->getUsername(),
        ]);

        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);

        // asserting user properties
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
                'SuluSecurityBundle:User',
                $this->users[1]->getId()
            );
        $this->assertTrue(\is_string($user->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());
        $this->assertEquals(1, $user->getPasswordResetTokenEmailsSent());

        $message = $mailCollector->getMessages()[0];
        // asserting sent mail
        \preg_match('/forgotPasswordToken=(.*)/', $message->getBody(), $regexMatches);
        $token = $regexMatches[1];
        $expectedEmailData = $this->getExpectedEmailData($this->client, $user, $token);

        $this->assertEquals(1, $mailCollector->getMessageCount());
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($expectedEmailData['sender'], \key($message->getFrom()));
        $this->assertEquals('installation.email@sulu.test', \key($message->getTo()));
        $this->assertEquals($expectedEmailData['subject'], $message->getSubject());
        $this->assertEquals($expectedEmailData['body'], $message->getBody());
    }

    public function testResendEmailActionTooMuch()
    {
        $this->client->enableProfiler();

        // these request should all work (starting counter at 1 - because user3 already has one sent email)
        $counter = 1;
        $maxNumberEmails = $this->getContainer()->getParameter('sulu_security.reset_password.mail.token_send_limit');
        for (; $counter < $maxNumberEmails; ++$counter) {
            $this->client->jsonRequest('GET', '/security/reset/email', [
                'user' => $this->users[2]->getEmail(),
            ]);

            $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');
            $response = \json_decode($this->client->getResponse()->getContent());

            $this->assertHttpStatusCode(204, $this->client->getResponse());
            $this->assertEquals(null, $response);
            $this->assertEquals(1, $mailCollector->getMessageCount());
        }

        // now this request should fail
        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[2]->getEmail(),
        ]);

        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');
        $response = \json_decode($this->client->getResponse()->getContent());
        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
                'SuluSecurityBundle:User',
                $this->users[2]->getId()
            );

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);
        $this->assertEquals(0, $mailCollector->getMessageCount());
        $this->assertEquals($counter, $user->getPasswordResetTokenEmailsSent());
    }

    public function testSendEmailActionWithMissingUser()
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email');

        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testSendEmailActionWithNotExistingUser()
    {
        $this->client->enableProfiler();

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => 'lord.voldemort@askab.an',
        ]);

        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testSendEmailActionMultipleTimes()
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
        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');
        // asserting response
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEquals(null, $response);
        $this->assertEquals(1, $mailCollector->getMessageCount());
    }

    public function testResetAction()
    {
        $newPassword = 'anewpasswordishouldremeber';

        $this->client->jsonRequest('GET', '/security/reset/email', [
            'user' => $this->users[2]->getUsername(),
        ]);
        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');
        $message = $mailCollector->getMessages()[0];
        \preg_match('/forgotPasswordToken=(.*)/', $message->getBody(), $regexMatches);
        $token = $regexMatches[1];

        $this->client->jsonRequest('GET', '/security/reset', [
            'token' => $token,
            'password' => $newPassword,
        ]);

        $user = $this->client->getContainer()->get('doctrine')->getManager()->find(
                'SuluSecurityBundle:User',
                $this->users[2]->getId()
            );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var DomainEvent $event */
        $event = $this->eventRepository->findOneBy(['eventType' => 'password_resetted']);
        $this->assertSame((string) $this->users[2]->getId(), $event->getResourceId());

        $encoder = $this->getContainer()->get('sulu_security.encoder_factory')->getEncoder($user);
        $this->assertEquals($encoder->encodePassword($newPassword, $user->getSalt()), $user->getPassword());
        $this->assertNull($user->getPasswordResetToken());
        $this->assertNull($user->getPasswordResetTokenExpiresAt());
    }

    public function testResetActionWithoutToken()
    {
        $passwordBefore = $this->users[2]->getPassword();

        $this->client->jsonRequest('GET', '/security/reset', [
            'password' => 'thispasswordshouldnotbeapplied',
        ]);
        $response = \json_decode($this->client->getResponse()->getContent());
        $user = $this->em->find('SuluSecurityBundle:User', $this->users[2]->getId());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(1006, $response->code);
        $this->assertEquals($passwordBefore, $user->getPassword());
    }

    public function testResetActionWithInvalidToken()
    {
        $passwordBefore = $this->users[2]->getPassword();

        $this->client->jsonRequest('GET', '/security/reset', [
            'token' => 'thistokendoesnotexist',
            'password' => 'thispasswordshouldnotbeapplied',
        ]);
        $response = \json_decode($this->client->getResponse()->getContent());
        $user = $this->em->find('SuluSecurityBundle:User', $this->users[2]->getId());

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $this->assertEquals(1005, $response->code);
        $this->assertEquals($passwordBefore, $user->getPassword());
    }

    public function testResetActionNoRole()
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

    public function testResetActionDifferentSystem()
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

    protected function getExpectedEmailData($client, User $user, string $token)
    {
        $sender = $this->getContainer()->getParameter('sulu_security.reset_password.mail.sender');
        $template = $this->getContainer()->getParameter('sulu_security.reset_password.mail.template');
        $resetUrl = $this->getContainer()->get('router')->generate(
            'sulu_admin',
            [],
            \Symfony\Component\Routing\Router::ABSOLUTE_URL
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

    protected function createRole($system)
    {
        $role = new Role();
        $role->setName($system);
        $role->setSystem($system);

        return $role;
    }

    protected function createUser($index, $email = null)
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

    protected function createUserRole(User $user, Role $role)
    {
        $userRole = new UserRole();
        $userRole->setLocale(\json_encode(['de']));
        $userRole->setRole($role);
        $userRole->setUser($user);

        return $userRole;
    }
}
