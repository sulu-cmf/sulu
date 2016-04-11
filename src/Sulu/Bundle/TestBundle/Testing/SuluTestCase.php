<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Component\Content\Document\WorkflowStage;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Security\Core\Tests\Authentication\Token\TestUser;

/**
 * Base test case for functional tests in Sulu.
 */
abstract class SuluTestCase extends KernelTestCase
{
    /**
     * @var PHPCRImporter
     */
    protected $importer;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        // enables garbage collector because symfony/phpunit-bridge disables it. see:
        // see: https://github.com/symfony/symfony/pull/13398/files#diff-81bfee6017752d99d3119f4ddb1a09edR1
        // see: https://github.com/symfony/symfony/pull/13398 (feature list)
        if (!gc_enabled()) {
            gc_enable();
        }
    }

    protected function setUp()
    {
        parent::setUp();

        $this->importer = new PHPCRImporter($this->getContainer()->get('sulu.phpcr.session')->getSession());
    }

    /**
     * Close the database connection after the tests finish.
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->getEntityManager()->getConnection()->close();
    }

    /**
     * Return the test user (which is provided / created
     * by the test_user_provider in this Bundle at runtime).
     *
     * @return TestUser
     */
    protected function getTestUser()
    {
        $user = $this->getEntityManager()->getRepository('Sulu\Bundle\SecurityBundle\Entity\User')
            ->findOneByUsername('test');

        return $user;
    }

    /**
     * Return the ID of the test user (which is provided / created
     * by the test_user_provider in this Bundle at runtime).
     *
     * @return int
     */
    protected function getTestUserId()
    {
        return $this->getTestUser()->getId();
    }

    /**
     * Create an authenticated client.
     *
     * @return Client
     */
    protected function createAuthenticatedClient()
    {
        return $this->createClient(
            [
                'environment' => 'dev',
            ],
            [
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            ]
        );
    }

    /**
     * Create client for tests on the "website" context.
     *
     * @return Client
     */
    protected function createWebsiteClient()
    {
        return $this->createClient(
            [
                'sulu_context' => 'website',
                'environment' => 'dev',
            ]
        );
    }

    /**
     * Initialize / reset the Sulu PHPCR environment.
     *
     * NOTE: We could use the document initializer here rather than manually creating
     *       the webspace nodes, but it currently adds more overhead and offers
     *       no control over *which* webspaces are created, see
     *       https://github.com/sulu-io/sulu/pull/2063 for a solution.
     */
    protected function initPhpcr()
    {
        /** @var SessionInterface $session */
        $registry = $this->getContainer()->get('doctrine_phpcr');

        foreach ($registry->getConnections() as $session) {
            if ($session->nodeExists('/cmf')) {
                $session->getNode('/cmf')->remove();
            }
            $session->save();
        }


        if (!$this->importer) {
            $this->importer = new PHPCRImporter($this->getContainer()->get('doctrine_phpcr')->getConnection());
        }
        $this->getContainer()->get('sulu_document_manager.initializer')->initialize();
        return;

        // to update this file use following command
        // php vendor/symfony-cmf/testing/bin/console doctrine:phpcr:workspace:export -p /cmf \
        // src/Sulu/Bundle/TestBundle/Resources/dump/initial-state.xml
        $this->importer->import(__DIR__ . '/../Resources/dump/initial-state.xml');
    }

    /**
     * Create a webspace node with the given locales.
     *
     * @param string $name
     * @param string[] $locales
     */
    protected function createHomeDocument($name, array $locales)
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $nodeManager = $this->getContainer()->get('sulu_document_manager.node_manager');

        $homeDocument = new HomeDocument();
        $homeDocument->setTitle('Homepage');
        $homeDocument->setStructureType('default');
        $homeDocument->setWorkflowStage(WorkflowStage::PUBLISHED);

        foreach ($locales as $locale) {
            $nodeManager->createPath('/cmf/' . $name . '/routes/' . $locale);
            $documentManager->persist(
                $homeDocument,
                $locale,
                [
                    'path' => '/cmf/' . $name . '/contents',
                    'auto_create' => true,
                    'load_ghost_content' => false,
                ]
            );
        }

        $documentManager->flush();
    }

    /**
     * Purge the Doctrine ORM database.
     */
    protected function purgeDatabase()
    {
        /** @var EntityManager $manager */
        $manager = $this->getEntityManager();
        $connection = $manager->getConnection();

        if ($connection->getDriver() instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $connection->executeUpdate('SET foreign_key_checks = 0;');
        }

        $purger = new ORMPurger();
        $executor = new ORMExecutor($manager, $purger);
        $referenceRepository = new ProxyReferenceRepository($manager);
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        if ($connection->getDriver() instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $connection->executeUpdate('SET foreign_key_checks = 1;');
        }
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
