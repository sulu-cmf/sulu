<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use PHPCR\ItemNotFoundException;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;


/**
 * handles content nodes
 */
class NodeController extends RestController implements ClassResourceInterface
{

    use RequestParametersTrait;

    /**
     * returns language code from request
     * @param Request $request
     * @return string
     */
    private function getLanguage(Request $request)
    {
        return $this->getRequestParameter($request, 'language', true);
    }

    /**
     * returns webspace key from request
     * @param Request $request
     * @return string
     */
    private function getWebspace(Request $request)
    {
        return $this->getRequestParameter($request, 'webspace', true);
    }

    /**
     * returns entry point (webspace as node)
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function entryAction(Request $request)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);

        $depth = $this->getRequestParameter($request, 'depth', false, 1);
        $ghostContent = $this->getBooleanRequestParameter($request, 'ghost-content', false, false);

        $view = $this->responseGetById(
            null,
            function () use ($language, $webspace, $depth, $ghostContent) {
                try {
                    return $this->getRepository()->getWebspaceNode(
                        $webspace,
                        $language,
                        $depth,
                        $ghostContent
                    );
                } catch (ItemNotFoundException $ex) {
                    return null;
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * returns a content item with given UUID as JSON String
     * @param Request $request
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(Request $request, $uuid)
    {
        $tree = $this->getBooleanRequestParameter($request, 'tree', false, false);

        if ($tree === false) {
            $response = $this->getSingleNode($request, $uuid);
        } else {
            $response = $this->getTreeForUuid($request, $uuid);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getSingleNode(Request $request, $uuid)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);
        $breadcrumb = $this->getBooleanRequestParameter($request, 'breadcrumb', false, false);
        $complete = $this->getBooleanRequestParameter($request, 'complete', false, true);
        $ghostContent = $this->getBooleanRequestParameter($request, 'ghost-content', false, false);

        $view = $this->responseGetById(
            $uuid,
            function ($id) use ($language, $webspace, $breadcrumb, $complete, $ghostContent) {
                try {
                    return $this->getRepository()->getNode(
                        $id,
                        $webspace,
                        $language,
                        $breadcrumb,
                        $complete,
                        $ghostContent
                    );
                } catch (ItemNotFoundException $ex) {
                    return null;
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * Returns a tree along the given path with the siblings of all nodes on the path.
     * This functionality is required for preloading the content navigation.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getTreeForUuid(Request $request, $uuid)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);

        $appendWebspaceNode = $this->getBooleanRequestParameter($request, 'webspace-node', false, false);

        try {
            if ($uuid !== null && $uuid !== '') {
                $result = $this->getRepository()->getNodesTree(
                    $uuid,
                    $webspace,
                    $language,
                    $excludeGhosts,
                    $appendWebspaceNode
                );
            } else {
                $result = $this->getRepository()->getWebspaceNode($webspace, $language);
            }
        } catch (ItemNotFoundException $ex) {
            // TODO return 404 and handle this edge case on client side
            return $this->redirect(
                $this->generateUrl(
                    'get_nodes',
                    array(
                        'tree' => 'false',
                        'depth' => 1,
                        'language' => $language,
                        'webspace' => $webspace,
                        'exclude-ghosts' => $excludeGhosts
                    )
                )
            );
        }

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * Returns nodes by given ids
     *
     * @param Request $request
     * @param array $idString
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getNodeyByIds(Request $request, $idString)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);

        $result = $this->getRepository()->getNodesByIds(
            preg_split('/[,]/', $idString, -1, PREG_SPLIT_NO_EMPTY),
            $webspace,
            $language
        );

        return $this->handleView($this->view($result));
    }

    /**
     * returns a content item for startpage
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);

        $result = $this->getRepository()->getIndexNode($webspace, $language);

        return $this->handleView($this->view($result));
    }

    /**
     * returns all content items as JSON String
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $tree = $this->getBooleanRequestParameter($request, 'tree', false, false);
        $ids = $this->getRequestParameter($request, 'ids');

        if ($tree === true) {
            return $this->getTreeForUuid($request, null);
        } elseif ($ids !== null) {
            return $this->getNodeyByIds($request, $ids);
        }

        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);
        $excludeGhosts = $this->getBooleanRequestParameter($request, 'exclude-ghosts', false, false);

        $parentUuid = $request->get('parent');
        $depth = $request->get('depth', 1);
        $depth = intval($depth);
        $flat = $request->get('flat', 'true');
        $flat = ($flat === 'true');

        // TODO pagination
        $result = $this->getRepository()->getNodes(
            $parentUuid,
            $webspace,
            $language,
            $depth,
            $flat,
            false,
            $excludeGhosts
        );

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * Returns the title of the pages for a given smart content configuration
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterAction(Request $request)
    {
        // load data from request
        $dataSource = $this->getRequestParameter($request, 'dataSource');
        $includeSubFolders = $this->getBooleanRequestParameter($request, 'includeSubFolders', false, false);
        $limitResult = $this->getRequestParameter($request, 'limitResult');
        $tagNames = $this->getRequestParameter($request, 'tags');
        $sortBy = $this->getRequestParameter($request, 'sortBy');
        $sortMethod = $this->getRequestParameter($request, 'sortMethod', false, 'asc');
        $webspaceKey = $this->getWebspace($request);
        $languageCode = $this->getLanguage($request);

        // resolve tag names
        $resolvedTags = array();

        /** @var TagManagerInterface $tagManager */
        $tagManager = $this->get('sulu_tag.tag_manager');

