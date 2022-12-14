<?php

declare(strict_types=1);
require '../lib/ArticleRepository.php';

use PHPUnit\Framework\TestCase as TestClass;
use App\Database\Repository\ArticleRepository as TestRepo;

final class ArticlesRepositoryTest extends TestClass
{

    public function test_that_articles_repository_can_fetch_all_articles(): void
    {
        //grab the Postgres PDO object
        $container = require '../dependencies/bootstrap.php';
        //create a new articlerepository with the Postgres PDO object
        $articleRepo = new TestRepo($container['pdoPostgres']);

        //attempt to fetch all articles
        $testArray = $articleRepo->findAll();

        //make sure the instantiation succeeded
        $this->assertNotNull($articleRepo);
        //make sure the query actually worked
        $this->assertNotEmpty($testArray);
        //make sure what we expect is accurate
        $this->assertSame(45, count($testArray));
    }
}