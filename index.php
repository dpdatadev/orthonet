<?php
require_once 'DB.php';
require_once 'BibleTools.php';

// Simple web page to display the latest Orthodox related web scrapes that frequently run on the backend


// TODO - dynamically get all scrape schemas and display all the details so we don't have to keep
//        updating this homepage with a new scraped site

//Tool for getting verse of the day and searching scripture passages from BibleGateway.com
$bibleGateway = new BibleGateway("NKJV");
$bibleGateway->getVerseOfTheDay();
$verseOftheDayText = $bibleGateway->text;
$verseOfTheDayReference = $bibleGateway->reference;
$verseOfTheDayLink = $bibleGateway->permalink;
$verseOfTheDayDisplay = "<a href='" . $verseOfTheDayLink . "'>" . $verseOftheDayText . "</a> - <small><i>" . $verseOfTheDayReference . "</i></small>";

$orthoChristianArticlesCount = Postgres::run("SELECT COUNT(*) FROM articles.orthochristian;")->fetch();
$orthodoxChristianTheologyArticlesCount = Postgres::run("SELECT COUNT(*) FROM articles.orthodoxchristiantheology;")->fetch();
$ancientFaithPodcastCount = Postgres::run("SELECT COUNT(*) FROM podcasts.ancientfaith;")->fetch();
$ocaDailyReadings = Postgres::run("SELECT * FROM scriptures.ocadailyreadings;");
$topAncientFaithPodcasts = Postgres::run("SELECT * FROM podcasts.ancientfaith ORDER BY text DESC FETCH NEXT 33 ROWS ONLY;");
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
            background-color: ghostwhite;
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
<body class="center">
<?php
echo "<strong>" . $verseOfTheDayDisplay . "</strong>";
?>
<div class="container">
    <div class="jumbotron">

        <div class="card">
            <div class="card-header">

                <img src="https://i.pinimg.com/originals/36/4b/ae/364baea6d4606a3482f8963d3f9f6190.jpg"
                     alt="Jesus Christ"
                     height="400" , width="300">
                <h3 style="color:darkred">Orthodox Portal</h3>
                <h5 style="color:darkblue"><i>The best of the Orthodox Internet, in one place.</i></h5>

                <div class="card-body">
                    <hr/>
                    <ul>


                        <li><?php echo "<h3>There are " . $orthoChristianArticlesCount['count'] . " new articles from OrthoChristian.com</h3>"; ?></li>
                        <br/>
                        <li><?php echo "<h3>There are " . $orthodoxChristianTheologyArticlesCount['count'] . " new articles from OrthodoxChristianTheology.com</h3>"; ?></li>
                        <br/>
                        <li><?php echo "<h3>There are " . $ancientFaithPodcastCount['count'] . " podcasts from AncientFaith.com" ?></li>
                        <br/>
                    </ul>
                </div>
                <div class="card-footer">
                    <?php echo "<p style='color:green'><strong>Database Connected...</strong></p>"; ?>
                    <?php echo "<i> Today is </i>" . date("Y/m/d") . "<br />"; ?>
                </div>
            </div>
        </div>


    </div>

    <div>
        <div>
            <h2>Daily Readings</h2>
            <?php
            echo "<ul>";
            while ($row = $ocaDailyReadings->fetch()) {
                //echo "<div class='card'>";
                //echo "<div class='card-body'>";
                echo "<li class='list-group-item'>" . "<a href='" . $row['link'] . "'>" . $row['text'] . "</a>" . "</li>";
                //echo "</div>";
                //echo "</div>";
            }
            echo "</ul>";
            ?>
            <br/>
            <div>
                <?php
                echo "<h2>Recent Podcasts</h2>";
                echo "<ul>";
                while ($row = $topAncientFaithPodcasts->fetch()) {
                    //echo "<div class='card'>";
                    //echo "<div class='card-body'>";
                    echo "<li class='list-group-item'>" . "<a href='" . $row['link'] . "'>" . $row['text'] . "</a>" . "</li>";
                    //echo "</div>";
                    //echo "</div>";
                }
                echo "</ul>";
                ?>

            </div>
        </div>
    </div>
</div>


</body>
</html>