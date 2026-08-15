<?php

/**

  algae framework | Admin utilities class.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/slate
 
*/

class algaeAdmin
{
  
  public $form_token;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    $this->form_token = 'first_life';
  }
  
  public static function showSettings()
  // --------------------------------------------------------------------------
  {
    global $app;
    if (strlen($app->getDbPassword()) > 0)
    {
      echo 'Database password successfully read.<p />';
    }
    else 
    {
      echo 'Database password not found, check for environment variable ', $app->settings->databasePasswordEnvVariable, '.<p />';
    }
  }
  
  public function processLoginForm()
  // --------------------------------------------------------------------------
  {
    global $app;
    if (isset($_POST['submit']) && $_POST['submit'])
    {
      if (isset($_SESSION["origURL"]))
      {
        $url = $_SESSION["origURL"];
      }
      elseif (isset($_POST["origURL"]))
      {
        $url = urldecode($_POST["origURL"]);
      }
      else
      {
        $url = $app->settings->defaultURL;
      }
      if (algaeForm::validTokens($this->form_token))
      {
        $username = algaeDB::cleanInput($_REQUEST['username']);
        $password = algaeDB::cleanInput($_REQUEST['password']);
        if (algaeAccess::login($username, $password, $url))
        {
          // algaeDB::logActivity("WebLogin", $_SERVER['HTTP_USER_AGENT']);
          if (!session_id())
          {
            session_start();
            session_regenerate_id(true);
          }
          header("Location: $url");
        }
        $app->errorMessage("Invalid username or password.");
      }
    }
  }
  
  /**
   * Show a login form.
   */
  public function showLoginForm()
  // --------------------------------------------------------------------------
  {
    $f = new algaeForm();
    algaeForm::startTabs(array(array('#login_tab', 'Login')));
    echo '<div id="login_tab">';
    $f->startForm($this->form_token);
    algaeTable::start('formTable', 'algae_form_table', '');
    algaeTable::writeHeader(array(), False);
    algaeTable::writeTwoColumns('Username', algaeForm::inputText('username', '', 20, algaeForm::REQUIRED, algaeForm::TEXT), False);
    algaeTable::writeTwoColumns('Password', algaeForm::inputText('password', '', 20, algaeForm::REQUIRED, algaeForm::PASSWORD), False);
    algaeTable::end();
    echo '<p />';
    $f->endForm('Login');
    echo '</div>';
    algaeForm::endTabs('tabs', '50%');
  }
  
  /**
   *
   */
  public function processSetupForm()
  // --------------------------------------------------------------------------
  {
    global $app;
    if (isset($_POST['submit']))
    {
      if (algaeForm::validTokens($this->form_token))
      {
        $database_password = $_POST['database_password'];
        echo 'Clear password: ', $database_password, '<p />';
        $encrypted_password = $app->encrypt($database_password);
        $decrypted_password = $app->decrypt($encrypted_password);
        echo 'Encrypted password: ', $encrypted_password, '<p />';
        echo 'Decrypted password: ', $decrypted_password, '<p />';
        echo 'Setup an Apache Environment Variable: <p />';
        echo 'SetEnv ', $app->settings->databasePasswordEnvVariable, ' ', $encrypted_password, '<p />';
      }
    }
  }
  
  /**
   *
   */
  public function showSetupForm()
  // --------------------------------------------------------------------------
  {
    $f = new algaeForm();
    $f->startForm($this->form_token);
    
    echo 'The database password needs to be encrypted and stored in an Apache environment variable.<p />';
    echo 'Database password: ';
    echo algaeForm::inputText('database_password', '', 20, algaeForm::REQUIRED);
    
    echo '<p />';
    $f->endForm();
  }
  
  /**
   *
   */
  public function processAddUserForm()
  // --------------------------------------------------------------------------
  {
    global $app;
    if (isset($_POST['submit']))
    {
      if (algaeForm::validTokens($this->form_token))
      {
        $u = new algaeTblCoreUser();
        $u->name = algaeForm::cleanInput($_POST['name']);
        $u->username = algaeForm::cleanInput($_POST['username']);
        $u->email = algaeForm::cleanInput($_POST['email']);
        $u->password = algaeForm::cleanInput($_POST['password']);
        $verify_password = algaeForm::cleanInput($_POST['verify_password']);
        if ($verify_password == $u->password)
        {
          if ($u->okToWrite())
          {
            if ($u->writeToDatabase())
            {
              $app->successMessage('User ' . $u->username . ' successfully added.');
            }
            else
            {
              $app->errorMessage('Problem adding user ' . $u->username . '.');
            }
          }
        }
        else
        {
          $app->errorMessage('Passwords do not match.');
        }
      }
    }
  }
  
  /**
   * Show the add user form.
   */
  public function showAddUserForm()
  // --------------------------------------------------------------------------
  {
    algaeForm::startSingleTab('Add User');
    $f = new algaeForm();
    $f->startForm($this->form_token);
    algaeTable::start('formTable', 'algae_form_table', '');
    algaeTable::writeHeader(array(), False);
    algaeTable::writeTwoColumns('Name', algaeForm::inputText('name', '', 20, algaeForm::REQUIRED), False);
    algaeTable::writeTwoColumns('Username', algaeForm::inputText('username', '', 20, algaeForm::REQUIRED), False);
    algaeTable::writeTwoColumns('E-mail', algaeForm::inputText('email', '', 20, algaeForm::REQUIRED), False);
    algaeTable::writeTwoColumns('Password', algaeForm::inputText('password', '', 20, algaeForm::REQUIRED, algaeForm::PASSWORD), False);
    algaeTable::writeTwoColumns('Verify Password', algaeForm::inputText('verify_password', '', 20, algaeForm::REQUIRED, algaeForm::PASSWORD), False);
    algaeTable::end();
    echo '<p />';
    $f->endForm();
    algaeForm::endSingleTab();
  }
  
}


