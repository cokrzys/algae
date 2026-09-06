<?php

/**

  algae framework | Base class for accessing a database table.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblBase
{
  
  const DEX_DOT = '_dex_dot_';  # substitute for a dot (period) in an html control id which is not supported in a POST
  
  const REQUIRED_PROP_NAME = 'required';
  const UNIQUE_KEY_PROP_NAME = 'uniqueKey';
  const UNIQUE_PROP_NAME = 'unique';
  const RELATIONSHIPS_PROP_NAME = 'relationships';
  const JOIN_SQL_PROP_NAME = 'joinSQL';
  
  public $database;
  public $table_name;
  public $rowid;
  public $timestamp_loaded_utc;
  public $timestamp_modified_utc;
  public $homepage;
  public $editpage;
  public $deletepage;
  public $browsepage;
  public $itemName;
  public $itemNamePlural;
  public $showBrowsePageLink;
  public $debug;
  public $inserted;
  public $updated;
  
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
    global $app;
    $this->database = $app->config->app_database;
    $this->table_name = 'algae.base';
    $this->rowid = null;
    $this->timestamp_loaded_utc = null;
    $this->timestamp_modified_utc = null;
    $this->homepage = null;
    $this->editpage = null;
    $this->deletepage = null;
    $this->browsepage = null;
    $this->itemName = 'Object';
    $this->itemNamePlural = null;  // leave null to automatically determine the plural name
    $this->showBrowsePageLink = True;
    $this->debug = False;
    $this->inserted = False;
    $this->updated = False;
  }
  
  protected function addDerivedFieldsToForm() {}
  protected function addDerivedFieldsToOverallDetails() {}
  protected function preInsert() { return True; }
  protected function preUpdate() { return True; }
  protected function postInsert() { return True; }
  protected function postUpdate() { return True; }
  protected function processDerivedVariables() {}
  
  protected function errorNotImplemented($method)
  // --------------------------------------------------------------------------
  {
    global $app;
    $app->errorMessage($method . '() not implemented in ' . get_class($this) . '.');
  }
  
  protected function errorVariableNotSet($varName)
  // --------------------------------------------------------------------------
  {
    global $app;
    $app->errorMessage($varName . ' not set in ' . get_class($this) . '.');
  }
  
  protected function checkVariable($var, $name, &$num_errors)
  // --------------------------------------------------------------------------
  {
    if ( (! isset($var)) || (strlen($var) == 0) || ($var == null) )
    {
      $this->errorVariableNotSet($name);
      $num_errors++;
      return false;
    }
    return true;
  }
  
  /**
   * Check to make sure key member variables are defined.
   * @return number Number of errors, > 0 if problems.
   */
  public function numVariableErrors()
  // --------------------------------------------------------------------------
  {
    $errors = 0;
    $this->checkVariable($this->table_name, 'table_name', $errors);
    $this->checkVariable($this->homepage, 'homepage', $errors);
    return $errors;
  }
  
  protected function get_array_from_dex($table_name, $property_name)
  // --------------------------------------------------------------------------
  {
    global $app;
    $columns = array();
    $table_found = false;
    $index = 0;
    while ( (! $table_found) && ($index < count($app->config->dex_json)) )
    {
      if ($app->config->dex_json[$index]->tableName == $table_name)
      {
        # echo 'DEBUG: Found match for table ', $table_name, '<p />';
        # echo 'DEBUG: Parent = [', $app->config->dex_json[$index]->parentTableName, ']<p />';
        
        if ($app->config->dex_json[$index]->parentTableName != null)
        {
          $columns = array_merge($columns, $this->get_array_from_dex($app->config->dex_json[$index]->parentTableName, $property_name));
        }
        if (property_exists($app->config->dex_json[$index], $property_name))
        {
          $columns = array_merge($columns, $app->config->dex_json[$index]->{$property_name});
        }
        $table_found = true;
      }
      else 
      {
        $index += 1;  
      }
    }
    if ($table_found === false)
    {
      echo 'ERROR: Data exchange parameters not found for table ', $table_name, PHP_EOL;
    }
    return $columns;
  }
  
  public function get_columns()
  // --------------------------------------------------------------------------
  {
    return $this->get_array_from_dex($this->table_name, 'columns');
  }
  
  /**
   * MAYBE OBSOLETE AND NOT NEEDED
   * Relationships can be worked out with reflection in read_extra_data().
   * @return array
   */
  public function get_relationships()
  // --------------------------------------------------------------------------
  {
    return $this->get_array_from_dex($this->table_name, algaeTblBase::RELATIONSHIPS_PROP_NAME);
  }
  
  public function print_columns($columns)
  // --------------------------------------------------------------------------
  {
    foreach ($columns as $column)
    {
      echo $column->name, '<p />';
    }
  }
  
  protected function get_columns_with_default_true($prop)
  // --------------------------------------------------------------------------
  {
    $return_columns = array();
    $columns = $this->get_columns();
    foreach ($columns as $column)
    {
      if ( (! property_exists($column, $prop)) || ($column->{$prop} === True) )
      {
        $return_columns[] = $column;
      }
    }
    return $return_columns;
  }
  
  protected function get_column_property($column, $property_name, $default)
  // --------------------------------------------------------------------------
  {
    if (property_exists($column, $property_name))
    {
      return $column->{$property_name};
    }
    return $default;
  }
  
  protected function get_columns_with_property($prop, $val)
  // --------------------------------------------------------------------------
  {
    $return_columns = array();
    $columns = $this->get_columns();
    foreach ($columns as $column)
    {
      if ( (property_exists($column, $prop)) && ($column->{$prop} === $val) )
      {
        $return_columns[] = $column;
      }
    }
    return $return_columns;
  }
  
  public function get_read_columns()
  // --------------------------------------------------------------------------
  {
    return $this->get_columns_with_default_true('canRead');
  }
  
  public function get_insert_columns()
  // --------------------------------------------------------------------------
  {
    return $this->get_columns_with_default_true('canInsert');
  }
  
  public function get_update_columns()
  // --------------------------------------------------------------------------
  {
    return $this->get_columns_with_default_true('canUpdate');
  }
  
  public function get_columns_for_name($name)
  // --------------------------------------------------------------------------
  {
    return $this->get_columns_with_property('name', $name);
  }
  
  public function get_unique_key_columns()
  // --------------------------------------------------------------------------
  {
    return $this->get_columns_with_property('uniqueKey', True);
  }
  
  public function get_column_value_for_key($column, $key)
  // --------------------------------------------------------------------------
  {
    if (property_exists($column, $key))
    {
      return $column->{$key};
    }
    return $column->{'name'};
  }
  
  public function get_class_variable_name($column)
  // --------------------------------------------------------------------------
  {
    return $this->get_column_value_for_key($column, 'classVariableName');
  }
  
  public function get_form_variable_name($column)
  // --------------------------------------------------------------------------
  {
    return $this->get_column_value_for_key($column, 'formVariableName');
  }
  
  public function get_fields()
  // --------------------------------------------------------------------------
  {
    $prop_table_name = 'table_name';  # property of the local class
    $altReadSQLProp = 'altReadSQL';  # property of the dex JSON
    //
    //
    //
    if (! property_exists($this, $prop_table_name))
    {
      algaeApp::errorMessage('Missing ' . $prop_table_name . ' attribute in class ' . get_class($this) . '.');
      return '';
    }
    //
    //
    //
    $separator = '';
    $columns = $this->get_read_columns();
    $sql = "";
    foreach ($columns as $column)
    {
      $cname = '';
      if (property_exists($column, $altReadSQLProp))
      {
        # use alternate sql from json
        $cname = str_replace('{' . $prop_table_name . '}', $this->{$prop_table_name}, $column->{$altReadSQLProp});
      }
      else 
      {
        # build name from table and column
        $cname = $this->{$prop_table_name} . '.' . $column->name;
      }
      $sql .= $separator . $cname;
      $separator = ', ';
    }
    return $sql;
  }
  
  public function get_joins()
  // --------------------------------------------------------------------------
  {
    $sql = " FROM " . $this->table_name;
    $relationships = $this->get_relationships();
    foreach ($relationships as $relationship)
    {
      if (property_exists($relationship, algaeTblBase::JOIN_SQL_PROP_NAME))
      {
        $sql .= ' ' . $relationship->{algaeTblBase::JOIN_SQL_PROP_NAME};
      }
    }
    return $sql;
  }
  
  public function get_sql($rowid_only = false)
  // --------------------------------------------------------------------------
  {
    $fields = '';
    if ($rowid_only)
    {
      $fields = $this->table_name . '.rowid';
    }
    else
    {
      $fields = $this->get_fields();
    }
    return "SELECT " . $fields . $this->get_joins();
  }
  
  public function read_row_from_database($row)
  // --------------------------------------------------------------------------
  {
    $columns = $this->get_read_columns();
    foreach ($columns as $index=>$column)
    {
      $classVariableName = $this->get_class_variable_name($column);
      # echo 'DEBUG: ', $classVariableName, '<p />';
      if (strpos($classVariableName, '.') !== false)
      {
        $parts = explode('.', $classVariableName);
        if (count($parts) == 2)
        {
          $this->{$parts[0]}->{$parts[1]} = $row[$index];
        }
      }
      else
      {
        $this->{$classVariableName} = $row[$index];
      }
    }
  }
  
  protected function read_extra_data()
  // --------------------------------------------------------------------------
  {
    foreach ($this as $obj) 
    {
      if (gettype($obj) == 'object')
      {
        if (method_exists($obj, 'read_row_from_database_with_rowid'))
        {
          $obj->read_row_from_database_with_rowid($obj->rowid, True);  
        }
      }
    }
  }
  
  public function read_row_from_database_with_sql($sql, $parms, $deep_read = True)
  // --------------------------------------------------------------------------
  {
    $data = algaeDB::getArray($sql, $parms);
    if ( ($data != Null) && (count($data) == 1) )
    {
      $this->read_row_from_database($data[0]);
      if ($deep_read)
      {
        $this->read_extra_data();
      }
      return true;
    }
    return false;
  }
  
  protected function get_rowid_read_sql()
  // --------------------------------------------------------------------------
  {
    $sql = $this->get_sql();
    $sql .= " WHERE $this->table_name.rowid = $1";
    return $sql;
  }
  
  public function read_row_from_database_with_rowid($rowid, $deep_read = True)
  // --------------------------------------------------------------------------
  {
    $sql = $this->get_rowid_read_sql();
    return $this->read_row_from_database_with_sql($sql, array($rowid), $deep_read);
  }
  
  protected function getPageLink($page, $label = 'Edit', $openInNewTab = True)
  // --------------------------------------------------------------------------
  {
    global $app;
    $html = '';
    if (strlen($page) > 0)
    {
      $html = $app->getPageLink($app->getURLBase() . $page . '?rowid=' . $this->rowid, $label, algaeAccess::ROLE_WRITE, $app->settings->appName, '', $openInNewTab);
    }
    return $html;
  }
  
  public function getEditPageLink($label = 'Edit', $openInNewTab = True)
  // --------------------------------------------------------------------------
  {
    return $this->getPageLink($this->editpage, $label, $openInNewTab);
  }
  
  /**
   * Get a link to the homepage for a record.
   * @param string $label Label for the link, will be the name if not specified.
   * @param integer $role Role constant, algaeAccess::ROLE_READ if not defined.
   * @param boolean $new_page True to open in a new tab, default is False.
   * @return string The link.
   */
  public function getHomepageLink($label = null, $role = algaeAccess::ROLE_READ, $new_page = False, $title = null)
  // --------------------------------------------------------------------------
  {
    if ($this->homepage != null)
    {
      global $app;
      if ( (property_exists($this, 'name')) && ($label == null) )
      {
        $label = $this->name;
      }
      return $app->getPageLink($app->getURLBase() . $this->homepage . '?rowid=' . $this->rowid, $label, $role, $app->settings->appName, '', $new_page, $title);
    }
    return '';
  }
  
  /**
   * Get links to act on the item.
   * @param boolean $openInNewTab True to open in a new tab, default is False.
   * @return string  HTML string with the links.
   */
  public function getActionLinks($openInNewTab = False)
  // --------------------------------------------------------------------------
  {
    global $app;
    $html = '';
    $separator = '';
    if ($this->numVariableErrors() == 0)
    {
      global $app;
      if (strlen($this->editpage) > 0)
      {
        $html = $this->getEditPageLink('Edit', $openInNewTab);
        $separator = $app->settings->menuSeparator;
      }
      if (strlen($this->deletepage) > 0)
      {
        $html .= $separator;
        $html .= $app->getPageLink($this->deletepage . '?rowid=' . $this->rowid, 'Delete', algaeAccess::ROLE_WRITE, $app->settings->appName, '', $openInNewTab);
        $separator = $app->settings->menuSeparator;
      }
      if ( (strlen($this->browsepage) > 0) && (! $app->isCurrentPage($this->browsepage)) && ($this->showBrowsePageLink) )
      {
        $html .= $separator;
        $html .= $app->getPageLink($this->browsepage, algaeCore::getSingularOrPlural(2, $this->itemName, $this->itemNamePlural), algaeAccess::ROLE_READ, $app->settings->appName, '', $openInNewTab);
        $separator = $app->settings->menuSeparator;
      }
    }
    return $html;
  }
  
  /**
   * Read data and show the homepage for a record.
   */
  public function showHomepage()
  // --------------------------------------------------------------------------
  {
    global $app;
    // don't start tabs here, causes problems elsewhere, i.e. stocks company page
    if (isset($_REQUEST['rowid']))
    {
      $this->rowid = algaeForm::cleanInput($_REQUEST['rowid']);
      if ($this->rowid > 0)
      {
        $this->read_row_from_database_with_rowid($this->rowid);
        if (strlen($this->timestamp_loaded_utc) > 0)
        {
          $this->reportDetails();
        }
        else
        {
          $app->errorMessage('Problem reading the details for rowid ' . $this->rowid . ' from ' . $this->table_name . '.');
          $sql = $this->get_rowid_read_sql();
          echo SqlFormatter::format(algaeDB::getSQLFromQueryAndParams($sql, array($this->rowid)));
        }
      }
      else
      {
        $app->errorMessage('Rowid specified on the URL is not > 0.');
      }
    }
    else
    {
      $app->errorMessage('Rowid not specified on the URL.');
    }
  }
  
  protected function getDefaultRecordsTableId($tableId = '')
  // --------------------------------------------------------------------------
  {
    if (strlen($tableId) == 0)
    {
      return get_class($this) . 'RecordsTable';
    }
    return $tableId;
  }
  
  /**
   * Get an HTML control id for a column.
   * Setup originally to handle converting something like record_status.name to record_status_dex_dot_name.
   * Dots are not supported in an HTML post.
   * @param string $column_name Column name from the data exchange config for the table.
   * @return string
   */
  public function get_control_id($column_name)
  // --------------------------------------------------------------------------
  {
    $col = $this->get_columns_for_name($column_name);
    if (count($col) == 1)
    {
      $formVariableName = $this->get_form_variable_name($col[0]);
      if (strpos($formVariableName, '.') !== false)
      {
        $parts = explode('.', $formVariableName);
        if (count($parts) == 2)
        {
          $formVariableName = $parts[0] . algaeTblBase::DEX_DOT . $parts[1];
        }
      }
      return $formVariableName;
    }
    else 
    {
      algaeApp::errorMessage('Problem getting a single data exchange column named ' . $column_name . '.'); 
    }
    return '';
  }
  
  public function post_control_data()
  // --------------------------------------------------------------------------
  {
    foreach ($_POST as $key => $val)
    {
      if (strpos($key, algaeTblBase::DEX_DOT) !== false)
      {
        $parts = explode(algaeTblBase::DEX_DOT, $key);
        if (count($parts) == 2)
        {
          if ( (property_exists($this, $parts[0])) && property_exists($this->{$parts[0]}, $parts[1]) )
          {
            $this->{$parts[0]}->{$parts[1]} = algaeForm::cleanInput($val);
          }
        }
      }
      else 
      {
        if (property_exists($this, $key))
        {
          $this->{$key} = algaeForm::cleanInput($val);
        }
      }
    }
  }
  
  protected function ok_to_write($columns)
  // --------------------------------------------------------------------------
  {
    $num_errors = 0;
    foreach ($columns as $column)
    {
      if ( ($this->get_column_property($column, $this::REQUIRED_PROP_NAME, False) == True) ||
           ($this->get_column_property($column, $this::UNIQUE_PROP_NAME, False) == True) ||
           ($this->get_column_property($column, $this::UNIQUE_KEY_PROP_NAME, False) == True) )
      {
        $data = $this->get_data_for_column($column);
        foreach ($data as $val)
        {
          if ( (! isset($val)) || ($val == null) || (strlen(strval($val)) == 0) )
          {
            algaeApp::errorMessage('Required data for ' . $column->name . ' missing.');
            $num_errors += 1;
          }
        }
        // TODO: Add additional check for 
      }
    }
    if ($num_errors > 0) 
    {
      return False;  
    }
    return True;
  }
  
  public function ok_to_insert()
  // --------------------------------------------------------------------------
  {
    return $this->ok_to_write($this->get_insert_columns());
  }
  
  public function ok_to_update()
  // --------------------------------------------------------------------------
  {
    return $this->ok_to_write($this->get_update_columns());
  }
  
  protected function get_named_parameters_from_sql($sql)
  // --------------------------------------------------------------------------
  {
    $done = False;
    $count = 0;
    $parameters = array();
    # echo 'DEBUG: ', $sql, '<p />';
    $pos1 = strpos($sql, '%(');
    while ( ($pos1 !== false) && ($pos1 >= 0) && (! $done) && ($count < 10)  )
    {
      $pos2 = strpos($sql, ')s', $pos1 + 2);
      if ($pos2 > $pos1)
      {
        # echo 'DEBUG: ', $pos1, ', ', $pos2, '<p />';
        $parameters[] = substr($sql, $pos1 + 2, $pos2 - $pos1 - 2);
        $pos1 = strpos($sql, '%(', $pos2);
      }
      else 
      {
        $done = True;
      }
      $count += 1;
    }
    return $parameters;
  }
  
  protected function get_named_parameters_from_altsql($column)
  // --------------------------------------------------------------------------
  {
    $key = 'altWriteSQL';
    if (property_exists($column, $key))
    {
      return $this->get_named_parameters_from_sql($column->{$key});
    }
    return null;
  }
  
  protected function replace_named_parameters_with_numbered_parameters($sql, &$parameter_number)
  // --------------------------------------------------------------------------
  {
    $ret = $sql;
    $done = false;
    $pos1 = strpos($ret, '%(');
    while ( ($pos1 !== false) && ($pos1 >= 0) && (! $done) )
    {
      $pos2 = strpos($ret, ')s', $pos1 + 2);
      if ($pos2 > $pos1)
      {
        $ret = substr($ret, 0, $pos1) . '$' . strval($parameter_number) . substr($ret, $pos2 + 2);
        $parameter_number += 1;
        $pos1 = strpos($ret, '%(');
      }
      else 
      {
        $done = true;
      }
    }
    return $ret;
  }
  
  public function get_data_placeholders(&$parameter_number, $column)
  // --------------------------------------------------------------------------
  {
    $key = 'altWriteSQL';
    if (property_exists($column, $key))
    {
      return $this->replace_named_parameters_with_numbered_parameters($column->{$key}, $parameter_number);
    }
    $parameter_number += 1;
    return '$' . strval($parameter_number - 1);
  }
  
  public function get_update_sql()
  // --------------------------------------------------------------------------
  {
    $columns = $this->get_update_columns();
    $separator = ' ';
    $sql = "UPDATE $this->table_name SET";
    $parameter_number = 1;
    foreach ($columns as $column)
    {
      $sql .= $separator . $column->name . ' = ' . $this->get_data_placeholders($parameter_number, $column);
      $separator = ', ';
      # $parameter_number += 1;
    }
    $sql .= " WHERE $this->table_name.rowid = $" . strval($parameter_number);
    return $sql;
  }
  
  public function get_insert_sql()
  // --------------------------------------------------------------------------
  {
    $columns = $this->get_insert_columns();
    $separator = '';
    #
    # ----- make a local copy of columns so we don't add to the columns list twice
    #
    $columns = $this->get_insert_columns();
    $sql = "INSERT INTO $this->table_name (";
    foreach ($columns as $column)
    {
      $sql .= $separator . $column->name;
      $separator = ',';
    }
    $sql .= ') VALUES (';
    $separator = '';
    $parameter_number = 1;
    foreach ($columns as $column)
    {
      $sql .= $separator . $this->get_data_placeholders($parameter_number, $column);
      $separator = ',';
      # $parameter_number += 1;
    }
    $sql .= ')';
    # handled in insert()
    # $sql .= ') RETURNING rowid';
    return $sql;
  }
  
  protected function get_data_for_column($column)
  // --------------------------------------------------------------------------
  {
    //
    // ----- check for simple names like rowid
    //
    $cvn = $this->get_class_variable_name($column);
    if (property_exists($this, $cvn))
    {
      // TODO: This likely needs to be more complex to account for data type and adding null values appropriately.
      //       Example is adding a slate geoprocess with blank decimals, not zero, blank.
      //       Could also handle writing a fixed number of decimals.
      return array($this->{$cvn});
    }
    //
    // -----
    //
    $data = array();
    $parameters = $this->get_named_parameters_from_altsql($column);
    foreach ($parameters as $parameter)
    {
      # echo 'DEBUG: variable name from altsql = ', $vn, '<p /';
      if (strpos($parameter, '.') !== false)
      {
        $parts = explode('.', $parameter);
        if (count($parts) == 2)
        {
          # echo 'DEBUG: [', $parts[0], '] [', $parts[1], ']<p />';
          if ( (property_exists($this, $parts[0])) && (property_exists($this->{$parts[0]}, $parts[1])) )
          {
            $data[] = $this->{$parts[0]}->{$parts[1]};
          }
        }
      }
      else 
      {
        if (property_exists($this, $parameter))
        {
          $data[] = $this->{$parameter};
        }
      }
    }
    if (count($data) == 0)
    {
      algaeApp::errorMessage('Unable to find a data value for the ', $column->name, ' column.');
    }
    return $data;
  }
  
  protected function get_data($columns, $include_rowid)
  // --------------------------------------------------------------------------
  {
    $parameters = array();
    foreach ($columns as $column)
    {
      $parameters = array_merge($parameters, $this->get_data_for_column($column));
    }
    //
    // ----- rowid
    //
    if ($include_rowid)
    {
      $parameters[] = $this->rowid;
    }
    return $parameters;
  }
  
  protected function get_insert_data()
  // --------------------------------------------------------------------------
  {
    return $this->get_data($this->get_insert_columns(), False);
  }
  
  protected function get_update_data()
  // --------------------------------------------------------------------------
  {
    return $this->get_data($this->get_update_columns(), True); 
  }
  
  public function insert()
  // --------------------------------------------------------------------------
  {
    $this->inserted = False;
    if ( ($this->preInsert()) && ($this->ok_to_insert()) )
    {
      $this->rowid = algaeDB::executeInsert($this->get_insert_sql(), $this->get_insert_data());
      if ($this->rowid > 0)
      {
        $this->postInsert();
        $this->inserted = True;
        return True;
      }
    }
    return False;
    
  }
  
  public function update()
  // --------------------------------------------------------------------------
  {
    $ret = False;
    $this->updated = False;
    if ( ($this->preUpdate()) && ($this->ok_to_update()) )
    {
      if ($this->debug)
      {
        echo $this->get_update_sql(), '<p />';
        $data = $this->get_update_data();
        var_dump($data);
      }
      $ret = algaeDB::executeQuery($this->get_update_sql(), $this->get_update_data());
      if ($ret == True)
      {
        $this->postUpdate();
        $this->updated = True;
      }
    }
    return $ret;
  }
  
  /**
   * Get list of fields to use in a SQL statement.
   */
  public function getFieldsV1()
  // --------------------------------------------------------------------------
  {
    return "$this->table_name.rowid,
    	        to_char($this->table_name.timestamp_loaded_utc, 'DD-Mon-YYYY HH24:MI:SS'),
    	        to_char($this->table_name.timestamp_modified_utc, 'DD-Mon-YYYY HH24:MI:SS')";
  }
  
  /**
   * Get SQL to read a record of data.
   */
  public function getSQLV1()
  // --------------------------------------------------------------------------
  {
    return "SELECT " . $this->getFieldsV1() . $this->getTableAndJoinsV1();
  }
  
}

