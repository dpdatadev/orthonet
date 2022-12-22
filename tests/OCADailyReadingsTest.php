<?php

declare(strict_types=1);

//TODO - (2022-12-02) - implement real assertion specs/tests with PHPUnit/PHPSpec

use Scraping\OCALivesOfSaints;

include_once('../lib/MiniScrapeFramework.php');

echo "<h2>Integration Tests</h2>";
echo "<br /><small>OCA Daily Scripture Readings</small><hr />";

$dailyReadingPage = new \Scraping\OCADailyReadings();

$dailyReadingPage->fetchScriptureInfo();
$dailyReadingPage->displayScriptureHTML();
