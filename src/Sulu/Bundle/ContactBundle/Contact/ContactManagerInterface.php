<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Contact;

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\ContactBundle\Entity\Address;

/**
 * Defines functionality of ContactManger.
 *
 * @template DoctrineEntity
 * @template ApiEntity
 * @template AddressRelationEntity
 */
interface ContactManagerInterface
{
    /**
     * adds an address to the entity.
     *
     * @param DoctrineEntity $entity The entity to add the address to
     * @param Address $address The address to be added
     * @param bool $isMain Defines if the address is the main Address of the contact
     *
     * @return AddressRelationEntity $relation
     */
    public function addAddress($entity, Address $address, $isMain);

    /**
     * removes the address relation from a contact and also deletes the address if it has no more relations.
     *
     * @param string $entity
     * @param AddressRelationEntity $address
     *
     * @return void
     */
    public function removeAddressRelation($entity, $address);

    /**
     * Returns a collection of relations to get addresses.
     *
     * @param DoctrineEntity $entity
     *
     * @return iterable<AddressRelationEntity>
     */
    public function getAddressRelations($entity);

    /**
     * sets the first element to main, if none is set.
     *
     * @param Collection<AddressRelationEntity> $arrayCollection
     *
     * @return void
     */
    public function setMainForCollection($arrayCollection);

    /**
     * unsets main of all elements of an ArrayCollection | PersistanceCollection.
     *
     * @param Collection<AddressRelationEntity> $arrayCollection
     *
     * @return bool returns true if a element was unset
     * @return void
     */
    public function unsetMain($arrayCollection);

    /**
     * sets main email, based on emails that are set on entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function setMainEmail($entity);

    /**
     * sets main fax, based on faxes that are set on entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function setMainFax($entity);

    /**
     * sets main url, based on urls that are set on entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function setMainUrl($entity);

    /**
     * sets main phone, based on phones that are set on entity.
     *
     * @param DoctrineEntity $entity
     *
     * @return void
     */
    public function setMainPhone($entity);

    /**
     * Returns an api entity.
     *
     * @param int $id
     * @param string $locale
     *
     * @return ApiEntity
     */
    public function getById($id, $locale);

    /**
     * Returns api entities.
     *
     * @param array $ids
     * @param string $locale
     *
     * @return ApiEntity[]
     */
    public function getByIds($ids, $locale);
}
