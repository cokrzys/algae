/**

  algae framework | Update SQL for the application database model.

  Apply updates in sequence by date.
  See setup_app_database.sql for version notes.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

-- 2026.10.12

ALTER TABLE ref.field_type ADD COLUMN sort_order INTEGER DEFAULT 0;
UPDATE ref.field_type SET sort_order = 0;
ALTER TABLE ref.field_type ALTER COLUMN sort_order SET NOT NULL;

ALTER TABLE ref.field_type ADD COLUMN html_color VARCHAR DEFAULT algae_default_color();
UPDATE ref.field_type SET html_color = algae_default_color();
ALTER TABLE ref.field_type ALTER COLUMN html_color SET NOT NULL;

ALTER TABLE ref.field ADD COLUMN html_color VARCHAR DEFAULT algae_default_color();
UPDATE ref.field SET html_color = algae_default_color();
ALTER TABLE ref.field ALTER COLUMN html_color SET NOT NULL;

CREATE OR REPLACE FUNCTION algae_app_database_version() RETURNS varchar LANGUAGE SQL AS
  $$ SELECT CAST('2026.10.12' AS VARCHAR); $$;

-- 2026.10.13

ALTER TABLE core.report ADD COLUMN record_status_rowid_fk INTEGER REFERENCES ref.record_status DEFAULT algae_active_rowid();
UPDATE core.report SET record_status_rowid_fk = algae_active_rowid();
ALTER TABLE core.report ALTER COLUMN record_status_rowid_fk SET NOT NULL;

ALTER TABLE core.report ADD COLUMN html_color VARCHAR DEFAULT algae_default_color();
UPDATE core.report SET html_color = algae_default_color();
ALTER TABLE core.report ALTER COLUMN html_color SET NOT NULL;

CREATE OR REPLACE FUNCTION algae_app_database_version() RETURNS varchar LANGUAGE SQL AS
  $$ SELECT CAST('2026.10.13' AS VARCHAR); $$;
  
-- 2026.10.18

UPDATE ref.record_status SET html_color = '#1a9850' WHERE name = 'Active';
UPDATE ref.record_status SET html_color = '#d73027' WHERE name = 'InActive';

CREATE OR REPLACE FUNCTION algae_app_database_version() RETURNS varchar LANGUAGE SQL AS
  $$ SELECT CAST('2026.10.18' AS VARCHAR); $$;


