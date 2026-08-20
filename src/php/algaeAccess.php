<?php

/**

  algae framework | User accounts and security support class.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeAccess
{
  
  const ROLE_GUEST = 0;
  const ROLE_READ = 100;
  const ROLE_WRITE = 200;
  const ROLE_ADMIN = 300;
  const ROLE_SYSADMIN = 500;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
  }
  
  /**
   * The maximum number of login attempts allowed before an account is locked.
   */
  public static function MAX_LOGIN_ATTEMPTS()
  // --------------------------------------------------------------------------
  {
    return 5;
  }
  
  /**
   * Check session variables to see if a user is logged-in.
   * @param boolean $redirect True (default) to redirect user to a login page if not logged-in.
   * @param boolean $regenerate_id True (default) to regenerate a session is when a new session is started.
   */
  public static function isLoggedIn($redirect = True, $regenerate_id = True)
  // --------------------------------------------------------------------------
  {
    global $app;
    if (! $app->config->security_on)
    {
      return True;
    }
    // error_log('DEBUG: session_id() = ' . session_id(), 0);
    // error_log('DEBUG: session_save_path() = ' . session_save_path(), 0);
    if (!session_id())
    {
      session_start();
      if ($regenerate_id)
      {
        //
        // ----- This was changed from session_regerate_id(true); which deletes the old session each time
        //       and was likely better from a security standpoint, but was causing a user to be logged-out
        //       if they double-clicked quickly on a link.  (Brian K, 18 Jun 2014)
        //
        session_regenerate_id(false);
      }
    }
    if (isset($_SESSION[$app->config->session_username_key]))
    {
      return true;
    }
    else
    {
      if ($redirect)
      {
        $_SESSION["origURL"] = $_SERVER['REQUEST_URI'];
        header("Location: {$app->config->login_page}?app={$app->config->default_page}");
      }
      else
      {
        // keep this commented out, not a critical error, more for debugging
        // error_log('algaeFramework Error: Redirect abort in isLoggedIn().', 0);
      }
    }
    return false;
  }
  
  /**
   * Restart a browser session.
   * @param string $username The username to set in a session variable, indicates a user is logged-in.
   * @param string $origUrl Optional original URL to preserve in an origURL session variable.
   */
  public static function restartSession($username, $origUrl = '')
  // --------------------------------------------------------------------------
  {
    global $app;
    //
    // ----- restart a new session
    //
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
    $_SESSION[$app->config->session_username_key] = $username;
    if (strlen($origUrl) > 0)
    {
      $_SESSION["origURL"] = $origUrl;
    }
  }
  
  /**
   * Login to a session.
   * @param string $username The username to login with.
   * @param string $password The password to login with.
   * @param string $url The referring URL if applicable so a user can be returned to the page after logging in.
   * @return True if logged in successfully, false otherwise.
   */
  public static function login($username, $password, $url = '')
  // --------------------------------------------------------------------------
  {
    $sql = "SELECT u.password
  	        FROM core.user u
  	        INNER JOIN ref.record_status s ON u.record_status_rowid_fk = s.rowid
  	        WHERE u.username ILIKE $1 AND u.failed_login_attempts < $2 AND s.name = 'Active'";
    $hash = algaeDB::getScalarString($sql, array($username, algaeAccess::MAX_LOGIN_ATTEMPTS()));
    if ( (isset($hash)) && (strlen($hash) > 0) )
    {
      if (password_verify($password, $hash))
      {
        //
        // ----- save the referring url if it was set
        //
        if (!session_id())
        {
          session_start();
          session_regenerate_id(true);
        }
        //
        // ----- restart a new session
        //
        algaeAccess::restartSession($username, $url);
        //
        // ----- reset the count of failed login attempts back to 0
        //
        $sql = "UPDATE core.user SET failed_login_attempts = 0 WHERE username ILIKE $1
                  AND record_status_rowid_fk = (SELECT rowid FROM ref.record_status WHERE name = 'Active')";
        algaeDB::executeQuery($sql, array($username));
        return true;
      }
      else
      {
        if (strlen($username) > 0)
        {
          //
          // ----- increment number of failed login attemps
          //
          $sql = "UPDATE core.user SET failed_login_attempts = failed_login_attempts + 1 WHERE username ILIKE $1
                  AND record_status_rowid_fk = (SELECT rowid FROM ref.record_status WHERE name = 'Active')";
          algaeDB::executeQuery($sql, array($username));
        }
      }
    }
    return false;
  }
  
  /**
   * Get the number of failed login attempts for a user.
   * @param string $username Username to get the number of failed attempts for.
   */
  public static function getNumFailedAttempts($username)
  // --------------------------------------------------------------------------
  {
    $num = 0;
    if (isset($username) && strlen($username) > 0)
    {
      $sql = "SELECT u.failed_login_attempts
  	            FROM core.user u
  	            INNER JOIN ref.record_status s ON u.record_status_rowid_fk = s.rowid
  	            WHERE u.username ILIKE $1 AND s.name = 'Active'";
      $num = algaeDB::getScalarInteger($sql, array($username), 0);
    }
    return $num;
  }
  
  /**
   * Get the rowid of the current user.
   * @return integer|number Rowid of user or null if not found.
   */
  public static function getUserRowid()
  // --------------------------------------------------------------------------
  {
    $username = algaeAccess::getUsername();
    return algaeDB::getScalarInteger('SELECT rowid FROM core.user WHERE username = $1', array($username), null);
  }
  
  /**
   * Get the username for the current session.
   * @return string The username, or an empty string if the username is not set for the session.
   */
  public static function getUsername()
  // --------------------------------------------------------------------------
  {
    global $app;
    if (isset($_SESSION[$app->config->session_username_key]))
    {
      return $_SESSION[$app->config->session_username_key];
    }
    return 'guest';
  }
  
  /**
   * Get real name of the logged-in user, this is read from the database when needed, it's not stored internally.
   * @return string The real name of the logged-in user.
   */
  public static function getUserRealName()
  // --------------------------------------------------------------------------
  {
    $username = algaeAccess::getUsername();
    $name = '';
    if (strlen($username) > 0)
    {
      $name = algaeDB::getScalarString("SELECT name FROM core.user WHERE username = $1", array($username));
    }
    return $name;
  }
  
  /**
   * Get bracketed ( ) SQL to return the rowid from core.user for a given username.
   * This function takes the record_status flag into account and will only return an Active record.
   * @param string $strUsername A username to get the rowid SQL for.
   */
  public static function getRowidSQLforUsername($strUsername)
  // --------------------------------------------------------------------------
  {
    $sql = "(SELECT u.rowid FROM core.user u INNER JOIN ref.record_status rs ON u.record_status_rowid_fk = rs.rowid AND rs.name = 'Active'
  	WHERE u.username = '$strUsername')";
    return $sql;
  }
  
  /**
   * Get bracketed ( ) SQL to return the rowid from core.user for a given user's real name.
   * This function takes the record_status flag into account and will only return an Active record.
   * @param string $strName A user's real name to get the rowid SQL for.
   */
  public static function getRowidSQLforUserRealName($strName)
  // --------------------------------------------------------------------------
  {
    $sql = "(SELECT u.rowid FROM core.user u INNER JOIN ref.record_status rs ON u.record_status_rowid_fk = rs.rowid WHERE u.name = '$strName')";
    return $sql;
  }
  
  /**
   * Get a link to logout.
   */
  public static function getLogoutLink()
  // --------------------------------------------------------------------------
  {
    global $app;
    return '<a href="' . $app->config->logout_page . '?app=' . $app->config->default_page . '">Logout</a>';
  }
  
  /**
   * Show a link to logout.
   */
  public static function showLogoutLink()
  // --------------------------------------------------------------------------
  {
    echo algaeAccess::getLogoutLink();
  }
  
  /**
   * Read the roles/rights for a user.
   * @param string $username Username to read roles for.  If not specified will read the
   * roles for the current user.
   * @return algaeTblCoreUserRight[] Array of roles/rights for the user.
   */
  public static function readRoles($username = null)
  // --------------------------------------------------------------------------
  {
    global $app;
    $coreUserRight = new algaeTblCoreUserRight();
    $rightsArray = array();
    $sql = $coreUserRight->getSQLV1();
    $sql .= ' WHERE core.user.username = $1';
    $sql .= ' ORDER BY  ref.object.name, ref.role.level';
    $parms = array();
    if ($username != null)
    {
      $parms[] = $username;
    }
    else 
    {
      $parms[] = algaeAccess::getUsername();
    }
    //
    // ----- connect to the database
    //
    $db = algaeDB::connect($app->config->admin_database);
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
          $right = new algaeTblCoreUserRight();
          $right->readRowFromDatabase($row);
          $rightsArray[] = $right;
        }
      }
      algaeDB::close($db, $result);
    }
    return $rightsArray;
  }
  
  /**
   * Check if the logged in user has sufficient rights to proceed.
   */
  public static function isSufficientRights($rightsArray, $requiredRole, $object)
  // --------------------------------------------------------------------------
  {
    if ( (isset($rightsArray)) && (count($rightsArray) > 0) )
    {
      // ----- loop and check each role, return True if user has rights
      //
      foreach ($rightsArray as $rights)
      {
        if  ($rights->object_name == $object)
        {
          if ($rights->role_level >= $requiredRole)
          {
            return True;
          }
        }
      }
    }
    return False;
  }  
  
}




