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


--- VIEWS: ---

CREATE VIEW tmp1.vw_osm_counts AS
  SELECT count(*) as n, count(distinct osm_id||osm_type) as osm_ids,
         count(distinct wd_id||osm_type) as wd_ids
  FROM tmp1.osm_br_raw
; -- 11454 |  11454 |  10850


CREATE or replace FUNCTION tmp1.j2links(jsonb) RETURNS text AS $f$
  SELECT array_to_string(array_agg(
    concat('[',k[1],'](https://www.openstreetmap.org/',k[1],'/',k[2],'): ',v)
    ),', ')
  FROM (
    SELECT regexp_split_to_array(key,':') as k, value as v
    FROM  jsonb_each($1)
  ) t2
$f$ language SQL;


CREATE VIEW tmp1.vw_osm_dups AS
  SELECT concat('[',wd_id,'](https://tools.wmflabs.org/reasonator/?lang=pt&q=',wd_id,')') as  wd_id,
         tmp1.j2links(osm_types) as osm_types,
         n_tot, name
  FROM (
    SELECT wd_id,
           jsonb_object_agg(osm_type||':'||osm_id,n) as osm_types,
           jsonb_object_agg(osm_type,n) as osm_types2,
           sum(n) as n_tot, max(name) as name
    FROM (
      SELECT wd_id, osm_type,
             max(osm_id) as osm_id,
             sum(n) as n,
             max(name) as name
      FROM (
        SELECT osm_type,wd_id,
               count(*) as n,
               max(osm_id) as osm_id,
               max(name) as name
        FROM tmp1.osm_br_raw
        GROUP BY 1,2
      ) t1
      GROUP BY 1,2
      ORDER BY 1,2
    ) t2
    GROUP BY 1
    HAVING sum(n)>1
  ) t3
  WHERE not(osm_types2='{"node":1,"relation":1}'::jsonb)
  ORDER BY 3 DESC,1
;
