<?php
namespace Sulu\Component\Rest;

use JMS\Serializer\Annotation\Exclude;

/**
 * The abstract base class for an API object, which wraps another entity.
 */
class ApiWrapper
{
    /**
     * the entity which is wrapped by this class.
     *
     * @var object
     * @Exclude
     */
    protected $entity;

    /**
     * the locale in which the wrapped entity should be expressed.
     *
     * @var string
     * @Exclude
     */
    protected $locale;

    public function getEntity()
    {
        return $this->entity;
    }
}
