<?php

//Functional test - ran after the ScrapeDatabaseCommand has loaded the SQLITE database
//with all the fresh links for the day

//This test just simply creates the scraper objects and invokes to display whatever is available
//in the database

declare(strict_types=1);

require_once 'OrthodoxScrapers.php';

error_reporting(E_ALL);

use app\scrapers\ScrapeManager as WebScraping;

echo "<h1>Web Scraping Test</h1>";


$podcastScraper = WebScraping::getScraper('Podcasts', "https://www.ancientfaith.com/podcasts#af-recent-episodes");
$articleScraper = WebScraping::getScraper('Articles', "https://www.orthodoxchristiantheology.com");
$readingScraper = WebScraping::getScraper('Readings', 'https://www.oca.org/readings');
$saintScraper = WebScraping::getScraper('Saints', 'https://www.oca.org/saints/lives/');


//.elementor-post__title a
echo "<hr />";


$podcastScraper->setDebugOn();
$articleScraper->setDebugOn();
$readingScraper->setDebugOn();
$saintScraper->setDebugOn();


$podcastScraper->displayDatabaseScrapeHTML('Recent Podcasts', 'podcasts');
$articleScraper->displayDatabaseScrapeHTML('Recent Articles', 'articles');
$readingScraper->displayDatabaseScrapeHTML('Recent Readings', 'readings');
$saintScraper->displayDatabaseScrapeHTML('Daily Saints', 'saints');

echo $articleScraper->getTableCount('web_scrape_data', 'articles');