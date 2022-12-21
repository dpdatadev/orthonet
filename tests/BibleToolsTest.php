<?php
/** @noinspection ALL */
declare(strict_types=1);

namespace ScrapingTest;

//dependencies
require_once './vendor/autoload.php';
//opensource PHP web scraping library
require_once 'simple_html_dom.php';

use function file_get_html as fetch_html;


use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\FetchMode;
use http\Exception\InvalidArgumentException;
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

trait ValidatesOrthodoxLinks
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
        if (str_contains($link, $linkType)){
            return true;
        } else {
            return false;
        }
    }
}

trait LinkElementDatabase
{
    private bool $debugFlag = false;

    public function isDebugOn(): bool
    {
        return $this->debugFlag;
    }

    public function setDebugOn(): void
    {
        $this->debugFlag = true;
    }

    public function setDebugOff(): void
    {
        if ($this->isDebugOn() === true) {
            $this->debugFlag = false;
        }
    }

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

    public function getDatabaseLinkCount(string $linkTable): int
    {
        $conn = $this->getSqlite3Connection();
        $query = "SELECT * FROM " . $linkTable;
        $num_rows = $conn->executeQuery($query)->rowCount();
        return $num_rows;
    }

    /*
    public function getDatabaseLinkCountCategory(string $linkTable, string $category): int
    {
        $conn = $this->getSqlite3Connection();
        $query = sprintf("SELECT * FROM %s WHERE category = %s", $linkTable, "'" . $category . "'");
        $num_rows = $conn->executeQuery($query)->rowCount();
        return $num_rows;
    }
    */

    public function dropCreateTable(string $table): void
    {
        $conn = $this->getSqlite3Connection();
        $conn->executeQuery('DROP TABLE IF EXISTS ' . $table . ';');
        $conn->executeQuery('CREATE TABLE ' . $table . ' (link varchar(255), text varchar(1000) null, category varchar(100) null)');
        $conn->close();
    }


    public function insertLinks(string $table, array $links, string $category): bool|int|string
    {
        $conn = $this->getSqlite3Connection();

        foreach ($links as $link) {
            $conn->insert($table, array('link' => $link->getLink(), 'text' => $link->getText(), 'category' => $category));
        }

        $lastInsertId = $conn->lastInsertId();

        $conn->close();

        return $lastInsertId;
    }

    public function getAllLinks(string $table, string $category): array
    {
        $databaseLinks = [];
        $conn = $this->getSqlite3Connection();
        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder->select('*')->from($table)->where('category = ?')->setParameter(0, $category);

        if ($this->isDebugOn() === true) {
            echo $queryBuilder->getSQL() . " (param): " . $queryBuilder->getParameter(0);
        }

        $stm = $queryBuilder->execute();
        foreach ($stm->fetchAll(FetchMode::NUMERIC) as $databaseLink) {
            $newLink = new LinkElement($databaseLink[0], $databaseLink[1]);
            $databaseLinks[] = $newLink;
        }

        return $databaseLinks;
    }
}

//A Scraper.
//Any class or abstract class could define specific configurations
//for scraping any kind of websites for whatever reason.
interface Scraper
{
    //Scraper clients will provide the configured URL
    public function getUrl(): string;
    //Scraper clients will provide the raw webpage HTML
    public function getHtml(): mixed;
    //Scraper clients will fetch information from the HTML
    //using XPATH or CSS SELECT
    public function fetchInfo(string $pageParam): void;
    //Scraper clients will be able to customize how they verify what they're searching for
    public function validatePageParam(string $pageParam): bool;
    //Scraper clients  will "prepare" the data (any customizations)
    public function prepareInfo(): void;
    //Scraper clients will be able to swap out the underlying array with a custom derived one
    public function setScrapeData(array $data): void;
    //Scraper clients will return the raw array of scrape data
    public function getScrapeData(): array;
    //Scraper clients will be able to display the scraped HTML
    public function displayScrapeHTML(): void;

}

interface ScraperFactory
{
    //The scraper factory must take instructions on how to create the desired scraper
    //Provide the type of scraper and also the scrapeUrl that will be used to construct it
    public static function createScraper(string $scraperType, string $scrapeUrl): Scraper;
}

class OrthodoxScraperFactory implements ScraperFactory
{
    public static function createScraper(string $scraperType, string $scrapeUrl): Scraper
    {
        $scraperClient = match($scraperType) {
            'Podcasts' => new AncientFaithPodcastScraper($scrapeUrl),
            'Saints' => new OCALivesOfSaints(),
            'Readings' => new OCADailyReadings()
        };

        return $scraperClient;
    }
}



