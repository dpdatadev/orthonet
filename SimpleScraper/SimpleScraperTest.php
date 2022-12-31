<?php

declare(strict_types=1);

namespace SimpleScraperTest;
require_once 'OrthodoxScrapers.php';

error_reporting(E_ALL);

use PHPUnit\Framework\TestCase;

use SimpleScraper\SQLITEManager;
use SimpleScraper\Scraper;


use app\scrapers\OrthodoxScraperFactory;

//Full API Test -- TODO, further refine tests (fixtures, mocks, increased coverage, etc.,)
final class SimpleScraperTest extends TestCase
{

    private function createTestTable(): void
    {
        SQLITEManager::createDatabaseTable('web_scrape_data', 'CREATE TABLE web_scrape_data(ID INTEGER PRIMARY KEY AUTOINCREMENT, link varchar(255) null, text varchar(255) null, category varchar(255) null, create_ts timestamp not null default(CURRENT_TIMESTAMP));');
    }

    protected function getOrthodoxPodcastScraper(): Scraper
    {
        //This is a scraper for getting Recent Podcast episodes from Ancient Faith Ministries
        $scrapeType = 'Podcasts';
        $scrapeUrl = "https://www.ancientfaith.com/podcasts#af-recent-episodes";
        //Create the Scraper
        return OrthodoxScraperFactory::createScraper($scrapeType, $scrapeUrl);
    }

    protected function getOrthodoxSaintScraper(): Scraper
    {
        $scrapeType = 'Saints';
        $scrapeUrl = "https://www.oca.org/saints/lives/";
        return OrthodoxScraperFactory::createScraper($scrapeType, $scrapeUrl);
    }

    protected function getOrthodoxScriptureScraper(): Scraper
    {
        $scrapeType = 'Readings';
        $scrapeUrl = "https://www.oca.org/readings";;
        return OrthodoxScraperFactory::createScraper($scrapeType, $scrapeUrl);
    }

    /**
     * @group ignore
     */
    public function test_create_database_and_web_scrape_table_and_verify_success(): void
    {
        //We are testing to ensure that we can create the database and table only once and then insert links
        //for all the configured linkelement scrapers.
        $this->createTestTable();
        $this->assertTrue(SQLITEManager::linkDatabaseExists());
    }

    //DEPRECATED

    /**
     * @group ignore
     */
    public function test_that_we_can_create_the_database_and_table_then_get_a_scraper_from_the_factory_then_scrape_and_display_using_the_database(): void
    {
        $testScraper = $this->getOrthodoxPodcastScraper();

        //Test that creation was successful
        $this->assertNotNull($testScraper);
        //Test that it's truly a Podcast scraper, proving the matching and instantiation was successful
        $this->assertClassHasAttribute('podcastLinks', $testScraper::class);

        //Provide a page parameter (the identifier that represents what part of the HTML we're collecting)
        $link = 'a';//standard <a href='..'>link</a>

        //Fetch the links
        $testScraper->fetchInfo($link);
        //Prepare the links (this could be anything you want to do with the data before further processing)
        $testScraper->prepareInfo();

        //Display the raw scrape data just captured
        //$testScraper->displayScrapeHTML();

        //Be able to view the generated SQL and other output
        $testScraper->setDebugOn();

        //Get an array of the data we scraped for testing
        $testLinks = $testScraper->getScrapeData();

        //Make sure we have some data to use
        $this->assertNotNull($testLinks);
        $this->assertNotEmpty($testLinks);

        //Now we'll interact with the database which will have been created in the test above
        //==================================================================================
        //The data being inserted into the database is the freshly scraped and prepared data that fetchInfo() returned.
        $testScraper->saveLinksToDatabase(category: 'podcasts');

        //Now to test that the links were properly saved to the database we will fetch and test
        $testDatabaseLinks = $testScraper->getLinksFromDatabase(category: 'podcasts');

        //Assert not empty
        $this->assertNotEmpty($testDatabaseLinks);
        //Assert not null - we actually have something cached in the DB
        $this->assertNotNull($testDatabaseLinks);

        //Now lets see how many links we got from the HTML page
        $scrapeCount = count($testLinks);

        //Lets see how many links we got back from the database
        $scrapeDatabaseCount = count($testDatabaseLinks);

        //Now we will compare the two numbers and make sure they are the same
        //The count should be 25 because in our prepare method, we spliced the array
        //to make sure only 25 elements would be stored for display
        $this->assertSame($scrapeCount, $scrapeDatabaseCount);

        //Now go ahead and show the (cached) data from the database!

        //Note: the custom podcast scraper client itself dictates how the data should be used for my application.
        //In this case I'm wrapping the links inside of a text-centered bootstrap container and displaying a simple
        //unordered HTML list

        //Your client could save this data to a text file, return it as JSON, be used in a Symfony controller - etc!
        //For me in this hypothetical example - I'm just using SQLITE as a cache. The database file can be dropped
        //and recreated and I don't have to manage a remote database server to store the scrape data which is
        //refreshed a few times a day and used just to display updates from these websites to my users.

        //But of course you could be scraping data that needs to be stored in a more permanent datastore, written to a file,
        //or passed to another PHP process!
        $testScraper->displayDatabaseScrapeHTML('Recent Podcasts', 'podcasts');
    }

