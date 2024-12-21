<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Tests\Unit\EventSubscriber\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Persistence\EventSubscriber\ORM\TimestampableSubscriber;
use Sulu\Component\Persistence\Model\TimestampableInterface;

class TimestampableSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<LoadClassMetadataEventArgs>
     */
    private $loadClassMetadataEvent;

    /**
     * @var ObjectProphecy<LifecycleEventArgs<EntityManager>>
     */
    private $lifecycleEvent;

    /**
     * @var ObjectProphecy<\stdClass>
     */
    private $timestampableObject;

    /**
     * @var ObjectProphecy<ClassMetadata>
     */
    private $classMetadata;

    /**
     * @var ObjectProphecy<\ReflectionClass>
     */
    private $refl;

    /**
     * @var ObjectProphecy<EntityManager>
     */
    private $entityManager;

    /**
     * @var TimestampableSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->loadClassMetadataEvent = $this->prophesize(LoadClassMetadataEventArgs::class);
        $this->lifecycleEvent = $this->prophesize(LifecycleEventArgs::class);
        $this->timestampableObject = $this->prophesize(\stdClass::class)
            ->willImplement(TimestampableInterface::class);
        $this->classMetadata = $this->prophesize(ClassMetadata::class);
        $this->refl = $this->prophesize(\ReflectionClass::class);
        $this->entityManager = $this->prophesize(EntityManager::class);

        $this->subscriber = new TimestampableSubscriber();
    }

    public function testLoadClassMetadata(): void
    {
        $this->loadClassMetadataEvent->getClassMetadata()->willReturn($this->classMetadata->reveal());
        $this->classMetadata->getReflectionClass()->willReturn($this->refl->reveal());
        $this->refl->implementsInterface(TimestampableInterface::class)->willReturn(true);

        $this->classMetadata->mapField(Argument::any())->shouldBeCalled();
        $this->classMetadata->hasField('created')->willReturn(false);
        $this->classMetadata->hasField('changed')->willReturn(false);

        $this->subscriber->loadClassMetadata($this->loadClassMetadataEvent->reveal());
    }

    public static function provideOnPreUpdate()
    {
        return [
            [null],
            [new \DateTime('2015-01-01')],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideOnPreUpdate')]
    public function testOnPreUpdate($created): void
    {
        $entity = $this->timestampableObject->reveal();
        $this->lifecycleEvent->getObject()->willReturn($this->timestampableObject->reveal());
        $this->lifecycleEvent->getObjectManager()->willReturn($this->entityManager->reveal());
        $this->entityManager->getClassMetadata(\get_class($entity))->willReturn($this->classMetadata);

        $this->classMetadata->getFieldValue($entity, 'created')->willReturn($created);

        if (null === $created) {
            $this->classMetadata->setFieldValue(
                $entity,
                'created',
                Argument::type('\DateTime')
            )->shouldBeCalled();
        } else {
            $this->classMetadata->setFieldValue(Argument::any())->shouldNotBeCalled();
        }

        $this->classMetadata->setFieldValue(
            $entity,
            'changed',
            Argument::type('\DateTime')
        )->shouldBeCalled();

        $this->subscriber->preUpdate($this->lifecycleEvent->reveal());
    }
}
