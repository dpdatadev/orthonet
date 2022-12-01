<?php
declare(strict_types=1);


namespace Scraping;

//opensource PHP web scraping library
include_once('simple_html_dom.php');

class LinkElement
{
    protected string $link;
    protected string $text;

    public function __construct(string $link, string $text)
    {
        $this->link = $link;
        $this->text = $text;
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

    //this is stupid - I have to upgrade to PHP 8
    protected static function str_contains($haystack, $needle): bool
    {
        if (is_string($haystack) && is_string($needle)) {
            return '' === $needle || false !== strpos($haystack, $needle);
        } else {
            return false;
        }
    }

    public function __toString(): string
    {
        return "::" . $this->getLink() . "::" . $this->getText() . "::";
    }
}

//Saint of the Day HTML objects
class SaintLink extends LinkElement
{
    //utility function
    //we only want to display "lives of the saints" links
    //and not the daily troparia and kontakia (or other misc links)
    public static function isLifeLink($link): bool
    {
        if (self::str_contains($link, 'lives')) {
            return true;
        } else {
            return false;
        }
    }
}

class OCALivesOfSaints
{
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
            if (SaintLink::isLifeLink($link->href)) {
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
        array_values($this->saintLinksSort);

        //there could be varying number of saints each day
        //we will only work with the top 3
        array_splice($this->saintNamesSort, 0, 3);
        array_splice($this->saintLinksSort, 0, 3);


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
        } catch (\http\Exception\UnexpectedValueException $e) {
            error_log("ERR::CANNOT RENDER HTML, ARRAY NOT DIVISIBLE BY 2, CHECK ELEMENT COUNTS::err");
        }
    }

    public function displaySaintHtml()
    {
        echo "<div class='container'>";
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
    const URL = 'http://www.biblegateway.com';

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
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);
        $xpath = new \DOMXPath($dom);
        $context = $xpath->query("//div[@class='passage-wrap']")->item(0);
        $pararaphs = $xpath->query("//div[@class='passage-wrap']//p");
        $verses = $xpath->query("//div[@class='passage-wrap']//span[contains(@class, 'text')]");
        foreach ($pararaphs as $paragraph) {
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