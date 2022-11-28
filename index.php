<?php
require_once 'DB.php';

// Simple web page to display the latest Orthodox related web scrapes that frequently run on the backend


// TODO - dynamically get all scrape schemas and display all the details so we don't have to keep
//        updating this homepage with a new scraped site
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
</head>
<body>
<div class="jumbotron">
    <ul>
        <?php echo "<p style='color:green'><strong>Database Connected...</strong></p>"; ?>
        <li><?php echo "<h3>There are " . $orthoChristianArticlesCount['count'] . " new articles from OrthoChristian.com</h3>"; ?></li>
<br />
        <li><?php echo "<h3>There are " . $orthodoxChristianTheologyArticlesCount['count'] . " new articles from OrthodoxChristianTheology.com</h3>"; ?></li>
<br />
        <li><?php echo "<h3>There are " . $ancientFaithPodcastCount['count'] . " podcasts from AncientFaith.com" ?></li>
<br />
    </ul>
</div>
<hr />
<div>
    <div>
        <h2>Daily Readings</h2>

        <?php
        echo "<ul class='list-group'>";
            while ($row = $ocaDailyReadings->fetch()) {
                echo "<div class='card'>";
                echo "<div class='card-body'>";
                echo "<li class='list-group-item'>" . "<a href='" . $row['link'] . "'". "/>". "<b><i>" . " " . $row['text'] . "</i></b>" . "</li>";
                echo "</div>";
                echo "</div>";
            }
        echo "</ul>";
        ?>

        <hr />


        <?php
        echo "<h2>Recent Podcasts</h2>";
        echo "<ul class='list-group'>";
        while ($row = $topAncientFaithPodcasts->fetch()) {
            echo "<div class='card'>";
            echo "<div class='card-body'>";
            echo "<li class='list-group-item'>" . "<a href='" . $row['link'] . "'". "/>". "<b><i>" . " " . $row['text'] . "</i></b>" . "</li>";
            echo "</div>";
            echo "</div>";
        }
        echo "</ul>";
        ?>


    </div>
</div>



</body>
</html>