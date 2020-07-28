<?php
require('./../src/thipages/sqlitecli/SqliteCli.php');
require('./../src/thipages/sqlitecli/Orders.php');
use thipages\sqlitecli\Orders;
use thipages\sqlitecli\SqliteCli;
$tests =[];
$dbName="test.db";
$table="addresses_table";
if (file_exists($dbName)) unlink($dbName);
if (file_exists('./up/'.$dbName)) unlink('./up/'.$dbName);
// add Primary
$cli=new SqliteCli($dbName);
$o=Orders::importCsv($table,'addresses.csv',",","on");
$res=$cli->execute($o);
$res=$cli->addPrimary($table,'id');
$res=$cli->execute("select id from $table");
$tests[]= [
    'addPrimary',
    ($res[0] && $res[1]==[1,2,3,4,5,6])
];
// export csv manually
$res=$cli->execute(
    "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
    "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
    '.mode csv',
    '.headers on',
    '.separator ,',
    '.output data.csv',
    'select id,name from simple;'
);
$tests[]= [
    'export csv1',
    compare('data.csv')
]; 
// export csv through the export csv method
$res=$cli->execute(Orders::exportCsv('data2.csv'), 'select id,name from simple;');
$tests[]= [
    'export csv2',
    compare('data2.csv')
];
// export in upper folder
$cli=new SqliteCli('./up/'.$dbName);
$res=$cli->execute(
    "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
    "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
    '.mode csv',
    '.headers on',
    '.separator ,',
    '.output ./up/data.csv',
    'select id,name from simple;'
);
$tests[]= [
    'export csv in up folder',
    compare('./up/data.csv')
];

//
foreach($tests as $t) {
    $s=[$t[0], $t[1]?'ok':'nok'];
    echo (join(' : ',$s)."\n");
}

function compare($csv) {
    return str_replace("\r","",file_get_contents($csv))=="id,name\n1,Paul\n2,Jack\n3,Charlie\n";
}