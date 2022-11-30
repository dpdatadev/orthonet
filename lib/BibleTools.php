<?php
declare(strict_types=1);
/**
 * Utility class used to get the "Verse of the Day" from BibleGateway.
 */

namespace Scraping;

include_once('simple_html_dom.php');
include_once('LinkElement.php');

class SaintLink extends LinkElement
{
    //Saint of the Day HTML objects

    //this is stupid - I have to upgrade to PHP 8
    protected static function str_contains($haystack, $needle): bool
    {
        if (is_string($haystack) && is_string($needle)) {
            return '' === $needle || false !== strpos($haystack, $needle);
        } else {
            return false;
        }
    }

    //utility function
    //we only want to display "lives of the saints" links
    //and not the daily troparia and kontakia (or other misc links)
    public static function isLifeLink($link): bool {
        if (self::str_contains($link, 'lives')) {
            return true;
        } else {
            return false;
        }
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

    public function __construct($version = 'ESV')
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
