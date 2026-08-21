<?php

/**
  Algae core.user_parameter table support.
  
  @author    Brian Krzys (cokrzys@gmail.com)
  @copyright 2020 RTSpatial Ltd.
  @license   https://opensource.org/licenses/MIT
  @link      https://github.com/cokrzys/algae
*/

class algaeTblCoreUserParameter extends algaeTblBase
{
  
  public $user;
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
    $this->user = new algaeTblCoreUser();
    $this->name = '';
    $this->val = '';
    $this->description = '';
  }
  
  /**
   * Get list of fields to use in a SQL statement.
   */
  public function getFields()
  // --------------------------------------------------------------------------
  {
    $sql = parent::getFields();
    $sql .= ", $this->table_name.user_rowid_fk, $this->table_name.name, $this->table_name.val, $this->table_name.description";
    return $sql;
  } 
  
  /**
   * Get tables and joins to select data.
   * @param string $alias Name alias for the primary table.
   */
  public function getTableAndJoins()
  // --------------------------------------------------------------------------
  {
    return " FROM $this->table_name
              INNER JOIN core.user ON $this->table_name.user_rowid_fk = core.user.rowid";
  } 
  
  /**
   * Read a row from the database.
   * @param array $row The array of data from the database.
   */
  public function readRowFromDatabase($row)
  // --------------------------------------------------------------------------
  {
    $cur = 0;
    $this->rowid = algaeDB::cleanDataRead($row[$cur++]);
    $this->timestamp_loaded_utc = algaeDB::cleanDataRead($row[$cur++]);
    $this->timestamp_modified_utc = algaeDB::cleanDataRead($row[$cur++]);
    $this->user->rowid = algaeDB::cleanDataRead($row[$cur++]);
    $this->name = algaeDB::cleanDataRead($row[$cur++]);
    $this->val = algaeDB::cleanDataRead($row[$cur++]);
    $this->description = algaeDB::cleanDataRead($row[$cur++]);
    return $cur;
  }
  
  /**
   * Determine if a record is ok to write.
   */
  public function okToWrite($showErrors = True)
  // --------------------------------------------------------------------------
  {
    global $app;
    $num_errors = 0;
    algaeForm::checkRequiredValue($this->name, 'Name', $num_errors, $showErrors);
    algaeForm::checkRequiredValue($this->username, 'Username', $num_errors, $showErrors);
    algaeForm::checkRequiredValue($this->email, 'E-Mail', $num_errors, $showErrors);
    algaeForm::checkRequiredValue($this->password, 'Password', $num_errors, $showErrors);
    //
    // ----- make sure the username doesn't already exist
    //
    if (strlen($this->username) > 0)
    {
      $sql = 'SELECT username FROM ' . $this->table_name . ' WHERE username = $1';
      $rowid = algaeDB::getScalarInteger($sql, array($this->username), 0);
      if ( ($rowid > 0) && ($rowid != $this->rowid) )
      {
        $app->errorMessage('Username ' . algaeCore::toHtml($this->username) . ' already exists.');
        $num_errors++;
      }
    }
    if ($num_errors > 0) return false;
    return true;
  }
  
  /**
   * Write record to the database.
   * @return True if success, false on fail.
   */
  function writeToDatabase()
  // --------------------------------------------------------------------------
  {
    //
    // -----SQL to insert a new record
    //
    $sql = "INSERT INTO $this->table_name (name, username, email, password) VALUES (";
    $sql .= algaeDB::getStringOrNull($this->name);
    $sql .= ", " . algaeDB::getStringOrNull($this->username);
    $sql .= ", " . algaeDB::getStringOrNull($this->email);
    $sql .= ", " . algaeDB::getStringOrNull(md5($this->password));
    $sql .= ")";
    //
    // ----- insert and get the rowid for the new record
    //
    $this->rowid = algaeDB::executeInsert($sql, array());
    if ($this->rowid > 0) return True;
    return False;
  }
  
  /**
   * Get the SQL to select a parameter.
   * @return string The SQL from the FROM clause, on, for example: FROM core.user_parameter ...
   * @param string $user_rowid_fk Direct rowid or SQL to get the rowid of the user to write the parameter for.
   * If not specified (default) then the current logged-in user is used.
   */
  public static function getSQLToSelectParameter($user_rowid_fk = null)
  // --------------------------------------------------------------------------
  {
    $sql = ' FROM core.user_parameter WHERE name = $1 AND user_rowid_fk = ';
    if ($user_rowid_fk != null)
    {
      $sql .= $user_rowid_fk;
    }
    else
    {
      $sql .= algaeAccess::getRowidSQLforUsername(algaeAccess::getUsername());
    }
    return $sql;
  }
  
  /**
   * Get a user parameter value.
   * @param string $name Parameter name, for example ScenarioName1.
   * @return string The parameter value as a string, blank if not found.
   */
  public static function getParameter($name)
  // --------------------------------------------------------------------------
  {
    return algaeDB::getScalarString('SELECT val' . algaeTblCoreUserParameter::getSQLToSelectParameter(), array($name));
  }
  
  public static function deleteParameter($name)
  // --------------------------------------------------------------------------
  {
    return algaeDB::executeQuery('DELETE' . algaeTblCoreUserParameter::getSQLToSelectParameter(), array($name));
  }
  
  /**
   * Write a user parameter to the database.
   * @param string $name Parameter name, for example CurrentGroup.
   * @param string $value Parameter value.
   * @param string $user_rowid_fk Direct rowid or SQL to get the rowid of the user to write the parameter for.
   * If not specified (default) then the current logged-in user is used.
   */
  public static function saveParameter($name, $value, $user_rowid_fk = null)
  // --------------------------------------------------------------------------
  {
    $rowid = algaeDB::getScalarInteger('SELECT rowid' . algaeTblCoreUserParameter::getSQLToSelectParameter($user_rowid_fk), array($name), 0);
    if (strlen($value) > 0)
    {
      if ($rowid > 0)
      {
        //
        // ----- update an existing parameter
        //
        $sql = 'UPDATE core.user_parameter SET val = ';
        $sql = $sql . algaeDB::getStringOrNull($value);
        $sql .= " WHERE rowid = '$rowid'";
      }
      else
      {
        //
        // ----- insert a new parameter
        //
        $sql = "INSERT INTO core.user_parameter (user_rowid_fk, name, val) VALUES (";
        if ($user_rowid_fk != null)
        {
          $sql .= $user_rowid_fk;
        }
        else
        {
          $sql = $sql . algaeAccess::getRowidSQLforUsername(algaeAccess::getUsername());
        }
        $sql = $sql . ", " . algaeDB::getStringOrNull($name);
        $sql = $sql . ", " . algaeDB::getStringOrNull($value);
        $sql = $sql . ")";
      }
      return algaeDB::executeQuery($sql, array());
    }
    else
    {
      $sql = 'DELETE FROM core.user_parameter WHERE rowid = $1';
      algaeDB::executeQuery($sql, array($rowid), '');
    }
  }
  
}


