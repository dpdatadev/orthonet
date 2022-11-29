<?php
require_once 'DB.php';

// Simple web page to display the latest Orthodox related web scrapes that frequently run on the backend

// TODO - dynamically get all scrape schemas and display all the details so we don't have to keep
//        updating this homepage with a new scraped site
//Tool for getting verse of the day and searching scripture passages from BibleGateway.com
$orthoChristianArticlesCount = Postgres::run("SELECT COUNT(*) FROM articles.orthochristian;")->fetch();
$orthodoxChristianTheologyArticlesCount = Postgres::run("SELECT COUNT(*) FROM articles.orthodoxchristiantheology;")->fetch();
$ancientFaithPodcastCount = Postgres::run("SELECT COUNT(*) FROM podcasts.displaypodcasts;")->fetch();
$ocaDailyReadings = Postgres::run("SELECT * FROM scriptures.ocadailyreadings;");
$topAncientFaithPodcasts = Postgres::run("SELECT * FROM podcasts.displaypodcasts;");
?>
<body class="center">
<div class="container">
    <?php
    include_once('header.php');
    ?>

    <div class="jumbotron" style="background-color: grey">
        <div class="container">
            <div class="card">
                <div class="card-header text-center">

                    <img src="https://i.pinimg.com/originals/36/4b/ae/364baea6d4606a3482f8963d3f9f6190.jpg"
                         alt="Jesus Christ"
                         height="400" , width="300">
                    <h3 style="color:darkred">Orthodox Portal</h3>
                    <h5 style="color:darkblue"><i>The best of the Orthodox Internet, in one place.</i></h5>
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
            <div class="container">
                <h2>Daily Readings</h2>
                <br/>
                <?php
                echo "<ul>";
                while ($row = $ocaDailyReadings->fetch()) {

                    echo "<li class='list-group-item'>" . "<a href='" . $row['link'] . "'>" . $row['text'] . "</a>" . "</li>";

                }
                echo "</ul>";
                ?>
                <br/>
            </div>
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
        </div>
    </div>
    <?php
    include_once('footer.php');
    ?>
</div>

</body>



