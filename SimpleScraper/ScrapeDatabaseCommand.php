<?php

//dpdatadev - 1/2/2023
//This could be a symfony command or yii command ... or any kind of generic command line/cron job task etc.,
//Should run once per day to get the latest updates - users will get the updated scrape data from SQLITE (fast cache)

declare(strict_types=1);

namespace SimpleScraper\Command;

require 'OrthodoxScrapers.php';

error_reporting(E_ALL);

use app\scrapers\ScrapeManager as WebScraping;

WebScraping::createDatabaseTable();

$podcastScraper = WebScraping::getScraper('Podcasts', "https://www.ancientfaith.com/podcasts#af-recent-episodes");
$orthodoxChristianTheology = WebScraping::getScraper('Articles', "https://www.orthodoxchristiantheology.com");
$orthoChristian = WebScraping::getScraper('Articles', 'https://orthochristian.com/202/');
$patristicFaith = WebScraping::getScraper('Articles', 'https://www.patristicfaith.com/category/orthodox-christianity/orthodox-christian-theology/');
$readingScraper = WebScraping::getScraper('Readings', 'https://www.oca.org/readings');
$saintScraper = WebScraping::getScraper('Saints', 'https://www.oca.org/saints/lives/');


$saintScraper->fetchInfo('article');
$saintScraper->prepareInfo();

$readingScraper->fetchInfo('a');
$readingScraper->prepareInfo();

$podcastScraper->fetchInfo('a');
$podcastScraper->prepareInfo();

$orthodoxChristianTheology->fetchInfo('#posts article .post-title a');
$orthodoxChristianTheology->prepareInfo();

$orthoChristian->fetchInfo('.list-articles-wide__uppertitle a');
$orthoChristian->prepareInfo();

$patristicFaith->fetchInfo('.elementor-post__title a');
$patristicFaith->prepareInfo();

//we have data to save or display
$podcastScraper->saveLinksToDatabase('podcasts');
$orthodoxChristianTheology->saveLinksToDatabase('articles');
$orthoChristian->saveLinksToDatabase('articles');
$patristicFaith->saveLinksToDatabase('articles');
$readingScraper->saveLinksToDatabase('readings');
$saintScraper->saveLinksToDatabase('saints');
