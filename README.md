# sqlitecli
SQLite CLI wrapper

## Installation
**composer** require thipages\sqlitecli

## Usage
```php
/* index.php */
/* ********* */
$cli=new SqliteCli('./database.db');
$cli->execute(
    "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
    "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
    '.mode csv',
    '.headers on',
    '.separator ,',
    '.output data.csv',
    'select id,name from simple limit 2;'
);
// OR
$cli-<execute(
    Orders::importCsv('simple','data.csv', ',', true),
    Orders::exportCsv('data.csv', 'select id,name from simple limit 2;')
);


```
```php
// run it on server
php index.php
```

## Advanced usage
```php
/* index.php */
/* ********* */
$cli=new SqliteCli('./database.db');
$cli->execute(
    "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
    "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
    "SELECT name FROM simple WHERE id=1;",
    function($res) {
        // Set 'Paul' to all records
        return "UPDATE simple SET name='$res'";
    }
);
```
## API

**SqliteCli class**
###### Constructor
`SqliteCli($dbPath)`
###### Methods
`execute(...$orders):[boolean,array]` executes sqlite commands (list of [array of] commands). This method adds a final `.quit` command

**Orders class**
###### Static methods
`addPrimary($table,$primaryName):[boolean,array]` adds a primary field to an existing table (first position)

`addField($table,$definition):[boolean,array]` adds a field to an existing table (first position)

`importCsv($table, $csvPath, $separator=',', $headers='on'):array` returns an array of commands for csv import

`exportCsv($csvPath, $separator=',', $headers='on'):array` returns an array of commands for csv export

`mergeCsvList($table,$csvPaths, $delimiter=',')` merges csv Files into `$table`. Files need to have the same fields. `$delimiter` can be an array matching `$csvPaths`