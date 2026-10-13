<?php

/**

  algae framework | Base class for data fields and ref.field (or derivatives).
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblField extends algaeTblReferenceBase
{
  
  const NO_COLOR_INDEX = -99;
  const DEFAULT_COLOR = '#FFFFFF00';
  
  public $field_type;
  public $num_decimals;
  public $in_report;
  public $calc_sql;
  
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
    $this->table_name = 'ref.field';
    $this->field_type = new algaeTblRefFieldType();
    $this->num_decimals = 2;
    $this->homepage = 'field.php';
    $this->editpage = 'edit_field.php';
    $this->in_report = False;
    $this->calc_sql = null;
  }
  
  public function isText()
  // --------------------------------------------------------------------------
  {
    if ( ($this->field_type->name == 'TEXT') || ($this->field_type->name == 'LINK') )
    {
      return True;
    }
    return False;
  }
  
  public function isInteger()
  // --------------------------------------------------------------------------
  {
    if ($this->field_type->name == 'INTEGER')
    {
      return True;
    }
    return False;
  }
  
  public function isNumeric()
  // --------------------------------------------------------------------------
  {
    if ( ($this->field_type->name == 'NUMERIC') || ($this->field_type->name == 'INTEGER') ||
      ($this->field_type->name == 'SCORE') || ($this->field_type->name == 'PERCENTAGE') ||
      ($this->field_type->name == 'CURRENCY') || ($this->field_type->name == 'MODEL')
      )
    {
      return True;
    }
    return False;
  }
  
  public function isDateTime()
  // --------------------------------------------------------------------------
  {
    if ($this->field_type->name == 'DATETIME')
    {
      return True;
    }
    return False;
  }
  
  public function isSetup()
  // --------------------------------------------------------------------------
  {
    if ( ($this->rowid != null) && ($this->rowid > 0) && ($this->name != null) && (strlen($this->name) > 0) )
    {
      return True;
    }
    return False;
  }
  
  protected function addDerivedFieldsToForm()
  // --------------------------------------------------------------------------
  {
    parent::addDerivedFieldsToForm();
    algaeTable::writeTwoColumns('Field Type', $this->field_type->getControl($this), False);
    algaeTable::writeTwoColumns('Num Decimals', algaeForm::inputText($this->get_control_id('num_decimals'),
      $this->num_decimals, 10, algaeForm::REQUIRED), False);
  }
 
  
  protected function addDerivedFieldsToOverallDetails()
  // --------------------------------------------------------------------------
  {
    algaeTable::writeTwoColumns('Type', $this->field_type->name);
    algaeTable::writeTwoColumns('Num Decimals', $this->num_decimals);
    algaeTable::writeTwoColumns('Sort Order', $this->sort_order);
    algaeTable::writeTwoColumns('SQL', $this->calc_sql);
  }
  
  /**
   * Report records.
   * @param string $tableId Id for HTML table.
   */
  public function reportRecords($tableId = 'fieldsTable', $whereClause = '', $maxRecords = 10000)
  // --------------------------------------------------------------------------
  {
    $class = get_class($this);
    $f = new $class(); 
    $sql = $f->get_sql(True);
    //
    // ----- run the query
    //
    $db = algaeDB::connect();
    if ($db)
    {
      $result = pg_query($db, $sql);
      if (! $result)
      {
        algaeDB::errorWithSQL($sql);
      }
      else
      {
        //
        // ----- display the result rows if there are any
        //
        $num_rows = pg_num_rows($result);
        if ($num_rows > 0)
        {
          //
          // ----- initial the table
          //
          algaeTable::initTablesorterJavascript($tableId, '[[1,0]]');
          algaeTable::start($tableId, 'tablesorter', 'width:100%;');
          //
          // ----- table header
          //
          $header_array = array(
            array('Action', '15%'),
            array('Sort Order', '10%'),
            array('Field', '20%'),
            array('Type', '10%'),
            array('Num Decimals', '15%'),
            array('Description', '30%')
          );
          algaeTable::writeHeader($header_array, True);
          //
          // ----- loop through the results
          //
          while ($row = pg_fetch_array($result))
          {
            $f->init();
            $f->read_row_from_database_with_rowid($row[0]);
            $f->in_report = True;
            echo '<tr>';
            algaeTable::writeData($f->getActionLinks(), False);
            algaeTable::writeData($f->sort_order);
            algaeTable::writeData($f->getHomepageLink(), False);
            algaeTable::writeData($f->field_type->name);
            algaeTable::writeData($f->num_decimals);
            algaeTable::writeData(algaeCore::getStringWithLinks($f->description), False);
            echo '</tr>';
          }
          algaeTable::end();
        }
        else
        {
          echo 'No data in ', $f->table_name, '.<p />';
        }
        algaeDB::close($db, $result);
      }
    }
  }
  
  /**
   * Get a formatted value based on the field type.
   * @param string $value Value to format.
   * @param boolean $include_color Include a color based on data breaks or categories if applicable.
   * @return string Formatted value.
   */
  public function getFormattedValue($value, $include_color = true)
  // --------------------------------------------------------------------------
  {
    $color_block = '';
    if ($include_color) $color_block = $this->getColorBlock($value);
    if (strlen($color_block) > 0) $color_block .= '&nbsp;';
    if ($this->field_type->name == 'TEXT')
    {
      return $color_block . $value;
    }
    if ( ($this->field_type->name == 'NUMERIC') || ($this->field_type->name == 'SCORE') || ($this->field_type->name == 'INTEGER') || ($this->field_type->name == 'MODEL') )
    {
      return $color_block . algaeCore::getFormattedNumber($value, $this->num_decimals, -99);
    }
    if ($this->field_type->name == 'PERCENTAGE')
    {
      return $color_block . algaeCore::getFormattedPercentage($value, $this->num_decimals, -99);
    }
    if ($this->field_type->name == 'CURRENCY')
    {
      return $color_block . algaeCore::getFormattedDollarAmount($value, $this->num_decimals, -99);
    }
    if ($this->field_type->name == 'LINK')
    {
      return $color_block . algaeCore::convertURLsToHrefs($value);
    }
    return $value;
  }
  
  /**
   * Show what will be deleted.
   * {@inheritDoc}
   * @see algaeTblNamedObjectBase::showWhatsBeingDeleted()
   */
  public function showWhatsBeingDeleted()
  // --------------------------------------------------------------------------
  {
    parent::showWhatsBeingDeleted();
    $b = new algaeTblBreak();
    $num_breaks = $b->getNumBreaks($this->rowid);
    if ($num_breaks > 0)
    {
      echo $num_breaks, ' color break(s) from ', $b->table_name, '.<p />';
    }
  }
  
  /**
   * Delete the item and everywhere it's used, delete order is important.
   * {@inheritDoc}
   * @see algaeTblBase::delete()
   */
  public function delete()
  // --------------------------------------------------------------------------
  {
    $num_errors = 0;
    //
    // ----- delete color breaks
    //
    if ($num_errors == 0)
    {
      $b = new algaeTblBreak();
      if (! $b->deleteBreaks($this->rowid))
      {
        $num_errors += 1;
      }
    }
    //
    // ----- delete field
    //
    if ($num_errors == 0)
    {
      if (! parent::delete())
      {
        $num_errors += 1;
      }
    }
    if ($num_errors == 0) return True;
    return False;
  }
  
  /**
   * Select fields of a specific type.  Uses passed and class attributes in a call to algaeForm::selectWithSQL().
   * @param string $id Control id.
   * @param mixed $default Default value.
   * @param string $types Allowed types as a string in the where clause, for example: "IN ('NUMERIC', 'INTEGER')".
   * @param boolean $required True/False (default) if required.
   * @param array $attributes Array of additional attributes.
   * @return string HTML string that displays the select control.
   */
  public function selectFieldsOfType($id, $default, $types, $required=False, $attributes=[])
  // --------------------------------------------------------------------------
  {
    $sql = "SELECT DISTINCT $this->table_name.name FROM $this->table_name INNER JOIN ref.field_type ON $this->table_name.field_type_rowid_fk = ref.field_type.rowid";
    $sql .= " WHERE ref.field_type.name $1 ORDER BY $this->table_name.name";
    $sql = str_replace('$1', $types, $sql);
    return algaeForm::selectWithSQL($sql, $id, $default, $required, $attributes);
  }
  
  /**
   * Select fields of a numeric type.
   * @param string $id Control id.
   * @param mixed $default Default value.
   * @param boolean $required True/False (default) if required.
   * @param array $attributes Array of additional attributes.
   * @return string HTML string that displays the select control.
   */
  public function selectNumericFields($id, $default, $required=False, $attributes=[])
  // --------------------------------------------------------------------------
  {
    return $this->selectFieldsOfType($id, $default, "IN ('NUMERIC', 'INTEGER')", $required, $attributes);
  }
  
  /**
   * Select fields of a text type.
   * @param string $id Control id.
   * @param mixed $default Default value.
   * @param boolean $required True/False (default) if required.
   * @param array $attributes Array of additional attributes.
   * @return string HTML string that displays the select control.
   */
  public function selectTextFields($id, $default, $required=False, $attributes=[])
  // --------------------------------------------------------------------------
  {
    return $this->selectFieldsOfType($id, $default, "IN ('TEXT')", $required, $attributes);
  }
  
}


