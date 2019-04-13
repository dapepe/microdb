MicroDB - a minimalistic DB abstraction layer for MySQL and PostgreSQL
======================================================================

Example
-------

Example:

```php
include './lib/microdb/src/connector.php';
include './lib/microdb/src/postgresql.php';

$db = new MicroDB\PostgreSQL('127.0.0.1', 'myuser', 'mypassword', 'mydb');

foreach ($db->tables() as $tableId) {
	echo 'Scanning '.$tableId."\n";
	print_r($db->table($tableId)->fields());
}
```


License
-------

Copyright (C) 2008 - 2014 [Peter Haider](http://about.me/peterhaider)

This work is licensed under the GNU Lesser General Public License (LGPL) which should be included with this software. You may also get a copy of the GNU Lesser General Public License from [http://www.gnu.org/licenses/lgpl.txt](http://www.gnu.org/licenses/lgpl.txt).