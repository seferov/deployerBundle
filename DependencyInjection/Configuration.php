<?php

/*
 * This file is part of the SeferovDeployerBundle package.
 *
 * (c) Farhad Safarov <http://ferhad.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Seferov\DeployerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Seferov\DeployerBundle\DependencyInjection
 * @author Farhad Safarov <http://ferhad.in>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('seferov_deployer');

        $rootNode
            ->children()
                ->arrayNode('servers')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('git')
                                ->cannotBeEmpty()
                                ->isRequired()
                            ->end()
                            ->scalarNode('git_branch')
                                ->defaultValue('master')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('commands')
                                ->children()
                                    ->variableNode('before_install')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('connection')
                                ->children()
                                    ->scalarNode('host')->isRequired()->end()
                                    ->integerNode('port')->defaultValue(22)->end()
                                    ->scalarNode('username')->defaultValue('root')->end()
                                    ->scalarNode('password')->end()
                                    ->scalarNode('path')->defaultValue('/var/www')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
