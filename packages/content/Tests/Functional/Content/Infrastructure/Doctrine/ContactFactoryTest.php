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

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Content\Infrastructure\Doctrine;

use Sulu\Bundle\ContentBundle\Content\Domain\Factory\ContactFactoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ContactFactoryTest extends SuluTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();
    }

    public function createContactFactory(): ContactFactoryInterface
    {
        return self::getContainer()->get('sulu_content.contact_factory');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testCreate(?int $contactId): void
    {
        $contactFactory = $this->createContactFactory();

        $result = $contactFactory->create($contactId);
        $this->assertSame(
            $contactId,
            $result ? $result->getId() : $result
        );
    }

    /**
     * @return \Generator<mixed[]>
     */
    public function dataProvider(): \Generator
    {
        yield [
            null,
        ];

        yield [
            1,
        ];
    }
}
