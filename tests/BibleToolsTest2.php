<?php

/*
 * Abstract/Synopsis
 * dpdatadev
 * 12/21/2022
 *
 * The main API/tool that this mini framework is built on is --> simple_html_dom PHP library
 * a single class, single file, PHP HTML library
 *
 * I want an easy and extensible set of classes that will use simple_html_dom to do these things:
 *
 * Scrape Links, Images, and Text from websites (I intend to scrape Orthodox Christian websites in my projects)
 *(^^Right now only Link and PlainText are supported)
 *
 * I want the tool to be able to display scraped data, return scraped data as objects, and be able to
 * store and retrieve the web scrape data inside a database. For now that is a small and simple SQLITE database.
 *
 * I could use simple_html_dom to procedurally scrape the data, repeat the steps for each site, and use
 * the PHP SQLITE3 library, maybe write a few functions, to store the data.
 *
 * If I could write a reusable set of classes to make it easy to drop into a larger project and is easily extensible-
 * that would be ideal.
 *
 * A web scraper is an object that can download a html webpage and extract information from it then either:
 * display the data
 * return the raw data
 * or store and retrieve the data in a relational database.
 *
 * A LinkElement is a class that represents an HTML link tag that consists of an HREF URI and a text element that describes the link.
 * A LinkElement web scraper is a scraper that does the things mentioned above but specifically for links in an HTML web page.
 *
 * The trait "LinkElementDatabase" provides functions for interacting with a (SQLITE) database.
 *
 * With the nature of the web pages I'm scraping for my website, I just need to fresh scrape data a few times a day.
 * The majority of the web requests being sent to the page can return data that has already been scraped and stored in the database.
 * It doesn't have to be a relational database, it could be noSQL, or it could be Redis.
 * But nonetheless - something that caches the data and doesn't require scraping the data fresh on every page request.
 *
 * I plan on writing some commands that can be called a few times a day to load fresh data and shouldn't manage
 * conditionally loading the database during the HTTP request lifecycle - only reading and displaying whatever data is available
 * in the database.
 *
 * The mini framework allows for scraper factories to be defined that will allow the system
 * to know how to configure and instantiate your custom scrapers of what kind.
 *
 * The custom scraper client that you implement will need to decide how to display or use the data
 * in whatever particular way you see fit.
 *
 * The utility classes provided just prevent you from having to make the same boilerplate code
 * for:
 * 1. Connecting to the webpage
 * 2. Downlaoding the webpage
 * 3. Returning the contents of the webpage
 * 4. Finding, validating, and returning certain elements of the webpage (i.e Links)
 * 5. Saving the elements to a database table
 * 6. Retrieving the elements from a database table
 *
 * Whatever you do from there is for you to focus on HOW the data should be used in your application.
 */

declare(strict_types=1);

require_once 'BibleToolsTest.php';

error_reporting(E_ALL);

use PHPUnit\Framework\TestCase;
use ScrapingTest\AncientFaithPodcastScraper;
use ScrapingTest\OrthodoxScraperFactory;

final class BibleToolsTest2 extends TestCase
{
    //Full API Test -- TODO, set up better test suite
    public function test_scraper_factory_can_create_scrapers_and_return_scraped_html_and_also_store_retrieve_and_display_the_data_using_a_database(): void
    {
        //Create a custom Scraper Factory which contains the configuration for instantiating our different page scrapers
        $scraperFactory = new OrthodoxScraperFactory();
        //This is a scraper for getting Recent Podcast episodes from Ancient Faith Ministries
        $scrapeType = 'Podcasts';
        $scrapeUrl = "https://www.ancientfaith.com/podcasts#af-recent-episodes";
        //Create the Scraper
        $testScraper = $scraperFactory::createScraper($scrapeType, $scrapeUrl);
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

        //Name of our table
        $testTable = 'web_scrape_data';

        //Verifying we actually have some data to work with
        $this->assertNotEmpty($testLinks);
        $this->assertNotNull($testLinks);

        //Creation of the database table and loading of the table is handled statically at the class level
        //These methods won't be called multiple times in the HTTP request life cycle but will be fired off
        //By a cron job or some other trigger..they are utility functions.
        AncientFaithPodcastScraper::createLinkDatabaseTable($testTable);

        //The data being inserted into the database is the freshly scraped and prepared data that fetchInfo() returned.
        AncientFaithPodcastScraper::saveLinksToDatabase($testTable, $testScraper->getScrapeData(), 'podcasts');

        //Now to test that the links were properly saved to the database we will fetch and test
        $testDatabaseLinks = $testScraper->getLinksFromDatabase('web_scrape_data', 'podcasts');

        //Assert not empty
        $this->assertNotEmpty($testDatabaseLinks);
        //Assert not null - we actually have something cached in the DB
        $this->assertNotNull($testDatabaseLinks);

        //Now lets see how many links we got from the HTML page
        $scrapeCount = count($testLinks);

        //Lets see how many links we got back from the database
        $scrapeDatabaseCount = count($testDatabaseLinks);

        //Now we will compare the two numbers and make sure they are the same
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
        $testScraper->displayDatabaseScrapeHTML($testTable);
    }

}
