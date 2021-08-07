<?php
namespace thipages\sqlitecli;
class Utils {
    private static function _subArrays($a, &$res, &$temp){
        $temp = [];
        if (!is_array($a)) {
            if (is_callable($a)) {
                $res[] = $a;
            } else {
                $temp[] = $a;
            }
        } else {
            foreach ($a as $item) {
                if (is_callable($item)) {
                    if (count($temp) <> 0) {
                        $res[] = $temp;
                        $temp = [];
                    }
                    $res[] = $item;
                } else if (is_array($item)) {
                    if (count($temp) <> 0) {
                        $res[] = $temp;
                        $temp = [];
                    }
                    self::_subArrays($item, $res, $temp);
                } else {
                    $temp[] = $item;
                }
            }
        }
    }
    public static function subArrays($a) {
        $res = [];
        $temp = [];
        self::_subArrays($a, $res, $temp);
        if (count($temp) <> 0) $res[] = $temp;
        return $res;
    }
}