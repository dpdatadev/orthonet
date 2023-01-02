<?php

/*
 * Abstract/Synopsis
 * dpdatadev
 * 12/21/2022
 *
 * The main API/tool that this mini framework is built on is --> simple_html_dom PHP library
 * a single class, single file, PHP HTML library
 *
 * I want an easy and extensible set of classes that will use simple_html_dom to do these things:
 *
 * Scrape Links, Images, and Text from websites (I intend to scrape Orthodox Christian websites in my projects)
 *(^^Right now only Link and PlainText are supported)
 *
 * I want the tool to be able to display scraped data, return scraped data as objects, and be able to
 * store and retrieve the web scrape data inside a database. For now that is a small and simple SQLITE database.
 *
 * I could use simple_html_dom to procedurally scrape the data, repeat the steps for each site, and use
 * the PHP SQLITE3 library, maybe write a few functions, to store the data.
 *
 * If I could write a reusable set of classes to make it easy to drop into a larger project and is easily extensible-
 * that would be ideal.
 *
 * A web scraper is an object that can download a html webpage and extract information from it then either:
 * display the data
 * return the raw data
 * or store and retrieve the data in a relational database.
 *
 * A LinkElement is a class that represents an HTML link tag that consists of an HREF URI and a text element that describes the link.
 * A LinkElement web scraper is a scraper that does the things mentioned above but specifically for links in an HTML web page.
 *
 * The trait "LinkElementDatabase" provides functions for interacting with a (SQLITE) database.
 *
 * With the nature of the web pages I'm scraping for my website, I just need to fresh scrape data a few times a day.
 * The majority of the web requests being sent to the page can return data that has already been scraped and stored in the database.
 * It doesn't have to be a relational database, it could be noSQL, or it could be Redis.
 * But nonetheless - something that caches the data and doesn't require scraping the data fresh on every page request.
 *
 * I plan on writing some commands that can be called a few times a day to load fresh data and shouldn't manage
 * conditionally loading the database during the HTTP request lifecycle - only reading and displaying whatever data is available
 * in the database.
 *
 * The mini framework allows for scraper factories to be defined that will allow the system
 * to know how to configure and instantiate your custom scrapers of what kind.
 *
 * The custom scraper client that you implement will need to decide how to display or use the data
 * in whatever particular way you see fit.
 *
 * The utility classes provided just prevent you from having to make the same boilerplate code
 * for:
 * 1. Connecting to the webpage
 * 2. Downloading the webpage
 * 3. Returning the contents of the webpage
 * 4. Finding, validating, and returning certain elements of the webpage (i.e Links)
 * 5. Saving the elements to a database table
 * 6. Retrieving the elements from a database table
 *
 * Whatever you do from there is for you to focus on HOW the data should be used in your application.
 */


//TODO (12/25/2022) - replace comments with PHPDocs
//TODO install composer version of simple_html_dom
//TODO -- reading:
/*
 * https://blog.eleven-labs.com/en/dependency-injection/
 * https://getcomposer.org/doc/05-repositories.md#path
 * https://hackthestuff.com/article/create-package-for-php-with-composer#:~:text=Create%20package%20for%20PHP%20with%20composer%201%20composer.json,package%20in%20your%20Project%20...%204%20Conclusion%20
 */

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

trait DisplaysLinks
{
    protected function displayLinkHTML(string $displayName, array $links): void
    {
        if(count($links) < 1)
        {
            throw new UnexpectedValueException("ERR::NO LINKS TO DISPLAY!::");
        } else {
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

    protected function getDatabaseLinkCount(string $table, string $category): int
    {
        $conn = self::getSqlite3Connection();
        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder->select('link, text')->from($table)->where('category = ?')->setParameter(0, $category);
        $stmt = $queryBuilder->execute();
        $num_rows = 0;

        if ($this->isDebugOn() === true) {
            echo $queryBuilder->getSQL() . " (param): " . $queryBuilder->getParameter(0) . PHP_EOL;
        }

        foreach($stmt->fetchAll(FetchMode::NUMERIC) as $row)
        {
            if ($row !== null)
            {
                $num_rows += 1;
            }
        }

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
    //Scrapers will be able to clear out HTML values from memory
    //This can happen either on demand via this function and/or
    //be part of the destructor() for the implementing class.
    public function clearHtml(): void;
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
    public function displayScrapeHTML(string $displayName): void;
}

interface ScraperFactory
{
    //The scraper factory must take instructions on how to create the desired scraper
    //Provide the type of scraper and also the scrapeUrl that will be used to construct it
    public static function createScraper(string $scraperType, string $scrapeUrl): Scraper;
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

    //Some of the webpages could be large
    //Clean up memory
    public function __destruct()
    {
        if ($this->getHtml() !== null || !empty($this->getHtml()))
        {
            $this->clearHtml();
        }
    }

    public function clearHtml(): void
    {
        if ((new ObjectInspector($this->getHtml()))->hasMethod('clear'))
        {
            $this->getHtml()->clear();
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

    public function displayScrapeHTML(string $displayName): void
    {
        $this->displayLinkHTML($displayName, $this->getScrapeData());
    }

    public function getLinkCount()
    {
        return array('count' => count($this->getScrapeData()));
    }

    //Scraper clients that specialize in links will provide vaildation rules for what they're searching for on the page
    public function validatePageParam(string $pageParam): bool
    {
        $isValid = false;

        if (str_contains($pageParam, 'a') || str_contains($pageParam, 'li') || str_contains($pageParam, 'href') || str_contains('article')) {
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

    public function saveLinksToDatabase(string $category, string $table = 'web_scrape_data',): void
    {
        $links = $this->getScrapeData();
        if (empty($links) || (count($links) < 1)) {
            throw new UnexpectedValueException("ERR::NO DATA TO INSERT TO DATABASE PROVIDED::UnexpectedValue::" . PHP_EOL);
        } else {
            self::insertLinks($table, $links, $category);
        }
    }

    public function getLinksFromDatabase(string $category, string $table = 'web_scrape_data',): array
    {
        return $this->getAllLinks($table, $category);
    }

    public function displayDatabaseScrapeHTML(string $displayName, string $category): void
    {
        $this->displayLinkHTML($displayName, $this->getLinksFromDatabase($category));
    }

    public function getTableCount(string $table, string $category)
    {
        return $this->getDatabaseLinkCount($table, $category);
    }
}