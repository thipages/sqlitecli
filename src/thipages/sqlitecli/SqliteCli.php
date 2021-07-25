<?php
namespace thipages\sqlitecli;
// todo : add a feature where the database is backuped before performing create or alter staements
// it could be a secure execute like executeWithBackup function
class SqliteCli {
    private $dbPath;
    // todo : add a second $option->fkOn=true argument adding by default "PRAGMA foreign_keys=on;" to command orders
    public function __construct($dbPath) {
        $this->dbPath=$dbPath;
    }
    // todo : multiple lines orders fail. Need to remove \n from each $orders item;
    // example :
    /*
     'CREATE TABLE contacts (
	contact_id INTEGER PRIMARY KEY,
	first_name TEXT NOT NULL,
	last_name TEXT NOT NULL,
	email TEXT NOT NULL UNIQUE,
	phone TEXT NOT NULL UNIQUE
);'
     */
    private function removeEOL($s) {
        return preg_replace( "/\r|\n/", " ", $s );
    }
    private function cleanOrder($s) {
        if (is_array($s)) {
            $r=[];
            foreach ($s as $item) $r[]=$this->removeEOL($item);
            return $r;
        } else {
            return $this->removeEOL($s);
        }

    }
    public function execute(...$orders) {
        exec(self::getCommand(...$orders), $output, $ret);
        return [$ret===0,$output];
    }
    // todo : check for length command lline limits
    // https://stackoverflow.com/questions/24510707/is-there-any-limit-on-sqlite-query-size
    public function getCommand(...$orders) {
        $orders=self::mergeOrders(...$orders);
        array_push($orders,Orders::quit());
        foreach ($orders as &$order) {
            $order=self::q($order);
            // EOL characters removal needed
            $this->cleanOrder($order);
        }
        array_unshift($orders,'sqlite3',self::q($this->dbPath));
        return join(' ',$orders);
    }
    public function addPrimary($table,$primaryName) {
        $res=$this->execute(".schema $table");
        if ($res[0]) {
            $o = Orders::addPrimary($table, join('', $res[1]), $primaryName);
            $res = $this->execute($o);
        }
        return $res;
    }
    public function addField($table,$definition) {
        $res=$this->execute(".schema $table");
        if ($res[0]) {
            $o = Orders::addField($table, join('', $res[1]), $definition);
            $res = $this->execute($o);
        }
        return $res;
    }
    
    private static function q($s) {
        return "\"$s\"";
    }
    public static function mergeOrders(...$orders) {
        $all=[];
        foreach ($orders as $order) {
            if (is_array($order)) {
                $all=array_merge($all,$order);
            } else {
                array_push($all,$order);
            }
        }
        return $all;
    }
}