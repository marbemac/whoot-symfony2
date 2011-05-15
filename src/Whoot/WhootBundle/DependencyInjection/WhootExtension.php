<?php
/**
 * User: Marc MacLeod
 * Date: 5/8/11
 * Time: 10:40 PM
 */
 
namespace Whoot\WhootBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class WhootExtension extends Extension {

    public function load(array $config, ContainerBuilder $container) {
        $definition = new Definition('Whoot\WhootBundle\Extension\WhootTwigExtension');
        $definition->addTag('twig.extension');
        $container->setDefinition('whoot_twig_extension', $definition);
    }
}