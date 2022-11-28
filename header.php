<?php
require_once 'BibleTools.php';

error_reporting(E_ALL); //debug
$bibleGateway = new BibleGateway("NKJV");
$bibleGateway->getVerseOfTheDay();
$verseOftheDayText = $bibleGateway->text;
$verseOfTheDayReference = $bibleGateway->reference;
$verseOfTheDayLink = $bibleGateway->permalink;
$verseOfTheDayDisplay = "<a href='" . $verseOfTheDayLink . "'>" . $verseOftheDayText . "</a> - <small><i>" . $verseOfTheDayReference . "</i></small>";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Orthodoxy: Whats new?</title>
    <!-- Bootstrap 4 CSS  -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <style>
        body {
            padding-top: 25px;
            background-color: navajowhite;
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