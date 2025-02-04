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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Content\Domain\Factory\ContactFactoryInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Webmozart\Assert\Assert;

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
     * @param array<string, mixed> $data
     */
    private function setAuthorData(AuthorInterface $dimensionContent, array $data): void
    {
        if (\array_key_exists('author', $data)) {
            Assert::nullOrInteger($data['author']);
            $dimensionContent->setAuthor(
                $data['author']
                    ? $this->contactFactory->create($data['author'])
                    : null
            );
        }

        if (\array_key_exists('lastModified', $data)) {
            Assert::nullOrString($data['lastModified']);
            $dimensionContent->setLastModified(
                $data['lastModified'] && (\array_key_exists('lastModifiedEnabled', $data) && $data['lastModifiedEnabled'])
                    ? new \DateTimeImmutable($data['lastModified'])
                    : null
            );
        }

        if (\array_key_exists('authored', $data)) {
            Assert::nullOrString($data['authored']);
            $dimensionContent->setAuthored(
                $data['authored']
                    ? new \DateTimeImmutable($data['authored'])
                    : null
            );
        }
    }
}
