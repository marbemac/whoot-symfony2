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
            'timeLapse'  => new \Twig_Filter_Method($this, 'timeLapse'),
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

    /*
     * Returns a string with the time passed since the given date
     *
     * @param DateTime|timestamp $timestamp
     * @param integer $granularity How specific to get. For example, a granularity of 2 would give 1 day, 3 hours. A granularity of 1 would just give 1 day.
     */
    public function timeLapse($timestamp, $granularity = 2)
    {
        // If we're passing a DateTime object, get the timestamp.
        if (is_object($timestamp) && get_class($timestamp) == 'DateTime')
        {
            $timestamp = $timestamp->getTimestamp();
        }

        $timestamp = time() - $timestamp;
        $units = array('1 year|%d years' => 31536000,
                       '1 week|%d weeks' => 604800,
                       '1 day|%d days' => 86400,
                       '1 hour|%d hours' => 3600,
                       '1 min|%d mins' => 60,
                       '1 sec|%d secs' => 1
        );
        $output = '';
        foreach ($units as $key => $value) {
            $key = explode('|', $key);
            if ($timestamp >= $value) {
                $pluralized = floor($timestamp / $value) > 1 ?
                        sprintf($key[1], floor($timestamp / $value)) :
                        $key[0];
                $output .= ($output ? ' ' : '') . $pluralized;
                $timestamp %= $value;
                $granularity--;
            }
            if ($granularity == 0) {
                break;
            }
        }
        return $output ? $output.' ago' : "Just now";
    }

    public function getName()
    {
        return 'whoot_twig_extension';
    }

}