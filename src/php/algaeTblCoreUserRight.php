<?php

/**

  algae framework | A user right and support for table core.user_right support.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblCoreUserRight extends algaeTblBase
{
  
  public $user_rowid_fk;
  public $username;
  public $object_rowid_fk;
  public $object_name;
  public $role_rowid_fk;
  public $role_name;
  public $role_level;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    parent::__construct();
    $this->init();
  }
  
  /**
   * Initial default values.
   */
  public function init()
  // --------------------------------------------------------------------------
  {
    global $app;
    parent::init();
    $this->database = $app->config->admin_database;
    $this->table_name = 'core.user_right';
    $this->user_rowid_fk = null;
    $this->object_rowid_fk = null;
    $this->role_rowid_fk = null;
    $this->username = '';
    $this->object_name = '';
    $this->role_name = '';
    $this->role_level = 0;
  }
  
  /**
   * Get list of fields to use in a SQL statement.
   */
  public function getFields()
  // --------------------------------------------------------------------------
  {
    $sql = parent::getFields();
    $sql .= ", $this->table_name.user_rowid_fk, $this->table_name.object_rowid_fk, $this->table_name.role_rowid_fk, core.user.username,
             ref.object.name, ref.role.name, ref.role.level";
    return $sql;
  }
  
  /**
   * Get tables and joins to select data.
   * @param string $alias Name alias for the primary table.
   */
  public function getTableAndJoins()
  // --------------------------------------------------------------------------
  {
    return " FROM $this->table_name
              INNER JOIN core.user ON $this->table_name.user_rowid_fk = core.user.rowid
              INNER JOIN ref.object ON $this->table_name.object_rowid_fk = ref.object.rowid
              INNER JOIN ref.role ON $this->table_name.role_rowid_fk = ref.role.rowid";
  }
  
  /**
   * Read a row from the database.
   * @param array $row The array of data from the database.
   */
  public function readRowFromDatabase($row)
  // --------------------------------------------------------------------------
  {
    $cur = 0;
    $this->rowid = algaeDB::cleanDataRead($row[$cur++]);
    $this->timestamp_loaded_utc = algaeDB::cleanDataRead($row[$cur++]);
    $this->timestamp_modified_utc = algaeDB::cleanDataRead($row[$cur++]);
    $this->user_rowid_fk = algaeDB::cleanDataRead($row[$cur++]);
    $this->object_rowid_fk = algaeDB::cleanDataRead($row[$cur++]);
    $this->role_rowid_fk = algaeDB::cleanDataRead($row[$cur++]);
    $this->username = algaeDB::cleanDataRead($row[$cur++]);
    $this->object_name = algaeDB::cleanDataRead($row[$cur++]);
    $this->role_name = algaeDB::cleanDataRead($row[$cur++]);
    $this->role_level = algaeDB::cleanDataRead($row[$cur++]);
    return $cur;
  }
  
}



