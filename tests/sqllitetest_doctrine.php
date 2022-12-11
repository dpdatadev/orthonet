<?php

require_once "vendor/autoload.php";

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\FetchMode;

$attrs = ['driver' => 'pdo_sqlite', 'path' => 'orthonet_cachedb__22_12_10.db'];

try {
    $conn = DriverManager::getConnection($attrs);

    $queryBuilder = $conn->createQueryBuilder();
    $queryBuilder->select('*')->from('testdata');

    $stm = $queryBuilder->execute();
    $rows = $stm->fetchAll(FetchMode::NUMERIC);

    foreach ($rows as $row) {
        echo "{$row[0]} {$row[1]}\n";
    }
} catch (\Doctrine\DBAL\Exception $e) {
}
