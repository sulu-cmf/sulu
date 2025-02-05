<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\UserInterface\Controller\Admin;

use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Snippet\Application\Message\ApplyWorkflowTransitionSnippetMessage;
use Sulu\Snippet\Application\Message\CopyLocaleSnippetMessage;
use Sulu\Snippet\Application\Message\CreateSnippetMessage;
use Sulu\Snippet\Application\Message\ModifySnippetMessage;
use Sulu\Snippet\Application\Message\RemoveSnippetMessage;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal this class should not be instated by a project
 *           Use instead a request or response listener to
 *           extend the endpoints behaviours
 */
final class SnippetController
{
    use HandleTrait;

    /**
     * @var SnippetRepositoryInterface
     */
    private $snippetRepository;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var ContentManagerInterface
     */
    private $contentManager;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $listBuilderFactory;

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    public function __construct(
        SnippetRepositoryInterface $snippetRepository,
        MessageBusInterface $messageBus,
        NormalizerInterface $normalizer,
        ContentManagerInterface $contentManager,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        RestHelperInterface $restHelper
    ) {
        $this->snippetRepository = $snippetRepository;
        $this->messageBus = $messageBus;
        $this->normalizer = $normalizer;

        // TODO controller should not need more then Repository, MessageBus, Serializer
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->listBuilderFactory = $listBuilderFactory;
        $this->restHelper = $restHelper;
        $this->contentManager = $contentManager;
    }

    public function cgetAction(Request $request): Response
    {
        // TODO this should be SnippetRepository::findFlatBy / ::countFlatBy methods
        //      but first we would need to avoid that the restHelper requires the request.
        //
        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(SnippetInterface::RESOURCE_KEY);
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(SnippetInterface::class);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['ghostLocale']);
        $listBuilder->setParameter('locale', $request->query->get('locale'));
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            SnippetInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse($this->normalizer->normalize(
            $listRepresentation->toArray(), // TODO maybe a listener should automatically do that for `sulu_admin` context
            'json',
            ['sulu_admin' => true, 'sulu_admin_snippet' => true, 'sulu_admin_snippet_list' => true],
        ));
    }

    public function getAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $dimensionAttributes = [
            'locale' => $request->query->getString('locale', $request->getLocale()),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        $snippet = $this->snippetRepository->getOneBy(
            \array_merge(
                [
                    'uuid' => $id,
                    'loadGhost' => true,
                ],
                $dimensionAttributes,
            ),
            [
                SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_ADMIN => true,
            ]
        );

        // TODO the `$snippet` should just be serialized
        //      Instead of calling the content resolver service which triggers an additional query.
        $dimensionContent = $this->contentManager->resolve($snippet, $dimensionAttributes);
        $normalizedContent = $this->contentManager->normalize($dimensionContent);

        return new JsonResponse($this->normalizer->normalize(
            $normalizedContent, // TODO this should just be the snippet entity see comment above
            'json',
            ['sulu_admin' => true, 'sulu_admin_snippet' => true, 'sulu_admin_snippet_content' => true],
        ));
    }

    public function postAction(Request $request): Response
    {
        $message = new CreateSnippetMessage($this->getData($request));

        /** @see Sulu\Snippet\Application\MessageHandler\CreateSnippetMessageHandler */
        /** @var SnippetInterface $snippet */
        $snippet = $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        $uuid = $snippet->getUuid();

        $this->handleAction($request, $uuid);

        $response = $this->getAction($request, $uuid);

        return $response->setStatusCode(201);
    }

    public function putAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $message = new ModifySnippetMessage(['uuid' => $id], $this->getData($request));
        /** @see Sulu\Snippet\Application\MessageHandler\ModifySnippetMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        $this->handleAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function postTriggerAction(Request $request, string $id): Response
    {
        $this->handleAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function deleteAction(Request $request, string $id): Response // TODO route should be a uuid
    {
        $message = new RemoveSnippetMessage(['uuid' => $id]);
        /** @see Sulu\Snippet\Application\MessageHandler\RemoveSnippetMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new Response('', 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function getData(Request $request): array
    {
        return \array_replace(
            $request->request->all(),
            [
                'locale' => $this->getLocale($request),
            ]
        );
    }

    private function getLocale(Request $request): string
    {
        return $request->query->getAlnum('locale', $request->getLocale());
    }

    private function handleAction(Request $request, string $uuid): ?SnippetInterface // @phpstan-ignore-line
    {
        $action = $request->query->get('action');

        if (!$action || 'draft' === $action) {
            return null;
        }

        if ('copy-locale' === $action) {
            $message = new CopyLocaleSnippetMessage(
                ['uuid' => $uuid],
                (string) $request->query->get('src'),
                (string) $request->query->get('dest')
            );

            /** @see Sulu\Snippet\Application\MessageHandler\CopyLocaleSnippetMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } else {
            $message = new ApplyWorkflowTransitionSnippetMessage(['uuid' => $uuid], $this->getLocale($request), $action);

            /** @see Sulu\Snippet\Application\MessageHandler\ApplyWorkflowTransitionSnippetMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }
    }
}
