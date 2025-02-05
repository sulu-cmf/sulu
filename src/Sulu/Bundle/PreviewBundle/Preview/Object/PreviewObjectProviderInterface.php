<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Object;

/**
 * Interface for preview-object-provider.
 */
interface PreviewObjectProviderInterface
{
    /**
     * Returns object with given id and locale.
     *
     * @param string $id
     * @param string $locale
     */
    public function getObject($id, $locale);

    /**
     * Returns id for given object.
     *
     * @return string
     */
    public function getId($object);

    /**
     * Set given data to the object.
     *
     * @param string $locale
     * @param array<string, mixed> $data
     */
    public function setValues($object, $locale, array $data);

    /**
     * Set given context to the object.
     *
     * @param string $locale
     * @param array<string, mixed> $context
     *
     * @return mixed New object which will be saved for the session
     */
    public function setContext($object, $locale, array $context);

    /**
     * Serializes object to string.
     *
     * @return string
     */
    public function serialize($object);

    /**
     * Deserializes object to string.
     *
     * @param string $serializedObject
     * @param string $objectClass
     */
    public function deserialize($serializedObject, $objectClass);

    /**
     * @param string $id
     * @param string $locale
     */
    public function getSecurityContext($id, $locale): ?string;
}
