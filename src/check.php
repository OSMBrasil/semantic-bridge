<?php
// usage: php src/check.php > data/lookup.csv &
//        ps ax | grep php

// CONFIGS
  $urlWd_tpl = 'https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=';
  $urlOsm_tpl = 'http://polygons.openstreetmap.fr/get_geojson.py?id=';
  $UF=''; $localCsv = true;  $stopAt=2000;

$saveFolder = realpath( dirname(__FILE__)."/../data" );
$url = $localCsv
     ? "$saveFolder/dump/wikidataP402.csv"
     : 'https://github.com/OSMBrasil/semantic-bridge/raw/master/data/dump/wikidataP402.csv'
;

$newUrl = "$saveFolder/dump/wikidataP402.new.csv"; // looking for a new download:
if ($localCsv && file_exists($newUrl)) $url = $newUrl; else $newUrl='';

// cols 0=subdivision, 1=name_prefix, 2=name, 3=id, 4=idIBGE, 5=wdId, 6=lexLabel
$item_idx=0; //$wdId_idx = 5;  $lexLabel_idx = 6;


$modo = ($argc>=2)?    ( ($argv[1]=='sort')? 'SORT': 'WIKIDATA'  ): 'WIKIDATA';
ERRprint( "\n USANDO $modo $url" );

// LOAD DATA:
$R = []; // [fname]= wdId
if (($handle = fopen($url, "r")) !== FALSE) {
   for($i=0; ($row=fgetcsv($handle)) && (!$stopAt || $i<$stopAt); $i++)
      if ( $i && isset($row[0]) )
         $R[ wdId_toInt($row[$item_idx]) ] = [$row[$item_idx]];
} else
   exit("\nERRO ao abrir planilha das cidades em \n\t$url\n");

ksort($R,SORT_NUMERIC);

if ($modo=='SORT') {
  print "wdId";
  foreach($R as $wdId=>$r) if ($wdId) print "\nQ$wdId";
  ERRprint("\n");
  die("\n");
}

//if ($modo=='FIX-ERR') foreach($R as $fname=>$wdId) {
//  if ( filesize("$saveFolder/dump_wikidata/$fname.$ext")>50 ) unset($R[$fname]);
//}


// WGET AND SAVE JSON:
$i=1;
$n=count($R);
$ERR=[];
$todayIso = substr(date(DATE_ATOM),0,10);

switch($modo) {

case '':
case 'WIKIDATA':
case 'DFT':
	print "wdId,osm_type,osm_id";
	foreach($R as $id=>$r) {
          if (rand (1,20)==1) {ERRprint("\nsleep..."); sleep(4);} // to avoid wikidata see as attack
	  $wdId = "Q$id";
	  ERRprint("\n\t($i of $n) $wdId ");
	  $osmR_id = getWdArray_fromWdUrl($wdId);
	  if ($osmR_id>1) {
		ERRprint("$osmR_id!");
		$wd2 = getWd_fromOsmUrl($osmR_id,$wdId); // gera erro la dentro
		if (trim($wd2) && $wd2==$wdId) {
			ERRprint(" CHECKED!");
			print "\n$wdId,R,$osmR_id";
		} elseif (trim($wd2)) ERRset($wdId,"OSM-WdID of R $osmR_id is not same, returned $wd2");
	  }
	  $i++;
	}
	break;

case 'OSM':
	ERRprint("\n--ERR UNDER CONSTRUCTION\n");
	die("\n");
	break;

default:
	ERRprint("\n Modo $modo DESCONHECIDO\n");
	die("\n");

} // end switch


if (count($ERR)) {
	$f = "$saveFolder/lookup_errors_$modo.csv";
	ERRprint("\n --- ERRORS at $f ---\n");
	if (file_exists($f)) unlink($f);
        $ERR[0]="wdId,message".$ERR[0];
	file_put_contents ($f , $ERR);
}


///// LIB

function wdId_toInt($str) {
 return preg_replace('#^(Q|https?://(www\.)?wikidata.org/(entity|wiki)/Q)#', '', $str);
}

function ERRprint($msg) {
  fwrite(STDERR, $msg);
}

function ERRset($fname,$msg0) {
   global $ERR;
   $msg = "ERROR, $msg0 for $fname.";
   ERRprint($msg);
   $ERR[] = "\n$fname,\"$msg\"";
   return 0;
}

function getWd_fromOsmUrl($osmid,$wdId,$osmtype='R') {
  $f = "https://nominatim.openstreetmap.org/details.php?format=json&osmtype=$osmtype&osmid=$osmid";
  $jstr = @file_get_contents($f);
  $j = json_decode( $jstr, JSON_BIGINT_AS_STRING|JSON_OBJECT_AS_ARRAY);
  if ($jstr) {
	  if (isset($j['extratags']['wikidata']) ) {
	    $x = $j['extratags']['wikidata'];
	    return $x? $x: ERRset($wdId,"OSM ($osmtype $osmid) with null value");
	  } else
	    return ERRset($wdId,"OSM ($osmtype $osmid) with no extratags/wikidata");
  } else
  	return ERRset($wdId,"OSM empty json");
}

function getWdArray_fromWdUrl($wdId) {
  global $urlWd_tpl;
  $f = "$urlWd_tpl$wdId";
  $jstr = @file_get_contents("$urlWd_tpl$wdId");
  if ($jstr) {
	$j = json_decode( $jstr, JSON_BIGINT_AS_STRING|JSON_OBJECT_AS_ARRAY);
	if ( !isset($j['entities']) )  return ERRset($wdId,"Wikidata with no entities");
	$ks=array_keys($j['entities']);
	$j = $j['entities'][$ks[0]];
	if (isset($j['claims']['P402'][0]['mainsnak']['datavalue']['value']) ) {
		$x= $j['claims']['P402'][0]['mainsnak']['datavalue']['value'];
		return $x? $x: ERRset($wdId,"Wikidata with P402 but 0 null value");
	} else
		return ERRset($wdId,"Wikidata with no P402 claim");
  } else
  	return ERRset($wdId,"empty json");
  return 0;
}

?>

