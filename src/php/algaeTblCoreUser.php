<?php

/**

  algae framework | User and table core.user support.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblCoreUser extends algaeTblBase
{
 
  public $username;
  public $name;
  public $email;
  public $password;
  public $failed_login_attempts;
  public $record_status;
  
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
    global $app;
    parent::init();
    $this->database = $app->config->admin_database;
    $this->table_name = 'core.user';
    $this->record_status = new algaeTblRecordStatus();
    $this->username = '';
    $this->name = '';
    $this->email = '';
    $this->password = '';
    $this->failed_login_attempts = 0;
  }
  
  /**
   * Read from the database with a username.
   * @param string $username The username.
   * @return boolean
   */
  public function readRowFromDatabaseWithUsername($username)
  // --------------------------------------------------------------------------
  {
    $sql = $this->get_sql();
    $sql .= " WHERE $this->table_name.username = $1";
    return $this->read_row_from_database_with_sql($sql, array($username));
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
  public function insert()
  // --------------------------------------------------------------------------
  {
    $saved_password = $this->password;
    $this->password = password_hash($this->password, PASSWORD_DEFAULT);
    $ret = parent::insert();
    $this->password = $saved_password;
    return $ret;
  }
  
}


