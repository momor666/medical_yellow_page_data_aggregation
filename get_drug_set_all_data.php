<?php

$db = new mysqli('localhost', 'medyp', 'mTrapok)1', 'medyp');
if ( $db->connect_errno ) {
    echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
}
$DRUG_CLASSES = [];

//INIT
$DRUG_CLASSES = $db->query("SELECT `name` from drug_class")->fetch_all(MYSQLI_NUM);
$DRUG_SETS = $db->query("SELECT `target_id` from `drug`")->fetch_all(MYSQLI_NUM);
//$DRUG_CLASSES[1028][0] = Xanthine Oxidase Inhibitors
//INIT//

require_once('vendor/autoload.php');
use Masterminds\HTML5;

$BASEURL = 'http://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=';//33d066a9-34ff-4a1a-b38b-d10983df3300

$getDrugSet = function ( $url, &$result ) use ( $BASEURL, $db ) {
    /*
     * @var string $BASEURL
     * @var mysqli object $db
     */
    $dom           = (new HTML5())->loadHTML(file_get_contents($url));
    $drugClassDivs = qp($dom, 'article.row');
    foreach ( $drugClassDivs as $drugClassDiv ) {
        $s     = qp($drugClassDiv)->find('div.results-info > h2 > a')->text();//<a href="search.cfm?query=5-alpha Reductase Inhibitor&amp;searchdb=class" id="anch_87">        5-alpha Reductase Inhibitor [EPC]                                    </a>
        $s     = preg_replace("/\t|\n/", "", trim($s));
        $regex = '/([^a-z\(]+)(\([a-zA-Z\s]+\))?(.*)/';
        preg_match_all($regex, $s, $matches);
        if ( count($matches) < 3 ) {
            continue;
        }

        $name           = strtolower(trim(strval($matches[1][0])));
        $medicationName = trim(substr(strval($matches[2][0]), 1, strval($matches[2][0]) - 2));
        $packagingTypes = preg_replace("/,\s/", ",", strtolower(trim(strval($matches[3][0]))));
        $fullname       = $s;

        $targetId = qp($drugClassDiv)->find('div.results-info > h2 > a')->attr('href');//<a href="/dailymed/drugInfo.cfm?setid=278e7dd6-7f56-479c-87d1-5a6f1a596010">
        try {
            $targetId = array_pop(explode("=", $targetId));
        } catch ( Exception $e ) {
            echo $e;
            continue;
        }
        $ndcCodes = trim(qp($drugClassDiv)->find('span.ndc-codes')->text());
        $ndcCodes = preg_replace("/\s|\t|\n|\r/", "", $ndcCodes);

        $packager = trim(qp($drugClassDiv)->find('li:contains("Packager:") > span')->text());

        $photos   = qp($drugClassDiv)->find('img[alt="Package Photo"]');
        $image[0] = "";
        $image[1] = "";
        $i        = 0;
        foreach ( $photos as $photo ) {
            if ( qp($photo)->attr('src') !== "/dailymed/images/drugimage-notavailable.gif" ) {
                $image[$i] = qp($photo)->attr('src');
            }
            $i ++;
        }

        $stmt = $db->prepare('REPLACE INTO `drug`(`name`, `medication_name`, `packaging_types`, `full_name`, `target_id`, `ndc_codes`, `packager`) VALUES (?,?,?,?,?,?,?)');

        $stmt->bind_param("sssssss", $name, $medicationName, $packagingTypes, $fullname, $targetId, $ndcCodes,
                          $packager);
        $stmt->execute();
    }

    return;
};


$result   = array();
$dsets = $DRUG_SETS;
$dsets = array_slice($dsets, 0, 2);
foreach ( $dsets as $drug_set ) {
        $page = $BASEURL . rawurlencode($drug_set[0]);
        $getDrugSet($page, $result);
}

$db->close();