<?php

/**

  algae framework | Generic class for items associated with a parent object.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblItemBase extends algaeTblBase
{
  
  public $parent;
  public $parent_column_name;
  
  /**
   * Constructor.
   */
  public function __construct($parentClass, $parentColumnName)
  // --------------------------------------------------------------------------
  {
    parent::__construct();
    $this->parent = new $parentClass();
    $this->parent_column_name = $parentColumnName;
  }
  
}