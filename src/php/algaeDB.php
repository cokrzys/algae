<?php

/**

  algae framework | Database support.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/slate
 
*/

class algaeDB
{
  
  /**
   * Default constructor with no arguments.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
  }
  
  /**
   * Very basic error handler, prints pg_last_error() message with no formatting and dies.
   */
  public static function error()
  // --------------------------------------------------------------------------
  {
    global $app;
    echo '<p />';
    $app->errorMessage("Database " . pg_last_error());
  }
  
  /**
   * Show database error with the SQL code that caused the problem, kills the page.
   * @param string $sql The SQL code that caused the error.
   * @param array $parms Optional array of parameters, for example as passed to pg_query_params().
   */
  public static function errorWithSQL($sql, $parms = null)
  // --------------------------------------------------------------------------
  {
    global $app;
    echo '<p />';
    $app->errorMessage('Database  ' . pg_last_error());
    echo SqlFormatter::format(algaeDB::getSQLFromQueryAndParams($sql, $parms));
    /*
    global $app;
    print("<p />");
    print("<h2><img src=\"/{$app->frameworkUrl}/img/exclamation_25px.png\" alt=\"exclamation\" height=\"25\"
          style=\"vertical-align:middle;\" />&nbsp;Database Error</h2><p />\n");
    print("<font style=\"font-family: courier; font-size:10pt;\"><b>SQL Code:</b> $sql</font><p />\n");
    if (isset($parms))
    {
      print("<font style=\"font-family: courier; font-size:10pt;\"><b>Parameters:</b><p />");
      foreach ($parms as $val)
      {
        print("$val<p />");
      }
      print("</font><p />\n");
    }
    */
  }
  
  /**
   * Clean an input string getting it ready to be used in a SQL statement.
   * @param string $str The input string to clean.
   * @return string The cleaned up string.
   */
  public static function cleanInput($str)
  // --------------------------------------------------------------------------
  {
    return trim(pg_escape_string(utf8_encode($str)));
  }
  
  /**
   * Clean a numeric input string getting it ready to be used in a SQL statement.
   * @param string $str The input string to clean.
   * @return string The cleaned up string.
   */
  public static function cleanNumericInput($str)
  // --------------------------------------------------------------------------
  {
    $ret = algaeDB::cleanInput($str);
    $ret = str_replace(',', '', $ret);
    $ret = str_replace('$', '', $ret);
    return $ret;
  }
  
  /**
   * Clean data read from the database, basically apply utf8_decode().
   * @param string $str Raw string read from the database;
   * @return string The string cleaned version of the data.
   */
  public static function cleanDataRead($str)
  // --------------------------------------------------------------------------
  {
    // return utf8_decode($str);
    return $str;
  }
  
  /**
   * Connect to a database utilizing default connection parameters when required.
   * @param string $database The database to connect to, if left blank the global $app->settings->database is used.
   * @param string $password The database password, if left blank the global environment variable specified by
   * Environment variable is used to retieve the encrypted password for the database.
   * @param string $host The host, if left blank the host specified by $app->settings->databaseHost is used.
   * @param string $port The port, if left blank the port specified by $app->settings->databasePort is used.
   * @param string $username The username, if left blank postgres is used.
   * @return object The database connection handle, null if not connected.
   */
  public static function connect($database = '', $password = '', $host = '', $port = '', $username = '')
  // --------------------------------------------------------------------------
  {
    $db = null;
    global $app;
    //
    // ----- use global values if not specified
    //
    if (strlen($port) == 0)
    {
      $port = $app->config->database_port;
    }
    if (strlen($database) == 0)
    {
      $database = $app->config->app_database;
    }
    if (strlen($host) == 0)
    {
      $host = $app->config->database_host;
    }
    if (strlen($password) == 0)
    {
      $password = $app->config->database_password;
    }
    if (strlen($username) == 0)
    {
      $username = $app->config->database_username;
    }
    //
    // ----- connect to the database
    //
    if ( (strlen($database) > 0) && (strlen($password) > 0) )
    {
      $db = pg_connect("host=$host port=$port dbname=$database user=$username password=" . $password);
      if (!$db)
      {
        algaeDB::error();
      }
    }
    else
    {
      $app->errorMessage("Database or password not specified trying to connect to a database.");
    }
    return $db;
  }
  
