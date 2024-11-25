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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value;

/**
 * @internal This class is intended for internal use only within the package/library. Modifying or depending on this class may result in unexpected behavior and is not supported.
 */
class ContentView
{
    /**
     * @param mixed[] $view
     */
    private function __construct(
        private mixed $content,
        private array $view
    ) {
    }

    /**
     * @param mixed[] $view
     */
    public static function create(mixed $content, array $view): self
    {
        return new self($content, $view);
    }

    /**
     * @param mixed[] $view
     */
    public static function createResolvable(string|int $id, string $resourceLoaderKey, array $view, ?\Closure $closure = null): self
    {
        return new self(new ResolvableResource($id, $resourceLoaderKey, $closure), $view);
    }

    /**
     * @param array<string|int> $ids
     * @param mixed[] $view
     */
    public static function createResolvables(array $ids, string $resourceLoaderKey, array $view): self
    {
        $resolvableResources = [];

        foreach ($ids as $id) {
            $resolvableResources[] = new ResolvableResource($id, $resourceLoaderKey);
        }

        return new self($resolvableResources, $view);
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * @return mixed[]
     */
    public function getView(): array
    {
        return $this->view;
    }

    public function setContent(mixed $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param mixed[] $view
     */
    public function setView(array $view): self
    {
        $this->view = $view;

        return $this;
    }
}