        if (isset($tagNames)) {
            $tags = explode(',', $tagNames);
            foreach ($tags as $tag) {
                $resolvedTag = $tagManager->findByName($tag);
                if ($resolvedTag) {
                    $resolvedTags[] = $resolvedTag->getId();
                }
            }
        }

        // get sort columns
        $sortColumns = array();
        if (isset($sortBy)) {
            $columns = explode(',', $sortBy);
            foreach ($columns as $column) {
                $sortColumns[] = $column;
            }
        }

        $filterConfig = array(
            'dataSource' => $dataSource,
            'includeSubFolders' => $includeSubFolders,
            'limitResult' => $limitResult,
            'tags' => $resolvedTags,
            'sortBy' => $sortColumns,
            'sortMethod' => $sortMethod
        );

        $content = $this->get('sulu_content.node_repository')->getFilteredNodes(
            $filterConfig,
            $languageCode,
            $webspaceKey,
            true,
            true
        );

        return $this->handleView($this->view($content));
    }

    /**
     * saves node with given uuid and data
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $uuid)
    {
        if ($uuid === 'index') {
            return $this->putIndex($request);
        }

        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);
        $template = $this->getRequestParameter($request, 'template', true);

        $isShadow = $this->getRequestParameter($request, 'shadowOn', false);
        $shadowBaseLanguage = $this->getRequestParameter($request, 'shadowBaseLanguage', null);

        $state = $this->getRequestParameter($request, 'state');

        if ($state !== null) {
            $state = intval($state);
        }

        $data = $request->request->all();

        $result = $this->getRepository()->saveNode(
            $data,
            $template,
            $webspace,
            $language,
            $this->getUser()->getId(),
            $uuid,
            null, // parentUuid
            $state,
            $isShadow,
            $shadowBaseLanguage
        );

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * put index page
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function putIndex(Request $request)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);
        $template = $this->getRequestParameter($request, 'template', true);
        $data = $request->request->all();

        try {
            if ($data['url'] != '/') {
                throw new InvalidArgumentException('Content', 'url', 'url of index page can not be changed');
            }

            $result = $this->getRepository()->saveIndexNode(
                $data,
                $template,
                $webspace,
                $language,
                $this->getUser()->getId()
            );
            $view = $this->view($result);
        } catch (RestException $ex) {
            $view = $this->view(
                $ex->toArray(),
                400
            );
        }

        return $this->handleView($view);
    }

    /**
     * Updates a content item and returns result as JSON String
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);
        $template = $this->getRequestParameter($request, 'template', true);
        $navigation = $this->getRequestParameter($request, 'navigation');
        $isShadow = $this->getRequestParameter($request, 'isShadow', false);
        $shadowBaseLanguage = $this->getRequestParameter($request, 'shadowBaseLanguage', null);
        $parent = $this->getRequestParameter($request, 'parent');
        $state = $this->getRequestParameter($request, 'state');
        if ($state !== null) {
            $state = intval($state);
        }

        $data = $request->request->all();

        if ($navigation === '0') {
            $navigation = false;
        } else {
            // default navigation
            $navigation = 'main';
        }

        $result = $this->getRepository()->saveNode(
            $data,
            $template,
            $webspace,
            $language,
            $this->getUser()->getId(),
            null, // uuid
            $parent,
            $state,
            $isShadow,
            $shadowBaseLanguage
        );

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * deletes node with given uuid
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $uuid)
    {
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);

        $view = $this->responseDelete(
            $uuid,
            function ($id) use ($language, $webspace) {
                try {
                    $this->getRepository()->deleteNode($id, $webspace, $language);
                } catch (ItemNotFoundException $ex) {
                    throw new EntityNotFoundException('Content', $id);
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * trigger a action for given node specified over get-action parameter
     * - move: moves a node
     *   + destination: specifies the destination node
     * - copy: copy a node
     *   + destination: specifies the destination node
     *
     * @Post("/nodes/{uuid}")
     * @param string $uuid
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postTriggerAction($uuid, Request $request)
    {
        // extract parameter
        $language = $this->getLanguage($request);
        $webspace = $this->getWebspace($request);
        $action = $this->getRequestParameter($request, 'action', true);
        $destination = $this->getRequestParameter($request, 'destination', true);
        $userId = $this->getUser()->getId();

        // prepare vars
        $repository = $this->getRepository();
        $view = null;
        $data = null;

        try {
            switch ($action) {
                case 'move':
                    // call repository method
                    $data = $repository->moveNode($uuid, $destination, $webspace, $language, $userId);
                    break;
                case 'copy':
                    // call repository method
                    $data = $repository->copyNode($uuid, $destination, $webspace, $language, $userId);
                    break;
                case 'order':
                    // call repository method
                    $data = $repository->orderBefore($uuid, $destination, $webspace, $language, $userId);
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // prepare view
            $view = $this->view($data, 200);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @return NodeRepositoryInterface
     */
    protected function getRepository()
    {
        return $this->get('sulu_content.node_repository');
    }
}
