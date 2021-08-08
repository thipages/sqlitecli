<?php
namespace thipages\sqlitecli;
class Utils {
    /*
     *  returns a two dimensional object array from a ".mode json" result
     */
    public static function toObject($jsonRes) {
        return json_decode(join('',$jsonRes));
    }
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