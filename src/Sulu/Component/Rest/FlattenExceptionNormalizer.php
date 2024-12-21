<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use Sulu\Component\Rest\Exception\ReferencingResourcesFoundExceptionInterface;
use Sulu\Component\Rest\Exception\RemoveDependantResourcesFoundExceptionInterface;
use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal the following class is only for internal use don't use it in your project
 */
class FlattenExceptionNormalizer implements ContextAwareNormalizerInterface
{
    public function __construct(private NormalizerInterface $decoratedNormalizer, private TranslatorInterface $translator)
    {
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            FlattenException::class => false,
        ];
    }

    /**
     * @param mixed $exception
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return array<int|string, mixed>
     */
    public function normalize($exception, $format = null, array $context = []): array
    {
        /** @var array<int|string, mixed> $data */
        $data = $this->decoratedNormalizer->normalize($exception, $format, $context);
        $data['code'] = $exception->getCode();

        $contextException = $context['exception'] ?? null;
        if ($contextException instanceof TranslationErrorMessageExceptionInterface) {
            // set error message to detail property of response to match rfc 7807
            $data['detail'] = $this->translator->trans(
                $contextException->getMessageTranslationKey(),
                $contextException->getMessageTranslationParameters(),
                'admin'
            );
        }

        if ($context['debug'] ?? false) {
            if ($exception instanceof FlattenException) {
                $errors = $exception->getAsString();
            } else {
                $errors = (string) $exception;
            }

            $data['errors'] = [$errors];
        }

        if ($contextException instanceof RemoveDependantResourcesFoundExceptionInterface) {
            $data['title'] = $this->translator->trans(
                $contextException->getTitleTranslationKey(),
                $contextException->getTitleTranslationParameters(),
                'admin'
            );
            $data['detail'] = $this->translator->trans(
                $contextException->getDetailTranslationKey(),
                $contextException->getDetailTranslationParameters(),
                'admin'
            );
            $data['dependantResourcesCount'] = $contextException->getDependantResourcesCount();
            $data['dependantResourceBatches'] = $contextException->getDependantResourceBatches();
            $data['resource'] = $contextException->getResource();
        }

        if ($contextException instanceof ReferencingResourcesFoundExceptionInterface) {
            $data['referencingResourcesCount'] = $contextException->getReferencingResourcesCount();
            $data['referencingResources'] = $contextException->getReferencingResources();
            $data['resource'] = $contextException->getResource();
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FlattenException && !($context['messenger_serialization'] ?? false);
    }
}
