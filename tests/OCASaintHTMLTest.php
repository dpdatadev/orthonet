<?php
declare(strict_types=1);

use Scraping\OCALivesOfSaints;

include_once('../lib/BibleTools.php');


$saintPage = new OCALivesOfSaints();

$saintPage->fetchSaintInfo();
$saintPage->prepareSaintHtml();
$saintPage->displaySaintHtml();








