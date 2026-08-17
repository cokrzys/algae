<?php

/**

  algae framework | Base class for ref.record_status table.
  
  Record status indicator orginally setup to indicate Active or InActive for data that is obsolete but cannot or should not be deleted. 
  Does NOT inherit from a named objects base table to avoid endless loops.

  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblRecordStatus extends algaeTblBase
{
  
  public $name;
  public $html_color;
  public $sort_order;
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
    $this->table_name = 'ref.record_status';
    $this->name = 'Active';
    $this->html_color = '#000000';
    $this->sort_order = 1;
    $this->description = null;
  }
  
}

