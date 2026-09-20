<?php

/**

  algae framework | App user and table core.app_user support.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblCoreAppUser extends algaeTblBase
{
 
  public $username;
  
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
    parent::init();
    $this->table_name = 'core.app_user';
    $this->username = null;
  }
  
  /**
   * Read from the database with a username.
   * @param string $username The username.
   * @return boolean
   */
  public function readRowFromDatabaseWithUsername($username)
  // --------------------------------------------------------------------------
  {
    $sql = $this->get_sql();
    $sql .= " WHERE $this->table_name.username = $1";
    return $this->read_row_from_database_with_sql($sql, array($username));
  }
  
}


