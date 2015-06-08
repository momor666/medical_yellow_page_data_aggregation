<?php

$db = new mysqli('localhost', 'medyp', 'mTrapok)1', 'medyp');
if ($db->connect_errno) {
    echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
}

require_once('vendor/autoload.php');
use Masterminds\HTML5;

$BASEURL = 'http://dailymed.nlm.nih.gov/dailymed/browse-drug-classes.cfm';
$pages   = [
    'http://dailymed.nlm.nih.gov/dailymed/browse-drug-classes.cfm?page=a#listing',
    'http://dailymed.nlm.nih.gov/dailymed/browse-drug-classes.cfm?page=b-c#listing',
    'http://dailymed.nlm.nih.gov/dailymed/browse-drug-classes.cfm?page=d-g#listing',
    'http://dailymed.nlm.nih.gov/dailymed/browse-drug-classes.cfm?page=h-l#listing',
    'http://dailymed.nlm.nih.gov/dailymed/browse-drug-classes.cfm?page=m-o#listing',
    'http://dailymed.nlm.nih.gov/dailymed/browse-drug-classes.cfm?page=p-z#listing'
];

$getDrugClass = function ( $url, &$result ) use ( $BASEURL, $db ) {
    /*
     * @var string $BASEURL
     * @var mysqli object $db
     */
    $dom           = (new HTML5())->loadHTML(file_get_contents($url));
    $drugClassDivs = qp($dom, "ul#double a");
    $dc            = [];
    $stmt = $db->prepare("REPLACE INTO `drug_class` (name, drug_type_text) VALUES(?, ?)");
    foreach ( $drugClassDivs as $drugClassDiv ) {
        $s     = qp($drugClassDiv)->text();//<a href="search.cfm?query=5-alpha Reductase Inhibitor&amp;searchdb=class" id="anch_87">        5-alpha Reductase Inhibitor [EPC]                                    </a>
        $s     = trim($s);
        $regex = '/([a-zA-Z\d\s\-\r\n;]+)\[?([a-zA-Z]+)?\]?/';
        preg_match_all($regex, $s, $matches);
        if ( count($matches) < 2 ) {
            continue;
        }

        $dc['name'] = trim(strval($matches[1][0]));
        $dc['drug_type_text'] = trim(strval($matches[2][0]));
        $stmt->bind_param("ss", $dc['name'], $dc['drug_type_text']);
        $stmt->execute();

    }
    $stmt->close();

    return;
};


$result = array();
foreach ( $pages as $page ) {
    $result = $getDrugClass($page, $result);
    print_r($result);
}
$db->close();