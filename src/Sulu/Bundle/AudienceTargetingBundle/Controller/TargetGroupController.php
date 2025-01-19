<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\AudienceTargetingBundle\Admin\AudienceTargetingAdmin;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepositoryInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes target groups available through a REST API.
 *
 * @RouteResource("target-group")
 */
class TargetGroupController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityKey = 'target_groups';

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $listBuilderFactory;

    /**
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    /**
     * @var TargetGroupRuleRepositoryInterface
     */
    private $targetGroupRuleRepository;

    /**
     * @var TargetGroupConditionRepositoryInterface
     */
    private $targetGroupConditionRepository;

    /**
     * @var TargetGroupWebspaceRepositoryInterface
     */
    private $targetGroupWebspaceRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        RestHelperInterface $restHelper,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        TargetGroupRepositoryInterface $targetGroupRepository,
        TargetGroupRuleRepositoryInterface $targetGroupRuleRepository,
        TargetGroupConditionRepositoryInterface $targetGroupConditionRepository,
        TargetGroupWebspaceRepositoryInterface $targetGroupWebspaceRepository,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($viewHandler);
        $this->restHelper = $restHelper;
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->listBuilderFactory = $listBuilderFactory;
        $this->targetGroupRepository = $targetGroupRepository;
        $this->targetGroupRuleRepository = $targetGroupRuleRepository;
        $this->targetGroupConditionRepository = $targetGroupConditionRepository;
        $this->targetGroupWebspaceRepository = $targetGroupWebspaceRepository;
        $this->entityManager = $entityManager;
    }

    public function getSecurityContext()
    {
        return AudienceTargetingAdmin::SECURITY_CONTEXT;
    }

    /**
     * Returns list of target-groups.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $listBuilder = $this->listBuilderFactory->create($this->targetGroupRepository->getClassName());

        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('target_groups');
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        // If webspaces are concatinated we need to group by id. This happens
        // when no fields are supplied at all OR webspaces are requested as field.
        $fieldsParam = $request->get('fields');
        $fields = \explode(',', $fieldsParam);
        if (null === $fieldsParam || false !== \array_search('webspaceKeys', $fields)) {
            $listBuilder->addGroupBy($fieldDescriptors['id']);
        }

        $results = $listBuilder->execute();
        $list = new PaginatedRepresentation(
            $results,
            static::$entityKey,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count()
        );

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Returns a target group by id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id)
    {
        $findCallback = function($id) {
            $targetGroup = $this->targetGroupRepository->find($id);

            return $targetGroup;
        };

        $view = $this->responseGetById($id, $findCallback);

        return $this->handleView($view);
    }

    /**
     * Handle post request for target group.
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $data = $this->convertFromRequest(\json_decode($request->getContent(), true));
        $targetGroup = $this->mapEntity($data);
        $this->entityManager->flush();

        return $this->handleView($this->view($targetGroup));
    }

    /**
     * Handle put request for target group.
     *
     * @param int $id
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        $jsonData = $request->getContent();
        $data = \json_decode($jsonData, true);

        // Id should be taken of request uri.
        $data['id'] = (int) $id;

        $data = $this->convertFromRequest($data);

        $targetGroup = $this->mapEntity($data);
        $this->entityManager->flush();

        return $this->handleView($this->view($targetGroup));
    }

    /**
     * Handle delete request for target group.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $targetGroup = $this->getTargetGroupById((int) $id);

        $this->entityManager->remove($targetGroup);
        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    /**
     * Handle multiple delete requests for target groups.
     *
     * @return Response
     *
     * @throws MissingParameterException
     */
    public function cdeleteAction(Request $request)
    {
        $idsData = $request->get('ids');
        $ids = \explode(',', $idsData);

        if (!\count($ids)) {
            throw new MissingParameterException('TargetGroupController', 'ids');
        }

        $targetGroups = $this->targetGroupRepository->findById($ids);

        foreach ($targetGroups as $targetGroup) {
            $this->entityManager->remove($targetGroup);
        }

        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    private function convertFromRequest(array $data)
    {
        if ($data['webspaceKeys']) {
            $data['webspaces'] = \array_map(function($webspaceKey) {
                return ['webspaceKey' => $webspaceKey];
            }, $data['webspaceKeys']);
        } else {
            $data['webspaces'] = [];
        }

        if (!$data['rules']) {
            $data['rules'] = [];
        }

        // Unset IDs of Conditions, otherwise they won't be able to save as id is null.
        if (\array_key_exists('rules', $data)) {
            foreach ($data['rules'] as $ruleKey => &$rule) {
                if (\array_key_exists('conditions', $rule)) {
                    foreach ($rule['conditions'] as $key => &$condition) {
                        if (\array_key_exists('id', $condition) && \is_null($condition['id'])) {
                            unset($condition['id']);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Maps array payload into TargetGroup object.
     *
     * @param array{
     *     id?: int|null,
     *     title: string,
     *     description: string,
     *     priority: int,
     *     active: bool,
     *     rules: array<array{
     *         id?: int|null,
     *         title: string,
     *         frequency: int,
     *         conditions: array<array{
     *             id?: int|null,
     *             type: string,
     *             condition: mixed[],
     *         }>,
     *     }>,
     *     webspaces: array<array{
     *          webspaceKey: string
     *     }>,
     * } $data
     */
    private function mapEntity(array $data): TargetGroupInterface
    {
        $targetGroup = $this->getOrCreateTargetGroupById($data['id'] ?? null);

        $targetGroup->setTitle($data['title']);
        $targetGroup->setDescription($data['description']);
        $targetGroup->setPriority($data['priority']);
        $targetGroup->setActive($data['active']);

        $oldRules = new ArrayCollection($targetGroup->getRules()->toArray()); // remove reference
        $targetGroup->clearRules();

        foreach ($data['rules'] as $ruleData) {
            $rule = $this->getOrCreateTargetGroupRuleById($targetGroup->getId(), $ruleData['id'] ?? null);
            $oldRules->removeElement($rule);

            $rule->setTargetGroup($targetGroup);
            $rule->setTitle($ruleData['title']);
            $rule->setFrequency($ruleData['frequency']);
            $rule->setTargetGroup($targetGroup);

            $rule = $this->targetGroupRuleRepository->save($rule);
            $targetGroup->addRule($rule);

            $oldConditions = new ArrayCollection($rule->getConditions()->toArray()); // remove reference
            $rule->clearConditions();

            foreach ($ruleData['conditions'] as $conditionData) {
                $condition = $this->getOrCreateTargetGroupConditionById($rule->getId(), $conditionData['id'] ?? null);
                $oldConditions->removeElement($condition);

                $condition->setRule($rule);
                $condition->setType($conditionData['type']);
                $condition->setCondition($conditionData['condition']);

                $this->entityManager->persist($condition);
                $rule->addCondition($condition);
            }

            foreach ($oldConditions as $oldCondition) {
                $this->entityManager->remove($oldCondition);
            }
        }

        foreach ($oldRules as $oldRule) {
            $this->entityManager->remove($oldRule);
        }

        $oldWebspaces = new ArrayCollection($targetGroup->getWebspaces()->toArray()); // remove reference
        $targetGroup->clearWebspaces();

        /**
         * @var array{
         *     webspaceKey: string
         * } $webspaceData
         */
        foreach ($data['webspaces'] as $webspaceData) {
            $targetGroupWebspace = $this->getOrCreateTargetGroupWebspaceByKey($webspaceData['webspaceKey'], $targetGroup);
            $oldWebspaces->removeElement($targetGroupWebspace);

            $this->entityManager->persist($targetGroupWebspace);
            $targetGroup->addWebspace($targetGroupWebspace);
        }

        foreach ($oldWebspaces as $oldWebspace) {
            $this->entityManager->remove($oldWebspace);
        }

        return $this->targetGroupRepository->save($targetGroup);
    }

    /**
     * Returns target group by id. Throws an exception if not found.
     *
     * @throws EntityNotFoundException
     */
    private function getTargetGroupById(int $id): TargetGroupInterface
    {
        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $this->targetGroupRepository->find($id);

        if (!$targetGroup) {
            throw new EntityNotFoundException($this->targetGroupRepository->getClassName(), (string) $id);
        }

        return $targetGroup;
    }

    /**
     * Returns target group rule by id. Throws an exception if not found.
     *
     * @throws EntityNotFoundException
     */
    private function getTargetGroupRuleById(int $targetGroupId, int $id): TargetGroupRuleInterface
    {
        /** @var TargetGroupRuleInterface $targetGroupRule */
        $targetGroupRule = $this->targetGroupRuleRepository->findOneBy([
            'id' => $id,
            'targetGroup' => $targetGroupId,
        ]);

        if (!$targetGroupRule) {
            throw new EntityNotFoundException($this->targetGroupRuleRepository->getClassName(), (string) $id);
        }

        return $targetGroupRule;
    }

    /**
     * Returns target group condition by id. Throws an exception if not found.
     *
     * @throws EntityNotFoundException
     */
    private function getTargetGroupConditionById(int $targetGroupRuleId, int $id): TargetGroupConditionInterface
    {
        /** @var TargetGroupConditionInterface $targetGroupCondition */
        $targetGroupCondition = $this->targetGroupConditionRepository->findOneBy([
            'id' => $id,
            'rule' => $targetGroupRuleId,
        ]);

        if (!$targetGroupCondition) {
            throw new EntityNotFoundException($this->targetGroupConditionRepository->getClassName(), (string) $id);
        }

        return $targetGroupCondition;
    }

    /**
     * Returns target group by id or creates it if no id provided. Throws an exception if not found.
     *
     * @throws EntityNotFoundException
     */
    private function getOrCreateTargetGroupById(?int $id): TargetGroupInterface
    {
        if (null !== $id) {
            return $this->getTargetGroupById($id);
        }

        return $this->targetGroupRepository->createNew();
    }

    /**
     * Returns target group rule by id or creates it if no id provided. Throws an exception if not found.
     *
     * @throws EntityNotFoundException
     */
    private function getOrCreateTargetGroupRuleById(?int $targetGroupId, ?int $id): TargetGroupRuleInterface
    {
        if (null !== $targetGroupId && null !== $id) {
            return $this->getTargetGroupRuleById($targetGroupId, $id);
        }

        return $this->targetGroupRuleRepository->createNew();
    }

    /**
     * Returns target group condition by id or creates it if no id provided. Throws an exception if not found.
     *
     * @throws EntityNotFoundException
     */
    private function getOrCreateTargetGroupConditionById(?int $targetGroupRuleId, ?int $id): TargetGroupConditionInterface
    {
        if (null != $targetGroupRuleId && null !== $id) {
            return $this->getTargetGroupConditionById($targetGroupRuleId, $id);
        }

        return $this->targetGroupConditionRepository->createNew();
    }

    /**
     * Returns target group by id. Throws an exception if not found.
     */
    private function getTargetGroupWebspaceById(string $webspaceKey, TargetGroupInterface $targetGroup): ?TargetGroupWebspaceInterface
    {
        /** @var  */
        return $this->targetGroupWebspaceRepository->findOneBy([
            'webspaceKey' => $webspaceKey,
            'targetGroup' => $targetGroup,
        ]);
    }

    /**
     * Returns target group webspace by id or creates it if no id provided. Throws an exception if not found.
     */
    private function getOrCreateTargetGroupWebspaceByKey(string $webspaceKey, TargetGroupInterface $targetGroup): TargetGroupWebspaceInterface
    {
        $targetGroupWebspace = $this->getTargetGroupWebspaceById($webspaceKey, $targetGroup);
        if ($targetGroupWebspace instanceof TargetGroupWebspaceInterface) {
            return $targetGroupWebspace;
        }

        /** @var TargetGroupWebspaceInterface $targetGroupWebspace */
        $targetGroupWebspace = $this->targetGroupWebspaceRepository->createNew();
        $targetGroupWebspace->setWebspaceKey($webspaceKey);
        $targetGroupWebspace->setTargetGroup($targetGroup);

        return $targetGroupWebspace;
    }
}