  /**
   * Close a database connection.
   * @param object $db An opened database connection resource.
   */
  public static function close(&$db, &$result = null)
  // --------------------------------------------------------------------------
  {
    if ( ($result != null) && (isset($result)) && (is_resource($result)) )
    {
      pg_free_result($result);
    }
    if (isset($db) && $db)
    {
      //
      // ----- seems like this isn't really necessary
      //
      // pg_close($db);
    }
  }
  
  /**
   * Get a single string value from the database, opens and closes a new database connection to get the value.
   * @param string $query The SQL to return a single value, may return a string or a number which will automatically be
   * returned as a string, will be passed to pg_query_params().
   * @param array $parms An array of parameters to substitute into the SQL string.
   * @param integer $connection Specifies the database type
   * @return string The value from the database or an empty string if there was a problem reading the value or it doesn't exist.
   */
  public static function getScalarString($query, $parms)
  // --------------------------------------------------------------------------
  {
    $strRet = '';
    $db = algaeDB::connect();
    if ($db)
    {
      //
      // ----- select records
      //
      $result = pg_query_params($db, $query, $parms);
      if (! $result)
      {
        algaeDB::errorWithSQL($query, $parms);
      }
      //
      // ----- get a single row
      //
      if ( ($result) && ($row = pg_fetch_array($result)) )
      {
        // $strRet = utf8_decode($row[0]); this does not handle properly encoded data
        //                                 see for example reading Limón last name from the vistic soho crime dataset
        $strRet = $row[0];
      }
      //
      // ----- free memory from the results array
      //
      algaeDB::close($db, $result);
    }
    return $strRet;
  }
  
  /**
   * Get a single integer value from the database, calls the underlying algaeDB::getScalarString() method.
   * @param string $query The SQL to return a single value.
   * @param array $parms An array of parameters to substitute into the SQL string.
   * @param integer $default The default integer value to return if no data is read.
   */
  public static function getScalarInteger($query, $parms, $default)
  // --------------------------------------------------------------------------
  {
    $num = $default;
    $str = algaeDB::getScalarString($query, $parms);
    if (strlen($str) > 0)
    {
      $num = intval($str);
    }
    return $num;
  }
  
  /**
   * Get a single float value from the database, calls the underlying algaeDB::getScalarString() method.
   * @param string $query The SQL to return a single value.
   * @param array $parms An array of parameters to substitute into the SQL string.
   * @param float $default The default float value to return if no data is read, null by default.
   */
  public static function getScalarFloat($query, $parms, $default = null)
  // --------------------------------------------------------------------------
  {
    $num = $default;
    $str = algaeDB::getScalarString($query, $parms);
    if (strlen($str) > 0)
    {
      $num = floatval($str);
    }
    return $num;
  }
  
  /**
   * Read data into an array of arrays that's the size of the number of rows x the number of columns.
   * @param string $query The SQL to return a single value.
   * @param array $parms An array of parameters to substitute into the SQL string.
   * @return array[][] Array of size [nrows][cols].
   */
  public static function getArray($query, $parms)
  // --------------------------------------------------------------------------
  {
    $data = array();
    //
    // ----- connect to the database and read the data
    //
    $db = algaeDB::connect();
    if ($db)
    {
      $result = pg_query_params($db, $query, $parms);
      if (! $result)
      {
        algaeDB::errorWithSQL($query, $parms);
      }
      if (pg_num_rows($result) > 0)
      {
        $num_fields = pg_num_fields($result);
        //
        // ----- loop through the results
        //
        while ($row = pg_fetch_array($result))
        {
          $r = array();
          for ($i = 0; $i < $num_fields; $i++)
          {
            $r[] = algaeDB::cleanDataRead($row[$i]);
          }
          $data[] = $r;
        }
      }
      algaeDB::close($db, $result);
    }
    return $data;
  }
  
  /**
   * Get an array of strings from a database query.
   * @param string $sql SQL statement that selects a set of text values.
   * @param array $parms Optional parameters array for the SQL statement.
   * @return array Returns array of strings or an empty array if no strings read.
   */
  public static function getArrayOfStrings($sql, $parms = array())
  // --------------------------------------------------------------------------
  {
    $a = array();
    //
    // ----- connect to the database and read the data
    //
    $db = algaeDB::connect();
    if ($db)
    {
      $result = pg_query_params($db, $sql, $parms);
      if (! $result)
      {
        algaeDB::errorWithSQL($sql, $parms);
      }
      if (pg_num_rows($result) > 0)
      {
        //
        // ----- loop through the results
        //
        while ($row = pg_fetch_array($result))
        {
          $a[] = algaeDB::cleanDataRead($row[0]);
        }
      }
      algaeDB::close($db, $result);
    }
    return $a;
  }
  
