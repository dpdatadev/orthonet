<?php

/** @noinspection ALL */
declare(strict_types=1);

namespace Scraping;

//dependencies
require_once './vendor/autoload.php';
//opensource PHP web scraping library
require_once './lib/simple_html_dom.php';


use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\FetchMode;
use http\Exception\UnexpectedValueException;

//Daily generated SQLITE database to hold web scrape data
//Somewhat of a cache for the frontend -
//the goal is to scrape the same data less
define('DAILY_DATABASE', "link_database_" . date('y_m_d') . ".db");

//#[AllowDynamicProperties] PHP 8.2 will deprecate dynamic properties outside of stdClass
class LinkElement
{
    protected string $link;
    protected string $text;
    //handle dynamic properties from HTML
    protected array $overloadedData;

    public function __construct(string $link, string $text)
    {
        $this->link = $link;
        $this->text = $text;
        $this->overloadedData = array();
    }

    public function __set($name, $value)
    {
        $this->overloadedData[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->overloadedData)) {
            return $this->overloadedData[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE
        );
        return null;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function displayHTML(): string
    {
        return "<li class='list-group-item'>" . "<a href='" . $this->link . "'>" . $this->text . "</a>" . "</li>";
    }

    public function __toString(): string
    {
        return "::" . $this->getLink() . "::" . $this->getText() . "::";
    }
}

//Ancient Faith Recent Podcasts
class PodcastLink extends LinkElement
{
}

//OCA Daily Scripture Readings
class ReadingLink extends LinkElement
{
}

//OCA Life of Saint Readings
class SaintLink extends LinkElement
{
}

trait ValidatesSaintLinks
{
    //utility functions for validating different types of links


    //we only want to display "lives of the saints" links
    //and not the daily troparia and kontakia (or other misc links)
    public function isLifeLink($link): bool
    {
        if (str_contains($link, 'lives')) {
            return true;
        } else {
            return false;
        }
    }
}

trait ValidatesReadingLinks
{
    //utility function
    //check if the link contains "/readings/daily"
    public function isScriptureLink($link): bool
    {
        if (str_contains($link, 'readings/daily')) {
            return true;
        } else {
            return false;
        }
    }
}



trait ValidatesLinkTypes
{
    //Traits can't have constants until PHP 8.2 I think....
    public function getPodcastLinkPattern(): string
    {
        return '/podcasts/';
    }
    public function getScriptureLinkPattern(): string
    {
        return 'readings/daily';
    }
    public function getLivesOfSaintsLinkPattern(): string
    {
        return 'lives';
    }

    public function isValidLinkType(string $link, string $linkType)
    {
        if (str_contains($link, $linkType)) {
            return true;
        } else {
            return false;
        }
    }
}

trait LinkElementDatabase
{
    private function getSqlite3Connection()
    {
        $attrs = ['driver' => 'pdo_sqlite', 'path' => DAILY_DATABASE];
        return DriverManager::getConnection($attrs);
    }

    //If the daily database doesn't exist then
    //the client class can choose to scrape the links fresh
    //and insert them into the database
    //every page request after that will be pulling SQL
    //which is much faster than constantly scraping for links
    public function linkDatabaseExists(): bool
    {
        return file_exists(DAILY_DATABASE);
    }

    private function dropCreateTable(string $table): void
    {
        $conn = $this->getSqlite3Connection();
        $conn->executeQuery('DROP TABLE IF EXISTS ' . $table . ';');
        $conn->executeQuery('CREATE TABLE ' . $table . ' (link varchar(255), text varchar(1000) null, category varchar(100) null)');
        $conn->close();
    }

    public function insertLinks(string $table, array $links): bool|int|string
    {
        $this->dropCreateTable($table);

        $conn = $this->getSqlite3Connection();

        foreach ($links as $link) {
            $conn->insert($table, array('link' => $link->getLink(), 'text' => $link->getText()));
        }

        $lastInsertId = $conn->lastInsertId();

        $conn->close();

        return $lastInsertId;
    }

    public function getAllLinks(string $table): array
    {
        $databaseLinks = [];
        $conn = $this->getSqlite3Connection();
        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder->select('*')->from($table);

        $stm = $queryBuilder->execute();
        foreach ($stm->fetchAll(FetchMode::NUMERIC) as $databaseLink) {
            $newLink = new LinkElement($databaseLink[0], $databaseLink[1]);
            $databaseLinks[] = $newLink;
        }

        return $databaseLinks;
    }
}

trait DisplaysLinks
{
    public function displayLinkHTML(string $displayName, array $links): void
    {
        echo "<div class='container text-center'>";
        echo "<br />";
        echo "<h2>" . $displayName . "</h2>";
        echo "<br />";

        foreach ($links as $link) {
            echo $link->displayHTML();
        }
        echo "<ul>";

        echo "</ul>";
        echo "<br />";
        echo "</div>";
        echo "<hr />";
    }
}

//Recent Podcast Episodes published by Ancient Faith
class AncientFaithPodcasts
{
    //provides different types of link patterns
    //for verification
    use ValidatesLinkTypes;

