<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

class AccountMediaAddedEvent extends DomainEvent
{
    private \Sulu\Bundle\ContactBundle\Entity\AccountInterface $account;

    private \Sulu\Bundle\MediaBundle\Entity\MediaInterface $media;

    public function __construct(AccountInterface $account, MediaInterface $media)
    {
        parent::__construct();

        $this->account = $account;
        $this->media = $media;
    }

    public function getAccount(): AccountInterface
    {
        return $this->account;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }

    public function getEventType(): string
    {
        return 'media_added';
    }

    public function getEventContext(): array
    {
        $fileVersionMeta = $this->getFileVersionMeta();

        return [
            'mediaId' => $this->media->getId(),
            'mediaTitle' => $fileVersionMeta ? $fileVersionMeta->getTitle() : null,
            'mediaTitleLocale' => $fileVersionMeta ? $fileVersionMeta->getLocale() : null,
        ];
    }

    public function getResourceKey(): string
    {
        return AccountInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->account->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->account->getName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return ContactAdmin::ACCOUNT_SECURITY_CONTEXT;
    }

    private function getFileVersionMeta(): ?FileVersionMeta
    {
        $file = $this->media->getFiles()[0] ?? null;
        $fileVersion = $file ? $file->getLatestFileVersion() : null;

        return $fileVersion ? $fileVersion->getDefaultMeta() : null;
    }
}
