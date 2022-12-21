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
    //Legacy compatibility
    //Existing functionality
    private Scraper $testScraper;

    public function getTestScraper(): Scraper
    {
        return $this->testScraper;
    }

    public function setTestScraper(Scraper $testScraper): void
    {
       $this->testScraper = $testScraper;
    }

    //TODO
    public function test_scraper_factory_can_create_scrapers(): void
    {
        $scraperFactory = new OrthodoxScraperFactory();
        $scrapeType = 'Podcasts';
        $scrapeUrl = "https://www.ancientfaith.com/podcasts#af-recent-episodes";
        $testScraper = $scraperFactory::createScraper($scrapeType, $scrapeUrl);

        $this->assertNotNull($testScraper);
        $this->isType('Scraper', $testScraper);
    }
    public function test_can_fetch_podcasts_and_display_scraped_html(): void
    {
        $ancientFaithPodcastScraper = new AncientFaithPodcastScraper("https://www.ancientfaith.com/podcasts#af-recent-episodes");
        $this->assertNotNull($ancientFaithPodcastScraper);

        //TODO
    }
}
