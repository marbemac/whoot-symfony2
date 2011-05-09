<?php
/**
 * User: Marc MacLeod
 * Date: 5/8/11
 * Time: 10:20 PM
 */
 
namespace Socialite\SocialiteBundle\Extension;

class SocialiteTwigExtension extends \Twig_Extension {

    private $postTypes = array(
        'working' => 'working tonight',
        'low_in'  => 'staying in tonight',
        'low_out' => 'having a relaxed night out',
        'big_out' => 'having a big night out'
    );

    public function getFilters() {
        return array(
            'var_dump'       => new \Twig_Filter_Function('var_dump'),
            'printPostType'  => new \Twig_Filter_Method($this, 'printPostType'),
        );
    }

    public function printPostType($key)
    {
        return $this->postTypes[$key];
    }

    public function getName()
    {
        return 'socialite_twig_extension';
    }

}