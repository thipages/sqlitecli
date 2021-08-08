<?php
namespace thipages\sqlitecli;
/*
 * todo ( getSchema )
 * - getSchema(): <field,structure> +helpers in util, eg map(field,aProperty>
 * - analyze ($level), for pattern/counting/distinct
 * - setFieldType(obj) for setting field types manually
 */
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
    // todo : implement field positionning?
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
    private static function _insert ($sourceDb, $targetDb) {
        return function ($res) use ($sourceDb, $targetDb) {
            $propList=join(',',$res);
            return "INSERT INTO '$targetDb' ($propList) select $propList from '$sourceDb';";
        };
    }
    private static function _insert_smart ($sourceDb, $targetDb,$key) {
        return function ($res,$registry) use ($key,$sourceDb, $targetDb) {
            $propList=join(',',$registry->get($key));
            return "INSERT INTO '$targetDb' ($propList) select $propList from '$sourceDb';";
        };
    }
    // todo : add a parameter to allow fields mapping between csv?
    // todo : add option : merging all fields (the current case) or to get the common set
    public static function mergeCsvList($table,$csvPaths, $delimiter=','){
        $tableIds=[];$orders=[];$delimiters=[];
        $namespace=__FUNCTION__;
        $KEYS=['INTER', 'DIFF'];
        if (is_string($delimiter)) {
            foreach ($csvPaths as $path) $delimiters[]=$delimiter;
        } else {
            $delimiters=$delimiter;
        }
        // Create n-1 temp tables over $table (ref table)
        for ($i=0;$i<count($csvPaths);$i++) {
            $tableIds[]= $i===0?$table:uniqid('temp_');
            $orders[]=Orders::importCsv($tableIds[$i],$csvPaths[$i],$delimiters[$i]);
            $orders[]=self::getFieldList($tableIds[$i]);//($namespace.':'.$tableIds[$i]);
            $orders[]=self::registerAs($tableIds[$i]);
        }
        // Identify the common fields by name
        $orders[]=function ($res,$registry) use ($namespace,$KEYS,$tableIds) {
            $refFields=$registry->get($tableIds[0]);//getNS($namespace)($tableIds[0]);
            $temp=$refFields;
            for ($i=1;$i<count($tableIds);$i++){
                $temp=array_intersect($temp,$registry->get($tableIds[$i]));//$registry->getNS($namespace)($tableIds[$i]));
            }
            $registry->set($KEYS[0],$temp);
            $registry->set($KEYS[1],array_diff($refFields,$temp));
        };
        $orders[]=function ($res,$registry) use ($namespace,$table,$KEYS) {
            $q=[];
            // todo : alternative to DROP COLUMN : not available before v3.35.0
            foreach($registry->get($KEYS[1]) as $field) {
                $q[]="ALTER TABLE '$table' DROP COLUMN $field;";
            }
            return $q;
        };
        for ($i=1;$i<count($csvPaths);$i++) {
            $orders[]=self::_insert_smart($tableIds[$i],$tableIds[0],$KEYS[0]);
        }
        $list=$tableIds;
        $list[]=$KEYS[0];$list[]=$KEYS[1];
        // todo : implement namespace
        foreach($list as $el) $orders[]=self::unregister($el);
        return $orders;
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
    public static function getFieldList($table, $registerKey=null) {
        return [
            '.headers off',
            "SELECT name FROM PRAGMA_TABLE_INFO('$table');"
        ];
    }
    public static function registerAs($key) {//,$namespace=''){
        return function ($res, $registry) use($key) {
            $registry->set($key,$res);
        };
    }
    public static function unregister(...$keys){
        $fKeys=Utils::flattenArray($keys);
        return function ($res, $registry) use($fKeys) {
            foreach($fKeys as $key) $registry->clear($key);
        };
    }
    /*public static function unregisterNS($namespace){
        return function ($res, $registry) use($namespace) {
            $registry->clearNS($namespace);
        };
    }*/
}