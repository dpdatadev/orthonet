<?php

/** @noinspection ALL */

declare(strict_types=1);

$params = parse_ini_file('database-pg.ini');
$dsnString = sprintf(
    "pgsql:host=%s;port=%d;dbname=%s;",
    $params['host'],
    $params['port'],
    $params['database']
);
$user = $params['user'];
$pass = $params['password'];

//DEFAULT DB - Postgres
return [
    'postgres' => [
        'dsn' => $dsnString,
        'username' => $user,
        'password' => $pass,
        'opts' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ]
];

//SQLITE3

//MYSQL
