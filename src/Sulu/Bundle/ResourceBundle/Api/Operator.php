<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Api;

use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Hateoas\Configuration\Annotation\Relation;
use Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Bundle\ResourceBundle\Entity\Operator as OperatorEntity;

/**
 * The Operator class which will be exported to the API
 *
 * @package Sulu\Bundle\ResourceBundle\Api
 * @Relation("self", href="expr('/admin/api/operators/' ~ object.getId())")
 * @ExclusionPolicy("all")
 */
class Operator extends ApiWrapper
{
    /**
     * @param OperatorEntity $entity
     * @param string $locale
     */
    public function __construct(OperatorEntity $entity, $locale)
    {
        $this->entity = $entity;
        $this->locale = $locale;
    }

    /**
     * Returns the name of the operator
     *
     * @return string The name of the operator
     * @VirtualProperty
     * @SerializedName("name")
     */
    public function getName()
    {
        return $this->getTranslation()->getName();
    }

    /**
     * Sets the name of the operator
     * @param string $name The name of the operator
     */
    public function setName($name)
    {
        $this->getTranslation()->setName($name);
    }

    /**
     * Get translation by locale
     *
     * @return OperatorTranslation
     */
    private function getTranslation()
    {
        $operatorTranslation = null;
        foreach ($this->entity->getTranslations() as $translation) {
            if ($translation->getLocale() == $this->locale) {
                $operatorTranslation = $translation;
            }
        }
        if (!$operatorTranslation) {
            $operatorTranslation = new OperatorTranslation();
            $operatorTranslation->setLocale($this->locale);
            $operatorTranslation->setOperator($this->entity);
            $this->entity->addTranslation($operatorTranslation);
        }

        return $operatorTranslation;
    }

    /**
     * Set operator
     *
     * @param string $operator
     * @return Operator
     */
    public function setOperator($operator)
    {
        $this->entity->setOperator($operator);
    }

    /**
     * Get operator
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("operator")
     */
    public function getOperator()
    {
        return $this->entity->getOperator();
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Operator
     */
    public function setType($type)
    {
        $this->entity->setType($type);
    }

    /**
     * Get type
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("type")
     */
    public function getType()
    {
        return $this->entity->getType();
    }

    /**
     * Set inputType
     *
     * @param string $inputType
     * @return Operator
     */
    public function setInputType($inputType)
    {
        $this->entity->setInputType($inputType);
    }

    /**
     * Get inputType
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("inputType")
     */
    public function getInputType()
    {
        return $this->entity->getInputType();
    }

    /**
     * Get id
     *
     * @return integer
     * @VirtualProperty
     * @SerializedName("id")
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Add translations
     *
     * @param OperatorTranslation $translations
     * @return Operator
     */
    public function addTranslation(OperatorTranslation $translations)
    {
        $this->entity->addTranslation($translations);
    }

    /**
     * Remove translations
     *
     * @param OperatorTranslation $translations
     */
    public function removeTranslation(OperatorTranslation $translations)
    {
        $this->entity->removeTranslation($translations);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->entity->getTranslations();
    }

    /**
     * Add values
     *
     * @param OperatorValue $value
     * @return Operator
     */
    public function addValue(OperatorValue $value)
    {
        $this->entity->addValue($value->getEntity());
    }

    /**
     * Remove values
     *
     * @param OperatorValue $value
     */
    public function removeValue(OperatorValue $value)
    {
        $this->entity->removeValue($value->getEntity());
    }

    /**
     * Get values
     *
     * @return \Doctrine\Common\Collections\Collection
     * @VirtualProperty
     * @SerializedName("values")
     */
    public function getValues()
    {
        $values = $this->entity->getValues();
        $result = [];
        if ($values) {
            foreach ($values as $value) {
                $result[] = new OperatorValue($value, $this->locale);
            }

            return $values;
        }

        return null;
    }
}
