# Upgrade

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
