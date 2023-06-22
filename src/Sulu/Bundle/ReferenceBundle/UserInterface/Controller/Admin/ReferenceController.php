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

namespace Sulu\Bundle\ReferenceBundle\UserInterface\Controller\Admin;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReferenceController extends AbstractRestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $listBuilderFactory;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var string
     */
    private $referenceClass;

    public function __construct(
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        RestHelperInterface $restHelper,
        TranslatorInterface $translator,
        SecurityCheckerInterface $securityChecker,
        string $referenceClass,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->listBuilderFactory = $listBuilderFactory;
        $this->restHelper = $restHelper;
        $this->translator = $translator;
        $this->securityChecker = $securityChecker;
        $this->referenceClass = $referenceClass;
    }

    public function cgetAction(Request $request): Response
    {
        $this->securityChecker->checkPermission(
            ReferenceAdmin::SECURITY_CONTEXT,
            PermissionTypes::VIEW
        );

        /** @var string|null $resourceId */
        $resourceId = $this->getRequestParameter($request, 'resourceId');
        /** @var string|null $resourceKey */
        $resourceKey = $this->getRequestParameter($request, 'resourceKey');

        /** @var UserInterface $user */
        $user = $this->getUser();

        /** @var array<string, FieldDescriptorInterface> $configurationFieldDescriptors */
        $configurationFieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(
            ReferenceInterface::LIST_KEY
        );

        $requiredFieldDescriptors = $this->getRequiredFieldDescriptors();
        $fieldDescriptors = \array_merge(
            $configurationFieldDescriptors,
            $requiredFieldDescriptors
        );

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($this->referenceClass);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        foreach ($requiredFieldDescriptors as $fieldDescriptor) {
            $listBuilder->addSelectField($fieldDescriptor);
        }

        if (null !== $resourceKey) {
            $this->addResourceKeyCondition($listBuilder, $fieldDescriptors, $resourceKey);

            if (null !== $resourceId) {
                $this->addResourceIdCondition($listBuilder, $fieldDescriptors, $resourceId);
            }
        }

        $references = $listBuilder->execute();
        $translationLocale = $user->getLocale();
        $references = \array_map(
            function(array $reference) use ($translationLocale) {
                $referenceResourceKeyTitle = $this->translator->trans(
                    \sprintf(
                        'sulu_reference.resource.%s',
                        $reference['referenceResourceKey'],
                    ),
                    [],
                    'admin',
                    $translationLocale
                );

                return \array_merge(
                    $reference,
                    [
                        'referenceResourceKey' => $reference['referenceResourceKey'],
                        'referenceResourceKeyTitle' => $referenceResourceKeyTitle,
                    ]
                );
            },
            $references
        );

        $listRepresentation = new ListRepresentation(
            $references,
            ReferenceInterface::RESOURCE_KEY,
            'sulu_reference.get_references',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $this->handleView(
            $this->view($listRepresentation, 200)
        );
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
        $entityName = $entityName ?? $this->referenceClass;
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
     * @return array<string, FieldDescriptorInterface>
     */
    private function getRequiredFieldDescriptors(): array
    {
        return [
            'resourceId' => $this->createFieldDescriptor('resourceId'),
            'resourceKey' => $this->createFieldDescriptor('resourceKey'),
            'referenceResourceId' => $this->createFieldDescriptor('referenceResourceId'),
            'referenceResourceKey' => $this->createFieldDescriptor('referenceResourceKey'),
            'referenceViewAttributes' => $this->createFieldDescriptor('referenceViewAttributes'),
        ];
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
}
