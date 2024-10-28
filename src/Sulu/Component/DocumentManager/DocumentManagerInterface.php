<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\Query\QueryInterface;
use Sulu\Component\DocumentManager\Query\Query;

interface DocumentManagerInterface
{
    /**
     * Find a document by path or UUID in the given locale, optionally enforcing the given type.
     *
     * @param string $identifier Path or UUID
     * @param string $locale Locale
     * @param array<string, mixed> $options
     *
     * @return object
     *
     * @throws Exception\DocumentManagerException
     */
    public function find($identifier, $locale = null, array $options = []);

    /**
     * Create a new document instance for the given alias.
     *
     * @param string $alias
     *
     * @return object
     *
     * @throws Exception\MetadataNotFoundException
     */
    public function create($alias);

    /**
     * Persist a document to a PHPCR node.
     *
     * @param object $document
     * @param string $locale
     * @param array<string, mixed> $options
     *
     * @return void
     */
    public function persist($document, $locale = null, array $options = []);

    /**
     * Remove the document. The document should be unregistered and the related PHPCR node should be removed from the
     * session.
     *
     * @param object $document
     *
     * @return void
     */
    public function remove($document/*, array $options = []*/);

    /**
     * Remove a specific locale of a document.
     *
     * @param object $document
     * @param string $locale
     *
     * @return void
     */
    public function removeLocale($document, $locale);

    /**
     * Move the PHPCR node to which the document is mapped to be a child of the node at the given path or UUID.
     *
     * @param object $document
     * @param string $destId The path of the new parent
     *
     * @return void
     */
    public function move($document, $destId);

    /**
     * Create a copy of the node representing the given document at the given path.
     *
     * @param object $document
     * @param string $destPath
     *
     * @return string|null
     */
    public function copy($document, $destPath);

    /**
     * Copy a specific locale of a document.
     *
     * @param object $document
     * @param string $srcLocale
     * @param string $destLocale
     *
     * @return void
     */
    public function copyLocale($document, $srcLocale, $destLocale);

    /**
     * Re-Order node before or after a specific node.
     * If the destId is null, then the document will be sorted at the end.
     *
     * @param object $document
     * @param string|null $destId
     *
     * @return void
     */
    public function reorder($document, $destId);

    /**
     * Sorting the siblings of the document.
     */
    public function sort(object $document, string $locale): void;

    /**
     * Publishes a document to the public workspace.
     *
     * @param object $document
     * @param string $locale
     * @param array<string, mixed> $options
     *
     * @return void
     */
    public function publish($document, $locale = null, array $options = []);

    /**
     * Unpublishes a document from the public workspace.
     *
     * @param object $document
     * @param string $locale
     *
     * @return void
     */
    public function unpublish($document, $locale);

    /**
     * Removes the draft for the given document and reverts it to the values from the public workspace.
     *
     * @param object $document
     * @param string $locale
     *
     * @return void
     */
    public function removeDraft($document, $locale);

    /**
     * Restores the given version of the document and makes it a new version keeping the linear approach.
     *
     * @param object $document
     * @param string $locale
     * @param string $version The UUID of the version to restore
     * @param array<string, mixed> $options
     *
     * @return void
     *
     * @throws Exception\VersionNotFoundException
     */
    public function restore($document, $locale, $version, array $options = []);

    /**
     * Refresh the given document with the persisted state of the node.
     *
     * @param object $document
     *
     * @return void
     */
    public function refresh($document);

    /**
     * Persist changes to the persistent storage.
     *
     * @return void
     */
    public function flush();

    /**
     * Clear the document manager, should reset the underlying PHPCR session and deregister all documents.
     *
     * @return void
     */
    public function clear();

    /**
     * Create a new query from a JCR-SQL2 query string.
     *
     * NOTE: This should not be used generally as it exposes the database structure and breaks abstraction. Use the
     *       domain-aware query builder instead.
     *
     * @param string|QueryInterface $query Either a JCR-SQL2 string, or a PHPCR query object
     * @param string $locale
     * @param array<string, mixed> $options
     *
     * @return Query
     */
    public function createQuery($query, $locale = null, array $options = []);
}
