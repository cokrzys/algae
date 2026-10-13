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