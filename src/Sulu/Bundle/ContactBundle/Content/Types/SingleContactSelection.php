<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Content\Types;

use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleContactSelection extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var ContactRepositoryInterface
     */
    protected $contactRepository;

    public function __construct(
        ContactRepositoryInterface $contactRepository,
        private ReferenceStoreInterface $contactReferenceStore
    ) {
        $this->contactRepository = $contactRepository;

        parent::__construct('SingleContact');
    }

    public function getContentData(PropertyInterface $property): ?ContactInterface
    {
        $id = $property->getValue();

        if (!$id) {
            return null;
        }

        return $this->contactRepository->findById($id);
    }

    public function preResolve(PropertyInterface $property)
    {
        $id = $property->getValue();
        if (!$id) {
            return;
        }

        $this->contactReferenceStore->add($id);
    }
}