    //Display HTML table of link elements
    //whether they be freshly scraped or from the database
    use DisplaysLinks;

    //Access to cached web scrape data in SQLITE
    use LinkElementDatabase;

    //most recent podcasts from Ancient Faith Ministries
    private const URL = "https://www.ancientfaith.com/podcasts#af-recent-episodes";

    //harvested podcast links
    private $podcastLinks;
    //loaded web page
    private $html;

    public function __construct()
    {
        $this->podcastLinks = array();

        $this->html = file_get_html(self::URL);
    }

    public function getPodcastLinkCount()
    {
        return array('count' => count($this->podcastLinks));
    }

    public function fetchPodcastInfo()
    {
        $podcasts = $this->html->find('a');

        foreach ($podcasts as $podcast) {
            if ($this->isValidLinkType($podcast->href, $this->getPodcastLinkPattern())) {
                $podcastLink = "https://www.ancientfaith.com" . $podcast->href;
                $podcastText = $podcast->plaintext;

                $newPodcast = new PodcastLink($podcastLink, $podcastText);

                $this->podcastLinks[] = $newPodcast;
            }
        }
    }

    public function preparePodcastHTML()
    {
        try {
            //TODO - 12/3/2022
            //TODO - remove hard-coding for only the types of podcasts we are interested in
            //TODO - well...filtering isn't the problem but hard-coding the categories probably is

            //Now only assuming that we actually have podcasts to display;
            //ensure the collection is unique and reverse sort
            if (count($this->podcastLinks) > 1) {
                $this->podcastLinks = array_unique($this->podcastLinks);
                rsort($this->podcastLinks);
                $this->podcastLinks = array_slice($this->podcastLinks, 0, 25);
            }
            //if at any time values aren't present in either array
            //then the state of this operation is to be considered very non-kosher
        } catch (UnexpectedValueException $e) {
            error_log("ERR::CANNOT RENDER HTML::err");
        }
    }

    public function saveLinksToDatabase(string $table): void
    {
        if (!$this->linkDatabaseExists()) {
            $this->fetchPodcastInfo();
            $this->preparePodcastHTML();
            $this->insertLinks($table, $this->podcastLinks);
        }
    }

    public function displayPodcastHTML()
    {
        $this->displayLinkHTML('Recent Podcasts', $this->podcastLinks);
    }


    public function displayDatabasePodcastLinks(string $table): void
    {
        $databaseLinks = $this->getAllLinks($table);
        $this->displayLinkHTML('Recent Podcasts', $databaseLinks);
    }
}


class OCADailyReadings
{
    use ValidatesReadingLinks;

    private const URL = "https://www.oca.org/readings";

    //array to hold daily scripture readings
    private $readingLinks;
    //html collected from the URL
    private $html;

    public function __construct()
    {
        $this->readingLinks = array();

        //open the url
        $this->html = file_get_html(self::URL);
    }

    public function fetchScriptureInfo()
    {
        //find all links
        $readings = $this->html->find('a');
        //now sift through them and find out which ones are for
        //the daily scriptures
        foreach ($readings as $reading) {
            if ($this->isScriptureLink($reading->href)) {
                $readingText = $reading->plaintext;
                $readingLink = "https://www.oca.org" . $reading->href;
                //create new scripture link
                $dailyReading = new ReadingLink($readingLink, $readingText);
                //add it to the array for display
                $this->readingLinks[] = $dailyReading;
            }
        }
    }

    public function displayScriptureHTML()
    {
        echo "<div class='container text-center'>";
        echo "<br />";
        echo "<h2>Daily Readings</h2>";
        echo "<br />";


        echo "<ul>";
        foreach ($this->readingLinks as $dailyScriptureReading) {
            echo $dailyScriptureReading->displayHTML();
        }
        echo "</ul>";
        echo "<br />";
        echo "</div>";
        echo "<hr />";
    }
}

class OCALivesOfSaints
{
    use ValidatesSaintLinks;

    private const URL = "https://www.oca.org/saints/lives/";

    private $saintSnippets;
    private $saintLinksSort;
    private $saintNamesSort;
    private $html;

    private $saintNames;
    private $saintLinks;

    public function __construct()
    {
        //open the url
        $this->html = file_get_html(self::URL);
        //create arrays to hold the formatted output
        $this->saintSnippets = array();
        $this->saintLinksSort = array();
        $this->saintNamesSort = array();
    }

