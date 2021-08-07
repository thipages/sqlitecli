<?php
require('./../src/thipages/sqlitecli/SqliteCli.php');
require('./../src/thipages/sqlitecli/Orders.php');
require('./../src/thipages/sqlitecli/Utils.php');
require('./../src/thipages/sqlitecli/Registry.php');
use thipages\sqlitecli\Orders;
use thipages\sqlitecli\SqliteCli;
use thipages\sqlitecli\Utils;
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
        [1,2, $f, [3, 4, $f, 5, 6]],
        [[1,2],[3,4]]
    ];
    $expected=[
        [[1]],
        [[1]],
        [[1,2],'A',[3,4],'A',[5,6]],
        [[1,2,3,4]]
    ];
    $valid=[];
    for ($i = 0; $i<count($test); $i++) {
        $res = Utils::normalizeArray($test[$i]);
        foreach ($res as &$v) {
            if (is_callable($v)) $v = $v();
        }
        $valid[]=json_encode($res)===json_encode($expected[$i]) ?'ok':'nok';
    }
    echo(__FUNCTION__.':'.join(' ', $valid)."\n");
}
function addField() {
    global $dbName, $table;
    $cli=new SqliteCli($dbName);
    $o=Orders::importCsv($table,'addresses.csv',",","on");
    $res=$cli->execute(
        $o,
        Orders::addField($table,'new_field TEXT')
    );
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
    $res=$cli->execute(
        $o,
        Orders::addPrimary($table,'id')
    );
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
            Orders::exportCsv('select id,name from simple;','data_bis.csv')
        ]
    );
    return [
        __FUNCTION__,
        compare('data_bis.csv')
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
function test_mergeCsv() {
    global $dbName;
    $cli=new SqliteCli($dbName);
    $res=$cli->execute(
        [
            Orders::mergeCsvList('merged',['./data.csv','./data2.csv'],','),
            "SELECT count(*) from merged;"
        ]
    );
    //echo(is_string($res[1][0]) ?'___s':'___ns');
    // todo : issue : count(*) is a string not a number!?
    return [
        __FUNCTION__,
        (int)$res[1][0]===6
    ];
}
function test_registry() {
    global $dbName, $createTable_simple;
    $cli=new SqliteCli($dbName);
    $reg=$cli->getRegistry();
    $res=$cli->execute(
        [
            $createTable_simple,
            "SELECT count(*) from simple;",
            $reg->set('A')
        ]
    );
    return [
        __FUNCTION__,
        (int)$reg->get('A')[0]===3
    ];
}
function compare($csv) {
    return str_replace("\r","",file_get_contents($csv))=="id,name\n1,Paul\n2,Jack\n3,Charlie\n";
}
// MAIN
$tests =[];
$dbName="test.db";
$table="addresses_table";
$up='./up/'.$dbName;
$createTable_simple= [
    "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
    "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');"
];
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
$tests[]=test_mergeCsv();
unlinkDB();
$tests[]=test_registry();
foreach($tests as $t) {
    $s=[$t[0], $t[1]?'ok':'NOK'];
    echo (join(' : ',$s)."\n");
}