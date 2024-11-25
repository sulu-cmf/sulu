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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Bundle\ContentBundle\Content\Domain\Factory\ContactFactoryInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\AuthorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class AuthorDataMapper implements DataMapperInterface
{
    /**
     * @var ContactFactoryInterface
     */
    private $contactFactory;

    public function __construct(ContactFactoryInterface $contactFactory)
    {
        $this->contactFactory = $contactFactory;
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof AuthorInterface) {
            return;
        }

        $this->setAuthorData($localizedDimensionContent, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function setAuthorData(AuthorInterface $dimensionContent, array $data): void
    {
        if (\array_key_exists('author', $data)) {
            $dimensionContent->setAuthor(
                $data['author']
                    ? $this->contactFactory->create($data['author'])
                    : null
            );
        }

        if (\array_key_exists('authored', $data)) {
            $dimensionContent->setAuthored(
                $data['authored']
                    ? new \DateTimeImmutable($data['authored'])
                    : null
            );
        }
    }
}
