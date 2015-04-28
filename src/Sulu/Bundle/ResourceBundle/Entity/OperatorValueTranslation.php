<?php

namespace Sulu\Bundle\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperatorValueTranslation
 */
class OperatorValueTranslation
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $shortDescription;

    /**
     * @var string
     */
    private $longDescription;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ResourceBundle\Entity\OperatorValue
     */
    private $operatorValue;


    /**
     * Set name
     *
     * @param string $name
     * @return OperatorValueTranslation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return OperatorValueTranslation
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set shortDescription
     *
     * @param string $shortDescription
     * @return OperatorValueTranslation
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * Get shortDescription
     *
     * @return string 
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Set longDescription
     *
     * @param string $longDescription
     * @return OperatorValueTranslation
     */
    public function setLongDescription($longDescription)
    {
        $this->longDescription = $longDescription;

        return $this;
    }

    /**
     * Get longDescription
     *
     * @return string 
     */
    public function getLongDescription()
    {
        return $this->longDescription;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set operatorValue
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorValue $operatorValue
     * @return OperatorValueTranslation
     */
    public function setOperatorValue(\Sulu\Bundle\ResourceBundle\Entity\OperatorValue $operatorValue)
    {
        $this->operatorValue = $operatorValue;

        return $this;
    }

    /**
     * Get operatorValue
     *
     * @return \Sulu\Bundle\ResourceBundle\Entity\OperatorValue 
     */
    public function getOperatorValue()
    {
        return $this->operatorValue;
    }
}
