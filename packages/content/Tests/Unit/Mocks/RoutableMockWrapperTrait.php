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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Mocks;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;

/**
 * Trait for composing a class that wraps a RoutableInterface mock.
 *
 * @see MockWrapper to learn why this trait is needed.
 *
 * @property RoutableInterface $instance
 */
trait RoutableMockWrapperTrait
{
    public static function getResourceKey(): string
    {
        return 'mock-resource-key';
    }

    public function getResourceId()
    {
        return $this->instance->getResourceId();
    }

    public function getLocale(): ?string
    {
        return $this->instance->getLocale();
    }
}
