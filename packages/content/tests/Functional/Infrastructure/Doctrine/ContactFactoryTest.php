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

namespace Sulu\Content\Tests\Functional\Infrastructure\Doctrine;

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Factory\ContactFactoryInterface;

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

    #[DataProvider('dataProvider')]
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
    public static function dataProvider(): \Generator
    {
        yield [
            null,
        ];

        yield [
            1,
        ];
    }
}
