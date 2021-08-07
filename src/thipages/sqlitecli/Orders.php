<?php
namespace thipages\sqlitecli;
class Orders {
    public static function importCsv($table, $csvPath, $separator=',', $headers='on') {
        return [[
            ".mode csv",
            ".separator $separator",
            ".headers $headers",
            ".import $csvPath $table",
            ".mode list" // set back to default mode
        ]];
    }
    public static function exportCsv($sql,$csvPath, $separator=',', $headers='on') {
        return [
            ".mode csv",
            ".separator $separator",
            ".headers $headers",
            ".output $csvPath",
            $sql,
            ".mode list" // set back to default mode
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
    // todo : evaluate the integration of https://github.com/maghead/sqlite-parser
    public static function parseSchema($schema) {
        $temp=explode('(',$schema);
        $tab1=array_slice($temp,0,1);
        $tab2=explode(',',array_slice($temp,1));
    }
    // todo : implement field positionning
    private static function _addField($table, $schema, $definition, $position=0,$prefix='old_') {
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
    private static function _addPrimary($table, $schema, $name, $prefix='old_') {
        return self::_addField($table,$schema,"$name INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL", $prefix);
    }
    public static function mergeCsvList($table,$csvPaths, $delimiter=','){
        if (is_string($delimiter)) {
            foreach ($csvPaths as $path) $delimiters[]=$delimiter;
        } else {
            $delimiters=$delimiter;
        }
        $tids=[];
        $orders=[];
        for ($i=0;$i<count($csvPaths);$i++) {
            $tids[]= ($i===0) ? $table : uniqid('temp_');
            $orders[]=Orders::importCsv($tids[$i],$csvPaths[$i],$delimiters[$i]);
        }
        //$orders[]='.headers off';
        //$orders[]='.mode list';
        for ($i=1;$i<count($csvPaths);$i++) {
            //$orders[]="SELECT name FROM PRAGMA_TABLE_INFO('$table');";
            $orders[]=self::getFieldList($table);
            $orders[]=self::insert($tids[$i], $table);
        }
        for ($i=1;$i<count($csvPaths);$i++) {
            $orders[]="DROP TABLE $tids[$i];";
        }
        $orders[]="PRAGMA auto_vacuum = FULL;VACUUM;";
        return $orders;
    }
    private static function insert ($sourceDb, $targetDb) {
        return function ($res) use ($sourceDb, $targetDb) {
            $propList=join(',',$res);
            return "INSERT INTO '$targetDb' ($propList) select $propList from '$sourceDb';";
        };
    }
    public static function addPrimary($table,$primaryName) {
        return
            [
                ".schema $table",
                function ($res) use ($table, $primaryName) {
                    return self::_addPrimary($table, join('', $res), $primaryName);
                }
            ];
    }
    public static function addField($table,$definition) {
        return [

            ".schema $table",
            function ($res) use ($table, $definition) {
                return self::_addField($table, join('', $res), $definition);
            }

        ];
    }
    public static function getFieldList($table) {
        return [
            '.headers off',
            "SELECT name FROM PRAGMA_TABLE_INFO('$table');"
        ];
    }
}