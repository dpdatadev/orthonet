<?php

require 'SimpleScraper.php';


use ScrapingTest\AncientFaithPodcasts;
use ScrapingTest\OCADailyReadings;
use ScrapingTest\OCALivesOfSaints;

$ancientFaithPodcastPage = new AncientFaithPodcasts();
$ancientFaithPodcastPage->setDebugOn();
$ancientFaithPodcastPage->savePodCastLinksToDatabase('web_scrape_links');
$ancientFaithPodcastPage->displayDatabasePodcastLinks('web_scrape_links');

$ocaDailyReadings = new OCADailyReadings();
$ocaDailyReadings->setDebugOn();
$ocaDailyReadings->saveScriptureLinksToDatabase('web_scrape_links');
$ocaDailyReadings->displayScriptureDatabaseLinks('web_scrape_links');

$ocaDailySaints = new OCALivesOfSaints();
$ocaDailySaints->setDebugOn();
$ocaDailySaints->saveSaintLinksToDatabase('web_scrape_links');
$ocaDailySaints->displaySaintDatabaseLinks('web_scrape_links');
