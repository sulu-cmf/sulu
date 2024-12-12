# Upgrade

## 3.0.0

### Removed deprecations for 3.0

Removed classes / services:

- `Sulu/Bundle/MarkupBundle/Listener/SwiftMailerListener`
- `Sulu\Bundle\DocumentManagerBundle\Slugifier\Urlizer`
- `Sulu\Component\Rest\Listing\ListQueryBuilder`
- `Sulu\Component\Rest\Listing\ListRepository`
- `Sulu\Component\Rest\Listing\ListRestHelper`
- `Sulu\\Bundle\\MediaBundle\\Media\\Storage\\AzureBlobStorage`
- `Sulu\\Bundle\\MediaBundle\\Media\\Storage\\GoogleCloudStorage`
- `Sulu\\Bundle\\MediaBundle\\Media\\Storage\\LocalStorage`
- `Sulu\\Bundle\\MediaBundle\\Media\\Storage\\S3Storage`
- `Sulu\\Bundle\\MediaBundle\\DependencyInjection\\S3ClientCompilerPass` (internal)

Removed deprecated functions and properties:

- `Sulu\Component\Security\Event\PermissionUpdateEvent::getSecurityIdentity`
- `Sulu\Component\Webspace\Portal::getXDefaultLocalization`
- `Sulu\Component\Webspace\Portal::setXDefaultLocalization`
- `Sulu\Component\Localization\Localization::isXDefault`
- `Sulu\Bundle\MediaBundle\Controller\MediaController::$entityKey`

Removed unused arguments:

- `Sulu\Component\Webspace\Analyzer\Attributes\WebsiteRequestProcessor::__construct` `$contentMapper` (2nd argument) removed

### Piwik replaced with Matomo script

The script provided by Sulu for the piwik implementation has been updated to use mataomo path so the script is now pointing to matomo.php instead of the piwik.php file.

### Removing deprecated guzzle integration

As part of the update of flysystem the support for the guzzle client package `guzzlehttp/guzzle` has been removed. If you need it you need to manually require it.

The `GoogleGeolocator` and the `NominatimGeolocator` no longer support the Guzzle client and require a `Symfony\HttpClient` client instead.

### Updating to flysystem 3

The Sulu media storage uses now the Flysystem Bundle which need to be configured:

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        default.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/uploads'
```

Here are some examples on how to migrate individual providers:

<details>
  <summary>Sulu Media S3 to Flysystem S3</summary>

Before:

```yaml
sulu_media:
    storage: s3
    storages:
        s3:
            key: 'your aws s3 key'
            secret: 'your aws s3 secret'
            bucket_name: 'your aws s3 bucket name'
            path_prefix: 'optional/path/prefix'
            region: 'eu-west-1'
```

New:

```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'aws'
            options:
                client: 'aws_client_service' # see service below
                key: ''
                secret: ''
                bucket: 'bucket_name'
                prefix: 'optional/path/prefix'
                streamReads: true
services:
    aws_client_service:
        class: Aws\\S3\\S3Client
        arguments:
            - [] # Put the value of the parameter "sulu_media.media.storage.s3.arguments" here (empty array by default)
```

</details>

<details>
  <summary>Sulu Google Cloud Config to Flysystem S3</summary>

> [!NOTE]  
> If you were using `superbalist/flysystem-google-storage` replace it with the official package version `league/flysystem-google-cloud-storage`.

Before:

```yaml
sulu_media:
    storage: google_cloud
    storages:
        google_cloud:
            key_file_path: '/path/to/key.json'
            bucket_name: 'sulu-bucket'
            path_prefix: 'optional path prefix'
```


New:
```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'gcloud'
            options:
                client: 'gcloud_client_service' # The service ID of the Google\\Cloud\\Storage\\StorageClient instance
                bucket: 'bucket_name'
                prefix: 'optional/path/prefix'
```

</details>

<details>
  <summary>Sulu Azure Config to Flysystem Config</summary>

Before:

```yaml
sulu_media:
    storage: google_cloud
    storages:
        google_cloud:
            key_file_path: '/path/to/key.json'
            bucket_name: 'sulu-bucket'
            path_prefix: 'optional path prefix'
```

New

```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'azure'
            options:
                client: 'azure_client_service' # The service ID of the MicrosoftAzure\Storage\Blob\BlobRestProxy instance
                container: 'container_name'
                prefix: 'optional/path/prefix'
```

</details>

If you want use a [different storage](https://github.com/thephpleague/flysystem-bundle/blob/3.x/docs/2-cloud-storage-providers.md) for Sulu you can configure it here:

```yaml
# config/packages/sulu_media.yaml
sulu_media:
    storage:
        flysystem_service: 'default.storage' # this is default and not required to be configured.
```

This will only create the service `sulu_media.storage` as the alias to `sulu_media.storage.*` services has been removed.

