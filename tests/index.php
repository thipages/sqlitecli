<?php
require('./../src/thipages/sqlitecli/SqliteCli.php');
require('./../src/thipages/sqlitecli/Orders.php');
require('./../src/thipages/sqlitecli/Utils.php');
use thipages\sqlitecli\Orders;
use thipages\sqlitecli\SqliteCli;
use thipages\sqlitecli\Utils;
// todo : add a test for mergeCsvList
$tests =[];
$dbName="test.db";
$table="addresses_table";
$up='./up/'.$dbName;
function unlinkDB() {
    global $dbName,$up;
    if (file_exists($dbName)) unlink($dbName);
    if (file_exists($up)) unlink($up);
}
function test_subArrays() {
    $f = function () {return 'A';};
    $test = [
        1,
        [1],
        [1,2, $f, [3, 4, $f, 4, 5]]
    ];
    $expected=[
        [[1]],
        [[1]],
        [[1,2],'A',[3,4],'A',[4,5]]
    ];
    $valid=[];
    for ($i = 0; $i<count($test); $i++) {
        $res = Utils::subArrays($test[$i]);
        array_walk_recursive($res,
            function(&$v) {
                if (is_callable($v)) $v = $v();
            }
        );
        $valid[]=json_encode($res)===json_encode($expected[$i]) ?'ok':'nok';
    }
    echo(__FUNCTION__.':'.join(' ', $valid)."\n");
}
function addField() {
    global $dbName, $table;
    $cli=new SqliteCli($dbName);
    $o=Orders::importCsv($table,'addresses.csv',",","on");
    $res=$cli->execute($o);
    $res=$cli->addField($table,'new_field TEXT');
    $res=$cli->execute("UPDATE $table SET new_field='foo' ");
    $res=$cli->execute("select new_field from $table");
    return [
        __FUNCTION__,
        ($res[0] && $res[1]==['foo','foo','foo','foo','foo','foo'])
    ];    
}
function addPrimary() {
    global $dbName, $table;
    $cli=new SqliteCli($dbName);
    $o=Orders::importCsv($table,'addresses.csv',",","on");
    $res=$cli->execute($o);
    $res=$cli->addPrimary($table,'id');
    $res=$cli->execute("select id from $table");
    return [
        __FUNCTION__,
        ($res[0] && $res[1]==[1,2,3,4,5,6])
    ];    
}
function csvManualExport() {
    global $dbName;
    $cli=new SqliteCli($dbName);
    $res=$cli->execute(
        "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
        "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
        '.mode csv',
        '.headers on',
        '.separator ,',
        '.output data.csv',
        'select id,name from simple;'
    );
    return [
        __FUNCTION__,
        compare('data.csv')
    ];    
}
function csvAPIExport() {
    global $dbName;
    $cli=new SqliteCli($dbName);
    $res=$cli->execute(
        [
            "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
            "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
            Orders::exportCsv('select id,name from simple;','data2.csv')
        ]
    );
    return [
        __FUNCTION__,
        compare('data2.csv')
    ];
}
function csvUpperFolderExport() {
    global $dbName;
    $cli = new SqliteCli($dbName);
    $res=$cli->execute(
        "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
        "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
        '.mode csv',
        '.headers on',
        '.separator ,',
        '.output ./up/data.csv',
        'select id,name from simple;'
    );
    return [
        __FUNCTION__,
        compare('./up/data.csv')
    ];

}
function test_function() {
    global $dbName;
    $cli=new SqliteCli($dbName);
    $res=$cli->execute(
        [
            "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
            "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
            "SELECT name from simple where id=1;",
            function ($res) {
                return "UPDATE simple SET name='$res[0]'";
            },
            "SELECT name from simple;"
        ]
    );
    return [
        __FUNCTION__,
        join('',$res[1])==='PaulPaulPaul'
    ];
}
function test_chainedFunctions() {
    global $dbName;
    $cli=new SqliteCli($dbName);
    $res=$cli->execute(
        [
            "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
            "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
            "SELECT name from simple where id=1;",
            function ($res) {
                return "UPDATE simple SET name='$res[0]'";
            },
            "SELECT id from simple where id=1;",
            function ($res) {
                return "UPDATE simple SET name='$res[0]'";
            },
            "SELECT name from simple;",
        ]
    );
    return [
        __FUNCTION__,
        join('',$res[1])==='111'
    ];
}
function compare($csv) {
    return str_replace("\r","",file_get_contents($csv))=="id,name\n1,Paul\n2,Jack\n3,Charlie\n";
}

test_subArrays();
unlinkDB();
$tests[]=addField();
unlinkDB();
$tests[]=addPrimary();
$tests[]=csvManualExport();
unlinkDB();
$tests[]=csvAPIExport();
unlinkDB();
$tests[]=csvUpperFolderExport();
unlinkDB();
$tests[]=test_function();
unlinkDB();
$tests[]=test_chainedFunctions();
foreach($tests as $t) {
    $s=[$t[0], $t[1]?'ok':'nok'];
    echo (join(' : ',$s)."\n");
}