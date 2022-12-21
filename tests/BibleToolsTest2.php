<?php

declare(strict_types=1);

require_once 'BibleToolsTest.php';

use ScrapingTest\AncientFaithPodcastScraper;

error_reporting(E_ALL);

use PHPUnit\Framework\TestCase;
use ScrapingTest\OrthodoxScraperFactory;
use ScrapingTest\Scraper;

final class BibleToolsTest2 extends TestCase
{
    //Standard scraping features - not testing the database functionality yet
    public function test_scraper_factory_can_create_scrapers_and_return_scraped_html(): void
    {
        $scraperFactory = new OrthodoxScraperFactory();
        $scrapeType = 'Podcasts';
        $scrapeUrl = "https://www.ancientfaith.com/podcasts#af-recent-episodes";
        $testScraper = $scraperFactory::createScraper($scrapeType, $scrapeUrl);

        $this->assertNotNull($testScraper);
        $this->assertClassHasAttribute('podcastLinks', $testScraper::class);

        //OrthodoxScraper is a Link scraper
        //So must pass a valid page parameter/identifier
        $link = 'a';//standard <a href='..'>link</a>

        $testScraper->fetchInfo($link);
        $testScraper->prepareInfo();
        $testScraper->displayScrapeHTML();

    }

}
