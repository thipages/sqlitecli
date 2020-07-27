<?php
namespace thipages\sqlitecli;
class SqliteCli {
    private $dbPath;
    public function __construct($dbPath) {
        $this->dbPath=$dbPath;
    }
    public function execute(...$orders) {
        exec(self::getCommand(...$orders), $output, $ret);
        return [$ret===0,$output];
    }
    public function getCommand(...$orders) {
        $orders=self::mergeOrders(...$orders);
        array_push($orders,Orders::quit());
        foreach ($orders as &$order) $order=self::q($order);
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