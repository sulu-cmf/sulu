<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\UserInterface\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\Message\CopyLocalePageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\Message\RemovePageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Application\MessageHandler\ModifyPageMessageHandler;
use Sulu\Page\Application\MessageHandler\RemovePageMessageHandler;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
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
final class PageController
{
    use HandleTrait;

    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private MessageBusInterface $messageBus,
        private NormalizerInterface $normalizer,
        private ContentManagerInterface $contentManager,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private EntityManagerInterface $entityManager
    ) {
        // TODO controller should not need more then Repository, MessageBus, Serializer
    }

    public function cgetAction(Request $request): Response
    {
        $locale = $request->query->get('locale');
        $parentId = $request->query->get('parentId');
        $expandedIds = \array_filter(\explode(',', $request->query->get('expandedIds')));

        // TODO this should be handled by PageRepository, currently copied from
        //      https://github.com/handcraftedinthealps/SuluResourceBundle
        //      see ListRepresentation/DoctrineNestedListRepresentationFactory.php
        $representation = $this->createDoctrineListRepresentation(PageInterface::RESOURCE_KEY,
            parameters: ['locale' => $locale],
            parentId: $parentId,
            expandedIds: $expandedIds,
            includedFields: ['locale', 'ghostLocale', 'webspaceKey', 'template']
        );

        return new JsonResponse($this->normalizer->normalize(
            $representation->toArray(), // TODO maybe a listener should automatically do that for `sulu_admin` context
            'json',
            ['sulu_admin' => true, 'sulu_admin_page' => true, 'sulu_admin_page_list' => true],
        ));
    }

    public function getAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $dimensionAttributes = [
            'locale' => $request->query->getString('locale', $request->getLocale()),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        $page = $this->pageRepository->getOneWithContentBy(
            $id,
            $dimensionAttributes,
            [
                PageRepositoryInterface::GROUP_SELECT_CONTEXT_ADMIN => true,
            ]
        );

        // TODO the `$page` should just be serialized
        //      Instead of calling the content resolver service which triggers an additional query.
        $dimensionContent = $this->contentManager->resolve($page, $dimensionAttributes);
        $normalizedContent = $this->contentManager->normalize($dimensionContent);

        // TODO move to a separate Normalizer
        $normalizedContent['id'] = $page->getUuid();
        $normalizedContent['webspace'] = $page->getWebspaceKey();

        return new JsonResponse($this->normalizer->normalize(
            $normalizedContent, // TODO this should just be the page entity see comment above
            'json',
            ['sulu_admin' => true, 'sulu_admin_page' => true, 'sulu_admin_page_content' => true],
        ));
    }

    public function postAction(Request $request): Response
    {
        $message = new CreatePageMessage(
            $request->query->getString('webspace'),
            $request->query->getString('parentId'),
            $this->getData($request)
        );

        /** @see CreatePageMessageHandler  */
        /** @var PageInterface $page */
        $page = $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        $id = $page->getUuid();

        $this->triggerAction($request, $id);

        $response = $this->getAction($request, $id);

        return $response->setStatusCode(201);
    }

    public function putAction(Request $request, string $id): Response
    {
        $message = new ModifyPageMessage($id, $this->getData($request));
        /* @see ModifyPageMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        $this->triggerAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function postTriggerAction(Request $request, string $id): Response
    {
        $this->triggerAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function deleteAction(string $id): Response
    {
        $message = new RemovePageMessage($id);
        /* @see RemovePageMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new Response('', 204);
    }

    /**
     * @return mixed[]
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

    private function triggerAction(Request $request, string $uuid): void
    {
        $action = $request->query->get('action');

        if (!$action || 'draft' === $action) {
            return;
        }

        if ('copy-locale' === $action) {
            $message = new CopyLocalePageMessage(
                $uuid,
                (string) $request->query->get('src'),
                (string) $request->query->get('dest')
            );

            /* @see Sulu\Page\Application\MessageHandler\CopyLocalePageMessageHandler */
            $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } else {
            $message = new ApplyWorkflowTransitionPageMessage($uuid, $this->getLocale($request), $action);

            /* @see Sulu\Page\Application\MessageHandler\ApplyWorkflowTransitionPageMessageHandler */
            $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }
    }

    private function createDoctrineListRepresentation(
        string $resourceKey,
        array $filters = [],
        array $parameters = [],
        $parentId = null,
        array $expandedIds = [],
        array $includedFields = [],
        array $groupByFields = [],
        ?string $listKey = null
    ): CollectionRepresentation {
        $listKey = $listKey ?? $resourceKey;

        /** @var DoctrineFieldDescriptor[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors($listKey);
        $listBuilder = $this->listBuilderFactory->create($fieldDescriptors['id']->getEntityName());
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        foreach ($parameters as $key => $value) {
            $listBuilder->setParameter($key, $value);
        }

        foreach ($filters as $key => $value) {
            $listBuilder->where($fieldDescriptors[$key], $value);
        }

        foreach ($includedFields as $field) {
            $listBuilder->addSelectField($fieldDescriptors[$field]);
        }

        foreach ($groupByFields as $field) {
            $listBuilder->addGroupBy($fieldDescriptors[$field]);
        }

        // disable pagination to simplify tree handling and select tree related properties that are used below
        $listBuilder->limit(\PHP_INT_MAX);
        $listBuilder->addSelectField($fieldDescriptors['lft']);
        $listBuilder->addSelectField($fieldDescriptors['rgt']);
        $listBuilder->addSelectField($fieldDescriptors['parentId']);

        // collect entities of which the children should be included in the response
        $idsToExpand = \array_merge(
            [$parentId],
            $this->findIdsOnPathsBetween($fieldDescriptors['id']->getEntityName(), $parentId, $expandedIds),
            $expandedIds
        );

        // generate expressions to select only entities that are children of the collected expand-entities
        $expandExpressions = [];
        foreach ($idsToExpand as $idToExpand) {
            $expandExpressions[] = $listBuilder->createWhereExpression(
                $fieldDescriptors['parentId'],
                $idToExpand,
                ListBuilderInterface::WHERE_COMPARATOR_EQUAL
            );
        }

        if (1 === \count($expandExpressions)) {
            $listBuilder->addExpression($expandExpressions[0]);
        } elseif (\count($expandExpressions) > 1) {
            $orExpression = $listBuilder->createOrExpression($expandExpressions);
            $listBuilder->addExpression($orExpression);
        }

        return new CollectionRepresentation(
            $this->generateNestedRows($parentId, $resourceKey, $listBuilder->execute()),
            $resourceKey
        );
    }

    private function findIdsOnPathsBetween(string $entityClass, int|string|null $startId, array $endIds): array
    {
        // there are no paths and therefore no ids if we dont have any end-ids
        if (0 === \count($endIds)) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from($entityClass, 'entity')
            ->select('entity.uuid');

        // if this start-id is not set we want to include all paths from the root to our end-ids
        if ($startId) {
            $queryBuilder->from($entityClass, 'startEntity')
                ->andWhere('startEntity.uuid = :startIds')
                ->andWhere('entity.lft > startEntity.lft')
                ->andWhere('entity.rgt < startEntity.rgt')
                ->setParameter('startIds', $startId);
        }

        $queryBuilder->from($entityClass, 'endEntity')
            ->andWhere('endEntity.uuid IN (:endIds)')
            ->andWhere('entity.lft < endEntity.lft')
            ->andWhere('entity.rgt > endEntity.rgt')
            ->setParameter('endIds', $endIds);

        return \array_map('current', $queryBuilder->getQuery()->getScalarResult());
    }

    private function generateNestedRows($parentId, string $resourceKey, array $flatRows): array
    {
        // add hasChildren property that is expected by the sulu frontend
        foreach ($flatRows as &$row) {
            $row['hasChildren'] = ($row['lft'] + 1) !== $row['rgt'];
        }

        // group rows by the id of their parent
        $rowsByParentId = [];
        foreach ($flatRows as &$row) {
            $rowParentId = $row['parentId'];
            if (!\array_key_exists($rowParentId, $rowsByParentId)) {
                $rowsByParentId[$rowParentId] = [];
            }
            $rowsByParentId[$rowParentId][] = &$row;
        }

        // embed children rows int their parent rows
        foreach ($flatRows as &$row) {
            $rowId = $row['id'];
            if (\array_key_exists($rowId, $rowsByParentId)) {
                $row['_embedded'] = [
                    $resourceKey => $rowsByParentId[$rowId],
                ];
            }
        }

        // remove tree related properties from the response
        foreach ($flatRows as &$row) {
            unset($row['rgt']);
            unset($row['lft']);
            unset($row['parentId']);
        }

        return $rowsByParentId[$parentId] ?? [];
    }
}
