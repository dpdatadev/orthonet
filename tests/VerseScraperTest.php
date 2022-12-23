<?php
declare(strict_types=1);

namespace BibleGateway;

//TODO - convert to PlainText Scraper using new classes (maybe)
//#[AllowDynamicProperties] PHP 8.2 will deprecate dynamic properties outside of stdClass
class VerseScraper
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
        $this->permalink = self::URL . '/passage?' . http_build_query(['search' => $this->reference, 'version' => $this->version]);
    }

    public function getText()
    {
        return $this->text;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function getPermaLink()
    {
        return $this->permalink;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function __get($name)
    {
        if ($name === 'permalink') {
            return $this->getPermaLink();
        }
        return $this->$name;
    }

    public function __set($name, $value)
    {
        if ($name === 'reference') {
            $this->searchPassage($this->reference);
        }

        $this->$name = $value;
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
        $url = self::URL . '/votd/get/?' . http_build_query(['format' => 'json', 'version' => $this->getVersion()]);
        $votd = json_decode(file_get_contents($url))->votd;
        $this->text = $votd->text;
        $this->reference = $votd->reference;
        return $this;
    }
}

$bibleGateway = new VerseScraper('NKJV');
//$bibleGateway->version = 'ESV';
$bibleGateway->getVerseOfTheDay();
echo $bibleGateway->getText();
//echo $bibleGateway->getPermaLink();
echo ' - ';
echo $bibleGateway->getReference();
echo ' ';
echo $bibleGateway->getVersion();
