<?php

require_once('vendor/autoload.php');
use Masterminds\HTML5;

$BASEURL         = 'http://p-o.co.uk';
$BASEURLPOSTCODE = 'http://p-o.co.uk/ch/siccode/85200/postcode/';

$getCompany = function ( $url, &$result ) use ( $BASEURL ) {
    /*
     * @var string $BASEURL
     */
    $dom         = (new HTML5())->loadHTML(file_get_contents($url));
    $companyDivs = qp($dom, "div#content > div");
    $links       = [];
    foreach ( $companyDivs as $companyDiv ) {
        $links[] = qp($companyDiv, 'a')->attr('href');
    }
    $links = array_slice($links, 2);
    foreach ( $links as $link ) {
        $url          = $BASEURL . $link;
        $dom          = (new HTML5())->loadHTML(file_get_contents($url));
        $companyName  = qp($dom, 'th:contains(Company name)')->next()->text();
        $addressSpans = qp($dom, 'td[itemprop="address"] span')->toArray();
        $result[]     = [
            $companyName,
            $addressSpans[0]->textContent,
            $addressSpans[1]->textContent,
            $addressSpans[2]->textContent,
            $url
        ];
    }

    return;
};


$result = array();
for ( $i = 1;$i <= 2;$i ++ ) {
    $getCompany($BASEURLPOSTCODE . 'b' . $i, $result);
}

echo implode(','.['Company Name', 'Street Address', 'Locality', 'Region', 'Url']);
foreach ( $result as $fields ) {
    print_r($fields);
}
