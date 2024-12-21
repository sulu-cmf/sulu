<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Kernel;

use Sulu\Bundle\TestBundle\SuluTestBundle;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Represents a kernel for sulu-application tests.
 */
class SuluTestKernel extends SuluKernel
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $environment, bool $debug, string $suluContext = SuluKernel::CONTEXT_ADMIN)
    {
        parent::__construct($environment, $debug, $suluContext);
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            // Dependencies
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Sulu\Bundle\CoreBundle\SuluCoreBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle(),
            new \Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \FOS\RestBundle\FOSRestBundle(),
            new \HandcraftedInTheAlps\RestRoutingBundle\RestRoutingBundle(),
            new \FOS\JsRoutingBundle\FOSJsRoutingBundle(),

            // Massive
            new \Massive\Bundle\SearchBundle\MassiveSearchBundle(),

            // Sulu
            new \Sulu\Bundle\AdminBundle\SuluAdminBundle(),
            new \Sulu\Bundle\SearchBundle\SuluSearchBundle(),
            new \Sulu\Bundle\PersistenceBundle\SuluPersistenceBundle(),
            new \Sulu\Bundle\PageBundle\SuluPageBundle(),
            new \Sulu\Bundle\ContactBundle\SuluContactBundle(),
            new \Sulu\Bundle\SecurityBundle\SuluSecurityBundle(),
            new \Sulu\Bundle\WebsiteBundle\SuluWebsiteBundle(),
            new \Sulu\Bundle\TestBundle\SuluTestBundle(),
            new \Sulu\Bundle\TagBundle\SuluTagBundle(),
            new \Sulu\Bundle\MediaBundle\SuluMediaBundle(),
            new \Sulu\Bundle\CategoryBundle\SuluCategoryBundle(),
            new \Sulu\Bundle\HttpCacheBundle\SuluHttpCacheBundle(),
            new \Sulu\Bundle\SnippetBundle\SuluSnippetBundle(),
            new \Sulu\Bundle\LocationBundle\SuluLocationBundle(),
            new \Sulu\Bundle\DocumentManagerBundle\SuluDocumentManagerBundle(),
            new \Sulu\Bundle\HashBundle\SuluHashBundle(),
            new \Sulu\Bundle\ActivityBundle\SuluActivityBundle(),
            new \Sulu\Bundle\CustomUrlBundle\SuluCustomUrlBundle(),
            new \Sulu\Bundle\RouteBundle\SuluRouteBundle(),
            new \Sulu\Bundle\MarkupBundle\SuluMarkupBundle(),
            new \Sulu\Bundle\PreviewBundle\SuluPreviewBundle(),
            new \Sulu\Bundle\AudienceTargetingBundle\SuluAudienceTargetingBundle(),
            new \Sulu\Bundle\TrashBundle\SuluTrashBundle(),
            new \Sulu\Bundle\ReferenceBundle\SuluReferenceBundle(),
        ];

        if (\class_exists(\PHPCR\PhpcrMigrationsBundle\PhpcrMigrationsBundle::class)) {
            $bundles[] = new \PHPCR\PhpcrMigrationsBundle\PhpcrMigrationsBundle();
        } elseif (\class_exists(\DTL\Bundle\PhpcrMigrations\PhpcrMigrationsBundle::class)) {
            // @deprecated use the phpcr/phpcr-migration-bundle
            @trigger_deprecation('sulu/sulu', '2.6', 'Using "%s" is deprecated, use "%s" instead.', 'dantleech/phpcr-migrations-bundle', 'phpcr/phpcr-migrations-bundle');
            $bundles[] = new \DTL\Bundle\PhpcrMigrations\PhpcrMigrationsBundle();
        }

        if (\class_exists(\Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle::class)) {
            $bundles[] = new \Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle();
        }

        if (\class_exists(\Symfony\Bundle\MonologBundle\MonologBundle::class)) {
            $bundles[] = new \Symfony\Bundle\MonologBundle\MonologBundle();
        }

        if (\class_exists(\Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class)) {
            $bundles[] = new \Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
        }

        if (\class_exists(\Massive\Bundle\BuildBundle\MassiveBuildBundle::class)) {
            $bundles[] = new \Massive\Bundle\BuildBundle\MassiveBuildBundle();
        }

        if (\class_exists(\FOS\HttpCacheBundle\FOSHttpCacheBundle::class)) {
            $bundles[] = new \FOS\HttpCacheBundle\FOSHttpCacheBundle();
        }

        if (self::CONTEXT_WEBSITE === $this->getContext()) {
            $bundles[] = new \Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle();
        }

        if (self::CONTEXT_ADMIN === $this->getContext()) {
            $bundles[] = new \Symfony\Bundle\SecurityBundle\SecurityBundle();
        }

        /** @var iterable<mixed, BundleInterface> */
        return $bundles;
    }

    /**
     * Gets the application root dir (path of the project's composer file).
     *
     * @return string The project root dir
     */
    public function getProjectDir(): string
    {
        if (null === $this->projectDir) {
            $r = new \ReflectionObject($this);
            $this->projectDir = \dirname($r->getFileName());
        }

        return $this->projectDir;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(SuluTestBundle::getConfigDir() . '/config.php');

        // we need to resolve the SULU_PHPCR_TRANSPORT environment variable at this point,
        // because the doctrine phpcr configuration is not working with unresolved environment variables
        $loader->load(function(ContainerBuilder $container) {
            $container->setParameter('phpcr.transport', $container->resolveEnvPlaceholders(
                $container->getParameter('phpcr.transport'),
                true
            ));
        });
    }
}
