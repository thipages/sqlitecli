<?php
namespace thipages\sqlitecli;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * TODO : improve array reduction, see tests [[1,2],[3,4]]-> [1,2,3,4]
 */
class Utils {
    public static function flattenArray($a){
        if (!is_array($a)) return [$a];
        $result = [];
        foreach ($a as $value) {
            $result = array_merge($result, self::flattenArray($value));
        }
        return $result;
    }
    public static function normalizeArray($a) {
        if (!is_array($a)) $a=[$a];
        $fa=self::flattenArray($a);
        $temp=null;
        $na=[];
        foreach ($fa as $item) {
            if (is_callable($item)) {
                if ($temp!==null) {
                    $na[]=$temp;
                    $temp=null;
                }
                $na[]=$item;
            } else {
                if ($temp===null) $temp=[];
                $temp[]=$item;
            }
        }
        if ($temp!==null) $na[]=$temp;
        return $na;
    }
}