<?php
namespace thipages\sqlitecli;
class Orders {
    public static function importCsv($table, $csvPath, $separator=',', $headers='on') {
        return [
            ".mode csv",
            ".separator $separator",
            ".headers $headers",
            ".import $csvPath $table"
        ];
    }
    // todo : not working, need to add sql query argument
    public static function exportCsv($csvPath, $separator=',', $headers='on') {
        return [
            ".mode csv",
            ".separator $separator",
            ".headers $headers",
            ".output $csvPath"
        ];
    }
    public static function quit() {
        return '.quit';
    }
    public static function schema() {
        return '.schema';
    }
    public static function getPragma($name, ...$args) {
        if ($args==null) {
            return "PRAGMA $name;";
        } else {
            $j=join(',',$args);
            return "PRAGMA $name($j);";
        }
    }
    public static function setPragma($name, $value) {
        return "PRAGMA $name=$value;";
    }
    public static function addPrimary($table, $schema, $name, $prefix='old_') {
        return self::addField($table,$schema,"$name INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL", $prefix);
        
    }
    // todo : evaluate the integration of https://github.com/maghead/sqlite-parser
    public static function parseSchema($schema) {
        $temp=explode('(',$schema);
        $tab1=array_slice($temp,0,1);
        $tab2=explode(',',array_slice($temp,1));
    }
    // todo : implement field positionning
    public static function addField($table, $schema, $definition, $position=0,$prefix='old_') {
        $old="$prefix$table";
        // puts quotes around name + add comma (first field);
        $def_array=explode(' ',trim($definition));
        $def_array[0]='"'.$def_array[0].'"';
        $def=join(' ',$def_array).',';
        $create=explode('(',$schema);
        $tab1=array_slice($create,0,1);
        $tab2=array_slice($create,1);
        $tab2[0]=$def.$tab2[0];
        $create=join(
            '(',
            array_merge($tab1, $tab2)
        );
        return join('',[
            "PRAGMA foreign_keys=off;",
            "BEGIN TRANSACTION;",
            "ALTER TABLE $table RENAME TO $old;",
            $create,
            "INSERT INTO $table SELECT null,* FROM $old;",
            "COMMIT;",
            "PRAGMA foreign_keys=on;",
            "DROP TABLE $old;"
        ]);
    }

}