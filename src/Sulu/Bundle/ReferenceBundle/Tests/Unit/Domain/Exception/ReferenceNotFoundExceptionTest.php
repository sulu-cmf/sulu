<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Unit\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ReferenceBundle\Domain\Exception\ReferenceNotFoundException;

class ReferenceNotFoundExceptionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $exception = $this->createReferenceNotFoundExceptionTest([
            'referenceKey' => 'media',
            'referenceId' => '1',
        ]);

        $this->assertSame(
            [
                'referenceKey' => 'media',
                'referenceId' => '1',
            ],
            $exception->getFilters()
        );
    }

    public function testGetMessage(): void
    {
        $exception = $this->createReferenceNotFoundExceptionTest([
            'referenceKey' => 'media',
            'referenceId' => '1',
        ]);

        $this->assertSame(
            'Reference with filters ({"referenceKey":"media","referenceId":"1"}) not found.',
            $exception->getMessage()
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function createReferenceNotFoundExceptionTest(array $filters): ReferenceNotFoundException
    {
        return new ReferenceNotFoundException($filters);
    }
}
