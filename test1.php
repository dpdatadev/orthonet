<?php
declare(strict_types=1);

include_once('./lib/simple_html_dom.php');
include_once('./lib/LinkElement.php');

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
$url = "https://www.oca.org/saints/lives/";


//open the url
$html = file_get_html($url);

//create arrays to hold the formatted output
$saintSnippets = array();
$saintLinksSort = array();
$saintNamesSort = array();

//find saint name headings
$saintNames = $html->find('article h2');
//find links for the saints life in the article
$saintLinks = $html->find('article a');

//construct fully qualified links for each of the saints
foreach ($saintLinks as $link) {
    if (SaintLink::isLifeLink($link->href)) {
        $saintLink = "https://www.oca.org" . $link->href;
        $saintLinksSort[] = $saintLink;
    }

}
//populate all the saint names (plain text, remove html/styling)
foreach ($saintNames as $saint) {
    $saintNamesSort[] = $saint->plaintext;
}


//sort the links
//this order will match the second array
asort($saintLinksSort);
//We need to have the keys reset
//to match the same integer index values as the second array.
//We do this because we're going to iterate over the arrays
//and create an object with each element of each array
//corresponding to a property ex. Object(link, text) -
//link is from array1, text is from array2.
array_values($saintLinksSort);

//there could be varying number of saints each day
//we will only work with the top 3
array_splice($saintNamesSort, 0, 3);
array_splice($saintLinksSort, 0, 3);

//testing
//print_r($saintLinksSort);
//print_r($saintNamesSort);

//now we will start accessing the arrays to build the display objects
try {
    //we must ensure that the above operations were successful
    if (count($saintNamesSort) == count($saintLinksSort)) {
        for ($i = 0; $i < count($saintNamesSort); $i++) {
           $saintLink = $saintLinksSort[$i];
           $saintName = $saintNamesSort[$i];

           $saint = new SaintLink($saintLink, $saintName);
           $saintSnippets[] = $saint;
        }
    }
} catch (\http\Exception\UnexpectedValueException $e) {
    error_log("ERR::CANNOT RENDER HTML, ARRAY NOT DIVISIBLE BY 2, CHECK ELEMENT COUNTS::err");
}

echo "<div class='container'>";
echo "<h2>Daily Saints</h2>";


echo "<ul>";
foreach ($saintSnippets as $saint) {
    echo $saint->displayHTML();
}
echo "</ul>";
echo "</div>";







