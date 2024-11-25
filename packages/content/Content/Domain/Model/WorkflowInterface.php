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

namespace Sulu\Bundle\ContentBundle\Content\Domain\Model;

interface WorkflowInterface
{
    // See https://github.com/sulu/SuluContentBundle/pull/53 for graphic about workflow

    public const WORKFLOW_PLACE_UNPUBLISHED = 'unpublished'; // was never published or set to review

    public const WORKFLOW_PLACE_REVIEW = 'review'; // unpublished changes are in review

    public const WORKFLOW_PLACE_PUBLISHED = 'published'; // is published

    public const WORKFLOW_PLACE_DRAFT = 'draft'; // published but has a draft data

    public const WORKFLOW_PLACE_REVIEW_DRAFT = 'review_draft'; // published but has draft data in review

    public const WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW = 'request_for_review';

    public const WORKFLOW_TRANSITION_REJECT = 'reject';

    public const WORKFLOW_TRANSITION_PUBLISH = 'publish';

    public const WORKFLOW_TRANSITION_UNPUBLISH = 'unpublish';

    public const WORKFLOW_TRANSITION_CREATE_DRAFT = 'create_draft';

    public const WORKFLOW_TRANSITION_REMOVE_DRAFT = 'remove_draft';

    public const WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT = 'request_for_review_draft';

    public const WORKFLOW_TRANSITION_REJECT_DRAFT = 'reject_draft';

    public const WORKFLOW_DEFAULT_NAME = 'content_workflow';

    public static function getWorkflowName(): string;

    public function getWorkflowPlace(): ?string;

    public function setWorkflowPlace(?string $workflowPlace): void;

    public function getWorkflowPublished(): ?\DateTimeImmutable;

    public function setWorkflowPublished(?\DateTimeImmutable $workflowPublished): void;
}
