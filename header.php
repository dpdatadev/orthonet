<?php
require_once './lib/BibleTools.php';

use Scraping\BibleGateway as Bible;

error_reporting(-1);
ini_set('display_errors', '1'); //debug
$bible = new Bible("NKJV");
$bible->getVerseOfTheDay();
$bibleVersion = $bible->version;
$verseOftheDayText = $bible->text;
$verseOfTheDayReference = $bible->reference;
$verseOfTheDayLink = $bible->permalink;
$verseOfTheDayDisplay = "<a href='" . $verseOfTheDayLink . "'>" . $verseOftheDayText . "</a> - <small><i>" . $verseOfTheDayReference . " " . $bibleVersion . "</i></small>";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Orthodoxy: Whats new?</title>
    <!-- Bootstrap 4 CSS  -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 25px;
            background-color: burlywood;
        }

        .center {
            position: absolute;
            /*  top: 0;
              bottom: 0; */
            left: 0;
            right: 0;
            margin: auto;
        }
    </style>
</head>

<?php
echo "<div class='container'><strong>" . $verseOfTheDayDisplay . "</strong></div>";
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">Orthodox Portal</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="about.php">About</a>
            </li>
        </ul>
    </div>
</nav>