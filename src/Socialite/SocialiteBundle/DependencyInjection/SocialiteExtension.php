<?php
/**
 * User: Marc MacLeod
 * Date: 5/8/11
 * Time: 10:40 PM
 */
 
namespace Socialite\SocialiteBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SocialiteExtension extends Extension {

    public function load(array $config, ContainerBuilder $container) {
        $definition = new Definition('Socialite\SocialiteBundle\Extension\SocialiteTwigExtension');
        $definition->addTag('twig.extension');
        $container->setDefinition('socialite_twig_extension', $definition);
    }
}