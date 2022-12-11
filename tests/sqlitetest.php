<?php

/** @noinspection PhpComposerExtensionStubsInspection */
//https://zetcode.com/php/sqlite3/
//$version = SQLite3::version();

//echo $version['versionString'];
//echo date('_y_m_d');

$db = new SQLite3('orthonet_cachedb_' . date('_y_m_d') . '.db');

$db->exec("DROP TABLE IF EXISTS testdata");
$db->exec("CREATE TABLE testdata (id int not null, data varchar(255) null);");
$db->exec("INSERT INTO testdata(id, data)VALUES(1, 'my data')");

$res = $db->query("SELECT * FROM testdata");

while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    echo "ID: {$row['id']} -  Data: {$row['data']} \n";
}
