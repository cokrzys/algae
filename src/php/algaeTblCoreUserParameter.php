<?php

/**

  algae framework | User parameters stored in the database and core.user_parameter support.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
*/

class algaeTblCoreUserParameter extends algaeTblBase
{
  
  public $app_user;
  public $name;
  public $val;
  public $description;
  
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
    $this->table_name = 'core.user_parameter';
    $this->app_user = new algaeTblCoreAppUser();
    $this->name = null;
    $this->val = null;
    $this->description = null;
  }
  
  public static function get_parameter($name)
  // --------------------------------------------------------------------------
  {
    $o = new algaeTblCoreUserParameter();
    $sql = "SELECT val FROM $o->table_name WHERE app_user_rowid_fk = $1 AND name = $2";
    return algaeDB::getScalarString($sql, array(algaeTblCoreAppUser::getAppUserRowidForLoggedInUser(), $name));
  }
  
  public static function save_parameter($name, $value)
  // --------------------------------------------------------------------------
  {
    $o = new algaeTblCoreUserParameter();
    $o->app_user->rowid = algaeTblCoreAppUser::getAppUserRowidForLoggedInUser();
    $o->name = $name;
    $o->val = $value;
    $o->write();
  }
  
}


