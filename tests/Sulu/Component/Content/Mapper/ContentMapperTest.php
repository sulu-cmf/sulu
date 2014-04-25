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

use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\Util\NodeHelper;
use ReflectionMethod;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\BreadcrumbItemInterface;
use Sulu\Component\Content\ContentEvents;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Webspace;

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
        } elseif ($structureKey == 'default') {
            return $this->getStructureMock(2);
        } elseif ($structureKey == 'complex') {
            return $this->getStructureMock(3);
        } elseif ($structureKey == 'mandatory') {
            return $this->getStructureMock(4);
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
                new Property('name', '', 'text_line', false, true, 1, 1, array(), array(new PropertyTag('sulu.node.name', 10)))
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property('url', '', 'resource_locator', false, true)
            )
        );

        if ($type == 1) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('tags', '', 'text_line', false, true, 2, 10)
                )
            );

            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('article', '', 'text_area', false, true)
                )
            );
        } elseif ($type == 2) {
            // not translated
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('blog', '', 'text_area', false, false)
                )
            );
        } elseif ($type == 3) {
            $blockProperty = new BlockProperty('block1', '', false, true, 2, 10);
            $blockProperty->addChild(new Property('name', '', 'text_line', false, true));
            $blockProperty->addChild(new Property('article', '', 'text_area', false, true));

            $method->invokeArgs(
                $structureMock,
                array(
                    $blockProperty
                )
            );
        } elseif ($type == 4) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('blog', '', 'text_line', true, true)
                )
            );
        }

        return $structureMock;
    }

    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager === null) {
            $webspace = new Webspace();
            $en = new Localization();
            $en->setLanguage('en');
            $en_us = new Localization();
            $en_us->setLanguage('en');
            $en_us->setCountry('us');
            $en_us->setParent($en);
            $en->addChild($en_us);

            $de = new Localization();
            $de->setLanguage('de');
            $de_at = new Localization();
            $de_at->setLanguage('de');
            $de_at->setCountry('at');
            $de_at->setParent($de);
            $de->addChild($de_at);

            $es = new Localization();
            $es->setLanguage('es');

            $webspace->addLocalization($en);
            $webspace->addLocalization($de);
            $webspace->addLocalization($es);

            $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
            $this->webspaceManager->expects($this->any())
                ->method('findWebspaceByKey')
                ->will($this->returnValue($webspace));
        }
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty('sulu_locale:de-name')->getString());
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $content = $this->mapper->load($structure->getUuid(), 'default', 'de');

        $this->assertNotNull($content->getUuid());
        $this->assertEquals('default', $content->getWebspaceKey());
        $this->assertEquals('de', $content->getLanguageCode());
        $this->assertEquals('overview', $content->getKey());
        $this->assertEquals('Testname', $content->name);
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        $contentBefore = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');
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
            'default' => $this->getStructureMock(2)
        );
        $this->prepareMapper();


        /** @var StructureInterface $content */
        $content = $this->mapper->load($contentBefore->getUuid(), 'default', 'de');
        // test values
        $this->assertEquals('Testname', $content->name);
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testname', $content->name);
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
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

        $this->assertEquals('Testname', $content->name);
        $this->assertEquals('thats a new test', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('thats cool', 'tag2', 'tag3'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty('sulu_locale:de-name')->getString());
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
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

        $this->assertEquals('Testname', $content->name);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag2', 'tag3'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty('sulu_locale:de-name')->getString());
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
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

        $this->assertEquals('Testname', $content->name);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag2', 'tag3'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty('sulu_locale:de-name')->getString());
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
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

        $this->assertEquals('Testname', $content->name);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(null, $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty('sulu_locale:de-name')->getString());
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data = array(
            'name' => 'Testname',
            'blog' => 'this is a blog test'
        );

        // update content
        $this->mapper->save($data, 'default', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        // old properties not exists in structure
        $this->assertEquals(false, $content->hasProperty('article'));
        $this->assertEquals(false, $content->hasProperty('tags'));

        // old properties are right
        $this->assertEquals('Testname', $content->name);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // new property is set
        $this->assertEquals('this is a blog test', $content->blog);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');
        $content = $route->getPropertyValue('sulu:content');

        // old properties exists in node
        $this->assertEquals('default', $content->getPropertyValue('sulu_locale:de-article'));
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('sulu_locale:de-tags'));

        // property of new structure exists
        $this->assertEquals('Testname', $content->getProperty('sulu_locale:de-name')->getString());
        $this->assertEquals('this is a blog test', $content->getPropertyValue('blog'));
        $this->assertEquals('default', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));
    }

    public function testUpdateURL()
    {
        $data = array(
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['url'] = '/news/test/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test/test/test', 'default', 'de');

        $this->assertEquals('Testname', $content->name);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test/test/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test/test/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty('sulu_locale:de-name')->getString());
        $this->assertEquals('default', $content->getProperty('sulu_locale:de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));

        // old resource locator is not a route (has property sulu:content), it is a history (has property sulu:route)
        $oldRoute = $root->getNode('cmf/default/routes/de/news/test');
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['name'] = 'test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // TODO works after this issue is fixed? but its not necessary
//        // check read
//        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');
//
//        $this->assertEquals('default', $content->name);
//        $this->assertEquals('default', $content->article);
//        $this->assertEquals('/news/test', $content->url);
//        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
//        $this->assertEquals(1, $content->creator);
//        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $content = $root->getNode('cmf/default/contents/test');

        $this->assertEquals('test', $content->getProperty('sulu_locale:de-name')->getString());
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['url'] = '/news/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, null, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test/test', 'default', 'de');
        $this->assertEquals('Testname', $content->name);

        // change simple content
        $data['url'] = '/news/asdf/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/asdf/test/test', 'default', 'de');
        $this->assertEquals('Testname', $content->name);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/asdf/test/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/asdf/test/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty('sulu_locale:de-name')->getString());
        $this->assertEquals('default', $content->getProperty('sulu_locale:de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue('sulu_locale:de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue('sulu_locale:de-sulu-template'));
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getPropertyValue('sulu_locale:de-sulu-state'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-creator'));
        $this->assertEquals(1, $content->getPropertyValue('sulu_locale:de-sulu-changer'));

        // old resource locator is not a route (has property sulu:content), it is a history (has property sulu:route)
        $oldRoute = $root->getNode('cmf/default/routes/de/news/test');
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
                'name' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'asdfasdfasdf'
            ),
            array(
                'name' => 'Testnews-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'default'
            ),
            array(
                'name' => 'Testnews-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2',
                'article' => 'default'
            ),
            array(
                'name' => 'Testnews-2-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2/test-1',
                'article' => 'default'
            )
        );

        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        // save root content
        $root = $this->mapper->save($data[0], 'overview', 'default', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $child = $this->mapper->save($data[2], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $this->mapper->save($data[3], 'overview', 'default', 'de', 1, true, null, $child->getUuid());

        // check nodes
        $content = $this->mapper->loadByResourceLocator('/news', 'default', 'de');
        $this->assertEquals('News', $content->name);
        $this->assertTrue($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-1', 'default', 'de');
        $this->assertEquals('Testnews-1', $content->name);
        $this->assertFalse($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-2', 'default', 'de');
        $this->assertEquals('Testnews-2', $content->name);
        $this->assertTrue($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-2/test-1', 'default', 'de');
        $this->assertEquals('Testnews-2-1', $content->name);
        $this->assertFalse($content->getHasChildren());

        // check content repository
        $root = $this->session->getRootNode();
        $contentRootNode = $root->getNode('cmf/default/contents');

        $newsNode = $contentRootNode->getNode('news');
        $this->assertEquals(2, sizeof($newsNode->getNodes()));
        $this->assertEquals('News', $newsNode->getPropertyValue('sulu_locale:de-name'));

        $testNewsNode = $newsNode->getNode('testnews-1');
        $this->assertEquals('Testnews-1', $testNewsNode->getPropertyValue('sulu_locale:de-name'));

        $testNewsNode = $newsNode->getNode('testnews-2');
        $this->assertEquals(1, sizeof($testNewsNode->getNodes()));
        $this->assertEquals('Testnews-2', $testNewsNode->getPropertyValue('sulu_locale:de-name'));

        $subTestNewsNode = $testNewsNode->getNode('testnews-2-1');
        $this->assertEquals('Testnews-2-1', $subTestNewsNode->getPropertyValue('sulu_locale:de-name'));
    }

    private function prepareTreeTestData()
    {
        $data = array(
            array(
                'name' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'asdfasdfasdf'
            ),
            array(
                'name' => 'Testnews-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'default'
            ),
            array(
                'name' => 'Testnews-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2',
                'article' => 'default'
            ),
            array(
                'name' => 'Testnews-2-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2/test-1',
                'article' => 'default'
            )
        );

        $this->eventDispatcher->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        $this->mapper->saveStartPage(array('name' => 'Start Page'), 'overview', 'default', 'de', 1);

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

        $this->assertEquals('News', $children[0]->name);

        // get children from 'News'
        $rootChildren = $this->mapper->loadByParent($root->getUuid(), 'default', 'de');
        $this->assertEquals(2, sizeof($rootChildren));

        $this->assertEquals('Testnews-1', $rootChildren[0]->name);
        $this->assertEquals('Testnews-2', $rootChildren[1]->name);

        $testNewsChildren = $this->mapper->loadByParent($child->getUuid(), 'default', 'de');
        $this->assertEquals(1, sizeof($testNewsChildren));

        $this->assertEquals('Testnews-2-1', $testNewsChildren[0]->name);

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
        $this->assertEquals('News', $children[0]->name);
        $this->assertEquals('Testnews-1', $children[1]->name);
        $this->assertEquals('Testnews-2', $children[2]->name);


        $children = $this->mapper->loadByParent(null, 'default', 'de', 3, true);
        $this->assertEquals(4, sizeof($children));
        $this->assertEquals('News', $children[0]->name);
        $this->assertEquals('Testnews-1', $children[1]->name);
        $this->assertEquals('Testnews-2', $children[2]->name);
        $this->assertEquals('Testnews-2-1', $children[3]->name);

        $children = $this->mapper->loadByParent($child->getUuid(), 'default', 'de', 3, true);
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('Testnews-2-1', $children[0]->name);
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
        $this->assertEquals('News', $children[0]->name);

        // /News/Testnews-1
        $tmp = $children[0]->getChildren()[0];
        $this->assertEquals(0, sizeof($tmp->getChildren()));
        $this->assertEquals('Testnews-1', $tmp->name);

        // /News/Testnews-2
        $tmp = $children[0]->getChildren()[1];
        $this->assertEquals(null, $tmp->getChildren());
        $this->assertTrue($tmp->getHasChildren());
        $this->assertEquals('Testnews-2', $tmp->name);


        $children = $this->mapper->loadByParent(null, 'default', 'de', 3, false);
        // /News
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('News', $children[0]->name);

        // /News/Testnews-1
        $tmp = $children[0]->getChildren()[0];
        $this->assertEquals(0, sizeof($tmp->getChildren()));
        $this->assertEquals('Testnews-1', $tmp->name);

        // /News/Testnews-2
        $tmp = $children[0]->getChildren()[1];
        $this->assertEquals(1, sizeof($tmp->getChildren()));
        $this->assertEquals('Testnews-2', $tmp->name);

        // /News/Testnews-2/Testnews-2-1
        $tmp = $children[0]->getChildren()[1]->getChildren()[0];
        $this->assertEquals(null, $tmp->getChildren());
        $this->assertFalse($tmp->getHasChildren());
        $this->assertEquals('Testnews-2-1', $tmp->name);

        $children = $this->mapper->loadByParent($child->getUuid(), 'default', 'de', 3, false);
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('Testnews-2-1', $children[0]->name);
    }

    public function testStartPage()
    {
        $data = array(
            'name' => 'startpage',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/',
            'article' => 'article'
        );

        $this->mapper->saveStartPage($data, 'overview', 'default', 'en', 1, false);

        $startPage = $this->mapper->loadStartPage('default', 'en');
        $this->assertEquals('startpage', $startPage->name);
        $this->assertEquals('/', $startPage->url);

        $data['name'] = 'new-startpage';

        $this->mapper->saveStartPage($data, 'overview', 'default', 'en', 1, false);

        $startPage = $this->mapper->loadStartPage('default', 'en');
        $this->assertEquals('new-startpage', $startPage->name);
        $this->assertEquals('/', $startPage->url);

        $startPage = $this->mapper->loadByResourceLocator('/', 'default', 'en');
        $this->assertEquals('new-startpage', $startPage->name);
        $this->assertEquals('/', $startPage->url);
    }

    public function testDelete()
    {
        $data = array(
            array(
                'name' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'asdfasdfasdf'
            ),
            array(
                'name' => 'Testnews-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'default'
            ),
            array(
                'name' => 'Testnews-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2',
                'article' => 'default'
            ),
            array(
                'name' => 'Testnews-2-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2/test-1',
                'article' => 'default'
            )
        );

        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
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
            'name' => 'ä   ü ö   Ä Ü Ö',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/',
            'article' => 'article'
        );

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        $structure = $this->mapper->save($data, 'overview', 'default', 'en', 1);

        $node = $this->session->getNodeByIdentifier($structure->getUuid());

        $this->assertEquals($node->getName(), 'ae-ue-oe-ae-ue-oe');
        $this->assertEquals($node->getPath(), '/cmf/default/contents/ae-ue-oe-ae-ue-oe');
    }

    public function testStateTransition()
    {
        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        // default state TEST
        $data1 = array(
            'name' => 't1'
        );
        $data1 = $this->mapper->save($data1, 'overview', 'default', 'de', 1);
        $this->assertEquals(StructureInterface::STATE_TEST, $data1->getNodeState());
        $this->assertNull($data1->getPublished());
        $this->assertFalse($data1->getPublishedState());

        // save with state PUBLISHED
        $data2 = array(
            'name' => 't2'
        );
        $data2 = $this->mapper->save($data2, 'overview', 'default', 'de', 1, true, null, null, 2);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $data2->getNodeState());
        $this->assertNotNull($data2->getPublished());
        $this->assertTrue($data2->getPublishedState());

        sleep(1);
        // change state from TEST to PUBLISHED
        $data3 = array(
            'name' => 't1'
        );
        $data3 = $this->mapper->save($data3, 'overview', 'default', 'de', 1, true, $data1->getUuid(), null, 2);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $data3->getNodeState());
        $this->assertNotNull($data3->getPublished());
        $this->assertTrue($data3->getPublishedState());
        $this->assertTrue($data3->getPublished() > $data2->getPublished());

        // change state from PUBLISHED to TEST (exception)
        $data4 = array(
            'name' => 't2'
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
                'name' => 't1'
            ),
            array(
                'name' => 't1-t1'
            ),
            array(
                'name' => 't1-t1-t1'
            ),
            array(
                'name' => 't1-t2'
            )
        );

        $this->eventDispatcher->expects($this->exactly(7))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
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
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        $result = $this->mapper->save($data, 'overview', 'default', 'de', 1, true, null, null, null, true);
        $content = $this->mapper->load($result->getUuid(), 'default', 'de');

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');
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
            'name' => 'Test',
            'tags' => array('tag1'),
            'url' => '/test-1',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        $d1 = $this->mapper->save($data, 'overview', 'default', 'de', 1);
        $data['url'] = '/test-2';
        $data['tags'] = array('tag2');
        $d2 = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $this->assertEquals('Test', $d1->name);
        $this->assertEquals(array('tag1'), $d1->tags);
        $this->assertEquals('Test', $d2->name);
        $this->assertEquals(array('tag2'), $d2->tags);

        $this->assertNotNull($this->session->getNode('/cmf/default/contents/test'));
        $this->assertNotNull($this->session->getNode('/cmf/default/contents/test-1'));

        $d1 = $this->mapper->load($d1->getUuid(), 'default', 'de');
        $d2 = $this->mapper->load($d2->getUuid(), 'default', 'de');

        $this->assertEquals('Test', $d1->name);
        $this->assertEquals(array('tag1'), $d1->tags);
        $this->assertEquals('Test', $d2->name);
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

    private function prepareGhostTestData()
    {
        $data = array(
            array(
                'name' => 'News-EN',
                'url' => '/news'
            ),
            array(
                'name' => 'News-DE_AT',
                'url' => '/news'
            ),
            array(
                'name' => 'Products-EN',
                'url' => '/products'
            ),
            array(
                'name' => 'Products-DE',
                'url' => '/products'
            ),
            array(
                'name' => 'Team-DE',
                'url' => '/team-de'
            )
        );

        $this->mapper->saveStartPage(array('name' => 'Start Page'), 'overview', 'default', 'de', 1);

        // save root content
        $result['news-en'] = $this->mapper->save($data[0], 'overview', 'default', 'en', 1);
        $result['news-de_at'] = $this->mapper->save(
            $data[1],
            'overview',
            'default',
            'de_at',
            1,
            true,
            $result['news-en']->getUuid()
        );

        $result['products-en'] = $this->mapper->save(
            $data[2],
            'overview',
            'default',
            'en',
            1,
            true
        );

        $result['products-de'] = $this->mapper->save(
            $data[3],
            'overview',
            'default',
            'de',
            1,
            true,
            $result['products-en']->getUuid()
        );

        $result['team-de'] = $this->mapper->save(
            $data[4],
            'overview',
            'default',
            'de',
            1,
            true
        );

        return $result;
    }

    public function testGhost()
    {
        /** @var StructureInterface[] $data */
        $data = $this->prepareGhostTestData();

        // both pages exists in en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'en', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('en', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('name'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('en', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('name'));
        $this->assertNull($result[1]->getType());
        $this->assertEquals('en', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // both pages exists in en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'en', 1, true, false, true);
        $this->assertEquals(2, sizeof($result));
        $this->assertEquals('en', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('name'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('en', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('name'));
        $this->assertNull($result[1]->getType());

        // both pages are ghosts in en_us from en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'en_us', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('en_us', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('en', $result[0]->getType()->getValue());
        $this->assertEquals('en_us', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[1]->getType()->getName());
        $this->assertEquals('en', $result[1]->getType()->getValue());
        $this->assertEquals('en_us', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // no page exists in en_us without ghosts
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'en_us', 1, true, false, true);
        $this->assertEquals(0, sizeof($result));

        // one page not exists in de (ghost from de_at), other exists in de
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'de', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('de', $result[0]->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result[0]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('de_at', $result[0]->getType()->getValue());
        $this->assertEquals('de', $result[1]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[1]->getPropertyValue('name'));
        $this->assertNull($result[1]->getType());
        $this->assertEquals('de', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('name'));
        $this->assertNull($result[2]->getType());

        // one page exists in de (without ghosts)
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'de', 1, true, false, true);
        $this->assertEquals(2, sizeof($result));
        $this->assertEquals('de', $result[0]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[0]->getPropertyValue('name'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('de', $result[1]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[1]->getPropertyValue('name'));
        $this->assertNull($result[1]->getType());

        // one page not exists in de_at (ghost from de), other exists in de_at
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'de', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('de', $result[0]->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result[0]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('de_at', $result[0]->getType()->getValue());
        $this->assertEquals('de', $result[1]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[1]->getPropertyValue('name'));
        $this->assertNull($result[1]->getType());
        $this->assertEquals('de', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('name'));
        $this->assertNull($result[2]->getType());

        // one page not exists in de_at (ghost from de), other exists in de_at
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'de_at', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('de_at', $result[0]->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result[0]->getPropertyValue('name'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('de_at', $result[1]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[1]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[1]->getType()->getName());
        $this->assertEquals('de', $result[1]->getType()->getValue());
        $this->assertEquals('de_at', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // both pages are ghosts in es from en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'es', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('es', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('en', $result[0]->getType()->getValue());
        $this->assertEquals('es', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[1]->getType()->getName());
        $this->assertEquals('en', $result[1]->getType()->getValue());
        $this->assertEquals('es', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('name'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // no page exists in en_us without ghosts
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'es', 1, true, false, true);
        $this->assertEquals(0, sizeof($result));

        // load content as de -> no ghost content
        $result = $this->mapper->load($data['news-de_at']->getUuid(), 'default', 'de', false);
        $this->assertEquals('de', $result->getLanguageCode());
        $this->assertEquals('', $result->getPropertyValue('name'));
        $this->assertNull($result->getType());

        // load content as de -> load ghost content
        $result = $this->mapper->load($data['news-de_at']->getUuid(), 'default', 'de', true);
        $this->assertEquals('de', $result->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result->getPropertyValue('name'));
        $this->assertEquals('ghost', $result->getType()->getName());
        $this->assertEquals('de_at', $result->getType()->getValue());

        // load only in german available page in english
        $result = $this->mapper->load($data['team-de']->getUuid(), 'default', 'en', true);
        $this->assertEquals('en', $result->getLanguageCode());
        $this->assertEquals('Team-DE', $result->getPropertyValue('name'));
        $this->assertEquals('ghost', $result->getType()->getName());
        $this->assertEquals('de', $result->getType()->getValue());
    }

    public function testTranslatedResourceLocator()
    {
        $data = array(
            'name' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );
        $structure = $this->mapper->save($data, 'overview', 'default', 'en', 1);
        $content = $this->mapper->load($structure->getUuid(), 'default', 'en');
        $contentDE = $this->mapper->load($structure->getUuid(), 'default', 'de');
        $nodeEN = $this->session->getNode('/cmf/default/routes/en/news/test');
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals('', $contentDE->url);
        $this->assertNotNull($nodeEN);
        $this->assertFalse($nodeEN->getPropertyValue('sulu:history'));
        $this->assertFalse($this->session->getNode('/cmf/default/routes/de')->hasNode('news/test'));
        $this->assertNotNull($this->languageRoutes['en']->getNode('news/test'));

        $data = array(
            'name' => 'Testname',
            'url' => '/neuigkeiten/test'
        );
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());
        $content = $this->mapper->load($structure->getUuid(), 'default', 'de');
        $contentEN = $this->mapper->load($structure->getUuid(), 'default', 'en');
        $nodeDE = $this->session->getNode('/cmf/default/routes/de/neuigkeiten/test');
        $this->assertEquals('/neuigkeiten/test', $content->url);
        $this->assertEquals('/news/test', $contentEN->url);
        $this->assertNotNull($nodeDE);
        $this->assertFalse($nodeDE->getPropertyValue('sulu:history'));
        $this->assertTrue($this->session->getNode('/cmf/default/routes/de')->hasNode('neuigkeiten/test'));
        $this->assertFalse($this->session->getNode('/cmf/default/routes/de')->hasNode('news/test'));
        $this->assertFalse($this->session->getNode('/cmf/default/routes/en')->hasNode('neuigkeiten/test'));
        $this->assertTrue($this->session->getNode('/cmf/default/routes/en')->hasNode('news/test'));
        $this->assertNotNull($this->languageRoutes['de']->getNode('neuigkeiten/test'));
    }

    public function testBlock()
    {
        $data = array(
            'name' => 'Test-name',
            'url' => '/test',
            'block1' => array(
                array(
                    'name' => 'Block-name-1',
                    'article' => 'Block-Article-1'
                ),
                array(
                    'name' => 'Block-name-2',
                    'article' => 'Block-Article-2'
                )
            )
        );

        // check save
        $structure = $this->mapper->save($data, 'complex', 'default', 'de', 1);
        $result = $structure->toArray();
        $this->assertEquals(
            $data,
            array(
                'name' => $result['name'],
                'url' => $result['url'],
                'block1' => $result['block1']
            )
        );

        // change sorting
        $tmp = $data['block1'][0];
        $data['block1'][0] = $data['block1'][1];
        $data['block1'][1] = $tmp;
        $structure = $this->mapper->save($data, 'complex', 'default', 'de', 1, true, $structure->getUuid());
        $result = $structure->toArray();
        $this->assertEquals(
            $data,
            array(
                'name' => $result['name'],
                'url' => $result['url'],
                'block1' => $result['block1']
            )
        );

        // check load
        $structure = $this->mapper->load($structure->getUuid(), 'default', 'de');
        $result = $structure->toArray();
        $this->assertEquals(
            $data,
            array(
                'name' => $result['name'],
                'url' => $result['url'],
                'block1' => $result['block1']
            )
        );
    }

    public function testMultilingual()
    {
        // change simple content
        $dataDe = array(
            'name' => 'Testname-DE',
            'blog' => 'German',
            'url' => '/news/test'
        );

        // update content
        $structureDe = $this->mapper->save($dataDe, 'default', 'default', 'de', 1);

        $dataEn = array(
            'name' => 'Testname-EN',
            'blog' => 'English'
        );
        $structureEn = $this->mapper->save($dataEn, 'default', 'default', 'en', 1, true, $structureDe->getUuid());
        $structureDe = $this->mapper->load($structureDe->getUuid(), 'default', 'de');

        // check data
        $this->assertNotEquals($structureDe->getPropertyValue('name'), $structureEn->getPropertyValue('name'));
        $this->assertEquals($structureDe->getPropertyValue('blog'), $structureEn->getPropertyValue('blog'));

        $this->assertEquals($dataEn['name'], $structureEn->getPropertyValue('name'));
        $this->assertEquals($dataEn['blog'], $structureEn->getPropertyValue('blog'));

        $this->assertEquals($dataDe['name'], $structureDe->getPropertyValue('name'));
        // En has overritten german content
        $this->assertEquals($dataEn['blog'], $structureDe->getPropertyValue('blog'));

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');
        /** @var NodeInterface $content */
        $content = $route->getPropertyValue('sulu:content');
        $this->assertEquals($dataDe['name'], $content->getPropertyValue('sulu_locale:de-name'));
        $this->assertNotEquals($dataDe['blog'], $content->getPropertyValue('blog'));
        $this->assertEquals($dataEn['name'], $content->getPropertyValue('sulu_locale:en-name'));
        $this->assertEquals($dataEn['blog'], $content->getPropertyValue('blog'));

        $this->assertFalse($content->hasProperty('sulu_locale:de-blog'));
        $this->assertFalse($content->hasProperty('sulu_locale:en-blog'));
        $this->assertFalse($content->hasProperty('name'));
    }

    public function testMandatory()
    {
        $data = array(
            'name' => 'Testname',
            'blog' => 'German',
            'url' => '/news/test'
        );
        $structure = $this->mapper->save($data, 'mandatory', 'default', 'de', 1);

        $this->assertEquals($data['name'], $structure->getPropertyValue('name'));
        $this->assertEquals($data['blog'], $structure->getPropertyValue('blog'));
        $this->assertEquals($data['url'], $structure->getPropertyValue('url'));

        $this->setExpectedException('\Sulu\Component\Content\Exception\MandatoryPropertyException', 'Data for mandatory property blog in template mandatory not found');
        $data = array(
            'name' => 'Testname',
            'url' => '/news/test'
        );
        $this->mapper->save($data, 'mandatory', 'default', 'de', 1);
    }
}
