<?php

namespace Geoks\AdminBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GeoksAdminExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('geoks_admin.local_bundle', $config['local_bundle']);
        $container->setParameter('geoks_admin.app_name', $config['app_name']);

        if (isset($config['ban_fields'])) {
            $container->setParameter('geoks_admin.ban_fields', $config['ban_fields']);
        } else {
            $container->setParameter('geoks_admin.ban_fields', null);
        }

        if (isset($config['import'])) {
            $container->setParameter('geoks_admin.import', $config['import']);
            $container->setParameter('geoks_admin.import.directories', $config['import']['directories']);
        } else {
            $container->setParameter('geoks_admin.import', null);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}