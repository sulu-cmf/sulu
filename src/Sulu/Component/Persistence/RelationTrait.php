<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence;

/**
 * Offers methods for easier handling of relations.
 */
trait RelationTrait
{
    /**
     * This method processes a put request (delete non-existing entities, update existing entities, add new
     * entries), and let the single actions be modified by callbacks.
     *
     * @param \Traversable $entities The list of entities to work on
     * @param array $requestEntities The entities as retrieved from the request
     * @param callable $get Return id of entity
     *
     * @return bool
     */
    public function processSubEntities(
        $entities,
        array $requestEntities,
        callable $get,
        ?callable $add = null,
        ?callable $update = null,
        ?callable $delete = null
    ) {
        // compare id with with $get callback
        $compareFunction = function($entity, $data) use ($get) {
            return isset($data['id']) && $data['id'] == $get($entity);
        };
        // define a matching function
        $matchFunction = function($entity, $requestEntities, &$matchedEntry, &$matchedKey) use ($compareFunction) {
            $this->findMatchByCallback($entity, $requestEntities, $compareFunction, $matchedEntry, $matchedKey);
        };

        return $this->compareData($entities, $requestEntities, $matchFunction, $add, $update, $delete);
    }

    /**
     * Compares entities with data array and calls the given callbacks.
     *
     * @param \Traversable $entities The list of entities to work on
     * @param array $requestEntities The entities as retrieved from the request
     * @param callable $compare return true if data matches entity
     *
     * @return bool
     */
    public function compareEntitiesWithData(
        $entities,
        array $requestEntities,
        callable $compare,
        ?callable $add = null,
        ?callable $update = null,
        ?callable $delete = null
    ) {
        // define a matching function
        $matchFunction = function($entity, $requestEntities, &$matchedEntry, &$matchedKey) use ($compare) {
            $this->findMatchByCallback($entity, $requestEntities, $compare, $matchedEntry, $matchedKey);
        };

        return $this->compareData($entities, $requestEntities, $matchFunction, $add, $update, $delete);
    }

    /**
     * Applies a given compare function to a given set of data entries. Returns the entity itself and its key with the
     * $matchedEntry and $matchKey parameters.
     *
     * @param \Traversable $entity The entity to compare
     * @param array $requestEntities The set of entities to search in
     * @param callable $compare Compare function, which defines if data matches the entity
     * @param array $matchedEntry
     * @param string $matchedKey
     *
     * @return void
     */
    protected function findMatchByCallback($entity, $requestEntities, callable $compare, &$matchedEntry, &$matchedKey)
    {
        $matchedEntry = null;
        $matchedKey = null;
        if (!empty($requestEntities)) {
            foreach ($requestEntities as $key => $data) {
                if ($compare($entity, $data)) {
                    $matchedEntry = $data;
                    $matchedKey = $key;
                    break;
                }
            }
        }
    }

    /**
     * function compares entities with data of array and makes callback.
     *
     * @param \Traversable $entities
     *
     * @return bool
     */
    public function compareData(
        $entities,
        array $requestEntities,
        ?callable $compare = null,
        ?callable $add = null,
        ?callable $update = null,
        ?callable $delete = null
    ) {
        $success = true;

        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $matchedEntry = null;
                $matchedKey = null;

                // find match callback
                $compare($entity, $requestEntities, $matchedEntry, $matchedKey);

                if (null == $matchedEntry && null != $delete) {
                    // delete entity if it is not listed anymore
                    $delete($entity);
                } elseif (null != $update) {
                    // update entity if it is matched
                    $success = $update($entity, $matchedEntry);
                    if (!$success) {
                        break;
                    }
                }

                // Remove done element from array
                if (null !== $matchedKey) {
                    unset($requestEntities[$matchedKey]);
                }
            }
        }

        // The entity which have not been delete or updated have to be added
        if (!empty($requestEntities) && null != $add) {
            foreach ($requestEntities as $entity) {
                if (!$success) {
                    break;
                }
                $success = $add($entity);
            }
        }

        return $success;
    }
}
