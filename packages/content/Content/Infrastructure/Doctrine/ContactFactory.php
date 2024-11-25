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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Factory\ContactFactoryInterface;

class ContactFactory implements ContactFactoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(?int $contactId): ?ContactInterface
    {
        if (!$contactId) {
            return null;
        }

        /** @var ContactInterface|null $contact */
        $contact = $this->entityManager->getPartialReference(
            ContactInterface::class,
            $contactId
        );

        return $contact;
    }
}