    public function fetchSaintInfo()
    {
        //find saint name headings
        $this->saintNames = $this->html->find('article h2');
        //find links for the saints life in the article
        $this->saintLinks = $this->html->find('article a');

        //construct fully qualified links for each of the saints
        foreach ($this->saintLinks as $link) {
            if ($this->isLifeLink($link->href)) {
                $saintLink = "https://www.oca.org" . $link->href;
                $this->saintLinksSort[] = $saintLink;
            }
        }
        //populate all the saint names (plain text, remove html/styling)
        foreach ($this->saintNames as $saint) {
            $this->saintNamesSort[] = $saint->plaintext;
        }
    }

    public function prepareSaintHtml()
    {
        //sort the links
        //this order will match the second array
        asort($this->saintLinksSort);
        //We need to have the keys reset
        //to match the same integer index values as the second array.
        //We do this because we're going to iterate over the arrays
        //and create an object with each element of each array
        //corresponding to a property ex. Object(link, text) -
        //link is from array1, text is from array2.
        //removed 12/2/2022
        //array_values($this->saintLinksSort);

        //there could be varying number of saints each day
        //we will only work with the top 3
        //removed 12/1/2022, may come back in the future
        //array_splice($this->saintNamesSort, 0, 3);
        //array_splice($this->saintLinksSort, 0, 3);


        //now we will start accessing the arrays to build the display objects
        try {
            //we must ensure that the above operations were successful
            if (count($this->saintNamesSort) == count($this->saintLinksSort)) {
                for ($i = 0; $i < count($this->saintNamesSort); $i++) {
                    $saintLink = $this->saintLinksSort[$i];
                    $saintName = $this->saintNamesSort[$i];

                    $saint = new SaintLink($saintLink, $saintName);
                    $this->saintSnippets[] = $saint;
                }
            }
        } catch (UnexpectedValueException $e) {
            error_log("ERR::CANNOT RENDER HTML, ARRAY NOT DIVISIBLE BY 2, CHECK ELEMENT COUNTS::err");
        }
    }

    public function displaySaintHtml()
    {
        echo "<div class='container text-center'>";
        echo "<br />";
        echo "<h2>Daily Saints</h2>";
        echo "<br />";


        echo "<ul>";
        foreach ($this->saintSnippets as $saint) {
            echo $saint->displayHTML();
        }
        echo "</ul>";
        echo "<br />";
        echo "</div>";
        echo "<hr />";
    }
}

class BibleGateway
{
    public const URL = 'http://www.biblegateway.com';

    protected $version;
    protected $reference;
    protected $text = '';
    protected $copyright = '';
    protected $permalink;

    public function __construct($version = 'KJV')
    {
        $this->version = $version;
    }

    public function __get($name)
    {
        if ($name === 'permalink') {
            return $this->permalink = self::URL . '/passage?' . http_build_query(['search' => $this->reference, 'version' => $this->version]);
        }
        return $this->$name;
    }

    public function __set($name, $value)
    {
        if (in_array($name, ['version', 'reference'])) {
            $this->$name = $value;
            $this->searchPassage($this->reference);
        }
    }

    public function searchPassage($passage)
    {
        $this->reference = $passage;
        $this->text = '';
        $url = self::URL . '/passage?' . http_build_query(['search' => $passage, 'version' => $this->version]);
        $html = file_get_contents($url);
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);
        $xpath = new \DOMXPath($dom);
        $context = $xpath->query("//div[@class='passage-wrap']")->item(0);
        $paragraphs = $xpath->query("//div[@class='passage-wrap']//p");
        $verses = $xpath->query("//div[@class='passage-wrap']//span[contains(@class, 'text')]");
        foreach ($paragraphs as $paragraph) {
            if ($xpath->query('.//span[contains(@class, "text")]', $paragraph)->length) {
                $results = $xpath->query("//sup[contains(@class, 'crossreference') or contains(@class, 'footnote')] | //div[contains(@class, 'crossrefs') or contains(@class, 'footnotes')]", $paragraph);
                foreach ($results as $result) {
                    $result->parentNode->removeChild($result);
                }
                $this->text .= $dom->saveHTML($paragraph);
            } else {
                $this->copyright = $dom->saveHTML($paragraph);
            }
        }
        return $this;
    }

    public function getVerseOfTheDay()
    {
        $url = self::URL . '/votd/get/?' . http_build_query(['format' => 'json', 'version' => $this->version]);
        $votd = json_decode(file_get_contents($url))->votd;
        $this->text = $votd->text;
        $this->reference = $votd->reference;
        return $this;
    }
}
