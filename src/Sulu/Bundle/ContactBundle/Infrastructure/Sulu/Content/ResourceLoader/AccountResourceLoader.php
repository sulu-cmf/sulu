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

namespace Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Bundle\ContactBundle\Api\Account as AccountApi;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress as AccountAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class AccountResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'account';

    /**
     * @param ContactManagerInterface<AccountInterface, AccountApi, AccountAddressEntity> $accountManager
     */
    public function __construct(
        private ContactManagerInterface $accountManager,
    ) {
    }

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $result = $this->accountManager->getByIds($ids, (string) $locale);

        $mappedResult = [];
        foreach ($result as $object) {
            $mappedResult[$object->getId()] = $object;
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}
