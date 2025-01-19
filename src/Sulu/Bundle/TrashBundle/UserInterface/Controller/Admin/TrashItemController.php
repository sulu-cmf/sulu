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

namespace Sulu\Bundle\TrashBundle\UserInterface\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Bundle\TrashBundle\Infrastructure\Sulu\Admin\TrashAdmin;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @RouteResource("trash-item")
 *
 * This controller cannot implement the SecuredControllerInterface, because then the SuluSecurityListener would check
 * for the "edit" permission in the "postTriggerAction", but the TrashAdmin::SECURITY_CONTEXT doesn't define an "edit" permission.
 * Because of this, the controller needs to explicitly check the "view" permissions by itself.
 */
class TrashItemController extends AbstractRestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * @param array<string, int> $permissions Inject `sulu_security.permissions` parameter
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private TranslatorInterface $translator,
        private TrashManagerInterface $trashManager,
        private TrashItemRepositoryInterface $trashItemRepository,
        private SecurityCheckerInterface $securityChecker,
        private ServiceLocator $restoreConfigurationProviderLocator,
        private string $trashItemClass,
        private array $permissions,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(Request $request): Response
    {
        $this->securityChecker->checkPermission(
            TrashAdmin::SECURITY_CONTEXT,
            PermissionTypes::VIEW
        );

        /** @var string|null $locale */
        $locale = $this->getLocale($request);
        /** @var UserInterface $user */
        $user = $this->getUser();

        /** @var array<string, FieldDescriptorInterface> $configurationFieldDescriptors */
        $configurationFieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(
            TrashItemInterface::LIST_KEY
        );

        $hiddenFieldDescriptors = $this->getHiddenFieldDescriptors();
        $requiredFieldDescriptors = $this->getRequiredFieldDescriptors();
        $fieldDescriptors = \array_merge(
            $hiddenFieldDescriptors,
            $requiredFieldDescriptors,
            $configurationFieldDescriptors
        );

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($this->trashItemClass);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listBuilder->setParameter('locale', $locale);

        foreach ($hiddenFieldDescriptors as $fieldDescriptor) {
            $listBuilder->addSelectField($fieldDescriptor);
        }

        foreach ($requiredFieldDescriptors as $fieldDescriptor) {
            $listBuilder->addSelectField($fieldDescriptor);
        }

        $this->addResourceSecurityContextCondition($listBuilder, $fieldDescriptors, $user);
        $this->addResourceObjectSecurityCondition($listBuilder, $fieldDescriptors, $user);

        $trashItems = $listBuilder->execute();

        $trashItems = \array_map(
            function(array $trashItem) use ($hiddenFieldDescriptors) {
                if (isset($trashItem['resourceType'])) {
                    $trashItem['resourceType'] = $this->getResourceTranslation($trashItem['resourceType'], $trashItem['restoreType']);
                }

                foreach ($hiddenFieldDescriptors as $fieldDescriptor) {
                    unset($trashItem[$fieldDescriptor->getName()]);
                }

                return $trashItem;
            },
            $trashItems
        );

        $listRepresentation = new PaginatedRepresentation(
            $trashItems,
            TrashItemInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count()
        );

        return $this->handleView(
            $this->view($listRepresentation, 200)
        );
    }

    public function getAction(int $id): Response
    {
        $this->securityChecker->checkPermission(
            TrashAdmin::SECURITY_CONTEXT,
            PermissionTypes::VIEW
        );

        $trashItem = $this->trashItemRepository->getOneBy(['id' => $id]);

        if (null !== $trashItem->getResourceSecurityContext()) {
            $this->securityChecker->checkPermission(
                new SecurityCondition(
                    $trashItem->getResourceSecurityContext(),
                    null,
                    $trashItem->getResourceSecurityObjectType(),
                    $trashItem->getResourceSecurityObjectId()
                ),
                PermissionTypes::VIEW
            );
        }

        $context = new Context();
        $context->setGroups(['trash_item_admin_api']);

        return $this->handleView(
            $this->view($trashItem)->setContext($context)
        );
    }

    public function deleteAction(int $id): Response
    {
        $this->securityChecker->checkPermission(
            TrashAdmin::SECURITY_CONTEXT,
            PermissionTypes::DELETE
        );

        $trashItem = $this->trashItemRepository->getOneBy(['id' => $id]);

        $this->trashManager->remove($trashItem);
        $this->entityManager->flush();

        return $this->handleView(
            $this->view(null, 204)
        );
    }

    public function postTriggerAction(int $id, Request $request): Response
    {
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            return match ($action) {
                'restore' => $this->restoreTrashItem($id, $request->request->all()),
                default => throw new RestException(\sprintf('Unrecognized action: "%s"', $action)),
            };
        } catch (RestException $ex) {
            $view = $this->view($ex->toArray(), 400);

            return $this->handleView($view);
        }
    }

    /**
     * @param mixed[] $restoreFormData
     */
    private function restoreTrashItem(int $id, array $restoreFormData): Response
    {
        $trashItem = $this->trashItemRepository->getOneBy(['id' => $id]);

        if (null !== $trashItem->getResourceSecurityContext()) {
            $this->securityChecker->checkPermission(
                new SecurityCondition(
                    $trashItem->getResourceSecurityContext(),
                    null,
                    $trashItem->getResourceSecurityObjectType(),
                    $trashItem->getResourceSecurityObjectId()
                ),
                PermissionTypes::ADD
            );
        }

        $restoredObject = $this->trashManager->restore($trashItem, $restoreFormData);
        $this->entityManager->flush();

        $viewContext = new Context();

        if ($this->restoreConfigurationProviderLocator->has($trashItem->getResourceKey())) {
            /** @var RestoreConfigurationProviderInterface $restoreConfigurationProvider */
            $restoreConfigurationProvider = $this->restoreConfigurationProviderLocator->get($trashItem->getResourceKey());
            $restoreConfiguration = $restoreConfigurationProvider->getConfiguration();

            if ($serializationGroups = $restoreConfiguration->getResultSerializationGroups()) {
                $viewContext->setGroups($serializationGroups);
            }
        }

        return $this->handleView(
            $this->view($restoredObject)->setContext($viewContext)
        );
    }

    /**
     * @param array<string, FieldDescriptorInterface> $fieldDescriptors
     */
    private function addResourceSecurityContextCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        UserInterface $user
    ): void {
        /** @var DoctrineFieldDescriptor $resourceSecurityContextFieldDescriptor */
        $resourceSecurityContextFieldDescriptor = $fieldDescriptors['resourceSecurityContext'];
        $listBuilder->addPermissionCheckField($resourceSecurityContextFieldDescriptor);

        $securityContexts = [];
        $viewPermission = $this->permissions[PermissionTypes::VIEW];

        foreach ($user->getRoleObjects() as $role) {
            foreach ($role->getPermissions() as $permission) {
                if (($permission->getPermissions() & $viewPermission) === $viewPermission) {
                    $securityContexts[] = $permission->getContext();
                }
            }
        }

        $securityContexts = \array_unique($securityContexts);

        $listBuilder->addExpression(
            $listBuilder->createOrExpression(
                [
                    $listBuilder->createInExpression(
                        $fieldDescriptors['resourceSecurityContext'],
                        $securityContexts
                    ),
                    $listBuilder->createWhereExpression(
                        $fieldDescriptors['resourceSecurityContext'],
                        null,
                        ListBuilderInterface::WHERE_COMPARATOR_EQUAL
                    ),
                ]
            )
        );
    }

    /**
     * @param array<string, FieldDescriptorInterface> $fieldDescriptors
     */
    private function addResourceObjectSecurityCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        UserInterface $user
    ): void {
        /** @var DoctrineFieldDescriptor $resourceSecurityObjectIdFieldDescriptor */
        $resourceSecurityObjectIdFieldDescriptor = $fieldDescriptors['resourceSecurityObjectId'];
        $listBuilder->addPermissionCheckField($resourceSecurityObjectIdFieldDescriptor);

        /** @var DoctrineFieldDescriptor $resourceSecurityObjectTypeFieldDescriptor */
        $resourceSecurityObjectTypeFieldDescriptor = $fieldDescriptors['resourceSecurityObjectType'];
        $listBuilder->addPermissionCheckField($resourceSecurityObjectTypeFieldDescriptor);

        $listBuilder->setPermissionCheckWithDynamicEntityClass(
            $user,
            PermissionTypes::VIEW,
            'resourceSecurityObjectType',
            'resourceSecurityObjectId'
        );
    }

    private function getResourceTranslation(string $resourceKey, ?string $restoreType = null): string
    {
        $resourceTranslation = $this->translator->trans(
            \sprintf(
                'sulu_activity.resource.%s',
                $resourceKey
            ),
            [],
            'admin'
        );

        if ($restoreType) {
            $resourceTranslation = \sprintf(
                '%s (%s)',
                $resourceTranslation,
                $this->translator->trans(
                    \sprintf(
                        'sulu_activity.resource.%s.%s',
                        $resourceKey,
                        $restoreType
                    ),
                    [],
                    'admin'
                )
            );
        }

        return $resourceTranslation;
    }

    /**
     * @return array<string, FieldDescriptorInterface>
     */
    private function getHiddenFieldDescriptors(): array
    {
        return [
            'resourceSecurityContext' => $this->createFieldDescriptor('resourceSecurityContext'),
            'resourceSecurityObjectType' => $this->createFieldDescriptor('resourceSecurityObjectType'),
            'resourceSecurityObjectId' => $this->createFieldDescriptor('resourceSecurityObjectId'),
        ];
    }

    /**
     * @return array<string, FieldDescriptorInterface>
     */
    private function getRequiredFieldDescriptors(): array
    {
        return [
            'id' => $this->createFieldDescriptor('id'),
            'resourceKey' => $this->createFieldDescriptor('resourceKey'),
            'restoreType' => $this->createFieldDescriptor('restoreType'),
        ];
    }

    /**
     * @param DoctrineJoinDescriptor[]|null $joins
     */
    private function createFieldDescriptor(
        string $name,
        ?string $fieldName = null,
        ?string $entityName = null,
        ?array $joins = null,
        ?string $type = null
    ): DoctrineFieldDescriptor {
        $fieldName = $fieldName ?? $name;
        $entityName = $entityName ?? $this->trashItemClass;
        $joins = $joins ?? [];
        $type = $type ?? 'string';

        return new DoctrineFieldDescriptor(
            $fieldName,
            $name,
            $entityName,
            null,
            $joins,
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            $type,
            false
        );
    }
}
