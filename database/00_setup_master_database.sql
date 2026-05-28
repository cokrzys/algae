/**

  00_setup_master_database.sql - Algae Framework PostgreSQL master database setup.
  
  Contains user accounts and rights to individual objects and application databases.
	
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
-- function to get the version
--
CREATE OR REPLACE FUNCTION algae_database_version() RETURNS varchar LANGUAGE SQL AS
  $$ SELECT CAST('0.0.1' AS VARCHAR); $$;

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
-- ref.role
--
DROP TABLE IF EXISTS ref.role;
DROP SEQUENCE IF EXISTS ref.role_rowid;
CREATE SEQUENCE ref.role_rowid START 1;
CREATE TABLE ref.role
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('ref.role_rowid'),
  record_status_rowid_fk INTEGER NOT NULL REFERENCES ref.record_status DEFAULT algae_active_rowid(),
  name VARCHAR NOT NULL UNIQUE,
  level INTEGER NOT NULL UNIQUE,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON ref.role FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
INSERT INTO ref.role (name, level, description) VALUES 
  ('Guest', 0, 'Guest.');
INSERT INTO ref.role (name, level, description) VALUES 
  ('Read', 100, 'Read an object.');
INSERT INTO ref.role (name, level, description) VALUES 
  ('Write', 200, 'Read and write an object.');
INSERT INTO ref.role (name, level, description) VALUES 
  ('Admin', 300, 'Administrative rights.'); 
INSERT INTO ref.role (name, level, description) VALUES 
  ('SysAdmin', 500, 'System wide administrative rights.'); 
  
--
-- ref.object
--
DROP TABLE IF EXISTS ref.object;
DROP SEQUENCE IF EXISTS ref.object_rowid;
CREATE SEQUENCE ref.object_rowid START 1;
CREATE TABLE ref.object
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('ref.object_rowid'),
  record_status_rowid_fk INTEGER NOT NULL REFERENCES ref.record_status DEFAULT algae_active_rowid(),
  name VARCHAR NOT NULL UNIQUE,
  description VARCHAR,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON ref.object FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
INSERT INTO ref.object (name, description) VALUES 
  ('algae', 'The algae framework.'); 
  
-- ============================================================================
--  core 
-- ============================================================================ 
  
--
-- core.user
--
DROP SEQUENCE IF EXISTS core.user_rowid;
DROP TABLE IF EXISTS core.user;
CREATE SEQUENCE core.user_rowid START 1;
CREATE TABLE core.user
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('core.user_rowid'),
  record_status_rowid_fk INTEGER NOT NULL REFERENCES ref.record_status DEFAULT algae_active_rowid(),
  username VARCHAR NOT NULL UNIQUE,
  password VARCHAR NOT NULL,
  name VARCHAR,
  email VARCHAR,
  failed_login_attempts INTEGER NOT NULL DEFAULT 0,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON core.user FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
  
--
-- the password is stored encrypted when a user is created via the web interface
-- so while a "password" is stored here it's not a risk as it won't work
--
INSERT INTO core.user (username, password, name) 
VALUES ('guest', 'won''t work', 'Guest');
  
--
-- core.user_right
--
DROP TABLE IF EXISTS core.user_right;
DROP SEQUENCE IF EXISTS core.user_right_rowid;
CREATE SEQUENCE core.user_right_rowid START 1;
CREATE TABLE core.user_right
(
  rowid INTEGER PRIMARY KEY DEFAULT nextval('core.user_right_rowid'),
  user_rowid_fk INTEGER NOT NULL REFERENCES core.user,
  object_rowid_fk INTEGER NOT NULL REFERENCES ref.object,
  role_rowid_fk INTEGER NOT NULL REFERENCES ref.role,
  timestamp_loaded_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  timestamp_modified_utc TIMESTAMP NOT NULL DEFAULT current_timestamp,
  UNIQUE(user_rowid_fk, object_rowid_fk, role_rowid_fk)
);
CREATE TRIGGER update_modified BEFORE UPDATE
  ON core.user_right FOR EACH ROW EXECUTE PROCEDURE
  algae_update_modified_column();
 
--
-- example only
--
-- INSERT INTO core.user_right (user_rowid_fk, object_rowid_fk, role_rowid_fk) VALUES
-- (
--   (SELECT rowid FROM core.user WHERE username = 'trilobite'),
--   (SELECT rowid FROM ref.object WHERE name = 'algae'),
--   (SELECT rowid FROM ref.role WHERE name = 'SysAdmin')
-- );
  
--
-- cleanup, refresh stats
--
VACUUM ANALYZE;


