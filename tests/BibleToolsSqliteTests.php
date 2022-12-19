<?php

require 'BibleToolsTest.php';


use ScrapingTest\AncientFaithPodcasts;
use ScrapingTest\OCADailyReadings;
use ScrapingTest\OCALivesOfSaints;


$ancientFaithPodcastPage = new AncientFaithPodcasts();

$ancientFaithPodcastPage->saveLinksToDatabase('ancient_faith_podcasts');
$ancientFaithPodcastPage->displayDatabasePodcastLinks('ancient_faith_podcasts');


/*
$ocaDailyReadings = new OCADailyReadings();
$ocaDailyReadings->fetchScriptureInfo();
$ocaDailyReadings->saveLinksToDatabase('oca_daily_readings');
$ocaDailyReadings->displayScriptureDatabaseLinks('oca_daily_readings');
*/

