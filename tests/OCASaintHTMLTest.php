<?php

declare(strict_types=1);

//TODO - (2022-12-02) - implement real assertion specs/tests with PHPUnit/PHPSpec

use Scraping\OCALivesOfSaints;

include_once('../lib/BibleTools.php');

echo "<h2>Integration Tests</h2>";
echo "<br /><small>OCA Lives of The Saints Scraping</small><hr />";

$saintPage = new OCALivesOfSaints();

$saintPage->fetchSaintInfo();
$saintPage->prepareSaintHtml();
$saintPage->displaySaintHtml();
