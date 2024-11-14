# Upgrade

## 3.x

### Article user settings updated

Due to the refactoring of the new content bundle storage, the user settings for articles must be erased to avoid conflicts.

Execute the following SQL query to delete the user settings:
```mysql
DELETE FROM `se_user_settings`
       WHERE `se_user_settings`.`settingsKey` LIKE '%article%'
```

### Elasticsearch Bundle need to be required

The SuluArticleBundle defines not longer its dependency to `handcraftedinthealps/elasticsearch-bundle` because
of the new `experimental` storage. If you update you need to require the bundle in your `composer.json`.

```bash
composer require "handcraftedinthealps/elasticsearch-bundle:^5.2"
```

## 2.5.2

### Rename WebsiteArticleController::renderBlock to WebsiteArticleController::renderBlockView

In Symfony 6.4, an independent `renderBlock` method was introduced to its `AbstractController`.
This change poses issues for projects upgrading to Symfony 6.4, as the `renderBlock` method in Sulu is incompatible with Symfony's `renderBlock` method.
To address this issue, we have to rename the Sulu `renderBlock` method to `renderBlockView`.

## 2.3.0

### Changed constructor of ArticleObjectProvider

Added `StructureMetadataFactoryInterface $structureMetadataFactory` to constructor of `Sulu\Bundle\ArticleBundle\Preview\ArticleObjectProvider`.

## 2.2.0

### Index mapping changed

The mapping of the `CategoryViewObject` and `ExcerptViewObject` changed, therefore a reindex is
necessary:

```bash
bin/websiteconsole sulu:article:reindex --drop
bin/adminconsole sulu:article:reindex --drop
``` 

## 2.1.0

### Elasticsearch Upgrade

We now use an own fork of the elasticsearch packages to provide Elasticsearch 7 support.
For upgrading you can use the following commands:

```bash
# Remove ongr packages:
composer remove ongr/elasticsearch-bundle --no-update
composer remove ongr/elasticsearch-dsl --no-update

# Use your matching elasticsearch version here ( ^5, ^6 or ^7 ):
composer require elasticsearch/elasticsearch:"^7" --no-update

# Update article bundle to newest version:
composer require sulu/article-bundle:"^2.1" --with-dependencies

# Reindex your articles when upgrading elasticsearch version:
bin/adminconsole ongr:es:index:create --manager default
bin/adminconsole ongr:es:index:create --manager live
bin/adminconsole sulu:article:reindex
bin/websiteconsole sulu:article:reindex
```

### Mapping of index changed

Because the length of the indexed formats of the MediaViewObject the type has been changed to `binary`.

To upgrade it run following commands:

```bash
bin/websiteconsole sulu:article:reindex --drop
bin/adminconsole sulu:article:reindex --drop
``` 

### Teaser Migrations

Run the following command to migrate the teaser provider in the articles.

```bash
bin/console phpcr:migrations:migrate
```

## 2.0.0

### Routing changed

Route definitions changed from xml to yaml.
```
sulu_article_api:
-    resource: "@SuluArticleBundle/Resources/config/routing_api.xml"
+    resource: "@SuluArticleBundle/Resources/config/routing_api.yml"
     type: rest
     prefix: /admin/api
```

Also, the routes `get_articles` and `get_article` changed to `sulu_article.get_articles` and `sulu_article.get_article`.

### ArticleSelectionContentType constructor changed

The last parameter `template` has been removed from the ArticleSelectionContentType constructor.

### The article type filter changed

The filter for article types has changed from `?type=blog` to `?types=blog`

### ElasticSearchFieldDescriptor constructor changed

The ElasticSearchFieldDescriptor changed see FieldDescriptor update in the UPGRADE.md of sulu/sulu.

## 1.2.0

We now use an own fork of the elasticsearch packages to provide Elasticsearch 7 support.
For upgrading you can use the following commands:

```bash
# Remove ongr packages:
composer remove ongr/elasticsearch-bundle --no-update
composer remove ongr/elasticsearch-dsl --no-update

# Use your matching elasticsearch version here ( ^5, ^6 or ^7 ):
composer require elasticsearch/elasticsearch:”^7” --no-update

# Update article bundle to newest version:
composer require sulu/article-bundle:”^1.2” --with-dependencies

# Reindex your articles when upgrading elasticsearch version:
bin/adminconsole ongr:es:index:create --manager default
bin/adminconsole ongr:es:index:create --manager live
bin/adminconsole sulu:article:reindex
bin/websiteconsole sulu:article:reindex
```

## 1.1.1

New security-contexts have been created per article type.
This means permission for articles need to be re-set.

## 1.1.0

### Elasticsearch 2.0 and PHP 5.5 support dropped

To support Elasticsearch 6 we needed to drop support for Elasticsearch 2.0
and PHP 5.5 they will be maintained in the `1.0.x` Version of the bundle.

When using Elasticsearch 6 and used the same index for custom entities you
need to change this and create an own index for them as having multiple
Elasticsearch types in the same index is not longer supported by Elasticsearch.

When you UPGRADE from ES2 to ES5/ES6 have a look at the new
[ongr_elasticsearch](Resources/doc/installation.md) configuration.

