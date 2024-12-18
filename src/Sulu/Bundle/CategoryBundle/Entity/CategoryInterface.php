<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Interface for the extensible Category entity.
 */
interface CategoryInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'categories';

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CategoryInterface
     */
    public function setId($id);

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return CategoryInterface
     */
    public function setLft($lft);

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft();

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return CategoryInterface
     */
    public function setRgt($rgt);

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt();

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return CategoryInterface
     */
    public function setDepth($depth);

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth();

    /**
     * Get key.
     *
     * @return string|null
     */
    public function getKey();

    /**
     * Set key.
     *
     * @param string|null $key
     *
     * @return CategoryInterface
     */
    public function setKey($key);

    /**
     * Set defaultLocale.
     *
     * @param string $defaultLocale
     *
     * @return CategoryInterface
     */
    public function setDefaultLocale($defaultLocale);

    /**
     * Get defaultLocale.
     *
     * @return string
     */
    public function getDefaultLocale();

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Add meta.
     *
     * @return CategoryInterface
     */
    public function addMeta(CategoryMetaInterface $meta);

    /**
     * Remove meta.
     *
     * @return void
     */
    public function removeMeta(CategoryMetaInterface $meta);

    /**
     * Get meta.
     *
     * @return Collection<int, CategoryMetaInterface>
     */
    public function getMeta();

    /**
     * Add translations.
     *
     * @return CategoryInterface
     */
    public function addTranslation(CategoryTranslationInterface $translations);

    /**
     * Remove translations.
     *
     * @return void
     */
    public function removeTranslation(CategoryTranslationInterface $translations);

    /**
     * Get translations.
     *
     * @return Collection<int, CategoryTranslationInterface>
     */
    public function getTranslations();

    /**
     * Get single meta by locale or false if does not exists.
     *
     * @param ?string $locale
     *
     * @return CategoryTranslationInterface|false
     */
    public function findTranslationByLocale($locale);

    /**
     * Add children.
     *
     * @return CategoryInterface
     */
    public function addChild(self $child);

    /**
     * Remove children.
     *
     * @return void
     */
    public function removeChild(self $child);

    /**
     * Get children.
     *
     * @return Collection<int, CategoryInterface>
     */
    public function getChildren();

    /**
     * Set parent.
     *
     * @return CategoryInterface
     */
    public function setParent(?self $parent = null);

    /**
     * Get parent.
     *
     * @return CategoryInterface|null
     */
    public function getParent();

    /**
     * Set creator.
     * Note: This property is set automatically by the UserBlameSubscriber if not set manually.
     *
     * @return CategoryInterface
     */
    public function setCreator(?UserInterface $creator = null);

    /**
     * Set created.
     * Note: This property is set automatically by the TimestampableSubscriber if not set manually.
     *
     * @return CategoryInterface
     */
    public function setCreated(\DateTime $created);

    /**
     * Set changer.
     * Note: This property is set automatically by the UserBlameSubscriber if not set manually.
     *
     * @return CategoryInterface
     */
    public function setChanger(?UserInterface $changer = null);

    /**
     * Set changed.
     * Note: This property is set automatically by the TimestampableSubscriber if not set manually.
     *
     * @return CategoryInterface
     */
    public function setChanged(\DateTime $changed);
}
