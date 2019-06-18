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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('kalamu_dynamique_config');

        $rootNode
                ->children()
                    ->scalarNode('base_configurator_template')
                        ->defaultValue('KalamuDynamiqueConfigBundle:DynamiqueConfigurator:base.html.twig')
                        ->info("Template for configuration page")
                    ->end()
                    ->arrayNode('configurator')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('label')
                                    ->isRequired()
                                    ->info("Display name of the configurator")
                                ->end()
                                ->scalarNode('controller')
                                    ->isRequired()
                                    ->info("Controller that handle this configurator")
                                ->end()
                                ->integerNode('priority')
                                    ->defaultValue(50)
                                    ->info("Priority order. The lower is first.")
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
