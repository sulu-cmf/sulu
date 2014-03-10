<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use Jackalope\RepositoryFactoryJackrabbit;
use Jackalope\Session;
use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SimpleCredentials;
use PHPCR\Util\NodeHelper;
use ReflectionMethod;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\BreadcrumbItemInterface;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Sulu\Component\PHPCR\SessionManager\SessionManager;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use \DateTime;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * tests content mapper with tree strategy and phpcr mapper
 */
class ContentMapperTest extends PhpcrTestCase
{
    public function setUp()
    {
        $this->prepareMapper();
    }

    public function structureCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];

        if ($structureKey == 'overview') {
            return $this->getStructureMock(1);
        } elseif ($structureKey == 'simple') {
            return $this->getStructureMock(2);
        }

        return null;
    }

    public function getStructureMock($type = 1)
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure',
            array('overview', 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'add'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property('title', 'text_line')
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property('url', 'resource_locator')
            )
        );

        if ($type == 1) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('tags', 'text_line', false, false, 2, 10)
                )
            );

            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('article', 'text_area')
                )
            );
        } elseif ($type == 2) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('blog', 'text_area')
                )
            );
        }

        return $structureMock;
    }

    public function tearDown()
    {
        if (isset($this->session)) {
            NodeHelper::purgeWorkspace($this->session);
            $this->session->save();
        }
    }

    public function testSave()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('sulu_locale:de-title')->getString());
        $this->assertEquals('default', $content->getProperty('sulu_locale:de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getPropertyValue('sulu_locale:de-sulu-state'));
        $this->assertEquals(false, $content->getPropertyValue('sulu_locale:de-sulu-navigation'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));
    }

    public function testLoad()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $content = $this->mapper->load($structure->getUuid(), 'default', 'de');

        $this->assertNotNull($content->getUuid());
        $this->assertEquals('default', $content->getWebspaceKey());
        $this->assertEquals('de', $content->getLanguageCode());
        $this->assertEquals('overview', $content->getKey());
        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(false, $content->getNavigation());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);
    }

    public function testNewProperty()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $contentBefore = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/test');
        /** @var NodeInterface $contentNode */
        $contentNode = $route->getPropertyValue('sulu:content');
        // simulate new property article, by deleting the property
        /** @var PropertyInterface $articleProperty */
        $articleProperty = $contentNode->getProperty('sulu_locale:de-article');
        $this->session->removeItem($articleProperty->getPath());
        $this->session->save();


        // simulates a new request
        $this->mapper = null;
        $this->session = null;
        $this->sessionManager = null;
        $this->structureValueMap = array(
            'overview' => $this->getStructureMock(1),
            'simple' => $this->getStructureMock(2)
        );
        $this->prepareMapper();


        /** @var StructureInterface $content */
        $content = $this->mapper->load($contentBefore->getUuid(), 'default', 'de');
        // test values
        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);
    }

    public function testLoadByRL()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);
    }

    public function testUpdate()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['tags'][] = 'tag3';
        $data['tags'][0] = 'thats cool';
        $data['article'] = 'thats a new test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals('thats a new test', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('thats cool', 'tag2', 'tag3'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('sulu_locale:de-title')->getString());
        $this->assertEquals('thats a new test', $content->getProperty('sulu_locale:de-article')->getString());
        $this->assertEquals(array('thats cool', 'tag2', 'tag3'), $content->getPropertyValue('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getPropertyValue('sulu_locale:de-sulu-state'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));
    }

    public function testPartialUpdate()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['tags'][] = 'tag3';
        unset($data['tags'][0]);
        unset($data['article']);

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag2', 'tag3'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('sulu_locale:de-title')->getString());
        $this->assertEquals('default', $content->getProperty('sulu_locale:de-article')->getString());
        $this->assertEquals(array('tag2', 'tag3'), $content->getPropertyValue('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getPropertyValue('sulu_locale:de-sulu-state'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));
    }

    public function testNonPartialUpdate()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['tags'][] = 'tag3';
        unset($data['tags'][0]);
        unset($data['article']);

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, false, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag2', 'tag3'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('sulu_locale:de-title')->getString());
        $this->assertEquals(false, $content->hasProperty('sulu_locale:de-article'));
        $this->assertEquals(array('tag2', 'tag3'), $content->getPropertyValue('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getPropertyValue('sulu_locale:de-sulu-state'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));
    }

    public function testUpdateNullValue()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['tags'] = null;
        $data['article'] = null;

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, false, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(null, $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('sulu_locale:de-title')->getString());
        $this->assertEquals(false, $content->hasProperty('sulu_locale:de-article'));
        $this->assertEquals(false, $content->hasProperty('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getPropertyValue('sulu_locale:de-sulu-state'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));
    }

    public function testUpdateTemplate()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data = array(
            'title' => 'Testtitle',
            'blog' => 'this is a blog test'
        );

        // update content
        $this->mapper->save($data, 'simple', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        // old properties not exists in structure
        $this->assertEquals(false, $content->hasProperty('article'));
        $this->assertEquals(false, $content->hasProperty('tags'));

        // old properties are right
        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // new property is set
        $this->assertEquals('this is a blog test', $content->blog);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/test');
        $content = $route->getPropertyValue('sulu:content');

        // old properties exists in node
        $this->assertEquals('default', $content->getPropertyValue('sulu_locale:de-article'));
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('sulu_locale:de-tags'));

        // property of new structure exists
        $this->assertEquals('Testtitle', $content->getProperty('sulu_locale:de-title')->getString());
        $this->assertEquals('this is a blog test', $content->getPropertyValue('sulu_locale:de-blog'));
        $this->assertEquals('simple', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));
    }

    public function testUpdateURL()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['url'] = '/news/test/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test/test/test', 'default', 'de');

        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test/test/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/test/test/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('sulu_locale:de-title')->getString());
        $this->assertEquals('default', $content->getProperty('sulu_locale:de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));

        // old resource locator is not a route (has property sulu:content), it is a history (has property sulu:route)
        $oldRoute = $root->getNode('cmf/default/routes/news/test');
        $this->assertTrue($oldRoute->hasProperty('sulu:content'));
        $this->assertTrue($oldRoute->hasProperty('sulu:history'));
        $this->assertTrue($oldRoute->getPropertyValue('sulu:history'));

        // history should reference to new route
        $history = $oldRoute->getPropertyValue('sulu:content');
        $this->assertEquals($route->getIdentifier(), $history->getIdentifier());
    }

    public function testNameUpdate()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['title'] = 'test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // TODO works after this issue is fixed? but its not necessary
//        // check read
//        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');
//
//        $this->assertEquals('default', $content->title);
//        $this->assertEquals('default', $content->article);
//        $this->assertEquals('/news/test', $content->url);
//        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
//        $this->assertEquals(1, $content->creator);
//        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $content = $root->getNode('cmf/default/contents/test');

        $this->assertEquals('test', $content->getProperty('sulu_locale:de-title')->getString());
        $this->assertEquals('default', $content->getProperty('sulu_locale:de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getPropertyValue('sulu_locale:de-sulu-state'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));
    }

    public function testUpdateUrlTwice()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['url'] = '/news/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, null, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test/test', 'default', 'de');
        $this->assertEquals('Testtitle', $content->title);

        // change simple content
        $data['url'] = '/news/asdf/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/asdf/test/test', 'default', 'de');
        $this->assertEquals('Testtitle', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/asdf/test/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/asdf/test/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('sulu_locale:de-title')->getString());
        $this->assertEquals('default', $content->getProperty('sulu_locale:de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getPropertyValue('sulu_locale:de-sulu-state'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));

        // old resource locator is not a route (has property sulu:content), it is a history (has property sulu:route)
        $oldRoute = $root->getNode('cmf/default/routes/news/test');
        $this->assertTrue($oldRoute->hasProperty('sulu:content'));
        $this->assertTrue($oldRoute->hasProperty('sulu:history'));
        $this->assertTrue($oldRoute->getPropertyValue('sulu:history'));

        // history should reference to new route
        $history = $oldRoute->getPropertyValue('sulu:content');
        $this->assertEquals($route->getIdentifier(), $history->getIdentifier());
    }

    public function testContentTree()
    {
        $data = array(
            array(
                'title' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'asdfasdfasdf'
            ),
            array(
                'title' => 'Testnews-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2/test-1',
                'article' => 'default'
            )
        );

        // save root content
        $root = $this->mapper->save($data[0], 'overview', 'default', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $child = $this->mapper->save($data[2], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $this->mapper->save($data[3], 'overview', 'default', 'de', 1, true, null, $child->getUuid());

        // check nodes
        $content = $this->mapper->loadByResourceLocator('/news', 'default', 'de');
        $this->assertEquals('News', $content->title);
        $this->assertTrue($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-1', 'default', 'de');
        $this->assertEquals('Testnews-1', $content->title);
        $this->assertFalse($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-2', 'default', 'de');
        $this->assertEquals('Testnews-2', $content->title);
        $this->assertTrue($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-2/test-1', 'default', 'de');
        $this->assertEquals('Testnews-2-1', $content->title);
        $this->assertFalse($content->getHasChildren());

        // check content repository
        $root = $this->session->getRootNode();
        $contentRootNode = $root->getNode('cmf/default/contents');

        $newsNode = $contentRootNode->getNode('news');
        $this->assertEquals(2, sizeof($newsNode->getNodes()));
        $this->assertEquals('News', $newsNode->getPropertyValue('sulu_locale:de-title'));

        $testNewsNode = $newsNode->getNode('testnews-1');
        $this->assertEquals('Testnews-1', $testNewsNode->getPropertyValue('sulu_locale:de-title'));

        $testNewsNode = $newsNode->getNode('testnews-2');
        $this->assertEquals(1, sizeof($testNewsNode->getNodes()));
        $this->assertEquals('Testnews-2', $testNewsNode->getPropertyValue('sulu_locale:de-title'));

        $subTestNewsNode = $testNewsNode->getNode('testnews-2-1');
        $this->assertEquals('Testnews-2-1', $subTestNewsNode->getPropertyValue('sulu_locale:de-title'));
    }

    private function prepareTreeTestData()
    {
        $data = array(
            array(
                'title' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'asdfasdfasdf'
            ),
            array(
                'title' => 'Testnews-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2/test-1',
                'article' => 'default'
            )
        );

        $this->mapper->saveStartPage(array('title' => 'Start Page'), 'overview', 'default', 'de', 1);

        // save root content
        $result['root'] = $this->mapper->save($data[0], 'overview', 'default', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, null, $result['root']->getUuid());
        $result['child'] = $this->mapper->save(
            $data[2],
            'overview',
            'default',
            'de',
            1,
            true,
            null,
            $result['root']->getUuid()
        );
        $result['subchild'] = $this->mapper->save(
            $data[3],
            'overview',
            'default',
            'de',
            1,
            true,
            null,
            $result['child']->getUuid()
        );

        return $result;
    }

    public function testLoadByParent()
    {
        $data = $this->prepareTreeTestData();
        /** @var StructureInterface $root */
        $root = $data['root'];
        /** @var StructureInterface $child */
        $child = $data['child'];

        // get root children
        $children = $this->mapper->loadByParent(null, 'default', 'de');
        $this->assertEquals(1, sizeof($children));

        $this->assertEquals('News', $children[0]->title);

        // get children from 'News'
        $rootChildren = $this->mapper->loadByParent($root->getUuid(), 'default', 'de');
        $this->assertEquals(2, sizeof($rootChildren));

        $this->assertEquals('Testnews-1', $rootChildren[0]->title);
        $this->assertEquals('Testnews-2', $rootChildren[1]->title);

        $testNewsChildren = $this->mapper->loadByParent($child->getUuid(), 'default', 'de');
        $this->assertEquals(1, sizeof($testNewsChildren));

        $this->assertEquals('Testnews-2-1', $testNewsChildren[0]->title);

        $nodes = $this->mapper->loadByParent($root->getUuid(), 'default', 'de', null);
        $this->assertEquals(3, sizeof($nodes));
    }

    public function testLoadByParentFlat()
    {
        $data = $this->prepareTreeTestData();
        /** @var StructureInterface $root */
        $root = $data['root'];
        /** @var StructureInterface $child */
        $child = $data['child'];

        $children = $this->mapper->loadByParent(null, 'default', 'de', 2, true);
        $this->assertEquals(3, sizeof($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('Testnews-1', $children[1]->title);
        $this->assertEquals('Testnews-2', $children[2]->title);


        $children = $this->mapper->loadByParent(null, 'default', 'de', 3, true);
        $this->assertEquals(4, sizeof($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('Testnews-1', $children[1]->title);
        $this->assertEquals('Testnews-2', $children[2]->title);
        $this->assertEquals('Testnews-2-1', $children[3]->title);

        $children = $this->mapper->loadByParent($child->getUuid(), 'default', 'de', 3, true);
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('Testnews-2-1', $children[0]->title);
    }

    public function testLoadByParentTree()
    {
        $data = $this->prepareTreeTestData();
        /** @var StructureInterface $root */
        $root = $data['root'];
        /** @var StructureInterface $child */
        $child = $data['child'];

        $children = $this->mapper->loadByParent(null, 'default', 'de', 2, false);
        // /News
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('News', $children[0]->title);

        // /News/Testnews-1
        $tmp = $children[0]->getChildren()[0];
        $this->assertEquals(0, sizeof($tmp->getChildren()));
        $this->assertEquals('Testnews-1', $tmp->title);

        // /News/Testnews-2
        $tmp = $children[0]->getChildren()[1];
        $this->assertEquals(null, $tmp->getChildren());
        $this->assertTrue($tmp->getHasChildren());
        $this->assertEquals('Testnews-2', $tmp->title);


        $children = $this->mapper->loadByParent(null, 'default', 'de', 3, false);
        // /News
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('News', $children[0]->title);

        // /News/Testnews-1
        $tmp = $children[0]->getChildren()[0];
        $this->assertEquals(0, sizeof($tmp->getChildren()));
        $this->assertEquals('Testnews-1', $tmp->title);

        // /News/Testnews-2
        $tmp = $children[0]->getChildren()[1];
        $this->assertEquals(1, sizeof($tmp->getChildren()));
        $this->assertEquals('Testnews-2', $tmp->title);

        // /News/Testnews-2/Testnews-2-1
        $tmp = $children[0]->getChildren()[1]->getChildren()[0];
        $this->assertEquals(null, $tmp->getChildren());
        $this->assertFalse($tmp->getHasChildren());
        $this->assertEquals('Testnews-2-1', $tmp->title);

        $children = $this->mapper->loadByParent($child->getUuid(), 'default', 'de', 3, false);
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('Testnews-2-1', $children[0]->title);
    }

    public function testStartPage()
    {
        $data = array(
            'title' => 'startpage',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/',
            'article' => 'article'
        );

        $this->mapper->saveStartPage($data, 'overview', 'default', 'en', 1, false);

        $startPage = $this->mapper->loadStartPage('default', 'en');
        $this->assertEquals('startpage', $startPage->title);
        $this->assertEquals('/', $startPage->url);

        $data['title'] = 'new-startpage';

        $this->mapper->saveStartPage($data, 'overview', 'default', 'en', 1, false);

        $startPage = $this->mapper->loadStartPage('default', 'en');
        $this->assertEquals('new-startpage', $startPage->title);
        $this->assertEquals('/', $startPage->url);

        $startPage = $this->mapper->loadByResourceLocator('/', 'default', 'en');
        $this->assertEquals('new-startpage', $startPage->title);
        $this->assertEquals('/', $startPage->url);
    }

    public function testDelete()
    {
        $data = array(
            array(
                'title' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'asdfasdfasdf'
            ),
            array(
                'title' => 'Testnews-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2/test-1',
                'article' => 'default'
            )
        );

        // save root content
        $root = $this->mapper->save($data[0], 'overview', 'default', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $child = $this->mapper->save($data[2], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $subChild = $this->mapper->save($data[3], 'overview', 'default', 'de', 1, true, null, $child->getUuid());

        // delete /news/test-2/test-1
        $this->mapper->delete($child->getUuid(), 'default');

        // check
        try {
            $this->mapper->load($child->getUuid(), 'default', 'de');
            $this->assertTrue(false, 'Node should not exists');
        } catch (ItemNotFoundException $ex) {
        }

        try {
            $this->mapper->load($subChild->getUuid(), 'default', 'de');
            $this->assertTrue(false, 'Node should not exists');
        } catch (ItemNotFoundException $ex) {
        }

        $result = $this->mapper->loadByParent($root->getUuid(), 'default', 'de');
        $this->assertEquals(1, sizeof($result));
    }

    public function testCleanUp()
    {
        $data = array(
            'title' => 'ä   ü ö   Ä Ü Ö',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/',
            'article' => 'article'
        );

        $structure = $this->mapper->save($data, 'overview', 'default', 'en', 1);

        $node = $this->session->getNodeByIdentifier($structure->getUuid());

        $this->assertEquals($node->getName(), 'ae-ue-oe-ae-ue-oe');
        $this->assertEquals($node->getPath(), '/cmf/default/contents/ae-ue-oe-ae-ue-oe');
    }

    public function testStateTransition()
    {
        // default state TEST
        $data1 = array(
            'title' => 't1'
        );
        $data1 = $this->mapper->save($data1, 'overview', 'default', 'de', 1);
        $this->assertEquals(StructureInterface::STATE_TEST, $data1->getNodeState());
        $this->assertNull($data1->getPublished());
        $this->assertFalse($data1->getPublishedState());

        // save with state PUBLISHED
        $data2 = array(
            'title' => 't2'
        );
        $data2 = $this->mapper->save($data2, 'overview', 'default', 'de', 1, true, null, null, 2);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $data2->getNodeState());
        $this->assertNotNull($data2->getPublished());
        $this->assertTrue($data2->getPublishedState());

        sleep(1);
        // change state from TEST to PUBLISHED
        $data3 = array(
            'title' => 't1'
        );
        $data3 = $this->mapper->save($data3, 'overview', 'default', 'de', 1, true, $data1->getUuid(), null, 2);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $data3->getNodeState());
        $this->assertNotNull($data3->getPublished());
        $this->assertTrue($data3->getPublishedState());
        $this->assertTrue($data3->getPublished() > $data2->getPublished());

        // change state from PUBLISHED to TEST (exception)
        $data4 = array(
            'title' => 't2'
        );
        $data4 = $this->mapper->save($data4, 'overview', 'default', 'de', 1, true, $data2->getUuid(), null, 1);
        $this->assertEquals(StructureInterface::STATE_TEST, $data4->getNodeState());
        $this->assertNull($data4->getPublished());
        $this->assertFalse($data4->getPublishedState());
    }

    public function testStateInheritance()
    {
        $data = array(
            array(
                'title' => 't1'
            ),
            array(
                'title' => 't1-t1'
            ),
            array(
                'title' => 't1-t1-t1'
            ),
            array(
                'title' => 't1-t2'
            )
        );

        $d1 = $this->mapper->save($data[0], 'overview', 'default', 'de', 1);
        $d2 = $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, null, $d1->getUuid());
        $d3 = $this->mapper->save($data[2], 'overview', 'default', 'de', 1, true, null, $d2->getUuid());
        $d4 = $this->mapper->save($data[3], 'overview', 'default', 'de', 1, true, null, $d1->getUuid());

        // default TEST
        $x1 = $this->mapper->load($d1->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x1->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x1->getNodeState());
        $x2 = $this->mapper->load($d2->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x2->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x2->getNodeState());
        $x3 = $this->mapper->load($d3->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x3->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x3->getNodeState());
        $x4 = $this->mapper->load($d4->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x4->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x4->getNodeState());

        // t1-t1-t1 to PUBLISHED (t1-t1-t1 TEST -> because t1-t1 is TEST -> inheritance)
        $d3 = $this->mapper->save($data[2], 'overview', 'default', 'de', 1, true, $d3->getUuid(), null, 2);

        $x1 = $this->mapper->load($d1->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x1->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x1->getNodeState());
        $x2 = $this->mapper->load($d2->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x2->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x2->getNodeState());
        $x3 = $this->mapper->load($d3->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x3->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $x3->getNodeState());
        $x4 = $this->mapper->load($d4->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x4->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x4->getNodeState());

        // t1-t1 to PUBLISHED (t1-t1-t1 PUBLISHED -> because t1-t1 is PUBLISHED -> inheritance)
        $d2 = $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, $d2->getUuid(), null, 2);

        $x1 = $this->mapper->load($d1->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x1->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x1->getNodeState());
        $x2 = $this->mapper->load($d2->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x2->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $x2->getNodeState());
        $x3 = $this->mapper->load($d3->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x3->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $x3->getNodeState());
        $x4 = $this->mapper->load($d4->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x4->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x4->getNodeState());

        // t1 to PUBLISHED
        $d1 = $this->mapper->save($data[0], 'overview', 'default', 'de', 1, true, $d1->getUuid(), null, 2);

        $x1 = $this->mapper->load($d1->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $x1->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $x1->getNodeState());
        $x2 = $this->mapper->load($d2->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $x2->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $x2->getNodeState());
        $x3 = $this->mapper->load($d3->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $x3->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $x3->getNodeState());
        $x4 = $this->mapper->load($d4->getUuid(), 'default', 'de');
        $this->assertEquals(StructureInterface::STATE_TEST, $x4->getGlobalState());
        $this->assertEquals(StructureInterface::STATE_TEST, $x4->getNodeState());
    }

    public function testShowInNavigation()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $result = $this->mapper->save($data, 'overview', 'default', 'de', 1, true, null, null, null, true);
        $content = $this->mapper->load($result->getUuid(), 'default', 'de');

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/news/test');
        $node = $route->getPropertyValue('sulu:content');

        $this->assertTrue($node->getPropertyValue('sulu_locale:de-sulu-navigation'));
        $this->assertTrue($result->getNavigation());
        $this->assertTrue($content->getNavigation());

        $result = $this->mapper->save(
            $data,
            'overview',
            'default',
            'de',
            1,
            true,
            $result->getUuid(),
            null,
            null,
            false
        );
        $content = $this->mapper->load($result->getUuid(), 'default', 'de');
        $this->assertFalse($result->getNavigation());
        $this->assertFalse($content->getNavigation());

        $result = $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $result->getUuid());
        $content = $this->mapper->load($result->getUuid(), 'default', 'de');
        $this->assertFalse($result->getNavigation());
        $this->assertFalse($content->getNavigation());

        $result = $this->mapper->save(
            $data,
            'overview',
            'default',
            'de',
            1,
            true,
            $result->getUuid(),
            null,
            null,
            true
        );
        $content = $this->mapper->load($result->getUuid(), 'default', 'de');
        $this->assertTrue($result->getNavigation());
        $this->assertTrue($content->getNavigation());
    }

    public function testLoadBySql2()
    {
        $this->prepareTreeTestData();

        $result = $this->mapper->loadBySql2('SELECT * FROM [sulu:content]', 'de', 'default');

        $this->assertEquals(5, sizeof($result));

        $result = $this->mapper->loadBySql2('SELECT * FROM [sulu:content]', 'de', 'default', 2);

        $this->assertEquals(2, sizeof($result));
    }

    public function testSameName()
    {
        $data = array(
            'title' => 'Test',
            'tags' => array('tag1'),
            'url' => '/test-1',
            'article' => 'default'
        );

        $d1 = $this->mapper->save($data, 'overview', 'default', 'de', 1);
        $data['url'] = '/test-2';
        $data['tags'] = array('tag2');
        $d2 = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $this->assertEquals('Test', $d1->title);
        $this->assertEquals(array('tag1'), $d1->tags);
        $this->assertEquals('Test', $d2->title);
        $this->assertEquals(array('tag2'), $d2->tags);

        $this->assertNotNull($this->session->getNode('/cmf/default/contents/test'));
        $this->assertNotNull($this->session->getNode('/cmf/default/contents/test-1'));

        $d1 = $this->mapper->load($d1->getUuid(), 'default', 'de');
        $d2 = $this->mapper->load($d2->getUuid(), 'default', 'de');

        $this->assertEquals('Test', $d1->title);
        $this->assertEquals(array('tag1'), $d1->tags);
        $this->assertEquals('Test', $d2->title);
        $this->assertEquals(array('tag2'), $d2->tags);
    }

    public function testBreadcrumb()
    {
        /** @var StructureInterface[] $data */
        $data = $this->prepareTreeTestData();

        /** @var BreadcrumbItemInterface[] $result */
        $result = $this->mapper->loadBreadcrumb($data['subchild']->getUuid(), 'de', 'default');

        $this->assertEquals(3, sizeof($result));
        $this->assertEquals(0, $result[0]->getDepth());
        $this->assertEquals('Start Page', $result[0]->getTitle());
        $this->assertEquals($this->sessionManager->getContentNode('default')->getIdentifier(), $result[0]->getUuid());

        $this->assertEquals(1, $result[1]->getDepth());
        $this->assertEquals('News', $result[1]->getTitle());
        $this->assertEquals($data['root']->getUuid(), $result[1]->getUuid());

        $this->assertEquals(2, $result[2]->getDepth());
        $this->assertEquals('Testnews-2', $result[2]->getTitle());
        $this->assertEquals($data['child']->getUuid(), $result[2]->getUuid());
    }
}
