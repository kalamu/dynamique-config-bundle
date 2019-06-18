<?php

/*
 * This file is part of the kalamu/dynamique-config-bundle package.
 *
 * (c) ETIC Services
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kalamu\DynamiqueConfigBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class KalamuDynamiqueConfigExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $configurators = $config['configurator'];
        uksort($configurators, function($var1, $var2) use ($configurators){
            return $configurators[$var1]['priority'] > $configurators[$var2]['priority'];
        });
        $configurators['_export_config'] = array(
            'label'         => '<i class="fa fa-exchange fa-fw"></i> Import/Export',
            'controller'    => 'KalamuDynamiqueConfigBundle:DynamiqueConfigurator:importExport',
            'priority'      => 1000
        );

        $container->setParameter('kalamu_dynamique_config.configurators', $configurators);
        $container->setParameter('kalamu_dynamique_config.base_template', $config['base_configurator_template']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
