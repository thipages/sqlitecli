<?php
namespace thipages\sqlitecli;
class Registry {
    private $reg=[];
    /*
     * todo: add a to2D(boolean) argument
     * - transform $res as a 2D array (through json mode)
     * - use json_decode((join('',$res));
     */
    public function set($key,$res){
        $this->reg[$key]=$res;
    }
    public function clear($key=null) {
        if ($key===null) $this->reg=[];
        else unset($this->reg[$key]);
    }    
    public function get($key=null){
        return ($key===null)
            ? $this->reg
            : $this->reg[$key];
    }
    /*
     * NAMESPACE IMPLEMENTATION
     * confusing so far,see mergeCsvList Order
     */
    //
    /*public function getNS($namespace) {
        return function ($key) use($namespace) {
            return $key===null?$key:"$namespace:$key";
        };
    }
    public function setNS($namespace) {
        return function ($key,$res) use($namespace) {
            $this->reg["$namespace:$key"]=$res;
        };
    }
    public function clearNS($namespace) {
        foreach ($this->reg as $item) {
            if (substr($item,0,strlen($namespace)+2)==="$namespace:")
                unset($this->reg[$item]);
        }
    }*/
}