/**

  algae framework | PostgreSQL application database setup.
	
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

--
-- turn notices off to make the output easier to read
--
SET client_min_messages TO WARNING;

--
-- function to keep the last modified date updated automatically
--
CREATE OR REPLACE FUNCTION algae_update_modified_column() 
        RETURNS TRIGGER AS '
  BEGIN
    NEW.timestamp_modified_utc = NOW();
    RETURN NEW;
  END;
' LANGUAGE 'plpgsql';

--
-- function to get a timestamp in ISO 8601 format, from:
-- http://postgresql.1045698.n5.nabble.com/Format-string-for-ISO-8601-date-and-time-td1910959.html
--
CREATE OR REPLACE FUNCTION algae_iso_timestamp(timestamp with time zone) 
   RETURNS VARCHAR AS $$ 
  SELECT substring(xmlelement(name x, $1)::VARCHAR FROM 4 for 32) 
$$ LANGUAGE SQL IMMUTABLE;

--
-- function to get a default color
--
CREATE OR REPLACE FUNCTION algae_default_color() RETURNS varchar LANGUAGE SQL AS
  $$ SELECT CAST('#000000' AS VARCHAR); $$;
  
--
-- Linear interpolation with clipping to the min/max destination bounds.
--
-- src_min = min of source range
-- src_max = max of source range
-- dest_min = min of destination range
-- dest_max = max of destination range
-- src_val = value in source to convert to destination
--
-- returns equivalent of src_val in the destination range
--
CREATE OR REPLACE FUNCTION algae_linear_interp(src_min numeric, src_max numeric, 
  dest_min numeric, dest_max numeric, src_val numeric) RETURNS numeric AS $$
  DECLARE
    src_range numeric := 0.0;
    dest_range numeric := 0.0;
  BEGIN
    IF dest_min = dest_max OR src_val <= src_min THEN
      RETURN dest_min;
    ELSE
      IF src_val >= src_max THEN
        RETURN dest_max;
      ELSE
        src_range := src_max - src_min;
        dest_range := dest_max - dest_min;
        IF src_range <> 0.0 THEN
          RETURN dest_min + ((dest_range / src_range) * (src_val - src_min));
        ELSE
          RETURN dest_min;
        END IF;
      END IF;
    END IF;
  END;
$$ LANGUAGE plpgsql;

--
-- Tests if a number if numeric by casting it to numeric and seeing if it works.
-- https://newbedev.com/isnumeric-with-postgresql
--
CREATE OR REPLACE FUNCTION algae_isnumeric(text) RETURNS BOOLEAN AS $$
DECLARE x NUMERIC;
BEGIN
    x = $1::NUMERIC;
    RETURN TRUE;
EXCEPTION WHEN others THEN
    RETURN FALSE;
END;
$$
STRICT
LANGUAGE plpgsql IMMUTABLE; 

--
-- schema logic
--
-- core = main system tables
-- ref = reference

--
-- drop anything that exists
--
DROP SCHEMA IF EXISTS core CASCADE;
DROP SCHEMA IF EXISTS ref CASCADE;

--
-- create schemas
--
CREATE SCHEMA ref;
CREATE SCHEMA core;


-- ============================================================================
--  ref
-- ============================================================================ 

--
-- ref.record_status
--
CREATE SEQUENCE ref.record_status_rowid START 1;
CREATE TABLE ref.record_status
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('ref.record_status_rowid'),
  name VARCHAR NOT NULL UNIQUE,
  sort_order INTEGER NOT NULL UNIQUE,
  html_color VARCHAR NOT NULL,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON ref.record_status FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
INSERT INTO ref.record_status (name, sort_order, html_color, description) 
  VALUES ('Active', 1, '#00FF00', 'Active record.');
INSERT INTO ref.record_status (name, sort_order, html_color, description) 
  VALUES ('InActive', 0, '#FF0000', 'InActive record.');
  
--
-- function to get the rowid for an active record
-- this is typically used to set a default value
--
CREATE OR REPLACE FUNCTION algae_active_rowid() RETURNS int LANGUAGE SQL AS
  $$ SELECT rowid FROM ref.record_status WHERE name = 'Active'; $$;
  
--
-- ref.process_status
--
CREATE SEQUENCE ref.process_status_rowid START 1;
CREATE TABLE ref.process_status
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('ref.process_status_rowid'),
  name VARCHAR NOT NULL UNIQUE,
  sort_order INTEGER NOT NULL UNIQUE,
  html_color VARCHAR NOT NULL,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON ref.process_status FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
INSERT INTO ref.process_status (name, sort_order, html_color, description) 
  VALUES ('Running', 10, '#8FE993', 'Process is currently running.');
INSERT INTO ref.process_status (name, sort_order, html_color, description) 
  VALUES ('Error', 50, '#FDEFA9', 'Process stopped with one or more errors.');
INSERT INTO ref.process_status (name, sort_order, html_color, description) 
  VALUES ('Success', 100, '#91cf60', 'Process finished successfully.');
INSERT INTO ref.process_status (name, sort_order, html_color, description) 
  VALUES ('Stopped', 55, '#FDEFA9', 'Process stopped.');
INSERT INTO ref.process_status (name, sort_order, html_color, description) 
  VALUES ('Finished', 105, '#91cf60', 'Process finished.');
  
--
-- ref.field_type
--
DROP SEQUENCE IF EXISTS ref.field_type_rowid;
DROP TABLE IF EXISTS ref.field_type;
CREATE SEQUENCE ref.field_type_rowid START 1;
CREATE TABLE ref.field_type
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('ref.field_type_rowid'),
  record_status_rowid_fk INTEGER NOT NULL REFERENCES ref.record_status DEFAULT algae_active_rowid(),
  name VARCHAR NOT NULL UNIQUE,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON ref.field_type FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
INSERT INTO ref.field_type (name, description) VALUES 
  ('TEXT', 'Text field.'); 
INSERT INTO ref.field_type (name, description) VALUES 
  ('INTEGER', 'Integer field.'); 
INSERT INTO ref.field_type (name, description) VALUES 
  ('NUMERIC', 'Floating point numeric field.'); 
INSERT INTO ref.field_type (name, description) VALUES 
  ('DATETIME', 'Date-time field.'); 
INSERT INTO ref.field_type (name, description) VALUES 
  ('PERCENTAGE', 'A percentage value from 0-100.'); 
INSERT INTO ref.field_type (name, description) VALUES 
  ('CURRENCY', 'A currency.');
INSERT INTO ref.field_type (name, description) VALUES 
  ('SCORE', 'Scored value.');
INSERT INTO ref.field_type (name, description) VALUES 
  ('LINK', 'Hyperlink.');
INSERT INTO ref.field_type (name, description) VALUES 
  ('MODEL', 'Model result.');
  
--
-- ref.field
--
DROP SEQUENCE IF EXISTS ref.field_rowid;
DROP TABLE IF EXISTS ref.field;
CREATE SEQUENCE ref.field_rowid START 1;
CREATE TABLE ref.field
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('ref.field_rowid'),
  record_status_rowid_fk INTEGER NOT NULL REFERENCES ref.record_status DEFAULT algae_active_rowid(),
  field_type_rowid_fk INTEGER NOT NULL REFERENCES ref.field_type,
  name VARCHAR NOT NULL UNIQUE,
  num_decimals INTEGER NOT NULL,
  sort_order INTEGER,
  calc_sql VARCHAR,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON ref.field FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
--
-- ref.field_set
--
DROP SEQUENCE IF EXISTS ref.field_set_rowid;
DROP TABLE IF EXISTS ref.field_set;
CREATE SEQUENCE ref.field_set_rowid START 1;
CREATE TABLE ref.field_set
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('ref.field_set_rowid'),
  record_status_rowid_fk INTEGER NOT NULL REFERENCES ref.record_status DEFAULT algae_active_rowid(),
  name VARCHAR NOT NULL UNIQUE,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON ref.field_set FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
--
-- ref.field_set_item
--
DROP SEQUENCE IF EXISTS ref.field_set_item_rowid;
DROP TABLE IF EXISTS ref.field_set_item;
CREATE SEQUENCE ref.field_set_item_rowid START 1;
CREATE TABLE ref.field_set_item
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('ref.field_set_item_rowid'),
  field_set_rowid_fk INTEGER NOT NULL REFERENCES ref.field_set,
  field_rowid_fk INTEGER NOT NULL REFERENCES ref.field,
  sort_order INTEGER NOT NULL,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON ref.field_set_item FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
-- ============================================================================
--  core 
-- ============================================================================ 

--
-- core.process
--
DROP SEQUENCE IF EXISTS core.process_rowid;
DROP TABLE IF EXISTS core.process;
CREATE SEQUENCE core.process_rowid START 1;
CREATE TABLE core.process
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('core.process_rowid'),
  user_rowid_fk INTEGER NOT NULL REFERENCES core.user,
  process_status_rowid_fk INTEGER NOT NULL REFERENCES ref.process_status,
  application VARCHAR,
  command VARCHAR,
  logfile VARCHAR,
  parmsfile VARCHAR,
  pid INTEGER,
  starting_url VARCHAR,
  result_url VARCHAR,
  progress NUMERIC,
  progress_message VARCHAR,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON core.process FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
--
-- core.query
--
DROP SEQUENCE IF EXISTS core.query_rowid;
DROP TABLE IF EXISTS core.query;
CREATE SEQUENCE core.query_rowid START 1;
CREATE TABLE core.query
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('core.query_rowid'),
  user_rowid_fk INTEGER NOT NULL REFERENCES core.user, 
  sql VARCHAR,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON core.query FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column(); 
  
--
-- core.standard_query
-- a standard query, potentially with replaceable portions
-- added Apr 2023 to support standard templates for stock fields
--
DROP SEQUENCE IF EXISTS core.standard_query_rowid;
DROP TABLE IF EXISTS core.standard_query;
CREATE SEQUENCE core.standard_query_rowid START 1;
CREATE TABLE core.standard_query
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('core.standard_query_rowid'),
  record_status_rowid_fk INTEGER NOT NULL REFERENCES ref.record_status DEFAULT algae_active_rowid(),
  name VARCHAR NOT NULL,
  sql VARCHAR,
  prototype VARCHAR,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  UNIQUE(name)
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON core.standard_query FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
--
-- core.user_parameter
--
DROP SEQUENCE IF EXISTS core.user_parameter_rowid;
DROP TABLE IF EXISTS core.user_parameter;
CREATE SEQUENCE core.user_parameter_rowid START 1;
CREATE TABLE core.user_parameter
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('core.user_parameter_rowid'),
  user_rowid_fk INTEGER NOT NULL REFERENCES core.user, 
  name VARCHAR NOT NULL,
  val VARCHAR NOT NULL,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  UNIQUE(user_rowid_fk, name)
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON core.user_parameter FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
--
-- core.report
--
DROP SEQUENCE IF EXISTS core.report_rowid;
DROP TABLE IF EXISTS core.report;
CREATE SEQUENCE core.report_rowid START 1;
CREATE TABLE core.report
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('core.report_rowid'),
  user_rowid_fk INTEGER NOT NULL REFERENCES core.user, 
  name VARCHAR NOT NULL,
  version VARCHAR NOT NULL,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  UNIQUE(user_rowid_fk, name, version)
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON core.report FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
--
-- core.report_item
--
DROP SEQUENCE IF EXISTS core.report_item_rowid;
DROP TABLE IF EXISTS core.report_item;
CREATE SEQUENCE core.report_item_rowid START 1;
CREATE TABLE core.report_item
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('core.report_item_rowid'),
  report_rowid_fk INTEGER NOT NULL REFERENCES core.report, 
  field_rowid_fk INTEGER NOT NULL REFERENCES ref.field,
  sort_order INTEGER NOT NULL,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  UNIQUE(report_rowid_fk, field_rowid_fk, sort_order)
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON core.report_item FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
--
-- core.file, references to uploaded single files
--
DROP TABLE IF EXISTS core.file;
DROP SEQUENCE IF EXISTS core.file_rowid;
CREATE SEQUENCE core.file_rowid START 1;
CREATE TABLE core.file
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('core.file_rowid'),
  record_status_rowid_fk INTEGER NOT NULL REFERENCES ref.record_status DEFAULT algae_active_rowid(),
  name VARCHAR NOT NULL,
  size_bytes INT NOT NULL,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  UNIQUE(name)
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON core.file FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
--
-- cleanup, refresh stats
--
VACUUM ANALYZE;


