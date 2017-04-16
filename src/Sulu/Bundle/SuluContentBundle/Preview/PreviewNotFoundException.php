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

use Sulu\Component\Rest\Exception\RestException;

/**
 * This exception is thrown when someone tries to access a preview for a content/user-combination, which does not exist
 * @package Sulu\Bundle\ContentBundle\Preview
 */
class PreviewNotFoundException extends RestException
{
    /**
     * The id of the user
     * @var int
     */
    private $userId;

    /**
     * The uuid of the content
     * @var string
     */
    private $contentUuid;

    function __construct($userId, $contentUuid)
    {
        parent::__construct(printf('Preview of user %s and content %s not found', $userId, $contentUuid));

        $this->contentUuid = $contentUuid;
        $this->userId = $userId;
    }

    /**
     * Returns the UUID of the content, for which no preview was found
     * @return string
     */
    public function getContentUuid()
    {
        return $this->contentUuid;
    }

    /**
     * Returns the id of the user, for which no preview was found
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
}
