<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Mocks;

use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;

/**
 * The SuluContentBundle uses static methods on its interfaces to allow for accessing class related information
 * without needing an instance of the class (eg. @see TemplateInterface::getTemplateType). Unfortunately it is not
 * possible to mock calls to static methods in PHP (https://phpunit.readthedocs.io/en/9.0/test-doubles.html).
 *
 * Therefore, when testing a service that calls a static method of a given object (eg. a TemplateInterface mock),
 * we need to wrap the mock into a wrapper-class that implements the static method that is called. If we dont
 * do this, accessing the static method will fail with a "Error: Using $this when not in object context" message.
 *
 * Along with the traits in this namespace, this class provides a simple way for composing a wrapper-class that
 * that implements specific interfaces. For example, @see TemplateDataMapperTest::wrapTemplateMock to learn how to
 * compose such a wrapper-class.
 *
 * @template-covariant T of object
 */
class MockWrapper
{
    /**
     * @var T
     */
    protected $instance;

    /**
     * @param ObjectProphecy<T> $configuredMock
     */
    public function __construct(ObjectProphecy $configuredMock)
    {
        $this->instance = $configuredMock->reveal();
    }
}
