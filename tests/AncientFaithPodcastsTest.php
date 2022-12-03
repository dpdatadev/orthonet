<?php
declare(strict_types=1);
error_reporting(E_ALL);

//TODO - (2022-12-02) - implement real assertion specs/tests with PHPUnit/PHPSpec

use Scraping\AncientFaithPodcasts;

include_once('../lib/BibleTools.php');

echo "<h2>Integration Tests</h2>";
echo "<br /><small>Ancient Faith Podcasts</small><hr />";

$podCastPage = new Scraping\AncientFaithPodcasts();
$podCastPage->fetchPodcastInfo();
$podCastPage->preparePodcastHTML();
$podCastPage->displayPodcastHTML();

