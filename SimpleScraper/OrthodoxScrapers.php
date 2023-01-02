<?php

/** @noinspection ALL */
declare(strict_types=1);

namespace app\scrapers;

require_once 'SimpleScraper.php';

//Implementation code - using the SimpleScraper mini-framework
//Code in this file will be specific to creating link scrapers
//with database access and any specific validation rules that the
//scrapers will use.


//The class is final because nothing should further extend our customized scraper client
use http\Exception\InvalidArgumentException;
use http\Exception\UnexpectedValueException;

//Our Link classes will inherit this base class
use SimpleScraper\LinkElement;

//We want to use a Link Scraper with Database access
use SimpleScraper\LinkElementDatabaseScraper;

//We will create a new ScraperFactory for building our scrapers
use SimpleScraper\ScraperFactory;

//Contract for building a Simple Scraper
use SimpleScraper\Scraper;
use SimpleScraper\SQLITEManager;

//We are scraping HTML links - here are the specific classes to create them
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

//Wordpress/site article links - ie:
//orthochristian.com
//orthodoxchristiantheology.com
class ArticleLink extends LinkElement
{
}

//Entry point class to use in applications
abstract class ScrapeManager
{
    public final static function databaseExists(): bool
    {
        return SQLITEManager::linkDatabaseExists();
    }
    public final static function createDatabaseTable(): void
    {
        SQLITEManager::createDatabaseTable('web_scrape_data', 'CREATE TABLE web_scrape_data(ID INTEGER PRIMARY KEY AUTOINCREMENT, link varchar(255) null, text varchar(255) null, category varchar(255) null, create_ts timestamp not null default(CURRENT_TIMESTAMP));');
    }
    public final static function getScraper(string $scrapeType, string $scrapeUrl): Scraper
    {
        return OrthodoxLinkScraperFactory::createScraper($scrapeType, $scrapeUrl);
    }
}

//Creation code
final class OrthodoxLinkScraperFactory implements ScraperFactory
{
    public static function createScraper(string $scraperType, string $scrapeUrl): Scraper
    {
        $scraperClient = match ($scraperType) {
            'Podcasts' => new AncientFaithPodcastLinkScraper($scrapeUrl),
            'Saints' => new OCALivesOfSaintLinkScraper($scrapeUrl),
            'Readings' => new OCADailyReadingLinkScraper($scrapeUrl),
            'Articles' => new ArticleScraper($scrapeUrl),
        };

        return $scraperClient;
    }
}

//Validation code
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

//Scraping Code


//Web scrapers used for scraping links from Orthodox Websites
//and saving them to SQLITE
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
            $this->podcastLinks = array_slice($this->podcastLinks, 0, 35);
            $this->podcastLinks = array_unique($this->podcastLinks);
            rsort($this->podcastLinks);
            $this->setScrapeData($this->podcastLinks);
        } else {
            throw new UnexpectedValueException("ERR::CANNOT RENDER HTML::err::no data");
        }
    }
}

final class OCADailyReadingLinkScraper extends LinkElementDatabaseScraper
{
    //provides different types of link patterns
    //for verification
    use ValidatesOrthodoxLinks;

    //array to hold daily scripture readings
    private $readingLinks;

    public function __construct(string $scrapeUrl)
    {
        parent::__construct($scrapeUrl);

        //scrapeData()
        $this->readingLinks = array();
    }

    public function fetchInfo(string $pageParam): void
    {
        if ($this->validatePageParam($pageParam)) {
            $this->downloadHtml();
            foreach ($this->getHtml()->find($pageParam) as $reading)
            {
                if ($this->isValidLinkType($reading->href, $this->getScriptureLinkPattern()))
                {
                    $readingText = $reading->plaintext;
                    $readingLink = "https://www.oca.org" . $reading->href;

                    //create new scripture link
                    $dailyReading = new ReadingLink($readingLink, $readingText);
                    $this->readingLinks[] = $dailyReading;
                }
            }
        }
    }

    public function prepareInfo(): void
    {
        $this->setScrapeData($this->readingLinks);
    }
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
        if ($this->validatePageParam($pageParam))
        {
            $this->downloadHtml();

            //TODO, parse array of various pageParams
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

        } else {
            throw new InvalidArgumentException("PAGE PARAM must be valid");
        }

    }

    public function prepareInfo(): void
    {
        asort($this->saintLinksSort);
        if (count($this->saintNamesSort) == count($this->saintLinksSort)) {
            for ($i = 0; $i < count($this->saintNamesSort); $i++) {
                $saintLink = $this->saintLinksSort[$i];
                $saintName = $this->saintNamesSort[$i];

                $saint = new SaintLink($saintLink, $saintName);
                $this->saintSnippets[] = $saint;
            }
            $this->setScrapeData($this->saintSnippets);
        } else {
            throw new UnexpectedValueException("ERR::CANNOT RENDER HTML, MISMATCHING ELEMENTS, CHECK ELEMENT COUNTS::err");
        }
    }
}

class ArticleScraper extends LinkElementDatabaseScraper
{
    private array $articleLinks;

    public function __construct(string $scrapeUrl)
    {
        parent::__construct($scrapeUrl);
        $this->articleLinks = array();
    }

    public function fetchInfo(string $pageParam): void
    {
        if ($this->validatePageParam($pageParam))
        {
            $this->downloadHtml();
            foreach ($this->getHtml()->find($pageParam) as $articleLink)
            {
                //this site doesn't have a particular pattern for article links
                //so we'll just make sure its a fully qualified URL

                $articleHref = $articleLink->href;

                $articleText = $articleLink->plaintext;

                $article = new ArticleLink($articleHref, $articleText);

                $this->articleLinks[] = $article;
            }
        }
    }

    public function prepareInfo(): void
    {
        if (count($this->articleLinks) < 1)
        {
            throw new UnexpectedValueException("ERR::did you forget to fetch the info first?");
        } else {
            $this->setScrapeData($this->articleLinks);
        }
    }
}