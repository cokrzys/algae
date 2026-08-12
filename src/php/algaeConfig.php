<?php

/**

  algae framework | Configuration support.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/slate
 
*/

class algaeConfig
{
  
  const KEY_RTSPATIAL_LOCAL_CONFIG_PATH = 'RTSPATIAL_LOCAL_CONFIG_PATH';
  
  public $config_path;
  public $local_config_path;
  public $app_name;
  
  public $master_database;
  public $app_database;
  public $database_username;
  public $database_port;
  public $database_host;
  public $default_css;
  
  public $dex_json;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    $this->app_name = 'algae';
    $this->config_path = '/opt/algae-main/config';
    $this->local_config_path = '/opt/rtspatial/config';
    
    $this->master_database = 'algae';
    $this->app_database = 'app';
    $this->database_username = 'postgres';
    $this->database_port = 5432;
    $this->database_host = 'localhost';
    $this->default_css = '/algae/css/algae.css';
    
    $this->dex_json = array();
    //
    // ----- path for main configuration files
    //       config that a user should not edit
    //       updated with application updates
    //
    echo 'OK: algaeConfig.php found at ', __DIR__, '<p />';
    $p = strpos(__DIR__, '/src/php');
    if ($p != false)
    {
      $this->config_path = substr(__DIR__, 0, $p) . DIRECTORY_SEPARATOR . 'config';
      echo 'OK: config_path set to ', $this->config_path, '<p />';
    }
    else
    {
      echo 'ERROR: Unable to convert ', __DIR__, ' into the config path, defaulting to ', $this->config_path, '<p />';
    }
    //
    // ----- path for local configuration changes
    //       user config changes that override app settings
    //       never changed with app updates
    //
    if (getenv(algaeConfig::KEY_RTSPATIAL_LOCAL_CONFIG_PATH) !== false)
    {
      $this->local_config_path = getenv(algaeConfig::KEY_RTSPATIAL_LOCAL_CONFIG_PATH);
      echo 'OK: local_config_path set to ', $this->local_config_path, '<p />';
    }
    else
    {
      echo 'WARNING: Environment varible ', algaeConfig::KEY_RTSPATIAL_LOCAL_CONFIG_PATH, ' not setup, defaulting to ', $this->local_config_path, '<p />';
    }
    //
    //
    //
    $this->loadINIConfig(algaeConfig::getFullPath($this->config_path, $this->app_name . '.ini'));
    $this->loadINIConfig(algaeConfig::getFullPath($this->local_config_path, $this->app_name . '.ini'));
    $this->loadDataExchangeConfig();
  }
  
  protected static function getFullPath($path, $filename)
  // --------------------------------------------------------------------------
  {
    if (strlen($path) == 0)
    {
      return $filename;
    }
    if (str_ends_with($filename, DIRECTORY_SEPARATOR))
    {
      return $path . $filename;
    }
    return $path . DIRECTORY_SEPARATOR . $filename;
  }
  
  /**
   * Load configuration data from a file.
   * File is read from the path defined by the RTSPATIAL_CONFIG_PATH environment variable.
   * Filename = app_name.ini.
   * The configuratinon is read and stored in an associative array $this->config.
   * When data is read with the same key (i.e. APP_DATABASE) newer configurations replace older.
   */
  public function loadINIConfig($filename)
  // --------------------------------------------------------------------------
  {
    if (file_exists($filename) === true)
    {
      $app_config = parse_ini_file($filename);
      // $this->config = array_merge($this->config, $app_config);
      echo 'OK: Loaded configuration file ', $filename, '<p />';
    }
    else 
    {
      echo 'WARNING: Configuration file ', $filename, ' does not exist.<p />';
    }
  }
  
  /**
   */
  public function loadDataExchangeConfig()
  // --------------------------------------------------------------------------
  {
    $filename = $this->config_path . '/' . $this->app_name . '_dex.json';
    // echo 'DEBUG: ', $this->app_name, '<p />';
    // echo 'DEBUG: Loading config file ', $filename, '<p /';
    if (file_exists($filename) === true)
    {
      $str = file_get_contents($filename);
      $json = json_decode($str);
      echo 'OK: ', count($json), ' data exchange definition(s) read from ', $filename, '<p />';
      $this->dex_json = array_merge($this->dex_json, $json);
    }
    else
    {
      echo 'ERROR: Data exchange JSON file ', $filename, ' does not exist.<p />';
    }
  }
  
  public function getConfigAsArray()
  // --------------------------------------------------------------------------
  {
    $prop_array = array();
    $properties = get_object_vars($this);
    foreach ($properties as $name => $obj)
    {
      if ( (gettype($obj) == 'string') || (gettype($obj) == 'double') || (gettype($obj) == 'integer') || (gettype($obj) == 'boolean') )
      {
        $prop_array[$name] = $obj;
      }
    }
    ksort($prop_array);
    return $prop_array;
  }
  
  public function showConfig()
  // --------------------------------------------------------------------------
  {
    $properties = $this->getConfigAsArray();
    foreach ($properties as $key => $val)
    {
      echo $key, ' = ', $val, '<p />';
    }
  }
  
}




