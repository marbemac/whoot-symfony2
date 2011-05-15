<?php
/**
 * User: Marc MacLeod
 * Date: 5/8/11
 * Time: 10:20 PM
 */
 
namespace Whoot\WhootBundle\Extension;

class WhootTwigExtension extends \Twig_Extension {

    private $postTypes = array(
        'working' => 'working tonight',
        'low_in'  => 'staying in tonight',
        'low_out' => 'having a relaxed night out',
        'big_out' => 'having a big night out'
    );

    public function getFilters() {
        return array(
            'debug'       => new \Twig_Filter_Method($this, 'debug'),
            'printPostType'  => new \Twig_Filter_Method($this, 'printPostType'),
        );
    }

    public function debug($var)
    {
        $return = '<pre>';
        $return .= var_export($var, true);
        $return .= '</pre>';
        return $return;
    }

    public function printPostType($key)
    {
        return $this->postTypes[$key];
    }

    public function getName()
    {
        return 'whoot_twig_extension';
    }

}