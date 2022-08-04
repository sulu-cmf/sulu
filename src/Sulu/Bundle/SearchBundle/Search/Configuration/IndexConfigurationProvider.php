<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Configuration;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides IndexConfigurations from the Symfony configuration stored in the container.
 */
class IndexConfigurationProvider implements IndexConfigurationProviderInterface
{
    /**
     * @var array|null
     */
    private $indexConfigurations = null;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $searchIndexes;

    public function __construct(TranslatorInterface $translator, array $searchIndexes)
    {
        $this->translator = $translator;
        $this->searchIndexes = $searchIndexes;
    }

    /**
     * Returns all IndexConfigurations available in this installation.
     *
     * @return IndexConfiguration[]
     */
    public function getIndexConfigurations()
    {
        if (null === $this->indexConfigurations) {
            foreach ($this->searchIndexes as $indexName => $indexConfiguration) {
                $this->indexConfigurations[$indexName] = new IndexConfiguration(
                    $indexName,
                    $indexConfiguration['icon'],
                    $this->translator->trans($indexConfiguration['name'], [], 'admin'),
                    new Route($indexConfiguration['view']['name'], $indexConfiguration['view']['result_to_view']),
                    isset($indexConfiguration['security_context']) ? $indexConfiguration['security_context'] : null,
                    isset($indexConfiguration['contexts']) ? $indexConfiguration['contexts'] : []
                );
            }
        }

        return $this->indexConfigurations;
    }

    /**
     * Returns the IndexConfiguration for the index with the given name.
     *
     * @param string $name The name of the index to get the IndexConfiguration from
     *
     * @return IndexConfiguration
     */
    public function getIndexConfiguration($name)
    {
        $this->indexConfigurations = $this->getIndexConfigurations();

        if (!\array_key_exists($name, $this->indexConfigurations)) {
            return;
        }

        return $this->indexConfigurations[$name];
    }
}
