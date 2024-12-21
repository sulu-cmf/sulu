<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Application\Collector;

use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;

class ReferenceCollector implements ReferenceCollectorInterface
{
    /**
     * @var ArrayCollection<int, ReferenceInterface>
     */
    private $referenceCollection;

    /**
     * @var ReferenceRepositoryInterface
     */
    private $referenceRepository;

    /**
     * @var string
     */
    private $referenceResourceId;

    /**
     * @var string
     */
    private $referenceResourceKey;

    /**
     * @var array<string, string>
     */
    private $referenceRouterAttributes;

    /**
     * @var string
     */
    private $referenceTitle;

    /**
     * @var string
     */
    private $referenceLocale;

    /**
     * @var string
     */
    private $referenceContext;

    /**
     * @param array<string, string> $referenceRouterAttributes
     */
    public function __construct(
        ReferenceRepositoryInterface $referenceRepository,
        string $referenceResourceKey,
        string $referenceResourceId,
        string $referenceLocale,
        string $referenceTitle,
        string $referenceContext,
        array $referenceRouterAttributes = [],
    ) {
        $this->referenceRepository = $referenceRepository;
        $this->referenceCollection = new ArrayCollection();

        $this->referenceResourceKey = $referenceResourceKey;
        $this->referenceResourceId = $referenceResourceId;
        $this->referenceLocale = $referenceLocale;
        $this->referenceTitle = $referenceTitle;
        $this->referenceContext = $referenceContext;
        $this->referenceRouterAttributes = $referenceRouterAttributes;
    }

    public function addReference(
        string $resourceKey,
        string $resourceId,
        string $referenceProperty,
    ): ReferenceInterface {
        $reference = $this->referenceRepository->create(
            $resourceKey,
            $resourceId,
            $this->referenceResourceKey,
            $this->referenceResourceId,
            $this->referenceLocale,
            $this->referenceTitle,
            $this->referenceContext,
            $referenceProperty,
            $this->referenceRouterAttributes,
        );

        $reference = $this->getReference($reference) ?? $reference;

        $this->referenceCollection->add($reference);

        return $reference;
    }

    public function persistReferences(): void
    {
        $this->referenceRepository->removeBy([
            'referenceResourceKey' => $this->referenceResourceKey,
            'referenceResourceId' => $this->referenceResourceId,
            'referenceLocale' => $this->referenceLocale,
            'referenceContext' => $this->referenceContext,
        ]);

        foreach ($this->referenceCollection as $reference) {
            $this->referenceRepository->add($reference);
        }

        $this->referenceCollection->clear();
    }

    private function getReference(ReferenceInterface $reference): ?ReferenceInterface
    {
        return $this->referenceCollection->filter(fn (ReferenceInterface $ref) => $ref->equals($reference))->first() ?: null;
    }
}
