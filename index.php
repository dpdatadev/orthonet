<?php //include_once('./lib/top-cache.php');?>
<?php
require_once './lib/DB.php';
session_start();
use PDOSingleton\Postgres as DB;
use Scraping\OCALivesOfSaints as SaintsPage;
use Scraping\OCADailyReadings as DailyScripturePage;
use Scraping\AncientFaithPodcasts as PodcastsPage;

// Website to display the latest articles, news, podcasts, scripture readings, and saint readings
//from various Orthodox websites. I can save what I want to for later viewing.

// TODO - dynamically get all scrape schemas and display all the details so we don't have to keep
//        updating this homepage with a new scraped site
//12/2/2022 - So we have successfully been able to display much of the scrape data dynamically from the web
//pages instead of scraping them and loading them to the database first.
//The plan will be to use the simple_dom library to scrape and display various available data
//from several websites. Then to make this tool truly usable for me I'll add the ability
//to save the resources for later - those will persist to the database.

//Manage login user
$pageUserDisplay = "<label style='color:red'><b>LOGIN PROBLEM - CONTACT ADMINISTRATOR!</b></label>";
$pageUserRegisterDisplay = "";

if (isset($_SESSION["username"])) {
    $pageUser = $_SESSION["username"];
    $pageUserDisplay = "<p style='color:blue' class='text-center'><small>Welcome <b><i>" . $pageUser . "!</i></b></small></p><a href='logout.php'><small>Logout</small></a>";
} else {
    $pageUserDisplay = "<p><button class='btn'><a href='login.php' />Login</button></p>";
    $pageUserRegisterDisplay = "<p><button class='btn'><a href='register.php' />Register</button></p>";
}

//Tool for getting verse of the day and searching scripture passages from BibleGateway.com
$orthoChristianArticlesCount = DB::run("SELECT COUNT(*) FROM articles.orthochristian;")->fetch();
$orthodoxChristianTheologyArticlesCount = DB::run("SELECT COUNT(*) FROM articles.orthodoxchristiantheology;")->fetch();
?>
<style>
    .card-header {
        background-image: url("https://assets.simpleviewinc.com/simpleview/image/fetch/q_75/https://assets.simpleviewinc.com/simpleview/image/upload/crm/newyorkstate/Holy-Trinity-Cathedral-Interior0_e1ab8e8e-e0ee-29b0-e85bf93ec8c25bd1.jpg")

    }
</style>
<body class="center">
<div class="text-center">
    <?php echo $pageUserDisplay ?>
</div>
<hr />
<div class="text-center">
    <?php echo $pageUserRegisterDisplay ?>
</div>
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

//construct Ancient Faith podcast HTML
$recentPodcasts = new PodcastsPage();
$recentPodcasts->fetchPodcastInfo();
$recentPodcasts->preparePodcastHTML();

$ancientFaithPodcastCount = $recentPodcasts->getPodcastLinkCount();
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
                        <?php echo "<h4 class='display-4 text-center'>Ancient Faith Ministries</h4>"; ?>
                        <?php echo "<p class='lead text-center'>" . $ancientFaithPodcastCount['count'] . "+ new Podcast episodes!</p>" ?>

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
            <!--Display the newest Ancient Faith podcast episodes-->
            <?php $recentPodcasts->displayPodcastHTML(); ?>

            <?php echo "<div class='text-center'><span class='badge badge-secondary' style='color:yellow'><i><b>Last Updated: " . date("Y-m-d") . "</b></i></span></div><br>"; ?>
        </div>
    </div>
    <?php
include_once('footer.php');
?>
</div>
</body>
<?php //include_once('./lib/bottom-cache.php');?>



