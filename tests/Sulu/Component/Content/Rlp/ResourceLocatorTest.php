<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Rlp;

use Doctrine\ORM\EntityManager;
use PHPCR\SessionInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class ResourceLocatorTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var RlpMapperInterface
     */
    private $rlpMapper;

    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    protected function setUp()
    {
        $this->purgeDatabase();
        $this->initOrm();
        $this->initPhpcr();

        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->session = $this->sessionManager->getSession();
        $this->rlpMapper = new PhpcrMapper($this->getContainer()->get('sulu.phpcr.session'));
        $this->strategy = $this->getContainer()->get('sulu.content.rlp.strategy.tree');

        $this->resourceLocator = new ResourceLocator($this->strategy, 'not-in-use');
    }

    protected function initOrm()
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }

    public function testWrite()
    {
        $property = new Property('url', array(), 'resource_locator');
        $property->setValue('/test');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $this->session->save();

        $this->resourceLocator->write($node, $property, 1, 'sulu_io', 'en');

        $this->assertEquals('/test', $node->getPropertyValue('url'));
        $this->assertTrue($this->session->getRootNode()->hasNode('cmf/sulu_io/routes/en/test'));
    }

    public function testLoadFromProperty()
    {
        $property = new Property('url', array(), 'resource_locator');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $node->setProperty($property->getName(), '/test');
        $this->session->save();

        $this->resourceLocator->read($node, $property, 1, 'sulu_io', 'en');

        $this->assertEquals('/test', $property->getValue());
    }

    public function testLoadFromNode()
    {
        $property = new Property('url', array(), 'resource_locator');
        $property->setValue('/test');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $this->session->save();

        $this->resourceLocator->write($node, $property, 1, 'sulu_io', 'en');
        $this->session->save();

        $property->setValue('not-good');

        $this->resourceLocator->read($node, $property, 'sulu_io', 'en');

        $this->assertEquals('/test', $property->getValue());
        $this->assertEquals('/test', $node->getPropertyValue('url'));
    }

    public function testOverride()
    {
        $property = new Property('url', array(), 'resource_locator');
        $property->setValue('/test');

        $node = $this->sessionManager->getContentNode('sulu_io')->addNode('test');
        $node->addMixin('sulu:content');
        $this->session->save();

        $this->resourceLocator->write($node, $property, 1, 'sulu_io', 'en');

        $this->assertEquals('/test', $node->getPropertyValue('url'));
        $this->assertTrue($this->session->getRootNode()->hasNode('cmf/sulu_io/routes/en/test'));

        $property->setValue('/test-2');
        $this->resourceLocator->write($node, $property, 1, 'sulu_io', 'en');

        $this->assertEquals('/test-2', $node->getPropertyValue('url'));
        $this->assertTrue($this->session->getRootNode()->hasNode('cmf/sulu_io/routes/en/test-2'));
    }
}
