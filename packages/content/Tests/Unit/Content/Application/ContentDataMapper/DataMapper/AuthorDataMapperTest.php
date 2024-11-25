<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\AuthorDataMapper;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\ContactFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class AuthorDataMapperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ContactFactoryInterface>
     */
    private $contactFactory;

    protected function setUp(): void
    {
        $this->contactFactory = $this->prophesize(ContactFactoryInterface::class);
    }

    protected function createAuthorDataMapperInstance(): AuthorDataMapper
    {
        return new AuthorDataMapper($this->contactFactory->reveal());
    }

    public function testMapNoAuthorInterface(): void
    {
        $data = [
            'author' => 1,
            'authored' => '2020-05-08T00:00:00+00:00',
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $contactFactory = $this->prophesize(ContactFactoryInterface::class);
        $contactFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $authorMapper = $this->createAuthorDataMapperInstance();
        $authorMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
    }

    public function testMapAuthorNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $this->contactFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $authorMapper = $this->createAuthorDataMapperInstance();
        $authorMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getAuthor());
        $this->assertNull($localizedDimensionContent->getAuthored());
    }

    public function testMapData(): void
    {
        $data = [
            'author' => 1,
            'authored' => '2020-05-08T00:00:00+00:00',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $contact = new Contact();
        $this->contactFactory->create(1)
            ->shouldBeCalled()
            ->willReturn($contact);

        $authorMapper = $this->createAuthorDataMapperInstance();
        $authorMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame($contact, $localizedDimensionContent->getAuthor());
        $authored = $localizedDimensionContent->getAuthored();
        $this->assertNotNull($authored);
        $this->assertSame('2020-05-08T00:00:00+00:00', $authored->format('c'));
    }

    public function testMapDataNull(): void
    {
        $data = [
            'author' => null,
            'authored' => null,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setAuthor(new Contact());
        $localizedDimensionContent->setAuthored(new \DateTimeImmutable());

        $this->contactFactory->create(Argument::cetera())
            ->shouldNotBeCalled();

        $authorMapper = $this->createAuthorDataMapperInstance();
        $authorMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getAuthor());
        $this->assertNull($localizedDimensionContent->getAuthored());
    }
}
