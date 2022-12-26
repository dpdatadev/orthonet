<?php

//TODO (12/25/2022) - replace comments with PHPDocs

/** @noinspection ALL */
declare(strict_types=1);

namespace SimpleScraper;

//dependencies
require_once './vendor/autoload.php';
//opensource PHP web scraping library
require_once 'simple_html_dom.php';

use ReflectionObject as ObjectInspector;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;


use Doctrine\DBAL\FetchMode;
use http\Exception\InvalidArgumentException;
use http\Exception\UnexpectedValueException;

use function file_get_html as fetch_html;
//use function PHPUnit\Framework\objectHasAttribute;

//#[AllowDynamicProperties] PHP 8.2 will deprecate dynamic properties outside of stdClass
class LinkElement
{
    protected string $link;
    protected string $text;
    //handle dynamic properties from HTML
    protected array $overloadedData;

    public function __construct(string $link, string $text)
    {
        if (empty($link) || $link === '') {
            $this->link = 'no value given';
        } else {
            $this->link = $link;
        }

        if (empty($text) || $text === '') {
            $this->text = 'no value given';
        } else {
            $this->text = $text;
        }

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

trait DisplaysLinks
{
    protected function displayLinkHTML(string $displayName, array $links): void
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

trait ValidatesOrthodoxLinks
{
    //TODO, there's a better way to handle uri validation..
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

//Daily generated SQLITE database to hold web scrape data
//Somewhat of a cache for the frontend -
define('DAILY_DATABASE', "link_database_" . date('y_m_d') . ".db");

abstract class SQLITEManager
{
    public function __construct()
    {
    }

    public final static function getSqlite3Connection(): Connection
    {
        $attrs = ['driver' => 'pdo_sqlite', 'path' => DAILY_DATABASE];
        return DriverManager::getConnection($attrs);
    }

    public final static function createDatabaseTable(string $table, string $columns): void
    {
        self::dropCreateTable($table, $columns);
    }
    public final static function linkDatabaseExists(): bool
    {
        return file_exists(DAILY_DATABASE);
    }

    private static function dropCreateTable(string $table, string $tableSQL): void
    {
        $conn = self::getSqlite3Connection();
        $conn->executeQuery('DROP TABLE IF EXISTS ' . $table . ';');
        $conn->executeQuery($tableSQL);
        $conn->close();
    }
}

trait LinkElementDatabaseAccess
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

    private static function getSqlite3Connection(): Connection
    {
        return SQLITEManager::getSqlite3Connection();
    }

    protected static function linkDatabaseExists(): bool
    {
        return SQLITEManager::linkDatabaseExists();
    }

    protected function getDatabaseLinkCount(string $linkTable): int
    {
        $conn = self::getSqlite3Connection();
        $query = "SELECT * FROM " . $linkTable;
        $num_rows = $conn->executeQuery($query)->rowCount();
        return $num_rows;
    }


    protected static function insertLinks(string $table, array $links, string $category): bool|int|string
    {
        $conn = self::getSqlite3Connection();

        foreach ($links as $link) {
            $conn->insert($table, array('link' => $link->getLink(), 'text' => $link->getText(), 'category' => $category));
        }

        $lastInsertId = $conn->lastInsertId();

        $conn->close();

        return $lastInsertId;
    }

    protected function getAllLinks(string $table, string $category): array
    {
        $databaseLinks = [];
        $conn = $this->getSqlite3Connection();
        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder->select('link, text')->from($table)->where('category = ?')->setParameter(0, $category);

        if ($this->isDebugOn() === true) {
            echo $queryBuilder->getSQL() . " (param): " . $queryBuilder->getParameter(0) . PHP_EOL;
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
    //Scrapers will download the html of a webpage only when invoked
    public function downloadHtml(): void;
    //Scraper clients will provide the Simple Dom obejct to interact with HTML
    public function getHtml(): mixed;
    //Scrapers will return the raw plaintext HTML from the SimpleDOM object
    public function getRawHtml(): mixed;
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

final class OrthodoxScraperFactory implements ScraperFactory
{
    public static function createScraper(string $scraperType, string $scrapeUrl): Scraper
    {
        $scraperClient = match ($scraperType) {
            'Podcasts' => new AncientFaithPodcastLinkScraper($scrapeUrl),
            'Saints' => new OCALivesOfSaintLinkScraper(),
            'Readings' => new OCADailyReadingLinkScraper()
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

    //webpage to fetch
    protected $scrapeUrl;

    //fetched webpage object
    protected $html;

    //Scrape data are li/a/href link elements
    //Client classes will have assign scraped data they customize to this field
    //using setScrapeData(array)
    private array $scrapeLinks;

    public function __construct(string $scrapeUrl)
    {
        //if (filter_var($scrapeUrl, FILTER_VALIDATE_URL) === true) //TODO, filters not working
        if (str_contains($scrapeUrl, 'http') || str_contains($scrapeUrl, 'https')) {
            $this->scrapeUrl = $scrapeUrl;
        } else {
            throw new \InvalidArgumentException("SCRAPE URL must be valid URI :: HOST/PATH REQUIRED");
        }
    }

    public function getUrl(): string
    {
        return $this->scrapeUrl;
    }

    //TODO (12/25/2022)
    public function getRawHtml(): mixed
    {
        if ((new ObjectInspector($this->getHtml()))->hasProperty('plaintext'))
        {
            return $this->getHtml()->plaintext;
        } else {
            throw new \Exception("Simple DOM error, no HTML to display! Make sure to call downloadHtml() first.");
        }
    }

    public function getHtml(): mixed
    {
        return $this->html;
    }

    public function downloadHtml(): void
    {
        $this->html = fetch_html($this->getUrl());
    }
    abstract public function fetchInfo(string $pageParam): void;
    abstract public function prepareInfo(): void;
    public function getScrapeData(): array
    {
        return $this->scrapeLinks;
    }
    public function setScrapeData(array $data): void
    {
        $this->scrapeLinks = $data;
    }

    abstract public function displayScrapeHTML(): void;

    //Scraper clients that specialize in links will provide vaildation rules for what they're searching for on the page
    public function validatePageParam(string $pageParam): bool
    {
        $isValid = false;

        if (str_contains($pageParam, 'a') || str_contains($pageParam, 'li') || str_contains($pageParam, 'href')) {
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
    use LinkElementDatabaseAccess;

    public function __construct(string $scrapeUrl)
    {
        parent::__construct($scrapeUrl);
    }

    public function saveLinksToDatabase(string $table, string $category): void
    {
        $links = $this->getScrapeData();
        if (empty($links) || (count($links) < 1)) {
            throw new UnexpectedValueException("ERR::NO DATA TO INSERT TO DATABASE PROVIDED::UnexpectedValue::" . PHP_EOL);
        } else {
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
//The class is final because nothing should further extend our customized scraper client
final class AncientFaithPodcastLinkScraper extends LinkElementDatabaseScraper
{
    //provides different types of link patterns
    //for verification
    use ValidatesOrthodoxLinks;

    //We need a property to function as our scrape data
    //We will pass this to setScrapeData($podcastLinks)
    private array $podcastLinks;
    public function __construct(string $scrapeUrl)
    {
        parent::__construct($scrapeUrl);
        $this->podcastLinks = array();
    }
    public function fetchInfo(string $pageParam): void
    {
        if ($this->validatePageParam($pageParam)) {
            $this->downloadHtml();
            foreach ($this->getHtml()->find($pageParam) as $podcast) {
                if ($this->isValidLinkType($podcast->href, $this->getPodcastLinkPattern())) {
                    $podcastLink = "https://www.ancientfaith.com" . $podcast->href;
                    $podcastText = $podcast->plaintext;

                    $newPodcast = new PodcastLink($podcastLink, $podcastText);

                    $this->podcastLinks[] = $newPodcast;
                }
            }
        } else {
            throw new InvalidArgumentException("PAGE PARAM must be valid");
        }
    }
    public function prepareInfo(): void
    {
        //We want a unique and reverse sorted collection
        //And we only want to show the first 25 elements
        if (count($this->podcastLinks) > 1)
        {
            $this->podcastLinks = array_unique($this->podcastLinks);
            rsort($this->podcastLinks);
            $this->podcastLinks = array_slice($this->podcastLinks, 0, 25);
            $this->setScrapeData($this->podcastLinks);
        } else {
            throw new UnexpectedValueException("ERR::CANNOT RENDER HTML::err::no data");
        }
    }

    public function getPodcastLinkCount()
    {
        return array('count' => count($this->getScrapeData()));
    }

    public function displayScrapeHTML(): void
    {
        $links = $this->getScrapeData();
        if(count($links) < 1)
        {
            throw new UnexpectedValueException("ERR::No scraped data -- did you fetch and prepare?::");
        } else {
            $this->displayLinkHTML('Recent Podcasts', $this->getScrapeData());
        }
    }

    public function displayDatabaseScrapeHTML(string $table): void
    {
        $databaseLinks = $this->getLinksFromDatabase($table, 'podcasts');
        if (count($databaseLinks) < 1)
        {
            throw new UnexpectedValueException("ERR::No Database Links for category [podcasts]::");
        } else {
            $this->displayLinkHTML('Recent Podcasts', $databaseLinks);
        }
    }
}

class OCADailyReadingLinkScraper //extends LinkElementDatabaseScraper
{
    //provides different types of link patterns
    //for verification
    use ValidatesOrthodoxLinks;

    //TODO
}

final class OCALivesOfSaintLinkScraper extends LinkElementDatabaseScraper
{
    //provides different types of link patterns
    //for verification
    use ValidatesOrthodoxLinks;

    private $saintSnippets; //setScrapeData
    private $saintLinksSort;
    private $saintNamesSort;

    private $saintNames;
    private $saintLinks;

    public function __construct(string $scrapeUrl)
    {
        parent::__construct($scrapeUrl);

        //create arrays to hold the formatted output
        //this scraper requires a good bit of formatting
        $this->saintSnippets = array();
        $this->saintLinksSort = array();
        $this->saintNamesSort = array();
    }

    public function fetchInfo(string $pageParam): void
    {
        $this->saintNames = $this->getHtml()->find('article h2');
        $this->saintLinks = $this->getHtml()->find('article a');

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

    public function prepareInfo(): void
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

        //we must ensure that the above operations were successful
        if (count($this->saintNamesSort) == count($this->saintLinksSort)) {
            for ($i = 0; $i < count($this->saintNamesSort); $i++) {
                $saintLink = $this->saintLinksSort[$i];
                $saintName = $this->saintNamesSort[$i];

                $saint = new SimpleScraper\SaintLink($saintLink, $saintName);
                $this->saintSnippets[] = $saint;
            }

            $this->setScrapeData($this->saintSnippets);
        } else {
            throw new UnexpectedValueException("ERR::CANNOT RENDER HTML, ARRAY NOT DIVISIBLE BY 2, CHECK ELEMENT COUNTS::err");
        }
    }

    public function getSaintLinkCount()
    {
        return array('count' => count($this->getScrapeData()));
    }

    public function displayScrapeHTML(): void
    {
        $links = $this->getScrapeData();
        if(count($links) < 1)
        {
            throw new UnexpectedValueException("ERR::No scraped data -- did you fetch and prepare?::");
        } else {
            $this->displayLinkHTML('Daily Saints', $this->getScrapeData());
        }
    }

    public function displayDatabaseScrapeHTML(string $table): void
    {
        $databaseLinks = $this->getLinksFromDatabase($table, 'saints');
        if (count($databaseLinks) < 1)
        {
            throw new UnexpectedValueException("ERR::No Database Links for category [saints]::");
        } else {
            $this->displayLinkHTML('Daily Saints', $databaseLinks);
        }
    }
}
