<?php
/**
 * Get OpenStreetMap dumps, and put it at data/dumps_osm.
 * @example   php src/OSM_get.php 59470:BR  # saves data/dumps_osm/BR_wdElements.csv
 * @example   php src/OSM_get.php BR  # same effect, automatically gets relation
 * @example   php src/OSM_get.php BR_states # saves by stats (beta test feature)
 * @example   php src/OSM_get.php all # get all countries (to be implented, very slow)
 */

$saveFolder = realpath( dirname(__FILE__)."/../data" );

$URL_Overpass = 'http://overpass-api.de/api/interpreter?data=';
$TPL_Overpass = <<<EOT
[out:csv(::type,::id,wikidata,name)] ;
relation   (_MASK_RELATION_ID_) -> .c ;
.c map_to_area -> .myarea ;
(
  node (area.myarea) [wikidata];
  way (area.myarea) [wikidata];
  relation (area.myarea) [wikidata];
);
out meta ;
EOT;

if ($argc >= 2) {
  ERRprint( "\n Using terminal args" );
  array_shift($argv);
  $R = $argv;
  if (count($R)==1) {
    if ($R[0]=='all') {
      // All countries, automatic by countries.csv
      $isoCountries = getCsvFields("$saveFolder/dumps_wd/countries.csv",['iso2','osm_relid']);
      $R=[];
      foreach ($isoCountries as $nada => $r) {
        $R[] = "$r[osm_relid]:$r[iso2]";
      }

    } elseif ( preg_match('/^[a-z][a-z]$/i',$R[0]) ) {
      // One country, automatic by its ISO code
      ERRprint( "\n Using country=$R[0]" );
      $R[0] = strtoupper($R[0]);
      $isoCountries = getCsvFields("$saveFolder/dumps_wd/countries.csv",['iso2','osm_relid']);
      $ok=false;
      foreach ($isoCountries as $nada => $r) if ($r['iso2']==$R[0]) {
        $R=["$r[osm_relid]:$R[0]"]; $ok=true; break;
      }
      if (!$ok) ERRdie('args',"no country $R[0]. Use 3166-1 alfa-2 country code.");

    } elseif ( $R[0]=='BR_states' ) {
      // One country and its states, automatic by country's ISO code
      ERRprint( "\n Using datasets-br/state-codes" );
      $R = [];
      $UFs_url = 'https://raw.githubusercontent.com/datasets-br/state-codes/master/data/br-state-codes.csv';
      foreach (getCsvFields($UFs_url,['subdivision','extinction']) as $r) if ($r && empty($r['extinction'])) {
        $uf = $r['subdivision'];
        $u = "https://raw.githubusercontent.com/datasets-br/state-codes/master/data/dump_wikidata/$uf.json";
        $j = json_decode(file_get_contents($u),true);
        $qid = $j['id'];
        $R[] = $j['claims']['P402'][0]['value'] .  ":BR_$uf";
      } // for
    } else
      ERRdie('args',"invalid argument");
  } // if count=1
} else
  ERRdie('args',"please check how to use arguments.");

foreach ($R as $arg) {
  list($r,$name) = explode(':',$arg);
  $u2 = overpassGet($r);
  print "\n - $name - relation $r to Overpass...";
  $tsv = file_get_contents($u2);
  $tsv_n = substr_count( trim($tsv), "\n" );
  if ($tsv_n>1) {
    print " $tsv_n lines read. Saving and waiting... ";
    file_put_contents( "$saveFolder/dumps_osm/{$name}_wdElements.csv", tsv2csv($tsv) );
  } else print " ops, some error... last_Overpass=\n$last_Overpass\n";
  sleep(rand(1,4));
  print date('h:i:s') ;
}
die("\n");


///// LIB


function overpassGet($osm_rel_id) {
    global $URL_Overpass;
    global $TPL_Overpass;
    global $last_Overpass;
    $last_Overpass = str_replace('_MASK_RELATION_ID_',$osm_rel_id,$TPL_Overpass);
    return $URL_Overpass.urlencode($last_Overpass);
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
function ERRdie($fname,$msg0) {
  ERRset($fname,$msg0);
  die("\n");
}

function tsv2csv($full) {
  $full = preg_replace_callback(
    '/\t([^\t]*)\n/',
    funtion ($m) { $x=trim($m[1],'" '); ",\"$x\"\n"; },
    $full
  );
  return str_replace("\t", ',', $full);
}

////  LIB2 - from https://github.com/ppKrauss/php-little-utils


/**
 * Check if is a filename string, not a CSV/XML/HTML/markup string.
 * @param $input string of filename or markup code.
 * @param $flenLimit integer 0 or limit of filename length.
 * @param $keyStr string '<' for XML, "\n" for CSV.
 * @return boolean true when is filename or path string, false when markup.
 */
function isFile($input,$flenLimit=600,$keyStr='<') {
	return strrpos($input,$keyStr)==false && (!$flenLimit || strlen($input)<$flenLimit);
}

/**
 * Standard "get array from CSV", file or CSV-string.
 * CSV conventions by default options of the build-in str_getcsv() function.
 * @param $f string file (.csv) with path or CSV string (with more tham 1 line).
 * @param $flenLimit integer 0 or limit of filename length (as in isFile function).
 * @return array of arrays.
 * @use isFile() at check.php
 */
function getCsv($f,$flenLimit=600) {
	return array_map(
		'str_getcsv',
		isFile($f,$flenLimit,"\n")? file($f): explode($f,"\n")
	);
}

/**
 * Get data (array of associative arrays) from CSV file, only the listed keys.
 * @param $f string file (.csv) with path or CSV string (with more tham 1 line).
 * @param $flist array of column names, or NULL for "all columns".
 * @param $outJSON boolean true for JSON output.
 * @param $flenLimit integer 0 or limit of filename length (as in isFile function).
 * @return mix JSON or array of associative arrays.
 */
function getCsvFields($f,$flist=NULL,$outJSON=false,$flenLimit=600) {
	$t = getCsv($f,$flenLimit);
	$thead = array_shift($t);
	$r = [];
	foreach($t as $x) {
		$a = array_combine($thead,$x);
		if ($flist===NULL)
			$r[] = $a;
		elseif (isset($a[$flist[0]])) {  // ~ array_column()
			$tmp = [];  // NEED OPTIMIZE WITH array_intersection!
			foreach ($flist as $g) $tmp[$g] = $a[$g];
			$r[] = $tmp;
		}
	}
	return $outJSON? json_encode($r): $r;
}
