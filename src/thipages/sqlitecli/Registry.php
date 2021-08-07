<?php
namespace thipages\sqlitecli;
class Registry {
    private $reg=[];
    public function set($key){
        return function ($res) use($key) {
            $this->reg[$key]=$res;
        };
    }
    public function get($key) {
        return $this->reg[$key];
    }
}