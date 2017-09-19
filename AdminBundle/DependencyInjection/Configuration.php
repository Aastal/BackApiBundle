<?php

namespace Geoks\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('geoks_admin');

        $rootNode
            ->children()
                ->scalarNode('local_bundle')
                    ->defaultValue('AdminBundle')
                ->end()
                ->scalarNode('app_name')
                    ->defaultValue('Geoks')
                ->end()
                ->arrayNode('ban_fields')
                    ->useAttributeAsKey('params')
                        ->prototype('variable')
                    ->end()
                ->end()
                ->arrayNode('multiple_fields')
                    ->useAttributeAsKey('params')
                        ->prototype('variable')
                    ->end()
                ->end()
                ->arrayNode('import')
                    ->children()
                        ->arrayNode('exceptions')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('directory')->isRequired()->end()
                                    ->arrayNode('classes')
                                        ->useAttributeAsKey('params')
                                            ->prototype('variable')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
