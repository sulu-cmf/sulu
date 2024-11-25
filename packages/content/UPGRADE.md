# Upgrade

## 0.9.0

### ContentResolver renamed to ContentAggregator

The ContentResolver was renamed to ContentAggregator to better reflect its purpose. The service and the interface
were renamed from `ContentResolver` and `ContentResolverInterface` to `ContentAggregator` and `ContentAggregatorInterface`.

The service has been renamed from `sulu_content.content_resolver` to `sulu_content.content_aggregator`.

Furthermore, the method `resolve` was renamed to `aggregate`.

**Before**

```php
public function resolve(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface;
```

**After**

```php
public function aggregate(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface;
```

## 0.8.0

### Require PHP 8.0

The SuluContentBundle now requires at least PHP 8.0.

## 0.7.0

### Route property name forced to `url`

The `route` property requires now to be named `url` to make things easier accessible
for different parts of the system.

### Add shadowLocale and shadowLocales fields

To support shadow feature of sulu shadowLocale and shadowLocales fields need to be added to
the dimension content tables.

```sql
ALTER TABLE test_example_dimension_contents ADD shadowLocale VARCHAR(7) DEFAULT NULL, ADD shadowLocales JSON DEFAULT NULL; -- replace `test_example_dimension_contents` with your table
```

### Add ghostLocale and availableLocales field

To support multi localization feature of sulu a ghostLocale need to be added to
the dimension content tables.

```sql
ALTER TABLE test_example_dimension_contents ADD ghostLocale VARCHAR(7) DEFAULT NULL, ADD availableLocales JSON DEFAULT NULL; -- replace `test_example_dimension_contents` with your table

-- Update ghostLocale:
UPDATE test_example_dimension_contents dc -- replace `test_example_dimension_contents` with your table
INNER JOIN test_example_dimension_contents dc2 -- replace `test_example_dimension_contents` with your table
    ON dc2.stage = dc.stage
        AND dc2.example_id = dc.example_id -- replace `example_id` with your relation
        AND dc2.locale IS NOT NULL
SET dc.ghostLocale = dc2.locale
WHERE
    dc.ghostLocale IS NULL
    AND dc.locale IS NULL;

-- Update availableLocales:
UPDATE test_example_dimension_contents dc -- replace `test_example_dimension_contents` with your table
LEFT JOIN (
    SELECT
        dc3.stage,
        dc3.example_id, -- replace `example_id` with your relation
        CONCAT('["', REPLACE(GROUP_CONCAT(dc3.locale), ',', '","'), '"]') as availableLocales
    FROM test_example_dimension_contents dc3 -- replace `test_example_dimension_contents` with your table
         WHERE locale IS NOT NULL
         GROUP BY
             dc3.example_id, -- replace `example_id` with your relation
             dc3.stage
    ) as dc4 ON
        dc4.example_id = dc.example_id -- replace `example_id` with your relation
        AND dc4.stage = dc.stage
SET dc.availableLocales = dc4.availableLocales
WHERE
    dc.availableLocales IS NULL
    AND dc.locale IS NULL;
```

### ContentMapperInterface changed

The `ContentMapperInterface` was changed as a preparation for refactoring the `DimensionContentCollection`:

**Before**

```php
    public function map(
        array $data,
        DimensionContentCollectionInterface $dimensionContentCollection
    ): void;
```

**After**

```php
    public function map(
        DimensionContentCollectionInterface $dimensionContentCollection,
        array $dimensionAttributes,
        array $data
    ): void;
```

### DataMapperInterface changed

The `DataMapperInterface` was changed to make it easier to set localized and unlocalized data:

**Before**

```php
    public function map(
        array $data,
        DimensionContentCollectionInterface $dimensionContentCollection
    ): void;
```

**After**

```php
    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void;
```

### Use jsonb for PostgreSQL json columns

This effects only PostgreSQL databases:

```sql
ALTER TABLE <your_entity>_example_dimensions ALTER COLUMN templateData SET DATA TYPE jsonb USING templateData::jsonb;
```

## 0.6.3

### Add dimension, templateKey, workflowPublished and workflowPlace indexes

Improve performance of the `*ContentDimension` tables with additional indexes for the database:

