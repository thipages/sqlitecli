<?php
namespace thipages\sqlitecli;
class Registry {
    private $reg=[];
    public function set($key,$res){
        $this->reg[$key]=$res;
    }
    public function clear($key=null) {
        if ($key===null) $reg=[];
        else unset($this->reg[$key]);
    }
    public function get($key=null){
        return ($key===null)
            ? $this->reg
            : $this->reg[$key];
    }
}