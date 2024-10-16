<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit\Infrastructure\Sulu\Content\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Api\Contact as ContactApi;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\ContactResourceLoader;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;

class ContactResourceLoaderTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<ContactManager>
     */
    private ObjectProphecy $contactManager;

    private ContactResourceLoader $loader;

    public function setUp(): void
    {
        $this->contactManager = $this->prophesize(ContactManager::class);
        $this->loader = new ContactResourceLoader($this->contactManager->reveal());
    }

    public function testGetKey(): void
    {
        $this->assertSame('contact', $this->loader::getKey());
    }

    public function testLoad(): void
    {
        $contact1 = $this->createContact(1);
        $contact2 = $this->createContact(3);

        $this->contactManager->getByIds([1, 3], 'en')->willReturn([
            $contact1,
            $contact2,
        ])
            ->shouldBeCalled();

        $result = $this->loader->load([1, 3], 'en', []);

        $this->assertSame([
            1 => $contact1,
            3 => $contact2,
        ], $result);
    }

    private static function createContact(int $id): ContactApi
    {
        $contact = new Contact();
        static::setPrivateProperty($contact, 'id', $id);

        return new ContactApi($contact, 'en');
    }
}
