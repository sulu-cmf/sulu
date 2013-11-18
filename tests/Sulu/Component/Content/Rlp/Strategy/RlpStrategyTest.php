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

use Jackalope\Node;
use PHPCR\NodeInterface;
use ReflectionClass;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategy;

class RlpStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RlpMapperInterface
     */
    private $mapper;
    /**
     * @var RlpStrategy
     */
    private $strategy;
    /**
     * @var string
     */
    private $className = 'Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategy';
    /**
     * @var bool
     */
    private $isSaved = false;

    protected function setUp()
    {
        $this->mapper = $this->getMock(
            'Sulu\Component\Content\Types\Rlp\Mapper\RlpMapper',
            array('unique', 'getUniquePath', 'save', 'loadByContent', 'loadByResourceLocator'),
            array('test-mapper'),
            'TestMapper'
        );
        $this->mapper->expects($this->any())
            ->method('unique')
            ->will($this->returnCallback(array($this, 'uniqueCallback')));
        $this->mapper->expects($this->any())
            ->method('getUniquePath')
            ->will($this->returnCallback(array($this, 'getUniquePathCallback')));
        $this->mapper->expects($this->any())
            ->method('save')
            ->will($this->returnCallback(array($this, 'saveCallback')));
        $this->mapper->expects($this->any())
            ->method('loadByContent')
            ->will($this->returnValue('/test'));
        $this->mapper->expects($this->any())
            ->method('loadByResourceLocator')
            ->will($this->returnValue('this-is-a-uuid'));

        $this->strategy = $this->getMockForAbstractClass(
            $this->className,
            array('test-strategy', $this->mapper),
            'TestStrategy'
        );
        $this->strategy->expects($this->any())
            ->method('generatePath')
            ->will($this->returnCallback(array($this, 'generateCallback')));
    }

    public function uniqueCallback()
    {
        $args = func_get_args();
        if ($args[0] == '/products/machines' || $args[0] == '/products/machines/drill') {
            return false;
        }

        return true;
    }

    public function getUniquePathCallback()
    {
        $args = func_get_args();
        if ($args[0] == '/products/machines' || $args[0] == '/products/machines/drill') {
            return $args[0] . '-1';
        }

        return $args[0];
    }

    public function generateCallback()
    {
        $args = func_get_args();

        return $args[1] . '/' . $args[0];
    }

    public function saveCallback()
    {
        $this->isSaved = true;
    }

    protected function tearDown()
    {

    }

    private function getMethod($class, $name)
    {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function testCleanUp()
    {
        $method = $this->getMethod($this->className, 'cleanup');
        $clean = $method->invokeArgs($this->strategy, array('-/aSDf     asdf/äöü-'));

        $this->assertEquals('/asdf-asdf/aeoeue', $clean);
    }

    public function testIsValid()
    {
        // false from mapper (is not unique)
        $result = $this->strategy->isValid('/products/machines', 'default');
        $this->assertFalse($result);

        // true from mapper (is unique)
        $result = $this->strategy->isValid('/products/machines-1', 'default');
        $this->assertTrue($result);

        // false from strategy incorrect signs
        $result = $this->strategy->isValid('/products/mä  chines', 'default');
        $this->assertFalse($result);
    }

    public function testGenerate()
    {
        // /products/machines => not unique add -1
        $result = $this->strategy->generate('machines', '/products', 'default');
        $this->assertEquals('/products/machines-1', $result);

        // /products/machines/drill => not unique add -1
        $result = $this->strategy->generate('drill', '/products/machines', 'default');
        $this->assertEquals('/products/machines/drill-1', $result);

        // /products/mä   chines => after cleanup => /products/mae-chines
        $result = $this->strategy->generate('mä   chines', '/products', 'default');
        $this->assertEquals('/products/mae-chines', $result);

        // /products/mächines => after cleanup => /products/maechines
        $result = $this->strategy->generate('mächines', '/products', 'default');
        $this->assertEquals('/products/maechines', $result);
    }

    /**
     * @return NodeInterface
     */
    private function getNodeMock()
    {
        return $this->getMockForAbstractClass('\Jackalope\Node', array(), 'MockNode', false);
    }

    public function testSave()
    {
        $this->isSaved = false;
        // its a delegate
        $this->strategy->save($this->getNodeMock(), '/test/test-1', 'default');

        $this->assertTrue($this->isSaved);
    }

    public function testRead()
    {
        // its a delegate
        $result = $this->mapper->loadByContent($this->getNodeMock(), 'default');
        $this->assertEquals('/test', $result);
    }

    public function testLoad()
    {
        //its a delegate
        $result = $this->mapper->loadByResourceLocator('/test', 'default');
        $this->assertEquals('this-is-a-uuid', $result);
    }
}
