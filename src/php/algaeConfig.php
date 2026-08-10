<?php

/**

  algae framework | Main configuration support.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/slate
 
*/

class algaeConfig
{
  
  const KEY_RTSPATIAL_CONFIG_PATH = 'RTSPATIAL_CONFIG_PATH';
  
  public $config_path;
  public $app_name;
  public $dex_json;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    echo 'DEBUG: __DIR__ = ', __DIR__, '<p />';
    $this->app_name = 'algae';
    $this->dex_json = array();
    //
    // ----- setup and read configuration
    //       simple error messaging since nothing is configured
    //
    if (getenv(algaeConfig::KEY_RTSPATIAL_CONFIG_PATH) !== false)
    {
      $this->config_path = getenv(algaeConfig::KEY_RTSPATIAL_CONFIG_PATH);
      $this->config[algaeConfig::KEY_RTSPATIAL_CONFIG_PATH] = $this->config_path;
      if (is_dir($this->config_path) === true)
      {
        $this->loadAppConfig();
        $this->loadDataExchangeConfig();
      }
      else 
      {
        echo 'Configuration path ', $this->config_path, ' does not exist.<p />';
      }
    }
    else 
    {
      echo 'Environment varible ', algaeConfig::KEY_RTSPATIAL_CONFIG_PATH, ' is not setup.<p />';
    }
  }
  
  /**
   * Load configuration data from a file.
   * File is read from the path defined by the RTSPATIAL_CONFIG_PATH environment variable.
   * Filename = app_name.ini.
   * The configuratinon is read and stored in an associative array $this->config.
   * When data is read with the same key (i.e. APP_DATABASE) newer configurations replace older.
   */
  public function loadAppConfig()
  // --------------------------------------------------------------------------
  {
    $filename = $this->config_path . '/' . $this->app_name . '.ini';
    // echo 'DEBUG: ', $this->app_name, '<p />';
    // echo 'DEBUG: Loading config file ', $filename, '<p /';
    if (file_exists($filename) === true)
    {
      $app_config = parse_ini_file($filename);
      // $this->config = array_merge($this->config, $app_config);
    }
    else 
    {
      echo 'Configuration file ', $filename, ' does not exist.<p />';
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
      $this->dex_json = array_merge($this->dex_json, json_decode($str));
    }
    else
    {
      echo 'Data exchange JSON file ', $filename, ' does not exist.<p />';
    }
  }
  
}




