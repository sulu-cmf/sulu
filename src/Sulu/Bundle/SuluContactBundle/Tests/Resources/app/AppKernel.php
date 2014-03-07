<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // Dependencies
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            // Sulu
            new \Sulu\Bundle\AdminBundle\SuluAdminBundle(),
            new \Sulu\Bundle\CoreBundle\SuluCoreBundle(),
            new \Sulu\Bundle\ContactBundle\SuluContactBundle(),
            new \Sulu\Bundle\TestBundle\SuluTestBundle(),
        );

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
var_dump(        file_exists(__DIR__ . '/config/config.' . $GLOBALS['$GLOBALS'] . '.yml'));
        if (array_key_exists('APP_DB', $GLOBALS) &&
            file_exists(__DIR__ . '/config/config.' . $GLOBALS['$GLOBALS'] . '.yml')
        ) {
            $loader->load(__DIR__ . '/config/config.' . $GLOBALS['$GLOBALS'] . '.yml');
        } else {
            $loader->load(__DIR__ . '/config/config.mysql.yml');
        }
    }
}