```sql
CREATE INDEX idx_dimension ON <your_entity>_content (stage, locale);
CREATE INDEX idx_locale ON <your_entity>_content (locale);
CREATE INDEX idx_stage ON <your_entity>_content (stage);
CREATE INDEX idx_template_key ON <your_entity>_content (templateKey);
CREATE INDEX idx_workflow_place ON <your_entity>_content (workflowPlace);
CREATE INDEX idx_workflow_published ON <your_entity>_content (workflowPublished);
```

## 0.6.0

### Adjusted ContentDataMapper to accept DimensionContentCollection instead of separate objects

The `ContentDataMapper` service was adjusted to accept a `DimensionContentCollection` parameter instead of 
a `unlocalizedObject` and an optional `localizedObject` parameter. This makes the interfaces more flexible 
and consistent to other parts of the bundle.

### Adjusted DataMapperInterface to accept DimensionContentCollection instead of separate objects

The `DataMapperInterface` and all services that implement the interface were adjusted to accept a 
`DimensionContentCollection` parameter instead of a `unlocalizedObject` and an optional `localizedObject` parameter.

### Removed getUnlocalizedDimensionContent and getLocalizedDimensionContent from DimensionContentCollectionInterface

To simplify the interface, the `getUnlocalizedDimensionContent` method and `getLocalizedDimensionContent` method 
were removed from the `DimensionContentCollectionInterface`. The `getDimensionContent` can be used as a
replacement like this:

```php
$localizedDimensionAttributes = $dimensionContentCollection->getDimensionAttributes();
$localizedDimensionContent = $dimensionContentCollection->getDimensionContent($localizedDimensionAttributes);

$unlocalizedDimensionAttributes = array_merge($localizedDimensionAttributes, ['locale' => null]);
$unlocalizedDimensionContent= $dimensionContentCollection->getDimensionContent($unlocalizedDimensionAttributes);
```

### Dimension Entity was removed

The `Dimension` entity was removed because it had no additional value and did make things
unnecessary complex.

#### Migrate data into your DimensionContent entity

As the Dimension Entity did contain locale and stage in which your DimensionContent is saved
this data need to be migrated into your own entity.

```sql
# Create stage and locale fields
ALTER TABLE test_example_dimension_contents ADD stage VARCHAR(16) DEFAULT NULL, ADD locale VARCHAR(7) DEFAULT NULL;

# Migrate data to new fields
UPDATE test_example_dimension_contents myContentDimension
INNER JOIN cn_dimensions dimension ON dimension.no = myContentDimension.dimension_id
SET myContentDimension.stage = dimension.stage, myContentDimension.locale = dimension.locale;

# Remove nullable from stage field
ALTER TABLE test_example_dimension_contents CHANGE stage stage VARCHAR(16) NOT NULL;

# Remove dimension relation
ALTER TABLE test_example_dimension_contents DROP FOREIGN KEY FK_9BFA55B277428AD;
DROP INDEX IDX_9BFA55B277428AD ON test_example_dimension_contents;
ALTER TABLE test_example_dimension_contents DROP dimension_id;

# Drop Dimension Table
DROP TABLE cn_dimensions;
```

If you are using the `DoctrineMigrationBundle` you can also reuse the following migration class
to migrate the data of you entity. Make sure to provide the correct `tableName`, `foreignKey` 
and `indexName` before executing the migration.