  /**
   * Execute a query against the database, typically an INSERT or UPDATE query, uses the pg_query_params() function.
   *
   * @param string $query A SQL query command with coded parameters.
   * @param array $parms An array of parameters that will be substituted into the query string.
   * @param boolean $showErrors True (the default) to show error messages, False to not show.
   * @param object $db Optional opened database connection, if not specified a new connection will be opened and closed, default is null.
   */
  public static function executeQuery($query, $parms, $showErrors = True, $db = null)
  // --------------------------------------------------------------------------
  {
    $success = false;
    $close_conn = false;
    //
    // ----- open and write to the database
    //
    if (! $db)
    {
      $db = algaeDB::connect();
      $close_conn = true;
    }
    if ($db)
    {
      $result = pg_query_params($db, $query, $parms);
      if (! $result)
      {
        if ($showErrors)
        {
          algaeDB::errorWithSQL($query, $parms);
        }
      } else
      {
        if (pg_affected_rows($result) > 0)
        {
          $success = true;
        }
      }
      //
      // ----- cleanup
      //
      if (is_resource($result))
      {
        pg_free_result($result);
      }
      if ($close_conn)
      {
        algaeDB::close($db);
      }
    }
    return $success;
  }
  
  /**
   * Execute a query against the database, typically an INSERT or UPDATE query, uses the pg_query_params() function.
   * @param string $query A SQL query command with coded parameters.
   * @param array $parms An array of parameters that will be substituted into the query string.
   * @param string $logActivityType Optional log activity type, when specified must be a valid entry in std.activity_type;
   * @param object $db Optional opened database connection, if not specified a new connection will be opened and closed, default is null.
   * @param boolean $showErrors True (the default) to show error messages, False otherwise.
   * @param boolean $logMessage An optional message for the activity log, default is the SQL used for the query.
   * @return integer The rowid of the record that was just inserted or 0 if it failed.
   */
  public static function executeInsert($query, $parms, $logActivityType = '', $db = null, $showErrors = True, $logMessage = null)
  // --------------------------------------------------------------------------
  {
    $rowid = 0;
    $close_conn = false;
    //
    // ----- open and write to the database
    //
    if (! $db)
    {
      $db = algaeDB::connect();
      $close_conn = true;
    }
    if ($db)
    {
      $query .= ' RETURNING rowid';
      $result = pg_query_params($db, $query, $parms);
      if (! $result)
      {
        if ($showErrors)
        {
          algaeDB::errorWithSQL($query, $parms);
        }
      }
      else
      {
        $rowid = pg_fetch_result($result, 0, 0);
      }
      //
      // ----- cleanup
      //
      if ($close_conn)
      {
        algaeDB::close($db, $result);
      }
    }
    return $rowid;
  }
  
