/**
 * STEP 1 - SQL PREPARATION
 */

--
-- PREPARE WITH
-- cd /tmp
-- git clone https://github.com/OSMBrasil/semantic-bridge.git
--

CREATE EXTENSION file_fdw;
CREATE SERVER files FOREIGN DATA WRAPPER file_fdw;

DROP SCHEMA IF EXISTS tmp1 CASCADE;
CREATE SCHEMA tmp1;

CREATE FOREIGN TABLE tmp1.wd_br_raw (
  wd_id bigint,           osm_relid bigint,
  latlong_wgs84 text,  ibge_id int,
  name text
) SERVER files OPTIONS (
  filename '/tmp/semantic-bridge/data/dumps_wd/BR_items.csv',
  format 'csv',
  header 'true'
);

CREATE FOREIGN TABLE tmp1.osm_br_raw (
  osm_type text,       osm_id bigint,
  wd_id text,          name text
) SERVER files OPTIONS (
  filename '/tmp/semantic-bridge/data/dumps_osm/BR_elements.csv',
  format 'csv',
  header 'true'
);

CREATE VIEW tmp1.wd_osm_join1 AS
  SELECT w.wd_id, osm_type, o.osm_id,
         CASE WHEN osm_type='relation' AND w.osm_relid=osm_id THEN true ELSE false END as osm_was_matching,
         '('|| w.name ||' | '|| o.name||')' as names
  FROM tmp1.wd_br_raw w INNER JOIN tmp1.osm_br_raw o
    ON w.wd_id::text = substr(o.wd_id,2)
  ORDER BY 1,2 desc
;

-- falta carregar cidades e estados para descontar.
