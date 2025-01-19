<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Version;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Handles the versions of pages.
 */
class VersionController extends AbstractRestController implements
    ClassResourceInterface,
    SecuredControllerInterface,
    SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private ListRestHelperInterface $listRestHelper,
        private UserRepositoryInterface $userRepository,
        private DocumentManagerInterface $documentManager,
        private RequestAnalyzerInterface $requestAnalyzer
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * Returns the versions for the page with the given UUID.
     *
     * @param string $id
     *
     * @return Response
     */
    public function cgetAction(Request $request, $id)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);

        $document = $this->documentManager->find($id, $request->query->get('locale'));
        $versions = \array_reverse(\array_filter($document->getVersions(), function($version) use ($locale) {
            /* @var Version $version */
            return $version->getLocale() === $locale;
        }));
        $total = \count($versions);

        $limit = $this->listRestHelper->getLimit();

        $versions = \array_slice($versions, $this->listRestHelper->getOffset(), $limit);

        $userIds = \array_unique(\array_map(function($version) {
            /* @var Version $version */
            return $version->getAuthor();
        }, $versions));

        $users = $this->userRepository->findUsersById($userIds);
        $fullNamesByIds = [];
        foreach ($users as $user) {
            $fullNamesByIds[$user->getId()] = $user->getContact()->getFullName();
        }

        $versionData = [];
        foreach ($versions as $version) {
            $versionData[] = [
                'id' => \str_replace('.', '_', $version->getId()),
                'locale' => $version->getLocale(),
                'author' => \array_key_exists($version->getAuthor(), $fullNamesByIds)
                    ? $fullNamesByIds[$version->getAuthor()] : '',
                'authored' => $version->getAuthored(),
            ];
        }

        $versionCollection = new PaginatedRepresentation(
            $versionData,
            'page_versions',
            (int) $this->listRestHelper->getPage(),
            (int) $limit,
            (int) $total
        );

        return $this->handleView($this->view($versionCollection));
    }

    /**
     * @param string $id
     * @param int $version
     *
     * @return Response
     */
    public function postTriggerAction(Request $request, $id, $version)
    {
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getLocale($request);

        $data = null;

        switch ($action) {
            case 'restore':
                $document = $this->documentManager->find($id, $locale);
                $this->documentManager->restore(
                    $document,
                    $locale,
                    \str_replace('_', '.', $version)
                );
                $this->documentManager->flush();

                $data = $this->documentManager->find($id, $locale);
                break;
        }

        $view = $this->view($data, null !== $data ? 200 : 204);

        $context = new Context();
        $context->setGroups(['defaultPage']);

        return $this->handleView($view->setContext($context));
    }

    public function getSecurityContext()
    {
        $webspace = $this->requestAnalyzer->getWebspace();

        if (!$webspace) {
            throw new MissingMandatoryParametersException('The webspace parameter is missing!');
        }

        if ($webspace) {
            return 'sulu.webspaces.' . $webspace->getKey();
        }
    }

    /**
     * @return string
     */
    public function getLocale(Request $request)
    {
        return $this->getRequestParameter($request, 'locale', true);
    }

    public function getSecuredClass()
    {
        return SecurityBehavior::class;
    }

    public function getSecuredObjectId(Request $request)
    {
        return $request->get('id');
    }
}
