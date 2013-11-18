<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Rlp\Strategy;

use Jackalope\RepositoryFactoryJackrabbit;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPCR\SimpleCredentials;
use PHPCR\Util\NodeHelper;
use \PHPUnit_Framework_TestCase;
use Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryInterface;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryService;

class PhpcrMapperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var RlpMapperInterface
     */
    private $mapper;
    /**
     * @var SessionFactoryInterface
     */
    private $sessionService;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var NodeInterface
     */
    private $content1;

    public function setUp()
    {
        $this->sessionService = new SessionFactoryService(new RepositoryFactoryJackrabbit(), array(
            'url' => 'http://localhost:8080/server',
            'username' => 'admin',
            'password' => 'admin',
            'workspace' => 'default'
        ));
        $this->session = $this->prepareSession();
        $this->prepareRepository();
        $this->prepareTestData();
        $this->mapper = new PhpcrMapper($this->sessionService, '/cmf/routes');
    }

    public function prepareRepository()
    {
        $this->session->getWorkspace()->getNamespaceRegistry()->registerNamespace('sulu', 'http://sulu.io/phpcr');
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new SuluNodeType(), true);
        $this->session->getWorkspace()->getNodeTypeManager()->registerNodeType(new PathNodeType(), true);
    }

    public function tearDown()
    {
        if (isset($this->session)) {
            NodeHelper::purgeWorkspace($this->session);
            $this->session->save();
        }
    }

    private function prepareSession()
    {
        $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server');
        $factory = new RepositoryFactoryJackrabbit();
        $repository = $factory->getRepository($parameters);
        $credentials = new SimpleCredentials('admin', 'admin');

        /** @var SessionInterface $session */
        $session = $repository->login($credentials, 'default');

        NodeHelper::purgeWorkspace($session);
        $session->save();

        return $session;
    }

    private function prepareTestData()
    {
        $cmf = $this->session->getRootNode()->addNode('cmf');
        $cmf->addMixin('mix:referenceable');

        $routes = $cmf->addNode('routes');
        $routes->addMixin('mix:referenceable');

        $products = $routes->addNode('products');
        $products->addMixin('mix:referenceable');

        $machines = $products->addNode('machines');
        $machines->addMixin('mix:referenceable');

        $machines1 = $products->addNode('machines-1');
        $machines1->addMixin('mix:referenceable');

        $drill = $machines->addNode('drill');
        $drill->addMixin('mix:referenceable');

        $drill1 = $machines->addNode('drill-1');
        $drill1->addMixin('mix:referenceable');

        $contents = $cmf->addNode('contents');
        $contents->addMixin('mix:referenceable');

        $this->content1 = $contents->addNode('content1');
        $this->content1->addMixin('mix:referenceable');

        $this->session->save();
    }

    public function testUnique()
    {
        // exists in phpcr
        $result = $this->mapper->unique('/products/machines', 'default');
        $this->assertFalse($result);

        // exists in phpcr
        $result = $this->mapper->unique('/products/machines/drill', 'default');
        $this->assertFalse($result);

        // not exists in phpcr
        $result = $this->mapper->unique('/products/machines-2', 'default');
        $this->assertTrue($result);

        // not exists in phpcr
        $result = $this->mapper->unique('/products/machines/drill-2', 'default');
        $this->assertTrue($result);

        // not exists in phpcr
        $result = $this->mapper->unique('/news', 'default');
        $this->assertTrue($result);
    }

    public function testGetUniquePath()
    {
        // machines & machines-1 exists
        $result = $this->mapper->getUniquePath('/products/machines', 'default');
        $this->assertEquals('/products/machines-2', $result);
        $this->assertTrue($this->mapper->unique($result, 'default'));

        // drill & drill-1 exists
        $result = $this->mapper->getUniquePath('/products/machines/drill', 'default');
        $this->assertEquals('/products/machines/drill-2', $result);
        $this->assertTrue($this->mapper->unique($result, 'default'));

        // products exists
        $result = $this->mapper->getUniquePath('/products', 'default');
        $this->assertEquals('/products-1', $result);
        $this->assertTrue($this->mapper->unique($result, 'default'));

        // news not exists
        $result = $this->mapper->getUniquePath('/news', 'default');
        $this->assertEquals('/news', $result);
        $this->assertTrue($this->mapper->unique($result, 'default'));
    }

    public function testSaveFailure()
    {
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException');
        $this->mapper->save($this->content1, '/products/machines/drill', 'default');
    }

    public function testSave()
    {
        $this->mapper->save($this->content1, '/products/news/content1-news', 'default');
        $this->sessionService->getSession()->save();

        $route = '/cmf/routes/products/news/content1-news';

        $node = $this->session->getNode($route);
        $this->assertTrue($node->getPropertyValue('sulu:content') == $this->content1);
        $this->assertTrue($node->hasProperty('sulu:content'));
    }

    public function testReadFailure()
    {
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorNotFoundException');
        $this->mapper->loadByContent($this->content1, 'default');
    }

    public function testRead()
    {
        $this->mapper->save($this->content1, '/products/news/content1-news', 'default');
        $this->sessionService->getSession()->save();

        $result = $this->mapper->loadByContent($this->content1, 'default');
        $this->assertEquals('/products/news/content1-news', $result);
    }

    public function testLoadFailure()
    {
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorNotFoundException');
        $this->mapper->loadByResourceLocator('/test/test-1', 'default');
    }

    public function testLoad()
    {
        // create route for content
        $this->mapper->save($this->content1, '/products/news/content1-news', 'default');
        $this->sessionService->getSession()->save();

        $result = $this->mapper->loadByResourceLocator('/products/news/content1-news', 'default');
        $this->assertEquals($this->content1->getIdentifier(), $result);
    }
}
