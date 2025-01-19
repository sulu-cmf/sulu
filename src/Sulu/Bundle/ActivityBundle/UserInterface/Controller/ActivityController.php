<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\UserInterface\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\ActivityAdmin;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
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
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActivityController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    /**
     * @param array<string, int> $permissions Inject `sulu_security.permissions` parameter
     */
    public function __construct(
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private SecurityCheckerInterface $securityChecker,
        private TranslatorInterface $translator,
        private string $activityClass,
        private string $contactClass,
        private string $userClass,
        private array $permissions,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(Request $request): Response
    {
        /** @var UserInterface $user */
        $user = $this->getUser();

        /** @var array<string, FieldDescriptorInterface> $configurationFieldDescriptors */
        $configurationFieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(
            ActivityInterface::LIST_KEY
        );

        $fieldDescriptors = \array_merge(
            $this->getRawDataFieldDescriptors(),
            $configurationFieldDescriptors
        );

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($this->activityClass);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);
        $listBuilder->setSelectFields($fieldDescriptors);
        $listBuilder->sort($fieldDescriptors['timestamp'], ListBuilderInterface::SORTORDER_DESC);
        $listBuilder->sort($fieldDescriptors['id'], ListBuilderInterface::SORTORDER_DESC);

        $translationLocale = $user->getLocale();

        /** @var string|null $resourceLocale */
        $resourceLocale = $this->getRequestParameter($request, 'locale');
        /** @var string|null $resourceId */
        $resourceId = $this->getRequestParameter($request, 'resourceId');
        /** @var string|null $resourceKey */
        $resourceKey = $this->getRequestParameter($request, 'resourceKey');

        $this->addResourceSecurityContextCondition($listBuilder, $fieldDescriptors, $user);
        $this->addResourceObjectSecurityCondition($listBuilder, $fieldDescriptors, $user);

        if (null !== $resourceLocale) {
            $this->addResourceLocaleCondition($listBuilder, $fieldDescriptors, $resourceLocale);
        }

        if (null !== $resourceKey) {
            $this->addResourceKeyCondition($listBuilder, $fieldDescriptors, $resourceKey);

            if (null !== $resourceId) {
                $this->addResourceIdCondition($listBuilder, $fieldDescriptors, $resourceId);
            }
        }

        $activities = $listBuilder->execute();
        $activities = \array_map(
            function(array $activity) use ($translationLocale, $configurationFieldDescriptors) {
                $description = $this->getActivityDescription($activity, $translationLocale);
                $resource = $this->getActivityResource($activity);

                return \array_filter(
                    \array_merge(
                        $activity,
                        [
                            'description' => $description,
                            'resource' => $resource,
                        ]
                    ),
                    function(string $key) use ($configurationFieldDescriptors) {
                        return 'id' === $key || \array_key_exists($key, $configurationFieldDescriptors);
                    },
                    \ARRAY_FILTER_USE_KEY
                );
            },
            $activities
        );

        $listRepresentation = new PaginatedRepresentation(
            $activities,
            ActivityInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count()
        );

        return $this->handleView(
            $this->view($listRepresentation, 200)
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

    /**
     * @param array<string, FieldDescriptorInterface> $fieldDescriptors
     */
    private function addResourceLocaleCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        string $resourceLocale
    ): void {
        $listBuilder->addExpression(
            $listBuilder->createOrExpression(
                [
                    $listBuilder->createWhereExpression(
                        $fieldDescriptors['resourceLocale'],
                        $resourceLocale,
                        ListBuilderInterface::WHERE_COMPARATOR_EQUAL
                    ),
                    $listBuilder->createWhereExpression(
                        $fieldDescriptors['resourceLocale'],
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
    private function addResourceKeyCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        string $resourceKey
    ): void {
        $listBuilder->where(
            $fieldDescriptors['resourceKey'],
            $resourceKey,
            ListBuilderInterface::WHERE_COMPARATOR_EQUAL
        );
    }

    /**
     * @param array<string, FieldDescriptorInterface> $fieldDescriptors
     */
    private function addResourceIdCondition(
        DoctrineListBuilder $listBuilder,
        array $fieldDescriptors,
        string $resourceId
    ): void {
        $listBuilder->where(
            $fieldDescriptors['resourceId'],
            $resourceId,
            ListBuilderInterface::WHERE_COMPARATOR_EQUAL
        );
    }

    /**
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    private function getTranslationParameters(array $array, string $translationLocale, string $prefix = '{', string $suffix = '}'): array
    {
        $translationParameters = [];
        foreach ($array as $key => $value) {
            if (\is_null($value)) {
                $translationParameters[$prefix . $key . $suffix] = 'null';
            } elseif (\is_bool($value)) {
                $translationParameters[$prefix . $key . $suffix] = $value ? 'true' : 'false';
            } elseif (\is_numeric($value)) {
                $translationParameters[$prefix . $key . $suffix] = $value;
            } elseif (\is_scalar($value)) {
                $value = (string) $value;

                if (\array_key_exists($key . 'Locale', $array)) {
                    $valueLocale = $array[$key . 'Locale'] ?? null;
                    $valueLocale = $valueLocale ? (string) $valueLocale : null;

                    $value = $this->getLocalizedValue($value, $valueLocale, $translationLocale);
                }

                $translationParameters[$prefix . $key . $suffix] = $value;
            } elseif (\is_array($value)) {
                foreach ($this->getTranslationParameters($value, $translationLocale, '', '') as $translationParameterKey => $translationParameterValue) {
                    $translationParameters[$prefix . $key . '_' . $translationParameterKey . $suffix] = $translationParameterValue;
                }
            }
        }

        return $translationParameters;
    }

    /**
     * @param array<string, mixed> $activity
     */
    private function getActivityResource(array $activity): string
    {
        return $this->translator->trans(
            \sprintf(
                'sulu_activity.resource.%s',
                $activity['resourceKey']
            ),
            [],
            'admin'
        );
    }

    /**
     * @param array<string, mixed> $activity
     */
    private function getActivityDescription(array $activity, string $translationLocale): string
    {
        $translationParameters = $this->getTranslationParameters($activity, $translationLocale);

        $translationParameters['{userFullName}'] = isset($activity['userFullName'])
            ? \sprintf('<b>%s</b>', $activity['userFullName'])
            : $this->translator->trans('sulu_activity.someone', [], 'admin', $translationLocale);

        $translationParameters['{resourceTitle}'] = $this->getLocalizedValue(
            $activity['resourceTitle'],
            $activity['resourceTitleLocale'] ?? null,
            $translationLocale
        );

        return $this->translator->trans(
            \sprintf(
                'sulu_activity.description.%s.%s',
                $activity['resourceKey'],
                $activity['type']
            ),
            $translationParameters,
            'admin',
            $translationLocale
        );
    }

    private function getLocalizedValue(?string $value, ?string $valueLocale, string $translationLocale): string
    {
        if (null !== $valueLocale && $translationLocale !== $valueLocale) {
            return $value . ' [' . \strtoupper($valueLocale) . ']';
        }

        return $value ?: '';
    }

    /**
     * @return array<string, FieldDescriptorInterface>
     */
    private function getRawDataFieldDescriptors(): array
    {
        $userJoins = [
            $this->userClass => new DoctrineJoinDescriptor(
                $this->userClass,
                $this->activityClass . '.user'
            ),
            $this->contactClass => new DoctrineJoinDescriptor(
                $this->contactClass,
                $this->userClass . '.contact'
            ),
        ];

        return [
            'id' => $this->createFieldDescriptor('id'),
            'type' => $this->createFieldDescriptor('type'),
            'context' => $this->createFieldDescriptor('context'),
            'timestamp' => $this->createFieldDescriptor('timestamp'),
            'batch' => $this->createFieldDescriptor('batch'),
            'resourceKey' => $this->createFieldDescriptor('resourceKey'),
            'resourceId' => $this->createFieldDescriptor('resourceId'),
            'resourceLocale' => $this->createFieldDescriptor('resourceLocale'),
            'resourceWebspaceKey' => $this->createFieldDescriptor('resourceWebspaceKey'),
            'resourceTitle' => $this->createFieldDescriptor('resourceTitle'),
            'resourceTitleLocale' => $this->createFieldDescriptor('resourceTitleLocale'),
            'resourceSecurityContext' => $this->createFieldDescriptor('resourceSecurityContext'),
            'resourceSecurityObjectType' => $this->createFieldDescriptor('resourceSecurityObjectType'),
            'resourceSecurityObjectId' => $this->createFieldDescriptor('resourceSecurityObjectId'),
            'userFullName' => $this->createConcatenationFieldDescriptor(
                'userFullName',
                [
                    $this->createFieldDescriptor('firstName', null, $this->contactClass, $userJoins),
                    $this->createFieldDescriptor('lastName', null, $this->contactClass, $userJoins),
                ]
            ),
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
        $entityName = $entityName ?? $this->activityClass;
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

    /**
     * @param FieldDescriptorInterface[] $fieldDescriptors
     */
    private function createConcatenationFieldDescriptor(
        string $name,
        array $fieldDescriptors,
        ?string $glue = null,
        ?string $type = null
    ): DoctrineConcatenationFieldDescriptor {
        $glue = $glue ?? ' ';
        $type = $type ?? 'string';

        return new DoctrineConcatenationFieldDescriptor(
            $fieldDescriptors,
            $name,
            null,
            $glue,
            FieldDescriptorInterface::VISIBILITY_ALWAYS,
            FieldDescriptorInterface::SEARCHABILITY_NEVER,
            $type,
            false
        );
    }

    public function getSecurityContext(): string
    {
        return ActivityAdmin::SECURITY_CONTEXT;
    }
}
