<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Domain\Exception;

use Sulu\Page\Domain\Model\PageInterface;

class PageNotFoundException extends \Exception
{
    private string $model;

    /**
     * @var array<string, mixed>
     */
    private array $filters;

    public function __construct(array $filters, int $code = 0, ?\Throwable $previous = null)
    {
        $this->model = PageInterface::class;

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

    public function getFilters(): array
    {
        return $this->filters;
    }
}
