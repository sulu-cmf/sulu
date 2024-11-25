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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer;

use Sulu\Bundle\ContentBundle\Content\Application\ContentNormalizer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;
use Symfony\Component\Serializer\Serializer;

class ContentNormalizer implements ContentNormalizerInterface
{
    /**
     * @var iterable<NormalizerInterface>
     */
    private $normalizers;

    /**
     * @var SymfonyNormalizerInterface
     */
    private $serializer;

    /**
     * @param iterable<NormalizerInterface> $normalizers
     */
    public function __construct(
        iterable $normalizers,
        ?SymfonyNormalizerInterface $serializer = null
    ) {
        $this->normalizers = $normalizers;
        $this->serializer = $serializer ?: $this->createSerializer();
    }

    public function normalize(object $object): array
    {
        $ignoredAttributes = [];

        foreach ($this->normalizers as $normalizer) {
            $ignoredAttributes = \array_merge(
                $ignoredAttributes,
                $normalizer->getIgnoredAttributes($object)
            );
        }

        /** @var mixed[] $normalizedData */
        $normalizedData = $this->serializer->normalize($object, null, [
            'ignored_attributes' => $ignoredAttributes,
        ]);

        foreach ($this->normalizers as $normalizer) {
            $normalizedData = $normalizer->enhance($object, $normalizedData);
        }

        \ksort($normalizedData);

        return $normalizedData;
    }

    private function createSerializer(): SymfonyNormalizerInterface
    {
        $normalizers = [new DateTimeNormalizer(), new GetSetMethodNormalizer()];

        return new Serializer($normalizers);
    }
}
