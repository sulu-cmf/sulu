<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Controller\Exception\ConstraintViolationException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagAlreadyExistsException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagNotFoundException;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Makes tag available through.
 */
class TagController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityName = \Sulu\Bundle\TagBundle\Entity\Tag::class;

    /**
     * @deprecated Use the TagInterface::RESOURCE_KEY constant instead
     */
    protected static $entityKey = 'tags';

    protected $unsortable = [];

    protected $bundlePrefix = 'tags.';

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private RestHelperInterface $restHelper,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private TagManagerInterface $tagManager,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $router,
        private string $tagClass
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * Returns a single tag with the given id.
     *
     * @return Response
     */
    public function getAction($id)
    {
        $view = $this->responseGetById(
            $id,
            function($id) {
                return $this->tagManager->findById($id);
            }
        );

        $context = new Context();
        $context->setGroups(['partialTag']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * returns all tags.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        if ('true' == $request->get('flat')) {
            $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('tags');
            $listBuilder = $this->listBuilderFactory->create($this->tagClass);

            $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

            $names = \array_filter(\explode(',', $request->get('names', '')));
            if (\count($names) > 0) {
                $listBuilder->in($fieldDescriptors['name'], $names);
                $listBuilder->limit(\count($names));
            }

            $list = new ListRepresentation(
                $listBuilder->execute(),
                TagInterface::RESOURCE_KEY,
                'sulu_tag.get_tags',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
            $view = $this->view($list, 200);
        } else {
            $list = new CollectionRepresentation($this->tagManager->findAll(), TagInterface::RESOURCE_KEY);

            $context = new Context();
            $context->setGroups(['partialTag']);
            $view = $this->view($list, 200)->setContext($context);
        }

        return $this->handleView($view);
    }

    /**
     * Inserts a new tag.
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function postAction(Request $request)
    {
        $name = $request->get('name');

        try {
            if (null == $name) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            $tag = $this->tagManager->save($this->getData($request));

            $context = new Context();
            $context->setGroups(['partialTag']);
            $view = $this->view($tag)->setContext($context);
        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException(
                'A tag with the name "' . $exc->getName() . '"already exists!',
                'name',
                ConstraintViolationException::EXCEPTION_CODE_NON_UNIQUE_NAME
            );
            $view = $this->view($cvExistsException->toArray(), 400);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the tag with the given ID.
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function putAction(Request $request, $id)
    {
        $name = $request->get('name');

        try {
            if (null == $name) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            $tag = $this->tagManager->save($this->getData($request), $id);

            $context = new Context();
            $context->setGroups(['partialTag']);
            $view = $this->view($tag)->setContext($context);
        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException(
                'A tag with the name "' . $exc->getName() . '"already exists!',
                'name',
                ConstraintViolationException::EXCEPTION_CODE_NON_UNIQUE_NAME
            );
            $view = $this->view($cvExistsException->toArray(), 400);
        } catch (TagNotFoundException $exc) {
            $entityNotFoundException = new EntityNotFoundException(self::$entityName, $id);
            $view = $this->view($entityNotFoundException->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the tag with the given ID.
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $delete = function($id) {
            try {
                $this->tagManager->delete($id);
            } catch (TagNotFoundException $tnfe) {
                throw new EntityNotFoundException(self::$entityName, $id, $tnfe);
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * POST Route annotation.
     *
     * @return Response
     */
    public function postMergeAction(Request $request)
    {
        try {
            $srcTagIds = \explode(',', $request->get('src'));
            $destTagId = $request->get('dest');

            $destTag = $this->tagManager->merge($srcTagIds, $destTagId);

            $view = $this->view(
                null,
                303,
                [
                    'location' => $this->router->generate('sulu_tag.get_tag', ['id' => $destTag->getId()]),
                ]
            );
        } catch (TagNotFoundException $exc) {
            $entityNotFoundException = new EntityNotFoundException(self::$entityName, $exc->getId());
            $view = $this->view($entityNotFoundException->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * @return Response
     */
    public function cpatchAction(Request $request)
    {
        try {
            $tags = [];

            $i = 0;
            while ($item = $request->get($i)) {
                if (isset($item['id'])) {
                    $tags[] = $this->tagManager->save($item, $item['id']);
                } else {
                    $tags[] = $this->tagManager->save($item);
                }
                ++$i;
            }
            $this->entityManager->flush();

            $context = new Context();
            $context->setGroups(['partialTag']);
            $view = $this->view($tags)->setContext($context);
        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException(
                'A tag with the name "' . $exc->getName() . '"already exists!',
                'name',
                ConstraintViolationException::EXCEPTION_CODE_NON_UNIQUE_NAME
            );
            $view = $this->view($cvExistsException->toArray(), 400);
        }

        return $this->handleView($view);
    }

    public function getSecurityContext()
    {
        return TagAdmin::SECURITY_CONTEXT;
    }

    /**
     * Get data.
     *
     * @return array
     */
    protected function getData(Request $request)
    {
        return $request->request->all();
    }
}
