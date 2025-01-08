# Upgrade

## 2.6.4

### Replacing compiler passes with `tagged_iterator`s
We have replaced the manual logic of getting a list of tagged services to the Symfony `tagged_iterator` argument. This
means that the following classes have been removed:
- src/Sulu/Bundle/AudienceTargetingBundle/DependencyInjection/Compiler/AddRulesPass.php

### Stricter Image Format Url Handling

The image formats URL requires an exact filename match to retrieve the correct image format. 
Old versions will be redirected to the new version and any non-matching filenames will now return a 404 error.

## 2.6.3

### Change locale length

Change length of the locale fields to support longer locales.

```sql
ALTER TABLE re_references CHANGE referenceLocale referenceLocale VARCHAR(15) DEFAULT NULL;
ALTER TABLE me_collection_meta CHANGE locale locale VARCHAR(15) NOT NULL;
ALTER TABLE me_file_version_publish_languages CHANGE locale locale VARCHAR(15) NOT NULL;
ALTER TABLE me_file_version_meta CHANGE locale locale VARCHAR(15) NOT NULL;
ALTER TABLE me_file_version_content_languages CHANGE locale locale VARCHAR(15) NOT NULL;
ALTER TABLE ca_category_meta CHANGE locale locale VARCHAR(15) DEFAULT NULL;
ALTER TABLE ca_category_translations CHANGE locale locale VARCHAR(15) NOT NULL;
ALTER TABLE ca_categories CHANGE default_locale default_locale VARCHAR(15) NOT NULL;
ALTER TABLE ca_keywords CHANGE locale locale VARCHAR(15) NOT NULL;
ALTER TABLE ro_routes CHANGE locale locale VARCHAR(15) NOT NULL;
```

## 2.6.0

### PHP 8.2 upgrade

Before upgrading to Sulu 2.6, ensure that your project's code and dependencies
are already compatible with PHP 8.2.

We recommend performing the PHP upgrade as a separate step before updating to
Sulu 2.6. This will make it easier for you to identify any bugs that may occur
in the project code with PHP 8.2.

### New required bundle

A new Bundle was added to Sulu which needs to be registered in the `config/bundles.php`:

```diff
return [
    // ...
+    Sulu\Bundle\ReferenceBundle\SuluReferenceBundle::class => ['all' => true],
];
```

And the bundles route configuration in the `config/routes/sulu_admin.yaml`:

```diff
# ...
+
+sulu_reference_api:
+    resource: "@SuluReferenceBundle/Resources/config/routing_api.yml"
+    type: rest
+    prefix: /admin/api
```

Also a new table is required to be created in the database you can use doctrine or doctrine migrations for it:

```sql
CREATE TABLE re_references (id INT AUTO_INCREMENT NOT NULL, resourceKey VARCHAR(191) NOT NULL, resourceId VARCHAR(191) NOT NULL, referenceResourceKey VARCHAR(191) NOT NULL, referenceResourceId VARCHAR(191) NOT NULL, referenceLocale VARCHAR(5) DEFAULT NULL, referenceRouterAttributes JSON NOT NULL, referenceTitle VARCHAR(191) NOT NULL, referenceProperty VARCHAR(191) NOT NULL, referenceContext VARCHAR(16) NOT NULL, created DATETIME NOT NULL, changed DATETIME NOT NULL, INDEX resource_idx (resourceKey, resourceId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
```

To create references for existing data run the following commands:

```bash
bin/adminconsole sulu:reference:refresh

bin/websiteconsole sulu:reference:refresh
```

### PHPCRMigrationBundle namespace changed

