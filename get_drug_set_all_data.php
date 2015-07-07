<?php

$db = new mysqli( 'localhost','medyp','mTrapok)1','medyp' );
if ( $db->connect_errno ) {
	echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
}
$DRUG_CLASSES = [ ];

//INIT
$DRUG_CLASSES = $db->query( "SELECT `name` from drug_class" )->fetch_all( MYSQLI_NUM );
$DRUG_SETS    = $db->query( "SELECT `target_id` from `drug`" )->fetch_all( MYSQLI_NUM );
//$DRUG_CLASSES[1028][0] = Xanthine Oxidase Inhibitors
//INIT//

require_once( 'vendor/autoload.php' );
use Masterminds\HTML5;

$BASEURL = 'http://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=';//33d066a9-34ff-4a1a-b38b-d10983df3300

$getDrugSet = function ( $url,$target_id ) use ( $BASEURL,$db ) {
	/*
	 * @var string $BASEURL
	 * @var mysqli object $db
	 */
	$SITEROOT = 'http://dailymed.nlm.nih.gov/';
	$dom      = ( new HTML5() )->loadHTML( file_get_contents( $url ) );
	//we need category, highlight_prescribe, indications_usage, principal_disp_panel, drug_interations, image1, image2
	$contentDiv = qp( $dom,'div.container' );
	/*
	 * $('#category').text()
	 * $('#Highlights').text()
	 * $($('a:contains("1 INDICATIONS & USAGE")')[1]).next().next('div').text()
	 * $($('a:contains("PACKAGE LABEL.PRINCIPAL DISPLAY PANEL")')[0]).next().next('div').html()
	 * $($('a[data-photo-type]')[0]).attr('href')
	 * $($('a[data-photo-type]')[1]).attr('href')
	 */

	$category   = qp( $contentDiv )->find( '#category' )->text();
	$highlight  = qp( $contentDiv )->find( '#Highlights' )->text();
	$temp       = qp( $contentDiv )->find( 'a:contains("1 INDICATIONS & USAGE")' )->toArray();
	$indi_usage = "";
	if ( is_array( $temp ) && ( count( $temp ) > 0 ) ) {
		$indi_usage = qp( $temp[1] )->next( 'div' )->text();
	}
	$temp            = qp( $contentDiv )->find( 'a:contains("PACKAGE LABEL.PRINCIPAL DISPLAY PANEL")' )->toArray();
	$prin_disp_panel = "";
	if ( is_array( $temp ) ) {
		$prin_disp_panel = qp( $temp[0] )->next( 'div' )->html();
	}
	$temp      = qp( $contentDiv )->find( 'a:contains("7 DRUG INTERACTIONS")' )->toArray();
	$drug_inte = "";
	if ( is_array( $temp ) && ( count( $temp ) > 0 ) ) {
		$drug_inte = qp( $temp[1] )->next( 'div' )->text();
	}
	$temp   = qp( $contentDiv )->find( 'a[data-photo-type]' )->toArray();
	$image1 = "";
	if ( is_array( $temp ) ) {
		$image1 = $SITEROOT . 'dailymed/' . qp( $temp[0] )->attr( 'href' );
	}
	$image2 = "";
	if ( count( $temp ) > 0 ) {
		$image2 = qp( $temp[1] )->attr( 'href' );
	}

	$stmt = $db->prepare( 'UPDATE `medyp`.`drug` set `category` = ?, `highlight_prescribe` = ?, `indications_usage` = ?, `principal_display_panel` = ?, `drug_interactions` = ?, `image_1` = ?, `image_2` = ? WHERE `target_id` =  ?' );
	array_map( function ( &$v ) {
		$v = trim($v);
		$v = preg_replace( '/\n\s*\n/',"\n",$v );
		$v = preg_replace( '/^[^[:alnum:]]*/','',$v );
	},
		array( &$category,&$highlight,&$indi_usage,&$prin_disp_panel,&$drug_inte,&$image1,&$image2,&$target_id ) );
	$stmt->bind_param( "ssssssss",$category,$highlight,$indi_usage,$prin_disp_panel,$drug_inte,$image1,$image2,$target_id );
	$stmt->execute();

	return;
};


$dsets  = $DRUG_SETS;
$dsets  = array_slice( $dsets,0,1 );
foreach ( $dsets as $drug_set ) {
	$page = $BASEURL . rawurlencode( $drug_set[0] );
	$getDrugSet( $page,$drug_set[0] );
}

$db->close();