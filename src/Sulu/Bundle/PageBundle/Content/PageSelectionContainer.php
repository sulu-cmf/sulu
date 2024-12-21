<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Content;

use JMS\Serializer\Annotation\Exclude;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Container for PageSelection, holds the config for a internal links, and lazy loads the structures.
 */
class PageSelectionContainer implements ArrayableInterface
{
    /**
     * The content mapper, which is needed for lazy loading.
     *
     * @var ContentQueryExecutorInterface
     */
    #[Exclude]
    private $contentQueryExecutor;

    /**
     * The content mapper, which is needed for lazy loading.
     *
     * @var ContentQueryBuilderInterface
     */
    #[Exclude]
    private $contentQueryBuilder;

    /**
     * The params to load.
     *
     * @var array
     */
    #[Exclude]
    private $params;

    /**
     * The key of the webspace.
     *
     * @var string
     */
    #[Exclude]
    private $webspaceKey;

    /**
     * The code of the language.
     *
     * @var string
     */
    #[Exclude]
    private $languageCode;

    /**
     * @var string[]
     */
    private $ids;

    /**
     * @var StructureInterface[]
     */
    #[Exclude]
    private $data;

    /**
     * @var bool
     */
    private $showDrafts;

    /**
     * @var array
     */
    private $permission;

    /**
     * @var array
     */
    private $enabledTwigAttributes = [];

    public function __construct(
        $ids,
        ContentQueryExecutorInterface $contentQueryExecutor,
        ContentQueryBuilderInterface $contentQueryBuilder,
        $params,
        $webspaceKey,
        $languageCode,
        $showDrafts,
        $permission = null,
        array $enabledTwigAttributes = [
            'path' => true,
        ]
    ) {
        $this->ids = $ids;
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->webspaceKey = $webspaceKey;
        $this->languageCode = $languageCode;
        $this->params = $params;
        $this->showDrafts = $showDrafts;
        $this->permission = $permission;
        $this->enabledTwigAttributes = $enabledTwigAttributes;

        if ($enabledTwigAttributes['path'] ?? true) {
            @trigger_deprecation('sulu/sulu', '2.3', 'Enabling the "path" parameter is deprecated.');
        }
    }

    /**
     * Lazy loads the data based on the filter criteria from the config.
     *
     * @return StructureInterface[]
     */
    public function getData()
    {
        if (null === $this->data) {
            $this->data = $this->loadData();
        }

        return $this->data;
    }

    /**
     * lazy load data.
     */
    private function loadData()
    {
        $result = [];
        if (null !== $this->ids && \count($this->ids) > 0) {
            $this->contentQueryBuilder->init(
                [
                    'ids' => $this->ids,
                    'properties' => (isset($this->params['properties']) ? $this->params['properties']->getValue() : []),
                    'published' => !$this->showDrafts,
                ]
            );
            $pages = $this->contentQueryExecutor->execute(
                $this->webspaceKey,
                [$this->languageCode],
                $this->contentQueryBuilder,
                true,
                -1,
                null,
                null,
                false,
                $this->permission
            );

            // init vars
            $map = [];

            // map pages
            foreach ($pages as $page) {
                if (!($this->enabledTwigAttributes['path'] ?? true)) {
                    unset($page['path']);
                }

                $map[$page['id']] = $page;
            }

            foreach ($this->ids as $id) {
                if (isset($map[$id])) {
                    $result[] = $map[$id];
                }
            }
        }

        return $result;
    }

    /**
     * magic getter.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'data':
                return $this->getData();
        }

        return;
    }

    /**
     * magic isset.
     */
    public function __isset($name)
    {
        return 'data' == $name;
    }

    public function toArray($depth = null)
    {
        return ['ids' => $this->ids];
    }
}
