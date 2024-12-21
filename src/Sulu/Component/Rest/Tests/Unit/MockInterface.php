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

namespace Sulu\Component\Rest\Tests\Unit;

interface MockInterface
{
    public function getId(): int;

    public function getValue(): int;

    public function delete(): void;

    public function update(): void;

    public function add(): void;

    public function get(): mixed;
}
