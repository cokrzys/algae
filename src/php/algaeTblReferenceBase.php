<?php

/**

  algae framework | Base class for reference tables.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblReferenceBase extends algaeTblNamedObjectBase
{
  
  public $sort_order;
  
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
    $this->table_name = 'algae.reference_base';
    $this->sort_order = 1;
  }
  
  /**
   * Write the header for the records table.
   */
  protected function writeRecordsTableHeader()
  // --------------------------------------------------------------------------
  {
    $header_array = array(
      array('Action', '10%'),
      array('Rowid', '7%'),
      array('Name', '20%'),
      array('Color', '10%'),
      array('Sort Order', '10%'),
      array('Status', '10%'),
      array('Description', '33%')
    );
    algaeTable::writeHeader($header_array, True);
  }
  
  /**
   * Write the record to a table.
   */
  protected function writeRecordToTable()
  // --------------------------------------------------------------------------
  {
    algaeTable::writeData($this->getActionLinks(), False);
    algaeTable::writeData($this->rowid);
    algaeTable::writeData($this->getHomepageLink(), False);
    algaeTable::writeData(algaeCore::getColorBlock($this->html_color, True), False);
    algaeTable::writeData($this->sort_order);
    algaeTable::writeData($this->record_status->name);
    algaeTable::writeData(algaeCore::getStringWithLinks($this->description), False);
  }
  
  /**
   * Tabular report of records.
   * {@inheritDoc}
   * @see algaeTblBase::reportRecords()
   */
  public function reportRecords($tableId = 'objectsTable', $whereClause = '', $maxRecords = 10000)
  // --------------------------------------------------------------------------
  {
    global $app;
    if ($this->numVariableErrors() == 0)
    {
      if ($tableId == 'objectsTable') $tableId = str_replace(' ', '', $this->itemName) . 'sTable';
      $sql = $this->get_sql(true);
      $sql .= ' ' . $whereClause;
      //
      // ----- setup add link, some detail to get the language right
      //
      $first_letter = strtoupper($this->itemName[0]);
      $prep = 'a';
      if ($first_letter == 'A') $prep = 'an';
      $add_link = $app->getPageLink($this->editpage, 'Add ' . $prep . ' '. $this->itemName, algaeAccess::ROLE_WRITE, $app->settings->appName, '') . '<p />';
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
            echo $add_link;
            //
            // ----- initial the table
            //
            $tableId = $this->getDefaultRecordsTableId($tableId);
            algaeTable::initTablesorterJavascript($tableId, '[[2,0]]');
            algaeTable::start($tableId, 'tablesorter', 'width:100%;');
            $this->writeRecordsTableHeader();
            //
            // ----- loop through the results
            //
            $class = get_class($this);
            $o = new $class();
            while ($row = pg_fetch_array($result))
            {
              $o->init();
              $o->read_row_from_database_with_rowid($row[0]);
              echo '<tr>';
              $o->writeRecordToTable();
              echo '</tr>';
            }
            algaeTable::end();
          }
          else
          {
            echo $add_link;
          }
          algaeDB::close($db, $result);
        }
      }
    }
  }
  
  public function getControlTesting($calling_class, $required = True, $column_name = null)
  // --------------------------------------------------------------------------
  {
    $html = '';
    $col = $column_name;
    if ($col == null)
    {
      if (strpos($this->table_name, '.') !== false)
      {
        $parts = explode('.', $this->table_name);
        if (count($parts) == 2)
        {
          $col = $parts[1] . '_rowid_fk';
        }
      }
    }
    if ($col != null)
    {
      # TODO: What if no calling class.
      $html = algaeForm::selectWithTableAndFieldWithRowid($this->table_name, 'name',
        $calling_class->get_control_id($col), $this->name, $required);
    }
    return $html;
  }
  
}