The [`dantleech/phpcr-migrations-bundle`](https://github.com/dantleech/phpcr-migrations-bundle) is now part of the phpcr
under the package name [`phpcr/phpcr-migrations-bundle`](https://github.com/phpcr/phpcr-migrations-bundle).

Besides changing the packages in your `composer.json`, you also need to change the namespace in the `config/bundles.php`:

```diff
return [
    // ...
-    DTL\Bundle\PhpcrMigrations\PhpcrMigrationsBundle::class => ['all' => true],
+    PHPCR\PhpcrMigrationsBundle\PhpcrMigrationsBundle::class => ['all' => true],
];
```

### New Reserved Templates directory for Global Blocks

With the introduction of the new [Global Blocks](https://docs.sulu.io/en/2.6/book/templates.html#templates-global-blocks) there
is a new reserved directory `config/templates/blocks`.

If you already did use that directory for `xi:includes` you should move the existing blocks to `config/templates/includes/blocks`.
Migrating to Global Blocks is not required and can be done step by step if you want to use the new Global Blocks
feature. Have a look at the Global Blocks [Documentation](https://docs.sulu.io/en/2.6/book/templates.html#templates-global-blocks).

### Custom Admin Builds npm version changed

Sulu 2.6 now supports [npm 8, 9, and 10](https://nodejs.org/en/download), 
as well as [pnpm 8](https://pnpm.io/) or [bun 1](https://bun.sh/) for custom 
admin builds. With the introduction of these new versions, it is necessary 
to drop the support for npm 6.

The upgrade of CKEditor to the latest version atleast [requires Node 18](https://github.com/ckeditor/ckeditor5-dev/blob/v39.6.3/package.json#L19).

### Webpack 5 upgrade

Sulu now uses Webpack 5 to build the administration interface application. To enable this, the following JavaScript dependencies were updated/changed:

- `webpack`: `^5.75.0`
- `webpack-cli`: `^5.0`
- `webpack-manifest-plugin`: `^5.0.0`
- `mini-css-extract-plugin`: `^2.7.1`
- `optimize-css-assets-webpack-plugin` was removed replaced by `css-minimizer-webpack-plugin`: `^6.0.0`
- `clean-webpack-plugin` was removed and replaced by `clean: true` webpack output option
- `webpack-clean-obsolete-chunks` was removed and replaced by `clean: true` webpack output option
- `is-email` was removed and replaced by `sulu-admin-bundle/utils/Email/validateEmail` method 
- `file-loader`: was removed and replaced by webpack internal [assets/resource](https://webpack.js.org/guides/asset-modules/)
- `raw-loader`: was removed and replaced by webpack internal [assets/source](https://webpack.js.org/guides/asset-modules/)

If you have integrated custom JavaScript components into the administration interface,
you might need to adjust your components to be compatible with the updated dependencies.
If you have not integrated custom JavaScript code, you project is adjusted automatically by the
[update build](https://docs.sulu.io/en/latest/upgrades/upgrade-2.x.html) command.

Additionally, the following packages where upgraded:

- `babel-loader`: `^9.1.0`
- `css-loader`: `^6.10.0`
- `glob`: `^10.3.10`
- `postcss-calc`: `^9.0.1`
- `postcss-import`: `^16.1.0`
- `postcss-loader`: `^8.1.0`
- `postcss-nested`: `^6.0.0`
- `postcss-simple-vars`: `^7.0.1`
- `debounce`: `^2.0`
- `react-dropzone`: `^14.2.0`
- `regenerator-runtime`: `^0.14.0`
- `@ckeditor/ckeditor5-dev-utils`: `^39.6.3`
- `@ckeditor/ckeditor5-theme-lark`: `^41.2.1`

This update is also handled normally by the update build command automatically.

### Return type adjustments to prepare Symfony 7 compatibility

Return type changes in `DocumentToUuidTransformer`:

```diff
-    public function transform($document)
+    public function transform($document): ?string

-    public function reverseTransform($uuid)
+    public function reverseTransform($uuid): ?object
```

Return type changes in `User`:

```diff
-    public function eraseCredentials()
+    public function eraseCredentials(): void
```

Return type changes in `AuthenticationEntryPoint`:

```diff
-    public function start(Request $request, ?AuthenticationException $authException = null)
+    public function start(Request $request, ?AuthenticationException $authException = null): Response
```

Return type changes in `UserProvider` and `TestUserProvider`:

```diff
-    public function refreshUser()
+    public function refreshUser(): UserInterface

-    public function supportsClass()
+    public function supportsClass(): bool
```

Return type changes in `SecurityContextVoter` and `TestVoter`:

```diff
-    public function vote(TokenInterface $token, $object, array $attributes)
+    public function vote(TokenInterface $token, $object, array $attributes): int
```

Return type changes in the internal `Warmer`:

```diff
-    public function warmUp($cacheDir)
+    public function warmUp($cacheDir, ?string $buildDir = null): array

-    public function isOptional()
+    public function isOptional(): bool
```

Return type changes in `Loader`:

```diff
-    public function load($resource, $type = null)
+    public function load($resource, $type = null): mixed


-    public function supports($resource, $type = null)
+    public function supports($resource, $type = null): bool
```

Return type changes in `ExpressionLanguageProvider`:

```diff
-    public function getFunctions()
+    public function getFunctions(): array
```

Return type changes in `Kernel`:

```diff
-    public function registerContainerConfiguration(LoaderInterface $loader)
+    public function registerContainerConfiguration(LoaderInterface $loader): void
```

### Symfony Doctrine Bridge 7 compatibility changes

To be compatible with the changes of Symfony 7 Doctrine Bridge all Sulu `doctrine.event_subscribers` were migrated to
`doctrine.event_listener`:

It is recommended to migrate own event subscribers also to listeners.

Example:

```diff
-<tag name="doctrine.event_subscriber" priority="-256"/>
+<tag name="doctrine.event_listener" event="onClear" priority="-256"/>
```

See also the documentation [official Doctrine Events documentation](https://symfony.com/doc/6.4/doctrine/events.html).  
Or the Merge request implementing this changes in Sulu [here](https://github.com/sulu/sulu/pull/7374/files).

### GeolocatorInterface locate method GeolocatorOptions added

To provide the Accept-Language locale to geolocator services, a custom Geolocator now requires support for the new ﻿`GeolocatorOptions` parameter:

```diff
-    public function locate(string $query): GeolocatorResponse
+    public function locate(string $query, ?GeolocatorOptions $options = null): GeolocatorResponse
```

### Replace Symfony Security class

The `Symfony\Component\Security\Core\Security` deprecated class was replaced by
`Symfony\Bundle\SecurityBundle\Security` for preparing Symfony 7 compatibility:

 - `Sulu/Bundle/ActivityBundle/Application/Subscriber/SetDomainEventUserSubscriber`
 - `Sulu/Bundle/PageBundle/EventListener/PageRemoveSubscriber`
 - `Sulu/Bundle/SecurityBundle/Metadata/TwoFactorFormMetadataVisitor`
 - `Sulu/Bundle/SecurityBundle/Security/AuthenticationHandler`
 - `Sulu/Bundle/TrashBundle/Infrastructure/Doctrine/Repository/TrashItemRepository`
 - `Sulu/Component/Content/Mapper/ContentMapper`
 - `Sulu/Component/Media/SmartContent/MediaDataProvider`
 - `Sulu/Component/Security/Authorization/AccessControl/AccessControlManager`

### Admin JS ResourceRouteRegistry getDetailUrl and getListUrl deprecated

The `getDetailUrl` and `getListUrl` methods of the `routeRegistry` were deprecated.  
Use the newly added `getUrl` method:

```diff
-routeRegistry.getDetailUrl(/* ... */)
+routeRegistry.getUrl('detail', (/* ... */)
-routeRegistry.getListUrl(/* ... */)
+routeRegistry.getUrl('list', (/* ... */)
```

### Static protected $defaultName property of commands removed

As deprecated in Symfony 6.1 the `$defaultName` of Sulu Commands were replaced with the new
`Symfony\Component\Console\Attribute\AsCommand` annotation.

### Jackalope 2 ContentRepository compatibility

To be compatible with Jackalope 2 the following method in the `ContentRepository` class
has changed:

```diff
public function resolveInternalLinkContent(
-    Row $row,
+    RowInterface $row,
     $locale,
```

### PHPCR and Jackalope update

An update of PHPCR and Jackalope to latest major version is optional but recommended.   
The following new versions are supported by Sulu:

 - `doctrine/phpcr-bundle`: `^3.0`
 - `phpcr/phpcr-utils`: `^2.0`
 - `jackalope/jackalope`: `^2.0`
 - `jackalope/jackalope-doctrine-dbal`: `^2.0`
 - `jackalope/jackalope-jackrabbit`: `^2.0`

In case of upgrading the `sulu_document_manager.yaml` cache configuration need to be changed:

```diff
# config/packages/sulu_document_manager.yaml

when@prod: &prod
    # ...

    services:
        doctrine_phpcr.meta_cache_provider:
-           class: Doctrine\Common\Cache\Psr6\DoctrineProvider
-           factory: ['Doctrine\Common\Cache\Psr6\DoctrineProvider', 'wrap']
+           class: Symfony\Component\Cache\Psr16Cache
            public: false
            arguments:
                - '@doctrine_phpcr.meta_cache_pool'
            tags:
                - { name: 'kernel.reset', method: 'reset' }

        doctrine_phpcr.nodes_cache_provider:
-           class: Doctrine\Common\Cache\Psr6\DoctrineProvider
-           factory: ['Doctrine\Common\Cache\Psr6\DoctrineProvider', 'wrap']
+           class: Symfony\Component\Cache\Psr16Cache
            public: false
            arguments:
                - '@doctrine_phpcr.nodes_cache_pool'
            tags:
                - { name: 'kernel.reset', method: 'reset' }

# ...
```

### ListBuilder Doctrine Changes

Bundle aliases where deprecated by [`doctrine/persistence` 3.0](https://github.com/doctrine/persistence/blob/3.2.0/UPGRADE.md#bc-break-removed-support-for-short-namespace-aliases) the full FQCN need to be used when upgrading to `doctrine/persistence:^3.0`:

```diff
    <joins name="address">
        <join>
-            <entity-name>SuluContactBundle:AccountAddress</entity-name>
+            <entity-name>Sulu\Bundle\ContactBundle\Entity\AccountAddress</entity-name>
            <field-name>%sulu.model.account.class%.accountAddresses</field-name>
            <method>LEFT</method>
-            <condition>SuluContactBundle:AccountAddress.main = TRUE</condition>
+            <condition>Sulu\Bundle\ContactBundle\Entity\AccountAddress.main = TRUE</condition>
        </join>

        <join>
-            <entity-name>SuluContactBundle:Address</entity-name>
+            <entity-name>Sulu\Bundle\ContactBundle\Entity\Address</entity-name>
-            <field-name>SuluContactBundle:AccountAddress.address</field-name>
+            <field-name>Sulu\Bundle\ContactBundle\Entity\AccountAddress.address</field-name>
        </join>
    </joins>

    <properties>
        <property
            name="state"
            visibility="no"
            translation="sulu_contact.state"
        >
            <field-name>state</field-name>
-            <entity-name>SuluContactBundle:Address</entity-name>
+            <entity-name>Sulu\Bundle\ContactBundle\Entity\Address</entity-name>

            <joins ref="address"/>

            <filter type="text" />
        </property>
```

### Deprecated urls variable in return value of sulu_content_load

The `urls` variable in the return value of the `sulu_content_load` function was deprecated.
This makes the data consistent with the data that is available inside of page templates.
Instead of using the `urls` variable, you should pass `url` in the `properties` parameter of
the function:

```twig
{% set page = sulu_content_load('1234-1234-1234-1234', {
    'title': 'title',
    'url': 'url',
}) %}
```

If you need to use the `urls` variable, you can enable it in your configuration. Be aware that
this configuration option will be removed in the next major version.

```yaml
# config/packages/sulu_website.yaml
sulu_website:
    twig:
        attributes:
            urls: true
```

### Fields Query parameter are now kept in mind for SnippetController

In the previous version the `SnippetController` would return the entire content of the snippet in the `cgetAction`. Now
it respects the list of fields provided in the query parameter and only returns those.

## 2.5.20

### Stricter Image Format Url Handling

The image formats URL requires an exact filename match to retrieve the correct image format. 
Old versions will be redirected to the new version and any non-matching filenames will now return a 404 error.

### The s-maxage header no longer affects server side http caching

Sulu accidentally cached large media files generated by the image/thumbnail generator controller. It was never expected that Sulu would cache based on the `s-maxage` behavior. Instead, it was supposed to use its own custom TTL header `X-Reverse-Proxy-TTL`. However, the CustomTtlListener unexpectedly fell back to the `s-maxage` header.

An upgrade of `friendsofsymfony/http-cache` to at least `2.16.0` or `3.1.0` is required.

```bash
composer update friendsofsymfony/http-cache
```

For custom controllers that currently use `s-maxage` for caching, also define the `X-Reverse-Proxy-TTL` header
if you still want to cache that response in the Symfony Http Cache:

```php
$response->headers->set(SuluHttpCache::HEADER_REVERSE_PROXY_TTL, $cacheLifetime);
```

## 2.5.15

### Run Shadow migrations

To fix shadow pages be correctly available you need to run the phpcr migration command:

```bash
bin/console phpcr:migrations:migrate
```


### Change FileVersion default meta relation

Currently, when removing the default meta, it did also remove the whole file version to avoid it following DB Change
is required:

```sql
ALTER TABLE me_file_versions DROP FOREIGN KEY FK_7B6E89456B801096
ALTER TABLE me_file_versions ADD CONSTRAINT FK_7B6E89456B801096 FOREIGN KEY (idFileVersionsMetaDefault) REFERENCES me_file_version_meta (id) ON DELETE SET NULL
```

## 2.5.12

### Hidden blocks wont be indexed anymore

To remove hidden blocks from the search index, you need to run the reindex commands:

```bash
bin/adminconsole massive:search:reindex --provider sulu_structure
bin/websiteconsole massive:search:reindex --provider sulu_structure
```

## 2.5.11

### Rename WebsiteController::renderBlock to WebsiteController::renderBlockView

In Symfony 6.4, an independent `renderBlock` method was introduced to its `AbstractController`.
This change poses issues for projects upgrading to Symfony 6.4, as the `renderBlock` method in Sulu is incompatible with Symfony's `renderBlock` method.
To address this issue, we have to rename the Sulu `renderBlock` method to `renderBlockView`.

## 2.5.7

### Constructor of ValidateWebspacesCommand changed

The constructor of `ValidateWebspacesCommand` requires now EventDispatcherInterface instead of activeTheme.

## 2.5.2

### Add indexes to route table

Improve performance of the `Route` table with additional indexes for the database:

```sql
CREATE INDEX idx_resource ON ro_routes (entity_id, entity_class);
CREATE INDEX idx_history ON ro_routes (history);
```

## 2.5.0

### Updated JavaScript dependencies

The JavaScript dependencies of the Sulu administration interface were updated to the following versions:

 - `@ckeditor/ckeditor5-dev-utils`: `^30.3.2`
 - `@ckeditor/ckeditor5-theme-lark`: `^34.2.0`
 - `@ckeditor/ckeditor5-*`: `34.2.0`
 - `postcss`: `^8.4.14`
 - `postcss-calc`: `^8.2.4`
 - `postcss-hexrgba`: `^2.0.0`
 - `postcss-import`: `^14.1.0`
 - `postcss-loader`: `^4.0.0`
 - `postcss-nested`: `^5.0.6`
 - `postcss-simple-vars`: `^6.0.3`
 - `autoprefixer`: `^10.4.7`

If you have integrated custom JavaScript components into the administration interface,
you might need to adjust your components to be compatible with the updated dependencies.
If you have not integrated custom JavaScript code, you project is adjusted automatically by the
[update build](https://docs.sulu.io/en/latest/upgrades/upgrade-2.x.html) command.

### Rename labelRef to inputContainerRef

The `labelRef` properties of the js components `Input` and `Number` was 
renamed to `inputContainerRef` as it is no longer a label tag for improving
accessibility of the interface.

### User table two factory authentication column added

To add support for two factor authentication the following
columns need to be added:

```sql
CREATE TABLE se_user_two_factors (id INT AUTO_INCREMENT NOT NULL, method VARCHAR(12) DEFAULT NULL, options LONGTEXT DEFAULT NULL, idUsers INT NOT NULL, UNIQUE INDEX UNIQ_732E8321347E6F4 (idUsers), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE se_user_two_factors ADD CONSTRAINT FK_732E8321347E6F4 FOREIGN KEY (idUsers) REFERENCES se_users (id) ON DELETE CASCADE;
```

### Drop support for PHP 7.4, 7.3 and 7.2

The support for older PHP versions 7.4, 7.3 and 7.2 were dropped.
Upgrade PHP to the [latest version](https://www.php.net/supported-versions.php) for your projects.

- [https://www.php.net/manual/en/migration73.php](https://www.php.net/manual/en/migration73.php)
- [https://www.php.net/manual/en/migration74.php](https://www.php.net/manual/en/migration74.php)
- [https://www.php.net/manual/en/migration80.php](https://www.php.net/manual/en/migration80.php)
- [https://www.php.net/manual/en/migration81.php](https://www.php.net/manual/en/migration81.php) (not required but recommended)

It is possible to use a tool like [PHP Rector](https://github.com/rectorphp/rector/) to make this easier.

For a detailed overview of changed dependencies see [sulu/sulu#6553](https://github.com/sulu/sulu/pull/6553/files).

### Drop support for Symfony 5.3 and lower

The support for Symfony 5.3 and lower was dropped.
Upgrade the used Symfony Components to 5.4 or greater.

 - [https://symfony.com/doc/5.4/setup/upgrade_minor.html](https://symfony.com/doc/5.4/setup/upgrade_minor.html)
 - [https://symfony.com/doc/5.0/setup/upgrade_major.html](https://symfony.com/doc/5.0/setup/upgrade_major.html)

It is possible to use a tool like [PHP Rector Symfony](https://github.com/rectorphp/rector-symfony/) to make this easier.

For a detailed overview of changed dependencies see [sulu/sulu#6553](https://github.com/sulu/sulu/pull/6553/files).

The Symfony 4.4 compatibility service
`Sulu\Bundle\WebsiteBundle\Controller\ExceptionController` / `sulu_website.exception_controller`
was removed. See also [UPGRADE 2.1.0-RC1](#210-rc1).

### Block component dragHandle property was renamed

The `dragHandle` property of the `Block.js` component was changed to `handle`:

```diff
-<Block dragHandle={handleComponent} />
+<Block handle={handleComponent} />
```

### User entity method return types changed

The sulu `User` entity requires the following changes:

```diff
-public function getRoles();
+public function getRoles(): array;
-public function isEqualTo(SymfonyUserInterface $user);
+public function isEqualTo(SymfonyUserInterface $user): bool;
```

### User entity getUsername method deprecated

In the sulu `User` entity the `getUsername` method is deprecated and replaced with `getUserIdentifier`:

```diff
-$user->getUsername();
+$user->getUserIdentifier();
```

### AuthenticationHandler method return types changed

The `AuthenticationHandler` requires the following changes:

```diff
-public function onAuthenticationSuccess(Request $request, TokenInterface $token)
+public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
-public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
+public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
```

### SuluHttpCache method return types changed

The sulu `SuluHttpCache` requires the following changes:

```diff
-public function fetch(Request $request, $catch = false)
+public function fetch(Request $request, $catch = false): Response
-protected function createStore()
+protected function createStore(): StoreInterface
```

If a method was overwritten it is required to be updated to the new return types.

### DocumentManager cache service definitions changed

The cache services which are activated for `stage` and `prod` environment
should be upgraded to the new `DoctrineProvider` factory.

Depending on your installation change the `config/packages/sulu_document_manager.yaml`
or for older skeleton versions `config/packages/prod/sulu_document_manager.yaml` file:

```diff
services:
    doctrine_phpcr.meta_cache_provider:
-        class: Symfony\Component\Cache\DoctrineProvider
+        class: Doctrine\Common\Cache\Psr6\DoctrineProvider
+        factory: ['Doctrine\Common\Cache\Psr6\DoctrineProvider', 'wrap']
        public: false
        arguments:
            - '@doctrine_phpcr.meta_cache_pool'
        tags:
            - { name: 'kernel.reset', method: 'reset' }

    doctrine_phpcr.nodes_cache_provider:
-        class: Symfony\Component\Cache\DoctrineProvider
+        class: Doctrine\Common\Cache\Psr6\DoctrineProvider
+        factory: ['Doctrine\Common\Cache\Psr6\DoctrineProvider', 'wrap']
        public: false
        arguments:
            - '@doctrine_phpcr.nodes_cache_pool'
        tags:
            - { name: 'kernel.reset', method: 'reset' }
```

### Upgrade Symfony security system

Not required but recommended is to upgrade the `config/packages/security.yaml`
to the latest changes which are required when upgrading to Symfony 6:

```diff
security:
+    enable_authenticator_manager: true

    # ...

-    encoders:
+    password_hashers:
        Sulu\Bundle\SecurityBundle\Entity\User: bcrypt

    # ...

    access_control:
-        - { path: ^/admin/reset, roles: IS_AUTHENTICATED_ANONYMOUSLY }
-        - { path: ^/admin/security/reset, roles: IS_AUTHENTICATED_ANONYMOUSLY }
-        - { path: ^/admin/login$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
-        - { path: ^/admin/_wdt, roles: IS_AUTHENTICATED_ANONYMOUSLY }
-        - { path: ^/admin/translations, roles: IS_AUTHENTICATED_ANONYMOUSLY }
-        - { path: ^/admin$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
-        - { path: ^/admin/$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
-        - { path: ^/admin/p/, roles: IS_AUTHENTICATED_ANONYMOUSLY }
+        - { path: ^/admin/reset, roles: PUBLIC_ACCESS }
+        - { path: ^/admin/security/reset, roles: PUBLIC_ACCESS }
+        - { path: ^/admin/login$, roles: PUBLIC_ACCESS }
+        - { path: ^/admin/_wdt, roles: PUBLIC_ACCESS }
+        - { path: ^/admin/translations, roles: PUBLIC_ACCESS }
+        - { path: ^/admin$, roles: PUBLIC_ACCESS }
+        - { path: ^/admin/$, roles: PUBLIC_ACCESS }
+        - { path: ^/admin/p/, roles: PUBLIC_ACCESS }
        - { path: ^/admin, roles: ROLE_USER }

    # ...

    firewalls:

        # ...

        admin:
            pattern: ^/admin(\/|$)
-            anonymous: lazy
+            lazy: true
            provider: sulu

            # ...

            logout:
                path: sulu_admin.logout
-                success_handler: sulu_security.logout_success_handler

            # ...
```

### WebsiteController methods removed and return types changed

Symfony 6 has deprecated and removed the `get` and `has` methods to access services.
Instead, the methods from the container should be used:

```diff
-$this->has('twig');
-$this->get('twig');
+$this->container->has('twig');
+$this->container->get('twig');
```

In order to support Symfony 6 the `getSubscribedServices` method requires an array return type:

```diff
-    public static function getSubscribedServices()
+    public static function getSubscribedServices(): array
```

### Password encoded depending service definitions changed

The following service changed its definition:

 - `sulu_security.login_failure_listener`
 - `test_user_provider`
 - `sulu_security.command.create_user`
 - `sulu_security.resetting_controller`

They require now `sulu_security.encoder_factory` instead of `security.encoder_factory`.

### Kernel Return Types changed

The Symfony Kernel requires the following return types now:

- `public function registerBundles(): iterable`
- `protected function getContainerClass(): string`
- `public function getCacheDir(): string`
- `public function getCommonCacheDir(): string`
- `public function getLogDir(): string`
- `protected function getKernelParameters(): array`
- `public function getEnvironment(): string`
- `public function isDebug(): bool`
- `public function getCharset(): string`
- `public function getStartTime(): float`
- `public function getContainer(): ContainerInterface`
- `public function getBundle(string $name): BundleInterface`
- `public function getBundles(): array`
- `public function locateResource(string $name): string`

If a method was overwritten it is required to be updated to the new return types.

### RouteProvider and RouteEnhancer return types changed

The following return types on RouteProviders were added
which effects `RouteProvider`, `ContentRouteProvider`, `CustomUrlRouteProvider` services:

- `public function getRouteCollectionForRequest(Request $request): RouteCollection`
- `public function getRouteByName($name): Route`
- `public function getRoutesByNames($names = null): iterable`

The following return types on the RouteEnhancer were added
which effects `AbstractEnhancer`, `ExternalLinkEnhancer`, `InternalLinkEnhancer`, `StructureEnhancer` services:

- `public function enhance(array $defaults, Request $request): array`

If a method was overwritten it is required to be updated to the new return types.

### DoctrineCacheBundle integration removed

All integration of the deprecated DoctrineCacheBundle were removed.

See [Doctrine Cache Bundle removed](#doctrinecachebundle-removed) upgrade.

### FOSJSRoutingBundle upgraded

The FOSJSRoutingBundle was upgraded and requires to change the routing include for it:

```diff
# config/routes/fos_js_routing_admin.yaml
fos_js_routing:
    prefix: /admin
-    resource: "@FOSJsRoutingBundle/Resources/config/routing/routing.xml"
+    resource: "@FOSJsRoutingBundle/Resources/config/routing/routing-sf4.xml"
```

### Changed constructor of AdminController

The `AdminController` now requires the password-policy information `$passwordPattern` and `$passwordInformationKey`. 

### Changed constructor of UserManager

The `UserManager` now requires the password-policy information `$passwordPattern`. 

### User Provider service definition changed

The user provider service now requires the `SystemStoreInterface` service
instead of the `RequestStack` to read correct set **security system**.

### User getSalt deprecated

The `getSalt` method on the Sulu User Entity is deprecated and will be
removed in future sulu major release.

### Replace SwiftMailer with Symfony Mailer

To provide support for Symfony 6 the deprecated **SwiftMailer** which was
used to send password forget emails was replaced with the **Symfony Mailer**.

This requires to configure the symfony mailer as your email provider:

```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

It should also be considered to remove the **SwiftMailer** and **SwiftMailerBundle**
from your application and replace it with [**Symfony Mailer**](https://symfony.com/doc/6.1/mailer.html).

## 2.4.19

### Run Shadow migrations

To fix shadow pages be correctly available you need to run the phpcr migration command:

```bash
bin/console phpcr:migrations:migrate
```

## 2.4.17

### Change FileVersion default meta relation

Currently, when removing the default meta, it did also remove the whole file version to avoid it following DB Change
is required:

```sql
ALTER TABLE me_file_versions DROP FOREIGN KEY FK_7B6E89456B801096
ALTER TABLE me_file_versions ADD CONSTRAINT FK_7B6E89456B801096 FOREIGN KEY (idFileVersionsMetaDefault) REFERENCES me_file_version_meta (id) ON DELETE SET NULL
```

## 2.4.16

### Hidden blocks wont be indexed anymore

To remove hidden blocks from the search index, you need to run the reindex commands:

```bash
bin/adminconsole massive:search:reindex --provider sulu_structure
bin/websiteconsole massive:search:reindex --provider sulu_structure
```

## 2.4.15

### Remove Node 12 Support for Custm Admin Builds

Changes in the JS ecosystem not longer allows us to test Sulu Admin
against Node 12. With this release so Sulu custom build may not longer
work on Node 12, we recommend updating your CI to atleast Node 14.

## 2.4.6

### Add indexes to route table

Improve performance of the `Route` table with additional indexes for the database:

```sql
CREATE INDEX idx_resource ON ro_routes (entity_id, entity_class);
CREATE INDEX idx_history ON ro_routes (history);
```

### Add indexes to audience targeting table

Only if you use audience targeting: Improve performance of the `AudienceTargeting` table with additional indexes for the database:

```sql
CREATE INDEX idx_active ON `at_target_groups` (`active`);
```

## 2.4.4

### Add indexes to activities table

Improve performance of the `Activities` table with additional indexes for the database:

```sql
CREATE INDEX timestamp_idx ON ac_activities (timestamp);
CREATE INDEX resourceKey_idx ON ac_activities (resourceKey);
CREATE INDEX resourceId_idx ON ac_activities (resourceId);
CREATE INDEX resourceSecurityContext_idx ON ac_activities (resourceSecurityContext);
CREATE INDEX resourceSecurityObjectType_idx ON ac_activities (resourceSecurityObjectType);
CREATE INDEX resourceSecurityObjectId_idx ON ac_activities (resourceSecurityObjectId);
```

## 2.4.3

### Add DELETE SET NULL to account parent relation

There did exist a bug in sulu when trying to remove an account entity
which was connected to another account entity it was not possible to
remove it. The `DELETE SET NULL` on the parent connection will solve
this issue. For this a database change is required:

```sql
ALTER TABLE co_accounts DROP FOREIGN KEY FK_805CD14AC9171171;
ALTER TABLE co_accounts ADD CONSTRAINT FK_805CD14AC9171171 FOREIGN KEY (idAccountsParent) REFERENCES co_accounts (id) ON DELETE SET NULL
```

### Add ON DELETE CASCADE to many-to-many relations

There was a bug in sulu that prevented removal of tags if the deleted
tag was still connected to an account or contact. The `ON DELETE CASCADE`
will clean up broken references for deleted tags, accounts or contacts.
For this a database change is required:

```sql
ALTER TABLE co_account_tags DROP FOREIGN KEY FK_E8D920051C41CAB8;
ALTER TABLE co_account_tags DROP FOREIGN KEY FK_E8D92005996BB4F7;
ALTER TABLE co_account_tags ADD CONSTRAINT FK_E8D920051C41CAB8 FOREIGN KEY (idTags) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE co_account_tags ADD CONSTRAINT FK_E8D92005996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_tags DROP FOREIGN KEY FK_4CB525501C41CAB8;
ALTER TABLE co_contact_tags DROP FOREIGN KEY FK_4CB5255060E33F28;
ALTER TABLE co_contact_tags ADD CONSTRAINT FK_4CB525501C41CAB8 FOREIGN KEY (idTags) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE co_contact_tags ADD CONSTRAINT FK_4CB5255060E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
```

Similar issues can be found in other many-to-many relations. The
following database change will fix these references as well:

```sql
ALTER TABLE co_account_bank_accounts DROP FOREIGN KEY FK_C873A53237FCD1D8;
ALTER TABLE co_account_bank_accounts DROP FOREIGN KEY FK_C873A532996BB4F7;
ALTER TABLE co_account_bank_accounts ADD CONSTRAINT FK_C873A53237FCD1D8 FOREIGN KEY (idBankAccounts) REFERENCES co_bank_account (id) ON DELETE CASCADE;
ALTER TABLE co_account_bank_accounts ADD CONSTRAINT FK_C873A532996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_emails DROP FOREIGN KEY FK_3E246FC32F9040C8;
ALTER TABLE co_account_emails DROP FOREIGN KEY FK_3E246FC3996BB4F7;
ALTER TABLE co_account_emails ADD CONSTRAINT FK_3E246FC32F9040C8 FOREIGN KEY (idEmails) REFERENCES co_emails (id) ON DELETE CASCADE;
ALTER TABLE co_account_emails ADD CONSTRAINT FK_3E246FC3996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_faxes DROP FOREIGN KEY FK_7A4E77DC996BB4F7;
ALTER TABLE co_account_faxes DROP FOREIGN KEY FK_7A4E77DCCF6A2007;
ALTER TABLE co_account_faxes ADD CONSTRAINT FK_7A4E77DC996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_faxes ADD CONSTRAINT FK_7A4E77DCCF6A2007 FOREIGN KEY (idFaxes) REFERENCES co_faxes (id) ON DELETE CASCADE;
ALTER TABLE co_account_notes DROP FOREIGN KEY FK_A3FBB24A16DFE591;
ALTER TABLE co_account_notes DROP FOREIGN KEY FK_A3FBB24A996BB4F7;
ALTER TABLE co_account_notes ADD CONSTRAINT FK_A3FBB24A16DFE591 FOREIGN KEY (idNotes) REFERENCES co_notes (id) ON DELETE CASCADE;
ALTER TABLE co_account_notes ADD CONSTRAINT FK_A3FBB24A996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_phones DROP FOREIGN KEY FK_918DA9648039866F;
ALTER TABLE co_account_phones DROP FOREIGN KEY FK_918DA964996BB4F7;
ALTER TABLE co_account_phones ADD CONSTRAINT FK_918DA9648039866F FOREIGN KEY (idPhones) REFERENCES co_phones (id) ON DELETE CASCADE;
ALTER TABLE co_account_phones ADD CONSTRAINT FK_918DA964996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_social_media_profiles DROP FOREIGN KEY FK_E06F75F5573F8344;
ALTER TABLE co_account_social_media_profiles DROP FOREIGN KEY FK_E06F75F5996BB4F7;
ALTER TABLE co_account_social_media_profiles ADD CONSTRAINT FK_E06F75F5573F8344 FOREIGN KEY (idSocialMediaProfiles) REFERENCES co_social_media_profiles (id) ON DELETE CASCADE;
ALTER TABLE co_account_social_media_profiles ADD CONSTRAINT FK_E06F75F5996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_urls DROP FOREIGN KEY FK_ADF183825969693F;
ALTER TABLE co_account_urls DROP FOREIGN KEY FK_ADF18382996BB4F7;
ALTER TABLE co_account_urls ADD CONSTRAINT FK_ADF183825969693F FOREIGN KEY (idUrls) REFERENCES co_urls (id) ON DELETE CASCADE;
ALTER TABLE co_account_urls ADD CONSTRAINT FK_ADF18382996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_bank_accounts DROP FOREIGN KEY FK_76CDDA0637FCD1D8;
ALTER TABLE co_contact_bank_accounts DROP FOREIGN KEY FK_76CDDA0660E33F28;
ALTER TABLE co_contact_bank_accounts ADD CONSTRAINT FK_76CDDA0637FCD1D8 FOREIGN KEY (idBankAccounts) REFERENCES co_bank_account (id) ON DELETE CASCADE;
ALTER TABLE co_contact_bank_accounts ADD CONSTRAINT FK_76CDDA0660E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_emails DROP FOREIGN KEY FK_898296312F9040C8;
ALTER TABLE co_contact_emails DROP FOREIGN KEY FK_8982963160E33F28;
ALTER TABLE co_contact_emails ADD CONSTRAINT FK_898296312F9040C8 FOREIGN KEY (idEmails) REFERENCES co_emails (id) ON DELETE CASCADE;
ALTER TABLE co_contact_emails ADD CONSTRAINT FK_8982963160E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_faxes DROP FOREIGN KEY FK_61EBBEA260E33F28;
ALTER TABLE co_contact_faxes DROP FOREIGN KEY FK_61EBBEA2CF6A2007;
ALTER TABLE co_contact_faxes ADD CONSTRAINT FK_61EBBEA260E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_faxes ADD CONSTRAINT FK_61EBBEA2CF6A2007 FOREIGN KEY (idFaxes) REFERENCES co_faxes (id) ON DELETE CASCADE;
ALTER TABLE co_contact_notes DROP FOREIGN KEY FK_B85E7B3416DFE591;
ALTER TABLE co_contact_notes DROP FOREIGN KEY FK_B85E7B3460E33F28;
ALTER TABLE co_contact_notes ADD CONSTRAINT FK_B85E7B3416DFE591 FOREIGN KEY (idNotes) REFERENCES co_notes (id) ON DELETE CASCADE;
ALTER TABLE co_contact_notes ADD CONSTRAINT FK_B85E7B3460E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_phones DROP FOREIGN KEY FK_262B509660E33F28;
ALTER TABLE co_contact_phones DROP FOREIGN KEY FK_262B50968039866F;
ALTER TABLE co_contact_phones ADD CONSTRAINT FK_262B509660E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_phones ADD CONSTRAINT FK_262B50968039866F FOREIGN KEY (idPhones) REFERENCES co_phones (id) ON DELETE CASCADE;
ALTER TABLE co_contact_social_media_profiles DROP FOREIGN KEY FK_74FF4CC0573F8344;
ALTER TABLE co_contact_social_media_profiles DROP FOREIGN KEY FK_74FF4CC060E33F28;
ALTER TABLE co_contact_social_media_profiles ADD CONSTRAINT FK_74FF4CC0573F8344 FOREIGN KEY (idSocialMediaProfiles) REFERENCES co_social_media_profiles (id) ON DELETE CASCADE;
ALTER TABLE co_contact_social_media_profiles ADD CONSTRAINT FK_74FF4CC060E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_urls DROP FOREIGN KEY FK_99D86D75969693F;
ALTER TABLE co_contact_urls DROP FOREIGN KEY FK_99D86D760E33F28;
ALTER TABLE co_contact_urls ADD CONSTRAINT FK_99D86D75969693F FOREIGN KEY (idUrls) REFERENCES co_urls (id) ON DELETE CASCADE;
ALTER TABLE co_contact_urls ADD CONSTRAINT FK_99D86D760E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE se_group_roles DROP FOREIGN KEY FK_9713F725937C91EA;
ALTER TABLE se_group_roles DROP FOREIGN KEY FK_9713F725A1FA6DDA;
ALTER TABLE se_group_roles ADD CONSTRAINT FK_9713F725937C91EA FOREIGN KEY (idGroups) REFERENCES se_groups (id) ON DELETE CASCADE;
ALTER TABLE se_group_roles ADD CONSTRAINT FK_9713F725A1FA6DDA FOREIGN KEY (idRoles) REFERENCES se_roles (id) ON DELETE CASCADE;
ALTER TABLE we_analytics_domains DROP FOREIGN KEY FK_F9323B6EA7A91E0B;
ALTER TABLE we_analytics_domains DROP FOREIGN KEY FK_F9323B6EEAC2E688;
ALTER TABLE we_analytics_domains ADD CONSTRAINT FK_F9323B6EA7A91E0B FOREIGN KEY (domain) REFERENCES we_domains (id) ON DELETE CASCADE;
ALTER TABLE we_analytics_domains ADD CONSTRAINT FK_F9323B6EEAC2E688 FOREIGN KEY (analytics) REFERENCES we_analytics (id) ON DELETE CASCADE;
```

## 2.4.1

### Change PreviewLinkInterface

Added the following method to the `PreviewLinkInterface`:

```php
public static function create(string $token, string $resourceKey, string $resourceId, string $locale, array $options): self;
```

## 2.4.0

### Added PreviewLink resource to PreviewBundle

To update your database schema to include the new table, you need to execute the following SQL statements:

```sql
CREATE TABLE pr_preview_links (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(12) NOT NULL, resourceKey VARCHAR(255) NOT NULL, resourceId VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, options JSON NOT NULL, visitCount INT NOT NULL, lastVisit DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_9A45BD685F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
```

To access the preview-link as anonymous user add following rule to the `access_control` section in your 
`config/packages/security.yaml`:

```diff
security:
    ...
    
    access_control:
         ...
         - { path: ^/admin/$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
+        - { path: ^/admin/p/, roles: IS_AUTHENTICATED_ANONYMOUSLY }
         - { path: ^/admin, roles: ROLE_USER }
```

Additionally, you need to include the routes of the bundle in your `config/routes/sulu_admin.yaml`:

```yaml
sulu_preview_api:
    type: rest
    resource: "@SuluPreviewBundle/Resources/config/routing_api.yml"
    prefix: /admin/api

sulu_preview_public:
    resource: "@SuluPreviewBundle/Resources/config/routing_public.yml"
    prefix: /admin/p
```

### Changed constructor of PageObjectProvider

- `Sulu\Bundle\PageBundle\Preview\PageObjectProvider`

### PreviewObjectProviderInterface was changed

A new method has been added to the `PreviewObjectProviderInterface`:

- `getSecurityContext`

### StorageInterface was changed

A new method has been added to the `StorageInterface` to allow for moving files:

- `move`

### ContactInterface was changed

Extends now from the `AuditableInterface`:

- `getCreated`
- `getChanged`
- `getCreator`
- `getChanger`

### MediaInterface was changed

A new method has been added to the `MediaInterface`:

- `setCreated`

### CategoryInterface was changed

A new method has been added to the `CategoryInterface`:

- `setCreated`

### Changed constructor of multiple services to integrate them with the SuluTrashBundle

To integrate the `SuluTrashBundle` with the existing services, the constructor of the following services was
adjusted. If you have extended one of these services in your project, you need to adjust your `parent::__construct`
call to pass the correct parameters:

- `Sulu\Bundle\TagBundle\Tag\TagManager`
- `Sulu\Bundle\CategoryBundle\Category\CategoryManager`
- `Sulu\Bundle\MediaBundle\Media\Manager\MediaManager`
- `Sulu\Bundle\WebsiteBundle\Analytics\AnalyticsManager`
- `Sulu\Bundle\ContactBundle\Contact\ContactManager`
- `Sulu\Bundle\ContactBundle\Controller\AccountController`
- `Sulu\Bundle\MediaBundle\Collection\CollectionManager`

### Added SuluTrashBundle to make resources trashable/restorable

A new bundle was added to the `sulu/sulu` package. The `SuluTrashBundle` implements the functionality to move resources
to trash and restore them from trash. To register the services of the bundle in your project, you need
to add the bundle to your `config/bundles.php` file:

```diff
+    Sulu\Bundle\TrashBundle\SuluTrashBundle::class => ['all' => true],
```

To update your database schema to include the tables that are used by the bundle, you need to execute the following SQL statements:

```sql
CREATE TABLE tr_trash_items (id INT AUTO_INCREMENT NOT NULL, resourceKey VARCHAR(191) NOT NULL, resourceId VARCHAR(191) NOT NULL, restoreData JSON NOT NULL, resourceSecurityContext VARCHAR(191) DEFAULT NULL, resourceSecurityObjectType VARCHAR(191) DEFAULT NULL, resourceSecurityObjectId VARCHAR(191) DEFAULT NULL, storeTimestamp DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', defaultLocale VARCHAR(191) DEFAULT NULL, userId INT DEFAULT NULL, INDEX IDX_102989B64B64DCC (userId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE tr_trash_items ADD restoreType VARCHAR(191) DEFAULT NULL, ADD restoreOptions JSON NOT NULL;
CREATE TABLE tr_trash_item_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(191) DEFAULT NULL, title VARCHAR(191) NOT NULL, trashItemId INT NOT NULL, INDEX IDX_8264DAF45C8D7CA (trashItemId), UNIQUE INDEX UNIQ_8264DAF45C8D7CA4180C698 (trashItemId, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE tr_trash_items ADD CONSTRAINT FK_102989B64B64DCC FOREIGN KEY (userId) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE tr_trash_item_translations ADD CONSTRAINT FK_8264DAF45C8D7CA FOREIGN KEY (trashItemId) REFERENCES tr_trash_items (id) ON DELETE CASCADE;
ALTER TABLE tr_trash_items ADD INDEX IDX_102989B5DAEB55C8CF57CB1 (resourceKey, resourceId);
```

> For MYSQL 5.6 and lower, the `JSON` type of the `restoreData` column must be replaced with `TEXT`. See the [doctrine/dbal type](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#json) documentation.

Additionally, you need to include the routes of the bundle in your `config/routes/sulu_admin.yaml`:

```yaml
sulu_trash_api:
    resource: "@SuluTrashBundle/Resources/config/routing_api.yml"
    type: rest
    prefix: /admin/api
```

Furthermore, you need to add the Javascript code of the bundle to the dependencies section in your `assets/admin/package.json`:

```json
"sulu-trash-bundle": "file:node_modules/@sulu/vendor/sulu/sulu/src/Sulu/Bundle/TrashBundle/Resources/js",
```

Finally, you need to import the `SuluTrashBundle` Javascript code in your `assets/admin/index.js` file:

```js
import 'sulu-trash-bundle';
```

### AccessControlRepositoryInterface has changed

A new method has been added to the `AccessControlRepositoryInterface`:

- `findIdsWithGrantedPermissions`

### CategoryRepositoryInterface has changed

A new method has been added to the `CategoryRepositoryInterface`:

- `findDescendantCategoryResources`

### MediaRepositoryInterface has changed

A new method has been added to the `MediaRepositoryInterface`:

- `findMediaResourcesByCollection`

### CollectionRepositoryInterface has changed

Two new methods have been added to the `CollectionManagerInterface`:

- `findDescendantCollectionResources`
- `countUnauthorizedDescendantCollections`

## 2.3.12

### Add DELETE SET NULL to account parent relation

There did exist a bug in sulu when trying to remove an account entity
which was connected to another account entity it was not possible to
remove it. The `DELETE SET NULL` on the parent connection will solve
this issue. For this a database change is required:

```sql
ALTER TABLE co_accounts DROP FOREIGN KEY FK_805CD14AC9171171;
ALTER TABLE co_accounts ADD CONSTRAINT FK_805CD14AC9171171 FOREIGN KEY (idAccountsParent) REFERENCES co_accounts (id) ON DELETE SET NULL
```

### Add ON DELETE CASCADE to many-to-many relations

There was a bug in sulu that prevented removal of tags if the deleted
tag was still connected to an account or contact. The `ON DELETE CASCADE`
will clean up broken references for deleted tags, accounts or contacts.
For this a database change is required:

```sql
ALTER TABLE co_account_tags DROP FOREIGN KEY FK_E8D920051C41CAB8;
ALTER TABLE co_account_tags DROP FOREIGN KEY FK_E8D92005996BB4F7;
ALTER TABLE co_account_tags ADD CONSTRAINT FK_E8D920051C41CAB8 FOREIGN KEY (idTags) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE co_account_tags ADD CONSTRAINT FK_E8D92005996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_tags DROP FOREIGN KEY FK_4CB525501C41CAB8;
ALTER TABLE co_contact_tags DROP FOREIGN KEY FK_4CB5255060E33F28;
ALTER TABLE co_contact_tags ADD CONSTRAINT FK_4CB525501C41CAB8 FOREIGN KEY (idTags) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE co_contact_tags ADD CONSTRAINT FK_4CB5255060E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
```

Similar issues can be found in other many-to-many relations. The
following database change will fix these references as well:

```sql
ALTER TABLE co_account_bank_accounts DROP FOREIGN KEY FK_C873A53237FCD1D8;
ALTER TABLE co_account_bank_accounts DROP FOREIGN KEY FK_C873A532996BB4F7;
ALTER TABLE co_account_bank_accounts ADD CONSTRAINT FK_C873A53237FCD1D8 FOREIGN KEY (idBankAccounts) REFERENCES co_bank_account (id) ON DELETE CASCADE;
ALTER TABLE co_account_bank_accounts ADD CONSTRAINT FK_C873A532996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_emails DROP FOREIGN KEY FK_3E246FC32F9040C8;
ALTER TABLE co_account_emails DROP FOREIGN KEY FK_3E246FC3996BB4F7;
ALTER TABLE co_account_emails ADD CONSTRAINT FK_3E246FC32F9040C8 FOREIGN KEY (idEmails) REFERENCES co_emails (id) ON DELETE CASCADE;
ALTER TABLE co_account_emails ADD CONSTRAINT FK_3E246FC3996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_faxes DROP FOREIGN KEY FK_7A4E77DC996BB4F7;
ALTER TABLE co_account_faxes DROP FOREIGN KEY FK_7A4E77DCCF6A2007;
ALTER TABLE co_account_faxes ADD CONSTRAINT FK_7A4E77DC996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_faxes ADD CONSTRAINT FK_7A4E77DCCF6A2007 FOREIGN KEY (idFaxes) REFERENCES co_faxes (id) ON DELETE CASCADE;
ALTER TABLE co_account_notes DROP FOREIGN KEY FK_A3FBB24A16DFE591;
ALTER TABLE co_account_notes DROP FOREIGN KEY FK_A3FBB24A996BB4F7;
ALTER TABLE co_account_notes ADD CONSTRAINT FK_A3FBB24A16DFE591 FOREIGN KEY (idNotes) REFERENCES co_notes (id) ON DELETE CASCADE;
ALTER TABLE co_account_notes ADD CONSTRAINT FK_A3FBB24A996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_phones DROP FOREIGN KEY FK_918DA9648039866F;
ALTER TABLE co_account_phones DROP FOREIGN KEY FK_918DA964996BB4F7;
ALTER TABLE co_account_phones ADD CONSTRAINT FK_918DA9648039866F FOREIGN KEY (idPhones) REFERENCES co_phones (id) ON DELETE CASCADE;
ALTER TABLE co_account_phones ADD CONSTRAINT FK_918DA964996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_social_media_profiles DROP FOREIGN KEY FK_E06F75F5573F8344;
ALTER TABLE co_account_social_media_profiles DROP FOREIGN KEY FK_E06F75F5996BB4F7;
ALTER TABLE co_account_social_media_profiles ADD CONSTRAINT FK_E06F75F5573F8344 FOREIGN KEY (idSocialMediaProfiles) REFERENCES co_social_media_profiles (id) ON DELETE CASCADE;
ALTER TABLE co_account_social_media_profiles ADD CONSTRAINT FK_E06F75F5996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_account_urls DROP FOREIGN KEY FK_ADF183825969693F;
ALTER TABLE co_account_urls DROP FOREIGN KEY FK_ADF18382996BB4F7;
ALTER TABLE co_account_urls ADD CONSTRAINT FK_ADF183825969693F FOREIGN KEY (idUrls) REFERENCES co_urls (id) ON DELETE CASCADE;
ALTER TABLE co_account_urls ADD CONSTRAINT FK_ADF18382996BB4F7 FOREIGN KEY (idAccounts) REFERENCES co_accounts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_bank_accounts DROP FOREIGN KEY FK_76CDDA0637FCD1D8;
ALTER TABLE co_contact_bank_accounts DROP FOREIGN KEY FK_76CDDA0660E33F28;
ALTER TABLE co_contact_bank_accounts ADD CONSTRAINT FK_76CDDA0637FCD1D8 FOREIGN KEY (idBankAccounts) REFERENCES co_bank_account (id) ON DELETE CASCADE;
ALTER TABLE co_contact_bank_accounts ADD CONSTRAINT FK_76CDDA0660E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_emails DROP FOREIGN KEY FK_898296312F9040C8;
ALTER TABLE co_contact_emails DROP FOREIGN KEY FK_8982963160E33F28;
ALTER TABLE co_contact_emails ADD CONSTRAINT FK_898296312F9040C8 FOREIGN KEY (idEmails) REFERENCES co_emails (id) ON DELETE CASCADE;
ALTER TABLE co_contact_emails ADD CONSTRAINT FK_8982963160E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_faxes DROP FOREIGN KEY FK_61EBBEA260E33F28;
ALTER TABLE co_contact_faxes DROP FOREIGN KEY FK_61EBBEA2CF6A2007;
ALTER TABLE co_contact_faxes ADD CONSTRAINT FK_61EBBEA260E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_faxes ADD CONSTRAINT FK_61EBBEA2CF6A2007 FOREIGN KEY (idFaxes) REFERENCES co_faxes (id) ON DELETE CASCADE;
ALTER TABLE co_contact_notes DROP FOREIGN KEY FK_B85E7B3416DFE591;
ALTER TABLE co_contact_notes DROP FOREIGN KEY FK_B85E7B3460E33F28;
ALTER TABLE co_contact_notes ADD CONSTRAINT FK_B85E7B3416DFE591 FOREIGN KEY (idNotes) REFERENCES co_notes (id) ON DELETE CASCADE;
ALTER TABLE co_contact_notes ADD CONSTRAINT FK_B85E7B3460E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_phones DROP FOREIGN KEY FK_262B509660E33F28;
ALTER TABLE co_contact_phones DROP FOREIGN KEY FK_262B50968039866F;
ALTER TABLE co_contact_phones ADD CONSTRAINT FK_262B509660E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_phones ADD CONSTRAINT FK_262B50968039866F FOREIGN KEY (idPhones) REFERENCES co_phones (id) ON DELETE CASCADE;
ALTER TABLE co_contact_social_media_profiles DROP FOREIGN KEY FK_74FF4CC0573F8344;
ALTER TABLE co_contact_social_media_profiles DROP FOREIGN KEY FK_74FF4CC060E33F28;
ALTER TABLE co_contact_social_media_profiles ADD CONSTRAINT FK_74FF4CC0573F8344 FOREIGN KEY (idSocialMediaProfiles) REFERENCES co_social_media_profiles (id) ON DELETE CASCADE;
ALTER TABLE co_contact_social_media_profiles ADD CONSTRAINT FK_74FF4CC060E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE co_contact_urls DROP FOREIGN KEY FK_99D86D75969693F;
ALTER TABLE co_contact_urls DROP FOREIGN KEY FK_99D86D760E33F28;
ALTER TABLE co_contact_urls ADD CONSTRAINT FK_99D86D75969693F FOREIGN KEY (idUrls) REFERENCES co_urls (id) ON DELETE CASCADE;
ALTER TABLE co_contact_urls ADD CONSTRAINT FK_99D86D760E33F28 FOREIGN KEY (idContacts) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE se_group_roles DROP FOREIGN KEY FK_9713F725937C91EA;
ALTER TABLE se_group_roles DROP FOREIGN KEY FK_9713F725A1FA6DDA;
ALTER TABLE se_group_roles ADD CONSTRAINT FK_9713F725937C91EA FOREIGN KEY (idGroups) REFERENCES se_groups (id) ON DELETE CASCADE;
ALTER TABLE se_group_roles ADD CONSTRAINT FK_9713F725A1FA6DDA FOREIGN KEY (idRoles) REFERENCES se_roles (id) ON DELETE CASCADE;
ALTER TABLE we_analytics_domains DROP FOREIGN KEY FK_F9323B6EA7A91E0B;
ALTER TABLE we_analytics_domains DROP FOREIGN KEY FK_F9323B6EEAC2E688;
ALTER TABLE we_analytics_domains ADD CONSTRAINT FK_F9323B6EA7A91E0B FOREIGN KEY (domain) REFERENCES we_domains (id) ON DELETE CASCADE;
ALTER TABLE we_analytics_domains ADD CONSTRAINT FK_F9323B6EEAC2E688 FOREIGN KEY (analytics) REFERENCES we_analytics (id) ON DELETE CASCADE;
```

## 2.3.7

### Add missing `kernel.reset` tag for document manager cache services

The configured `doctrine_phpcr.meta_cache_provider` and `doctrine_phpcr.nodes_cache_provider`
in the `config/packages/prod/sulu_document_manager.yaml` should be tagged with `kernel.reset`
to be correctly be reseted:

```diff
# config/packages/prod/sulu_document_manager.yaml

# ...

services:
    doctrine_phpcr.meta_cache_provider:
        class: Symfony\Component\Cache\DoctrineProvider
        public: false
        arguments:
            - '@doctrine_phpcr.meta_cache_pool'
+        tags:
+            - { name: 'kernel.reset', method: 'reset' }

    doctrine_phpcr.nodes_cache_provider:
        class: Symfony\Component\Cache\DoctrineProvider
        public: false
        arguments:
            - '@doctrine_phpcr.nodes_cache_pool'
+        tags:
+            - { name: 'kernel.reset', method: 'reset' }

# ...
```

### Add missing on delete cascade on CategoryTranslation to Keyword relation

The relation table `ca_category_translation_keywords` is missing a `ON DELETE CASCADE`
to the related entities.

```sql
ALTER TABLE ca_category_translation_keywords DROP FOREIGN KEY FK_D15FBE3717CA14DA;
ALTER TABLE ca_category_translation_keywords DROP FOREIGN KEY FK_D15FBE37F9FC9F05;
ALTER TABLE ca_category_translation_keywords ADD CONSTRAINT FK_D15FBE3717CA14DA FOREIGN KEY (idCategoryTranslations) REFERENCES ca_category_translations (id) ON DELETE CASCADE;
ALTER TABLE ca_category_translation_keywords ADD CONSTRAINT FK_D15FBE37F9FC9F05 FOREIGN KEY (idKeywords) REFERENCES ca_keywords (id) ON DELETE CASCADE;
```

## 2.3.6

### Add doctrine/dbal 3 and doctrine/orm 2.10 support

The doctrine/orm 2.10 did remove the deprecated `json_array` type which need to be patched to `json` type.

```sql
ALTER TABLE se_role_settings CHANGE value value JSON NOT NULL;
ALTER TABLE we_analytics CHANGE content content JSON NOT NULL;

-- if you use audience targeting also the following is required:
ALTER TABLE at_target_group_conditions CHANGE condition condition JSON NOT NULL
```

If you upgrade doctrine/dbal to Version 3 see the [DBAL 3.0 UPGRADE.md](https://github.com/doctrine/dbal/blob/3.1.x/UPGRADE.md#upgrade-to-30).

Else you should define the doctrine/dbal version to ^2.10 with:

```bash
composer require doctrine/dbal:^2.10 --no-update
composer update
```

## 2.3.5

### Migrate access control entityIdInteger field

Because of performance problems, an additional integer representation of the
entityId need to be added to the access control list.

MySQL:

```sql
ALTER TABLE se_access_controls ADD entityIdInteger INT DEFAULT NULL;
CREATE INDEX IDX_C526DC524473BB7A ON se_access_controls (entityIdInteger);

UPDATE se_access_controls
SET entityIdInteger = entityId
WHERE entityIdInteger IS NULL
    AND LENGTH(entityId) != 36;
```

PostgreSQL:

```sql
ALTER TABLE se_access_controls ADD entityIdInteger INT DEFAULT NULL;
CREATE INDEX IDX_C526DC524473BB7A ON se_access_controls (entityIdInteger);

UPDATE se_access_controls
SET entityIdInteger = CAST(entityId AS int)
WHERE entityIdInteger IS NULL
    AND LENGTH(entityId) != 36;
```

## 2.3.1

### Migrate permissions properties for pages

The role-specific PHPCR properties used for storing page permissions decrease performance when
used in combination with website security. To mitigate the problem and improve performance, all permissions 
are now stored in a single property. 

If you use page-specific permissions in your project, you need to migrate the existing data by running the 
phpcr migration command:

```bash
bin/console phpcr:migrations:migrate
```

### MediaAdmin constructor changed

A new argument `$activityViewBuilderFactory` has been added to the constructor of the `MediaAdmin` and `PageAdmin` class.

## 2.3.0

### Preview updateContext method

The method `Preview::updateContext` has been extended with the `data` argument. The argument is necessary
to make sure that the rendered data are consistent with the template.

## 2.3.0-RC1

### Auditable Fields to User

The `User` entity was extended with `creator`, `changer`, `created` and `changed` fields.
For this the following database migration is needed:

```sql
ALTER TABLE se_users ADD created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(), ADD changed DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(), ADD idUsersCreator INT DEFAULT NULL, ADD idUsersChanger INT DEFAULT NULL;
ALTER TABLE se_users CHANGE created created DATETIME NOT NULL, CHANGE changed changed DATETIME NOT NULL;
ALTER TABLE se_users ADD CONSTRAINT FK_B10AC28EDBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE se_users ADD CONSTRAINT FK_B10AC28E30D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
CREATE INDEX IDX_B10AC28EDBF11E1D ON se_users (idUsersCreator);
CREATE INDEX IDX_B10AC28E30D07CD5 ON se_users (idUsersChanger);
```

### Deprecated table adapters `table_light` and `tree_table_slim`

The two adapters `table_light` and `tree_table_slim` are deprecated and will be removed in `3.0`.

If you have used these adapters, you should use the default adapter and add the modifications through
the `adapterOptions`. 

#### table_light

```diff
{
-    ->addListAdapters(['table_light'])
+    ->addListAdapters(['table'])
+    ->addAdapterOptions(
+        [
+            'table' => [
+                'skin' => 'light',
+            ],
+        ]
+   )
}
```

#### tree_table_slim

```diff
{
-    ->addListAdapters(['tree_table_slim'])
+    ->addListAdapters(['tree_table'])
+    ->addAdapterOptions(
+        [
+            'tree_table' => [
+                'show_header' => false,
+            ],
+        ]
+   )
}
```

### React Tabs skin was removed

The `skin` prop and `small` prop of the `Tabs` component was replaced with a `type` prop.
Currently there are three `types` available: `root`, `nested` and `inline`. If you have used 
the `Tabs` component with a skin, be sure to replace them with the corresponding type. 

- `type='root'` is the same as `skin='default'`.
- `type='inline'` is similar to `small=true` and `skin='transparent'`.

### Added removeFileVersion method to MediaManagerInterface

The `MediaManagerInterface` declares a new method `removeFileVersion`.
If you have overridden this service in your project without extending from Sulu's `MediaManager`,
you need to implement this new method in order for the `MediaVersionRemovedEvent` to be emitted.

### Sync object permissions stored in phpcr to doctrine

To enable permission checking on database level for resources with object permissions stored in phpcr,
you need to sync these permissions into doctrine. A command is available for that:

```bash
bin/adminconsole sulu:security:sync-phpcr-permissions
```

This command needs to be executed just once when upgrading sulu to 2.3,
in the future the permissions are being synced automatically.

### Change entityId field in AccessControl entity to string

To allow entities with uuid's instead of auto generated ids to have object permissions,
the `entityId` field of the `AccessControl` entity had to be changed from `integer` to `string`.
Therefore the following sql statement needs to be executed.

```sql
ALTER TABLE se_access_controls CHANGE entityId entityId VARCHAR(36) NOT NULL;
```

### JS Dependencies updated

We always try to keep sulu compatible with newest dependencies in this release
we did update the following JS packages to a newer major version:

**CKeditor**

If you use a custom ckeditor plugin make sure that it is compatible to `^27.1.0` of the
`@ckeditor` package in your `assets/admin/package.json`.

```json
    "@ckeditor/ckeditor5-dev-utils": "^24.2.1",
    "@ckeditor/ckeditor5-theme-lark": "^27.1.0",
```

### Extend DocumentManagerInterface

The `DocumentManagerInterface` has been extended with a new method `copyLocale`. If you have overridden this service
in your project, you have to implement that method as well.

### Changed constructor of multiple services to integrate them with the SuluActivityBundle

To integrate the `SuluActivityBundle` with the existing services, the constructor of the following services was 
adjusted. If you have extended one of these services in your project, you need to adjust your `parent::__construct` 
call to pass the correct parameters:

- `Sulu\Component\CustomUrl\Manager\CustomUrlManager`
- `Sulu\Bundle\TagBundle\Tag\TagManager`
- `Sulu\Bundle\CategoryBundle\Category\CategoryManager`
- `Sulu\Bundle\CategoryBundle\Category\KeywordManager`
- `Sulu\Bundle\WebsiteBundle\Analytics\AnalyticsManager`
- `Sulu\Bundle\ContactBundle\Contact\ContactManager`
- `Sulu\Bundle\ContactBundle\Controller\ContactMediaController`
- `Sulu\Bundle\ContactBundle\Controller\PositionController`
- `Sulu\Bundle\ContactBundle\Controller\ContactTitleController`
- `Sulu\Bundle\ContactBundle\Controller\AccountMediaController`
- `Sulu\Bundle\ContactBundle\Controller\AccountController`
- `Sulu\Bundle\ContactBundle\Contact\AccountManager`
- `Sulu\Bundle\SecurityBundle\Controller\ResettingController`
- `Sulu\Bundle\SecurityBundle\Controller\RoleController`
- `Sulu\Bundle\SecurityBundle\UserManager\UserManager`
- `Sulu\Bundle\MediaBundle\Media\Manager\MediaManager`
- `Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManager`
- `Sulu\Bundle\MediaBundle\Media\FormatOptions\FormatOptionsManager`
- `Sulu\Bundle\MediaBundle\Controller\MediaPreviewController`
- `Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManager`

### Added SuluActivityBundle for recording activities in the application

A new bundle was added to the `sulu/sulu` package. The `SuluActivityBundle` implements a central hub for dispatching
events and recording activities in the application. To register the services of the bundle in your project, you need
to add the bundle to your `config/bundles.php` file:

```diff
+    Sulu\Bundle\ActivityBundle\SuluActivityBundle::class => ['all' => true],
```

Additionally, you need to update your database schema to include the tables that are used by the bundle:

```sql
CREATE TABLE ac_activities (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(191) NOT NULL, context JSON NOT NULL, timestamp DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', batch VARCHAR(191) DEFAULT NULL, payload JSON DEFAULT NULL, resourceKey VARCHAR(191) NOT NULL, resourceId VARCHAR(191) NOT NULL, resourceLocale VARCHAR(191) DEFAULT NULL, resourceWebspaceKey VARCHAR(191) DEFAULT NULL, resourceTitle VARCHAR(191) DEFAULT NULL, resourceTitleLocale VARCHAR(191) DEFAULT NULL, resourceSecurityContext VARCHAR(191) DEFAULT NULL, resourceSecurityObjectType VARCHAR(191) DEFAULT NULL, resourceSecurityObjectId VARCHAR(191) DEFAULT NULL, userId INT DEFAULT NULL, INDEX IDX_3EE015D064B64DCC (userId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

ALTER TABLE ac_activities ADD CONSTRAINT FK_3EE015D064B64DCC FOREIGN KEY (userId) REFERENCES se_users (id) ON DELETE SET NULL;
```

> For MYSQL 5.6 and lower, the `JSON` type of the `context` and `payload` columns must be replaced with `TEXT`. See the [doctrine/dbal type](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#json) documentation.

Finally, you need to include the routes of the bundle in your `config/routes/sulu_admin.yaml`:

```yaml
sulu_activity_api:
    resource: "@SuluActivityBundle/Resources/config/routing_api.yml"
    type: rest
    prefix: /admin/api
```

### Deprecated constructing `sulu_media.media_manager` with the `sulu_media.ffprobe` service

Instead of the `sulu_media.ffprobe` the new [`tagged_iterator`](https://symfony.com/doc/4.4/service_container/tags.html#reference-tagged-services)
of `sulu_media.media_properties_provider` should be injected into the `sulu_media.media_manager` service.

### Changed data-format used by the single_account_selection field-type

The `single_account_selection` field-type was adjusted to process an id instead of a serialized account entity.
This makes the data-format used by the `single_account_selection` field-type consistent to all other `single_*_selection` field types.

The data that is sent to the server by the field-type was changed like this:

```diff
{
-    "single_account_selection_property": {
-        "id": 1,
-        "name": "Test Account",
-        ...
-    },
+    "single_account_selection_property": 1,
}
```

If you have used the `single_account_selection` field-type in a form configuration for your custom entity,
you should adjust the API of the custom entity to be compatible with the new data-format. 
If you cannot adjust the API, you can use the `use_deprecated_object_data_format` param to bring back the old behaviour:

```diff
     <property name="single_account_selection_property" type="single_account_selection">
         <!-- .. -->
+        <params>
+            <param name="use_deprecated_object_data_format" value="true" />
+        </params>
     </property>
```

### Changed data-format used by the auto_complete type of single_selection field-type

The `auto_complete` type of `single_selection` field-type was adjusted to process an id instead of a serialized object.
This makes the data-format used by the `auto_complete` type consistent to all other `single_selection` types and therefore
allows to switch between different types.

If you have configured a `single_selection` field-type with the `auto_complete` type for your custom entity, 
the data that is sent to the server by the field-type is changed like this:

```diff
{
-    "auto_complete_single_selection_property": {
-        "id": 1,
-        "name": "...",
-        ...
-    },
+    "auto_complete_single_selection_property": 1,
}
```

If you have used such a field-type in a form configuration for your custom entity, you should adjust the API of the 
custom entity to be compatible with the new data-format.
If you cannot adjust the API, you can use the `use_deprecated_object_data_format` param to bring back the old behaviour:

```diff
     <property name="auto_complete_single_selection_property" type="single_entity_selection">
         <!-- .. -->
+        <params>
+            <param name="use_deprecated_object_data_format" value="true" />
+        </params>
     </property>
```

### Adjusted SingleAutoComplete component to accept SingleSelectionStore

The `SingleAutoComplete` container component was adjusted to accept a `SingleSelectionStore` instance via the `store` prop. 
Furthermore, the `onChange`, `resourceKey` and `value` prop were replaced by the `store` prop and have been removed.
This makes the implementation of the `SingleAutoComplete` consistent to the implementation of the `MultiAutoComplete`
and makes it easier to reuse the component.

If you are using the `SingleAutoComplete` container component in your custom javascript code, you need to adjust your 
code to instantiate a `SingleSelectionStore` object and pass it to the `store` prop.

### conditionDataProvider interface changed

The interface of `conditionDataProviders` changed its arguments. In order to make these providers even more powerful,
they now also get the `dataPath` passed. Also, instead of the `options` and `metadataOptions` a `formInspector` instance
is passed, which allows accessing both of the properties.

```javascript
// before
function (data: {[string]: any}, options: {[string]: any}, metadataOptions: {[string]: any}) {
    const webspaceKey = data.webspace || options.webspace || (metadataOptions && metadataOptions.webspace);
}

// after
function (data: {[string]: any}, dataPath: ?string, formInspector: FormInspector) {
    const {options, metadataOptions} = formInspector;
    const webspaceKey = data.webspace || options.webspace || (metadataOptions && metadataOptions.webspace);
}
```

### Props of Renderer component changed

The [`Renderer` React component](https://github.com/sulu/sulu/blob/release/2.2/src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/Renderer.js)
has changed its interface to support better evaluation of the `disbledCondition` and `visibleCondition`. Therefore it
needs the data of the entire form being passed to the `data` prop and the data for the fields rendered by the
`Renderer` need to be passed to the `value` prop.

### Deprecated path variable in twig

The `path` twig variable was deprecated because it was confused with the `url` property by many new developers.
The now deprecated variable contains the internal PHPCR path of a page which should not be exposed to the twig template.
You should disable the variable in your project via:

```yaml
# config/packages/sulu_website.yaml
sulu_website:
    twig:
        attributes:
            path: false
```

### Changes in `SchemaMetadata` classes

The metadata classes in the `Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata` namespace have changed significantly.

* The `PropertyMetadata` accepts a third optional attribute called `$schemaMetadata`, which allows to define the schema of that property.

* `ArrayMetadata` and `ConstMetadata` don't extend from `PropertyMetadata` anymore.
  Therfore the `$name` and `$mandatory` arguments have been removed from their constructors.

  To restore the same behaviour of these classes as before
  * `new ArrayMetadata($name, $mandatory, $itemsSchema)` has to be changed to `new PropertyMetadata($name, $mandatory, new ArrayMetadata($itemsSchema))` and
  * `new ConstMetadata($name, $mandatory, $value)` has to be changed to `new PropertyMetadata($name, $mandatory, new ConstMetadata($value))`.

* Various new metadata classes (like `ObjectMetadata`, `StringMetadata`, `NumberMetadata`, ...) have been introduced to allow better schema definitions of a property.

### The constructor of the `TeaserContentType` requires a new `$propertyMetadataMinMaxValueResolver` argument for full functionality

Without this service, `min` and `max` parameters of a `teaser_selection` property will not work.

### The constructor of the `MediaSelectionContentType` requires a new `$propertyMetadataMinMaxValueResolver` argument for full functionality

Without this service, `min` and `max` parameters of a `media_selection` property will not work.

### Added resolveConflict parameter to RouteManagerInterface::createOrUpdateByAttributes

The `createOrUpdateByAttributes` method of the `RouteManagerInterface` was adjusted to include a `resolveConflict`
parameter. This makes the available parameters consistent to the `RouteManagerInterface::create` method and the
`RouteManagerInterface::update` method.

If you have implemented this interface in your project, you need to add the parameter to the 
`createOrUpdateByAttributes` method of your implementation.

## 2.2.17

### Add missing `kernel.reset` tag for document manager cache services

The configured `doctrine_phpcr.meta_cache_provider` and `doctrine_phpcr.nodes_cache_provider`
in the `config/packages/prod/sulu_document_manager.yaml` should be tagged with `kernel.reset`
to be correctly be reseted:

```diff
# config/packages/prod/sulu_document_manager.yaml

# ...

services:
    doctrine_phpcr.meta_cache_provider:
        class: Symfony\Component\Cache\DoctrineProvider
        public: false
        arguments:
            - '@doctrine_phpcr.meta_cache_pool'
+        tags:
+            - { name: 'kernel.reset', method: 'reset' }

    doctrine_phpcr.nodes_cache_provider:
        class: Symfony\Component\Cache\DoctrineProvider
        public: false
        arguments:
            - '@doctrine_phpcr.nodes_cache_pool'
+        tags:
+            - { name: 'kernel.reset', method: 'reset' }

# ...
```

### Add missing on delete cascade on CategoryTranslation to Keyword relation

The relation table `ca_category_translation_keywords` is missing a `ON DELETE CASCADE`
to the related entities.

```sql
ALTER TABLE ca_category_translation_keywords DROP FOREIGN KEY FK_D15FBE3717CA14DA;
ALTER TABLE ca_category_translation_keywords DROP FOREIGN KEY FK_D15FBE37F9FC9F05;
ALTER TABLE ca_category_translation_keywords ADD CONSTRAINT FK_D15FBE3717CA14DA FOREIGN KEY (idCategoryTranslations) REFERENCES ca_category_translations (id) ON DELETE CASCADE;
ALTER TABLE ca_category_translation_keywords ADD CONSTRAINT FK_D15FBE37F9FC9F05 FOREIGN KEY (idKeywords) REFERENCES ca_keywords (id) ON DELETE CASCADE;
```

## 2.2.15

### Add doctrine/dbal 3 and doctrine/orm 2.10 support

The doctrine/orm 2.10 did remove the deprecated `json_array` type which need to be patched to `json` type.

```sql
ALTER TABLE se_role_settings CHANGE `value` `value` JSON NOT NULL;
ALTER TABLE we_analytics CHANGE `content` `content` JSON NOT NULL;

-- if you use audience targeting also the following is required:
ALTER TABLE at_target_group_conditions CHANGE `condition` `condition` JSON NOT NULL
```

If you upgrade doctrine/dbal to Version 3 see the [DBAL 3.0 UPGRADE.md](https://github.com/doctrine/dbal/blob/3.1.x/UPGRADE.md#upgrade-to-30).

Else you should define the doctrine/dbal version to ^2.10 with:

```bash
composer require doctrine/dbal:^2.10 --no-update
composer update
```

## 2.2.11

### Migrate permissions properties for pages

The role-specific PHPCR properties used for storing page permissions decrease performance when
used in combination with website security. To mitigate the problem and improve performance, all permissions 
are now stored in a single property. 

If you use page-specific permissions in your project, you need to migrate the existing data by running the 
phpcr migration command:

```bash
bin/console phpcr:migrations:migrate
```

## 2.2.6

### Changed ContentRepository to return title of source instead of link destination for internal link pages

The `ContentRepository` service was changed to return the title of the source page instead of the title of the destination
page for internal links. This makes the behaviour consistent with external links and the `ContentMapper` service. 
This change only affects you if you are using the `ContentRepository` service with a mapping that includes the `title` property.

### A new argument `$requestStack` has been added to the `ContentTwigExtension`

Instantiating a `ContentTwigExtension` without the `$requestStack` argument is deprecated.

## 2.2.5

### Deprecated `PageTeaserProvider` class

A new service `PHPCRPageTeaserProvider` has been added and will replace the old `PageTeaserProvider` in the future.
The constructor of `PageTeaserProvider` has been changed to accept the `PHPCRPageTeaserProvider` service as fourth argument.
If it's passed, the new `PHPCRPageTeaserProvider` will be used automatically instead of the old `PageTeaserProvider`.

### Removed PageOutOfBoundsException

The `PageOutOfBoundsException` has been removed in `Sulu\Component\SmartContent\ContentType`, because it
did not provide any advantage for the developer experience.

### Deprecated service `sulu_location.geolocator.guzzle.client` and parameter `sulu_location.guzzle.client.class`

Because `NominatimGeolocator` and `GoogleGeolocator` now use the symfony http client the `sulu_location.geolocator.guzzle.client` is now deprecated
as is the parameter `sulu_location.guzzle.client.class`.

### The constructor of the `NominatimGeolocator` and `GoogleGeolocator` requires a `Symfony\Contracts\HttpClient\HttpClientInterface` for the `$client` argument

Constructing `NominatimGeolocator` and `GoogleGeolocator` with the previous `GuzzleHttp\ClientInterface` is deprecated. 

### Doctrine changes for PHP 8 Support

To prepare for PHP8 support, the version constraints of the `doctrine/persistence` package and the `doctrine/dbal` package were updated 
to include a new major version. If you update these packages in your project, you might need to adjust the code of the project to be compatible with 
the new major version. To do this, it is enough to replace the `Doctrine/Common/Persistence` namespace with `Doctrine/Persistence` in most cases:

```diff
-    use Doctrine\Common\Persistence\ObjectManager;
+    use Doctrine\Persistence\ObjectManager;
```

Alternatively, if you want to prevent the upgrade of the packages, you can set the version constraint of the `doctrine/persistence` to `^1.3` 
and the `doctrine/dbal` package to `^2.6` in the `composer.json` of your project. But keep in mind that this means that your project will 
use outdated dependencies and will not be compatible with new PHP versions in this case.

## 2.2.4

### Increased maximum length of contact position name

To allow for longer contact position names, the length of the database column was increased. To do this in your project, you can use the following statement:

```SQL
ALTER TABLE co_positions CHANGE position position VARCHAR(191) NOT NULL;
```

## 2.2.2

### Added default value to anonymous column of se_roles table

The se_roles was adjusted to use a default value for the `anonymous` column:

```sql
ALTER TABLE `se_roles` CHANGE `anonymous` `anonymous` TINYINT(1) NOT NULL DEFAULT 0;
```

## 2.2.0-RC1

### Changed default value for single_select properties from empty string to null

The default value for properties with the type `single_select` was changed from an empty string (`''`) to `null` to
be consistent with the `*_single_selection` property types. If you depend on the default value being an empty string
in your twig template, you need to adjust your template.

### CKeditor update

Due to the update of the CKEditor you have to make sure that you are also using the latest ckeditor packages in your
application based on our skeleton. These are the packages you should reference in your `package.json`:

```
"@ckeditor/ckeditor5-dev-utils": "^23.5.1",
"@ckeditor/ckeditor5-theme-lark": "^23.0.0",
```

### Deprecation of passing action string to submit form functions

Passing an `action` string to the `submit` function of the `Form` react component is deprecated, instead an `options`
object is passed now, which is more flexible. The old way still works, but will log a warning and will be removed
in the next major release.

This change affects the following JavaScript code:
- `Form` container
- `Form` view
- `FormInspector`
- `SaveHandler` registered in the `FormInspector`

To avoid using the deprecation rewrite your code as shown in the next code snippet:

```javascript
// Before
form.submit('publish');
// After
form.submit({action: 'publish'});
```

### Role Entity changed for anonymous roles

Sulu needs to handle anonymous users, so we need additional anonymous roles.

Therefore the `Role` Entity needs a new field:

```sql
ALTER TABLE se_roles ADD anonymous TINYINT(1) NOT NULL;
```

The `RoleInterface` has changed and now contains a `getAnonymous` and `setAnonymous` method.

Now you need to run the following command to create the anonymous users:

```bash
bin/adminconsole sulu:security:init
```

### Deprecation of securityContextStore methods

The `loadSecurityContextGroups` and `loadAvailableActions` method from the `securityContextStore` have been deprecated.
Use the `getSecurityContextGroups` and `getAvailableActions` methods returning the data directly instead of in a
promise.

### Deprecation of ContextsController

The `ContextsController` has been deprecated. The information is now delivered via `sulu_security.securityContexts`
config in the `admin/config` endpoint.

### Disallow usage of "type" and "settings" as block property names

You are not allowed to use `type` and `settings` as names for properties within blocks. They already have special
meaning and using them has strange side effects. So we are actively throwing exceptions now to avoid this kind of
behavior.

### Deprecated urls variable in twig

The `urls` twig variable has been deprecated in favour of the `localizations` variable. So the code should be adapted
as shown in the following snippet:

```twig
{# Before #}
<ul>
    {% for locale, url in urls %}
        <li>
            <a href="{{ sulu_content_path(url, request.webspaceKey, locale) }}">{{ locale }}</a>
        </li>
    {% endfor %}
</ul>

{# After #}
<ul>
    {% for localization in localizations %}
        <li>
            <a href="{{ localization.url }}">{{ localization.locale }}</a>
        </li>
    {% endfor %}
</ul>
```

### HTTP Cache environment handling

The `sulu_http_cache` configuration behaved differently based on the configured environment. Since this behavior was
causing some configuration to be ignored in the `dev` and `test` environment, it was very hard to understand. Therefore
we removed this behavior. In order to imitate the old behavior, the `sulu_http_cache` has to be configured
appropriately. This can be done by moving the `config/packages/sulu_http_cache.yaml` file to
`config/packages/prod/sulu_http_cache.yaml`:

```bash
git mv config/packages/sulu_http_cache.yaml config/packages/prod/sulu_http_cache.yaml
```

Additionally a `sulu_http_cache` configuration for the `stage` environment should also be created:

```yaml
# config/packages/stage/sulu_http_cache.yaml
imports:
    - { resource: '../prod/sulu_http_cache.yaml' }
```

## 2.1.10

### Changed ContentRepository to return title of source instead of link destination for internal link pages

The `ContentRepository` service was changed to return the title of the source page instead of the title of the destination
page for internal links. This makes the behaviour consistent with external links and the `ContentMapper` service. 
This change only affects you if you are using the `ContentRepository` service with a mapping that includes the `title` property.

### A new argument `$requestStack` has been added to the `ContentTwigExtension`

Instantiating a `ContentTwigExtension` without the `$requestStack` argument is deprecated.

## 2.1.9

### Deprecated `PageTeaserProvider`

A new service `PHPCRPageTeaserProvider` has been added and will replace the old `PageTeaserProvider` in the future.
The constructor of `PageTeaserProvider` has been changed to accept the `PHPCRPageTeaserProvider` service as fourth argument.
If it's passed, the new `PHPCRPageTeaserProvider` will be used automatically instead of the old `PageTeaserProvider`.

### Removed PageOutOfBoundsException

The `PageOutOfBoundsException` has been removed in `Sulu\Component\SmartContent\ContentType`, because it
did not provide any advantage for the developer experience.

### Deprecated service `sulu_location.geolocator.guzzle.client` and parameter `sulu_location.guzzle.client.class`

Because `NominatimGeolocator` and `GoogleGeolocator` now use the symfony http client the `sulu_location.geolocator.guzzle.client` is now deprecated
as is the parameter `sulu_location.guzzle.client.class`.

### The constructor of the `NominatimGeolocator` and `GoogleGeolocator` requires a `Symfony\Contracts\HttpClient\HttpClientInterface` for the `$client` argument

Constructing `NominatimGeolocator` and `GoogleGeolocator` with the previous `GuzzleHttp\ClientInterface` is deprecated. 

### Doctrine changes for PHP 8 Support

To prepare for PHP8 support, the version constraints of the `doctrine/persistence` package and the `doctrine/dbal` package were updated 
to include a new major version. If you update these packages in your project, you might need to adjust the code of the project to be compatible with 
the new major version. To do this, it is enough to replace the `Doctrine/Common/Persistence` namespace with `Doctrine/Persistence` in most cases:

```diff
-    use Doctrine\Common\Persistence\ObjectManager;
+    use Doctrine\Persistence\ObjectManager;
```

Alternatively, if you want to prevent the upgrade of the packages, you can set the version constraint of the `doctrine/persistence` to `^1.3` 
and the `doctrine/dbal` package to `^2.6` in the `composer.json` of your project. But keep in mind that this means that your project will 
use outdated dependencies and will not be compatible with new PHP versions in this case.

## 2.1.8

### Increased maximum length of contact position name

To allow for longer contact position names, the length of the database column was increased. To do this in your project, you can use the following statement:

```SQL
ALTER TABLE co_positions CHANGE position position VARCHAR(191) NOT NULL;
```

## 2.1.6

### Smartcontent Type Filtering

The Smartcontent is now able to filter the content via the types (templates).
This results in two minor breaking changes in the `BuilderInterface` and the `ProviderConfigurationInterface`:

```diff
// src/Sulu/Component/SmartContent/Configuration/BuilderInterface.php
+    public function enableTypes(array $types = []);
```

```diff
// src/Sulu/Component/SmartContent/Configuration/ProviderConfigurationInterface.php
+    public function hasTypes(): bool;
```

If you implemented one of these interfaces, you have to add both methods to your custom implementation.

Furthermore the `PageDataProvider`, the `SnippetDataProvider` and the `MediaDataProvider` have been updated
with additional services. The injected services are optionally but usage of these providers without them will throw
a deprecation warning.

`PageDataProvider` and `SnippetDataProvider` now additionally requires the `FormMetadataProvider` and the `TokenStorageInterface` services.
The `MediaDataprovider` requires the `EntityManagerInterface` and the `TranslatorInterface`.

For the type filtering to work properly all those mentioned services are necessary.

In addition the reference to the `AudienceTargetingBundle` in the DataProviderPool is removed. Now every provider,
which requires the `AudienceTargetingBundle`, has to enable it by itself only when the `AudienceTargetingBundle` is really enabled.

## 2.1.3

### GhostDialog

When a page was opened in a ghost language, the `GhostDialog`, which allows to copy content from another locale,
appeared. But after copying the language, the API returned the wrong locale, and therefore the UI was broken. We fixed
that by separating the `src` and `locale` parameter in the pages API. This results in a different request being sent to
that API:

Before:

```
/admin/api/pages/d3354303-a11b-443f-9cb7-cace67a4e57c?webspace=sulu-test&action=copy-locale&dest=de&locale=en
```

After:

```
/admin/api/pages/d3354303-a11b-443f-9cb7-cace67a4e57c?webspace=sulu-test&action=copy-locale&src=en&dest=de&locale=de
```

Mind the change of the `locale` query parameter and the addition of the `src` parameter. In case you have reused that
functionality with some of your custom entities, you need to adjust your API so that they work with the new query
parameters.

### DateTime filter type

The DateTime filter type does now support time by default. If you want to reuse the "old" behaviour we have introduced
a Date filter type.

## 2.1.0-RC2

### Add RestRoutingBundle

To make the update to symfony 5 as seamless as possible a new bundle need to be registered in your `config/bundles.php`:

```php
HandcraftedInTheAlps\RestRoutingBundle\RestRoutingBundle::class => ['all' => true],
```

## 2.1.0-RC1

### Deprecated ExceptionController changed to ErrorController

Sulu will not longer use the deprecated TwigBundle `ExceptionController`.
Instead it decorates now the Framework `ErrorController` and does not longer contain the `currentContent` variable in error templates.

### DocumentFixtures changed

The document fixtures should be changed to services and be tagged with `sulu.document_manager_fixture`.
When using the symfony autoconfigure feature this is done automatically,
but you need to make sure the `DataFixtures\Document` folder is not excluded in the `services.yaml`.

Also the `--fixture` was replaced with a `--group` option on the `sulu:document:fixtures:load` command.
Which accept the classname like `AppFixture` or a specified group using the `DocumentFixtureGroupInterface` interface.

### Added webspace as reserved property name

The term `webspace` cannot be used as name in XML templates anymore, because we need the page API to return the
webspace of the page now, in order to create a deep link for them.

### Event classes changed

The Sulu `Event` classes extend now from the new `Symfony\Contracts\EventDispatcher\Event`
class instead of the deprecated `Symfony\Component\EventDispatcher\Event`.
If you have code depending on the old class you need to update it.

### DoctrineCacheBundle removed

The doctrine cache bundle requirement has been removed from sulu. The DoctrineCacheBundle is
still required when your project is using doctrine/doctrine-bundle ^1.12.

When you have configured the preview before using another cache adapter you need to change
the configuration the following way:

**before**:

```yaml
sulu_preview:
    cache:
        type: redis
        redis:
            host: "localhost"
```

**after**

```yaml
sulu_preview:
    cache_adapter: "cache.adapter.redis" # default here is `cache.app`

framework:
    default_redis_provider: 'redis://localhost' # this is default and not needed
```

Read more about symfony cache adapters [here](https://symfony.com/doc/4.4/cache.html#configuring-cache-with-frameworkbundle).

If you still want to use DoctrineCacheBundle you would need to require it in your `composer.json`.
Keep in mind that the DoctrineCacheBundle is deprecated and will not support Symfony 5.

### WebServerBundle removed

The Symfony WebServerBundle has been removed.
Use for development the [Symfony Local Webserver](https://symfony.com/doc/current/setup/symfony_server.html)
or the internal [php web server](https://www.php.net/manual/en/features.commandline.webserver.php) instead:

```bash
php -S localhost:8000 -t public/ config/router.php
```

### Kernel accept RoutingConfigurator

To support both the new `RoutingConfigurator` and the deprecated `RouteCollectionBuilder` in the SuluKernel the following methods have been changed.
Remove the type hints if you overwrote these methods in your `Kernel`:

**before**

```php
protected function configureRoutes(RouteCollectionBuilder $routes) {}

protected function import(RouteCollectionBuilder $routes, $confDir, $pattern) {}
```

**after**

```php
protected function import($routes, $confDir, $pattern) {}

protected function configureRoutes($routes)
```

### Add key property to Role entity

To allow for referencing `Role` entities by a human readable string, a `key` property was added to the `Role` entity.
Furthermore, if the `key` is set, it is used for generating the identifier of a role instead of the `name` property.
The following statements update the database to include the new property:

```sql
ALTER TABLE se_roles ADD role_key VARCHAR(60) DEFAULT NULL;
CREATE UNIQUE INDEX UNIQ_13B749A03EF22FDB ON se_roles (role_key);
```

### Backdrop component

Our `Backdrop` React component does not have the `local` and `open` props anymore. It is not supported anymore to render
the backdrop in a portal. Instead it will be rendered right where it is put in the component tree, and if you want it to
be in a `Portal` you have to put it there. The `open` prop has also been removed, since you can use conditional
rendering to render the `Backdrop` only if needed.

```javascript
// Before
<Backdrop open={open} />

// After
{open && <Backdrop />}
```

### CacheClearer service changed

The CacheClearer service `sulu_website.http_cache.clearer` constructor arguments has changed. The third argument the
`$kernelRootDir` has been removed. If you did overwrite or extend this service you need to update the constructor call of it.

### Router attributes

The `Router` service now interprets more into the URL parameters that are passed to it. In addition to parsing numbers,
boolean and strings to its JS equivalents, it will now do the same for `undefined` and dates in the format `yyyy-mm-dd`.

### Passing MultiSelectionStore to MultiAutoComplete

The `MultiAutoComplete` container component now accepts the `MultiSelectionStore` via the `store` prop, instead of
creating it on its own. Therefore the `filterParameter`, `locale`, `onChange`, `resourceKey` and `value` prop have been
removed. Instead a `MultiSelectionStore` has to be created manually and passed via the `store` prop.

### Configuration of list item actions

The prop of the `List` container which is used to configure the item actions was changed from `actions` to 
`itemActionsProvider`. The new prop accepts a function that returns an array of actions for a given item.
This allows to disable specific actions for specific items.

```javascript
// Before
render() {
    const actions = [
        {
            icon: 'su-process',
            onClick: this.handleRestoreClick,
        },
    ];

    return (
        <List
            actions={actions}
            adapters={['table']}
            store={this.listStore}
        />
    );
}

// After
render() {
    const itemActionsProvider = (item) => {
        return [
            {
                icon: 'su-process',
                onClick: this.handleRestoreClick,
                disabled: item.disabled
            },
        ];
    };

    return (
        <List
            adapters={['table']}
            itemActionsProvider={itemActionsProvider}
            store={this.listStore}
        />
    );
}
```

### Configuration of list filters

The old filter configuration on a list XML file was ignored until now. But the new list filtering functionality requires
some changes in order to work. That also has some effects on the list XML files. Mind that this only concerns you if
you had some `filter-type` attributes in your configurations, which would not have any effect at all until now.

```diff
-        <identity-property name="accountId" visibility="never" filter-type="auto-complete" translation="sulu_contact.organization">
+        <identity-property name="accountId" visibility="never" translation="sulu_contact.organization">
             <field-name>account</field-name>
             <entity-name>SuluContactBundle:AccountContact</entity-name>
 
             <joins ref="accountContact"/>
 
-            <filter-type-parameters>
-                <parameter key="singleUrl"><![CDATA[/admin/api/accounts/{id}]]></parameter>
-                <parameter key="remoteUrl">
-                    <![CDATA[/admin/api/accounts?searchFields=name,number&fields=id,name&flat=true]]>
-                </parameter>
-                <parameter key="resultKey">accounts</parameter>
-                <parameter key="valueKey">name</parameter>
-            </filter-type-parameters>
+            <filter type="selection">
+                <param name="displayProperty" value="name" />
+                <param name="resourceKey" value="accounts" />
+            </filter>
         </identity-property>
```

The `filter-type` property is gone, and replaced as `type` attribute on the new `filter` node within the `property` tag.
The `parameter` have been renamed to `param` to match the template XML files and take a `name` (instead of a `key`) and
a `value` (instead of using the child of the node) attribute. This was necessary to allow params to be nested:

```xml
<property name="type" visibility="never" translation="sulu_media.type">
    <filter type="dropdown">
        <param name="options" type="collection">
            <param name="audio" value="sulu_media.audio" />
            <param name="document" value="sulu_media.document" />
            <param name="image" value="sulu_media.image" />
            <param name="video" value="sulu_media.video" />
        </param>
    </filter>
</property>
```

### RouteRepositoryInterface changed

In the `RouteRepositoryInterface` a new remove method was introduced.

## 2.0.6

### sulu_page.templates ToolbarAction

The `sulu_page.templates` ToolbarAction was removed. It did basically the same as `sulu_admin.type`, so that should be
used as a replacement.

## 2.0.5

### Deprecation of localizationStore method

The `loadLocalizations` method from the `localizationStore` has been deprecated. Use the `localizations` synchronous 
property instead.

### Deprecation of webspaceStore methods

The `loadWebspaces` method from the `webspaceStore` has been deprecated. Use the `grantedWebspaces` synchronous
property instead.

### Deprecation of LocalizationController

The `LocalizationController` has been deprecated. The information is now delivered via `sulu_admin.localizations` config
in the `admin/config` endpoint. 

### Deprecation of WebspaceController

The `WebspacesController` has been deprecated. The information is now delivered via `sulu_page.webspaces` config
in the `admin/config` endpoint. 

### Add position to category medias

Currently the category media sorting was not saved for this the following database update is needed:

```sql
ALTER TABLE ca_category_translation_medias ADD id INT AUTO_INCREMENT NOT NULL, ADD position INT DEFAULT 0 NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id);
```

## 2.0.4

### Replace {host} placeholder right after loading webspaces

Previously a `host` parameter had to be passed when a route of type `portal` was generated on a webspace containing a
`{host}` placeholder in its URL. This is not necessary anymore, and if you still do it, it will cause a `host` query
parameter to be added to the generated URL.

### Preview constructor changed

The constructor of `Preview` has changed. The first argument isn't an array of `ObjectProviders` anymore, instead it's a
`PreviewObjectProviderRegistry`.

## 2.0.3

When upgrading also have a look at the changes in the
[sulu skeleton](https://github.com/sulu/skeleton/compare/2.0.2...2.0.3).

### New methods in ViewBuilderInterfaces

Following methods has been added:

* `ListViewBuilderInterface::addResourceStorePropertiesToListMetadata`
* `ListViewBuilderInterface::addRequestParameters`
* `FormViewBuilderInterface::addRequestParameters`
* `FormOverlayListViewBuilderInterface::addRequestParameters`
* `PreviewFormViewBuilderInterface::addRequestParameters`

### Ghost- and Shadow-Locale in ListAdapters

Our `ListAdapter`s, namely `table`, `table_light`, `tree_table`, `column_list`, `media_card_overview` and
`media_card_selection` are now always using the `ghostLocale` and `shadowLocale` properties from the resource instead of
the `type` property.

### Symfony/templating requirement removed

Sulu does not longer need the `symfony/templating` package which is deprecated.

If you depend on `symfony/templating` you need to require it in your project:

```bash
composer require symfony/templating
```

If you want to remove it also from your project you need to change the include syntax:

**Before**

```twig
{% include "SuluWebsiteBundle:Extension:seo.html.twig" %}
```

**After**

```twig
{% include "@SuluWebsite/Extension/seo.html.twig" %}
```

## 2.0.2

### RouteManagerInterface / RouteRepositoryInterface changed

In the `RouteManagerInterface` a new method was introduced `createOrUpdateByAttributes`.
In the `RouteRepositoryInterface` a new method was introduced `persist`.

### NavigationItem

The `NavigationItem` class used in the `Admin` classes still had some properties that are not used anymore in Sulu 2.0.
In order to avoid confusion these properties and their corresponding getters and setters have been removed. This affects
the following properties:

- event
- eventArguments
- headerTitle
- headerIcon
- hasSettings

## 2.0.1

### mode schemaOption in ResourceLocator

The `resource_locator` field had a `mode` schema option, which could e.g. be used like this:


```xml
<property name="url" type="resource_locator" mandatory="true">
    <params>
        <param name="mode" value="full" />
    </params>

    <tag name="sulu.rlp"/>
</property>
```

This option does not exist anymore. Instead you should set the correct `resource-locator-strategy` in your webspace
configuration.

### Fix AccountInterface nullable setters/getter

Setters and getter of nullable fields on the Account entity were fixed.
For some function on the AccountInterface need to be changed to the following:

```php
// Before
public function setExternalId(string $externalId): AccountInterface;
public function setNumber(string $number): AccountInterface;
public function setRegisterNumber(string $registerNumber): AccountInterface;
public function setPlaceOfJurisdiction(string $placeOfJurisdiction): AccountInterface;
public function addNote(Note $note): AccountInterface;

// After
public function setExternalId(?string $externalId): AccountInterface;
public function setNumber(?string $number): AccountInterface;
public function setRegisterNumber(?string $registerNumber): AccountInterface;
public function setPlaceOfJurisdiction(?string $placeOfJurisdiction): AccountInterface;
public function setNote(?string $note): AccountInterface;
```

### SnippetSelection type param

Previously the `snippet_selection` field type accepted a `snippetType` param, which filtered the assignable snippets.
This param was renamed to `types`, in order to make it consistent with e.g. the `media_selection`.

```xml
<!-- before -->
<property name="snippets" type="snippet_selection">
    <params>
        <param name="snippetType" value="default" />
    </params>
</property>

<!-- after -->
<property name="snippets" type="snippet_selection">
    <params>
        <param name="types" value="default" />
    </params>
</property>
```

### Sitemap Provider changed

As a sitemap is always domain specific and a domain can have multiple webspaces and portal
the `SitemapProviderInterface` changed. To make it possible to provide also none Sulu
routes the `SitemapUrl` constructor introduced a `defaultLocale` as third parameter.

```php
// before
public function build($page, $portal) {
     return [
          new SitemapUrl('/test-1', 'de');
     ];
}

public function createSitemap() {
    return new Sitemap($alias, $this->getMaxPage()/*, $lastMod */);
}

public function getMaxPage() {
     return 1;
}

// after
public function build($page, $scheme, $host) {
     return [
          new SitemapUrl('http://test.lo/test-1', 'de', 'de');
     ];
}

public function createSitemap() {
    return new Sitemap($this->getAlias(), $this->getMaxPage()/*, $lastMod */);
}

public function getAlias(): string {
    return 'myalias';
}

public function getMaxPage($scheme, $host) {
     return 1;
}
```

The `XmlSitemapDumper` and `XmlSitemapRender` no longer need `PortalInformations`.

Also the `sulu_website` configuration `default_host` was removed and will use now the
[router context parameter](https://symfony.com/doc/current/routing.html#generating-urls-in-commands) instead.

```yaml
# before
sulu_website:
    sitemap:
        default_host: 'localhost'

# after
parameters:
    router.request_context.host: 'localhost'
```

### DeleteToolbarAction with conflict

The `DeleteToolbarAction` asks for confirmation if e.g. a page that is being tried to deleted is linked on other pages.
The delete action of the controller now also has to return the ID that was trying to be deleted, in order for the
application to know what was tried to be deleted.

```
# Before
{
    "items": [
        {
            "name": "Test1"
        },
        {
            "name": "Test2"
        }
    ]
}

# After
{
    "id": "page-uuid",
    "items": [
        {
            "name": "Test1"
        },
        {
            "name": "Test2"
        }
    ]
}
```

## 2.0.0

When upgrading also have a look at the changes in the
[sulu skeleton](https://github.com/sulu/skeleton/compare/2.0.0-RC3...2.0.0).

### WebspaceMangerInterface changed

The `WebspaceManagerInterface` changed that in all methods the `$environment` variable is nullable
and will use in the implementation the current `kernel.environment`.

### Ugrading JMS Serializer dependency

See [JMS/Serializer](https://github.com/schmittjoh/serializer/blob/master/UPGRADING.md)
and [JMS/SerializerBundle](https://github.com/schmittjoh/JMSSerializerBundle/blob/master/UPGRADING.md)
Upgrade files.

Serialization to `array` type is not longer possible use the new `sulu_core.array_serializer` service instead.

### Refactor Rest Controllers

The Sulu `RestController` was deprecated and replaced with the `AbstractRestController`.
All Sulu Rest controllers were refactored to extend the new `AbstractRestController`. 
Furthermore, all these controllers and now use constructor injection to gather their dependencies.

### Admin Route/View renamings

The `Sulu\Bundle\AdminBundle\Admin\Routing` namespace was renamed to `Sulu\Bundle\AdminBundle\Admin\View`.

`RouterBuilder`s have been renamed to `ViewBuilder`s and some of the methods have been renamed (they are used in
multiple `ViewBuilder`s and are named the same):

| Old function name                     | New function name                       |
|---------------------------------------|-----------------------------------------|
| addRouterAttributesToListStore        | addRouterAttributesToListRequest        |
| addRouterAttributesToFormStore        | addRouterAttributesToFormRequest        |
| addResourceStorePropertiesToListStore | addResourceStorePropertiesToListRequest |
| addResourceStorePropertiesToFormStore | addResourceStorePropertiesToFormRequest |
| setBackRoute                          | setBackView                             |
| setAddRoute                           | setAddView                              |
| setEditRoute                          | setEditView                             |
| setApiOptions                         | setRequestParameters                    |

The most critical change is the different signature in the `Admin` class.

```php
// Before
public function configureRoutes(RouteCollection $routeCollection): void {}

// After
public function configureViews(ViewCollection $viewCollection): void {}
```

The `NavigationItem` functions have also changed:

| Old function name | New function name |
|-------------------|-------------------|
| setMainRoute      | setView           |
| setChildRoutes    | setChildViews     |
| addChildRoute     | addChildView      |


The `RouterBuilderFactory` is now renamed to `ViewBuilderFactory`, and in all method names the string `Route` is
replaced with `View`.

The Configuration of the `SuluSearchBundle` has also changed:

| Old configuration key                             | New configuration key                           |
|---------------------------------------------------|-------------------------------------------------|
| sulu_search.indexes.<index>.route                 | sulu_search.indexes.<index>.view                |
| sulu_search.indexes.<index>.route.result_to_route | sulu_search.indexes.<index>.view.result_to_view |

### Refactor WebsiteController and DefaultController

The WebsiteController and DefaultController were refactored to not extend the deprecated Symfony Controller class.
The controllers now use the new Symfony [AbstractController](https://github.com/symfony/symfony/blob/4.4/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php) class as base class.

If you extend from one of these Controllers, you need to define your service dependencies in the `getSubscribedServices` method:

```php
public static function getSubscribedServices()
{
    $subscribedServices = parent::getSubscribedServices();
    $subscribedServices['app.custom_service'] = CustomService::class;

    return $subscribedServices;
}
```

### Security Profile and Conexts routes changed

The endpoints for the profile and security contexts apis changed:

 - `/admin/security/contexts` -> `/admin/api/security-contexts`
 - `/admin/security/profile` -> `/admin/api/profile`

### Symfony 3.4 support dropped

To fix current deprecations in symfony packages we needed to drop symfony 3.4 support and go on the newest minor version of symfony (4.3).

## 2.0.0-RC3

When upgrading also have a look at the changes in the
[sulu skeleton](https://github.com/sulu/skeleton/compare/f08ac00c57756f38cbf5166c2a3d09782f34fcb3...2.0.0-RC3).

### Country Table ('co_countries') was replace with Symfony Intl Regionbundle

The country table was removed in favor of the Symfony Intl Regionbundle.
Existing addresses need to migrate to the `countryCode` field which use the ISO-3166-1 code instead of an id:

```sql
ALTER TABLE co_addresses ADD countryCode VARCHAR(5) DEFAULT NULL;
UPDATE co_addresses INNER JOIN co_countries ON co_addresses.idCountries = co_countries.id SET co_addresses.countryCode = co_countries.code, co_addresses.idCountries = NULL WHERE co_addresses.idCountries IS NOT NULL;
ALTER TABLE co_addresses DROP FOREIGN KEY FK_26E9A614A18CC0FB;
DROP INDEX IDX_26E9A614A18CC0FB ON co_addresses;
ALTER TABLE co_addresses DROP idCountries;
DROP TABLE co_countries;
```

The `sulu_contact.countries` route and `sulu_contact.country_repository` service was removed,
the contacts and accounts api accept a 2 letter ISO-3166 `countryCode` instead of an ID now.

### NL and FR system languages removed

At current state sulu 2.0 is only translated to EN and DE.
Existing users need to migrate there system language to EN or DE:

```sql
UPDATE `se_users` SET `locale` = 'en' WHERE `locale` NOT IN ('en', 'de');
```

### Webspace resources permission key changed

For webspace specific resources the permission key has been changed:

```sql
UPDATE se_permissions SET context = REPLACE(context, 'sulu.webspace_settings.', 'sulu.webspace.') WHERE context LIKE 'sulu.webspace_settings.%';
```

### RequestLocaleTranslator removed

The `sulu_website.event_listener.translator` will now set the correct locale for the `translator` service.

Because of that the following classes and services were removed:

 - `Sulu\Bundle\WebsiteBundle\EventListener\TranslatorEventListener`
 - `Sulu\Bundle\WebsiteBundle\Translator\RequestLocaleTranslator`
 - `sulu_website.translator.request_analyzer`

### Test Bundle service aliases removed

The following service alias were removed:

 - `sulu_test.doctrine.orm.default_entity_manager`
 - `sulu_test.doctrine_phpcr.default_session`
 - `sulu_test.doctrine_phpcr`
 - `sulu_test.doctrine_phpcr.live_session`
 - `sulu_test.doctrine_phpcr.session`
 - `sulu_test.massive_search.adapter`
 - `sulu_test.massive_search.adapter.test`

Use the services without the `sulu_test.` prefix instead.

### Test Bundle Voter and User Provider changed

To allow tests for security the TestBundle Voter and TestUserProvider will allow only permission and return a
auto generated user when the username `test` is used.

### LocationBundle

The LocationBundle was cleaned up and therefor the configuration of the bundle was changed:

__Before:__

```yaml
sulu_location:
    types:
        location:
            template:             'SuluLocationBundle:Template:content-types/location.html.twig'
    enabled_providers:

        # Defaults:
        - leaflet
        - google
    default_provider:             ~ # One of "leaflet"; "google"
    geolocator:                   ~ # One of "nominatim"; "google"
    providers:
        leaflet:
            title:                'Leaflet (OSM)'
        google:
            title:                'Google Maps'
            api_key:              null
    geolocators:
        nominatim:
            endpoint:             'http://open.mapquestapi.com/nominatim/v1/search.php'
        google:
            api_key:              ''
```

__After:__

```yaml
sulu_location:
    geolocator:                   ~ # One of "nominatim"; "google"
    geolocators:
        nominatim:
            api_key:              ''
            endpoint:             'http://open.mapquestapi.com/nominatim/v1/search.php'
        google:
            api_key:              ''
```

Unfortunately all of the supported geolocators require an authentication key now, therefore it is required to configure 
the `api_key` parameter for the `google` provider or the `nominatim` provider to use the geolocation functionality
in the admin.

Furthermore, the location field-type was refactored to always use OpenStreetMap for displaying selected locations.
Because of this the provider related configuration was removed.

Finally, the `GeolocatorController` was refactored. The queryAction is now registered with the name 
`sulu_location.geolocator_query` instead of `sulu_location_geolocator_query` and the `query` parameter was renamed
to `search`.

### Hateoas Library and Bundle Removed

The Hateoas Bundle is not longer a requirement of sulu and can be removed from `bundles.php` in your project.

```diff
-    Bazinga\Bundle\HateoasBundle\BazingaHateoasBundle::class => ['all' => true],
```

Two new classes where added to replace the Hateoas:

```php
// before
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;

// after
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
```

### CollaborationBundle

The `CollaborationBundle` has been integrated in the `AdminBundle`, because it was a non-optional dependency of it.
Because of that the `CollaborationBundle` has to be removed from the `config/bundles.php` file. And there is a new
`routing_api.yml` file in the `SuluAdminBundle`, which has to be imported in `config/routes/sulu_admin.yml`.

```yaml
sulu_admin_api:
    resource: "@SuluAdminBundle/Resources/config/routing_api.yml"
    type: rest
    prefix: /admin/api
```

### ToolbarActions

The `addToolbarActions` method of different `RouteBuilder` do not accept simple arrays anymore, but arrays of the new
`ToolbarAction` class. The `ToolbarAction` class takes the type of the `ToolbarAction` and an array of additional
options. There are special `ToolbarAction` classes for the `sulu_admin.dropdown` (`DropdownToolbarAction`) and
`sulu_admin.toggler` (`TogglerToolbarAction`) type, because they contain translatable values.

```php
<?php
// before
$formToolbarActionsWithType = [
    'sulu_admin.save_with_publishing' => [
        'publish_display_condition' => '(!_permissions || _permissions.live)',
        'save_display_condition' => '(!_permissions || _permissions.edit)',
    ],
    'sulu_page.templates',
    'sulu_admin.delete' => [
        'display_condition' => '(!_permissions || _permissions.delete) && url != "/"',
    ],
    'sulu_admin.dropdown' => [
        'label' => $this->translator->trans('sulu_admin.edit', [], 'admin'),
        'icon' => 'su-pen',
        'actions' => [
            'sulu_admin.copy_locale' => [
                'display_condition' => '(!_permissions || _permissions.edit)',
            ],
            'sulu_admin.delete_draft' => [
                'display_condition' => $publishDisplayCondition,
            ],
            'sulu_admin.set_unpublished' => [
                'display_condition' => $publishDisplayCondition,
            ],
        ],
    ],
];

// after
$formToolbarActionsWithType = [
    new ToolbarAction(
        'sulu_admin.save_with_publishing',
        [
            'publish_display_condition' => '(!_permissions || _permissions.live)',
            'save_display_condition' => '(!_permissions || _permissions.edit)',
        ]
    ),
    new ToolbarAction('sulu_page.templates'),
    new ToolbarAction(
        'sulu_admin.delete',
        [
            'display_condition' => '(!_permissions || _permissions.delete) && url != "/"',
        ]
    ),
    new DropdownToolbarAction(
        'sulu_admin.edit',
        'su-pen',
        [
            new ToolbarAction(
                'sulu_admin.copy_locale',
                [
                    'display_condition' => '(!_permissions || _permissions.edit)',
                ]
            ),
            new ToolbarAction(
                'sulu_admin.delete_draft',
                [
                    'display_condition' => $publishDisplayCondition,
                ]
            ),
            new ToolbarAction(
                'sulu_admin.set_unpublished',
                [
                    'display_condition' => $publishDisplayCondition,
                ]
            ),
        ]
    ),
];
```

## 2.0.0-RC2

When upgrading also have a look at the changes in the
[sulu skeleton](https://github.com/sulu/sulu-minimal/compare/2.0.0-RC1...2.0.0-RC2).

### SuluTestCase and KernelTestCase changed

The `SuluTestCase` and `KernelTestCase` extend now from the default Symfony `KernelTestCase` to support the newest features
of it. The new `KernelTestCase` can in some cases behave different e.g.: `createClient` in the Symfony `KernelTestCase`
does reboot the `Kernel` which was not the behaviour before.

Also the following function and services were removed:

 - `SuluTestCase::createHomeDocument` removed
 - `SuluTestCase::$importer` removed
 - `KernelTestCase::getKernel` removed use `KernelTestCase::$kernel` or `KernelTestCase::bootKernel` instead

The TestKernel option `sulu_context` was renamed to `sulu.context` to match its service tag.

### Renaming of JS files

All JavaScript files containing not a constructor but export an instance of a class instead have been renamed to start
with a lowercase letter now.

### Configuring the navigation and routes using the Admin classes

Previously it was hard to change already existing `Routes` and `NavigationItems` for the administration interface. In
order to make that easier, we have changed the way this is happening. Instead of returning these items from the
`getNavigation` and `getRoutes` function we have introduced the `configureNavigationItems` and the `configureRoutes`
functions.

These functions get a `NavigationItemCollection` resp. a `RouteCollection` passed as their first argument. Instead of
returning these items they get added to these collections. The collections also allow to get one of its item by passing
the item's name to the `get` method of the collection. These items can then be manipulated, which e.g. enables the
developer to add another item in a form's toolbar from another bundle or the application.

The `RouteCollection` now also holds objects implementing one of the `RouteBuilder` interfaces. This means that instead
of working with the low-level functions from the `Route` class the methods of the `RouteBuilder` can be used.

Also, the `NavigationItem` has no `$parent` parameter in the constructor anymore:

```php
// Before
$parentNavigationItem = new NavigationItem('parent');
$childNavigationItem = new NavigationItem('child', $parentNavigationItem);

// After
$parentNavigationItem = new NavigationItem('parent');
$childNavigationItem = new NavigationItem('child');
$parentNavigationItem->addChild($childNavigationItem);
```

The namespaces of some classes have been changed, as described in the following table:

| Old FQCN | New FQCN |
|----------|----------|
| Sulu\Bundle\AdminBundle\Navigation\NavigationItem | Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem |
| Sulu\Bundle\AdminBundle\Admin\NavigationProviderInterface | Sulu\Bundle\AdminBudnle\Admin\Navigation\NavigationProviderInterface |
| Sulu\Bundle\AdminBundle\Admin\NavigationRegistry | Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry |
| Sulu\Bundle\AdminBundle\Admin\RouteProviderInterface | Sulu\Bundle\AdminBundle\Admin\Routing\RouteProviderInterface |
| Sulu\Bundle\AdminBundle\Admin\RouteRegistry | Sulu\Bundle\AdminBundle\Admin\Routing\RouteRegistry |

The following snippet shows an example of an old vs. a new `Admin` class. The example shows how to add a navigation item
to the existing Settings navigation item.

```php
// Before
<?php
class TagAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.settings.tags';

    const LIST_ROUTE = 'sulu_tag.list';

    public function getNavigation(): Navigation
    {
        $rootNavigationItem = $this->getNavigationItemRoot();

        $settings = Admin::getNavigationItemSettings();

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $roles = new NavigationItem('sulu_tag.tags', $settings);
            $roles->setPosition(30);
            $roles->setMainRoute(static::LIST_ROUTE);
        }

        if ($settings->hasChildren()) {
            $rootNavigationItem->addChild($settings);
        }

        return new Navigation($rootNavigationItem);
    }

    public function getRoutes(): array
    {
        $routes = [];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $routes[] = $this->routeBuilderFactory->createListRouteBuilder(static::LIST_ROUTE, '/tags')
                ->setResourceKey('tags')
                ->setListKey('tags')
                ->setTitle('sulu_tag.tags')
                ->addListAdapters(['table'])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->addToolbarActions($listToolbarActions)
                ->getRoute();
        }

        return $routes;
    }
}

// After
class TagAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.settings.tags';

    const LIST_ROUTE = 'sulu_tag.list';

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $tags = new NavigationItem('sulu_tag.tags');
            $tags->setPosition(30);
            $tags->setMainRoute(static::LIST_ROUTE);

            $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($tags);
        }
    }

    public function configureRoutes(RouteCollection $routeCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $routeCollection->add(
                $this->routeBuilderFactory->createListRouteBuilder(static::LIST_ROUTE, '/tags')
                    ->setResourceKey('tags')
                    ->setListKey('tags')
                    ->setTitle('sulu_tag.tags')
                    ->addListAdapters(['table'])
                    ->setAddRoute(static::ADD_FORM_ROUTE)
                    ->setEditRoute(static::EDIT_FORM_ROUTE)
            );
        }
    }
}
```

Since the `RouteCollection` only takes `RouteBuilder` instances, we have introduced a new `RouteBuilder` for standard
routes:

```php
<?php
// Before
class SearchAdmin extends Admin
{
    const SEARCH_ROUTE = 'sulu_search.search';

    public function getRoutes(): array
    {
        return [
            (new Route(static::SEARCH_ROUTE, '/', 'sulu_search.search'))
                ->setOption('test1', 'value1'),
        ];
    }
}

// After
class SearchAdmin extends Admin
{
    const SEARCH_ROUTE = 'sulu_search.search';

    public function configureRoutes(RouteCollection $routeCollection): void
    {
        $routeCollection->add(
            $this->routeBuilderFactory->createRouteBuilder(static::SEARCH_ROUTE, '/', 'sulu_search.search')
                ->setOption('test1', 'value1')
        );
    }
}
```

### Use yaml files for configuring routes

All remaining XML route definition files were migrated to use the YAML format. Therefore, the following resource paths 
must be adjusted:

| Previous Path                                                     | New Path                                                          |
|-------------------------------------------------------------------|-------------------------------------------------------------------|
| @SuluAudienceTargetingBundle/Resources/config/routing_api.xml     | @SuluAudienceTargetingBundle/Resources/config/routing_api.yml     |
| @SuluAudienceTargetingBundle/Resources/config/routing_website.xml | @SuluAudienceTargetingBundle/Resources/config/routing_website.yml |
| @SuluCoreBundle/Resources/config/routing_api.xml                  | @SuluCoreBundle/Resources/config/routing_api.yml                  |
| @SuluPreviewBundle/Resources/config/routing.xml                   | @SuluPreviewBundle/Resources/config/routing.yml                   |
| @SuluRouteBundle/Resources/config/routing_api.xml                 | @SuluRouteBundle/Resources/config/routing_api.yml                 |
| @SuluSearchBundle/Resources/config/routing_website.xml            | @SuluSearchBundle/Resources/config/routing_website.yml            |
| @SuluSecurityBundle/Resources/config/routing.xml                  | @SuluSecurityBundle/Resources/config/routing.yml                  |
| @SuluSecurityBundle/Resources/config/routing_api.xml              | @SuluSecurityBundle/Resources/config/routing_api.yml              |

### Add bundle prefix to rest route names

We decided to add a bundle prefix to all of our rest routes to keep things consistent and prevent eventual collisions 
in the future. The following route names were changed:

| Previous Name                      | New Name                                             |
|------------------------------------|------------------------------------------------------|
| get_tag                            | sulu_tag.get_tag                                     |
| get_tags                           | sulu_tag.get_tags                                    |
| post_tag                           | sulu_tag.post_tag                                    |
| put_tag                            | sulu_tag.put_tag                                     |
| delete_tag                         | sulu_tag.delete_tag                                  |
| post_tag_merge                     | sulu_tag.post_tag_merge                              |
| patch_tags                         | sulu_tag.patch_tags                                  |
| get_customers                      | sulu_contact.get_customers                           |
| get_contacts                       | sulu_contact.get_contacts                            |
| delete_contact                     | sulu_contact.delete_contact                          |
| get_contact                        | sulu_contact.get_contact                             |
| post_contact                       | sulu_contact.post_contact                            |
| put_contact                        | sulu_contact.put_contact                             |
| patch_contact                      | sulu_contact.patch_contact                           |
| get_country                        | sulu_contact.get_country                             |
| get_countries                      | sulu_contact.get_countries                           |
| multipledeleteinfo_account         | sulu_contact.multipledeleteinfo_account              |
| get_account_contacts               | sulu_contact.get_account_contacts                    |
| get_account_addresses              | sulu_contact.get_account_addresses                   |
| put_account_contacts               | sulu_contact.put_account_contacts                    |
| delete_account_contacts            | sulu_contact.delete_account_contacts                 |
| get_accounts                       | sulu_contact.get_accounts                            |
| post_account                       | sulu_contact.post_account                            |
| put_account                        | sulu_contact.put_account                             |
| patch_account                      | sulu_contact.patch_account                           |
| delete_account                     | sulu_contact.delete_account                          |
| get_account_deleteinfo             | sulu_contact.get_account_deleteinfo                  |
| get_account                        | sulu_contact.get_account                             |
| get_contact-title                  | sulu_contact.get_contact-title                       |
| get_contact-titles                 | sulu_contact.get_contact-titles                      |
| post_contact-title                 | sulu_contact.post_contact-title                      |
| put_contact-title                  | sulu_contact.put_contact-title                       |
| delete_contact-titles              | sulu_contact.delete_contact-titles                   |
| delete_contact-title               | sulu_contact.delete_contact-title                    |
| patch_contact-titles               | sulu_contact.patch_contact-titles                    |
| get_contact-position               | sulu_contact.get_contact-position                    |
| get_contact-positions              | sulu_contact.get_contact-positions                   |
| post_contact-position              | sulu_contact.post_contact-position                   |
| put_contact-position               | sulu_contact.put_contact-position                    |
| delete_contact-positions           | sulu_contact.delete_contact-positions                |
| delete_contact-position            | sulu_contact.delete_contact-position                 |
| patch_contact-positions            | sulu_contact.patch_contact-positions                 |
| cget_account_medias                | sulu_contact.cget_account_medias                     |
| delete_account_medias              | sulu_contact.delete_account_medias                   |
| post_account_medias                | sulu_contact.post_account_medias                     |
| get_account_medias                 | sulu_contact.get_account_medias                      |
| cget_contact_medias                | sulu_contact.cget_contact_medias                     |
| delete_contact_medias              | sulu_contact.delete_contact_medias                   |
| post_contact_medias                | sulu_contact.post_contact_medias                     |
| get_contact_medias                 | sulu_contact.get_contact_medias                      |
| cget_contexts                      | sulu_security.cget_contexts                          |
| get_contexts                       | sulu_security.get_contexts                           |
| get_profile                        | sulu_security.get_profile                            |
| put_profile                        | sulu_security.put_profile                            |
| patch_profile_settings             | sulu_security.patch_profile_settings                 |
| delete_profile_settings            | sulu_security.delete_profile_settings                |
| get_roles                          | sulu_security.get_roles                              |
| get_role                           | sulu_security.get_role                               |
| post_role                          | sulu_security.post_role                              |
| put_role                           | sulu_security.put_role                               |
| delete_role                        | sulu_security.delete_role                            |
| get_role_setting                   | sulu_security.get_role_setting                       |
| put_role_setting                   | sulu_security.put_role_setting                       |
| get_groups                         | sulu_security.get_groups                             |
| get_group                          | sulu_security.get_group                              |
| post_group                         | sulu_security.post_group                             |
| put_group                          | sulu_security.put_group                              |
| delete_group                       | sulu_security.delete_group                           |
| get_user                           | sulu_security.get_user                               |
| post_user                          | sulu_security.post_user                              |
| post_user_trigger                  | sulu_security.post_user_trigger                      |
| put_user                           | sulu_security.put_user                               |
| patch_user                         | sulu_security.patch_user                             |
| delete_user                        | sulu_security.delete_user                            |
| get_users                          | sulu_security.get_users                              |
| get_permissions                    | sulu_security.get_permissions                        |
| put_permissions                    | sulu_security.put_permissions                        |
| post_page_resourcelocator_generate | sulu_page.post_page_resourcelocator_generate         |
| get_page_resourcelocators          | sulu_page.get_page_resourcelocators                  |
| delete_page_resourcelocators       | sulu_page.delete_page_resourcelocators               |
| post_resourcelocator               | sulu_page.post_resourcelocator                       |
| entry_node                         | sulu_page.entry_node                                 |
| index_node                         | sulu_page.index_node                                 |
| get_node                           | sulu_page.get_node                                   |
| get_nodes                          | sulu_page.get_nodes                                  |
| put_node                           | sulu_page.put_node                                   |
| post_node                          | sulu_page.post_node                                  |
| delete_node                        | sulu_page.delete_node                                |
| post_node_trigger                  | sulu_page.post_node_trigger                          |
| entry_page                         | sulu_page.entry_page                                 |
| index_page                         | sulu_page.index_page                                 |
| get_pages                          | sulu_page.get_pages                                  |
| post_page_trigger                  | sulu_page.post_page_trigger                          |
| get_page                           | sulu_page.get_page                                   |
| put_page                           | sulu_page.put_page                                   |
| post_page                          | sulu_page.post_page                                  |
| delete_page                        | sulu_page.delete_page                                |
| get_webspace_localizations         | sulu_page.get_webspace_localizations                 |
| get_items                          | sulu_page.get_items                                  |
| get_webspaces                      | sulu_page.get_webspaces                              |
| get_webspace                       | sulu_page.get_webspace                               |
| get_teasers                        | sulu_page.get_teasers                                |
| get_collection                     | sulu_media.get_collection                            |
| get_collections                    | sulu_media.get_collections                           |
| post_collection                    | sulu_media.post_collection                           |
| put_collection                     | sulu_media.put_collection                            |
| delete_collection                  | sulu_media.delete_collection                         |
| post_collection_trigger            | sulu_media.post_collection_trigger                   |
| cget_media                         | sulu_media.cget_media                                |
| get_media                          | sulu_media.get_media                                 |
| post_media                         | sulu_media.post_media                                |
| put_media                          | sulu_media.put_media                                 |
| delete_media                       | sulu_media.delete_media                              |
| delete_media_version               | sulu_media.delete_media_version                      |
| post_media_trigger                 | sulu_media.post_media_trigger                        |
| post_media_preview                 | sulu_media.post_media_preview                        |
| delete_media_preview               | sulu_media.delete_media_preview                      |
| get_formats                        | sulu_media.get_formats                               |
| get_media_formats                  | sulu_media.get_media_formats                         |
| put_media_format                   | sulu_media.put_media_format                          |
| patch_media_formats                | sulu_media.patch_media_formats                       |
| get_category                       | sulu_category.get_category                           |
| get_categories                     | sulu_category.get_categories                         |
| post_category_trigger              | sulu_category.post_category_trigger                  |
| post_category                      | sulu_category.post_category                          |
| put_category                       | sulu_category.put_category                           |
| patch_category                     | sulu_category.patch_category                         |
| delete_category                    | sulu_category.delete_category                        |
| get_category_keywords              | sulu_category.get_category_keywords                  |
| post_category_keyword              | sulu_category.post_category_keyword                  |
| get_category_keyword               | sulu_category.get_category_keyword                   |
| put_category_keyword               | sulu_category.put_category_keyword                   |
| delete_category_keyword            | sulu_category.delete_category_keyword                |
| delete_category_keywords           | sulu_category.delete_category_keywords               |
| get_snippets                       | sulu_snippet.get_snippets                            |
| get_snippet                        | sulu_snippet.get_snippet                             |
| post_snippet                       | sulu_snippet.post_snippet                            |
| put_snippet                        | sulu_snippet.put_snippet                             |
| delete_snippet                     | sulu_snippet.delete_snippet                          |
| post_snippet_trigger               | sulu_snippet.post_snippet_trigger                    |
| get_snippet-areas                  | sulu_snippet.get_snippet-areas                       |
| put_snippet-area                   | sulu_snippet.put_snippet-area                        |
| delete_snippet-area                | sulu_snippet.delete_snippet-area                     |
| get_languages                      | sulu_snippet.get_languages                           |
| cget_webspace_analytics            | sulu_website.cget_webspace_analytics                 |
| cdelete_webspace_analytics         | sulu_website.cdelete_webspace_analytics              |
| get_webspace_analytics             | sulu_website.get_webspace_analytics                  |
| post_webspace_analytics            | sulu_website.post_webspace_analytics                 |
| put_webspace_analytics             | sulu_website.put_webspace_analytics                  |
| delete_webspace_analytics          | sulu_website.delete_webspace_analytics               |
| get_localizations                  | sulu_core.get_localizations                          |
| cget_webspace_custom-urls          | sulu_custom_url.cget_webspace_custom-urls            |
| cdelete_webspace_custom-urls       | sulu_custom_url.cdelete_webspace_custom-urls         |
| get_webspace_custom-urls           | sulu_custom_url.get_webspace_custom-urls             |
| post_webspace_custom-urls          | sulu_custom_url.post_webspace_custom-urls            |
| put_webspace_custom-urls           | sulu_custom_url.put_webspace_custom-urls             |
| delete_webspace_custom-urls        | sulu_custom_url.delete_webspace_custom-urls          |
| get_webspace_custom-urls_routes    | sulu_custom_url.get_webspace_custom-urls_routes      |
| delete_webspace_custom-urls_routes | sulu_custom_url.delete_webspace_custom-urls_routes   |
| get_routes                         | sulu_routes.get_routes                               |
| delete_routes                      | sulu_routes.delete_routes                            |
| get_target-group_fields            | sulu_audience_targeting.get_target-group_fields      |
| get_target-group_rule_fields       | sulu_audience_targeting.get_target-group_rule_fields |
| get_target-groups                  | sulu_audience_targeting.get_target-groups            |
| get_target-group                   | sulu_audience_targeting.get_target-group             |
| post_target-group                  | sulu_audience_targeting.post_target-group            |
| put_target-group                   | sulu_audience_targeting.put_target-group             |
| delete_target-group                | sulu_audience_targeting.delete_target-group          |
| delete_target-groups               | sulu_audience_targeting.delete_target-groups         |

### Added Type-Hints

We added type-hints to following interfaces:

* `Sulu\Component\Localization\Manager\LocalizationManagerInterface`
* `Sulu\Component\Webspace\Manager\WebspaceManagerInterface`
* `Sulu\Component\Localization\Provider\LocalizationProviderInterface`

### Removed various base classes

The `BaseRole`, `BaseUser`, `BaseUserRole`, `BaseRoute` and `BaseCollection` class were removed. The functionality
of these classes was moved to the `Role`, `User`, `UserRole`, `Route` and `Collection` class.

### Removed deprecated commands

Instead of `sulu:webspaces:init` or `sulu:phpcr:init` use `sulu:document:initialize`.

### Removed SuluResourceBundle 

The `SuluResourceBundle` was removed from the source code as it is not used by Sulu anymore.

### Rename system column of se_roles table to securitySystem for MySQL 8 compatibility

The `system` column of the `se_roles` table was renamed to `securitySystem` to make Sulu compatible with MySQL 8.0.
This is necessary because `SYSTEM` is a SQL keyword since MySQL 8.0.3.

```sql
ALTER TABLE se_roles CHANGE system securitySystem VARCHAR(60) NOT NULL;
```

### Rename ToolbarActionRegistry and AbstractToolbarAction javscript classes

**This change only affects you if you have used a 2.0.0 release before**

The `ToolbarActionRegistry` class and the `AbstractToolbarAction` class of the javascript Form view were renamed to `FormToolbarActionRegistry` and `AbstractFormToolbarAction`.
Furthermore the `ToolbarActionRegistry` class and the `AbstractToolbarAction` class of the javascript List view were renamed to `ListToolbarActionRegistry` and `AbstractListToolbarAction`.

This change does not affect you if you have imported these classes via the `index.js` file of the `AdminBundle` as these classes were already exported with view specific names there.

## 2.0.0-RC1

When upgrading also have a look at the changes in the
[sulu skeleton](https://github.com/sulu/sulu-minimal/compare/2.0.0-alpha6...2.0.0-RC1).

### Search indexes

The configuration for the search indexes are not generated on the fly anymore. Instead every index that should be listed
in the search should be registered using the `sulu_search.indexes` configuration.

### Search description

If the `description` tag of the XML configuration files in the `Resources/config/massive-search` bundle directory contain HTML it
will strip the HTML tags in the search result. While it is still possible to define HTML in the `description` tag it
will not be shown anymore.

### Controller for search indexes

The controller returning the search indexes will now embed the indexes in an `_embedded` and `search_indexes` keys:

```javascript
// before
[
    // indexes
]

// after

{
    _embedded: {
        search_indexes: [
            // indexes
        ]
    }
}
```

### RouteBundle API

The API for routes under `/admin/api/routes` has changed a bit, in order to avoid having PHP class names in the
frontent. Instead it uses the already known `resourceKey` now.

Therefore the API has changed, instead of `entityClass` it now takes the `resourceKey`, and the `entityId` attribute has
changed to just `id`.

```
# before
/admin/api/routes?history=true&entityClass=Sulu\Bundle\ArticleBundle\Document\ArticleDocument&entityId=c88e4b89-7e2b-4161-b5d0-c33993535140&locale=en

# after
/admin/api/routes?history=true&resourceKey=articles&id=101b58ef-d422-4122-98f9-a4009bd74bd1&locale=en
```

This `resourceKey` now has to be configured in the `sulu_route` configuration as well:

```yaml
# before
sulu_route:
    mappings:
        Sulu\Bundle\ArticleBundle\Document\ArticleDocument:
            generator: "schema"
            options:
                route_schema: "/articles/{object.getTitle()}"

# after
sulu_route:
    mappings:
        Sulu\Bundle\ArticleBundle\Document\ArticleDocument:
            resource_key: "articles"
            generator: "schema"
            options:
                route_schema: "/articles/{object.getTitle()}"
```

### Page ResourceLocator API

The API for the history of resource locators (`/admin/api/pages/<id>/resourcelocators`) for pages has changed in order
to be more consistent with the API from the `RouteBundle`. The property `resourcelocator` has now be renamed to `path`.

### TargetGroup API

The TargetGroup API available under `/admin/api/target-groups` changed the key in the `_embedded` object from
`target-groups` to `target_groups`.

Also the webspaces for the target groups are now set using the `webspaceKeys` field instead of the `webspaces` field.
The `webspaceKeys` field accepts only the webspace key as a string instead of an object.

### NavigationItem Action

The `action` property of the `NavigationItem` class int he `Sulu\Bundle\Admin\Navigation` namespace is not needed
anymore. Therefore it was removed.

### Display options in MediaSelection

The `media_selection` content type showed all available display options(left top, top, right, ...) except for `middle`
by default. From now on this feature is deactivated, if the `displayOptions` parameter is not defined in the XML
template definition.

In case you have relied on the old behavior, you have to add the parameters yourself:

```xml
<!-- Before -->
<property name="media" type="media_selection" />

<!-- After (if you really need all the available options) -->
<property name="media" type="media_selection">
    <params>
        <param name="displayOptions" type="collection">
            <param name="leftTop" value="true"/>
            <param name="top" value="true"/>
            <param name="rightTop" value="true"/>
            <param name="left" value="true"/>
            <param name="middle" value="false"/>
            <param name="right" value="true"/>
            <param name="leftBottom" value="true"/>
            <param name="bottom" value="true"/>
            <param name="rightBottom" value="true"/>
        </param>
    </params>
</property>
```

### Media in Contact & Account REST API

The APIs under `/admin/api/contacts/<id>` and `/admin/api/accounts/<id>` have a `medias` field containing references
to assigned medias. This field was an array of objects containing only an `id` property. Now the `medias` property is an
array of numbers. The same change was made in the PATCH and PUT requests.

There are also subresources at `/admin/api/contacts/<id>/medias` and `/admin/api/accounts/<id>/medias`, where the key in
the `_embedded` object was changed from `media` to `contact_media` resp. `account_media`

## 2.0.0-alpha6

When upgrading also have a look at the changes in the
[sulu skeleton](https://github.com/sulu/sulu-minimal/compare/2.0.0-alpha5...2.0.0-alpha6).

### Restructuring of Contact & Account REST API

The `urls` property of the REST API has been renamed to `websites`, and the `socialMediaProfiles` property was renamed
to `socialMedia`. The properties under these properties have been changed accordingly.

The `emails`, `phones`, `faxes`, `websites` and `socialMedia` properties have been grouped under a `contactDetails`
property. The types ("work", "private") of all these `contactDetails` properties have changed from an object to an ID.

The above changes have been made in all actions, this includes `GET`, `POST` and `PUT`.

### Rename contact content type to contact_account_selection

**Before**:

```xml
<property name="contacts" type="contact" />
```

**After**:

```xml
<property name="contacts" type="contact_account_selection" />
```

Following container parameters removed:

 - `sulu_contact.content.contact.class`

Following service renamed:

 - `sulu_contact.content.contact` to `sulu_contact.content.contact_account_selection`

### Rename snippet content type to snippet_selection

**Before**:

```xml
<property name="snippets" type="snippet" />
```

**After**:

```xml
<property name="snippets" type="snippet_selection" />
```

### BundleReady and BundleReadyPromise removed

**This change only affects you if you have used a 2.0.0 alpha release before**

The `bundleReady` call is not longer needed and needs to be removed from your bundle `index.js`.

### Router removeUpdateRouteHook

**This change only affects you if you have used a 2.0.0 alpha release before**

The `removeUpdateRouteHook` function from the `Router` was removed. If you want to remove hooks again, you can now use
the disposer being returned from the `addUpdateRouteHook` function.

```javascript
// Before
const hook = () => {};
router.addUpdateRouteHook(hook);
router.removeUpdateRouteHook(hook);

// After
const hook = () => {};
const disposer = router.addUpdateRouteHook(hook);
disposer();
```

### Custom URL services

The `CustomUrlController` delivering the API for custom urls doesn't take a locale as query parameter anymore, because
they are not localized. In order to be consistent the `CustomUrlDocument` does not implement the `LocaleBehavior`
anymore and neither the `CustomUrlManager` and `CustomUrlRepository` accept a locale parameter.

### Rename Internal Link and Single Internal Link Content Type

The `single_internal_link` and `internal_links` content type were renamed.

**Before**:

```xml
<property name="link" type="single_internal_link" />
<property name="links" type="internal_links" />
```

**After**:

```xml
<property name="link" type="single_page_selection" />
<property name="links" type="page_selection" />
```

### Removed container parameters

The following parameters where removed:

 - sulu.content.type.internal_links.class
 - sulu.content.type.single_internal_link.class
 - sulu.content.type.phone.class
 - sulu.content.type.password.class
 - sulu.content.type.url.class
 - sulu.content.type.email.class
 - sulu.content.type.date.class
 - sulu.content.type.time.class
 - sulu.content.type.color.class
 - sulu.content.type.checkbox.class

Instead you need now create a service with the same id to overwrite the class.

### Markup LinkConfiguration

The `LinkConfiguration`, which has to be provided by any class implementing the `LinkProviderInterface`, changed,
because there are different arguments to pass now. You can also use the `LinkConfigurationBuilder`, which will guide you
through the process.

### sulu:link Markup Tag

The `sulu:link` tag made problems in some cases, because other tools could not handle the colon in its name. So we have
replace it by a dash. The tag therefore is called `sulu-link` from now on.

These changes must also be reflect in the database, therefore you should executed the PHPCR migrations:

```bash
bin/console phpcr:migrations:migrate
```

### sulu:media Markup Tag

Previously the `sulu:media` tag was used to link different media in the text editor. There is also a `sulu:link` tag,
which has a concept of providers, to load different type of resources. Since this is used for pages and in our
ArticleBundle we have decided to remove the `sulu:media` tag and embed it in the `sulu:link` tag as well. This has an
impact on your data in PHPCR, since the `sulu:media` tags have to be replaced. This was implemented in a PHPCR migration
so just make sure you execute the migration command:

```bash
bin/console phpcr:migrations:migrate
```

### Piwik analytics has been renamed to Matomo

The analytics software Piwik has been renamed to [Matomo](https://matomo.org/blog/2018/01/piwik-is-now-matomo/).
Therefore we have also renamed our analytics type to matomo, which means that existing data has to be updated with the
following SQL statement:

```sql
UPDATE we_analytics SET type="matomo" WHERE type="piwik";
```

### Removed sulu twig variables

The following sulu twig variables are removed and its symfony equivilants should be used instead:

| Before                 | After
|------------------------|---------------------|
| request.currentLocale  | app.request.locale  |
| request.post           | app.request.request |
| request.get            | app.request.get     |

### Commands changed

The Sulu commands were refactored to use the `Command` class instead of the `ContainerAwareCommand` class, because some
services are private and cannot be used anymore without using dependency injection. If you have overridden a command
before, you have to specify the constructor arguments correctly now.

### Moved preview functionality of Form view

**This change only affects you if you have used a 2.0.0 alpha release before**

The functionality to display the preview of the page which is edited in a sidebar was moved from the default `Form` 
view(registered with the name `sulu_admin.form`) to a new `PreviewForm` view (registered with key 
`sulu_admin.preview_form`). 

Furthermore, the route-option `preview` which is used to define a condition whether to preview should be displayed or
not was renamed to `previewCondition`.

### excluded query parameter of Media API

The `excluded` query parameter which can be used to exclude specific ids from the media list returned by the Media API
was renamed to `excludedIds` to increase the consistency within our APIs. 

### Router Attributes to List or Form Store switched

**This change only affects you if you have used a 2.0.0 alpha release before**

If you have use the `routerPropertiesToListStore` or `routerPropertiesToFormStore` options the properties where switched:

**Before**

```php
    ->addRouterAttributesToListStore([
        'listStoreProperty' => 'routeAttribute',
    ]);
```

or

```php
    ->addRouterAttributesToFormStore([
        'formStoreProperty' => 'routeAttribute',
    ]);
```

**After**

```php
    ->addRouterAttributesToListStore([
        'routeAttribute' => 'listStoreProperty',
    ]);
```

or

```php
    ->addRouterAttributesToFormStore([
        'routeAttribute' => 'formStoreProperty',
    ]);
```

### Endpoint configuration

**This change only affects you if you have used a 2.0.0 alpha release before**

The Symfony configuration to set the endpoints for a specific resource have changed. Instead of defining one endpoint
two different routes for a list and a detail view are defined. This allows to remove a dirty hack with an empty
`cgetAction` when only a detail view exists.

```yaml
# Before
sulu_admin:
    resources:
        pages:
            endpoint: get_pages

# After
sulu_admin:
    resources:
        pages:
            routes:
                list: get_pages
                detail: get_page
```

### ResourceRequester

**This change only affects you if you have used a 2.0.0 alpha release before**

The `id` parameter in all methods of the `ResourceRequester` has been removed. Instead of building the URLs following a
certain schema the real routes from Symfony are used and if the `id` is required it has to be passed as parameter. This
allows to support sub resources at a later point.

```javascript
// Before
ResourceRequster.get('snippets', 5, {locale: 'de'});

// After
ResourceRequster.get('snippets', {id: 5, locale: 'de'});
```

Therefore also the `postWithId` method was removed, since the `post` method can be used instead now.

```javascript
// Before
ResourceRequster.postWithId('snippets', 5, {locale: 'de'});

// After
ResourceRequster.post('snippets', {id: 5, locale: 'de'});
```

In case you have used the `ResourceEndpointRegistry` somewhere, it has been renamed to `ResourceRouteRegistry` to better
match the other names.

## 2.0.0-alpha5

### Datagrid renaming

**This change only affects you if you have used a 2.0.0 alpha release before**

The word `datagrid` has been renamed to `list`, which also includes all occurences of this word including file names,
class names, function resp. method names and service parameters as well as the Symfony configuration.

The most important changes are described in the next few paragraphs:

The configuration for the directories of the list XMLs have changed:

```yaml
# Before
sulu_admin:
    datagrids:
        - your/folder

# After
sulu_admin:
    lists:
        - your/folder
```

Also the `datagrid` resp. `datagrid_overlay` types for the `selection` resp. `single_selection` `field_type_options`
changed to `list` resp. `list_overlay`:

```yaml
# Before
sulu_admin:
    field_type_options:
        selection:
            default_type: 'datagrid'
            types:
                datagrid:
                    datagrid_key: 'test'
                datagrid_overlay:
                    datagrid_key: 'test'
        single_selection:
            default_type: 'datagrid'
            types:
                datagrid:
                    datagrid_key: 'test'
                datagrid_overlay:
                    datagrid_key: 'test'

# After
sulu_admin:
    field_type_options:
        selection:
            default_type: 'list'
            types:
                list:
                    list_key: 'test'
                list_overlay:
                    list_key: 'test'
        single_selection:
            default_type: 'list'
            types:
                list:
                    list_key: 'test'
                list_overlay:
                    list_key: 'test'
```

The Javascript container component and view has been renamed from `Datagrid` to `List`. This is also true for the
registries that are used to register some more types:

| Old name                         | New name                     |
|----------------------------------|------------------------------|
| datagridAdapterRegistry          | listAdapterRegistry          |
| datagridFieldTransformerRegistry | listFieldTransformerRegistry |

The root tag for the list XMLs also changed from `datagrid` to `list`:

```xml
<!-- before -->
<?xml version="1.0" ?>
<datagrid xmlns="http://schema.sulu.io/list-builder/datagrid">
    <!-- config -->
</datagrid>

<!-- after -->
<?xml version="1.0" ?>
<list xmlns="http://schema.sulu.io/list-builder/list">
    <!-- config -->
</list>
```

In addition to that the method to get the route builder for lists and two of its methods changed:

```php
// Before
class CategoryAdmin extends Admin
{
    public function getRoutes(): array
    {
        return [
            $this->routeBuilderFactory->createDatagridRouteBuilder(static::DATAGRID_ROUTE, '/categories/:locale')
                ->setResourceKey('categories')
                ->setDatagridKey('categories')
                ->setTitle('sulu_category.categories')
                ->addDatagridAdapters(['tree_table'])
                ->addLocales($locales)
                ->setDefaultLocale($locales[0])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->enableSearching()
                ->addToolbarActions($listToolbarActions)
                ->getRoute(),
        ];
    }
}

// After
class CategoryAdmin extends Admin
{
    public function getRoutes(): array
    {
        return [
            $this->routeBuilderFactory->createListRouteBuilder(static::LIST_ROUTE, '/categories/:locale')
                ->setResourceKey('categories')
                ->setListKey('categories')
                ->setTitle('sulu_category.categories')
                ->addListAdapters(['tree_table'])
                ->addLocales($locales)
                ->setDefaultLocale($locales[0])
                ->setAddRoute(static::ADD_FORM_ROUTE)
                ->setEditRoute(static::EDIT_FORM_ROUTE)
                ->enableSearching()
                ->addToolbarActions($listToolbarActions)
                ->getRoute(),
        ];
    }
}
```

Also, because the name `list` is a reserved keyword and can't be used for classes nor namespaces in PHP, we have added
a `Metadata` suffix to the classes in the `Metadata` namespace:

| Old class                                          | New class                                                      |
|----------------------------------------------------|----------------------------------------------------------------|
|`Sulu\Bundle\AdminBundle\Metadata\Schema\Schema`    |`Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata`|
|`Sulu\Bundle\AdminBundle\Metadata\Form\Form`        |`Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata`    |
|`Sulu\Bundle\AdminBundle\Metadata\Datagrid\Datagrid`|`Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata`    |

All the other files in these namespaces have been moved as well.

### Datagrid Toolbar Actions added

**This change only affects you if you have used a 2.0.0 alpha release before**

The datagrid will not longer add automatically toolbar actions so if you e.g.
need a delete and add button on your datagrid you should add them to your
toolbarActions the following way:

```php
$this->routeBuilderFactory->createDatagridRouteBuilder(...)
    ->addToolbarActions([
        'sulu_admin.add',
        'sulu_admin.delete',
    ]);
```

The functions `enableMoving` and `disableMoving` where also replaced by a
ToolbarAction called `sulu_admin.move`.

### Add sulu preinstall script to your package.json

Sulu will check if the dependencies are correctly install in a preinstall script
add this to your project `package.json`:

```diff
     "scripts": {
+        "preinstall": "node vendor/sulu/sulu/preinstall.js",
```

### Type information of contacts

The contacts have different sub entities, which are assigned in combination with a type (e.g. phone numbers, fax number,
...). The name of these types have been slightly renamed, and since this data is stored in the database, it has to be
renamed there as well. The following sql snippet can be used for that:

```sql
UPDATE co_phone_types SET name="sulu_contact.work" WHERE name="phone.work";
UPDATE co_phone_types SET name="sulu_contact.private" WHERE name="phone.home";
UPDATE co_phone_types SET name="sulu_contact.mobile" WHERE name="phone.mobile";
UPDATE co_email_types SET name="sulu_contact.work" WHERE name="email.work";
UPDATE co_email_types SET name="sulu_contact.private" WHERE name="email.home";
UPDATE co_address_types SET name="sulu_contact.work" WHERE name="address.work";
UPDATE co_address_types SET name="sulu_contact.private" WHERE name="address.home";
UPDATE co_url_types SET name="sulu_contact.work" WHERE name="url.work";
UPDATE co_url_types SET name="sulu_contact.private" WHERE name="url.private";
UPDATE co_fax_types SET name="sulu_contact.work" WHERE name="fax.work";
UPDATE co_fax_types SET name="sulu_contact.private" WHERE name="fax.home";
```

### Webspace template file extension removed

Sulu supports now also different format for static webspace templates like search and error.
The following need to be changed in your webspace configuration:

**Before**

```xml
<templates>
	<template type="search">templates/search.html.twig</template>
	<template type="error">templates/error.html.twig</template>
</templates>
```

**After**

```xml
<templates>
	<template type="search">templates/search</template>
	<template type="error">templates/error</template>
</templates>
```

If you have custom webspaces template you also need to change how you get to the template:

The format is optional and will fallback to html if not given.

**Before**

```xml
$webspace->getTemplate('search');
```

**After**

```xml
$webspace->getTemplate('search', $request->getRequestFormat());
```

### Contact and Account API

The APIs on `/admin/api/contacts` and `/admin/api/accounts` now use an array of IDS for their `categories` instead of
returning resp. passing an entire object. In addition to that it also uses IDS instead of objects for the `country` and
`addressType` property.

### SuluKernel::construct changed

The `suluContext` is optional now to support extending from Symfony `KernelTestCase` of Symfony:

**Before**:

```php
public function __construct($environment, $debug, $suluContext) {}
```

**After**:

```php
public function __construct($environment, $debug, $suluContext = self::CONTEXT_ADMIN) {}
```

### Styling of View components

**This change only affects you if you have used a 2.0.0 alpha release before**

The React components registered as views in the `ViewRegistry` had to add the padding using the
`$viewPadding` scss variable on their own. That is not neccessary anymore, since it is added in a
central place.

### DatagridStore

A new constructor argument has been added to the `DatagridStore` JavaScript class, to allowhaving different
representations on the same resource. The new parameter comes on second place and is called `datagridKey`.

### Renamed ContentBundle to PageBundle

Following things have changed:

* The bundle name changed from `SuluContentBundle` to `SuluPageBundle`
* All services or parameters prefixed with `sulu_content` will now use the prefix `sulu_page`
* The config tree for `sulu_content` was renamed to `sulu_page`
* All the Classes from the namespace `Sulu\Bundle\ContentBundle` moved to `Sulu\Bundle\PageBundle`

### Logger is optional

As the logger is now optional in the following services the constructor changed:

 - `sulu.content.type.internal_links`
 - `sulu_content.compat.structure.legacy_property_factory`
 - `sulu_content.node_repository`
 - `sulu_media.search.subscriber.media`
 - `sulu_media.format_manager`
 - `sulu_media.storage.local`
 - `sulu_snippet.import.snippet`
 - `sulu_website.twig.content`

### TagBundle services changed

Following Services changed:

 - `sulu_tag.tag_repository` removed use `sulu.repository.tag`

Following Function removed:

 - `TagManagerInterface::getFieldDescriptors` and `TagManagerInterface::getFieldDescriptor` removed use `FieldDescriptorFactory::getFieldDescriptorForClass` instead

The `TagManager` doesn't longer depend on FieldDescriptorFactory, TagEntityName and UserRepository so its constructor changed.

Following Functions changed as the `$userId` is not longer needed:

 - `TagManagerInterface::findOrCreateByName($name, $userId);` -> `TagManagerInterface::findOrCreateByName($name);`
 - `TagManagerInterface::save($name, $userId, $id);` -> `TagManagerInterface::save($name, $id);`

### Change tag_list to tag_selection

The tag_list content and field type was changed to `tag_selection`

**Before**

```xml
<property name="yourname" type="tag_list">
    <!-- ... --->
</property>
```

**After**

```xml
<property name="yourname" type="tag_selection">
    <!-- ... --->
</property>
```

### Change category_list to category_selection

The `category_list` content and field type was changed to `category_selection`

**Before**

```xml
<property name="yourname" type="category_list">
    <!-- ... --->
</property>
```

**After**

```xml
<property name="yourname" type="category_selection">
    <!-- ... --->
</property>
```

### Metadata refactoring

**This change only affects you if you have used a 2.0.0 alpha release before**

The `ResourceMetadata` got rid of the form and datagrid metadata. So the metadata is not available in the response of
the `/admin/resources/{resource}` action anymore, but under `/admin/metadata/form/{formKey}` and
`/admin/metadata/datagrid/{datagridKey}`. The reason for that is that we want to have multiple different forms and
datagrids for each resource.

The form XML file has changed, because it needs a separate key now, which identifies the form unrelated to the resource
it is using.

```xml
<form xmlns="http://schemas.sulu.io/template/template"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/form-1.0.xsd"
>
    <key>category_details</key>
    <properties>
        <!-- the same properties as before -->
    </properties>
</form>
```

The files located in the `Resources/config/list-builder` folder have moved to `Resources/config/datagrids`. In addition
to that a `key` tag was added the same way as in the forms. The root tag was changed from `class` to `datagrid`, and the
`orm:` namespace was moved to the same as all the other tags.

```xml
<!-- before -->
<class xmlns="http://schemas.sulu.io/class/general"
    xmlns:list="http://schemas.sulu.io/class/list"
    xmlns:orm="http://schemas.sulu.io/class/doctrine"
>
    <orm:joins name="translation">
        <orm:join>
            <orm:entity-name>%sulu.model.category_translation.class%</orm:entity-name>
            <orm:field-name>%sulu.model.category.class%.translations</orm:field-name>
            <orm:condition>%sulu.model.category_translation.class%.locale = ':locale'</orm:condition>
        </orm:join>
    </orm:joins>

    <properties>
        <case-property name="name" list:translation="sulu_category.name" visibility="always" searchability="yes">
            <orm:field>
                <orm:field-name>translation</orm:field-name>
                <orm:entity-name>%sulu.model.category_translation.class%</orm:entity-name>
                <orm:joins ref="translation"/>
            </orm:field>
            <orm:field>
                <orm:field-name>translation</orm:field-name>
                <orm:entity-name>%sulu.model.category_translation.class%Default</orm:entity-name>
                <orm:joins ref="defaultTranslation"/>
            </orm:field>
        </case-property>
    </properties>
</class>

<!-- after -->
<datagrid xmlns="http://schemas.sulu.io/list-builder/datagrid">
    <key>categories</key>
    <joins name="translation">
        <join>
            <entity-name>%sulu.model.category_translation.class%</entity-name>
            <field-name>%sulu.model.category.class%.translations</field-name>
            <condition>%sulu.model.category_translation.class%.locale = ':locale'</condition>
        </join>
    </joins>
    <properties>
        <case-property name="name" translation="sulu_category.name" visibility="always" searchability="yes">
            <field>
                <field-name>translation</field-name>
                <entity-name>%sulu.model.category_translation.class%</entity-name>
                <joins ref="translation"/>
            </field>
            <field>
                <field-name>translation</field-name>
                <entity-name>%sulu.model.category_translation.class%Default</entity-name>
                <joins ref="defaultTranslation"/>
            </field>
        </case-property>
    </properties>
</datagrid>
```

Also the `FieldDescriptorFactory` holding all the above information now uses the `key` tag from the above example to
load these values. Therefore the name has also be renamed from `FieldDescriptorFactory::getFieldDescriptorForClass` to
`FieldDescriptorFactory::getFieldDescriptors`.

The `FieldDescriptorInterface` has remove the functions for `width`, `minWidth`, `class` and `editable`. The constructor
of the `FieldDescriptor` has also lost these arguments.

The frontend routes for forms defined in the `Admin` classes now need this `formKey` in addition to the `resourceKey`.
This allows to have the same endpoint for multiple forms, and solves a bunch of issues we were having.

```php
return [
    (new Route('sulu_category.edit_form.details', '/details', 'sulu_admin.form'))
        ->setOption('tabTitle', 'sulu_category.details')
        ->setOption('resourceKey', 'categories')
        ->setOption('formKey', 'category_details')
        ->setOption('backRoute', 'sulu_category.datagrid')
];
```

Of course the `resourceKey` can still often be passed from a parent route and therefore omitted in the form route
definition, which is often the case when making use of the `sulu_admin.resource_tabs` view.

The frontend routes for a datagrid defined in the `Admin` classes now need the `datagridKey` in addition to the 
`resourceKey`. This allows to have the same endpoint for multiple datagrids.

```php
return [
    (new Route('sulu_category.datagrid, '/categories', 'sulu_admin.datagrid'))
        ->setOption('title', 'sulu_category.categories')
        ->setOption('adapters', ['table'])
        ->setOption('resourceKey', 'categories')
        ->setOption('datagridKey', 'categories');
];
```

Adding additional fields works a little bit different now. Previously you had to define a separate form XML file, and
add it in the configuration:

```yml
sulu_admin:
    resources:
        categories:
            form:
                - "@MyCategoryBundle/Resources/config/forms/Category.xml"
```

Now the forms are merged based on the key from the form XML file. The sulu-minimal edition already comes with a
`config/forms` folder, in which form XML files can be put. These will extend existing forms if the same form key already
exists. In case the files should be stored in a different folder it can still be configured in the configuration:

```yml
sulu_admin:
    forms:
        directories:
            - "%kernel.project_dir%/config/my-forms"
```

Also the representations in the cache have changed, so the cache should be cleared:

```bash
bin/adminconsole cache:clear
bin/websiteconsole cache:clear
```

### Expressions in Form XML

Parameters in form XMLs using expression are written a bit different now. The `type` attribute can now take the value
`expression` and the expression itself goes into `value`.

```xml
<!-- before -->
<param name="something" expression="service('some_service').getValue()"/>
<!-- after -->
<param name="something" type="expression" value="service('some_service').getValue()"/>
```

This will make it easier to add more similar features in the future.

### RouteBuilder

Instead of creating all the Routes in the `Admin::getRoutes` method completely on your own there is now the possibility
to use the `RouteBuilderFactory`. It offers methods to create Form, Datagrid and ResourceTabs routes in a typed way,
which means auto completion in the IDE should work as well.

Apart from that the `Route::addOption` and `Route::addAttributeDefault` were renamed to `Route::setOption` and
`Route::setAttributeDefault`, since they overrides existing values.

### MediaBundle storage configuration

Configuration tree for local storage has changed:

__Before:__
```
sulu_media:
    storage:
        local:
            path: '%kernel.project_dir%/var/uploads/media'
            segments: 10
```

__After:__
```
sulu_media:
    storages:
        local:
            path: '%kernel.project_dir%/var/uploads/media'
            segments: 10
```

### Media Bundle several Interfaces changed

To allow adding new features some interfaces where changed and needs to be updated if you did build something on top 
of them:

 - StorageInterface
 - LocalStorage
 - FormatManagerInterface
 - FormatManager
 - FormatCacheInterface
 - LocalFormatCache
 - ImageConverterInterface
 - ImagineImageConverter
 - MediaExtractorInterface
 - MediaImageExtractor
 - FileVersion::getStorageOptions

### Test Setup

The `KERNEL_DIR` env variable in phpunit.xml was replaced with `KERNEL_CLASS`.

### Output folder of admin build changed

The output file of the admin build has changed from `/admin/build` to `/build/admin`
to solve issues with the php internal webserver.

```bash
mkdir public/build
git mv public/admin/build public/build/admin
```

As in every update you should also update the build by running:

```bash
npm install
npm run build
```

### Toolbar Button label configuration

**This change only affects you if you have used a 2.0.0 alpha release before**

The name of the property to configure the text shown on a button in the toolbar has been change from `value` to `label`
in order to be more consistent.

### Multiple Select renamed

The multiple select content type was renamed to `select` to be equal to the other content types.

**Before**

```xml
<property name="yourname" type="multiple_select">
    <!-- ... -->
</property>
```

**After**

```xml
<property name="yourname" type="select">
    <!-- ... -->
</property>
```

Also the service was renamed from `sulu.content.type.multiple_select` to `sulu.content.type.select`.

### Collection Controller

The `include-root` option of this Controller was renamed to `includeRoot` to be more consistent. The `RootCollection`
was removed from the `getAction`, because it does not make sense there.

### CategoryController

The `parentId` parameter for moving a category has been renamed to `destination` in order to be consistent. In
addition to that you have to use `root` as destination instead of `null`.

### Form Labels

We do not generate the label for form fields from its name anymore. So you if you don't pass an explicit title to the
property in the XML like below, there will be no label rendered at all.

```xml
<property name="title" type="text_line" />
```

In order to get the same title as before this change, you have to set it explicitly now:


```xml
<property name="title" type="text_line">
    <meta>
        <title lang="en">Title</title>
        <title lang="de">Title</title>
    </meta>
</property>
```

### Log Folder changes

To match the symfony 4 folder structure the logs are now written into **`var/log`** instead of var/logs.

### Form visibilityCondition

**This change only affects you if you have used a 2.0.0 alpha release before**

The `visibilityCondition` on the Form XML has been renamed to `visibleCondition`.

### PageController

The `concreteLanguages` property has been renamed to `contentLocales`.
The `enabledShadowLanguages` property has been renamed to `shadowLocales`, and swapped key and value in the map, so
that the key is the locale of the page, and the value is the locale it uses to load the data.

The `availableLocales` property was introduced, and shows for which locale some kind of content exists, no matter if
it is actual content or uses shadow content. This flag only excludes ghost locales.

## 2.0.0-alpha4

### MediaController

The returned JSON of the `MediaController` does not return the entire `categories` anymore, but only its IDs instead.

### DateTime Serialization

DateTimes in a REST response do not contain the timezone anymore.

### Websocket

The websocket-bundle and component was removed without replacement.

### Preview

To register a `sulu_preview.object_provider` you have to change your tag definition:

Before:

```xml
<tag name="sulu_preview.object_provider" class="Sulu\Bundle\ArticleBundle\Document\ArticleDocument"/>
```

After:

```xml
<tag name="sulu_preview.object_provider" provider-key="articles"/>
```

Additionally the rdfa properties are obsolete and they can be removed from your twig templates.

### Content Type Manager

The `ContentTypeManagerInterface::getAll` function will not longer return instances of the content types.
Instead it will return a list of the content types aliases for performance reasons.
Use the `ContentTypeManagerInterface::get($alias)` to get the service instance of the content type.

### Default folder changed

The following default configurations were changed to use the symfony 4 folder structure.

| Configuration                                | Old Default                                  | New Default
|----------------------------------------------|----------------------------------------------|-----------------------------------------------
| sulu_core.webspaces.config_dir               | %kernel.root_dir%/Resources/webspaces        | %kernel.project_dir%/config/webspaces
| sulu_media.storage.local.path                | %kernel.root_dir%/../uploads/media           | %kernel.project_dir%/var/uploads/media
| sulu_media.format_cache..path                | %kernel.root_dir%/../web/uploads/media       | %kernel.project_dir%/public/uploads/media
| sulu_media.image_format_files[0]             | %kernel.root_dir%/config/image-formats.xml   | %kernel.project_dir%/config/image-formats.xml
| massive_search.adapters.zend_lucene.basepath | %kernel.root_dir%/data                       | %kernel.project_dir%/var/indexes

### Selection Component

**This change only affects you if you have used a 2.0.0 alpha release before**

The `Selection` container component was renamed to `MultiSelection`.

### SmartContent

The `DataProvider`s for the `SmartContent` have changed their aliases. The alias now matches the resourceKey of the
entity. These aliases are also what has to be passed to the `smart_content` type as `provider` param. Have a look at
the following table to find the changed aliases:

Old alias | New alias
----------|----------
content   | pages
snippet   | snippets
account   | accounts
contact   | contacts

The `Builder` class which is used in the `DataProvider`s for generating the SmartContent configuration has also
changed a bit. The `enableDataSource` method now takes different parameters: The first one defines which resource
should be loaded, and the second one which `DatagridAdapter` should be used for displaying the resources.

### Datagrid

**This change only affects you if you have used a 2.0.0 alpha release before**

Some props of the `Datagrid` and its adapters have been renamed. See the following two tables to identify which ones:

`Datagrid`:

Old prop   | New prop
-----------|----------
onAddClick | onItemAdd

`DatagridOverlay`:

Old prop           | New prop
-------------------|--------------------
onAddClick         | onItemAdd
onDeleteClick      | onRequestItemDelete
onItemActivation   | onItemActivate
onItemDeactivation | onItemDeactivate

## 2.0.0-alpha3

### AutoComplete

**This change only affects you if you have used a 2.0.0 alpha release before**

There is no `AutoComplete` component anymore, instead this component was split into a `SingleAutoComplete` and
`MultiAutoComplete` component. This way we can distinguish selecting multiple and only single values.

### Configuration changes for Selection and SingleSelection field types

**This change only affects you if you have used a 2.0.0 alpha release before**

The `sulu_admin.field_type_options` have changed. The allow to define different type of components to be used for a
selection. But each of these types had to redefine the `resourceKey`. So the structure was slightly changed to allow
defining the `resourceKey` only once. Also, since the naming convention in the Symfony configuration is to use snake
case, we have adapted the options accordingly.

The following example shows the changes:

```yaml
# old version
sulu_admin:
    field_type_options:
        selection:
            page_selection:
                adapter: 'column_list'
                displayProperties: 'title'
                icon: 'su-document'
                label: 'sulu_content.selection_label'
                resourceKey: 'pages'
                overlayTitle: 'sulu_content.selection_overlay_title'
        single_selection:
            single_account_selection:
                auto_complete:
                    displayProperty: 'name',
                    searchProperties: ['number', 'name']
                    resourceKey: 'accounts'

# new version
sulu_admin:
    field_type_options:
        selection:
            page_selection:
                default_type: 'overlay'
                resource_key: 'pages'
                types:
                    overlay:
                        adapter: 'column_list'
                        displayProperties: 'title'
                        icon: 'su-document'
                        label: 'sulu_content.selection_label'
                        resourceKey: 'pages'
                        overlayTitle: 'sulu_content.selection_overlay_title'
        single_selection:
            single_account_selection:
                default_type: 'auto_complete'
                resource_key: 'accounts'
                types:
                    auto_complete:
                        display_property: 'name'
                        search_properties: ['number', 'name']
```

### Category API

The `/admin/api/categories` endpoint delivered a flat list of categories with a `parentId` attribute if the
`expandedIds` query parameter was defined. This behavior changed, each category now has a `_embedded` field, which has
a `categories` key, under which all sub categories are located. This way not every client using this API has to build
a tree using the `parentId` on their own.

## 2.0.0-alpha2

### parent query parameter

The `parent` query parameter, which is used in quite some controllers like for pages and categories, was renamed to
`parentId`, because it only references the id, and not the entire reference. This is also the new convention within
our new Admin, so if your API should work with it, you have to name the query paramter `parentId`.

### Highlight section

In previous versions of Sulu a section named `highlight` had a special design in the administratin interface. Its
background was darker than the rest of the form. This was removed with the new design, and therefore a `highlight`
section does not do anything special anymore, and therefore can be safely removed from your template XML files.

## 2.0.0-alpha1

### Admin Navigation

The admin navigation should not be built into the constructor anymore. Instead of `setNavigation` 
create `getNavigation` function in the `Admin` class which should return a `Navigation` object.
This makes it easier to override only this part of the Admin.

### sulu.rlp tag deprecated

The `sulu.rlp` tag, which can be added in the template XMLs, is not used anymore by the new UI. Instead the result of
the URL generation will be simply put into the `resource_locator` field type.

### Test Setup changed

If you use the SuluTestBundle to test your custom sulu bundles you maybe need to change in your test config.yml
the path to the gedmo extension:

```yml
doctrine:
    orm:
        mappings:
            gedmo_tree:
                type: xml
                prefix: Gedmo\Tree\Entity
                dir: "%kernel.root_dir%/../../vendor/gedmo/doctrine-extensions/lib/Gedmo/Tree/Entity"
                alias: GedmoTree
                is_bundle: false
```

### Field Descriptor interface changed

The field descriptor parameter `$default` and `$disabled` where combined into a new parameter `$visibility`:

Use the following table for upgrading:

| Disabled | Default  | Visiblity
|----------|----------|--------------
| false    | true     | FieldDescriptorInterface::VISIBILITY_ALWAYS (always)
| false    | false    | FieldDescriptorInterface::VISIBILITY_YES (yes)
| true     | false    | FieldDescriptorInterface::VISIBILITY_NO (no)
| true     | true     | FieldDescriptorInterface::VISIBILITY_NEVER (never)

We have also introduced a new parameter `$searchability` on the fourth position.

The following table shows the values:

| Value  | Description                 | Searchability
|--------|-----------------------------|--------------
| NEVER  | not searchable at all       | FieldDescriptorInterface::SEARCHABILITY_NEVER (never)
| NO     | it's not used per default   | FieldDescriptorInterface::SEARCHABILITY_NO (no)
| YES    | it's used per default       | FieldDescriptorInterface::SEARCHABILITY_YES (yes)

This new property brings use the possibility to use our REST Api's without the parameter `searchFields`.
Default behavior then is to use all fields with `searchability` set to `YES`.

**Before**

```php
new FieldDescriptor(
    'name',
    'translation',
    false, // Disabled
    true, // Default
    // ...
);
```

**After**

```php
new FieldDescriptor(
   'name',
   'translation',
   FieldDescriptorInterface::VISIBILITY_YES, // Visibility
   FieldDescriptorInterface::SEARCHABILITY_NEVER, // Searchability
   // ...
);
```

The same is also for the `DoctrineFieldDescriptor`, `DoctrineJoinDescriptor`, ...:

**Before**

```php
new DoctrineFieldDescriptor(
    'fieldName',
    'name',
        single_selection:
            single_account_selection:
                default_type: 'auto_complete'
                resource_key: 'accounts'
                types:
                    auto_complete:
                        display_property: 'name'
                        search_properties: ['number', 'name']
```

### Category API

The `/admin/api/categories` endpoint delivered a flat list of categories with a `parentId` attribute if the
`expandedIds` query parameter was defined. This behavior changed, each category now has a `_embedded` field, which has
a `categories` key, under which all sub categories are located. This way not every client using this API has to build
a tree using the `parentId` on their own.

### Dependencies

Removed required dependency `pulse00/ffmpeg-bundle`. If you want to use preview images for videos, run following 
command:

```bash
composer require pulse00/ffmpeg-bundle:^0.6
```

Otherwise remove `new Dubture\FFmpegBundle\DubtureFFmpegBundle(),` from the list in app/AbstractKernel.php

### Deprecations

Removed following Bundles:

* Sulu\Bundle\TranslateBundle\SuluTranslateBundle (needs to be removed from the list in app/AbstractKernel.php)

Removed following Classes:

* Sulu\Bundle\CoreBundle\Build\CacheBuilder

Removed following services:

* `sulu_contact.account_repository` (use `sulu.repository.account`)
* `sulu_contact.contact_repository` (use `sulu.repository.contact`)

Removed following parameters:

* `sulu_contact.contact.entity` (use `sulu.model.contact.class`)
* `sulu_contact.account.entity` (use `sulu.model.account.class`)

Renamed following Methods:

* Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface::count => Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface::countCollections

Rename following classes/interfaces:

* Sulu\Component\HttpCache\HttpCache => Sulu\Bundle\HttpCacheBundle\Cache\AbstractHttpCache
* Sulu\Component\Contact\Model\ContactInterface => Sulu\Bundle\ContactBundle\Entity\ContactInterface
* Sulu\Component\Contact\Model\ContactRepositoryInterface => Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface

### Contact temporarily position removed

The `setCurrentPosition` function was removed from the contact entity as this position was only used temporarily and was not persisted to the database. Use the `setPosition` to set the contact main account position function instead.

### Dependency updates

Follow upgrade path of following libraries:

 - **Symfony 3.4**
   - https://symfony.com/doc/3.4/setup/upgrade_major.html
   - https://symfony.com/doc/current/setup/upgrade_minor.html
 - **FOSRestBundle**:
   - https://github.com/FriendsOfSymfony/FOSRestBundle/blob/master/UPGRADING-2.0.md
   - https://github.com/FriendsOfSymfony/FOSRestBundle/blob/master/UPGRADING-2.1.md
 - **JMSSerializerBundle**:
   - https://github.com/schmittjoh/JMSSerializerBundle/blob/master/UPGRADING.md
 - **PHPUnit 6**:
   - https://thephp.cc/news/2017/02/migrating-to-phpunit-6

### Node api

The api endpoint for `/admin/api/nodes/filter` was removed and replaced by `/admin/api/items`.

### Database changes

To support utf8mb4 we needed to change some database entities which you also need to migrate when not using utf8mb4:
Run the following SQL to migrate to the new schema:

```sql
ALTER DATABASE <database_name> CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
USE <database_name>;
ALTER TABLE me_format_options CHANGE format_key format_key VARCHAR(191) NOT NULL;
ALTER TABLE me_collections CHANGE collection_key collection_key VARCHAR(191) DEFAULT NULL;
ALTER TABLE me_collection_types CHANGE collection_type_key collection_type_key VARCHAR(191) DEFAULT NULL;
ALTER TABLE me_media_types CHANGE name name VARCHAR(191) NOT NULL;
ALTER TABLE me_file_versions CHANGE mimeType mimeType VARCHAR(191) DEFAULT NULL;
ALTER TABLE se_users CHANGE email email VARCHAR(191) DEFAULT NULL;
ALTER TABLE se_role_settings CHANGE settingKey settingKey VARCHAR(191) NOT NULL;
ALTER TABLE se_permissions CHANGE context context VARCHAR(191) NOT NULL;
ALTER TABLE se_access_controls CHANGE entityClass entityClass VARCHAR(191) NOT NULL;
ALTER TABLE ca_categories CHANGE category_key category_key VARCHAR(191) DEFAULT NULL;
ALTER TABLE ca_keywords CHANGE keyword keyword VARCHAR(191) NOT NULL;
ALTER TABLE ta_tags CHANGE name name VARCHAR(191) NOT NULL;
ALTER TABLE we_domains CHANGE url url VARCHAR(191) NOT NULL;
ALTER TABLE we_analytics CHANGE webspace_key webspace_key VARCHAR(191) NOT NULL;
ALTER TABLE ro_routes CHANGE path path VARCHAR(191) NOT NULL, CHANGE entity_class entity_class VARCHAR(191) NOT NULL, CHANGE entity_id entity_id VARCHAR(191) NOT NULL;
ALTER TABLE me_collection_meta CHANGE title title VARCHAR(191) NOT NULL;
ALTER TABLE me_file_version_meta CHANGE title title VARCHAR(191) NOT NULL;
ALTER TABLE me_file_versions CHANGE name name VARCHAR(191) NOT NULL;
ALTER TABLE ca_categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE ca_category_meta CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE ca_category_translations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE ca_category_translations_keywords CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE ca_keywords CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE category_translation_media_interface CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_addresses CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_bank_accounts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_contacts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_emails CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_faxes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_medias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_notes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_phones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_social_media_profiles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_tags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_account_urls CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_accounts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_address_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_addresses CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_bank_account CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_addresses CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_bank_accounts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_emails CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_faxes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_locales CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_medias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_notes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_phones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_social_media_profiles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_tags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_titles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contact_urls CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_contacts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_countries CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_email_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_emails CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_fax_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_faxes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_notes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_phone_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_phones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_positions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_social_media_profile_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_social_media_profiles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_url_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE co_urls CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_collection_meta CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_collection_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_collections CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_file_version_categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_file_version_content_languages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_file_version_meta CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_file_version_publish_languages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_file_version_tags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_file_versions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_files CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_format_options CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_media CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE me_media_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE ro_routes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_access_controls CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_group_roles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_groups CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_permissions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_role_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_roles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_security_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_user_groups CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_user_roles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_user_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE se_users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE ta_tags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE we_analytics CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE we_analytics_domains CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE we_domains CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Create new tables `ca_category_translations_keywords` and `ca_category_translation_medias`

```sql
CREATE TABLE ca_category_translation_keywords (idKeywords INT NOT NULL, idCategoryTranslations INT NOT NULL, INDEX IDX_D15FBE37F9FC9F05 (idKeywords), INDEX IDX_D15FBE3717CA14DA (idCategoryTranslations), PRIMARY KEY(idKeywords, idCategoryTranslations)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB;
ALTER TABLE ca_category_translation_keywords ADD CONSTRAINT FK_D15FBE37F9FC9F05 FOREIGN KEY (idKeywords) REFERENCES ca_keywords (id);
ALTER TABLE ca_category_translation_keywords ADD CONSTRAINT FK_D15FBE3717CA14DA FOREIGN KEY (idCategoryTranslations) REFERENCES ca_category_translations (id);

CREATE TABLE ca_category_translation_medias (idCategoryTranslations INT NOT NULL, idMedia INT NOT NULL, INDEX IDX_39FC41BA17CA14DA (idCategoryTranslations), INDEX IDX_39FC41BA7DE8E211 (idMedia), PRIMARY KEY(idCategoryTranslations, idMedia)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB;
ALTER TABLE ca_category_translation_medias ADD CONSTRAINT FK_39FC41BA17CA14DA FOREIGN KEY (idCategoryTranslations) REFERENCES ca_category_translations (id) ON DELETE CASCADE;
ALTER TABLE ca_category_translation_medias ADD CONSTRAINT FK_39FC41BA7DE8E211 FOREIGN KEY (idMedia) REFERENCES me_media (id) ON DELETE CASCADE;
```

The tables `co_contacts` and `co_accounts` now also need a note field:

```sql
ALTER TABLE co_accounts ADD note LONGTEXT DEFAULT NULL;
ALTER TABLE co_contacts ADD note LONGTEXT DEFAULT NULL;
```

In addition that also the PHPCR tables have to be changed to utf8mb4 in case jackalope-doctrine-dbal is used:

```sql
ALTER TABLE `phpcr_binarydata` CHARACTER SET = utf8mb4;
ALTER TABLE `phpcr_internal_index_types` CHARACTER SET = utf8mb4;
ALTER TABLE `phpcr_namespaces` CHARACTER SET = utf8mb4;
ALTER TABLE `phpcr_nodes` CHARACTER SET = utf8mb4;
ALTER TABLE `phpcr_nodes_references` CHARACTER SET = utf8mb4;
ALTER TABLE `phpcr_nodes_weakreferences` CHARACTER SET = utf8mb4;
ALTER TABLE `phpcr_type_childs` CHARACTER SET = utf8mb4;
ALTER TABLE `phpcr_type_nodes` CHARACTER SET = utf8mb4;
ALTER TABLE `phpcr_type_props` CHARACTER SET = utf8mb4;
ALTER TABLE `phpcr_workspaces` CHARACTER SET = utf8mb4;

ALTER TABLE `phpcr_binarydata` CHANGE `property_name` `property_name` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_binarydata` CHANGE `workspace_name` `workspace_name` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';

ALTER TABLE `phpcr_internal_index_types` CHANGE `type` `type` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';

ALTER TABLE `phpcr_namespaces` CHANGE `prefix` `prefix` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_namespaces` CHANGE `uri` `uri` VARCHAR(255)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';

ALTER TABLE `phpcr_nodes` CHANGE `path` `path` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_nodes` CHANGE `parent` `parent` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_nodes` CHANGE `local_name` `local_name` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_nodes` CHANGE `namespace` `namespace` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_nodes` CHANGE `workspace_name` `workspace_name` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_nodes` CHANGE `identifier` `identifier` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_nodes` CHANGE `type` `type` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_nodes` CHANGE `props` `props` LONGTEXT  CHARACTER SET utf8mb4  NOT NULL;
ALTER TABLE `phpcr_nodes` CHANGE `numerical_props` `numerical_props` LONGTEXT  CHARACTER SET utf8mb4  NULL;

ALTER TABLE `phpcr_nodes_references` CHANGE `source_property_name` `source_property_name` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';

ALTER TABLE `phpcr_nodes_weakreferences` CHANGE `source_property_name` `source_property_name` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';

ALTER TABLE `phpcr_type_childs` CHANGE `name` `name` VARCHAR(255)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_type_childs` CHANGE `primary_types` `primary_types` VARCHAR(255)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_type_childs` CHANGE `default_type` `default_type` VARCHAR(255)  CHARACTER SET utf8mb4  NULL  DEFAULT NULL;

ALTER TABLE `phpcr_type_nodes` CHANGE `name` `name` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_type_nodes` CHANGE `supertypes` `supertypes` VARCHAR(255)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_type_nodes` CHANGE `primary_item` `primary_item` VARCHAR(255)  CHARACTER SET utf8mb4  NULL  DEFAULT NULL;

ALTER TABLE `phpcr_type_props` CHANGE `name` `name` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
ALTER TABLE `phpcr_type_props` CHANGE `default_value` `default_value` VARCHAR(255)  CHARACTER SET utf8mb4  NULL  DEFAULT NULL;

ALTER TABLE `phpcr_workspaces` CHANGE `name` `name` VARCHAR(191)  CHARACTER SET utf8mb4  NOT NULL  DEFAULT '';
```

**Migrations**

Migrate values from ca_category_translations_keywords to ca_category_translation_keywords:

```sql
INSERT INTO ca_category_translation_keywords (idKeywords, idCategoryTranslations) SELECT keyword_id, category_meta_id FROM ca_category_translations_keywords;
DROP TABLE ca_category_translations_keywords;
```

Migrate values from category_translation_media_interface to ca_category_translation_medias:

```sql
INSERT INTO ca_category_translation_medias (idCategoryTranslations, idMedia) SELECT category_translation_id, media_interface_id FROM category_translation_media_interface;
DROP TABLE category_translation_media_interface;
```

### Routing changes

Some resources in app/config/admin/routing.yml need to be removed:
- sulu_tag
- sulu_contact
- sulu_content
- sulu_category

### Security changes
The admin security needs to be changed (app/config/admin/security.yml).
The new settings are:

```yaml
security:
    access_decision_manager:
        strategy: unanimous
        allow_if_all_abstain: true

    encoders:
        Sulu\Bundle\SecurityBundle\Entity\User:
            algorithm: sha512
            iterations: 5000
            encode_as_base64: false

    providers:
        sulu:
            id: sulu_security.user_provider

    access_control:
        - { path: ^/admin/reset, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin/security/reset, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin/login$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin/_wdt, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin/translations, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin/$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin, roles: ROLE_USER }

    firewalls:
        admin:
            pattern: ^/
            anonymous: ~
            entry_point: sulu_security.authentication_entry_point
            json_login:
                check_path: sulu_admin.login_check
                success_handler: sulu_security.authentication_handler
                failure_handler: sulu_security.authentication_handler
            logout:
                path: sulu_admin.logout
                success_handler: sulu_security.logout_success_handler

sulu_security:
    checker:
        enabled: true
```

## 1.6.24

### Collection Repository count function changed

For the php 7.3 compatibility we needed to upgrade doctrine/orm for this we needed to rename the following method:

 * Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface::count => Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface::countCollections

## 1.6.17

### Address latitude/longitude

The address of contact/account was extended by latitude and longitude - therefore the database has to be updated.
Run the following command:

```bash
php bin/console doctrine:schema:update --force
```

or the following SQL statements on your database:

```sql
ALTER TABLE co_addresses ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL;
```

### SEO Title

The default length for the title field in the SEO tab has changed from 55 to 70, because Google has expanded
the max length. If you want to have a different length for some reason you can change it in the configuration:

```yaml
sulu_content:
    seo:
        max_title_length: 55
```

## 1.6.16

### Page index extension

The field `authored` are now added to massive_search index. Because of this the index has to be rebuild.

```bash
bin/adminconsole massive:search:reindex --provider sulu_structure
bin/websiteconsole massive:search:reindex --provider sulu_structure
```

## 1.6.15

### Priority of UpdateResponseSubscriber

If you had hooked into response-event of symfony to change the cache behavior of Sulu, be aware of changed priority
and update yours if required.

## 1.6.11

### SEO Description

The default length for the description field in the SEO tab has changed from 155 to 320, because Google has expanded
the max length. If you want to have a different length for some reason you can change it in the configuration:

```yaml
sulu_content:
    seo:
        max_description_length: 155
```

## 1.6.9

### CacheBuilder

The `CacheBuilder`, which was responsible for deleting the cache at the beginning of the `sulu:build` command has been
removed, because after clearing the cache the container is not functional anymore starting with Symfony 3.4.

So from now on the `cache:clear` command has to be executed manually before the `sulu:build` command.

### sulu_content_load

We have changed the behaviour of the `sulu_content_load()` twig extension. Instead of throwing an exception when the given parameter
cannot be resolved to a valid document, it will now just log the exception and return null, so you can gracefully handle this case
in your twig template.

```
{% set content = sulu_content_load(null) %}
{# content is now null #}

{% set content = sulu_content_load('not-existing-guid') %}
{# content is now null #}
```

## 1.6.7

### Custom Analytics

We've added the possibility to determine the position of the content.
The '<script></script>' wrapper was also removed from the custom template.
That means the user has to add this wrapper when it's needed.
 
Changes to existing custom analytics needs to be deployed with following SQL statement on your database:

```sql
UPDATE we_analytics w SET content = CONCAT('{"position":"bodyClose","value":"<script>', SUBSTRING(content,2,LENGTH(content) -2), '</script>"}') WHERE w.type = 'custom';
```

## 1.6.4

### File Version - Tag Cascading

That its possible to delete a Tag, which is referenced in a Media FileVersion, a `on-delete CASCADE` need to be added to the database.
Run the following command:

```bash
php bin/console doctrine:schema:update --force
```

or the following SQL statements on your database:

```sql
ALTER TABLE me_file_version_tags DROP FOREIGN KEY FK_150A30BE1C41CAB8;
ALTER TABLE me_file_version_tags ADD CONSTRAINT FK_150A30BE1C41CAB8 FOREIGN KEY (idTags) REFERENCES ta_tags (id) ON DELETE CASCADE;
```

## 1.6.0

### Default Snippets

Default snippets were replaced with snippet areas. To have the same behaviour as before replace the old twig extension:

__Before:__

```twig
sulu_snippet_load_default('your_snippet_key')[0]
```

__After:__

```twig
sulu_snippet_load_by_area('your_snippet_key')
```

### Sitemap Localization

The `build` method of the `SitemapProviderInterface` had a `$locale` parameter,
which shouldn't be there, because the sitemaps need to be generated or all
locales at once. If you have implemented this interface you have to adapt the
implementation to remove the `$locale` parameter and return the URLs for all
locales instead.

### Snippet list

Some field configuration has changed, so we need to delete the saved one in the database:
```sql
DELETE FROM `se_user_settings` WHERE `settingsKey` = 'snippetsFields';
```

## 1.6.0-RC1

### Social media profile fixtures

Add fixtures for social media profile of contacts. Run following command to
add the fixtures:

```bash
INSERT INTO co_social_media_profile_types (id, name) VALUES ('1', 'Facebook'), ('2', 'Twitter'), ('3', 'Instagram');
```

### ProxyManager

We had to update `ocramius/proxy-manager` in order to be compatible with PHP 7.
In case you have defined your own proxies, you should check the
[ProxyManager UPGRADE.md](https://github.com/Ocramius/ProxyManager/blob/master/UPGRADE.md).

### ContentTypeInterface

Following methods and constants was removed from `ContentTypeInterface`.

* `PRE_SAVE`
* `POST_SAVE`
* `getType()`
* `getReferenceUuids()`

For replacement of `getReferenceUuids` we have introduced the 
[reference-store](http://docs.sulu.io/en/latest/bundles/content/reference-store.html)
and the `PreResolveContentTypeInterface::preResolve` method. 

### Additional routing file from SuluRouteBundle

Add following lines to `app/config/admin/routing.yml`:

```yml
sulu_route_api:
    type: rest
    resource: "@SuluRouteBundle/Resources/config/routing_api.xml"
    prefix: /admin/api
```

### Route-Table changed

The route-table was extended with auditable information. Run following sql-statement to
update the database schema.

```bash
ALTER TABLE ro_routes ADD changed DATETIME DEFAULT '1970-01-01 00:00:00' NOT NULL, ADD created DATETIME DEFAULT '1970-01-01 00:00:00' NOT NULL;
ALTER TABLE ro_routes CHANGE created created DATETIME NOT NULL, CHANGE changed changed DATETIME NOT NULL;
```

### Highlight section styling changed

To make the highlight section reusable the css not longer depend on the `#content-form`
selector you should use now the `.form` class instead. 

### Removed symfony/security-acl dependency

The following deprecated classes was removed:

* `Sulu\Component\Security\Authorization\AccessControl\PermissionMap`
* `Sulu\Component\Security\Authorization\AccessControl\SymfonyAccessControlManager`

Therefor the dependency `symfony/security-acl` was useless and removed.

## 1.5.21

### User API performance improvement

The API at `/admin/api/users/{id}` does not contain the `permissions` field of the roles anymore, because it caused
problems if many webspaces are configured.

## 1.5.15

Added method `hasType` to `Sulu\Component\Content\Compat\Block\BlockPropertyInterface`.

## 1.5.0-RC1

### Media formats uniqueness

The uniqueness of media formats is now checked, and an exception is thrown in
case duplicated format keys exist.

In addition to that the existing formats have been prefixed with `sulu-`,
so that they are less likely to conflict. If you have relied on these formats,
which you shouldn't have, then you have to create them now in your own theme.

The following formats do not exist anymore, and should therefore be deleted
from the `web/uploads/media`-folder, except you decide to create the image
format on your own:

* 400x400
* 400x400-inset
* 260x
* 170x170
* 100x100
* 100x100-inset
* 50x50

### Page author

The page has a new property `author` and `authored` which will be prefilled
with values from `creator`/`created`.

If you have used one of these names before for some properties in your page or
snippet templates, then you have to change the name. Rename the field in the
XML definition and in the twig template, and execute the following command with
the filled in placeholders wrapped in `<>`:

```
app/console doctrine:phpcr:nodes:update --query "SELECT * FROM [nt:base] AS n WHERE [i18n:<localization>-author] IS NOT NULL AND ISDESCENDANTNODE(n, '/cmf')" --apply-closure="\$node->setProperty('i18n:<localization>-<new-field-name>', \$node->getPropertyValue('i18n:<localization>-author')); \$node->getProperty('i18n:<localization>-author')->remove();"
```

This command should be executed for every registered localization and for both
sessions (once without parameter and once with `--session=live`).

Afterwards you can safely migrate the data to use `creator` and `created` as
start values for `author` and `authored`.

```
app/console phpcr:migrations:migrate
```

If the migration failed with `getContact() on a none object` upgrade to at least 1.5.4 and run the migration command again.

### Twig 2

If you upgrade twig to version 2 please read follow
[this instructions](http://twig.sensiolabs.org/doc/1.x/deprecated.html).

The most important change is ``_self`` for calling macros. You have to import it before using.

__Before:__
```twig
{ _self.macro_name() }}
```

__After:__
```twig
{% import _self as self %}
{ self.macro_name() }}
```

If you dont want to use twig2 please add following line to your ``composer.json``:

```json
"require": {
    ...
    "twig/twig": "^1.11"
}
```

### Deprecations

Following classes and methods were removed because of deprecations:

* Sulu\Component\Security\Authorization\AccessControl\SymfonyAccessControlVoter
* Sulu\Component\Rest\RestController::responsePersistSettings
* Sulu\Component\Rest\RestController::responseList
* Sulu\Component\Rest\RestController::createHalResponse
* Sulu\Component\Rest\RestController::getHalLinks
* Sulu\Bundle\WebsiteBundle\DefaultController::redirectAction
* Sulu\Bundle\WebsiteBundle\DefaultController::redirectWebspaceAction

Additionally the GeneratorBundle was removed because it was not maintained since a while.
You have to remove the Bundle from you Kernels.

## 1.4.3

### Multiple properties in template

There was a bug in the template definition for the `minOccurs` field. It was
not working if the `minOccurs` field had a value of `1`. So if you have a field
like the following and you don't want it to be a multiple field you have to
remove the `minOccurs` property:

```xml
    <property name="test1" type="text_line" minOccurs="2"></property>
```

### Format cache

To generate the correct file extension the `FormatManager::purge` interface
has changed.

```diff
-    public function purge($idMedia, $fileName, $options)
+    public function purge($idMedia, $fileName, $mimeType, $options)
```

## 1.4.2

### Security Context

The length of the security context was rather short (only 60 character). The
length has been increased to 255 characters, to also allow longer webspace
names. Execute the `doctrine:schema:update --force` command to update the
database schema.

### Webspace keys

Webspace keys are only allowed to have lower case letters, numbers and `-` or
`_`. Other characters might cause problems and are therefore already restricted
in the XSD file. If you have other characters you have to rename your webspace.

In case you have to you also must rename the `/cmf/<webspace>` e.g. using the
PHPCR shell and reconfigure your role permissions. You should also clear the
search index with the `massive:search:purge` command and reindex with the
`massive:search:reindex` command.

## 1.4.0

### Ports in webspace config

From now on the the port has to be a part of the URL in the webspace
configuration. So if you are running your website on a different port than the
default port of the protocol you are using, you have to change the webspace
config. The port must still be omitted when the `{host}` placeholder is used.

## 1.4.0-RC2

### Admin User Settings

The method `sulu.loadUserSetting()` was removed from the Sulu Aura.js extension located in `Sulu/Bundle/AdminBundle/Resources/public/js/aura_extensions/sulu-extension.js`. 
Instead the method `sulu.getUserSetting()` should be used, which provides same functionality, but is called differently (no need to provide neither URL nor callback in addition to the key).

 
### Media StorageInterface

The `StorageInterface` in the `Sulu\Bundle\MediaBundle\Media\Storage` namespace
got a new `loadAsString` method, which should return the file for the given
parameters as a binary string. If you have already developed your own storage
implementation you have to add this method.

### Page-Templates

The cache-lifetime of page-templates was extended by the `type` attribute.
This attribute is optional and default set to seconds which behaves like
before and set the `max-age` to given integer.

There is now a second type `expression` which allows you to define the
lifetime with a cron-expression which enhances the developer to define
that a page has to be invalidated at a specific time of the day (or
whatever you need).

__BEFORE:__
```xml
<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template-1.0.xsd">

    <key>template</key>

    <view>page.html.twig</view>
    <controller>SuluContentBundle:Default:index</controller>
    <cacheLifetime>2400</cacheLifetime>
    
    ...
    
</template>
```

__NOW:__
```xml
<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template-1.0.xsd">

    <key>template</key>

    <view>page.html.twig</view>
    <controller>SuluContentBundle:Default:index</controller>

    <!-- releases cache each day at midnight -->
    <cacheLifetime type="expression">@daily</cacheLifetime>
    
    ...
    
</template>
```

Therefor we changed the type of the return value for `Sulu\Component\Content\Compat\StructureInterface::getCacheLifeTime`
to array. This array contains the `type` and the `value` of the configured
cache-lifetime.

For resolving this array to a concrete second value we introduced the service
`sulu_http_cache.cache_lifetime.resolver` there you can call the `resolve`
function which returns the concrete second value.

## 1.4.0-RC1

### Refactored category management in backend

The backend of the category bundle was refactored and the category related entities were implemented extensible.
This lead to the following changes:

**API:**
`/categories`: renamed parameter `parent` which accepted an id to `rootKey` which accepts a key
`/categories/{key}/children` was replaced with `/categories/{id}/children`

**Classes:**
`Category\CategoryRepositoryInterface` moved to `Entity\CategoryRepositoryInterface` 
`Category\KeywordRepositoryInterface` moved to `Entity\KeywordRepositoryInterface`
`Category\Exception\KeywordIsMultipleReferencedException` moved to `Exception\KeywordIsMultipleReferencedException` 
`Category\Exception\KeywordNotUniqueException` moved to `Exception\KeywordNotUniqueException` 
`Category\Exception\KeyNotUniqueException` was replaced with `Exception\CategoryKeyNotUniqueException` 

**Methods:**
Removed: `Api\Category::setName($name)`
Replacement: `Api\Category::setTranslation(CategoryTranslationInterface $translation)`
Reason: The api-entity cannot create a translation-entity as the translation-entity is implemented extensible.

Removed: `Api\Category::setMeta($metaArrays)`
Replacement: `Api\Category::setMeta($metaEntities)`
Reason: The api-entity cannot create a meta-entity as the meta-entity is implemented extensible.

Deprecated: `CategoryRepositoryInterface::findCategoryByIds(array $ids)`
Replacement: `CategoryRepositoryInterface::findCategoriesByIds(array $ids)`

Deprecated: `CategoryRepositoryInterface::findCategories($parent = null, $depth = null, $sortBy = null, $sortOrder = null)`
Replacement: `CategoryRepositoryInterface::findChildrenCategoriesByParentId($parentId = null)`

Deprecated: `CategoryRepositoryInterface::findChildren($key, $sortBy = null, $sortOrder = null)`
Replacement: `CategoryRepositoryInterface::findChildrenCategoriesByParentKey($parentKey = null)`

Deprecated: `CategoryManagerInterface::find($parent = null, $depth = null, $sortBy = null, $sortOrder = null)`
Replacement: `CategoryManagerInterface::findChildrenByParentId($parentId = null)`

Deprecated: `CategoryManagerInterface::findChildren($key, $sortBy = null, $sortOrder = null)`
Replacement: `CategoryManagerInterface::findChildrenByParentKey($parentKey = null)`

**Container Parameters/Definitions:**
Deprecated: `sulu_category.entity.category` 
Replacement: `sulu.model.category.class` 

Deprecated: `sulu_category.entity.keyword`
Replacement: `sulu.model.keyword.class`

Deprecated: `sulu_category.category_repository`
Replacement: `sulu.repository.category`

Deprecated: `sulu_category.keyword_repository`
Replacement: `sulu.repository.keyword`

**Extensibility**
Every association of the `Category` entity must be of the type `CategoryInterface` to ensure extensibility
Every association of the `CategoryTranslation` entity must be of the type `CategoryTranslationInterface` to ensure extensibility
Every association of the `CategoryMeta` entity must be of the type `CategoryMetaInterface` to ensure extensibility
Every association of the `Keyword` entity must be of the type `KeywordInterface` to ensure extensibility

### New definition mechanism for image-formats

A new structure for the image-formats configuration files was introduced.
For an explanation on how to define image-formats in the new way please
refer to the documentation (http://docs.sulu.io/en/latest/book/image-formats.html).

Out of the box, image-formats defined in the old way do still work,
but the `XMLLoader` and commands are marked as deprecated.
However when using more profound functionality regarding the image-formats,
there are some BC breaks:

#### `sulu_media.image_format_file` changed to `sulu_media.image_format_files`
The configuration `sulu_media.image_format_file` of the MediaBundle
was changed to `sulu_media.image_format_files` and the type was changed
from a scalar to an array.

#### "Command" renamed to "Transformation"
Internally the concept of a command on an image was renamed to 
"transformation". This renaming was consequently executed throughout the
MediaBundle. This BC break is only important when custom commands have
been created. To update the custom commands (now transformations) they now have
to implement the `TransformationInterface` instead of the `CommandInterface`.
Moreover the service tag under which a transformation gets registered changed
from `sulu_media.image.command` to `sulu_media.image.transformation`. The namespaces
containing "Command" were changed to contain "Transformation" instead.
Note that there was a slight change in the `TransformationInterface` itself.
The `execute` method has to return an image and the passed parameter is not
a reference anymore.

#### Array structure of `sulu_media.image.formats`
The structure of the arrays in which the formats are stored under
the symfony parameter `sulu_media.image.formats` changed. `name` was
renamed to `key`, `commands` was renamed to `transformation` and consequently
`command` to `transformation.`. In addition the first `scale` or `resize` command
is now not contained in the `commands` array anymore, but represented by the `scale`
sub-array of the format.

### ListRestHelper

The `ListRestHelper` has changed its constructor, it takes the `RequestStack`
instead of a `Request` now.

### RouteGenerator

The configuration for the route-generator has changed:

**Before:**
```
sulu_route:
    mappings:
        AppBundle\Entity\Example:
            route_schema: /example/{object.getTitle()}
```

**After:**
```
sulu_route:
    mappings:
        AppBundle\Entity\Example:
            generator: schema
            options:
                route_schema: /example/{object.getTitle()}
```

### Data-Navigation

The class `DataNavigationItem` got removed and is not supported
anymore. Please use other forms of navigating instead.

## 1.3.1

If Sulu is used in combination with a port, the port has to be included in the
URLs of the webspace configuration. So if you want to use Sulu on port 8080 the
configuration has to look like this:

```xml
<url>sulu.lo:8080/{localization}</url>
```

The port can still be emitted if the standard HTTP or HTTPS port is used.

## 1.3.0-RC3

### Resource-locator generation

The `generate` method of the `RlpStrategyInterface` uses `parentUuid` instead of `parentPath` now.
The signature changed from
`public function generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey = null);`
to
`public function generate($title, $parentUuid, $webspaceKey, $languageCode, $segmentKey = null);`

Also the `generateForUuid` method of the `RlpStrategyInterface` got removed.

### 190x Image Format

The image format "190x" got removed as it was only used in and old
design. If this format is needed for a website please redefine it
in the corresponding config file.

### Address country is nullable

To make it easier to migrate data the country in the address entity is now nullable in sulu.

```sql
ALTER TABLE co_addresses CHANGE idCountries idCountries INT DEFAULT NULL;
```

### Databases

#### ORM

The mapping structure of analytic settings have changed.
Use the following command to update:

```bash
app/console doctrine:schema:update --force
```

## 1.3.0-RC2

### RestController locale

The `getLocale` method of the RestController returned the locale
of the user (a system locale), if no request parameter with the name
`locale` was passed. As RestControllers most often provide access
to content in content locales and not in system locales,
this behaviour was removed. The `getLocale` method now just returns
a possibly passed request parameter named `locale` or null.
If a locale is needed for sure, the `getLocale` method needs to be
overwritten. It is also advised to override the method, if in no
case locales are needed.

### SearchController

The `SearchController` has been moved from sulu-standard to sulu. Therefore the
new template type `search` has been introduced. Just define the twig template
you want to use for the search in your webspace configuration:

```xml
<templates>
    <template type="search">ClientWebsiteBundle:views:query.html.twig</template>
</templates>
```

The name of the route also changed from `website_search` to
`sulu_search.website_search`, because the controller is located in the
SuluSearchBundle now.

### Removed HTTP Cache Paths-Handler

The HTTP Cache Integration has been refactored. The following configuration
is not available anymore and must be removed: `sulu_http_cache.handlers.paths`

### Webspace Configuration

The configuration schema for webspaces has changed. Instead of
`error-templates` you have to define `templates` now with a certain type.
For the error templates this type is `error` for the default error, and
`error-<code>` for certain error codes.

Before:
```xml
<error-templates>
    <error-template code="404">SomeBundle:view:error404.html.twig</error-template>
    <error-template default="true">SomeBundle:view:error.html.twig</error-template>
</error-templates>
```

After:
```xml
<templates>
    <template type="error-404">SomeBundle:views:error404.html.twig</template>
    <template type="error">SomeBundle:views:error.html.twig</template>
</templates>
```

And the `resource-locator` node has moved from `portal` to `webspace`. 

This change only affects the files which use the 1.1 version of the webspace
schema definition.

## 1.3.0-RC1

### Image Formats
The image format "150x100" as well as the format "200x200" got removed
from the backend formats. If a website relied on this format,
it should be - as all image formats a website needs - defined
in the theme specific config file.
(http://docs.sulu.io/en/latest/book/creating-a-basic-website/configuring-image-formats.html)

### PHPCR
To adapt to the new PHPCR structure execute the migrations:

```
app/console phpcr:migrations:migrate
```

### Media selection overlay
The frontend component 'media-selection-overlay@sulumedia' got removed,
please use 'media-selection/overlay@sulumedia' instead.

### NodeRepository

The `orderBefore` method of the `NodeRepository` has been removed. Use the
`reorder` method of the `DocumentManager` instead.

### LocalizationProvider
The core LocalizationProvider (which provided the system locales)
got removed. At this point the WebspaceLocalizationProvider is the
only LocalizationProvider in Sulu. If the system locales 
(locales in which translations for the admin panel are available) are
needed, please refer directly to the config `sulu_core.translations`.

### Translations
The command `sulu:translate:import` got removed, as the export command
(`sulu:translate:export`) now takes its translations directly from
the translation files and not from the database anymore. This change
would only cause conflicts, if one had a dependency directly on the
translations in the database. If so, please use the files in the
`Resources` folders.

### Publishing

For the publishing a separate workspace was introduced. This workspace
will be created and correctly filled by the PHPCR migrations.

Because the search index is now split into draft and live pages you have
to reindex all the content:

```bash
app/console massive:search:purge --all
app/console massive:search:reindex
app/webconsole massive:search:reindex
```

Also the `persist` call of the `DocumentManager` changed it behavior.
After persisting a document it will not be available on the website
immediately. Instead you also need to call `publish` with the same
document and locale.

### PHPCR Sessions

The sessions for PHPCR were configured at `sulu_core.phpcr` in the
configuration. This happens now at `sulu_document_manager.sessions`. You can
define multiple sessions here using different names and refer to one of them as
default session using the `sulu_document_manager.default_session` and to
another as live session using the `sulu_document_manager.live_session`.

### Documemt Manager Initializer

The `initialize` method of the `InitializerInterface` has now also a `$purge`
parameter, which tells the initializer if it should purge something. The
Initializer can use this information or simply ignore it, but existing
Initializers have to adapt to the new interface.

### Twig variable `request.routeParameters` removed

The `request.routeParameters` variable has been removed because it is not longer required when generate an url.

**Before**

```twig
{{ path('client_website.search', request.routeParameters) }}
```

**After**

```twig
{{ path('client_website.search') }}
```

### TitleBehavior is not localized anymore

If you have implemented your own Documents with an `TitleBehavior`,
you will recognize that the title in PHPCR is not translated anymore.
If you still want this Behavior you have to switch to the
`LocalizedTitleBehavior`.

### Indexing title of pages for search

It was possible to define that the title field of a page should be
indexed as title, although this value was already the default:

```
<property name="title" type="text_line" mandatory="true">
    <meta>
        <title lang="en">Title</title>
    </meta>
    <tag name="sulu.search.field" type="string" role="title" />
</property>
 ```

 This setting does not work anymore, because the title is now handled
 separately from the rest of the structure, and the title is not indexed
 anymore with this tag. Just remove it, and it will be the same as
 before.

### Webspaces

We have deprecated (1.0) the schema for webspaces and created a new version (1.1) of it. 

```
<?xml version="1.0" encoding="utf-8"?>
<webspace xmlns="http://schemas.sulu.io/webspace/webspace"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/webspace/webspace http://schemas.sulu.io/webspace/webspace-1.1.xsd">
          
          ...
          
</webspace>
```

You should update your webspace.xml files soonish. To do that you simply have to move the `default-templates` and
`error-templates` from the `theme` node and put it into the `webspace` node after the `theme`.
 
The theme is now optional and can be used with a theme-bundle. Sulu has extracted this functionality to make it
replaceable with any theming bundle you want. To keep the old directory-structure and functionality please read the
next part of this file.

### Theming

If you have multiple themes (or you don't want to change the folder structure of you project) you have to include the
bundle https://github.com/sulu/SuluThemeBundle in your abstract kernel.

This bundle contains all the code which is necessary to use theming in your application.

### Configuration

The configuration of `sulu_content.preview` and `sulu_website.preview_defaults` has been moved to:

```
$ app/console config:dump-reference sulu_preview
# Default configuration for extension with alias: "sulu_preview"
sulu_preview:
    defaults:
        analytics_key:        UA-SULU-PREVIEW-KEY
    error_template:       ~ # Example: ClientWebsiteBundle:Preview:error.html.twig
    mode:                 auto

    # Used for the delayed send of changes
    delay:                500
```

The flag `sulu_content.preview.websocket` has been replaced with `sulu_websocket.enabled`. This flag is
now default `false`.

### Content-Types

The Interface or content-types has been cleaned. The function `ContentTypeInterface::readForPreview` will never
be called in the future and can therefor be removed.

## 1.2.7

### Default Country

The default country for addresses in the ContactBundle is set by the ISO 3166 country-code
instead the of database-id now.

## 1.2.4

### ContactRepository

A Interface for the ContactRepository has been created. Due to the refactoring
the function `appendJoins` has been changed from public to protected.
Therefore this function cannot be called anymore.

## 1.2.1

### UserRepository

The methods from the `UserProviderInterface` of Symfony have been moved to a
separate `UserProvider` implementation. Also, the `getUserInSystem` method
has been renamed to `findUserBySystem` and takes the system as an argument.

If you want to use the `Sulu` system you should inject the
`sulu_security.system` container parameter instead of hardcoding it.

## 1.2.0

### sulu_content_path
src/Sulu/Bundle/WebsiteBundle/Resources/config/services.xml
The twig function `sulu_content_path('/path')` now always returning the full-qualified-domain
`http://www.sulu.io/de/path`.

### Custom-Routes

The naming of the custom-routes with `type: portal` has changed. You can use now the configured name 
and pass the host and prefix in the parameter. The current parameter will be populated in the variable
`request.routeParameters`.

__before:__
```
{{ path(request.portalUrl ~'.'~ request.locale ~ '.website_search') }}
```

__after:__
```
{{ path('website_search', request.routeParameters) }}
```

### Admin

The navigation entry with the empty name wont be used in sulu anymore. It should be replaced by:

__before:__

```php
    $section = new NavigationItem('');
```

__after:__

```php
    $section = new NavigationItem('navigation.modules');
```

### Twig function `sulu_resolve_user`

This twig function returns now the user. To get the related contact use following code snippet:

```twig
{{ sulu_resolve_user(userId).contact.fullName }}
```

### Webspace validation

Webspaces which have unused localizations by portals will now be not valid and ignored. Remove this
localizations or add them to a portal.

### New security permission for cache
 
To be able to clear the cache the user need the permission LIVE in the
webspace context.

### Document-Manager

The Behaviors `TimestampBehavior` and `BlameBehavior` now save the values in the non-localized
properties. To keep the old behavior use the `LocalizedTimestampBehavior` and 
`LocalizedBlameBehavior` instead.

### Deprecated sulu:phpcr:init and sulu:webspace:init

The `sulu:phpcr:init` and `sulu:webspace:init` commands are now deprecated.
Use the `sulu:document:initialize` command instead.

### Definition of security contexts

The definition of security contexts in the `Admin` classes has changed. They
used to look like the following example:

```php
public function getSecurityContexts()
{
    return [
        'Sulu' => [
            'Media' => [
                'sulu.media.collections',
            ],
        ],
    ];
}
```

Now you should also pass the permission types that you want to enable in the
context:

```php
public function getSecurityContexts()
{
    return [
        'Sulu' => [
            'Media' => [
                'sulu.media.collections' => [
                    PermissionTypes::VIEW,
                    PermissionTypes::ADD,
                    PermissionTypes::EDIT,
                    PermissionTypes::DELETE,
                    PermissionTypes::SECURITY,
                ],
            ],
        ],
    ];
}
```

By default, we will enable the permission types `VIEW`, `ADD`, `EDIT`, `DELETE`
and `SECURITY` in your context.

### Page search index

The metadata for pages has changed. Run following command to update your search index

```bash
app/console massive:search:purge --all
app/console massive:search:reindex
```

### Media uploads

Write permissions for the webserver must be set on `web/uploads` instead of
`web/uploads/media` alone to support simple cache clearing.

### BlameSubscriber

The BlameBehavior has been moved from the DocumentManager component to the
Sulu Content component. Documents which implemented
`Sulu\Component\DocumentManager\Behavior\Audit\BlameBehavior` should now
implement `Sulu\Component\Content\Document\Behavior\BlameBehavior` instead.

### Contact Entity is required for User

When you create new `User` entities in your application it is required now
that this user has a `Contact` entity. The following SQL will return you
all users which have no contact entity. You need to update them manually.

```sql
SELECT * FROM se_users WHERE se_users.idContacts IS NULL 
```

### Admin Commands

The method `getCommands` on the Admin has been removed, because Symfony can
autodetect Commands in the `Command` directory of each bundle anyway. This only
affects you, if you have not followed the Symfony standards and located your
commands somewhere else.

### WebsiteRequestAnalyzer

The `Current`-part of all setters have been removed, because they have already
been removed from the getters. This only affects you if you have overridden the
`WebsiteRequestAnalyzer` and have called or overridden these methods.

### Databases

#### PHPCR

A new namespace and additional system nodes were added. To create them run the following command:

```bash
app/console sulu:document:initialize
```

#### ORM

The relational structure of categories, translations and users have changed. 
Use the following command to update:

```bash
app/console doctrine:schema:update --force
```

It might be possible that foreign key checks have to be disabled for this update.

### ContentNavigation & Navigation

The ContentNavigationItems & NavigationItems will be sorted by their position. If there is no position set, the item
will be placed behind all other items.

```php
$item = new ContentNavigationItem('content-navigation.entry');
$item->setPosition(10);
```

## 1.1.10

### Filter

Update the schema `app/console doctrine:schema:update --force` and run following SQL-Statement:
 
```sql
UPDATE re_conditions SET value = CONCAT('"', value, '"') WHERE value NOT LIKE '"%"';
INSERT INTO `re_operators` (`id`, `operator`, `type`, `inputType`) VALUES
    (16, 'and', 5, 'tags'),
    (17, 'or', 5, 'tags'),
    (18, '=', 6, 'auto-complete'),
    (19, '!=', 6, 'auto-complete');
INSERT INTO `re_operator_translations` (`id`, `name`, `locale`, `shortDescription`, `longDescription`, `idOperators`) VALUES
    (35, 'gleich', 'de', NULL, NULL, 18),
    (36, 'is', 'en', NULL, NULL, 18),
    (37, 'ungleich', 'de', NULL, NULL, 19),
    (38, 'is not', 'en', NULL, NULL, 19),
    (39, 'und', 'de', NULL, NULL, 16),
    (40, 'and', 'en', NULL, NULL, 16),
    (41, 'oder', 'de', NULL, NULL, 17),
    (42, 'or', 'en', NULL, NULL, 17);
```

Additionally the filter by country has changed. Run following SQL script to update your filter conditions:

```sql
UPDATE `re_conditions` SET `field` = 'countryId', `type` = 6, `value` = CONCAT('"', (SELECT `id` FROM `co_countries` WHERE `code` = REPLACE(`re_conditions`. `value`, '"', '') LIMIT 1), '"') WHERE `field` = 'countryCode' AND `operator` != 'LIKE';

DELETE FROM `re_filters` WHERE `re_filters`.`id` IN (SELECT `re_condition_groups`.`idFilters` FROM `re_condition_groups` LEFT JOIN `re_conditions` ON `re_condition_groups`.`id` = `re_conditions`.`idConditionGroups` WHERE `re_conditions`.`operator` = 'LIKE');
```

Filter with a "like" condition for country (account and contact) will be lost after the upgrade because there is no
functionality for that anymore.

## 1.1.2

### Reindex-Command & Date Content-Type

First remove the version node `201511240844` with following command:

```bash
app/console doctrine:phpcr:node:remove /jcr:versions/201511240844
```

Then run the migrate command (`app/console phpcr:migrations:migrate`) to remove translated properties with non locale
and upgrade date-values within blocks.

## 1.1.0

### IndexName decorators from MassiveSearchBundle

The names of the indexes in the system can now be altered using decorators. There
is also a `PrefixDecorator`, which can prefixes the index name with an installation
specific prefix, which can be set using the `massive_search.metadata.prefix`
parameter.

The configuration parameter `massive_search.localization_strategy` have been removed.

The indexes have to be rebuilt using the following command:

```bash
app/console massive:search:index:rebuild --purge
```

### List-Toobar

To enable a sticky behaviour take a look at the documentation <http://docs.sulu.io/en/latest/bundles/admin/javascript-hooks/sticky-toolbar.html>

### Url Content-Type

The old upgrade for the url content-type don't upgrade properties in blocks. Rerun the migrate command to upgrade them.

```bash
app/console phpcr:migrations:migrate
```

### User locking

The locked toggler in the user tab of the contact section now sets the `locked`
field in the `se_users` table. Before this setting was written to the
`disabled` flag in the `co_contacts` table, which is removed now. If you have
used this field make sure to backup the data before applying the following
command:

```bash
app/console doctrine:schema:update --force
```

### Date Content-Type

The type of the date value in the database was wrong to update your existing data use following command:

```bash
app/console phpcr:migrations:migrate
```

### Media View Settings

The media collection thumbnailLarge view was removed from the media, 
to avoid an error, remove all `collectionEditListView` from the user settings table.

```sql
DELETE FROM `se_user_settings` WHERE `settingsKey` = 'collectionEditListView';
```
### Search

To index multiple fields (and `category_list` content-type) you have to add the attribute `type="array"` to the
`sulu.search.field` tag. The `tag_list` content-type has its own search-field type `tags`
(`<tag name="sulu.search.field" type="tags"/>`).

### Category Content-Type

The category content-type converts the selected ids into category data only for website rendering now. 

### System Collections

Remove the config `sulu_contact.form.avatar_collection` and note it you will need it in the sql statement below for the
placeholder `{old-avatar-collection}` (default value is `1`).

Update the database schema and then update the data-fixtures by running following sql statement.

```bash
app/console doctrine:schema:update --force
```

```sql
UPDATE me_collection_types SET collection_type_key='collection.default', name='Default' WHERE id=1;
INSERT INTO me_collection_types (id, name, collection_type_key) VALUES ('2', 'System Collections', 'collection.system');
```

The following sql statement moves the avatar images into the newly created system collections. To find the value for the
placeholder `{new-system-collection-id}` you can browse in the admin to the collection and note the `id` you find in the
url.

```sql
UPDATE me_media SET idCollections={new-system-collection-id} WHERE idCollections={old-avatar-collection};
```

### Search

The search mapping has to be changed, in particular the `index` tag. It is now
evaluated the same way as the other fields, so using `<index name="..."/>` will
now try to resolve the name of the index using a property from the given
object. If the old behavior is desired `<index value="..."/>` should be used
now.

Also the structure of the indexes has changed. Instead of one `page` index
containing all the pages this index is split into smaller ones after the scheme
`page_<webspace-key>`. This means that your own SearchController have to be
adapted. Additionally you have to rebuild your index, in order for these
changes to apply:

```bash
app/console massive:search:index:rebuild --purge
```

### Category
Category has now a default locale this has to set before use. You can use this sql statement after update your schema
(`app/console doctrine:schema:update --force`):

```sql
UPDATE ca_categories AS c SET default_locale = (SELECT locale FROM ca_category_translations WHERE idCategories = c.id LIMIT 1) WHERE default_locale = "";
```

### Websocket Component
The following Interfaces has new methods

Interface                                                             | Method                                                                  | Description
----------------------------------------------------------------------|-------------------------------------------------------------------------|---------------------------------------------------
Sulu/Component/Websocket/MessageDispatcher/MessageHandlerInterface    | onClose(ConnectionInterface $conn, MessageHandlerContext $context)      | will be called when a connection is closed or lost.
Sulu/Component/Websocket/MessageDispatcher/MessageDispatcherInterface | onClose(ConnectionInterface $conn, ConnectionContextInterface $context) | will be called when a connection is closed or lost.

### Logo/Avatar in Contact-Section
Can now be deleted from collection view. For that the database has to be updated.

```bash
app/console doctrine:schema:update --force
```

### Infinite scroll
The infinite-scroll-extension got refactored. To initialize infinite-scroll on an element, use
"this.sandbox.infiniteScroll.initialize(selector, callback)" instead of "this.sandbox.infiniteScroll(selector, callback)"
now. To unbind an infinite-scroll handler, use "this.sandbox.infiniteScroll.destroy(selector)"

### URL-ContentType
The URL-ContentType can now handle schemas like http or https. For that you have to add the default scheme to the
database records by executing following SQL statement:

```sql
UPDATE co_urls AS url SET url.url = CONCAT('http://', url.url) WHERE url.url NOT LIKE 'http://%';
```

To updated you content pages and snippets simply run:

```bash
app/console phpcr:migrations:migrate
```

Consider that the URL is now stored including the scheme (http://, ftp://, and so on), and therefore must not be
appended in the Twig template anymore.

### Media Metadata
Copyright field is now available in the metadata of medias. Therefore you have to update you database:

```bash
app/console doctrine:schema:update --force
```

### XML-Templates
Blocks now supports `minOccurs="0"` and `maxOccurs > 127`. For that the validation was improved and for both negative
values wont be supported anymore.

### Preview
The preview can now handle attributes and nested properties. To differentiate blocks and nested properties, it is now
necessary to add the property `typeof="collection"` to the root of a block `<div>` and 
`typeof="block" rel="name of block property"` to each child - see example.

__block:__

```twig
<div class="row" property="block" typeof="collection">
    {% for block in content.block %}
        <div rel="block" typeof="block">
            <h1 property="title">{{ block.title }}</h1>
        </div>
    {% endfor %}
</div>
```

__nested properties:__

```twig
<div property="is_winter">
    {% if content.is_winter %}
        <div property="article">{{ content.winter_article }}</div>
    {% endif %}
</div>
```

### Content Type Export Interface added

All default content type implement the new `ContentTypeExportInterface`.  
Content types which were exportable need to implement this interface and tag for which export `format` they are available.  

``` xml
        <service id="client_website.content.type.checkbox" class="%client_website.content.type.checkbox.class%">
            <tag name="sulu.content.type" alias="custom_checkbox"/>
            <tag name="sulu.content.export" format="1.2.xliff" translate="false" />
        </service>
```

### Extensions constructor changed

Extensions can also be exportable for this they need to implement the new `ExportExtensionInterface`.  
In the sulu excerpt extension the constructor changed, if you extend or overwrite this extension you maybe need to add
the `sulu_content.export.manager` service to the constructor.

### ApiCategory
The function `getTranslation` was removed.  This avoid a INSERT SQL Exception when a serialization of categories
(without translation) is called in the same request.

### Registering JS-Routes
When registering backbone-routes now - instead of directly starting the corresponding component via 'this.html' - make your callback returning the component.
So for example the following:
``` js
sandbox.mvc.routes.push({
    route: 'contacts/accounts/edit::id/:content',
    callback: function(id) {
        this.html('<div data-aura-component="accounts/edit@sulucontact" data-aura-id="' + id + '"/>');
    }
});
```
becomes:
``` js
sandbox.mvc.routes.push({
    route: 'contacts/accounts/edit::id/:content',
    callback: function(id) {
        return '<div data-aura-component="accounts/edit@sulucontact" data-aura-id="' + id + '"/>';
    }
});
```

### Media Content Selection Type attribute changed

When you use the sulu media selection in your custom bundle you need to change the `data-type`.

**Before:**

``` html
<div id="{{ id|raw }}"
    ...
    data-type="mediaSelection"
    data-aura-component="media-selection@sulumedia"
    ...
</div>
```

**After:**
``` html
<div id="{{ id|raw }}"
    ...
    data-type="media-selection"
    data-aura-component="media-selection@sulumedia"
    ...
</div>
```

### Header
The header got a complete redesign, the breadcrumb and bottom-content are not available anymore. Also the event `header.set-toolbar` got marked as deprecated. The recommended way to start a sulu-header is via the header-hook of a view-component.

Some properties in the header-hook have changed, some are new, some not supported anymore. For a complete overview on the current properties in the header-hook see the documentation: http://docs.sulu.io/en/latest/bundles/admin/javascript-hooks/index.html

The major work when upgrading to the new header is to change the button-templates to sulu-buttons. Before you had to pass a template like e.g. 'default', which initialized a set of buttons, now each button is passed explicitly which gives you more flexibility. Lets have a look at an example:

**Before:**
```js
header: {
    tabs: {
        url: '/admin/content-navigations?alias=category'
    },
    toolbar: {
        template: 'default'
    }
}
```

**After:**
```js
header: {
    tabs: {
        url: '/admin/content-navigations?alias=category'
    },
    toolbar: {
        buttons: {
            save: {},
            settings: {
                options: {
                    dropdownItems: {
                        delete: {}
                    }
                }
            }
        }
    }
}
```

If you are using the `default` template in the header and now change to the sulu-buttons `save` and `delete` the emitted events changed.

| **Before**                    | **After**                    |
|-------------------------------|------------------------------|
| `sulu.header.toolbar.save`    | `sulu.toolbar.save`          |
| `sulu.header.toolbar.delete`  | `sulu.toolbar.delete`        |

Also the call for `disable`, `enable` and `loading` state of the `save` button has changed:

**Before:**

``` js
this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', false); // enable
this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', true, true); // disabled
this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button'); // loading 
```

**After:**

``` js
this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false); // enable
this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true); // disabled
this.sandbox.emit('sulu.header.toolbar.item.loading', 'save'); // loading 
```

#### Tabs
The tabs can be configured with the 'url', 'data' and 'container' option. The option 'fullControll' got removed. You can get the same effect by passing data with no 'component'-property.
For a complete overview on the current properties in the header-hook see the documentation: http://docs.sulu.io/en/latest/bundles/admin/javascript-hooks/index.html

#### Toolbar
The language-changer can be configured as it was. 'Template' and 'parentTemplate' in contrast are not supported anymore. Instead you pass an array of sulu-buttons.
Moreover the format of the buttons itself changed: https://github.com/massiveart/husky/blob/f9b3abeb547553c9c031710f1f98d0288b08ca9c/UPGRADE.md
Have a look at the documentation: http://docs.sulu.io/en/latest/bundles/admin/javascript-hooks/header.html

#### Language changer
The interface of the language-changer in the header hook stayed the same, however the emitted event changed from `sulu.header.toolbar.language-changed` to `sulu.header.language-changed`. A callback to this event recieves an object with an `id`- and a `title`-property.

**Before:**
```js
this.sandbox.on('sulu.header.toolbar.language-changed', this.languageChanged.bind(this));
// ...
languageChanged: function(locale) {
    this.options.locale = locale;
}
```

**After:**
```js
this.sandbox.on('sulu.header.language-changed', this.languageChanged.bind(this));
// ...
languageChanged: function(locale) {
    this.options.locale = locale.id;
}
```

#### Sulu-buttons
Buttons for toolbars get specified in an aura-extension (`sandbox.sulu.buttons` and `sandbox.sulu.buttons.dropdownItems`). Therfore each bundle can add their own buttons to the pool. The toolbar in the header fetches its buttons from this pool.
Have a look at the documentation: http://docs.sulu.io/en/latest/bundles/admin/sulu-buttons.html

#### List-toolbar
The 'inHeader' option got removed and is not supported anymore. `Sulu.buttons` are used internally and can be passed via the template which is recommended instead of using string templates.

#### Content-Types

Interface of Method has changed.

Old                                | New
-----------------------------------|---------------------------------------------------------------------
public function getDefaultParams() | public function getDefaultParams(PropertyInterface $property = null)

### Modified listbuilder to work with expressions

The listbuilder uses now expressions to build the query. In course of these changes some default values have been
removed from some methodes of the `AbstractListBuilder` because of unclear meaning / effect. Changed function parameters:

- where (conjunction removed)
- between (conjunction removed)

### Security

The security now requires its own phpcr namespace for storing security related information. To register this namespace
execute the following command.

```bash
app/console sulu:phpcr:init
```

### Enabled listbuilder to have multiple sort fields

It's now possible to have multiple sort fields by calling `sort()`. It's previous behavior was to always reset the sort
field, instead of adding a new one. Check if you haven't applied `sort()` multiple times to a listbuilder with the
purpose of overriding its previous sort field.

### Datagrid style upgrade

- Deleted options: fullwidth, stickyHeader, rowClickSelect
- The list-view with no margin was removed from the design. The view is still of width: 'max' but now with spacing on the left and right.
- For routing from a list to the edit the new option actionCallback should be used. For other actions like displaying additional information in the sidebar there exists a new option clickCallback. These two callbacks alow the component to adapt its style depending on if there is an action or not. For special cases there is still the item.click event.

### Filter conjunction field is nullable

```bash
app/console doctrine:schema:update --force
```

## 1.0.8

The `sulu_meta_seo` twig method does not render the canonical tag for shadow pages. Therefore this method is deprecated
and will be removed with Sulu 1.2. Use the new `sulu_seo` method instead. This method will also render the title, so
there is no need for the title block as it has been in the Sulu standard edition anymore.

## 1.0.6

### Configuration

The syntax of `sulu_core.locales` configuration has changed. It has to be defined with a translation. Additional the
translations of backend (currently only en/de) and a fallback locale can be configured.

```
sulu_core:
    locales:
        de: Deutsch
        en: English
    fallback_locale: 'en'
    translations: ['de', 'en']
```

## 1.0.4

### External link

If you have external-link pages created before 1.0.0 you should run the following command to fix them.
 
```
app/console phpcr:migrations:migrate
```

### Shadow-Pages

Filter values will now be copied from shadow-base locale to shadowed locale. Upgrade your data with following command:

```bash
app/console phpcr:migrations:migrate
```

### User serialization

The groups of the JMSSerializer for the users have changed. Make sure to include the group `fullUser` in the
`SerializationContext` if you are missing some fields in the serialized User.

## 1.0.0

### User / Role management changed

Service `sulu_security.role_repository` changed to `sulu.repository.role`.
Service `sulu_security.user_repository` should be avoided. Use `sulu.repository.user` instead.

### Snippets

Snippet state has been removed and set default to published. Therefor all snippets has to be set to published by this
running this command for each <locale>:

```bash
app/console doctrine:phpcr:nodes:update --query="SELECT * FROM [nt:unstructured] WHERE [jcr:mixinTypes] = 'sulu:snippet'" --apply-closure="\$node->setProperty('i18n:<locale>-state', 2);"
```

### Page-Templates

1. The tag `sulu.rlp` is now mandatory for page templates.
2. Page templates will now be filtered: only implemented templates in the theme will be displayed in the dropdown.
 
To find pages with not implemented templates run following command:

```bash
app/console sulu:content:validate <webspace-key>
```

To fix that pages, you could implement the template in the theme or save the pages with an other template over ui.

### Webspaces

1. The default-template config moved from global configuration to webspace config. For that it is needed to add this config to each webspace. 
2. The excluded xml tag has been removed from the webspace configuration file, so you have to remove this tag from all these files.

After that your webspace theme config should look like this:

```xml
<?xml version="1.0" encoding="utf-8"?>
<webspace xmlns="http://schemas.sulu.io/webspace/webspace"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/webspace/webspace http://schemas.sulu.io/webspace/webspace-1.0.xsd">

...

    <theme>
        <key>default</key>
        <default-templates>
            <default-template type="page">default</default-template>
            <default-template type="homepage">overview</default-template>
        </default-templates>
    </theme>

...

</webspace>
```

Also remove the following default-template config for page and homepage from the file `app/config/config.yml`:

```yml
sulu_core:
    content:
        structure:
            default_type:
                snippet: "default"
-               page: "default"
-               homepage: "overview"
```

## 1.0.0-RC3

### Document Manager

The new Document Manager have been introduced, which means that a few files need to be updated.

There is a new namespace `Sulu\Component\Content\Compat`, which acts as a compatability layer. So all the controllers
extending the `DefaultController` and adapting its `indexAction` need to change a use statement from
`Sulu\Component\Content\StructureInterface` to `Sulu\Component\Content\Compat\StructureInterface`.

There are also some changes in the database, so you have to run the migrations:

```
app/console phpcr:migrations:migrate
```

In case the system still contains old data from the internal links content type you also have to run the upgrade command
again.

```
app/console sulu:upgrade:rc3:internal-links
```

The following classes have been moved, and every reference to them has to be updated:

Old name                                                 | New name
---------------------------------------------------------|---------------------------------------------------------------
Sulu\Component\Content\Event\ContentNodeDeleteEvent      | Sulu\Component\Content\Mapper\Event\ContentNodeDeleteEvent
Sulu\Component\Content\Event\ContentNodeEvent            | Sulu\Component\Content\Mapper\Event\ContentNodeEvent
Sulu\Component\Content\Event\ContentNodeOrderEvent       | Sulu\Component\Content\Mapper\Event\ContentNodeOrderEvent
Sulu\Component\Content\Block\BlockProperty               | Sulu\Component\Content\Compat\Block\BlockProperty
Sulu\Component\Content\Block\BlockPropertyInterface      | Sulu\Component\Content\Compat\Block\BlockPropertyInterface
Sulu\Component\Content\Block\BlockPropertyType           | Sulu\Component\Content\Compat\Block\BlockPropertyType
Sulu\Component\Content\Block\BlockPropertyWrapper        | Sulu\Component\Content\Compat\Block\BlockPropertyWrapper
Sulu\Component\Content\Section\SectionProperty           | Sulu\Component\Content\Compat\Section\SectionProperty
Sulu\Component\Content\Section\SectionPropertyInterface  | Sulu\Component\Content\Compat\Section\SectionPropertyInterface
Sulu\Component\Content\ErrorStructure                    | Sulu\Component\Content\Compat\ErrorStructure
Sulu\Component\Content\Section\MetaData                  | Sulu\Component\Content\Compat\MetaData
Sulu\Component\Content\Section\PageInterface             | Sulu\Component\Content\Compat\PageInterface
Sulu\Component\Content\Section\Property                  | Sulu\Component\Content\Compat\Property
Sulu\Component\Content\Section\PropertyInterface         | Sulu\Component\Content\Compat\PropertyInterface
Sulu\Component\Content\Section\PropertyParameter         | Sulu\Component\Content\Compat\PropertyParameter
Sulu\Component\Content\Section\PropertyTag               | Sulu\Component\Content\Compat\PropertyTag
Sulu\Component\Content\Section\Structure                 | Sulu\Component\Content\Compat\Structure
Sulu\Component\Content\Section\StructureInterface        | Sulu\Component\Content\Compat\StructureInterface
Sulu\Component\Content\Section\StructureManager          | Sulu\Component\Content\Compat\StructureManager
Sulu\Component\Content\Section\StructureManagerInterface | Sulu\Component\Content\Compat\StructureManagerInterface
Sulu\Component\Content\Section\StructureTag              | Sulu\Component\Content\Compat\StructureTag
Sulu\Component\Content\Section\StructureType             | Sulu\Component\Content\Compat\StructureType

### Upgrade commands

All the upgrade commands have been removed, since they are not of any use for
future versions of Sulu. The only exception is the
`sulu:upgrade:0.9.0:resource-locator` command, which has been renamed to
`sulu:content:resource-locator:maintain`.

### Contact management changed

Service `sulu_contact.contact_repository` changed to `sulu.repository.contact`.

| Removed methods                                                           | Use instead                                                               |
|---------------------------------------------------------------------------|---------------------------------------------------------------------------|
| `Sulu/Bundle/ContactBundle/Api/Account:addAccountAddresse`                | `Sulu/Bundle/ContactBundle/Api/Account:addAccountAddress`                 |
| `Sulu/Bundle/ContactBundle/Api/Account:removeAccountAddresse`             | `Sulu/Bundle/ContactBundle/Api/Account:removeAccountAddress`              |
| `Sulu/Bundle/ContactBundle/Api/Contact:addFaxe`                           | `Sulu/Bundle/ContactBundle/Api/Contact:addFax`                            |
| `Sulu/Bundle/ContactBundle/Api/Contact:removeFaxe`                        | `Sulu/Bundle/ContactBundle/Api/Contact:removeFax`                         |
| `Sulu/Bundle/ContactBundle/Api/Contact:addCategorie`                      | `Sulu/Bundle/ContactBundle/Api/Contact:addCategory`                       |
| `Sulu/Bundle/ContactBundle/Api/Contact:removeCategorie`                   | `Sulu/Bundle/ContactBundle/Api/Contact:removeCategory`                    |
| `Sulu/Bundle/ContactBundle/Entity/AbstractAccount:addAccountAddresse`     | `Sulu/Bundle/ContactBundle/Entity/AbstractAccount:addAccountAddress`      |
| `Sulu/Bundle/ContactBundle/Entity/AbstractAccount:removeAccountAddresse`  | `Sulu/Bundle/ContactBundle/Entity/AbstractAccount:removeAccountAddress`   |
| `Sulu/Bundle/ContactBundle/Entity/AbstractAccount:addCategorie`           | `Sulu/Bundle/ContactBundle/Entity/AbstractAccount:addCategory`            |
| `Sulu/Bundle/ContactBundle/Entity/AbstractAccount:removeCategorie`        | `Sulu/Bundle/ContactBundle/Entity/AbstractAccount:removeCategory`         |
| `Sulu/Bundle/ContactBundle/Entity/Address:addAccountAddresse`             | `Sulu/Bundle/ContactBundle/Entity/Address:addAccountAddress`              |
| `Sulu/Bundle/ContactBundle/Entity/Address:removeAccountAddresse`          | `Sulu/Bundle/ContactBundle/Entity/Address:removeAccountAddress`           |
| `Sulu/Bundle/ContactBundle/Entity/Address:addContactAddresse`             | `Sulu/Bundle/ContactBundle/Entity/Address:addContactAddress`              |
| `Sulu/Bundle/ContactBundle/Entity/Address:removeContactAddresse`          | `Sulu/Bundle/ContactBundle/Entity/Address:removeContactAddress`           |
| `Sulu/Bundle/ContactBundle/Entity/Contact:addFaxe`                        | `Sulu/Bundle/ContactBundle/Entity/Contact:addFax`                         |
| `Sulu/Bundle/ContactBundle/Entity/Contact:removeFaxe`                     | `Sulu/Bundle/ContactBundle/Entity/Contact:removeFax`                      |
| `Sulu/Bundle/ContactBundle/Entity/Contact:addContactAddresse`             | `Sulu/Bundle/ContactBundle/Entity/Contact:addContactAddress`              |
| `Sulu/Bundle/ContactBundle/Entity/Contact:removeContactAddresse`          | `Sulu/Bundle/ContactBundle/Entity/Contact:removeContactAddress`           |
| `Sulu/Bundle/ContactBundle/Entity/Contact:addCategorie`                   | `Sulu/Bundle/ContactBundle/Entity/Contact:addCategory`                    |
| `Sulu/Bundle/ContactBundle/Entity/Contact:removeCategorie`                | `Sulu/Bundle/ContactBundle/Entity/Contact:removeCategory`                 |

## 1.0.0-RC2

### Twig-Extensions

Following Twig-Functions has changed the name (new prefix for sulu functions):

| Before                    | Now                           |
|---------------------------|-------------------------------|
| `resolve_user`            | `sulu_resolve_user`           |
| `content_path`            | `sulu_content_path`           |
| `content_root_path`       | `sulu_content_root_path`      |
| `get_type`                | `sulu_get_type`               |
| `needs_add_button`        | `sulu_needs_add_button`       |
| `get_params`              | `sulu_get_params`             |
| `parameter_to_select`     | `sulu_parameter_to_select`    |
| `parameter_to_key_value`  | `sulu_parameter_to_key_value` |
| `content_load`            | `sulu_content_load`           |
| `content_load_parent`     | `sulu_content_load_parent`    |
| `get_media_url`           | `sulu_get_media_url`          |
| `meta_alternate`          | `sulu_meta_alternate`         |
| `meta_seo`                | `sulu_meta_seo`               |
| `navigation_root_flat`    | `sulu_navigation_root_flat`   |
| `navigation_root_tree`    | `sulu_navigation_root_tree`   |
| `navigation_flat`         | `sulu_navigation_flat`        |
| `navigation_tree`         | `sulu_navigation_tree`        |
| `breadcrumb`              | `sulu_breadcrumb`             |
| `sitemap_url`             | `sulu_sitemap_url`            |
| `sitemap`                 | `sulu_sitemap`                |
| `snippet_load`            | `sulu_snippet_load`           |

To automatically update this name you can run the following script. If your themes are not in the ClientWebsiteBundle
you have to change the folder in the second line.

```
#!/usr/bin/env bash
TWIGS=($(find ./src/Client/Bundle/WebsiteBundle/Resources/themes -type f -iname "*.twig"))

NAMES[0]="resolve_user"
NAMES[1]="content_path"
NAMES[2]="content_root_path"
NAMES[3]="get_type"
NAMES[4]="needs_add_button"
NAMES[5]="get_params"
NAMES[6]="parameter_to_select"
NAMES[7]="parameter_to_key_value"
NAMES[8]="content_load"
NAMES[9]="content_load_parent"
NAMES[10]="get_media_url"
NAMES[11]="meta_alternate"
NAMES[12]="meta_seo"
NAMES[13]="navigation_root_flat"
NAMES[14]="navigation_root_tree"
NAMES[15]="navigation_flat"
NAMES[16]="navigation_tree"
NAMES[17]="breadcrumb"
NAMES[18]="sitemap_url"
NAMES[19]="sitemap"
NAMES[20]="snippet_load"

for twig in ${TWIGS[*]}
do
    for name in ${NAMES[*]}
    do
        sed -i '' -e "s/$name/sulu_$name/g" $twig
    done
done
```

After running this script please check the changed files for conflicts and wrong replaces! 

### Website Navigation

Children of pages with the state "test" or pages which have the desired navigaiton context not assigned won't be moved
up in the hierarchy, instead they won't show up in the navigation at all.

## 1.0.0-RC1

### Security Roles
The identifiers in the acl_security_identities should be rename from SULU_ROLE_* to ROLE_SULU_*. This SQL snippet should 
do the job for you, you should adapt it to fit your needs:

UPDATE `acl_security_identities` SET `identifier` = REPLACE(`identifier`, 'SULU_ROLE_', 'ROLE_SULU_');

### Texteditor

The params for the texteditor content type where changed.

| Before                                        | Now                                                                                                           |
|-----------------------------------------------|---------------------------------------------------------------------------------------------------------------|
| `<param name="tables" value="true" />`        | `<param name="table" value="true" />`                                                                         |
| `<param name="links" value="true" />`         | `<param name="link" value="true" />`                                                                          |
| `<param name="pasteFromWord" value="true" />` | `<param name="paste_from_word" value="true" />`                                                               |
| `<param name="maxHeight" value="500" />`      | `<param name="max_height" value="500" />`                                                                     |
|                                               |                                                                                                               |
| `<param name="iframes" value="true" />`       | iframe and script tags can activated with an ckeditor parameter:                                              |
| `<param name="scripts" value="true" />`       | `<param name="extra_allowed_content" value="img(*)[*]; span(*)[*]; div(*)[*]; iframe(*)[*]; script(*)[*]" />` |


## 0.18.0

## Search index rebuild

Old data in search index can cause problems. You should clear the folder `app/data` and rebuild the index.
 
```bash
rm -rf app/data/*
app/console massive:search:index:rebuild
```

### Search adapter name changed

Adapter name changed e.g. from `massive_search_adapter.<adaptername>` to just `<adaptername>` in
configuration.

### Search index name changed

Pages and snippets are now indexed in separate indexes for pages and snippets.
Replace all instances of `->index('content')` with `->indexes(array('page',
'snippet')`.

### Search searches non-published pages by default

Pages which are "Test" are no longer indexed. If you require only
"published" pages modify your search query to start with: `state:published AND `
and escape the quotes:

```php
$hits = $searchManager
    ->createSearch(sprintf('state:published AND "%s"', str_replace('"', '\\"', $query)))
    ->locale($locale)
    ->index('page')
    ->execute();
```

### PHPCR: Doctrine-Dbal

The structure of data has changed. Run following command:

```bash
app/console doctrine:schema:update --force
```

### Smart content tag operator

The default operator for tags is now changed to OR. So you have to update with the following command,
because the previous default operator was AND.

```bash
app/console sulu:upgrade:0.18.0:smart-content-operator tag and
```

### Media Format Cache Public Folder

If you use the `sulu_media.format_cache.public_folder` parameter, 
the following configuration update need to be done,
because the parameter does not longer exists:

``` yml
sulu_media:
    format_cache:
        public_folder: 'public' # delete this line
        path: %kernel.root_dir%/../public/uploads/media # add this new configuration
```

### Admin

The `Sulu` prefix from all `ContentNavigationProviders` and `Admin` classes has
been removed. You have to change these names in all usages of this classes in
your own code.

### Media image converter commands

The image converter commands are now handled via service container tags. No need for the 
`sulu_media.image.command.prefix` anymore. If you have created your own command, you have to
tag your image converter command service with `sulu_media.image.command`.

Before:

```xml
<services>
    <service id="%sulu_media.image.command.prefix%blur" class="%acme.image.command.blur.class%" />
</services>
```

Change to:

```xml
<services>
    <service id="acme.image.command.blur" class="%acme.image.command.blur.class%">
        <tag name="sulu_media.image.command" alias="resize" />
    </service>
</services>
```

### Media preview urls

The thumbnail url will only be generated for supported mime-types. Otherwise it returns a zero length array.

To be sure that it is possible to generate a preview image you should check if the thumbnail url isset:

```twig
{% if media.thumbnails['200x200'] is defined %}
<img src="{{ media.thumbnails['200x200'] }}"/>
{% endif %}
```

### Error templates

Variables of exception template `ClientWebsiteBundle:error404.html.twig` has changed.

* `status_code`: response code 
* `status_text`: response text
* `exception`: whole exception object
* `currentContent`: content which was rendered before exception was thrown

Especially for 404 exception the `path` variable has been removed.

Before:
```twig
<p>The path "<em>{{ path }}</em>" does not exist.</p>
```

After:
```twig
<p>The path "<em>{{ request.resourceLocator }}</em>" does not exist.</p>
```

The behaviour of the errors has changed. In dev mode no custom error pages appears.
To see them you have to open following url:

```
{portal-prefix}/_error/{status_code}
sulu.lo/de/_error/500
```

More Information can be found in [sulu-docs](http://docs.sulu.io/en/latest/cookbook/custom-error-page.html).

To keep the backward compatibility you have to add following lines to your webspace configuration:

```xml
<webspace>
    ...
    
    <theme>
        ...
        
        <error-templates>
            <error-template code="404">ClientWebsiteBundle:views:error404.html.twig</error-template>
            <error-template default="true">ClientWebsiteBundle:views:error.html.twig</error-template>
        </error-templates>
    </theme>
    
    ...
</webspace>
```

### Twig Templates

If a page has no url for a specific locale, it returns now the resource-locator to the index page (`'/'`) instead of a
empty string (`''`).
 
__Before:__
```
urls = array(
    'de' => '/ueber-uns',
    'en' => '/about-us',
    'es' => ''
);
```

__After:__
```
urls = array(
    'de' => '/ueber-uns',
    'en' => '/about-us',
    'es' => '/'
);
```

### Util

The `Sulu\Component\Util\UuidUtils` has been removed. Use the `Phpcr\Utils\UuidHelper` instead.

## 0.17.0

### Media

Fill up the database column `me_collection_meta.locale` with the translated language like: `de` or `en`. If you
know you have only added collections in only one language you can use following sql statement:

```sql
UPDATE `me_collection_meta` SET `locale` = 'de';
```

Due to this it is possible that one collection has multiple metadata for one language. You have to remove this
duplicates by hand. For example one collection should have only one meta for the language `de`.

The collection and media has now a specific field to indicate which meta is default. For this run following commands.

```bash
app/console sulu:upgrade:0.17.0:collections
app/console sulu:upgrade:0.17.0:media
```

### Content navigation

The interfaces for the content navigation have been changed, so you have to
apply these changes if you have used a content navigation in your bundle.

Basically you can delete the `NavigationController` delivering the content
navigation items together with its routes. It's now common to suffix the
classes providing content navigation items with `ContentNavigationProvider`.

These classes have to implement the `ContentNavigationProviderInterface` and be
registered as services as described in the
[documentation](http://docs.sulu.io/en/latest/cookbook/using-the-tab-navigation.html).

Consider that the URLs for the retrieval of the content navigation items have
changed to `/admin/content-navigations?alias=your-alias` and have to be updated
in your javascript components.

### Contact and Account Security

The security checks are now also applied to contacts and accounts, make sure
that the users you want to have access have the correct permissions.

### Content

Behaviour of internal links has changed. It returns the link title for navigation/smartcontent/internal-link.

### Media Types

The media types are now set by wildcard check and need to be updated,
by running the following command: `sulu:media:type:update`.

### Media API Object

The `versions` attribute of the media API object changed from [array to object list](https://github.com/sulu-io/docs/pull/14/files).

### Contact

CRM-Components moved to a new bundle. If you enable the new Bundle everything should work as before.

BC-Breaks are:

 * AccountCategory replaced with standard Categories here is a migration needed
 
For a database upgrade you have to do following steps:

* The Account has no `type` anymore. This column has to be removed from `co_accounts` table.
* The table `co_account_categories` has to be removed manually.
* The table `co_terms_of_delivery` has to be removed manually.
* The table `co_terms_of_payment` has to be removed manually.
* `app/console doctrine:schema:update --force`

### Security

The names of some classes have changed like shown in the following table:

Old name                                                          | New name
------------------------------------------------------------------|--------------------------------------------------------------
Sulu\Bundle\SecurityBundle\Entity\RoleInterface                   | Sulu\Component\Security\Authentication\RoleInterface
Sulu\Component\Security\UserInterface                             | Sulu\Component\Security\Authentication\UserInterface
Sulu\Bundle\SecurityBundle\Factory\UserRepositoryFactoryInterface | Sulu\Component\Security\Authentication\UserRepositoryFactoryInterface
Sulu\Component\Security\UserRepositoryInterface                   | Sulu\Component\Security\Authentication\UserRepositoryInterface
Sulu\Bundle\SecurityBundle\Permission\SecurityCheckerInterface    | Sulu\Component\Security\Authorization\SecurityCheckerInterface

If you have used any of these interfaces you have to update them.

## 0.16.0

### Content Types

Time content types returns now standardized values (hh:mm:ss) and can handle this as localized string in the input
field.

For content you can upgrade the pages with:

```bash
app/console sulu:upgrade:0.16.0:time
```

In the website you should change the output if time to your format.

If you use the field in another component you should upgrade your api that it returns time values in format (hh:mm:ss).

### Security

Database has changed: User has now a unique email address. Run following command:

```bash
app/console doctrine:schema:update --force
```

## 0.15.0

### Sulu Locales
The Sulu Locales are not hardcoded anymore, but configured in the `app/config/config.yml` file:

```yml
sulu_core:
    locales: ["de","en"]
```

You have to add the locales to your configuration, otherwise Sulu will stop working.

### Internal Links

The internal representation of the internal links have changed, you have to run the following command to convert them:

```bash
app/console sulu:upgrade:0.15.0:internal-links
```

### Content Types

PropertyParameter are now able to hold metadata. Therefore the Interface has changed. Please check all your
ContentTypes which uses params.

__Before:__

DefaultParams:

```php
array(
    'name' => 'value',
    ...
)
```
Access in Twig:

```twig
{{ params.name }}
```

__After:__

```php
array(
    'name' => new PropertyParameter('name', 'string', 'value', array()),
    ...
)
```
Access in Twig:

```twig
As String:
{{ params.name }}

As Array or boolean:
{{ params.name.value }}

Get translated title:
{{ params.name.getTitle('de') }}
```

__Optional:__

Metadata under properties in template:

```xml
<property name="smart_content" type="smart_content">
    <params>
        <param name="display_as" type="collection">
            <param name="two">
                <meta>
                    <title lang="de">Zwei Spalten</title>
                    <title lang="en">Two columns</title>

                    <info_text lang="de">Die Seiten werden in zwei Spalten dargestellt</info_text>
                    <info_text lang="en">The pages would be displayed in two columns</info_text>
                </meta>
            </param>
        </param>
    </params>
</property>
```

### Websocket Component

Websocket start command changed to `app/console sulu:websocket:run`. If you use xdebug on your server please start
websockets with `app/console sulu:websocket:run -e prod`.

Default behavior is that websocket turned of for preview, if you want to use it turn it on in the
`app/config/admin/config.yml` under:

```yml
 sulu_content:
     preview:
         mode: auto       # possibilities [auto, on_request, off]
         websocket: false # use websockets for preview, if true it tries to connect to websocket server,
                          # if that fails it uses ajax as a fallback
         delay: 300       # used for the delayed send of changes, lesser delay are more request but less latency
```

### HTTP Cache

The HTTP cache integration has been refactored. The following configuration
must be **removed**:

````yaml
sulu_core:
    # ...
    http_cache:
        type: symfonyHttpCache
````

The Symfony HTTP cache is enabled by default now, so there is no need to do
anything else. See the [HTTP cache
documentation](http://sulu.readthedocs.org/en/latest/reference/bundles/http_cache.html)
for more information.

### Renamed RequestAnalyzerInterface methods

The text "Current" has been removed from all of the request analyzer methods.
If you used the request analyzer service then you will probably need to update
your code, see: https://github.com/sulu-cmf/sulu/pull/749/files#diff-23

## 0.14.0

* Role name is now unique
  * check roles and give them unique names
* Apply all permissions correctly, otherwise users won't be able to work on snippets, categories or tags anymore

## 0.13.0

* Remove `/cmf/<webspace>/temp` from repository
  * run `app/console doctrine:phpcr:node:remove /cmf/<webspace>/temp` foreach webspace

## 0.12.0

* Permissions have to be correct now, because they are applied
  * otherwise add a permission value of 120 for `sulu.security.roles`,
    `sulu.security.groups` and `sulu.security.users` to one user to change
    the settings in the UI
  * also check for the correct value in the `locale`-column of the `se_user_roles`-table
    * value has to be a json-string (e.g. `["en", "de"]`)
* Snippet content type defaults to all snippet types available instead of the
  default one
  * Explicitly define a snippet type in the parameters if this is not desired