  /**
   * Add a log entry to the database activity table.
   * @param string $activityType The activity type, must match an entry in the std.activity_type table.
   * @param string $description A descriptive comment associated with the activity.
   * @param string $username Optional username, if not specified uses the current user.
   * @param string $application Optional application, if not specified uses the current application as defined by the $app initialization.
   */
  public static function logActivity($activityType, $description, $username = '', $application = '')
  // --------------------------------------------------------------------------
  {
    global $app;
    if (strlen($application) > 0)
    {
      $cleanAppName = algaeDB::cleanInput($application);
    }
    else
    {
      $cleanAppName = algaeDB::cleanInput($app->name);
    }
    if (strlen($username) > 0)
    {
      $cleanUsername = algaeDB::cleanInput($username);
    }
    else
    {
      $cleanUsername = algaeDB::cleanInput(algaeAccess::getUsername());
    }
    $cleanActivityType = algaeDB::cleanInput($activityType);
    $cleanDescription = algaeDB::cleanInput($description);
    $query = "INSERT INTO core.activity (application_rowid_fk, user_info_rowid_fk, activity_type_rowid_fk, description) VALUES (
              (SELECT rowid FROM std.application WHERE name = $1),
              (SELECT rowid FROM core.user_info WHERE username = $2),
              (SELECT rowid FROM std.activity_type WHERE name = $3), $4)";
    algaeDB::executeQuery($query, array($cleanAppName, $cleanUsername, $cleanActivityType, $cleanDescription),
      '', null, False);
  }
  
  /**
   * Create a combo box of choices on a web form from data in a database.
   * @param string $sql The SQL used to select a list of items, should return one column of results.
   * @param string $id The id for the control in the HTML form.
   * @param string $default If applicable the default (current) value for the item.
   * @param string $event A Javascript event handler if applicable.
   * @param string $group A string value like "All" indicating that there is a choice to select all the values.
   * @param boolean $required True or False (default) if a value is required or not.
   * @param integer $size Width of the control, if not specified width is set automatically.
   * @param string $class Class name for the control, typically used to associate the control with CSS styling.
   * @param integer $connection Specifies type of database connection
   */
  public static function comboWithSQL($sql, $id, $default, $event, $group, $required = false, $size=-1, $class='', $connection = 0)
  // --------------------------------------------------------------------------
  {
    if ($connection == algaeDB::DB_POSTGRESQL())
    {
      $db = algaeDB::connect();
    }
    else if ($connection == algaeDB::DB_MSSQL())
    {
      $db = algaeDB::connect_mssql();
    }
    if ($db)
    {
      //
      // ----- select records for dropdown list
      //
      if ($connection == algaeDB::DB_POSTGRESQL())
      {
        $result = pg_query($db, $sql);
      }
      else if ($connection == algaeDB::DB_MSSQL())
      {
        $result = mssql_query($sql);
      }
      if (! $result)
      {
        algaeDB::errorWithSQL($sql);
      }
      //
      // ----- create the select control and add choices
      //
      print "\n<select name=\"{$id}\" id=\"{$id}\" $event";
      if ($size>-1)
      {
        print(" style=\"width:{$size}px;\"");
      }
      if ($class)
      {
        print(" class=\"$class\"");
      }
      if ($required)
      {
        print(" required=\"required\"");
      }
      print(">\n");
      //
      // ----- add a blank value at the top if no default is set
      //
      if ( (strlen($default) == 0) || (! $required) )
      {
        print "\n\t<option value=\"\"></option>";
      }
      if (strlen($group) > 0)
      {
        if (isset($default) && ($group == $default))
        {
          print "\n\t<option selected value=\"" . algaeCore::toHtml($group) . "\">" . algaeCore::toHtml($group) . "</option>";
        }
        else
        {
          print "\n\t<option value=\"" . algaeCore::toHtml($group) . "\">" . algaeCore::toHtml($group) . "</option>";
        }
      }
      //
      // ----- add choices from the database
      //
      if ($connection == algaeDB::DB_POSTGRESQL())
      {
        while ($row = pg_fetch_array($result))
        {
          $str = utf8_decode($row[0]);
          if (isset($row[1]))
          {
            $val = utf8_decode($row[1]);
          }
          else
          {
            $val = $str;
          }
          if (isset($default) && ($str == $default))
          {
            print "\n\t<option selected value=\"" . algaeCore::toHtml($val) . "\">" . algaeCore::toHtml($str) . "</option>";
          }
          else
          {
            print "\n\t<option value=\"" . algaeCore::toHtml($val) . "\">" . algaeCore::toHtml($str) . "</option>";
          }
        }
      }
      else if ($connection == algaeDB::DB_MSSQL())
      {
        while ($row = mssql_fetch_array($result))
        {
          $str = utf8_decode($row[0]);
          if (isset($row[1]))
          {
            $val = utf8_decode($row[1]);
          }
          else
          {
            $val = $str;
          }
          if (isset($default) && ($str == $default))
          {
            print "\n\t<option selected value=\"" . algaeCore::toHtml($val) . "\">" . algaeCore::toHtml($str) . "</option>";
          }
          else
          {
            print "\n\t<option value=\"" . algaeCore::toHtml($val) . "\">" . algaeCore::toHtml($str) . "</option>";
          }
        }
      }
      print "\n</select>";
      //
      // ----- free memory from the results array
      //
      if ($connection == algaeDB::DB_POSTGRESQL())
      {
        algaeDB::close($db, $result);
      }
    }
  }
  
  /**
   * Create a combo box of choices on a web form from a table and field.
   * @param string $table Table to read the data from.
   * @param string $field Field to read the data from.
   * @param string $id The id for the control in the HTML form.
   * @param string $default If applicable the default (current) value for the item.
   * @param string $event A Javascript event handler if applicable.
   * @param boolean $required True or False (default) if a value is required or not.
   * @param boolean $activeOnly True (default) to add a clause for active rows only, this will be an inner join to std.record_status.
   * @param string $group A string value like "All" indicating that there is a choice to select all the values.
   * @param string $where_clause An optional where clause to add to the sql statement.
   * @param integer $connection Determines if this is a postgres (0) or mssql (1) database connection.
   * @param boolean $second true if a second field should be displayed in the drop-down menu.
   * @param string $second_table table to get the second field from
   * @param string $second_field name of the second field
   * @param string $foreign_key the foreign key column in the first table used to join the two tables
   * @param string $id_column the column of the primary key in the second table
   */
  public static function comboWithTableAndField($table, $field, $id, $default, $event = '', $required = False, $activeOnly = True, $group = '', $where_clause = '',
    $connection = 0, $second = null, $second_table = null, $second_field = null, $foreign_key = null, $id_column = null)
    // --------------------------------------------------------------------------
  {
    if ($second)
    {
      $sql = "SELECT t.{$field} + ' (' + tt.{$second_field} + ')' AS full_name, t.{$field} FROM {$table} t";
    }
    else
    {
      $sql = "SELECT DISTINCT t.{$field} FROM {$table} t";
    }
    if ($activeOnly)
    {
      $sql .= " INNER JOIN std.record_status s ON t.record_status_rowid_fk = s.rowid AND s.name = 'Active'";
    }
    if ($second_field)
    {
      $sql .= " INNER JOIN {$second_table} tt ON t.{$foreign_key} = tt.{$id_column}";
    }
    $sql .= " {$where_clause}";
    $sql .= " ORDER BY t.{$field}";
    algaeDB::comboWithSQL($sql, $id, $default, $event, $group, $required, null, null, $connection);
  }
  
  /**
   * Return a string in single quotes or NULL if the input string is empty.
   * @param string $str Input string.
   * @return string A string in single quotes or NULL if the input string is empty.
   */
  public static function getStringOrNull($str)
  // --------------------------------------------------------------------------
  {
    $strRet = trim($str);
    if (strlen($strRet) == 0)
    {
      $strRet = "NULL";
    }
    else
    {
      //
      // ----- form inputs are escaped with trim(pg_escape_string(utf8_encode($str))) which takes care
      //       of single quotes so adding below commented-out code double escapes them
      //       removed 18 Nov 2021
      //
      // $strRet = str_replace("'", "''", $strRet);
      $strRet = "'" . $strRet . "'";
    }
    return $strRet;
  }
  
  /**
   * Get a number formatted for a SQL insert or NULL if the value is not a valid number.
   * @param string or numeric $value The value to format.
   * @param integer $decimals The number of decimals, default is 2.
   * @param float $nan Comparison numeric if the value has not been set, -99 by default.
   * @return string SQL string formatted version of the number if it's a valid number, NULL otherwise.
   */
  public static function getNumericOrNull($value, $decimals = 2, $nan = -99)
  // --------------------------------------------------------------------------
  {
    $str = algaeCore::getFormattedNumber($value, $decimals, $nan, '');
    if ( (! isset($str)) || (strlen($str) == 0) )
    {
      $str = 'NULL';
    }
    return $str;
  }
  
  /**
   * Get a properly formatted string to insert a geography point.
   * Example insert string: 'SRID=4326;POINT(-110 30)'
   * @param float $latitude Latitude in decimal degrees.
   * @param float $longitude Longitude in decimal degrees.
   * Completed string or 'NULL' if values are not valid.
   */
  public static function getGeographyPtOrNull($latitude, $longitude)
  // --------------------------------------------------------------------------
  {
    if (($latitude != null) && ($longitude != null))
    {
      return "'SRID=4326;POINT(" .
        algaeCore::getFormattedNumber($longitude, 6, null, '') . ' ' .
        algaeCore::getFormattedNumber($latitude, 6, null, '') .
        ")'";
    }
    return 'NULL';
  }
  
  public static function getDatetimeStringOrNull($datetime, $format=DateTime::ISO8601)
  // --------------------------------------------------------------------------
  {
    if ($datetime != null)
    {
      return algaeDB::getStringOrNull($datetime->format($format));
    }
    return 'NULL';
  }
  
  /**
   * Get SQL to select a rowid or NULL if the value doesn't exist.
   * @param string $table Table to get a rowid in.
   * @param string $field Field to compare the value to get the rowid for.
   * @param string $value Value to get a rowid for.
   * @param string $rowid_column Column name in std table that contains the rowid.
   * @param string $and optional additional AND statement for selection
   * @return string A SQL statement that will select the rowid or NULL if the input string value doesn't exist.
   */
  public static function getRowidSQLOrNull($table, $field, $value, $rowid_column = "rowid", $and = null)
  // --------------------------------------------------------------------------
  {
    $sql = 'NULL';
    if (strlen(trim($value)) > 0)
    {
      $sql = "(SELECT $rowid_column FROM $table WHERE $field = '$value'";
      if (strlen(trim($and)) > 0)
      {
        $sql .= " " . $and;
      }
      $sql .= ")";
    }
    return $sql;
  }
    
  /**
   * Get a value from the metadata table core.metadata.
   * @param string $name Name of the value to get.
   * @return string The metadata value if it exists, a blank string otherwise.
   */
  public static function getMetadataValue($name)
  // --------------------------------------------------------------------------
  {
    return algaeDB::getScalarString('SELECT val FROM core.metadata WHERE name = $1', array($name));
  }
  
  /**
   * Write a value to the metadata table core.metadata.
   * @param string $name Name of the value to write.
   * @param string $value The value to write.
   */
  public static function writeMetadataValue($name, $value)
  // --------------------------------------------------------------------------
  {
    $rowid = algaeDB::getScalarInteger('SELECT rowid FROM core.metadata WHERE name = $1', array($name), 0);
    if ($rowid > 0)
    {
      $sql = 'UPDATE core.metadata SET val = $1 WHERE rowid = $2';
      return algaeDB::executeQuery($sql, array($value, $rowid));
    }
    $sql = 'INSERT INTO core.metadata (name, val) VALUES ($1, $2)';
    return algaeDB::executeQuery($sql, array($name, $value));
  }
  
  /**
   * Debugging function to get a flushed-out SQL statement from a paramaterized SQL statement and
   * an array of parameters. This is from: http://www.php.net/manual/en/function.pg-query-params.php
   * @param string $query The paramaterized SQL statement, i.e. 'SELECT * FROM mytable WHERE rowid = $1'
   * @param array $array The array of parameters, i.e. array(1).
   * @return string Fully flushed-out SQL statement.
   */
  public static function getSQLFromQueryAndParams($query, $array)
  // --------------------------------------------------------------------------
  {
    $query_parsed = $query;
    if ($array != null)
    {
      for ($a = 0, $b = sizeof($array); $a < $b; $a++)
      {
        if ( is_numeric($array[$a]) )
        {
          $query_parsed = str_replace(('$'.($a+1)), str_replace("'","''", $array[$a]), $query_parsed );
        }
        else
        {
          $query_parsed = str_replace(('$'.($a+1)), "'".str_replace("'","''", $array[$a])."'", $query_parsed );
        }
      }
    }
    return $query_parsed;
  }
  
  /**
   * Delete data from a table using a specific field and rowid.
   * @param string $table The table to delete from.
   * @param string $rowid_field The field containing the rowid.
   * @param integer $rowid The rowid to delete.
   * @return boolean True if successful, false otherwise.
   */
  public static function deleteFromTable($table, $rowid_field, $rowid)
  // --------------------------------------------------------------------------
  {
    $sql = "SELECT COUNT(*) FROM $table WHERE $rowid_field = $1";
    $num = algaeDB::getScalarInteger($sql, array($rowid), 0);
    if ($num > 0)
    {
      $sql = "DELETE FROM $table WHERE $rowid_field = $1";
      return algaeDB::executeQuery($sql, array($rowid));
    }
    return True;
  }
  
  /**
   * Check if a table exists.
   * @param string $schema Schema to check.
   * @param string $table Table to check.
   * @return boolean True if table exists, False if it does not.
   */
  public static function tableExists($schema, $table)
  // --------------------------------------------------------------------------
  {
    $name = algaeDB::getScalarString('SELECT table_name FROM information_schema.tables
                WHERE table_schema = $1 AND table_name = $2', array($schema, $table));
    if (strlen($name) > 0) return True;
    return False;
  }
  
  /**
   * Check if a column exists.
   * @param string $schema Schema to check.
   * @param string $table Table to check.
   * @param string $column Column to check.
   * @return boolean True if table exists, False if it does not.
   */
  public static function columnExists($schema, $table, $column)
  // --------------------------------------------------------------------------
  {
    $name = algaeDB::getScalarString('SELECT column_name FROM information_schema.columns
                WHERE table_schema = $1 AND table_name = $2 AND column_name = $3', array($schema, $table, $column));
    if (strlen($name) > 0) return True;
    return False;
  }
  
  /**
   * Show a row count statistic, for example: 5,403 (62.7%) of 8,612.
   * This is a common reporting utility to show how many and the percentage of rows in a subset of data.
   * @param string $sqlAll The SQL to count ALL rows in the table or larger set.
   * @param string $sqlSelected The SQL to count the number of rows in the subset.
   * @param number $pctDecimals The number of decimal places for the percentage, 1 by default.
   */
  public static function showRowCountStatistic($sqlAll, $sqlSelected, $pctDecimals = 1)
  // --------------------------------------------------------------------------
  {
    $total = algaeDB::getScalarInteger($sqlAll, array(), 0);
    $selected = algaeDB::getScalarInteger($sqlSelected, array(), 0);
    echo algaeCore::getFormattedNumber($selected, 0, -99);
    if ($total > 0)
    {
      echo ' (', algaeCore::getFormattedNumber($selected / $total * 100, $pctDecimals, -99), '%)';
    }
    echo ' of ', algaeCore::getFormattedNumber($total, 0, -99);
  }
  
  /**
   * Checks if a query is allowed.  This is used to validate a query input by a user in a
   * web page, for example anything containing DELETE, TRUNCATE, etc., is not allowed.
   * @param string $sql The SQL query to check.
   * @return boolean True if allowed, false if not allowed.
   */
  public static function isAllowedQuery($sql)
  // --------------------------------------------------------------------------
  {
    global $app;
    if ( (stripos($sql, 'DELETE', 0) !== False) ||
      (stripos($sql, 'UPDATE', 0) !== False) ||
      (stripos($sql, 'INSERT', 0) !== False) ||
      (stripos($sql, 'DROP', 0) !== False) ||
      (stripos($sql, 'ALTER', 0) !== False) ||
      (stripos($sql, 'CREATE', 0) !== False) ||
      (stripos($sql, 'TRUNCATE', 0) !== False) )
    {
      $app->errorMessage('One or more forbidden commands used.');
      return false;
    }
    return true;
  }
  
  /**
   * Get the name of a unique table that doesn't already exist.
   * Format will be prefixXXXXX, i.e. tmp03876.
   * @param string $schema Schema, will new within the schema.
   * @param string $prefix Prefix, optional, 'tmp' by default.
   * @param integer $digits Number of digits in the random number to test for uniquness, default 5.
   * @return string Tablename or null if not successful.
   */
  public static function getUniqueNewTablename($schema, $prefix = 'tmp', $digits = 5)
  // --------------------------------------------------------------------------
  {
    global $app;
    $max_tries = 500;
    for ($i=0; $i < $max_tries; $i++)
    {
      $table = $prefix . str_pad(rand(0, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT);
      if (! algaeDB::tableExists($schema, $table))
      {
        return $table;
      }
    }
    $app->errorMessage('Exceeded ' . $max_tries . ' trying to make a uique table name.');
    return null;
  }
  
  /**
   * Add a WHERE clause to the end of a SQL statement.
   * This is fairly simple and is designed to be used with a SQL statment that does not already have a where clause at the end.
   * Typical call at the end of building report SQL is $sql = algaeDB::addWhereClause($sql, $whereClause);
   * @param string $sql Starting SQL statement.
   * @param string $whereClause WHERE clause to add, with or without WHERE at the beginning.
   * @return string Completed SQL statment with WHERE clause appended.
   */
  public static function addWhereClause($sql, $whereClause)
  // --------------------------------------------------------------------------
  {
    $ret = $sql;
    if ( ($whereClause != null) && (strlen($whereClause) > 0) )
    {
      $wc = strtoupper(trim($whereClause));
      if (! algaeCore::startsWith($wc, 'WHERE'))
      {
        $ret .= ' WHERE ';
      }
      $ret .= $whereClause;
    }
    return $ret;
  }
  
}
