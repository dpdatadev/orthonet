<?php

declare(strict_types=1);

require_once 'OrthodoxScrapers.php';

error_reporting(E_ALL);

use Scrapers\OrthodoxWebScraper as WebScraping;

//WebScraping::createDatabaseTable();

$podcastScraper = WebScraping::getScraper('Podcasts', "https://www.ancientfaith.com/podcasts#af-recent-episodes");

echo "<h1>Web Scraping Test</h1>";


//$podcastScraper->fetchInfo('a');
//$podcastScraper->prepareInfo();

$podcastScraper->setDebugOn();
/*
if ($podcastScraper->getLinkCount() > 0)
{
    $podcastScraper->saveLinksToDatabase(category: 'podcasts');
}
*/
echo "<hr />";

$podcastScraper->displayDatabaseScrapeHTML('Recent Podcasts', 'podcasts');


