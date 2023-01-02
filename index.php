<?php //include_once('./lib/top-cache.php');?>
<?php
session_start();

require_once './SimpleScraper/OrthodoxScrapers.php';

use app\scrapers\ScrapeManager as WebScraping;

// Website to display the latest articles, news, podcasts, scripture readings, and saint readings
//from various Orthodox websites. I can save what I want to for later viewing.

$podcastScraper = WebScraping::getScraper('Podcasts', "https://www.ancientfaith.com/podcasts#af-recent-episodes");
$articleScraper = WebScraping::getScraper('Articles', "https://www.orthodoxchristiantheology.com");
$readingScraper = WebScraping::getScraper('Readings', 'https://www.oca.org/readings');
$saintScraper = WebScraping::getScraper('Saints', 'https://www.oca.org/saints/lives/');


$ancientFaithPodcastCount = $podcastScraper->getTableCount('web_scrape_data', 'podcasts');
$articleCount = $articleScraper->getTableCount('web_scrape_data', 'articles');


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
                        <?php echo "<h4 class='display-4 text-center'>OrthodoxChristianTheology.com</h4>"; ?>
                        <?php echo "<h4 class='display-4 text-center'>PatristicFaith.com</h4>"; ?>
                        <?php echo "<p class='lead text-center'>" . $articleCount . "+ new articles</p>" ?>
                        <br/>
                        <?php echo "<h4 class='display-4 text-center'>Ancient Faith Ministries</h4>"; ?>
                        <?php echo "<p class='lead text-center'>" . $ancientFaithPodcastCount . "+ new Podcast episodes!</p>" ?>

                    </ul>

                </div>

                <div class="card-footer">
                    <p class="display-4 text-center">Coming Soon!</p>
                    <ul class="text-center">
                        <p class="lead"><i>Search functionality!<i></p>
                        <p class="lead"><i>Patristic Faith Ministries!</i></p>
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
            <?php $readingScraper->displayDatabaseScrapeHTML('Recent Readings', 'readings'); ?>
            <!--Display the OCA daily Lives of Saints-->
            <?php $saintScraper->displayDatabaseScrapeHTML('Daily Saints', 'saints'); ?>
            <!--Display the newest Ancient Faith podcast episodes-->
            <?php $podcastScraper->displayDatabaseScrapeHTML('Recent Podcasts', 'podcasts'); ?>
            <!--Display the newest articles -->
            <?php $articleScraper->displayDatabaseScrapeHTML('Recent Articles', 'articles'); ?>

            <?php echo "<div class='text-center'><span class='badge badge-secondary' style='color:yellow'><i><b>Last Updated: " . date("Y-m-d") . "</b></i></span></div><br>"; ?>
        </div>
    </div>
    <?php
include_once('footer.php');
?>
</div>
</body>
<?php //include_once('./lib/bottom-cache.php');?>



