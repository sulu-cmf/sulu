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

namespace Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Exception;

use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;

class ExampleNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $model;

    /**
     * @var mixed[]
     */
    private $filters;

    /**
     * @param mixed[] $filters
     */
    public function __construct(array $filters, int $code = 0, ?\Throwable $previous = null)
    {
        $this->model = Example::class;

        $criteriaMessages = [];
        foreach ($filters as $key => $value) {
            if (\is_object($value)) {
                $value = \get_debug_type($value);
            } else {
                $value = \json_encode($value);
            }

            $criteriaMessages[] = \sprintf('"%s" %s', $key, $value);
        }

        $message = \sprintf(
            'Model "%s" with %s not found',
            $this->model,
            \implode(' and ', $criteriaMessages)
        );

        parent::__construct($message, $code, $previous);

        $this->filters = $filters;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return mixed[]
     */
    public function getCriteria(): array
    {
        return $this->filters;
    }
}
