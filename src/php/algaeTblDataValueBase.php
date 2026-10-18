<?php

/**

  algae framework | Generic class for data values associated with a parent object.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblDataValueBase extends algaeTblBase
{
  
  public $parent;
  public $parent_column_name;
  public $field;
  public $field_column_name;
  public $text_val;
  public $numeric_val;
  public $integer_val;
  public $datetime_val;
  public $description;
  
  /**
   * Constructor.
   */
  public function __construct($parentClass, $parentColumnName, $fieldClass = 'algaeTblField', 
    $fieldColumnName = 'field_rowid_fk')
  // --------------------------------------------------------------------------
  {
    parent::__construct();
    $this->parent = new $parentClass();
    $this->field = new $fieldClass();
    $this->parent_column_name = $parentColumnName;
    $this->field_column_name = $fieldColumnName;
    $this->zero();
  }
  
  /**
   * 
   */
  public function zero()
  // --------------------------------------------------------------------------
  {
    $this->text_val = null;
    $this->numeric_val = null;
    $this->integer_val = null;
    $this->datetime_val = null;
    $this->description = null;
  }
  
  public function storeValue($val)
  // --------------------------------------------------------------------------
  {
    global $app;
    if ($this->field->isText())
    {
      $this->text_val = $val;
    }
    elseif ($this->field->isInteger())
    {
      $this->integer_val = $val;
    }
    elseif ($this->field->isNumeric())
    {
      $this->numeric_val = $val;
    }
    elseif ($this->field->isDateTime())
    {
      $this->datetime_val = $val;
    }
    else 
    {
      $app->errorMessage('Unrecognized field type ' . $this->field->field_type . ' in ' . __FUNCTION__ . '.');
    }
  }
  
  public function getCalcSQL()
  // --------------------------------------------------------------------------
  {
    return 'SELECT ' . $this->parent_column_name . ' AS rowid, ' .
      $this->field->getDataColumn() . ' AS val FROM ' . $this->table_name;
  }
  
  protected function readFieldWithName($name)
  // --------------------------------------------------------------------------
  {
    if ($this->field != null)
    {
      $this->field->readRowFromDatabaseWithName($name);
    }
  }
  
  /**
   * Get the number of values for a parent.
   * @param integer $parent_rowid_fk The parent rowid.
   * @return integer The number of values.
   */
  public function getNumValuesWithParent($parent_rowid_fk)
  // --------------------------------------------------------------------------
  {
    return algaeDB::getScalarInteger("SELECT COUNT(*) FROM $this->table_name WHERE $this->parent_rowid_name = $1", array($parent_rowid_fk), 0);
  }
  
  public function getValueWithParent($parent_rowid_fk)
  // --------------------------------------------------------------------------
  {
    global $app;
    if ($this->isFieldSetup())
    {
      $sql = "SELECT " . $this->getDataField();
      $sql .= " FROM $this->table_name WHERE $this->parent_rowid_name = $1 AND field_rowid_fk = $2";
      if ($this->field->isText())
      {
        $this->text_val = algaeDB::getScalarString($sql, array($parent_rowid_fk, $this->field->rowid));
        return $this->text_val;
      }
      elseif ($this->field->isInteger())
      {
        $this->integer_val =  algaeDB::getScalarInteger($sql, array($parent_rowid_fk, $this->field->rowid), null);
        return $this->integer_val;
      }
      elseif ($this->field->isNumeric())
      {
        $this->numeric_val = algaeDB::getScalarFloat($sql, array($parent_rowid_fk, $this->field->rowid), null);
        return $this->numeric_val;
      }
      elseif ($this->field->isDateTime())
      {
        // TODO: Implement Datetime support in algaeTblDataValue::getValue()
        $app->errorMessage('Datetime support not implemented in algaeTblDataValue::getValue().');
      }
      else
      {
        $app->errorMessage('Unrecognized field type ' . $this->field->field_type . ' in algaeTblDataValue::getValue().');
      }
    }
    return null;
  }
  
  public function getValueWithParentAndField($parent_rowid_fk, $field_name)
  // --------------------------------------------------------------------------
  {
    $this->readFieldWithName($field_name);
    return $this->getValueWithParent($parent_rowid_fk);
  }
  
  public function writeValue($parent_rowid_fk, $field_name, $val)
  // --------------------------------------------------------------------------
  {
    global $app;
    $this->readFieldWithName($field_name);
    if ($this->isFieldSetup())
    {
      $this->parent->rowid = $parent_rowid_fk;
      $this->storeValue($val);
      if (! $this->exists())
      {
        return $this->writeToDatabase();
      }
      else
      {
        return $this->updateDatabase();
      }
    }
    else 
    {
      $app->errorMessage('Field ' . $field_name . ' does not exist in writeValue().');
    }
    return False;
  }
  
  public function getFormattedValue($include_color = False)
  // --------------------------------------------------------------------------
  {
    $color_block = '';
    if ( ($include_color) && ($this->field->isNumeric()) && ($this->field->can_have_breaks == 'Yes') )
    {
      if ( ($this->field->field_type == 'NUMERIC') || ($this->field->field_type == 'PERCENTAGE') ||
        ($this->field->field_type == 'CURRENCY') )
      {
        $color_block = $this->field->getColorBlock($this->numeric_val);
      }
      else if ($this->field->field_type == 'INTEGER')
      {
        $color_block = $this->field->getColorBlock($this->integer_val);
      }
    }
    if (strlen($color_block) > 0) $color_block .= '&nbsp;';
    if ($this->field->field_type == 'TEXT')
    {
      return $this->text_val;
    }
    if ($this->field->field_type == 'INTEGER')
    {
      return $color_block . algaeCore::getFormattedNumber($this->integer_val, 0, null, '');
    }
    if ($this->field->field_type == 'NUMERIC')
    {
      return $color_block . algaeCore::getFormattedNumber($this->numeric_val, $this->field->num_decimals, null);
    }
    if ($this->field->field_type == 'PERCENTAGE')
    {
      return $color_block . algaeCore::getFormattedPercentage($this->numeric_val, $this->field->num_decimals, null);
    }
    if ($this->field->field_type == 'CURRENCY')
    {
      return $color_block . algaeCore::getFormattedDollarAmount($this->numeric_val, $this->field->num_decimals, null);
    }
    if ($this->field->field_type == 'LINK')
    {
      return '<a target="_blank" href="' . $this->text_val .'">' . $this->text_val .'</a>';
    }
    if ($this->field->field_type == 'DATETIME')
    {
      return $this->date_val;
    }
    return 'Unsupported field type ' . $this->field->field_type . ' in getFormattedValue().';
  }
  
  public function getValue()
  // --------------------------------------------------------------------------
  {
    if ( ($this->field->field_type == 'TEXT') || ($this->field->field_type == 'LINK') )
    {
      return $this->text_val;
    }
    if ($this->field->field_type == 'INTEGER')
    {
      return $this->integer_val;
    }
    if ( ($this->field->field_type == 'NUMERIC') || ($this->field->field_type == 'PERCENTAGE') || ($this->field->field_type == 'CURRENCY') )
    {
      return algaeCore::getFormattedNumber($this->numeric_val, $this->field->num_decimals, null);
    }
    if ($this->field->field_type == 'DATETIME')
    {
      return $this->date_val;
    }
    return 'Unsupported field type ' . $this->field->field_type . ' in getValue().';
  }
  
  /**
   * Get estimated width for an input prompt.
   * @return number The width.
   */
  public function getWidth()
  // --------------------------------------------------------------------------
  {
    if (property_exists($this->field, 'width')) # use reflection to check if member variable width exists
    {
      return $this->field->width;
    }
    if ($this->field->isNumeric())
    {
      return 15;
    }
    return 75;
  }
  
  /**
   * Get an input prompt for the data value.
   * @param string $where_clause Optional where clause to filter the selection items for a text prompt.
   * For example "AND p.dataset_rowid_fk = 49" to only get text values for a specific dataset.  See also
   * visticDatapoint for a working example.
   * @return string Formatted HTML for the input control.
   */
  public function getPrompt($where_clause = null)
  // --------------------------------------------------------------------------
  {
    global $app;
    if ($this->field->isText())
    {
      $sql = "SELECT text_val FROM $this->table_name v";
      $sql .= " INNER JOIN " . $this->parent->table_name . " p ON v.$this->parent_rowid_name = p.rowid";
      $sql .= " WHERE v.field_rowid_fk = " . $this->field->rowid;
      if ($where_clause != null)
      {
        $sql .= $where_clause;
      }
      $sql .= " GROUP BY text_val ORDER BY text_val";
      return algaeForm::inputTextWithSelection('field_' . $this->field->rowid, $this->getValue(), $sql, $this->getWidth());
    }
    elseif ($this->field->isNumeric())
    {
      return algaeForm::inputText('field_' . $this->field->rowid, $this->getValue(), $this->getWidth());
    }
    $app->errorMessage('Field type not supported in algaeTblDataValue.getPrompt().');
    return '';
  }
  
  /**
   * Checks if a data value is set in the object.
   * @return boolean True if set, false otherwise.
   */
  public function isValueSet()
  // --------------------------------------------------------------------------
  {
    if ( ($this->field->isText()) && ($this->text_val != null) )
    {
      return True;
    }
    elseif ( ($this->field->isInteger()) && ($this->integer_val != null) ) 
    {
      return True;
    }
    elseif ( ($this->field->isNumeric()) && ($this->numeric_val != null) )
    {
      return True;
    }
    elseif ( ($this->field->isDateTime()) && ($this->datetime_val != null) )
    {
      return True;
    }
    return False;
  }
  
}