//A Scraper which specializes in HTML Links
//It is neccessary to derive this class for further customization
//To work with certain types of links
abstract class LinkElementScraper implements Scraper
{
    //Display HTML table of link elements
    //whether they be freshly scraped or from the database
    use DisplaysLinks;
    //Scrape data are li/a/href link elements
    private array $scrapeLinks;

    public function __construct()
    {

    }

    public abstract function getUrl(): string;
    public abstract function getHtml(): mixed;
    public abstract function fetchInfo(string $pageParam): void;
    public abstract function prepareInfo(): void;
    public function getScrapeData(): array
    {
        return $this->scrapeLinks;
    }
    public function setScrapeData(array $data): void
    {
        $this->scrapeLinks = $data;
    }

    public abstract function displayScrapeHTML(): void;

    //Scraper clients that specialize in links will provide vaildation rules for what they're searching for on the page
    public function validatePageParam(string $pageParam): bool
    {
        $isValid = false;

        if (str_contains($pageParam, 'a') || str_contains($pageParam, 'li') || str_contains($pageParam, 'href'))
        {
            $isValid = true;
        }

        return $isValid;
    }
}

//Link Scraper which can engage with a SQLITE database
//Optionally if one wants to use SQLITE for their webscraper
//Then derive this class and work with the data
abstract class LinkElementDatabaseScraper extends LinkElementScraper
{
    //Access to cached web scrape data in SQLITE
    use LinkElementDatabase;

    //webpage to fetch
    private $scrapeUrl;
    //fetched webpage
    private $html;

    public function __construct(string $scrapeUrl)
    {
        parent::__construct();
        //if (filter_var($scrapeUrl, FILTER_VALIDATE_URL) === true) //TODO, filters not working
        if(str_contains($scrapeUrl, 'http') || str_contains($scrapeUrl, 'https'))
        {
            $this->scrapeUrl = $scrapeUrl;
            $this->html = fetch_html($scrapeUrl);
        } else {
            throw new \InvalidArgumentException("SCRAPE URL must be valid URI :: HOST/PATH REQUIRED");
        }
    }

    public function getUrl(): string
    {
        return $this->scrapeUrl;
    }

    public function getHtml(): mixed
    {
        return $this->html;
    }

    public static function saveLinksToDatabase(string $table, array $links, string $category): void
    {
        if (!self::linkDatabaseExists())
        {
            //If the database doesn't exist
            //then we definitely need to get the freshest data and load the links
            self::fetchFreshData();
            self::dropCreateTable($table);
            self::insertLinks($table, $links, $category);
        }
    }

    public function getLinksFromDatabase(string $table, string $category): array
    {
        return $this->getAllLinks($table, $category);
    }
}

//Webscraper meant to be derived by classes which will be used
//to scrape Orthodox Christian websites
//Here we only care about what is specific to the exact kind of data
//we want to scrape
class AncientFaithPodcastScraper extends LinkElementDatabaseScraper
{
    //provides different types of link patterns
    //for verification
    use ValidatesOrthodoxLinks;

    //We need a property to function as our scrape data
    private array $podcastLinks;
    public function __construct(string $scrapeUrl)
    {
        parent::__construct($scrapeUrl);
        $this->podcastLinks = array();

    }
    public function fetchInfo(string $pageParam): void
    {
        if ($this->validatePageParam($pageParam)) {
            foreach ($this->getHtml()->find($pageParam) as $podcast) {
                if ($this->isValidLinkType($podcast->href, $this->getPodcastLinkPattern())) {
                    $podcastLink = "https://www.ancientfaith.com" . $podcast->href;
                    $podcastText = $podcast->plaintext;

                    $newPodcast = new PodcastLink($podcastLink, $podcastText);

                    $this->podcastLinks[] = $newPodcast;
                }
            }
        } else {
            throw new InvalidArgumentException("PAGE PARAM must be valid"); //TODO
        }
    }
    public function prepareInfo(): void
    {
        try {
            //We want a unique and reverse sorted collection
            //And we only want to show the first 25 elements
            if (count($this->podcastLinks) > 1) {
                $this->podcastLinks = array_unique($this->podcastLinks);
                rsort($this->podcastLinks);
                $this->podcastLinks = array_slice($this->podcastLinks, 0, 25);
            }
            $this->setScrapeData($this->podcastLinks);
        } catch (UnexpectedValueException $e) {
            error_log("ERR::CANNOT RENDER HTML::err");
        }
    }

    public function getPodcastLinkCount()
    {
        return array('count' => count($this->getScrapeData()));
    }