    /**
     * @group ignore
     */
    public function test_loading_existing_podcasts_without_scraping(): void
    {
        $testScraper = $this->getOrthodoxPodcastScraper();

        $this->assertTrue(SQLITEManager::linkDatabaseExists());
        //We ran one fresh podcast scrape in the test prior
        $expectedCount = 25;

        $testCount = count($testScraper->getLinksFromDatabase(category: 'podcasts'));

        $this->assertSame($expectedCount, $testCount);

        $testScraper->setDebugOn(); //show the SQL for the below operation

        $testScraper->displayDatabaseScrapeHTML();

    }

    /**
     * @group ignore
     */
    public function test_oca_saint_scraper_can_save_links_to_database_and_display_them(): void
    {
        $this->createTestTable();
        $this->assertTrue(SQLITEManager::linkDatabaseExists());
        $testScraper = $this->getOrthodoxSaintScraper();
        $this->assertNotEmpty($testScraper);
        $this->assertNotNull($testScraper);
        $testScraper->fetchInfo('article h2 article a');
        $this->assertObjectHasAttribute('html', $testScraper);
        $testScraper->prepareInfo();
        $this->assertObjectHasAttribute('saintSnippets', $testScraper);
        $testLinks = $testScraper->getScrapeData();
        $this->assertNotNull($testLinks);
        $this->assertNotEmpty($testLinks);
        $this->assertNotCount(0, $testLinks);

        $testScraper->saveLinksToDatabase(category: 'saints');

        //Now get the saint links we just saved and validate
        $testDatabaseLinks = $testScraper->getLinksFromDatabase(category: 'saints');

        $this->assertNotEmpty($testDatabaseLinks);
        $this->assertNotNull($testDatabaseLinks);

        $scrapeCount = count($testLinks);
        $scrapeDatabaseCount = count($testDatabaseLinks);

        $this->assertSame($scrapeCount, $scrapeDatabaseCount);

        $testScraper->setDebugOn();
        $testScraper->displayDatabaseScrapeHTML();
    }


    public function test_oca_scripture_scraper_can_save_links_to_database_and_display_them(): void
    {
        $this->createTestTable();
        $this->assertTrue(SQLITEManager::linkDatabaseExists());
        $testScraper = $this->getOrthodoxScriptureScraper();
        $this->assertNotEmpty($testScraper);
        $this->assertNotNull($testScraper);
        $testScraper->fetchInfo('a');
        $this->assertObjectHasAttribute('html', $testScraper);
        $testScraper->prepareInfo();
        $this->assertObjectHasAttribute('readingLinks', $testScraper);
        $testLinks = $testScraper->getScrapeData();
        $this->assertNotNull($testLinks);
        $this->assertNotEmpty($testLinks);
        $this->assertNotCount(0, $testLinks);

        $testScraper->saveLinksToDatabase(category: 'scriptures');
        usleep(800);
        //Now get the scripture links we just saved and validate
        $testDatabaseLinks = $testScraper->getLinksFromDatabase(category: 'scriptures');

        $this->assertNotEmpty($testDatabaseLinks);
        $this->assertNotNull($testDatabaseLinks);

        $scrapeCount = count($testLinks);
        $scrapeDatabaseCount = count($testDatabaseLinks);

        $this->assertSame($scrapeCount, $scrapeDatabaseCount);

        $testScraper->setDebugOn();
        $testScraper->displayDatabaseScrapeHTML('Daily Readings', 'scriptures');

    }

    //TODO - test raw HTML can be returned and that SimpleDOM HTML values can be nulled out using clear() method
}
