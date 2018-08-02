# semantic-bridge
A "[semantic](https://en.wikipedia.org/wiki/Semantic_Web) bridge" between [OpenStreetMap](http://openstreetmap.org) (OSM) and [Wikidata](http://wikidata.org) (WD) by **reciprocal** identification.

[ ![](assets/wdOsm-semanticBridge-480px.jpeg) ](assets#credits)

## Basics

All relevant feature at OSM can be tagged with [`key:wikidata`](https://wiki.openstreetmap.org/wiki/Key:wikidata), pointing to its Wikidata semantic.

When, at Wikidata infrastructure, at the pointed semantic (a Wikidata ID) there are also a pointer to OSM, the "semantic bridge" has been built (!), so there are a complete [authority control with reciprocal use](https://www.wikidata.org/wiki/Q24075706). The [`lookup.csv` table](data/lookup.csv) list the OSM features that offers this reciprocity.

At July 2018 there are:

* [~1,123,500 OSM features](https://taginfo.openstreetmap.org/search?q=wikidata#keys) with a `wikidata` key.

* [**~63,000** Wikidata entities](https://query.wikidata.org/#SELECT%20%28COUNT%28DISTINCT%20%3Fitem%29%20AS%20%3Fcount%29%20WHERE%20%7B%3Fitem%20wdt%3AP402%20%5B%5D.%7D%0A) with the [OSM relation ID (`P402`)](http://wikidata.org/entity/P402) property pointing to OSM.

* 5% of errors in a sample of 2000 from Wikidata, where ~1900 items passed the test (a check ensuring that each OSM feature was really tagged with a reciprocal Wikidata identification) to constitute the *lookup* table.

## The lookup as certification

Some examples and fields description for the [`lookup.csv`](data/lookup.csv) main dataset of this project.

wdId|osm_type|osm_id|isReciprocal|check_date
----|--------|------|------|-------
[Q155](http://wikidata.org/entity/Q155)|R|[59470](https://www.openstreetmap.org/relation/59470) ([js](https://nominatim.openstreetmap.org/details.php?format=json&osmtype=R&osmid=59470))|y|2018-07-06
[Q17061](http://wikidata.org/entity/Q17061)|R|[23092](https://www.openstreetmap.org/relation/23092) ([js](https://nominatim.openstreetmap.org/details.php?format=json&osmtype=R&osmid=23092) fail)|y|2018-07-06
[Q2880208](http://wikidata.org/entity/Q2880208)|W|[75488634](https://www.openstreetmap.org/way/75488634) ([js](https://nominatim.openstreetmap.org/details.php?format=json&osmtype=W&osmid=75488634))|n|2018-07-06
[Q2500246](http://wikidata.org/entity/Q2500246)|N|[817882603](https://www.openstreetmap.org/node/817882603) ([js](https://nominatim.openstreetmap.org/details.php?format=json&osmtype=N&osmid=817882603))|n|2018-07-06
...|...|...|...|...

* `wdId`: the Wikidata ID, can be resolved by `http://wikidata.org/entity/{wdId}`
* `osm_type`: the OSM datatype used to represent the feature. `R`=Relation (polygon), `W`=Way (line), `N`=Node (point).
* `osm_id`: the ID attributed to OSM feature in the check_date.

The lookup not need all these fields, but as illustration above we add:
* `isReciprocal`: a flag to say that the Wikidata and OSM indications are reciprocal or not (`y` or `n`).
* `check_date`: an [ISO 8601](https://en.wikipedia.org/wiki/ISO_8601) date, when last checking procedure was performed.

The lookup and [its CSV for error log](data/lookup_errors_WIKIDATA.csv) (`lookup_errors_WIKIDATA`) are generated by software, see [`/src`](src).

## Dump as source for comparisions

There are two big [*dump* files](https://en.wikipedia.org/wiki/Database_dump) at [`data/dump`](data/dump) folder:

* [osm_relation.csv](data/dump/osm_relation.csv) with pairs of *osm_relationId-wdId* fields;
* [osm_way.csv](data/dump/osm_way.csv) with pairs of *osm_relationId-wdId* fields;

As commented at ["Preparing OSM dumps"](src/README.md#preparing-osm-dumps), we can express it by Overpass and generate samples, but not do the real task, because is really big.  We can be split into countryes, and it will be better to use  with specialized curators... But even splitting we need OSMium tools to generate the dump files. So the v0.1 checking is using the online tools, that is a lazzy solution, so the project is producing only samples.

## Towards a microservice to offer the lookup-table
The service will be a hub for ***name resolution***, in the sense of [URN resolution](https://tools.ietf.org/html/rfc2169) (a standard terminology since 1997). The first step is to offer to Wikidata's [`P402`](http://wikidata.org/entity/P402) a persistent [URL template](https://en.wikipedia.org/wiki/URL_Template), offering  [Persistent URLs](https://en.wikipedia.org/wiki/Persistent_uniform_resource_locator), something like <br/> &nbsp; `http://wd.openstreetmap.org/{wikidata_id}` <br/>for redirection service, <br/> &nbsp; `http://wd.openstreetmap.org/{otherName}` <br/>for official reference redirection service  (to main official synonyms as [contry ISO codes](https://datahub.io/core/country-codes) or [local ISO administrative codes](http://datasets.ok.org.br/state-codes)).

The other resolution services (ISO to Wikidata, OSM to Wikidata, Wikidata to OSM, etc.) including [canonicalization](https://en.wikipedia.org/wiki/Canonicalization) of OSM-elements (duplicates of Wikidata tag at OSM), will use something like <br/> &nbsp; `http://urn.openstreetmap.org/{namespace}:{name}/{method}` <br/>with a standard methods, as showed by the [ISSN-L-Resolver project](https://github.com/okfn-brasil/ISSN-L-Resolver). The `namespace` parameter is like an [URN schema](https://en.wikipedia.org/wiki/Uniform_Resource_Name), the `name` can be an official name or a valid ID for the namespace.

The `method` defines the endpoint of a service. Typical methods implemented as JSON services are `N2C` (name-to-canonic) to obtain the canonical name, `isN`  to check that the name exists, `N2Ns` (name-to-names) to list all official synonyms of a name... and `info` to return a catalog with all basic metadata that describes the refered (canonical) item.  

## The curators
... Organazing by country. Each "community of curators" check its data, the erros and do corrections.

------

[&#160; Contents and data of this project are dedicated to<br/> ![](assets/CC0-logo-200px.png) ](LICENSE.md)
