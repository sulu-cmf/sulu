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

namespace Sulu\Bundle\PageBundle\EventListener;

use PHPCR\Query\QueryInterface;
use PHPCR\Query\RowInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Domain\Exception\RemovePageDependantResourcesFoundException;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Rest\Exception\InsufficientDescendantPermissionsException;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;

class PageRemoveSubscriber implements EventSubscriberInterface
{
    public const FORCE_REMOVE_CHILDREN_OPTION = 'force_remove_children';

    /**
     * @param array<string, int> $permissions
     */
    public function __construct(
        private SessionManagerInterface $sessionManager,
        private AccessControlRepositoryInterface $accessControlRepository,
        private SystemStoreInterface $systemStore,
        private Security|SymfonyCoreSecurity|null $security,
        private array $permissions,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::REMOVE => ['preventRemoveWithChildren', 4096],
            Events::CONFIGURE_OPTIONS => ['addForceRemoveChildrenOption'],
        ];
    }

    /**
     * @param array<array{id: string, resourceKey: string, depth: int}> $resources
     *
     * @return array<int, array<array{id: string, resourceKey: string}>>
     */
    private function groupResourcesByDepth(array $resources): array
    {
        $grouped = [];

        foreach ($resources as $resource) {
            $depth = $resource['depth'];
            unset($resource['depth']);

            if (!isset($grouped[$depth])) {
                $grouped[$depth] = [];
            }

            $grouped[$depth][] = $resource;
        }

        \krsort($grouped);

        return \array_values($grouped);
    }

    public function preventRemoveWithChildren(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $forceRemoveChildren = (bool) $event->getOption(static::FORCE_REMOVE_CHILDREN_OPTION, false);

        if ($forceRemoveChildren) {
            return;
        }

        $query = $this->createSql2Query(
            \sprintf(
                'SELECT [jcr:uuid] FROM [nt:unstructured] AS page WHERE [jcr:mixinTypes] = "sulu:page" AND ISDESCENDANTNODE(page, "%s")',
                $document->getPath()
            )
        );
        $result = $query->execute();

        $descendantPages = [];

        /** @var RowInterface<mixed> $row */
        foreach ($result->getRows() as $row) {
            $descendantPages[] = [
                'id' => $row->getValue('page.jcr:uuid'),
                'depth' => \substr_count((string) $row->getPath(), '/'),
                'resourceKey' => PageDocument::RESOURCE_KEY,
            ];
        }

        $anonymousRole = $this->systemStore->getAnonymousRole();

        $descendantPageIds = \array_column($descendantPages, 'id');
        $descendantAuthorizedPageIds = $this->accessControlRepository->findIdsWithGrantedPermissions(
            $this->getCurrentUser(),
            $this->permissions[PermissionTypes::DELETE],
            SecurityBehavior::class,
            $descendantPageIds,
            $this->systemStore->getSystem(),
            $anonymousRole ? $anonymousRole->getId() : null
        );
        $descendantUnauthorizedPageIds = \array_diff($descendantPageIds, $descendantAuthorizedPageIds);

        if (!empty($descendantUnauthorizedPageIds)) {
            throw new InsufficientDescendantPermissionsException(
                \count($descendantUnauthorizedPageIds),
                PermissionTypes::DELETE
            );
        }

        if (!empty($descendantPages)) {
            throw new RemovePageDependantResourcesFoundException(
                [
                    'id' => $document->getUuid(),
                    'resourceKey' => PageDocument::RESOURCE_KEY,
                ],
                $this->groupResourcesByDepth($descendantPages),
                \count($descendantPages)
            );
        }
    }

    public function addForceRemoveChildrenOption(ConfigureOptionsEvent $event): void
    {
        $optionsResolver = $event->getOptions();

        $optionsResolver->setDefault(static::FORCE_REMOVE_CHILDREN_OPTION, false);
        $optionsResolver->setAllowedTypes(static::FORCE_REMOVE_CHILDREN_OPTION, 'bool');
    }

    private function getCurrentUser(): ?UserInterface
    {
        if (null === $this->security) {
            return null;
        }

        /** @var UserInterface|null $user */
        $user = $this->security->getUser();

        return $user;
    }

    private function createSql2Query(string $sql2): QueryInterface
    {
        $queryManager = $this->sessionManager->getSession()->getWorkspace()->getQueryManager();

        return $queryManager->createQuery($sql2, 'JCR-SQL2');
    }
}
