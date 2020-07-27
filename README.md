# sqlitecli
SQLite CLI wrapper

## Installation
**composer** require thipages\sqlitecli

## Usage
```php
// Export to CSV
$cli=new SqliteCli('./database.db');
$res=$cli->execute(
    "CREATE TABLE simple (id INTEGER PRIMARY KEY, name);",
    "INSERT INTO simple (name) VALUES ('Paul'), ('Jack'),('Charlie');",
    '.mode csv',
    '.headers on',
    '.separator ,',
    '.output data.csv',
    'select id,name from simple;'
);

OR

$res=$cli->execute(Orders::exportCsv('data.csv'), 'select id,name from simple;');
```

## API

**SqliteCli class**
###### Constructor
`SqliteCli($dbPath)`
###### Methods
`execute(...$orders):[boolean,array]` executes sqlite commands (list of [array of] commands)
`addPrimary($table,$primaryName):[boolean,array]` adds a primary field to an existing table

Note : `execute` method includes a final `.quit` command

**Orders class**
###### Static methods
`importCsv($table, $csvPath, $separator=',', $headers='on'):array` returns an array of commands for csv import
`exportCsv($csvPath, $separator=',', $headers='on'):array` returns an array of commands for csv export