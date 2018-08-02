Source code of "checking tools", in many programming languages.

## Preparing dumps_wd
Wikidata [dumps](https://en.wikipedia.org/wiki/Database_dump).

### countries.csv
Each country (P17) by its [código ISO 3166-1 alfa-2 (P297)](https://www.wikidata.org/wiki/Property:P297)
and  [ID de relação OpenStreetMap (P402)](https://www.wikidata.org/wiki/Property:P402).

```sh
curl -o data/dumps_wd/countries.csv -G 'https://query.wikidata.org/sparql' \
     --header "Accept: text/csv"  \
     --data-urlencode query='
 SELECT DISTINCT ?iso2 ?qid ?osm_relid ?itemLabel
 WHERE {
  ?item wdt:P297 _:b0.
  BIND(strafter(STR(?item),"http://www.wikidata.org/entity/") as ?qid).
  OPTIONAL { ?item wdt:P1448 ?name .}
  OPTIONAL { ?item wdt:P297 ?iso2 .}
  OPTIONAL { ?item wdt:P402 ?osm_relid .}
  SERVICE wikibase:label { bd:serviceParam wikibase:language "en,[AUTO_LANGUAGE]" . }
 }
 ORDER BY ?iso2
'
```

### country-full list of itens

```sparql
SELECT DISTINCT ?qid ?osm_relid ?wgs84 ?codIBGE ?itemLabel
WHERE {
  ?item wdt:P625 _:b0.
  ?item wdt:P31*/wdt:P279*/wdt:P17 wd:$_QID_COUNTRY_.
  BIND(xsd:integer(strafter(str(?item), "http://www.wikidata.org/entity/Q")) as ?qid)
  OPTIONAL { ?item wdt:P1448 ?name. }
  OPTIONAL { ?item wdt:P402 ?osm_relid .}
  OPTIONAL { ?item wdt:P625 ?wgs84 .}  
  OPTIONAL { ?item wdt:P1585 ?codIBGE .}
  SERVICE wikibase:label { bd:serviceParam wikibase:language "en,[AUTO_LANGUAGE]". }
}
ORDER BY ASC(?qid)
```
So, using BR as country code the associated QID is Q155, `$_QID_COUNTRY_='Q155'` and we obtain:
```sh
curl -o data/dumps_wd/BR_full.new.csv \
     -G 'https://query.wikidata.org/sparql' \
     --header "Accept: text/csv"  \
     --data-urlencode query='
     SELECT DISTINCT ?qid ?osm_relid ?wgs84 ?codIBGE ?itemLabel
     WHERE {
       ?item wdt:P625 _:b0.
       ?item wdt:P31*/wdt:P279*/wdt:P17 wd:Q155.
       #BIND(STRAFTER(STR(?item), "http://www.wikidata.org/entity/Q") AS ?qid)
       BIND(xsd:integer(strafter(str(?item), "http://www.wikidata.org/entity/Q")) as ?qid)
       OPTIONAL { ?item wdt:P1448 ?name. }
       OPTIONAL { ?item wdt:P402 ?osm_relid .}
       OPTIONAL { ?item wdt:P625 ?wgs84 .}  
       OPTIONAL { ?item wdt:P1585 ?codIBGE .}
       SERVICE wikibase:label { bd:serviceParam wikibase:language "en,[AUTO_LANGUAGE]". }
     }
     ORDER BY ASC(?qid)
'  # 2 minutes
```

### Old (deprecated) CSVs

1. [Querying at Wikidata](https://query.wikidata.org/#SELECT%20DISTINCT%20%3Fitem%20WHERE%20%7B%3Fitem%20wdt%3AP402%20%5B%5D.%7D%0A) with `SELECT DISTINCT ?item WHERE {?item wdt:P402 [].}`

2. Download as `wikidataP402.new.csv`.

3. `php src/check.php sort > data/dump/wikidataP402.csv` and, id all there,  `rm data/dump/wikidataP402.new.csv`.

4. `wc -l wikidataP402.csv` to estimate number of itens (please update home-README when necessary).

## Preparing dumps_osm
OpenStreetMap [dumps](https://en.wikipedia.org/wiki/Database_dump).

By country, example: `php src/OSM_get.php BR` will refresh the `data/dumps_osm/BR_wdElements.csv` file.
<!--
This Overpass-script generates content for [`osm_way.csv`](../data/dump/osm_way.csv) file.
Replacing `way` to `relation` at  script, will generate `osm_relation.csv` file.

As [this discussion](https://gis.stackexchange.com/q/288751/7505) the best is perhapts to use OSMium tools, https://osmcode.org/osmium-tool
See [osmium-tags-filter](https://github.com/osmcode/osmium-tool/blob/master/man/osmium-tags-filter.md).
-->

## Preparing lookup

After wikidata dump prepared, this recipe will match dumps, do some checks, and refresh lookup files, including the error output.

1. `php src/check.php BR > data/BR_lookup.csv` ... and wait a lot!<br/>To test you can use eg. `$stopAt=50`.
2. check file and `git diff data/BR_lookup.csv`
