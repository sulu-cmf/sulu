<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\DependencyInjection;

use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountRepository;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal this is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_contact');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('phoneType')->defaultValue('1')->end()
                        ->scalarNode('phoneTypeMobile')->defaultValue('3')->end()
                        ->scalarNode('phoneTypeIsdn')->defaultValue('1')->end()
                        ->scalarNode('emailType')->defaultValue('1')->end()
                        ->scalarNode('addressType')->defaultValue('1')->end()
                        ->scalarNode('urlType')->defaultValue('1')->end()
                        ->scalarNode('faxType')->defaultValue('1')->end()
                        ->scalarNode('socialMediaProfileType')->defaultValue('1')->end()
                        ->scalarNode('country')->defaultValue('AT')->end()
                    ->end()
                ->end()
                ->arrayNode('form')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('contact')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('category_root')->defaultValue(null)->end()
                            ->end()
                        ->end()
                        ->arrayNode('account')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('category_root')->defaultValue(null)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('form_of_address')
                    ->useAttributeAsKey('title')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('id')->end()
                            ->scalarNode('name')->end()
                            ->scalarNode('translation')->end()
                        ->end()
                    ->end()
                    ->defaultValue([
                        'male' => ['id' => 0, 'name' => 'male', 'translation' => 'contact.contacts.formOfAddress.male'],
                        'female' => ['id' => 1, 'name' => 'female', 'translation' => 'contact.contacts.formOfAddress.female'],
                    ])
                ->end()
            ->end();

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds `objects` section.
     */
    private function addObjectsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('contact')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Contact::class)->end()
                                ->scalarNode('repository')->defaultValue(ContactRepository::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('account')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Account::class)->end()
                                ->scalarNode('repository')->defaultValue(AccountRepository::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
