<?php

/**

  algae framework | Generic reporting class.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
*/

class algaeReport
{
  
  const MOST_RECENT_REPORT = '==MostRecent==';
  
  public $report_table_name;
  public $items_table_name;
  public $master_class;
  public $field_class;
  public $rowid;
  public $name;
  public $version;
  public $save_report_page;
  public $load_report_page;
  public $load_report_names_page;
  public $results_tab_name;
  public $first_column_name;
  public $fixed_columns;
  public $default_sort;
  public $final_sql;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    $this->init();
  }
  
  /**
   * Initial default values.
   */
  public function init()
  // --------------------------------------------------------------------------
  {
    $this->report_table_name = 'core.report';
    $this->items_table_name = 'core.report_item';
    $this->save_report_page = 'ajax_save_report.php';
    $this->load_report_page = 'ajax_load_report.php';
    $this->load_report_names_page = 'ajax_load_report_names.php';
    $this->master_class = null;
    $this->field_class = 'algaeTblField';
    $this->rowid = null;
    $this->name = '';
    $this->results_tab_name = 'Results';
    $this->first_column_name  = 'Project';
    $this->version = algaeReport::MOST_RECENT_REPORT;
    $this->fixed_columns = null; // override in derived class if needed
    $this->default_sort = null; // override in derived class if needed
    $this->final_sql = null;  // final SQL used to get data for the report
  }
  
  /**
   * Error message if a method is not implemented.
   * @param string $method Method name.
   */
  protected function errorNotImplemented($method)
  // --------------------------------------------------------------------------
  {
    global $app;
    $app->errorMessage($method . '() not implemented in ' . get_class($this) . '.');
  }
  
  /**
   * Key methods that need to be implemented in the derived class.
   */
  // protected function showResultsTable() { $this->errorNotImplemented(__FUNCTION__); }
  
  /**
   * Update JavaScript with settings from PHP.
   */
  protected function updateJavaScript()
  // --------------------------------------------------------------------------
  {
    $num_errors = 0;
    algaeApp::checkVariable($this->name, 'name', get_class($this), $num_errors);
    algaeApp::checkVariable($this->save_report_page, 'save_report_page', get_class($this), $num_errors);
    if ($num_errors == 0)
    {
      algaeCore::addJavaScript("algaeReport.active = true;");
      algaeCore::addJavaScript("algaeReport.name = '" . $this->name . "';");
      algaeCore::addJavaScript("algaeReport.saveURL = '" . $this->save_report_page . "';");
      algaeCore::addJavaScript("algaeReport.loadURL = '" . $this->load_report_page . "';");
      algaeCore::addJavaScript("algaeReport.getReportListURL = '" . $this->load_report_names_page . "';");
    }
  }
  
  protected function getReportFieldsSQL()
  // --------------------------------------------------------------------------
  {
    $field = new $this->field_class();
    $sql = "SELECT f.* FROM (";
    $sql .= $field->get_sql();
    $sql .= ") f
              RIGHT OUTER JOIN
              (
                SELECT ri.field_rowid_fk, ri.sort_order
                            FROM " . $this->items_table_name . " ri
                            WHERE ri.report_rowid_fk = $1
                            ORDER BY ri.sort_order
              ) sf ON f.rowid = sf.field_rowid_fk
              ORDER BY sf.sort_order";
    return $sql;
  }
  
  /**
   * Read a set of fields, may be available fields or those used in the report.
   */
  protected function readFields($sql, $parms = array())
  // --------------------------------------------------------------------------
  {
    $fields_array = array();
    //
    // ----- setup sql to read fields in standard way, but just for the report fields
    //
    $db = algaeDB::connect();
    if ($db)
    {
      $result = pg_query_params($db, $sql, $parms);
      if (! $result)
      {
        algaeDB::errorWithSQL($sql, $parms);
      }
      //
      // ----- loop through the results
      //
      while ($row = pg_fetch_array($result))
      {
        $f = new $this->field_class();
        $f->readRowFromDatabase($row);
        $fields_array[] = $f;
      }
      algaeDB::close($db, $result);
    }
    return $fields_array;
  }
  
  protected function readSelectedFields()
  // --------------------------------------------------------------------------
  {
    $sql = $this->getReportFieldsSQL();
    echo 'DEBUG: ', $sql, '<p />';
    return $this->readFields($sql, array($this->rowid));
  }
  
  /**
   * Show a field in the list of available or selected fields.
   * @param object $field Field object, typically instance of algaeTblField.
   * @param boolean $includeDelete True to show delete icon.
   */
  protected function showField($field, $includeDelete)
  // --------------------------------------------------------------------------
  {
    echo '<li class="ui-state-default"';
    echo ', rowid="', $field->rowid, '"';
    echo ', title="', $field->description, '"';
    echo '>', $field->name, ' | ', strtolower($field->field_type);
    if ($includeDelete)
    {
      echo '<a href="#" class="delete_item align_right"><img src="/algae/img/red_minus_128px.png" height="18px" width="18px" style="margin-top:3px;" /></a>';
    }
    echo '</li>';
  }
  
  /**
   * Show the available fields.  Each field is a list item like:
   * <li class="ui-state-default", title="Test field 1 title.">Test Field 1</li>
   */
  protected function showAvailableFields()
  // --------------------------------------------------------------------------
  {
    $f = new $this->field_class();
    $sql = $f->get_sql() . " ORDER BY $f->table_name.name";
    $available_fields = $this->readFields($sql);
    foreach ($available_fields as $field)
    {
      $this->showField($field, False);
    }
  }
  
  /**
   * Load and show fields in a report.
   */
  protected function showSelectedFields()
  // --------------------------------------------------------------------------
  {
    if ($this->rowid > 0)
    {
      $selected_fields = $this->readSelectedFields();
      foreach ($selected_fields as $field)
      {
        $this->showField($field, True);
      }
    }
  }
  
  /**
   *
   */
  protected function getFieldsForSQLSelect($fields)
  // --------------------------------------------------------------------------
  {
    $sql = '';
    foreach ($fields as $key => $field)
    {
      //
      // ----- if name is not important should change this to a for loop
      //       by number of items in the future
      //
      $sql .= ', field' . $key . '.val AS field' . $key;
    }
    return $sql;
  }
  
  protected function getJoinsForSQLSelect($fields)
  // --------------------------------------------------------------------------
  {
    $sql = '';
    foreach ($fields as $key => $field)
    {
      $sql .= ' LEFT OUTER JOIN (';
      $sql .= $field->calc_sql;
      $sql .= ') field' . $key . ' ON field' . $key . '.rowid = m.rowid';
    }
    return $sql;
  }
  
  /**
   * Show the options tab, override in the derived class.
   */
  protected function showOptionsTab()
  // --------------------------------------------------------------------------
  {
  }
  
  /**
   * Show the tabbed options for the report.
   */
  protected function showOptions()
  // --------------------------------------------------------------------------
  {
    $f = new algaeForm();
    $html = '<span id="download_link" class="align_right"></span><span id="download_link" class="align_right"></span>';
    $f->startOptionsForm(algaeForm::getDefaultToken($this), 'none', $html);
    echo '<p />';
    algaeForm::startTabs(array(
      array('#fields_tab', 'Fields'),
      array('#options_tab', 'Options')
    ));
    //
    // ----- containers for fields tab and field divs
    //
    echo '<div id="fields_tab">';
    echo '<div id="fields_container">';
    echo '<div id="fields_left_container">';
    //
    // ----- available fields div
    //
    echo '<div id="available_fields_div">';
    echo '<div class="fields_header">';
    echo 'Available Fields (drag and drop to report fields)';
    echo '</div>';
    echo '<ul id="available_fields_list" class="available_fields_list">';
    $this->showAvailableFields();
    echo '</ul>';
    echo '</div>';
    //
    // ----- fields filter div
    //
    echo '<div id="fields_filter_div">';
    echo 'Filter: <input type="text" id="fields_filter" size="50" />';
    echo '</div>';
    echo '</div>';
    echo '<div id="fields_right_container">';
    //
    // ----- selected fields div
    //
    echo '<div id="selected_fields_div">';
    echo '<div class="fields_header">';
    echo 'Report Fields  (drag and drop to reorder)';
    echo '</div>';
    echo '<ul id="selected_fields_list" class="selected_fields_list">';
    $this->showSelectedFields();
    echo '</ul>';
    echo '</div>';
    //
    // ----- div and buttons for pre-defined reports
    //
    echo '<div id="predefined_buttons_div">';
    echo 'Custom report: ';
    echo algaeForm::button('saveReport', 'Save');
    echo '&nbsp;';
    echo algaeForm::button('loadReport', 'Load');
    echo '</div>';
    echo '</div>';
    //
    // ----- div ends
    //
    echo '</div>';  // end fields_container
    echo '</div>';  // end fields_tab
    //
    // ----- options tab
    //
    echo '<div id="options_tab">';
    $this->showOptionsTab();
    echo '</div>';
    algaeForm::endTabs('tabs', '400px');
    $f->endOptionsForm();
  }
  
  /**
   * Read the rowid for the most recent report.
   */
  protected function readReportRowid()
  // --------------------------------------------------------------------------
  {
    $sql = "SELECT rowid FROM core.report WHERE name = $1 AND version = $2 AND user_rowid_fk = ";
    $sql .= algaeAccess::getRowidSQLforUsername(algaeAccess::getUsername());
    $this->rowid = algaeDB::getScalarInteger($sql, array($this->name, $this->version), 0);
  }
  
  /**
   * Show the report.
   * @param boolean $includeResultsTab True (default) to include a 'Results' tab containing the report.
   */
  public function showReport($includeResultsTab = True, $addActions = False, $addSQLTab = False)
  // --------------------------------------------------------------------------
  {
    if (! $addSQLTab)
    {
      $tab = 'report_tab';
      if ($includeResultsTab) algaeForm::startSingleTab($this->results_tab_name, $tab);
      $this->showResultsTable($addActions);
      if ($includeResultsTab) algaeForm::endSingleTab($tab);
    }
    else 
    {
      $id = 'results_and_sql_tab';
      algaeForm::startTabs(array(
        array('#report_tab', $this->results_tab_name),
        array('#sql_tab', 'SQL')
      ), $id);
      //
      // ----- report_tab
      //
      echo '<div id="report_tab">';
      $this->showResultsTable($addActions);
      echo '</div>';
      //
      // ----- sql_tab
      //
      echo '<div id="sql_tab">';
      echo SqlFormatter::format(algaeDB::getSQLFromQueryAndParams($this->final_sql, array()));
      echo '</div>';
      //
      // ----- end tabs
      //
      algaeForm::endTabs($id);
    }
  }
  
  /**
   * Show the report.
   */
  public function show($addActions = False, $addSQLTab = False)
  // --------------------------------------------------------------------------
  {
    $this->readReportRowid();
    $this->updateJavaScript();
    $this->showOptions();
    echo '<p />';
    $this->showReport(True, $addActions, $addSQLTab);
  }
  
  /**
   * Save the report header, or if it already exists get the rowid.
   */
  protected function saveReport()
  // --------------------------------------------------------------------------
  {
    $this->readReportRowid();
    if ($this->rowid <= 0)
    {
      //
      // -----SQL to insert a new record
      //
      $sql = "INSERT INTO $this->report_table_name (user_rowid_fk, name, version) VALUES (";
      $sql .= algaeAccess::getRowidSQLforUsername(algaeAccess::getUsername());
      $sql .= ", " . algaeDB::getStringOrNull($this->name);
      $sql .= ", " . algaeDB::getStringOrNull($this->version);
      $sql .= ")";
      //
      // ----- insert and get the rowid for the new record
      //
      $this->rowid = algaeDB::executeInsert($sql, array());
    }
  }
  
  /**
   * Save the fields in a report.
   * @param array $data Array of arrays with a named index field_rowid on the inner arrays.
   */
  protected function writeFieldsFromRowids($data)
  // --------------------------------------------------------------------------
  {
    $sort_order = 1;
    $num_errors = 0;
    algaeDB::deleteFromTable($this->items_table_name, 'report_rowid_fk', $this->rowid);
    if (isset($data) && (count($data) > 0))
    {
      foreach ($data as $row)
      {
        $field_rowid_fk = $row['field_rowid'];
        $sql = "INSERT INTO $this->items_table_name (report_rowid_fk, field_rowid_fk, sort_order) VALUES (";
        $sql .= algaeDB::getNumericOrNull($this->rowid, 0, 0);
        $sql .= ", " . algaeDB::getNumericOrNull($field_rowid_fk, 0, 0);
        $sql .= ", " . algaeDB::getNumericOrNull($sort_order, 0, 0);
        $sql .= ")";
        $rowid = algaeDB::executeInsert($sql, array());
        if ($rowid > 0)
        {
          $sort_order++;
        }
        else
        {
          $num_errors++;
        }
      }
    }
    if ($num_errors == 0) return True;
    return False;
  }
  
  /**
   * Save any optional data, override in derived class.
   * @param array $data Array with one JSON object.
   * @return boolean True on success, False on fail.
   */
  protected function saveOptionalData($data)
  // --------------------------------------------------------------------------
  {
    //
    // ----- example access
    // error_log('DEBUG: num = ' . count($data), 0);
    // error_log('DEBUG: group = ' . $data['group'], 0);
    //
    return True;
  }
  
  /**
   * Save a report, this is via an AJAX call.
   */
  public function save()
  // --------------------------------------------------------------------------
  {
    $num_errors = 0;
    $data = null;
    $optionalData = null;
    if (isset($_POST['name']))$this->name = $_POST['name'];
    if (isset($_POST['version']))$this->version = $_POST['version'];
    if (isset($_POST['data'])) $data = $_POST['data'];
    if (isset($_POST['optionalData'])) $optionalData = $_POST['optionalData'];
    if (strlen($this->version) == 0) $this->version = algaeReport::MOST_RECENT_REPORT;
    /*
    error_log('DEBUG: report name = ' . $this->name, 0);
    error_log('DEBUG: report version = ' . $this->version, 0);
    error_log('DEBUG: username = ' . algaeAccess::getUsername(), 0);
    */
    //
    // ----- save report and fields
    //
    $this->saveReport();
    if ($this->rowid > 0)
    {
      if (! $this->writeFieldsFromRowids($data))
      {
        $num_errors++;
      }
      if ($optionalData != null)
      {
        if (! $this->saveOptionalData($optionalData))
        {
          $num_errors++;
        }
      }
    }
    else 
    {
      $num_errors++;  
    }
    //
    // ----- return success or fail
    //
    $status = 'success';
    if ($num_errors > 0) $status = 'fail';
    echo json_encode(array('status'=>$status));
    exit;
  }
  
  /**
   * Load a report, this is via an AJAX call.
   */
  public function load()
  // --------------------------------------------------------------------------
  {
    exit;
  }
  
  /**
   * Load a list of reports via an AJAX call.
   */
  public function loadReportsList()
  // --------------------------------------------------------------------------
  {
    $sql = "SELECT rowid, version
            FROM core.report
            WHERE name = $1 AND user_rowid_fk = $2 AND version <> '==MostRecent=='
            ORDER BY version";
    $reports_list = algaeDB::getArray($sql, array($this->name, algaeAccess::getUserRowid()));
    //
    // -----
    //
    $status = 'fail';
    $names_array = array();
    if (count($reports_list) > 0)
    {
      $status = 'success';
      foreach ($reports_list as $row)
      {
        $names_array[] = array('rowid'=>$row[0], 'name'=>$row[1]);
      }
    }
    # error_log('DEBUG: ' . json_encode(array('status'=>$status, 'num_reports'=>count($names_array), 'reports'=>$names_array), JSON_NUMERIC_CHECK), 0);
    echo json_encode(array('status'=>$status, 'num_reports'=>count($names_array), 'reports'=>$names_array), JSON_NUMERIC_CHECK);
    exit;
  }
  
  /**
   * Get the main fields for the report, typically used to display the first constant columns in a table and/or
   * for optional filters.
   * @return string The main fields.
   */
  protected function getMainFields()
  // --------------------------------------------------------------------------
  {
    return "SELECT m.rowid, m.name";
  }
  
  /**
   * Get end of SQL, typically used in a derived class to add a report level filter.
   * @return string
   */
  protected function getSQLEnd()
  // --------------------------------------------------------------------------
  {
    return "";
  }
  
  /**
   * Read the main fields.  Can be overridden to read additional main fields.
   * @param object $item Instance of the master class.
   * @param array $row Row read from the database.
   * @return number Position of next column to read from the row.
   */
  protected function readMainFields($item, $row)
  // --------------------------------------------------------------------------
  {
    $cur = 0;
    $item->rowid = algaeDB::cleanDataRead($row[$cur++]);
    $item->name = algaeDB::cleanDataRead($row[$cur++]);
    return $cur;
  }
  
  protected function writeFixedColumns($item, $addActions)
  // --------------------------------------------------------------------------
  {
    if ($addActions)
    {
      algaeTable::writeData($item->getActionLinks(True), False);
    }
    algaeTable::writeData($item->getHomepageLink($item->name, algaeAccess::ROLE_READ, True), False);
  }
  
  protected function showResultsTable($addActions = False)
  // --------------------------------------------------------------------------
  {
    //
    // ----- read the fields (columns in the report)
    //
    $fields = $this->readSelectedFields();
    //
    // ----- setup sql
    //
    $class = $this->master_class;
    $item = new $class();
    $sql = $this->getMainFields();
    $sql .= $this->getFieldsForSQLSelect($fields);
    $sql .= " FROM $item->table_name m";
    //
    // ----- add joins and finish SQL
    //
    $sql .= $this->getJoinsForSQLSelect($fields);
    $sql .= $this->getSQLEnd();
    $this->final_sql = $sql;
    //
    // ----- connect and read the data
    //
    $db = algaeDB::connect();
    if ($db)
    {
      $parms = array();
      $result = pg_query_params($db, $sql, $parms);
      if (! $result)
      {
        algaeDB::errorWithSQL($sql, $parms);
      }
      else
      {
        //
        // ----- initial the table
        //
        $tableId = 'resultsTable';
        $sort = '[[0,0]]';
        if ($addActions)
        {
          $sort = '[[1,0]]';
        }
        if ($this->default_sort != null) $sort = $this->default_sort;
        algaeTable::initTablesorterJavascript($tableId, $sort);
        algaeTable::start($tableId, 'tablesorter', 'width:100%;');
        //
        // ----- table header
        //
        $header_array = array();
        if ($this->fixed_columns == null)
        {
          if ($addActions)
          {
            $header_array[] = array('Actions', '10%');
          }
          $header_array[] = array($this->first_column_name, '20%');
        }
        else 
        {
          $header_array = $this->fixed_columns; 
        }
        //
        // ----- add dynamic columns
        //
        if (count($fields) > 0)
        {
          $width = floor(80 / count($fields)) . '%';
          foreach ($fields as $field)
          {
            $header_array[] = array($field->name, $width);
          }
        }
        algaeTable::writeHeader($header_array, True);
        //
        // ----- loop through the results
        //
        while ($row = pg_fetch_array($result))
        {
          $item->init();
          $cur = $this->readMainFields($item, $row);
          echo '<tr>';
          $this->writeFixedColumns($item, $addActions);
          foreach ($fields as $field)
          {
            $val = algaeDB::cleanDataRead($row[$cur++]);
            algaeTable::writeData($field->getFormattedValue($val, False), False);
          }
          echo '</tr>';
        }
        algaeTable::end();
      }
      algaeDB::close($db, $result);
    }
  }
  
  public static function twoTabbedReportWithSQL($report_function, $sql, $sql_parms = array(), $tab1_name = 'Data')
  // --------------------------------------------------------------------------
  {
    algaeForm::startTabs(array(
      array('#data_tab', $tab1_name),
      array('#sql_tab', 'SQL')
    ));
    //
    // ----- data_tab
    //
    echo '<div id="data_tab">';
    $report_function();
    echo '</div>';
    //
    // ----- sql_tab
    //
    echo '<div id="sql_tab">';
    echo SqlFormatter::format(algaeDB::getSQLFromQueryAndParams($sql, $sql_parms));
    echo '</div>';
    algaeForm::endTabs();
  }
  
}
