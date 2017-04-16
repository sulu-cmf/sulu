<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Category;


use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\CategoryBundle\Category\CategoryManager;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryRepositoryInterface;
use Sulu\Component\Security\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryMangerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var CategoryManagerInterface
     */
    private $categoryManager;

    public function setUp()
    {
        $this->categoryRepository = $this->getMockForAbstractClass(
            'Sulu\Bundle\CategoryBundle\Category\CategoryRepositoryInterface',
            array(),
            '',
            false
        );

        $this->userRepository = $this->getMockForAbstractClass(
            'Sulu\Component\Security\UserRepositoryInterface',
            array(),
            '',
            false
        );

        $this->em = $this->getMockForAbstractClass(
            'Doctrine\Common\Persistence\ObjectManager',
            array(),
            '',
            false
        );

        $this->eventDispatcher = $this->getMockForAbstractClass(
            'Symfony\Component\EventDispatcher\EventDispatcherInterface',
            array(),
            '',
            false
        );

        $this->categoryManager = new CategoryManager(
            $this->categoryRepository,
            $this->userRepository,
            $this->em,
            $this->eventDispatcher
        );
    }

    public function testGetApiObject()
    {
        $entity = new CategoryEntity();
        $wrapper = $this->categoryManager->getApiObject($entity, 'en');

        $this->assertTrue($wrapper instanceOf CategoryWrapper);

        $wrapper = $this->categoryManager->getApiObject(null, 'de');

        $this->assertEquals(null, $wrapper);
    }

    public function testGetApiObjects()
    {
        $entities = [
            new CategoryEntity(),
            null,
            new CategoryEntity(),
            new CategoryEntity(),
            null
        ];

        $wrappers = $this->categoryManager->getApiObjects($entities, 'en');

        $this->assertTrue($wrappers[0] instanceof CategoryWrapper);
        $this->assertTrue($wrappers[2] instanceof CategoryWrapper);
        $this->assertTrue($wrappers[3] instanceof CategoryWrapper);
        $this->assertEquals(null, $wrappers[1]);
        $this->assertEquals(null, $wrappers[4]);
    }
}
