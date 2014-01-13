<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Sulu\Component\Content\StructureInterface;
use Symfony\Component\HttpFoundation\Response;

interface PreviewInterface
{
    /**
     * starts a preview for given user and content
     * @param int $userId
     * @param string $contentUuid
     * @param string $workspaceKey
     * @param string $languageCode
     * @return StructureInterface
     */
    public function startPreview($userId, $contentUuid, $workspaceKey, $languageCode);

    /**
     * saves changes for given user and content
     * @param int $userId
     * @param string $contentUuid
     * @param string $property propertyName which was changed
     * @param mixed $data new data
     * @return string
     */
    public function update($userId, $contentUuid, $property, $data);

    /**
     * renders a content for given user
     * @param int $userId
     * @param string $contentUuid
     * @param string|null $property
     * @return string
     */
    public function render($userId, $contentUuid, $property = null);
} 
