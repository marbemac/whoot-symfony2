<?php

namespace Whoot\WhootBundle\Util;

class ArraySorter
{
    public function __construct()
    {
    }

    public function sortBySubkey($unsorted, $subkey, $sortType = SORT_ASC) {
        $isObject = false;
        // Is the whole thing an object? If so, run through it once and make a new array of it.
        if (is_object($unsorted))
        {
            $tmp = array();
            foreach ($unsorted as $item)
            {
                $tmp[] = $item;
                if (is_object($item))
                {
                    $isObject = true;
                }
            }
            $unsorted = $tmp;
            unset($tmp);
        }

        // Are the items objects? Do we need to access them through getters?
        if ($isObject)
        {
            $method = 'get'.ucfirst($subkey);
            switch($sortType) {
                case SORT_ASC:
                    usort($unsorted, function ($a, $b) use ($method) {
                                        return strcmp($a->$method(), $b->$method());
                                    });
                    break;
                case SORT_DESC:
                    usort($unsorted, function ($a, $b) use ($method) {
                                        return -1 * strcmp($a->$method(), $b->$method());
                                    });
                    break;
            }
        }
        // Else the items are just arrays
        else
        {
            switch($sortType) {
                case SORT_ASC:
                    usort($unsorted, function ($a, $b) use ($subkey) {
                                        strcmp($a[$subkey], $b[$subkey]);
                                    });
                    break;
                case SORT_DESC:
                    usort($unsorted, function ($a, $b) use ($subkey) {
                                        -1 * strcmp($a[$subkey], $b[$subkey]);
                                    });
                    break;
            }
        }

        // Now sorted :)
        return $unsorted;
    }
}