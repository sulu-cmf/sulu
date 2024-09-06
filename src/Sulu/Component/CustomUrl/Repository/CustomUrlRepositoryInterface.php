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

namespace Sulu\Component\CustomUrl\Repository;

use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * @extends ObjectRepository<CustomUrl>
 */
interface CustomUrlRepositoryInterface extends ObjectRepository
{
    public function findByWebspaceKey(string $webspaceKey): RowsIterator;

    /**
     * @param array<string> $baseDomains
     */
    public function findByWebspaceAndBaseDomains(string $webspaceKey, array $baseDomains = []): RowsIterator;

    /**
     * @return array<CustomUrl>
     */
    public function findByTarget(UuidBehavior $page): array;

    /**
     * @param array<int> $ids
     */
    public function deleteByIds(array $ids): void;

    /**
     * @return array<string>
     */
    public function findPathsByWebspace(string $webspace): array;

    public function findNewestPublishedByUrl(string $url, string $webspace, ?string $locale = null): ?CustomUrl;
}
