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
  
}


