<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

/**
 * This exception is thrown when the username is not unique.
 */
class UsernameNotUniqueException extends SecurityException implements TranslationErrorMessageExceptionInterface
{
    /**
     * @param string $username
     */
    public function __construct(private $username)
    {
        parent::__construct('a username has to be unique!', 1001);
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_security.username_assigned_to_other_user';
    }

    public function getMessageTranslationParameters(): array
    {
        return ['{username}' => $this->username];
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'username' => $this->username,
        ];
    }
}
