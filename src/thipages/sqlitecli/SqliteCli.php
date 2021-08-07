<?php
namespace thipages\sqlitecli;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use thipages\sqlitecli\Registry;

/**
 * TODO : executeWithBackup()
 * Add a feature where the database is backuped before performing create or alter statements
 * It could be through a dedicated secure execute : "executeWithBackup" function
 * database version management could be added through a dedicated class "Backups"
 */
class SqliteCli {
    private $dbPath;
    private $registry;
    /**
     * TODO : add options ( fkOn )
     * add $option["fkOn"]=true, adding by default "PRAGMA foreign_keys=on;" to command orders?
     *        OR/AND
     * add a new Order fkOn($on=true) -> "PRAGMA foreign_keys=on/off;"
     */
    public function __construct($dbPath) {
        $this->dbPath=$dbPath;
        $this->registry=new Registry();
    }
    // multiple lines orders fail. Need to remove \n from each $orders item;
    private static function removeEOL($s) {
        return preg_replace( "/\r|\n/", " ", $s );
    }
    private static function q($s) {
        return "\"$s\"";
    }
    private static function normalize($o) {
        $r= (!is_array($o)) ? [$o]: $o;
        $r=self::flattenArray($r);
        foreach ($r as &$item) $item=self::q(self::removeEOL($item));
        return $r;
    }
    private static function flattenArray($a, $removeDuplicates=false){
        $i= new RecursiveIteratorIterator(new RecursiveArrayIterator($a));
        return iterator_to_array($i, $removeDuplicates);
    }
    private function _execute($order, $args=null) {
        if (is_string($order)) $order = [$order];
        if (is_array($order)) {
            $commands = self::normalize($order);
        } else if (is_callable($order)) {
            $commands = self::normalize($order($args[1], $this->registry));
        }
        array_push($commands, Orders::quit());
        array_unshift($commands, 'sqlite3', self::q($this->dbPath));
        exec(join(' ', $commands), $output, $ret);
        return [$ret === 0, $output];
    }
    // todo : check for length command lline limits
    // https://stackoverflow.com/questions/24510707/is-there-any-limit-on-sqlite-query-size
    public function execute(...$orders) {
        $error=false;
        $res=null;
        $_orders=Utils::subArrays($orders);
        for ($i=0;$i<count($_orders);$i++) {
            if ($i===0) $res=$this->_execute($_orders[0]);
            else $res=$this->_execute($_orders[$i],$res);
            if (!$res[0]) {$error=true;break;}
        }
        if ($i===-1 && $error) {
            echo("SQLITECLI ERROR in command\n");
        } else if ($error) {
            echo("SQLITECLI ERROR in command #$i\n");
            return [false,$i];
        }
        return $res;
    }
    public function getRegistry() {
        return $this->registry;
    }
}