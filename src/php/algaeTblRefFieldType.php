<?php

/**

  algae framework | Field types and support for ref.field_type.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/slate

*/

class algaeTblRefFieldType extends algaeTblReferenceBase
{
  
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
    $this->table_name = 'ref.field_type';
    $this->itemName = 'Field Type';
  }
  
  public function getControl($calling_class, $fk_column_name = null)
  // --------------------------------------------------------------------------
  {
    return algaeForm::selectWithTableAndFieldWithRowid($this->table_name, 'name',
      $calling_class->get_control_id('field_type_rowid_fk'), $this->name, True);
  }
  
}
