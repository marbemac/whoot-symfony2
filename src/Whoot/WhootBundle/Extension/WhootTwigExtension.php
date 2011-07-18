<?php
/**
 * User: Marc MacLeod
 * Date: 5/8/11
 * Time: 10:20 PM
 */
 
namespace Whoot\WhootBundle\Extension;

class WhootTwigExtension extends \Twig_Extension {

    private $postTypes = array(
        'working' => '<span>working</span> tonight',
        'low_in'  => '<span>staying in</span> tonight',
        'low_out' => '<span>relaxing out</span> tonight',
        'big_out' => '<span>raging</span> tonight'
    );

    private $postTypesShort = array(
        'working' => 'Working',
        'low_in' => 'Staying In',
        'low_out' => 'Relaxing Out',
        'big_out' => 'Raging'
    );

    private $postTypesNoun = array(
        'working' => 'work',
        'low_in' => 'stay in',
        'low_out' => 'relax',
        'big_out' => 'rage'
    );

    public function getFilters() {
        return array(
            'debug'       => new \Twig_Filter_Method($this, 'debug'),
            'gethostname'       => new \Twig_Filter_Method($this, 'gethostname'),
            'printPostType'  => new \Twig_Filter_Method($this, 'printPostType'),
            'printPostTypeShort'  => new \Twig_Filter_Method($this, 'printPostTypeShort'),
            'printPostTypeNoun'  => new \Twig_Filter_Method($this, 'printPostTypeNoun'),
            'timeLapse'  => new \Twig_Filter_Method($this, 'timeLapse'),
            'stripSlashes'  => new \Twig_Filter_Method($this, 'stripSlashes'),
            'truncate'  => new \Twig_Filter_Method($this, 'truncate'),
            'conditionalLength'  => new \Twig_Filter_Method($this, 'conditionalLength'),
            'rebuildArray'  => new \Twig_Filter_Method($this, 'rebuildArray'),
            'countStatus'  => new \Twig_Filter_Method($this, 'countStatus'),
            'round'  => new \Twig_Filter_Method($this, 'round'),
            'json_encode'  => new \Twig_Filter_Method($this, 'json_encode'),
            'json_decode'  => new \Twig_Filter_Method($this, 'json_decode'),
        );
    }

    public function getFunctions() {
        return array(
            'calculateTimeOfDay'  => new \Twig_Function_Method($this, 'calculateTimeOfDay')
        );
    }

    public function debug($var)
    {
        $return = '<pre>';
        $return .= var_export($var, true);
        $return .= '</pre>';
        return $return;
    }

    public function gethostname()
    {
        return gethostname();
    }

    public function printPostType($key)
    {
        return $this->postTypes[$key];
    }

    public function printPostTypeShort($key)
    {
        return $this->postTypesShort[$key];
    }

    public function printPostTypeNoun($key)
    {
        return $this->postTypesNoun[$key];
    }

    public function stripSlashes($val)
    {
        return stripslashes($val);
    }

    public function truncate($val, $length, $append='...')
    {
        if (strlen($val) > $length)
        {
            $val = substr($val, 0, $length);
            $val .= $append;
        }

        return $val;
    }

    /**
     * Loop through an array and get the count of elements that match given $key => $val
     *
     * @param array $array
     * @param string $key
     * @param string $val
     *
     * @return int $count
     */
    public function conditionalLength($array, $key, $val)
    {
        $count = 0;
        foreach ($array as $item)
        {
            if ($item[$key] == $val)
                $count++;
        }
        return $count;
    }

    /**
     * Rebuild an array with just the elements that match $key => $val
     *
     * @param array $array
     * @param sring $key
     * @param string $val
     *
     * @return array $newArray
     */
    public function rebuildArray($array, $key, $val)
    {
        $newArray = array();
        foreach ($array as $item)
        {
            if ($item[$key] == $val)
                $newArray[] = $item;
        }
        return $newArray;
    }

    /**
     * Given an array of structure posts['post'], see how many posts/users have the given status.
     *
     * @param  array $array
     * @param  string $status
     *
     * @return void
     */
    public function countStatus($array, $status)
    {
        $count = 0;
        foreach ($array as $post)
        {
            $post = $post[0];

            if ($post['type'] == $status)
            {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Simple round function
     *
     * @param  $precision
     * @return $rounded
     */
    public function round($val, $precision)
    {
        return round($val, $precision);
    }

    /**
     * encode and return a json string
     *
     * @param  array $object
     * @return string
     */
    public function json_encode($object)
    {
        return json_encode($object);
    }

    /**
     * Decode and return a json string
     *
     * @param  string $val
     * @return object
     */
    public function json_decode($val)
    {
        return json_decode($val);
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

    /*
     * Returns a string corresponding to the time of day.
     * This is used in the body tag to change the site background gradient.
     */
    public function calculateTimeOfDay()
    {
        $hour = date('G', time());
        switch (true) {
            case ($hour >= 5 && $hour < 18):
                return 'day';
            case ($hour >= 18 && $hour < 20):
                return 'sunset';
            case ($hour >= 20 || $hour < 5):
                return 'night';
        }
    }

    public function getName()
    {
        return 'whoot_twig_extension';
    }

}