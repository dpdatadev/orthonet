<?php
require_once './lib/DB.php';

use Scraping\OCALivesOfSaints as SaintsPage;
use Scraping\OCADailyReadings as DailyScripturePage;

// Simple web page to display the latest Orthodox related web scrapes that frequently run on the backend

// TODO - dynamically get all scrape schemas and display all the details so we don't have to keep
//        updating this homepage with a new scraped site
//Tool for getting verse of the day and searching scripture passages from BibleGateway.com
$orthoChristianArticlesCount = Postgres::run("SELECT COUNT(*) FROM articles.orthochristian;")->fetch();
$orthodoxChristianTheologyArticlesCount = Postgres::run("SELECT COUNT(*) FROM articles.orthodoxchristiantheology;")->fetch();
$ancientFaithPodcastCount = Postgres::run("SELECT COUNT(*) FROM podcasts.displaypodcasts;")->fetch();
$topAncientFaithPodcasts = Postgres::run("SELECT * FROM podcasts.displaypodcasts;");
?>
<style>
    .card-header {
        background-image: url("https://assets.simpleviewinc.com/simpleview/image/fetch/q_75/https://assets.simpleviewinc.com/simpleview/image/upload/crm/newyorkstate/Holy-Trinity-Cathedral-Interior0_e1ab8e8e-e0ee-29b0-e85bf93ec8c25bd1.jpg")

    }
</style>
<body class="center">
<div class="container">
    <?php
    include_once('header.php');
    ?>

    <?php

    //construct the OCA Daily Scripture Readings HTML
    $dailyScriptureReadings = new DailyScripturePage();
    $dailyScriptureReadings->fetchScriptureInfo();

    //construct the OCA Saint of the Day HTML
    $saintsOfTheDay = new SaintsPage();
    $saintsOfTheDay->fetchSaintInfo();
    $saintsOfTheDay->prepareSaintHtml();
    ?>

    <div class="jumbotron" style="background-color: grey">
        <div class="container">
            <div class="card">
                <div class="card-header text-center">
                    <div style="background-color: lightblue">
                        <h2 class='display-2' style="color:darkred">Orthodox Portal</h2>
                    </div>
                    <img src="https://i.pinimg.com/originals/36/4b/ae/364baea6d4606a3482f8963d3f9f6190.jpg"
                         alt="Jesus Christ"
                         height="400" , width="300">

                    <div style="background-color: lightblue">
                        <h4 class='display-4' style="color:darkblue"><i>The best of the Orthodox Internet, in one
                                place.</i></h4>
                    </div>
                </div>

                <div class="card-body">

                    <ul>


                        <?php echo "<h4 class='display-4 text-center'>OrthoChristian.com</h4>"; ?>
                        <?php echo "<p class='lead text-center'>" . $orthoChristianArticlesCount['count'] . "+ new articles</p>" ?>
                        <br/>
                        <?php echo "<h4 class='display-4 text-center'>OrthodoxChristianTheology.com</h4>"; ?>
                        <?php echo "<p class='lead text-center'>" . $orthodoxChristianTheologyArticlesCount['count'] . "+ new articles</p>" ?>
                        <br/>
                        <?php echo "<h4 class='display-4 text-center'>Ancient Faith Minitries</h4>"; ?>
                        <?php echo "<p class='lead text-center'>" . $ancientFaithPodcastCount['count'] . "+ new Podcasts episodes!</p>" ?>

                    </ul>

                </div>

                <div class="card-footer">
                    <p class="display-4 text-center">Coming Soon!</p>
                    <ul class="text-center">
                        <p class="lead"><i>Search functionality!<i></p>
                        <p class="lead"><i>Patristic Faith Ministries!</i></p>
                        <p class="lead"><i>Patristic Nectar, Abbot Tryphon</i></p>
                        <p class="lead"><i>and more!</i></p>
                    </ul>
                </div>
            </div>
        </div>


    </div>

    <div>
        <div>
            <hr/>
            <!--Display the OCA daily Scripture Readings-->
            <?php $dailyScriptureReadings->displayScriptureHTML(); ?>
            <!--Display the OCA daily Lives of Saints-->
            <?php $saintsOfTheDay->displaySaintHtml(); ?>
            <br/>
            <div class="container">
                <?php
                echo "<h2>Recent Podcasts</h2>";
                echo "<br />";
                echo "<ul>";
                while ($row = $topAncientFaithPodcasts->fetch()) {

                    echo "<li class='list-group-item'>" . "<a href='" . $row['link'] . "'>" . $row['text'] . "</a>" . "</li>";

                }
                echo "</ul>";
                ?>
            </div>
            <br/>
            <hr/>
            <?php echo "<div class='text-center'><span class='badge badge-secondary' style='color:yellow'><i><b>Last Updated: " . date("Y-m-d") . "</b></i></span></div><br>"; ?>
        </div>
    </div>
    <?php
    include_once('footer.php');
    ?>
</div>

</body>