<details>
<summary>DoctrineMigrationBundle Example</summary>

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210120152235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate dimension attributes into dimension content table.';
    }

    /**
     * @return array<array<string, string>>
     */
    private function tableData(): array
    {
        return [
            [
                // TODO replace the following values with the ones by your table
                'tableName' => 'my_entity_dimension_content', # name of the table of your DimensionContent entity
                'foreignKey' => 'FK_61A94F1277428AD', # dimension foreign key on the DimensionContent table
                'indexName' => 'IDX_61A94F1277428AD', # dimension index on the DimensionContent table
            ],
        ];
    }

    public function up(Schema $schema): void
    {
        foreach ($this->tableData() as $tableConfig) {
            $tableName = $tableConfig['tableName'];
            $foreignKey = $tableConfig['foreignKey'];
            $indexName = $tableConfig['indexName'];

            // Create stage and locale fields
            $this->addSql(\sprintf('ALTER TABLE %s ADD stage VARCHAR(16) DEFAULT NULL, ADD locale VARCHAR(7) DEFAULT NULL;', $tableName));

            // Migrate data to new fields
            $this->addSql(\sprintf('UPDATE %s myContentDimension INNER JOIN cn_dimensions dimension ON dimension.no = myContentDimension.dimension_id SET myContentDimension.stage = dimension.stage, myContentDimension.locale = dimension.locale;', $tableName));

            // Remove dimension relation
            $this->addSql(\sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $tableName, $foreignKey));
            $this->addSql(\sprintf('DROP INDEX %s ON %s', $indexName, $tableName));
            $this->addSql(\sprintf('ALTER TABLE %s DROP dimension_id;', $tableName));
        }

        // Drop Dimension Table
        $this->addSql('DROP TABLE cn_dimensions');
    }

    public function down(Schema $schema): void
    {
        // create old dimension table
        $this->addSql('CREATE TABLE cn_dimensions (no INT AUTO_INCREMENT NOT NULL, id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', locale VARCHAR(5) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, stage VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_979F85354180C698 (locale), UNIQUE INDEX UNIQ_979F8535BF396750 (id), INDEX IDX_979F8535C27C9369 (stage), PRIMARY KEY(no)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');

        foreach ($this->tableData() as $tableConfig) {
            $tableName = $tableConfig['tableName'];
            $foreignKey = $tableConfig['foreignKey'];
            $indexName = $tableConfig['indexName'];

            // Create dimension relation
            $this->addSql(\sprintf('ALTER TABLE %s ADD dimension_id INT NOT NULL', $tableName));

            // migrate data into the old table
            $this->addSql(\sprintf('
                INSERT INTO cn_dimensions (id, locale, stage)
                (SELECT
                   UUID() as id,
                   myContentDimension.locale,
                   myContentDimension.stage
                   FROM ec_product_line_dimension_content myContentDimension
                LEFT JOIN cn_dimensions ON (cn_dimensions.stage = myContentDimension.stage AND cn_dimensions.locale = myContentDimension.locale OR (cn_dimensions.locale IS NULL AND myContentDimension.locale IS NULL))
                WHERE cn_dimensions.id IS NULL
                GROUP BY myContentDimension.locale, myContentDimension.stage)
            ', $tableName));

            $this->addSql(\sprintf('
                UPDATE %s myContentDimension 
                INNER JOIN cn_dimensions dimension
                ON myContentDimension.stage = dimension.stage AND (myContentDimension.locale = dimension.locale OR (myContentDimension.locale IS NULL AND dimension.locale IS NULL)) 
                SET myContentDimension.dimension_id = dimension.no
            ', $tableName));

            // Add dimension relation
            $this->addSql(\sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (dimension_id) REFERENCES cn_dimensions (no) ON DELETE CASCADE', $tableName, $foreignKey));
            $this->addSql(\sprintf('CREATE INDEX %s ON %s (dimension_id)', $indexName, $tableName));

            // remove stage and locale
            $this->addSql(\sprintf('ALTER TABLE %s DROP stage, DROP locale', $tableName));
        }
    }
}
```

</details>

#### Update your ContentRichEntity class and DimensionContent class

In your "ContentRichEntity" class you need to change the createDimensionContent method:

```diff
-    public function createDimensionContent(DimensionInterface $dimension): DimensionContentInterface
+    public function createDimensionContent(): DimensionContentInterface
     {
-        return new ExampleDimensionContent($this, $dimension);
+        $exampleDimensionContent = new ExampleDimensionContent($this);
+
+        return $exampleDimensionContent;
     }
```

Also the constructor of your "DimensionContent" entity need to be changed:

```diff
-    public function __construct(Example $example, DimensionInterface $dimension)
+    public function __construct(Example $example)
     {
         $this->example = $example;
-        $this->dimension = $dimension;
     }
```

The `DimensionContentInterface` has the `getDimension` removed and will now directly
need to provide the `getStage`, `setStage`, `getLocale` and `setLocale` methods.
If you are using the traits provided by the ContentBundle, these methods should be added to your entity automatically.

#### Update your list configuration

If you use the dimension data in your list configuration, you need to change it the following way:

```diff
<?xml version="1.0" ?>
<list xmlns="http://schemas.sulu.io/list-builder/list">
    <key>examples</key>

-    <joins name="dimensionContent" ref="dimension">
+    <joins name="dimensionContent">
        <join>
            <entity-name>dimensionContent</entity-name>
            <field-name>Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example.dimensionContents</field-name>
            <method>LEFT</method>
-            <condition>dimensionContent.dimension = %sulu.model.dimension.class%.no</condition>
+            <condition>dimensionContent.locale = :locale AND dimensionContent.stage = 'draft'</condition>
        </join>
    </joins>
    
-    <joins name="dimension">
-        <join>
-            <entity-name>%sulu.model.dimension.class%</entity-name>
-            <condition>%sulu.model.dimension.class%.locale = :locale AND %sulu.model.dimension.class%.stage = 'draft'</condition>
-         </join>
-     </joins>

    <properties>
        <property name="id" translation="sulu_admin.id">
            <field-name>id</field-name>
            <entity-name>Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example</entity-name>
        </property>

-        <property name="dimensionId" visibility="never">
-            <field-name>id</field-name>
-            <entity-name>%sulu.model.dimension.class%</entity-name>
-
-            <joins ref="dimension"/>
-        </property>
-
        <property name="title" visibility="yes" translation="sulu_admin.title">
            <field-name>title</field-name>
            <entity-name>dimensionContent</entity-name>

            <joins ref="dimensionContent"/>
        </property>
    </properties>
</list>
```

## 0.5.0

### ContentTeaserProvider constructor changed

The constructor of the `ContentTeaserProvider` requires like the `ContentDataProviderRepository` the `show_drafts`
parameter. In this case also the `getShowDrafts` was removed from the `ContentTeaserProvider` class.

**before**:

```yaml
    example_test.example_teaser_provider:
        class: Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Teaser\ExampleTeaserProvider
        public: true
        arguments:
            - '@sulu_content.content_manager'
            - '@doctrine.orm.entity_manager'
            - '@sulu_content.content_metadata_inspector'
            - '@sulu_page.structure.factory'
            - '@translator'
            - '%sulu_document_manager.show_drafts%'
        tags:
            - { name: sulu.teaser.provider, alias: examples }
```

```php
    public function __construct(
        ContentManagerInterface $contentManager,
        EntityManagerInterface $entityManager,
        ContentMetadataInspectorInterface $contentMetadataInspector,
        StructureMetadataFactoryInterface $metadataFactory,
        TranslatorInterface $translator
    ) {
        parent::__construct($contentManager, $entityManager, $contentMetadataInspector, $metadataFactory, Example::class);

        $this->translator = $translator;
    }
```

**after**:

```yaml
    example_test.example_teaser_provider:
        class: Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Teaser\ExampleTeaserProvider
        public: true
        arguments:
            - '@sulu_content.content_manager'
            - '@doctrine.orm.entity_manager'
            - '@sulu_content.content_metadata_inspector'
            - '@sulu_page.structure.factory'
            - '@translator'
            - '%sulu_document_manager.show_drafts%' # this was added
        tags:
            - { name: sulu.teaser.provider, alias: examples }
```

```php
    public function __construct(
        ContentManagerInterface $contentManager,
        EntityManagerInterface $entityManager,
        ContentMetadataInspectorInterface $contentMetadataInspector,
        StructureMetadataFactoryInterface $metadataFactory,
        TranslatorInterface $translator,
        bool $showDrafts // this was added
    ) {
        parent::__construct($contentManager, $entityManager, $contentMetadataInspector, $metadataFactory, Example::class, $showDrafts); // this was added

        $this->translator = $translator;
    }
```

## 0.4.0

### Rename getContentRichEntity method of DimensionContentInterface to getResource

The `getContentRichEntity` method of the `DimensionContentInterface` was renamed to `getResource`.
This makes the naming consistent with the `getResourceKey` method of the `DimensionContentInterface` and
the `getResourceId` method of the `RoutableInterface`.

### Rename getRoutableId method of RoutableInterface to getResourceId

The `getRoutableId` method of the `RoutableInterface` was renamed to `getResourceId`. This makes the naming consistent
with the `getResourceKey` method of the `RoutableInterface` and the `DimensionContentInterface`.

### Add contentRichEntityClass parameter to getDefaultToolbarActions method of ContentViewBuilderFactory

The `getDefaultToolbarActions` method of the `ContentViewBuilderFactory` has a required `contentRichEntityClass` parameter
now. The parameter is used for determining the correct toolbar actions based on the implemented interfaces.

### Move static getResourceKey method from ContentRichEntityInterface to DimensionContentInterface

The static `getResourceKey` method was moved from the `ContentRichEntityInterface` to the `DimensionContentInterface`.
This makes it consistent with the `getTemplateType` method and the `getWorkflowName` method .

### Refactor RoutableInterface to use configured schema for route generation

The bundle now uses the `route_schema` that is configured via `sulu_route.mappings` for generating the route for an
entity instead of a hardcoded value. If no `route_schema` is configured, no route will be generated.

Therefore, the `getContentId` method of the `RoutableInterface` was renamed to `getRoutableId` and the
`getContentClass` method was replaced with a `getResourceKey` method.

### Moved automation bundle services

The services related to the `SuluAutomationBundle` were moved to the
`Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Automation` namespace.
Furthermore the `ContentEntityPublishHandler` was renamed to `ContentPublishTaskHandler` and
the `ContentEntityUnpublishHandler` was renamed to `ContentUnpublishTaskHandler`.

### Removed ContentProjection concept

To simplify the usage of the bundle, the ContentProjection concept was removed from the source code.
Therefore, the `ContentProjectionInterface` and the `ContentProjectionFactoryInterface` were removed.

Services that returned a `ContentProjectionInterface` instance were adjusted to return a merged
`DimensionContentInterface` instance. Furthermore, the `ContentMergerInterface::merge` method
was refactored to accept a `DimensionContentCollectionInterface` parameter.

### Renamed merger services

* The `sulu_content.template_content_projection_factory_merger` was renamed to `sulu_content.template_merger`.
* The `sulu_content.workflow_content_projection_factory_merger` was renamed to `sulu_content.workflow_merger`.
* The `sulu_content.excerpt_content_projection_factory_merger` was renamed to `sulu_content.excerpt_merger`.
* The `sulu_content.seo_content_projection_factory_merger` was renamed to `sulu_content.seo_merger`.

## 0.3.0

### Refactored the ContentViewBuilder

The class and its interface was renamed from `ContentViewBuilder` & `ContentViewBuilderInterface`
to `ContentViewBuilderFactory` & `ContentViewBuilderFactoryInterface`.

The service has been renamed from `sulu_content.content_view_builder` to `sulu_content.content_view_builder_factory`.

The function `build` was replaced by `createViews` and additional functions has been introduced.

The behaviour of the `createViews` function detects now the needed views: the template-view if the `TemplateInterface`
is implemented, the seo-view if the `SeoInterface` is implemented, and the excerpt-view if the `ExcerptInterface` is
implemented.

**before**:

```php
$this->contentViewBuilder->build(
    $viewCollection,
    $resourceKey,
    Example::TEMPLATE_TYPE,
    static::EDIT_TABS_VIEW,
    static::ADD_TABS_VIEW
);
```

**after**:

```php
$viewBuilders = $this->contentViewBuilderFactory->createViews(
    Example::class,
    Example::TEMPLATE_TYPE,
    static::EDIT_TABS_VIEW,
    static::ADD_TABS_VIEW
);

foreach ($viewBuilders as $viewBuilder) {
    $viewCollection->add($viewBuilder);
}
```

### Changed the constructor of ContentObjectProvider class

The arguments of the constructor of the `ContentObjectProvider` class were changed.

### Renamed the `sulu_content.content_projection_factory_merger` tag to `sulu_content.merger`

The `sulu_content.content_projection_factory_merger` tag was renamed to `sulu_content.merger`.

### Renamed the `sulu_content.normalize_enhancer` tag to `sulu_content.normalizer`

The `sulu_content.normalize_enhancer` tag was renamed to `sulu_content.normalizer`.
