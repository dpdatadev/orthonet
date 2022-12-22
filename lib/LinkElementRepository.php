<?php

namespace App\Database\Repository;

use App\Database\ExtendedPDO;

interface Repository
{
    public function findAll();
    public function findOne(int $id);
    public function insertOne(object $data);
    public function insertMany(array $data);
}

abstract class LinkElementRepository implements Repository
{
    protected ExtendedPDO $db;

    public function __construct(ExtendedPDO $db)
    {
        $this->db = $db;
    }

    abstract public function findAll();
    abstract public function findOne(int $id);
    abstract public function insertOne($data);
    abstract public function insertMany(array $data);
}