   public function displayScrapeHTML(): void
   {
       $this->displayLinkHTML('Recent Podcasts', $this->getScrapeData());
   }

   public function displayDatabaseScrapeHTML(string $table, string $category): void
   {
       $databaseLinks = $this->getLinksFromDatabase($table, $category);
       $this->displayLinkHTML('Recent Podcasts', $databaseLinks);
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
    use ValidatesOrthodoxLinks;

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

    private function fetchFreshData() : void
    {
        $this->fetchPodcastInfo();
        $this->preparePodcastHTML();
    }

    public static function savePodCastLinksToDatabase(string $table): void
    {

        //We only load the scrape data once per day
        //When the daily database name changes out
        //For all other page hits - the links already stored (cached)
        //in the database will be displayed.
        //We are now hitting the site far less for the same data!
        if (!self::linkDatabaseExists())
        {
            //If the database doesn't exist
            //then we definitely need to get the freshest data and load the links
            self::fetchFreshData();
            self::dropCreateTable($table);
            self::insertLinks($table, self::podcastLinks, 'podcasts');
        }
    }

    public function displayPodcastHTML()
    {
        $this->displayLinkHTML('Recent Podcasts', $this->podcastLinks);
    }


    public function displayDatabasePodcastLinks(string $table): void
    {
       $databaseLinks = $this->getAllLinks($table, 'podcasts');
       $this->displayLinkHTML('Recent Podcasts', $databaseLinks);
    }
}

class OCADailyReadings
{
    //provides different types of link patterns
    //for verification
    use ValidatesOrthodoxLinks;

    //Display HTML table of link elements
    //whether they be freshly scraped or from the database
    use DisplaysLinks;

    //Access to cached web scrape data in SQLITE
    use LinkElementDatabase;

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
            if ($this->isValidLinkType($reading->href, $this->getScriptureLinkPattern())) {
                $readingText = $reading->plaintext;
                $readingLink = "https://www.oca.org" . $reading->href;
                //create new scripture link
                $dailyReading = new ReadingLink($readingLink, $readingText);
                //add it to the array for display
                $this->readingLinks[] = $dailyReading;
            }
        }
    }

    private function fetchFreshData() : void
    {
        $this->fetchScriptureInfo();
    }
    //TODO
    public function saveScriptureLinksToDatabase(string $table): void
    {

        //We only load the scrape data once per day
        //When the daily database name changes out
        //For all other page hits - the links already stored (cached)
        //in the database will be displayed.
        //We are now hitting the site far less for the same data!
        if (!$this->linkDatabaseExists())
        {
            //If the database doesn't exist
            //then we definitely need to get the freshest data and load the links
            $this->fetchFreshData();
            //$this->dropCreateTable($table);
            $this->insertLinks($table, $this->readingLinks, 'scriptures');
        }

    }

    public function displayScriptureHTML()
    {
        $this->displayLinkHTML('Recent Readings', $this->readingLinks);
    }


    public function displayScriptureDatabaseLinks(string $table): void
    {
        $databaseLinks = $this->getAllLinks($table, 'scriptures');
        $this->displayLinkHTML('Recent Readings', $databaseLinks);
    }
}

class OCALivesOfSaints
{
    //provides different types of link patterns
    //for verification
    use ValidatesOrthodoxLinks;

    //Display HTML table of link elements
    //whether they be freshly scraped or from the database
    use DisplaysLinks;

    //Access to cached web scrape data in SQLITE
    use LinkElementDatabase;

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
            if ($this->isValidLinkType($link->href, $this->getLivesOfSaintsLinkPattern())) {
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
        try {
            //TODO, rethink this
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

    private function fetchFreshData() : void
    {
        $this->fetchSaintInfo();
        $this->prepareSaintHtml();
    }

    public function saveSaintLinksToDatabase(string $table): void
    {

        //We only load the scrape data once per day
        //When the daily database name changes out
        //For all other page hits - the links already stored (cached)
        //in the database will be displayed.
        //We are now hitting the site far less for the same data!
        if (!$this->linkDatabaseExists())
        {
            //If the database doesn't exist
            //then we definitely need to get the freshest data and load the links
            $this->fetchFreshData();
            //$this->dropCreateTable($table);
            $this->insertLinks($table, $this->saintSnippets, 'saints');
        }

    }

    public function displaySaintHTML()
    {
        $this->displayLinkHTML('Daily Saints', $this->saintSnippets);
    }


    public function displaySaintDatabaseLinks(string $table): void
    {
        $databaseLinks = $this->getAllLinks($table, 'saints');
        $this->displayLinkHTML('Daily Saints', $databaseLinks);
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
