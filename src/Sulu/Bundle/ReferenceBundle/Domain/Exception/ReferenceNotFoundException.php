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

namespace Sulu\Bundle\ReferenceBundle\Domain\Exception;

class ReferenceNotFoundException extends \Exception
{
    /**
     * @var array<string, mixed>
     */
    private $filters;

    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(array $filters, ?\Throwable $previous = null)
    {
        $this->filters = $filters;

        parent::__construct(
            \sprintf('Reference with filters (%s) not found.', \json_encode($this->filters)),
            0,
            $previous
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