## 1.0.0

### Localized webspace settings

Reindex elasticsearch data:

```bash
bin/websiteconsole sulu:article:reindex --drop
bin/adminconsole sulu:article:reindex --drop
``` 

Check also the new possible localized configuration.

### Elasticsearch Mapping Changed

Mapping has changed, reindex whole elasticsearch data:

```bash
bin/websiteconsole sulu:article:reindex --drop
bin/adminconsole sulu:article:reindex --drop
``` 

## 1.0.0-RC7

### Multi webspace behavior

When you have a multi webspace setup you need to follow the new instructions:
[multi webspaces](Resources/doc/multi-webspaces.md)

### ArticlePageDocument route definition need to be defined

The ArticleBundle will not longer prepend the configuration for the article page routes
for this you need to define them in your configuration (app/config/config.yml):

```yml
sulu_route:
    mappings:
        # ...
        Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument:
            generator: "article_page"
            options:
                route_schema: "/{translator.trans(\"page\")}-{object.getPageNumber()}"
                parent: "{object.getParent().getRoutePath()}"
```

## 1.0.0-RC5

### Author and Authored

The author and authored are now localized and has to be updated.

```bash
bin/adminconsole phpcr:migrations:migrate 
bin/websiteconsole sulu:article:reindex --no-interaction
``` 

## 1.0.0-RC1

### Reindex command

The `sulu:article:index-rebuild` command was refactored and renamed to `sulu:article:reindex`. 
See [Commands in documentation](Resources/doc/commands.md).

### NewIndex mapping has changed

Recreate the index to update mapping (new `content_data` field) and reindex your articles:

```bash
bin/adminconsole sulu:article:reindex --drop --no-interaction
bin/websiteconsole sulu:article:reindex --drop --no-interaction
```

## 0.7.0

### Index mapping has changed

Update configuration for Elasticsearch [^2.2](Resources/doc/installation-es2.md) or 
[^5.0](Resources/doc/installation-es5.md) and add the new analyzer `pathAnalyzer`.

After that recreate the index and reindex your articles:

```bash
bin/adminconsole ongr:es:index:drop -m default --force
bin/websiteconsole ongr:es:index:drop -m live --force

bin/adminconsole ongr:es:index:create -m default
bin/websiteconsole ongr:es:index:create -m live

bin/adminconsole sulu:article:index-rebuild ###LOCALE###
bin/websiteconsole sulu:article:index-rebuild ###LOCALE### --live
```

## 0.6.1

### Resolve of excerpt data

Excerpt data is now resolved in the article template instead of an array of categories and images you
get directly the data.

__before__:

```twig
{{ extension.excerpt.categories[0] }}
{{ extension.excerpt.images.ids[0] }}
{{ extension.excerpt.icon.ids[0] }}
```

__after__:

```twig
{{ extension.excerpt.categories[0].id }}
{{ extension.excerpt.images[0].id }}
{{ extension.excerpt.icon[0].id }}
```

## 0.6.0

### Index mapping has changed

Recreate the index and reindex your articles:

```bash
bin/adminconsole ongr:es:index:drop -m default --force
bin/websiteconsole ongr:es:index:drop -m live --force

bin/adminconsole ongr:es:index:create -m default
bin/websiteconsole ongr:es:index:create -m live

bin/adminconsole sulu:article:index-rebuild ###LOCALE###
bin/websiteconsole sulu:article:index-rebuild ###LOCALE### --live
```

### DocumentManager

Removed persist option `route_path` use the method `setRoutePath` instead.

## 0.5.0

### Elasticsearch 5.0

Now also support for ElasticSearch 5. To still be compatible with ^2.2, make sure you run: 
* composer require ongr/elasticsearch-bundle:1.2.9

## 0.4.0

### WebsiteArticleController

The multi-page feature needs a refactoring of the ``WebsiteArticleController``.
If you have overwritten it you have to adapt it.

__Before:__

```php
class CustomArticleController extends Controller
{
    public function indexAction(Request $request, ArticleDocument $object, $view)
    {
        $content = $this->get('jms_serializer')->serialize(
            $object,
            'array',
            SerializationContext::create()
                ->setSerializeNull(true)
                ->setGroups(['website', 'content'])
                ->setAttribute('website', true)
        );
        
        return $this->render(
            $view . '.html.twig',
            $this->get('sulu_website.resolver.template_attribute')->resolve($content),
            $this->createResponse($request)
        );
    }
}
```

__After:__

```php
class CustomArticleController extends WebsiteArticleController
{
    public function indexAction(Request $request, ArticleInterface $object, $view, $pageNumber = 1)
    {
        return $this->renderArticle($request, $object, $view, $pageNumber, []);
    }
}
```

### Cachelifetime request attribute changed

The `_cacheLifetime` attribute available in the request parameter of a article
controller will return the seconds and don't need longer be resolved manually
with the cachelifetime resolver.

## 0.2.0

Reindex elastic search indexes:

```bash
bin/adminconsole sulu:article:index-rebuild ###LOCALE### --live
bin/adminconsole sulu:article:index-rebuild ###LOCALE###
```
