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
  
  public function getAppUserRowidForLoggedInUser()
  // --------------------------------------------------------------------------
  {
    $username = algaeAccess::getUsername();
    $sql = "SELECT rowid FROM $this->table_name WHERE $this->table_name.username = $1";
    return algaeDB::getScalarInteger($sql, array($username), null);
  }
  
}


