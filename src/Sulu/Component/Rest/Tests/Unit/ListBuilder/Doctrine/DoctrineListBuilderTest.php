<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;
use Sulu\Component\Rest\Exception\InvalidSearchException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCountFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderCreateEvent;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderEvents;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeInterface;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\SinglePropertyMetadata;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DoctrineListBuilderTest extends TestCase
{
    use ProphecyTrait;
    use ReadObjectAttributeTrait;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $eventDispatcher;

    /**
     * @var ObjectProphecy<FilterTypeRegistry>
     */
    private $filterTypeRegistry;

    /**
     * @var DoctrineListBuilder
     */
    private $doctrineListBuilder;

    /**
     * @var ObjectProphecy<EntityManager>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<ClassMetadata>
     */
    private $classMetadata;

    /**
     * @var ObjectProphecy<QueryBuilder>
     */
    private $queryBuilder;

    /**
     * @var ObjectProphecy<AbstractQuery>
     */
    private $query;

    /**
     * @var ObjectProphecy<SystemStoreInterface>
     */
    private $systemStore;

    /**
     * @var \ReflectionMethod
     */
    private $findIdsByGivenCriteria;

    /**
     * Result of id subquery.
     *
     * @var array
     */
    private $idResult = [
        ['id' => '1'],
        ['id' => '2'],
        ['id' => '3'],
    ];

    private static $entityName = 'Sulu\Bundle\CoreBundle\Entity\Example';

    private static $entityNameAlias = 'Sulu_Bundle_CoreBundle_Entity_Example';

    private static $translationEntityName = 'Sulu\Bundle\CoreBundle\Entity\ExampleTranslation';

    private static $translationEntityNameAlias = 'Sulu_Bundle_CoreBundle_Entity_ExampleTranslation';

    public function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManager::class);
        $this->filterTypeRegistry = $this->prophesize(FilterTypeRegistry::class);
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
        $this->query = $this->prophesize(AbstractQuery::class);
        $this->classMetadata = $this->prophesize(ClassMetadata::class);

        $this->entityManager->createQueryBuilder()->willReturn($this->queryBuilder->reveal());
        $this->entityManager->getClassMetadata(Argument::any())
            ->willReturn($this->classMetadata->reveal());

        $this->queryBuilder->from(self::$entityName, self::$entityNameAlias)->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->select(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->addGroupBy()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->where(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setMaxResults(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->getQuery()->willReturn($this->query->reveal());
        $this->queryBuilder->getDQL()->willReturn('');

        $this->queryBuilder->distinct(false)->should(function() {});
        $this->queryBuilder->setParameter('ids', ['1', '2', '3'])->should(function() {});
        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldBeCalled();

        $this->query->getArrayResult()->willReturn($this->idResult);
        $this->query->getScalarResult()->willReturn([[3]]);

        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->systemStore = $this->prophesize(SystemStoreInterface::class);
        $this->systemStore->getSystem()->willReturn('Sulu');

        $this->doctrineListBuilder = new DoctrineListBuilder(
            $this->entityManager->reveal(),
            self::$entityName,
            $this->filterTypeRegistry->reveal(),
            $this->eventDispatcher->reveal(),
            [PermissionTypes::VIEW => 64],
            new AccessControlQueryEnhancer($this->systemStore->reveal(), $this->entityManager->reveal())
        );
        $this->doctrineListBuilder->limit(10);
        $this->queryBuilder->setFirstResult(Argument::any())->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setMaxResults(Argument::any())->willReturn($this->queryBuilder->reveal());

        $event = new ListBuilderCreateEvent($this->doctrineListBuilder);
        $this->eventDispatcher->dispatch($event, ListBuilderEvents::LISTBUILDER_CREATE)->willReturn($event);

        $doctrineListBuilderReflectionClass = new \ReflectionClass($this->doctrineListBuilder);
        $this->findIdsByGivenCriteria = $doctrineListBuilderReflectionClass->getMethod('findIdsByGivenCriteria');
        $this->findIdsByGivenCriteria->setAccessible(true);
    }

    public function testSetFields(): void
    {
        $this->doctrineListBuilder->setSelectFields(
            [
                new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName),
                new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName),
            ]
        );

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name_alias')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.desc AS desc_alias')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetFieldsWithStandardFieldDescriptor(): void
    {
        $this->doctrineListBuilder->setSelectFields(
            [
                new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName),
                new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName),
                new FieldDescriptor('test', 'test_alias', self::$entityName),
            ]
        );

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name_alias')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.desc AS desc_alias')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.test AS test_alias')->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testIdSelect(): void
    {
        $this->queryBuilder->select(self::$entityNameAlias . '.id AS id')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testPreselectWithNoJoins(): void
    {
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor(
                'name',
                'name_alias',
                self::$entityName,
                '',
                [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        self::$entityName . '.translations'
                    ),
                    'anotherEntityName' => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        'anotherEntityName.translations',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
                ]
            )
        );

        // no joins should be made
        $this->queryBuilder->leftJoin(Argument::cetera())->shouldNotBeCalled();
        $this->queryBuilder->innerJoin(Argument::cetera())->shouldNotBeCalled();

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testPreselectWithJoinsBecauseOfInnerJoin(): void
    {
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor(
                'name',
                'name_alias',
                self::$entityName,
                '',
                [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        self::$entityName . '.translations',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
                    'anotherEntityName' => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        'anotherEntityName.translations',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
                ]
            )
        );

        $this->queryBuilder->innerJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ''
        )->shouldBeCalled();

        $this->queryBuilder->innerJoin(
            'anotherEntityName.translations',
            'anotherEntityName',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ''
        )->shouldBeCalled();

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testPreselectWithConditions(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor(
            'name',
            'name_alias',
            'anotherEntityName',
            '',
            [
                self::$translationEntityName => new DoctrineJoinDescriptor(
                    self::$translationEntityName,
                    self::$entityName . '.translations'
                ),
                'anotherEntityName' => new DoctrineJoinDescriptor(
                    self::$translationEntityName,
                    'anotherEntityName.translations'),
            ]
        );

        $this->doctrineListBuilder->addSelectField($fieldDescriptor);
        $this->doctrineListBuilder->where($fieldDescriptor, 'test');

        $this->queryBuilder->andWhere(Argument::containingString('anotherEntityName.name = :name_alias'))->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name_alias'), 'test')->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ''
        )->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            'anotherEntityName.translations',
            'anotherEntityName',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            ''
        )->shouldBeCalled();

        $this->findIdsByGivenCriteria->invoke($this->doctrineListBuilder);
    }

    public function testAddField(): void
    {
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName));
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName));

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name_alias')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.desc AS desc_alias')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testAddStandardField(): void
    {
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName));
        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('desc', 'desc_alias', self::$entityName));
        $this->doctrineListBuilder->addSelectField(new FieldDescriptor('test', 'test_alias', self::$entityName));

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name_alias')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.desc AS desc_alias')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.test AS test_alias')->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testAddFieldWithJoin(): void
    {
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor(
                'desc', 'desc_alias', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName, self::$entityNameAlias . '.translations'
                    ),
                ]
            )
        );

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$translationEntityNameAlias . '.desc AS desc_alias')->shouldBeCalled();
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testAssignParametersForExecute(): void
    {
        $this->queryBuilder->getDQL()->willReturn('SELECT * FROM table WHERE locale = :locale AND parent = :parent');

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $this->doctrineListBuilder->setParameter('locale', 'de');
        $this->doctrineListBuilder->setParameter('parent', '7');
        $this->doctrineListBuilder->setParameter('webspace', 'sulu');

        $this->queryBuilder->setParameter('locale', 'de')->shouldBeCalled();
        $this->queryBuilder->setParameter('parent', '7')->shouldBeCalled();
        $this->queryBuilder->setParameter('webspace', Argument::any())->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testAssignParametersForCount(): void
    {
        $this->queryBuilder->getDQL()->willReturn('SELECT * FROM table WHERE locale = :locale AND parent = :parent');

        $this->doctrineListBuilder->addSelectField(new DoctrineFieldDescriptor('name', 'alias', self::$entityName));
        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();

        $this->doctrineListBuilder->setParameter('locale', 'de');
        $this->doctrineListBuilder->setParameter('parent', '7');
        $this->doctrineListBuilder->setParameter('webspace', 'sulu');

        $this->queryBuilder->setParameter('locale', 'de')->shouldBeCalled();
        $this->queryBuilder->setParameter('parent', '7')->shouldBeCalled();
        $this->queryBuilder->setParameter('webspace', Argument::any())->shouldNotBeCalled();

        $this->doctrineListBuilder->count();
    }

    public function testSearchFieldWithJoin(): void
    {
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor(
                'name', 'name', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName, self::$entityNameAlias . '.translations'
                    ),
                ]
            )
        );

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        // join is only needed in the preselect query, not in the main query. therefore it should be added a one time
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->shouldBeCalledTimes(1);

        $this->doctrineListBuilder->execute();
    }

    public function testWhereWithJoin(): void
    {
        $this->doctrineListBuilder->where(
            new DoctrineFieldDescriptor(
                'name', 'name', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName, self::$entityNameAlias . '.translations'
                    ),
                ]
            ),
            'test-name'
        );

        // join is only needed in the preselect query, not in the main query. therefore it should be added a one time
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->shouldBeCalledTimes(1);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $this->queryBuilder->andWhere(Argument::containingString('.name = :name'))->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name'), 'test-name')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSelectFieldWithJoin(): void
    {
        $this->doctrineListBuilder->addSelectField(
            new DoctrineFieldDescriptor(
                'name', 'name', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName, self::$entityNameAlias . '.translations'
                    ),
                ]
            )
        );

        // join is only needed in the main query, not in the preselect query. therefore it should be added a one time
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->shouldBeCalledTimes(1);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_ExampleTranslation.name AS name')->shouldBeCalledTimes(1);

        $this->doctrineListBuilder->execute();
    }

    public function testSortFieldWithJoin(): void
    {
        $this->doctrineListBuilder->sort(
            new DoctrineFieldDescriptor(
                'desc', 'desc_alias', self::$translationEntityName, 'translation', [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName, self::$entityName . '.translations'
                    ),
                ]
            )
        );

        // join should be added two times: one time in the preselect query and one time in the main query
        $this->queryBuilder->leftJoin(
            self::$entityNameAlias . '.translations',
            self::$translationEntityNameAlias,
            'WITH',
            ''
        )->shouldBeCalledTimes(2);

        $this->queryBuilder->getDQLPart('select')->willReturn([]);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        // will be called for preselect query
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_ExampleTranslation.desc AS desc_alias')->shouldBeCalled();
        // will be called for result (should not be displayed)
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_ExampleTranslation.desc AS HIDDEN desc_alias')->shouldBeCalled();
        $this->queryBuilder->addOrderBy('desc_alias', 'ASC')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSearch(): void
    {
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('desc', 'desc', self::$translationEntityName)
        );
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('name', 'name', self::$entityName)
        );
        $this->doctrineListBuilder->search('value');

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $this->queryBuilder->andWhere(
            '(' . self::$translationEntityNameAlias . '.desc LIKE :search OR ' . self::$entityNameAlias . '.name LIKE :search)'
        )->shouldBeCalled();
        $this->queryBuilder->setParameter('search', '%value%')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSearchWithPlaceholder(): void
    {
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('desc', 'desc', self::$translationEntityName)
        );
        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('name', 'name', self::$entityName)
        );

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $this->doctrineListBuilder->search('val*e');

        $this->queryBuilder->andWhere(
            '(' . self::$translationEntityNameAlias . '.desc LIKE :search OR ' . self::$entityNameAlias . '.name LIKE :search)'
        )->shouldBeCalled();
        $this->queryBuilder->setParameter('search', '%val%e%')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testFilter(): void
    {
        $filterType = $this->prophesize(FilterTypeInterface::class);
        $this->filterTypeRegistry->getFilterType('text')->willReturn($filterType->reveal());

        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name', self::$entityName);
        $nameMetadata = new SinglePropertyMetadata('name');
        $nameMetadata->setFilterType('text');
        $nameFieldDescriptor->setMetadata($nameMetadata);

        $this->doctrineListBuilder->setFieldDescriptors([
            'name' => $nameFieldDescriptor,
        ]);
        $this->doctrineListBuilder->filter(['name' => 'value']);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $filterType->filter($this->doctrineListBuilder, $nameFieldDescriptor, 'value')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSearchWithoutSearchFields(): void
    {
        $this->expectException(InvalidSearchException::class);

        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();

        $this->doctrineListBuilder->search('value');
        $this->doctrineListBuilder->execute();
    }

    public function testSort(): void
    {
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->getDQLPart('select')->willReturn([]);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        // will be called for result (should not be displayed)
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS HIDDEN desc')->shouldBeCalled();
        // will be called for id query
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')->shouldBeCalled();
        $this->queryBuilder->addOrderBy('desc', 'ASC')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSortWithExistingSelect(): void
    {
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->getDQLPart('select')->willReturn([new Select('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')]);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        // will NOT be called for result (should not be displayed)
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS HIDDEN desc')->shouldNotBeCalled();
        // will be called for id query
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')->shouldBeCalled();
        $this->queryBuilder->addOrderBy('desc', 'ASC')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    /**
     * Test if multiple calls to sort with same field descriptor will lead to multiple order by calls.
     */
    public function testSortWithMultipleSort(): void
    {
        $this->queryBuilder->getDQLPart('select')->willReturn([new Select('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')]);

        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName));

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')->shouldBeCalledTimes(1);
        $this->queryBuilder->addOrderBy('desc', 'ASC')->shouldBeCalledTimes(2);

        $this->doctrineListBuilder->execute();
    }

    /**
     * Test if sort is correnctly overwritten, when field descriptor is provided multiple times.
     */
    public function testChangeSortOrder(): void
    {
        $this->queryBuilder->getDQLPart('select')->willReturn([new Select('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')]);

        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName), 'ASC');
        $this->doctrineListBuilder->sort(new DoctrineFieldDescriptor('desc', 'desc', self::$entityName), 'DESC');

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.desc AS desc')->shouldBeCalledTimes(1);
        $this->queryBuilder->addOrderBy('desc', 'DESC')->shouldBeCalledTimes(2);

        $this->doctrineListBuilder->execute();
    }

    public function testSortWithoutDefault(): void
    {
        // when no sort is applied, results should be orderd by id by default
        $this->queryBuilder->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSortConcat(): void
    {
        $select = 'CONCAT(Sulu_Bundle_CoreBundle_Entity_Example.name, CONCAT(\' \', Sulu_Bundle_CoreBundle_Entity_Example.desc)) AS name_desc';

        $this->doctrineListBuilder->sort(new DoctrineConcatenationFieldDescriptor(
            [
                new DoctrineFieldDescriptor('name', 'name', self::$entityName),
                new DoctrineFieldDescriptor('desc', 'desc', self::$entityName),
            ],
            'name_desc'
        ));

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect($select)->shouldBeCalled();

        $selectExpression = $this->prophesize(Select::class);
        $selectExpression->getParts()->willReturn([$select]);
        $this->queryBuilder->getDQLPart('select')->willReturn([$selectExpression->reveal()]);

        $this->doctrineListBuilder->execute();

        $this->queryBuilder->addOrderBy('name_desc', 'ASC')->shouldHaveBeenCalledTimes(2);
    }

    public function testLimit(): void
    {
        $this->doctrineListBuilder->limit(5);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->setMaxResults(5)->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->setFirstResult(0)->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetIds(): void
    {
        $this->doctrineListBuilder->setIds([11, 22]);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('id'), [11, 22])->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetIdsEmpty(): void
    {
        $this->doctrineListBuilder->setIds([]);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString(' IS NULL')
        )->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetIdsNull(): void
    {
        $this->doctrineListBuilder->setIds(null);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetExcludedIds(): void
    {
        $this->doctrineListBuilder->setExcludedIds([55, 99]);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('id'), [55, 99])->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('NOT(Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetExcludedIdsEmpty(): void
    {
        $this->doctrineListBuilder->setExcludedIds([]);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('NOT(Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetExcludedIdsNull(): void
    {
        $this->doctrineListBuilder->setExcludedIds(null);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('NOT(Sulu_Bundle_CoreBundle_Entity_Example.id IN (:id')
        )->shouldNotBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testCount(): void
    {
        $this->doctrineListBuilder->setSelectFields(
            [
                new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName),
                new DoctrineFieldDescriptor(
                    'desc', 'desc_alias', self::$translationEntityName, 'translation', [
                        self::$translationEntityName => new DoctrineJoinDescriptor(
                            self::$translationEntityName, self::$entityName . '.translations'
                        ),
                    ]
                ),
            ]
        );

        $this->doctrineListBuilder->addSearchField(
            new DoctrineFieldDescriptor('desc', 'desc', self::$translationEntityName)
        );
        $this->doctrineListBuilder->search('value');

        $this->doctrineListBuilder->limit(5);

        $this->queryBuilder->andWhere(Argument::cetera())->shouldBeCalled();
        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();
        $this->queryBuilder->leftJoin(Argument::cetera())->shouldBeCalledTimes(1);
        $this->queryBuilder->setParameter(Argument::cetera())->shouldBeCalledTimes(1);
        $this->queryBuilder->setMaxResults(Argument::cetera())->shouldNotBeCalled();
        $this->queryBuilder->setFirstResult(Argument::cetera())->shouldNotBeCalled();

        $this->doctrineListBuilder->count();
    }

    public function testSetWhereWithSameName(): void
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
            'desc_id' => new DoctrineFieldDescriptor('id', 'desc_id', self::$entityName),
        ];

        $filter = [
            'title_id' => 3,
            'desc_id' => 1,
        ];

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS desc_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title'), 3)->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('desc'), 1)->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id = :title_id')
        )->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id = :desc_id')
        )->shouldBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value);
        }

        $this->assertCount(2, $this->readObjectAttribute($this->doctrineListBuilder, 'expressions'));
        $expressions = $this->readObjectAttribute($this->doctrineListBuilder, 'expressions');
        $this->assertEquals(3, $expressions[0]->getValue());
        $this->assertEquals(1, $expressions[1]->getValue());

        $this->assertCount(2, $this->readObjectAttribute($this->doctrineListBuilder, 'expressions'));
        $this->assertEquals('title_id', $expressions[0]->getFieldName());
        $this->assertEquals('desc_id', $expressions[1]->getFieldName());
        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereWithNull(): void
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
        ];

        $filter = [
            'title_id' => null,
        ];

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), Argument::any())->shouldNotBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value);
        }

        $this->queryBuilder->andWhere('(Sulu_Bundle_CoreBundle_Entity_Example.id IS NULL)')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereWithNotNull(): void
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
        ];

        $filter = [
            'title_id' => null,
        ];

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), Argument::any())->shouldNotBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value, ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL);
        }

        $this->queryBuilder->andWhere('(Sulu_Bundle_CoreBundle_Entity_Example.id IS NOT NULL)')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetWhereNot(): void
    {
        $fieldDescriptors = [
            'title_id' => new DoctrineFieldDescriptor('id', 'title_id', self::$entityName),
            'desc_id' => new DoctrineFieldDescriptor('id', 'desc_id', self::$entityName),
        ];

        $filter = [
            'title_id' => 3,
            'desc_id' => 1,
        ];

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS desc_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), 3)->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('desc_id'), 1)->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id != :title_id')
        )->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id != :desc_id')
        )->shouldBeCalled();

        foreach ($filter as $key => $value) {
            $this->doctrineListBuilder->addSelectField($fieldDescriptors[$key]);
            $this->doctrineListBuilder->where($fieldDescriptors[$key], $value, ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL);
        }

        $this->assertCount(2, $this->readObjectAttribute($this->doctrineListBuilder, 'expressions'));
        $expressions = $this->readObjectAttribute($this->doctrineListBuilder, 'expressions');
        $this->assertEquals(3, $expressions[0]->getValue());
        $this->assertEquals(1, $expressions[1]->getValue());

        $this->assertCount(2, $this->readObjectAttribute($this->doctrineListBuilder, 'expressions'));
        $this->assertEquals('title_id', $expressions[0]->getFieldName());
        $this->assertEquals('desc_id', $expressions[1]->getFieldName());
        $this->doctrineListBuilder->execute();
    }

    public function testSetIn(): void
    {
        $fieldDescriptor = new DoctrineFieldDescriptor('id', 'title_id', self::$entityName);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS title_id')->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('title_id'), [1, 2])->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.id IN (:title_id')
        )->shouldBeCalled();

        $this->doctrineListBuilder->addSelectField($fieldDescriptor);
        $this->doctrineListBuilder->in($fieldDescriptor, [1, 2]);

        $this->doctrineListBuilder->execute();
    }

    public function testJoinMethods(): void
    {
        $fieldDescriptors = [
            'id1' => new DoctrineFieldDescriptor(
                '',
                '',
                '',
                '',
                [
                    'a' => new DoctrineJoinDescriptor('a', 'a.test', '', DoctrineJoinDescriptor::JOIN_METHOD_LEFT),
                ]
            ),
            'id2' => new DoctrineFieldDescriptor(
                '',
                '',
                '',
                '',
                [
                    'b' => new DoctrineJoinDescriptor('b', 'b.test', '', DoctrineJoinDescriptor::JOIN_METHOD_INNER),
                ]
            ),
        ];

        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);

        $this->queryBuilder->addSelect('. AS ')->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        // not necessary for id join
        $this->queryBuilder->leftJoin('a.test', 'a', 'WITH', '')->shouldBeCalled();
        // called when select ids and for selecting data
        $this->queryBuilder->innerJoin('b.test', 'b', 'WITH', '')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testJoinWithoutFieldName(): void
    {
        $fieldDescriptors = [
            'name' => new DoctrineFieldDescriptor(
                'name',
                'name',
                self::$entityName,
                '',
                [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        null,
                        'alias.id = translation.id'
                    ),
                ]
            ),
        ];

        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name')->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            self::$translationEntityName,
            self::$translationEntityNameAlias,
            'WITH',
            'alias.id = translation.id'
        )->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testJoinWithoutFieldNameByGivenEntity(): void
    {
        $fieldDescriptors = [
            'name' => new DoctrineFieldDescriptor(
                'name',
                'name',
                self::$entityName,
                '',
                [
                    self::$translationEntityName => new DoctrineJoinDescriptor(
                        self::$translationEntityName,
                        self::$translationEntityName,
                        'alias.id = translation.id'
                    ),
                ]
            ),
        ];

        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.name AS name')->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            self::$translationEntityName,
            self::$translationEntityNameAlias,
            'WITH',
            'alias.id = translation.id'
        )->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testJoinConditions(): void
    {
        $fieldDescriptors = [
            'id1' => new DoctrineFieldDescriptor(
                '',
                '',
                '',
                '',
                [
                    self::$entityName . '1' => new DoctrineJoinDescriptor(
                        self::$entityName . '1',
                        null,
                        'field1 = value1',
                        DoctrineJoinDescriptor::JOIN_METHOD_LEFT
                    ),
                ]
            ),
            'id2' => new DoctrineFieldDescriptor(
                '',
                '',
                '',
                '',
                [
                    self::$entityName . '2' => new DoctrineJoinDescriptor(
                        self::$entityName . '2',
                        null,
                        'field2 = value2',
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER,
                        DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON
                    ),
                ]
            ),
        ];
        $this->doctrineListBuilder->setSelectFields($fieldDescriptors);
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('. AS ')->shouldBeCalled();
        $this->queryBuilder->leftJoin(
            self::$entityName . '1',
            self::$entityNameAlias . '1',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_WITH,
            'field1 = value1'
        )->shouldBeCalled();
        $this->queryBuilder->innerJoin(
            self::$entityName . '2',
            self::$entityNameAlias . '2',
            DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON,
            'field2 = value2'
        )->shouldBeCalled();
        $this->doctrineListBuilder->execute();
    }

    public function testGroupBy(): void
    {
        $this->queryBuilder->addOrderBy(Argument::cetera())->shouldNotBeCalled();

        $queryBuilder1 = $this->prophesize(QueryBuilder::class);
        $query1 = $this->prophesize(AbstractQuery::class);
        $queryBuilder2 = $this->prophesize(QueryBuilder::class);
        $query2 = $this->prophesize(AbstractQuery::class);
        $queryBuilder3 = $this->prophesize(QueryBuilder::class);
        $query3 = $this->prophesize(AbstractQuery::class);
        $this->entityManager->createQueryBuilder()->willReturn(
            $queryBuilder1->reveal(),
            $queryBuilder2->reveal(),
            $queryBuilder3->reveal()
        );

        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName);
        $countFieldDescriptor = new DoctrineCountFieldDescriptor('id', 'count', self::$entityName);

        $query1->getArrayResult()->willReturn([
            [
                'id' => 1,
            ],
            [
                'id' => 2,
            ],
        ]);
        $queryBuilder1->getDQL()->willReturn('');
        $queryBuilder1->getQuery()->willReturn($query1->reveal());
        $queryBuilder1->from(self::$entityName, self::$entityNameAlias)->willReturn($queryBuilder1->reveal());
        $queryBuilder1->setMaxResults(10)->willReturn($queryBuilder1->reveal());
        $queryBuilder1->setFirstResult(0)->willReturn($queryBuilder1->reveal());
        $queryBuilder1->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($queryBuilder1->reveal());
        $queryBuilder1->select(self::$entityNameAlias . '.id AS id')->shouldBeCalled()->willReturn($queryBuilder1->reveal());

        $query2->getArrayResult()->willReturn([
            [
                'id' => 1,
                'name' => 'Test 1',
            ],
            [
                'id' => 2,
                'name' => 'Test 2',
            ],
        ]);
        $queryBuilder2->getDQL()->willReturn('');
        $queryBuilder2->getQuery()->willReturn($query2->reveal());
        $queryBuilder2->from(self::$entityName, self::$entityNameAlias)->willReturn($queryBuilder2->reveal());
        $queryBuilder2->distinct(false)->willReturn($queryBuilder2->reveal());
        $queryBuilder2->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($queryBuilder2->reveal());
        $queryBuilder2->addSelect(self::$entityNameAlias . '.name AS name_alias')->shouldBeCalled()->willReturn($queryBuilder2->reveal());
        $queryBuilder2->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled()->willReturn($queryBuilder2->reveal());
        $queryBuilder2->where(self::$entityNameAlias . '.id IN (:ids)')->shouldBeCalled()->willReturn($queryBuilder2->reveal());
        $queryBuilder2->setParameter('ids', [1, 2])->shouldBeCalled()->willReturn($queryBuilder2->reveal());

        $query3->getArrayResult()->willReturn([
            1 => [
                'id' => 1,
                'count' => 10,
            ],
            2 => [
                'id' => 2,
                'count' => 20,
            ],
        ]);
        $queryBuilder3->getDQL()->willReturn('');
        $queryBuilder3->getQuery()->willReturn($query3->reveal());
        $queryBuilder3->from(self::$entityName, self::$entityNameAlias)->willReturn($queryBuilder3->reveal());
        $queryBuilder3->distinct(false)->willReturn($queryBuilder3->reveal());
        $queryBuilder3->addSelect('COUNT(' . self::$entityNameAlias . '.id) AS count')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->where(self::$entityNameAlias . '.id IN (:ids)')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->setParameter('ids', [1, 2])->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->addGroupBy(self::$entityNameAlias . '.name')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->indexBy(self::$entityNameAlias, self::$entityNameAlias . '.id')->shouldBeCalled()->willReturn($queryBuilder3->reveal());
        $queryBuilder3->addOrderBy(self::$entityNameAlias . '.id', 'ASC')->willReturn($queryBuilder3->reveal());

        $this->doctrineListBuilder->setSelectFields([
            $nameFieldDescriptor,
            $countFieldDescriptor,
        ]);

        $this->doctrineListBuilder->addGroupBy($nameFieldDescriptor);

        $result = $this->doctrineListBuilder->execute();
        $this->assertSame([
            [
                'id' => 1,
                'name' => 'Test 1',
                'count' => 10,
            ],
            [
                'id' => 2,
                'name' => 'Test 2',
                'count' => 20,
            ],
        ], $result);
    }

    public function testBetween(): void
    {
        $nameFieldDescriptor = new DoctrineFieldDescriptor('name', 'name_alias', self::$entityName);

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();
        $this->queryBuilder->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.name AS name_alias')->shouldBeCalled();
        $this->queryBuilder->andWhere(
            Argument::containingString('Sulu_Bundle_CoreBundle_Entity_Example.name BETWEEN :name_alias')
        )->shouldBeCalledTimes(1);
        $this->queryBuilder->setParameter(Argument::containingString('name_alias'), 0)->shouldBeCalled();
        $this->queryBuilder->setParameter(Argument::containingString('name_alias'), 1)->shouldBeCalled();

        $this->doctrineListBuilder->setSelectFields(
            [
                $nameFieldDescriptor,
            ]
        );

        $this->doctrineListBuilder->between($nameFieldDescriptor, [0, 1]);

        $this->doctrineListBuilder->execute();
    }

    public function testDistinct(): void
    {
        $this->doctrineListBuilder->distinct(true);

        $this->queryBuilder->distinct(true)->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testNoDistinct(): void
    {
        $this->queryBuilder->distinct(false)->shouldBeCalled();

        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testIdField(): void
    {
        $idField = $this->prophesize(DoctrineFieldDescriptorInterface::class);
        $idField->getSelect()->willReturn('example.id');
        $idField->getName()->willReturn('id');

        $this->doctrineListBuilder->setIdField($idField->reveal());

        $this->queryBuilder->select('example.id AS id')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->addSelect('example.id AS id')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->where('example.id IN (:ids)')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testIdFieldChanged(): void
    {
        $idField = $this->prophesize(DoctrineFieldDescriptorInterface::class);
        $idField->getSelect()->willReturn('example.uuid');
        $idField->getName()->willReturn('other');

        $this->doctrineListBuilder->setIdField($idField->reveal());
        $this->query->getArrayResult()->willReturn([
            [
                'other' => 1,
            ],
            [
                'other' => 2,
            ],
            [
                'other' => 3,
            ],
        ]);

        $this->queryBuilder->addSelect('example.uuid AS other')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder->where('example.uuid IN (:ids)')->shouldBeCalled()->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testNoIdField(): void
    {
        $this->queryBuilder
            ->addSelect('Sulu_Bundle_CoreBundle_Entity_Example.id AS id')
            ->shouldBeCalled()
            ->willReturn($this->queryBuilder->reveal());
        $this->queryBuilder
            ->where('Sulu_Bundle_CoreBundle_Entity_Example.id IN (:ids)')
            ->shouldBeCalled()
            ->willReturn($this->queryBuilder->reveal());

        $this->doctrineListBuilder->execute();
    }

    public function testSetPermissionCheck(): void
    {
        $user = $this->prophesize(User::class);
        $role = $this->prophesize(Role::class);
        $role->getId()->willReturn(1);
        $role->getSystem()->willReturn('Sulu');
        $user->getRoleObjects()->willReturn([$role->reveal()]);

        $this->doctrineListBuilder->setPermissionCheck($user->reveal(), PermissionTypes::VIEW);

        $accessQueryBuilder = $this->prophesize(QueryBuilder::class);
        $this->entityManager->createQueryBuilder()->willReturn(
            $this->queryBuilder->reveal(),
            $accessQueryBuilder->reveal(),
            $this->queryBuilder->reveal()
        );

        $accessQueryBuilder->from(self::$entityName, 'entity')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->select('entity.id')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $accessQueryBuilder->setParameter('entityClass', self::$entityName)->shouldBeCalled();

        $accessQueryBuilder->innerJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            'accessControl.entityClass = :entityClass AND accessControl.entityId = entity.id'
        )->shouldBeCalled();

        $accessQueryBuilder->innerJoin('accessControl.role', 'role')->shouldBeCalled();

        $accessQueryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) <> :permission AND accessControl.permissions IS NOT NULL'
        )->shouldBeCalled();

        $accessQueryBuilder->andWhere('role.id IN(:roleIds)')->shouldBeCalled();

        $accessQueryBuilder->setParameter('roleIds', [1])->shouldBeCalled();
        $accessQueryBuilder->setParameter('permission', 64)->shouldBeCalled();

        $accessQuery = $this->prophesize(AbstractQuery::class);
        $accessQueryBuilder->getQuery()->willReturn($accessQuery->reveal());
        $accessQuery->getScalarResult()->willReturn([['id' => 42]]);

        $this->queryBuilder->andWhere('Sulu_Bundle_CoreBundle_Entity_Example.id NOT IN (:accessControlIds)')
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('accessControlIds', [42])->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetPermissionCheckIntIdentifier(): void
    {
        $user = $this->prophesize(User::class);
        $role = $this->prophesize(Role::class);
        $role->getId()->willReturn(1);
        $role->getSystem()->willReturn('Sulu');
        $user->getRoleObjects()->willReturn([$role->reveal()]);

        $this->doctrineListBuilder->setPermissionCheck($user->reveal(), PermissionTypes::VIEW);

        $accessQueryBuilder = $this->prophesize(QueryBuilder::class);
        $this->entityManager->createQueryBuilder()->willReturn(
            $this->queryBuilder->reveal(),
            $accessQueryBuilder->reveal(),
            $this->queryBuilder->reveal()
        );

        $this->classMetadata->getTypeOfField('id')
            ->willReturn('integer')
            ->shouldBeCalled();

        $accessQueryBuilder->from(self::$entityName, 'entity')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->select('entity.id')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $accessQueryBuilder->setParameter('entityClass', self::$entityName)->shouldBeCalled();

        $accessQueryBuilder->innerJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            'accessControl.entityClass = :entityClass AND accessControl.entityIdInteger = entity.id'
        )->shouldBeCalled();

        $accessQueryBuilder->innerJoin('accessControl.role', 'role')->shouldBeCalled();

        $accessQueryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) <> :permission AND accessControl.permissions IS NOT NULL'
        )->shouldBeCalled();

        $accessQueryBuilder->andWhere('role.id IN(:roleIds)')->shouldBeCalled();

        $accessQueryBuilder->setParameter('roleIds', [1])->shouldBeCalled();
        $accessQueryBuilder->setParameter('permission', 64)->shouldBeCalled();

        $accessQuery = $this->prophesize(AbstractQuery::class);
        $accessQueryBuilder->getQuery()->willReturn($accessQuery->reveal());
        $accessQuery->getScalarResult()->willReturn([['id' => 42]]);

        $this->queryBuilder->andWhere('Sulu_Bundle_CoreBundle_Entity_Example.id NOT IN (:accessControlIds)')
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('accessControlIds', [42])->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetPermissionCheckWithSecuredEntityName(): void
    {
        $user = $this->prophesize(User::class);
        $role = $this->prophesize(Role::class);
        $role->getId()->willReturn(1);
        $role->getSystem()->willReturn('Sulu');
        $user->getRoleObjects()->willReturn([$role->reveal()]);

        $this->doctrineListBuilder->setPermissionCheck($user->reveal(), PermissionTypes::VIEW, \stdClass::class);

        $accessQueryBuilder = $this->prophesize(QueryBuilder::class);
        $this->entityManager->createQueryBuilder()->willReturn(
            $this->queryBuilder->reveal(),
            $accessQueryBuilder->reveal(),
            $this->queryBuilder->reveal()
        );

        $accessQueryBuilder->from(\stdClass::class, 'entity')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->select('entity.id')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $accessQueryBuilder->setParameter('entityClass', \stdClass::class)->shouldBeCalled();

        $accessQueryBuilder->innerJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            'accessControl.entityClass = :entityClass AND accessControl.entityId = entity.id'
        )->shouldBeCalled();

        $accessQueryBuilder->innerJoin('accessControl.role', 'role')->shouldBeCalled();

        $accessQueryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) <> :permission AND accessControl.permissions IS NOT NULL'
        )->shouldBeCalled();

        $accessQueryBuilder->andWhere('role.id IN(:roleIds)')->shouldBeCalled();

        $accessQueryBuilder->setParameter('roleIds', [1])->shouldBeCalled();
        $accessQueryBuilder->setParameter('permission', 64)->shouldBeCalled();

        $accessQuery = $this->prophesize(AbstractQuery::class);
        $accessQueryBuilder->getQuery()->willReturn($accessQuery->reveal());
        $accessQuery->getScalarResult()->willReturn([['id' => 42]]);

        $this->queryBuilder->andWhere(\stdClass::class . '.id NOT IN (:accessControlIds)')
            ->shouldBeCalled();

        $this->queryBuilder->setParameter('accessControlIds', [42])->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }

    public function testSetPermissionCheckWithSecuredEntityNameAndAdditionalJoins(): void
    {
        $user = $this->prophesize(User::class);
        $role = $this->prophesize(Role::class);
        $role->getId()->willReturn(1);
        $role->getSystem()->willReturn('Sulu');
        $user->getRoleObjects()->willReturn([$role->reveal()]);

        $joinFieldDescriptor = $this->prophesize(DoctrineJoinDescriptor::class);
        $joinFieldDescriptor->getEntityName()->willReturn('MyTest');
        $joinFieldDescriptor->getJoin()->willReturn('stdClass.myTest');
        $joinFieldDescriptor->getJoinMethod()->willReturn(DoctrineJoinDescriptor::JOIN_METHOD_LEFT);
        $joinFieldDescriptor->getJoinConditionMethod()->willReturn(DoctrineJoinDescriptor::JOIN_CONDITION_METHOD_ON);
        $joinFieldDescriptor->getJoinCondition()->willReturn('stdClass.id = MyTest.id');

        $permissionCheckField = $this->prophesize(DoctrineFieldDescriptor::class);
        $permissionCheckField->getEntityName()->willReturn('MyTest');
        $permissionCheckField->getJoins()->willReturn(['MyTest' => $joinFieldDescriptor->reveal()]);

        $this->doctrineListBuilder->setPermissionCheck($user->reveal(), PermissionTypes::VIEW, \stdClass::class);
        $this->doctrineListBuilder->addPermissionCheckField($permissionCheckField->reveal());

        $accessQueryBuilder = $this->prophesize(QueryBuilder::class);
        $this->entityManager->createQueryBuilder()->willReturn(
            $this->queryBuilder->reveal(),
            $accessQueryBuilder->reveal(),
            $this->queryBuilder->reveal()
        );

        $accessQueryBuilder->from(\stdClass::class, 'entity')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());

        $accessQueryBuilder->select('entity.id')
            ->shouldBeCalled()
            ->willReturn($accessQueryBuilder->reveal());
        $this->queryBuilder->addSelect(self::$entityNameAlias . '.id AS id')->shouldBeCalled();

        $accessQueryBuilder->setParameter('entityClass', \stdClass::class)->shouldBeCalled();

        $accessQueryBuilder->innerJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            'accessControl.entityClass = :entityClass AND accessControl.entityId = entity.id'
        )->shouldBeCalled();

        $accessQueryBuilder->innerJoin('accessControl.role', 'role')->shouldBeCalled();

        $accessQueryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) <> :permission AND accessControl.permissions IS NOT NULL'
        )->shouldBeCalled();

        $accessQueryBuilder->andWhere('role.id IN(:roleIds)')->shouldBeCalled();

        $accessQueryBuilder->setParameter('roleIds', [1])->shouldBeCalled();
        $accessQueryBuilder->setParameter('permission', 64)->shouldBeCalled();

        $accessQuery = $this->prophesize(AbstractQuery::class);
        $accessQueryBuilder->getQuery()->willReturn($accessQuery->reveal());
        $accessQuery->getScalarResult()->willReturn([['id' => 42]]);

        $this->queryBuilder->andWhere(\stdClass::class . '.id NOT IN (:accessControlIds)')
            ->shouldBeCalled();

        $this->queryBuilder->leftJoin(
            'stdClass.myTest',
            'MyTest',
            'ON',
            'stdClass.id = MyTest.id'
        )->shouldBeCalled();

        $this->queryBuilder->setParameter('accessControlIds', [42])->shouldBeCalled();

        $this->doctrineListBuilder->execute();
    }
}
