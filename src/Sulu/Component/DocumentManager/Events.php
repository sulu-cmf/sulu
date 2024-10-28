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

class Events
{
    /**
     * Fired when a document is persisted (mapped to a PHPCR node).
     *
     * Fired both when a document is updated and when it is persisted
     * for the first time.
     */
    public const PERSIST = 'sulu_document_manager.persist';

    /**
     * Fired when a node is hydrated (a PHPCR node is mapped to a document).
     */
    public const HYDRATE = 'sulu_document_manager.hydrate';

    /**
     * Fired when a document is removed via the document manager.
     */
    public const REMOVE = 'sulu_document_manager.remove';

    /**
     * Fired when a document localization is removed via the document manager.
     */
    public const REMOVE_LOCALE = 'sulu_document_manager.remove_locale';

    /**
     * Fired when a document localization is copied via the document manager.
     */
    public const COPY_LOCALE = 'sulu_document_manager.copy_locale';

    /**
     * Fired when a document should be refreshed.
     */
    public const REFRESH = 'sulu_document_manager.refresh';

    /**
     * Fired when a document is copied via the document manager.
     */
    public const COPY = 'sulu_document_manager.copy';

    /**
     * Fired when a document is moved via the document manager.
     */
    public const MOVE = 'sulu_document_manager.move';

    /**
     * Fired when a document is created via the document manager.
     *
     * NOTE: This event is NOT fired when a node is persisted for the first time,
     *       it is fired when a NEW INSTANCE of a document is created from the document
     *       manager, look at the PERSIST event instead.
     */
    public const CREATE = 'sulu_document_manager.create';

    /**
     * Fired when the document manager should be cleared (i.e. detach all documents).
     */
    public const CLEAR = 'sulu_document_manager.clear';

    /**
     * Fired when the document manager find method is called.
     */
    public const FIND = 'sulu_document_manager.find';

    /**
     * Fired when the document manager reorder method is called.
     */
    public const REORDER = 'sulu_document_manager.reorder';

    /**
     * Fired when the document manager sort method is called.
     */
    public const SORT = 'sulu_document_manager.sort';

    /**
     * Fired when the document manager publish method is called.
     */
    public const PUBLISH = 'sulu_document_manager.publish';

    /**
     * Fired when the document manager unpublish method is called.
     */
    public const UNPUBLISH = 'sulu_document_manager.unpublish';

    /**
     * Fired when the document discardDraft method is called.
     */
    public const REMOVE_DRAFT = 'sulu_document_manager.remove_draft';

    /**
     * Fired when the document manager requests that are flush to persistent storage happen.
     */
    public const FLUSH = 'sulu_document_manager.flush';

    /**
     * Fired when a query should be created from a query string.
     */
    public const QUERY_CREATE = 'sulu_document_manager.query.create';

    /**
     * Fired when a new query builder should be created.
     */
    public const QUERY_CREATE_BUILDER = 'sulu_document_manager.query.create_builder';

    /**
     * Fired when a PHPCR query should be executed.
     */
    public const QUERY_EXECUTE = 'sulu_document_manager.query.execute';

    /**
     * Enables subscribers to define options.
     */
    public const CONFIGURE_OPTIONS = 'sulu_document_manager.configure_options';

    /**
     * Enables fields to be added to the mapping.
     */
    public const METADATA_LOAD = 'sulu_document_manager.metadata_load';

    /**
     * Fired when an old version of the document should be restored.
     */
    public const RESTORE = 'sulu_document_manager.restore';
}
