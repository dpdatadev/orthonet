<?php

namespace App\Database\Repository;

require 'LinkElementRepository.php';

use App\Database\ExtendedPDO;
use PhpSpec\Exception\Fracture\InterfaceNotImplementedException;

class ArticleRepository extends LinkElementRepository {

    public function __construct(ExtendedPDO $db)
    {
        parent::__construct($db);
    }

    public function findAll()
    {
        return $this->db->run("SELECT * FROM articles.orthochristian union all SELECT * FROM articles.orthodoxchristiantheology")->fetchAll();
    }

    public function findOne(int $id)
    {
        throw new InterfaceNotImplementedException("METHOD NOT IMPL", get_called_class(), 'Repository');
    }

    public function insertOne($data)
    {
        throw new InterfaceNotImplementedException("METHOD NOT IMPL", get_called_class(), 'Repository');
    }

    public function insertMany(array $data)
    {
        throw new InterfaceNotImplementedException("METHOD NOT IMPL", get_called_class(), 'Repository');
    }
}