<?php

namespace Geoks\ApiBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('geoks_api');

        $rootNode
            ->children()
                ->scalarNode('app_bundle')
                        ->defaultValue('AppBundle')
                ->end()
                ->scalarNode('user_class')
                    ->defaultValue('AppBundle:User')
                ->end()
                ->arrayNode('jms_groups')
                    ->useAttributeAsKey('params')
                        ->prototype('variable')
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
