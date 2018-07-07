Source code of "checking tools", in many programming languages. 

## Preparing Wikidata dump

1. [Querying at Wikidata](https://query.wikidata.org/#SELECT%20DISTINCT%20%3Fitem%20WHERE%20%7B%3Fitem%20wdt%3AP402%20%5B%5D.%7D%0A) with `SELECT DISTINCT ?item WHERE {?item wdt:P402 [].}` 

2. Download as `wikidataP402.new.csv`.

3. `php src/check.php sort > data/dump/wikidataP402.csv` and, id all there,  `rm data/dump/wikidataP402.new.csv`.

4. `wc -l wikidataP402.csv` to estimate number of itens (please update home-README when necessary).

## Preparing lookup

After wikidata dump prepared, 

1. `php src/check.php > data/lookup.csv` ... and wait a lot!<br/>To test you can use eg. `$stopAt=50`.
2. check file and `git diff data/lookup.csv`

