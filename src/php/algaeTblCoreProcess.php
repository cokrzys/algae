<?php

/**

  algae framework | Process and support for table core.process.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblCoreProcess extends algaeTblBase
{
  
  public $app_user;
  public $process_status;
  public $application;
  public $command;
  public $logfile;
  public $parmsfile;
  public $pid;
  public $result_url;
  public $starting_url;
  public $progress;
  public $progress_message;
  public $description;
  public $logfile_root;
  public $datetime_prefix;
  public $run_time;
  
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
    global $app;
    $this->table_name = 'core.process';
    $this->homepage = $app->config->process_status_page;
    $this->app_user = new algaeTblCoreAppUser();
    $this->process_status = new algaeTblProcessStatus();
    $this->application = null;
    $this->command  = null;
    $this->logfile = null;
    $this->parmsfile = null;
    $this->pid = null;
    $this->result_url = null;
    $this->starting_url = null;
    $this->progress = null;
    $this->progress_message = null;
    $this->description = null;
    $this->logfile_root = null;
    $this->run_time = null;
    $this->datetime_prefix = algaeFile::getDateTimePrefix();
  }
  
  /**
   * Get an array with the attributes of an object.  For example used to write to JSON
   * with json_encode($a, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT).
   */
  public function getArray()
  // --------------------------------------------------------------------------
  {
    $a = array();
    $a['rowid'] = $this->rowid;
    $a['userRowid'] = algaeAccess::getUserRowid();
    return $a;
  }
  
  public function getActionLinks($openInNewTab=False)
  // --------------------------------------------------------------------------
  {
    global $app;
    $html = '';
    $html .= $this->getLink('process_status.php?rowid=' . $this->rowid, 'View');
    if (strlen($this->starting_url) > 0)
    {
      $html .= $app->config->menu_separator;
      $html .= $this->getLink($this->starting_url, 'Run Again');
    }
    if (strlen($this->deletepage) > 0)
    {
      $html .= $app->config->menu_separator;
      $html .= $this->getLink($this->deletepage . '?rowid=' . $this->rowid, 'Delete');
    }
    return $html;
  }
  
  /**
   * Build a log filename, setting up directories if required.
   * The general format of the filename is:
   * /ebs1/sp/gbhk/2020/09/20200927_create_study_area.log
   * The root /ebs1/sp/gbhk must exist, the rest is created.
   */
  protected function buildLogFilenameOld()
  // --------------------------------------------------------------------------
  {
    if (strlen($this->logfile_root) > 0)
    {
      $this->logfile = '';
      $directory = $this->logfile_root . date('Y');
      if (! file_exists($directory))
      {
        mkdir($directory, 0760);
      }
      if (file_exists($directory))
      {
        $directory .= '/' . date('m');
        if (! file_exists($directory))
        {
          mkdir($directory, 0760);
        }
        if (file_exists($directory))
        {
          $this->logfile = $directory . '/' . date('Ymd') . '_' . $this->rowid . '_' . $this->application . '.log';
        }
      }
    }
    else 
    {
      global $app;
      $app->errorMessage('Logfile root not defined in ' . get_class($this) . '::' . __FUNCTION__ . '().');
    }
  }

  /**
   * Build a process filename.
   * @param string $extension Typically '.log' or '.json' for log or parameters.
   * @return string
   */
  public function getProcessFilename($extension)
  // --------------------------------------------------------------------------
  {
    if (strlen($this->logfile_root) > 0)
    {
      return $this->logfile_root . $this->datetime_prefix . '_' . $this->application . $extension;
    }
    else
    {
      global $app;
      $app->errorMessage('Logfile root not defined in ' . get_class($this) . '::' . __FUNCTION__ . '().');
    }
    return '';
  }
  
  /**
   * Create a process by inserting a new row to core.process, building a log filename,
   * and inserting the log filename into the new record in core.process.
   * @return boolean True if success, False on failure.
   */
  public function createProcess()
  // --------------------------------------------------------------------------
  {
    $this->logfile = $this->getProcessFilename('.log');
    if ($this->parmsfile == null)
    {
        $this->parmsfile = $this->getProcessFilename('.json');
    }
    $this->app_user->rowid = algaeTblCoreAppUser::getAppUserRowidForLoggedInUser();
    $this->process_status->read_row_from_database_with_name('Running');
    $this->progress = 0;
    return $this->insert();
  }
  
  /**
   * Start a process in the background.
   * The logic for this is from:
   * http://stackoverflow.com/questions/45953/php-execute-a-background-process
   * @param string $command The command to run in the background.
   * @param string $status_page The name of the status page to associate with the process.
   * @return boolean True if success, False on failure.
   */
  public function startInBackground($command, $status_page)
  // --------------------------------------------------------------------------
  {
    global $app;
    $pid = array();
    if (file_exists($this->logfile_root) == false)
    {
      mkdir($this->logfile_root, 0775, True);
    }
    if (file_exists($this->logfile_root))
    {
      exec(sprintf("%s > %s 2>&1 & echo $!", $command, $this->logfile), $pid);
      if ($pid[0] > 0)
      {
        $sql = "UPDATE core.process SET command = $1, pid = $2 WHERE rowid = $3";
        algaeDB::executeQuery($sql, array($command, $pid[0], $this->rowid));
        $this->command = $command;
        $this->pid = $pid[0];
        // $this->status_page = $status_page;
        // $this->showBackgroundMessage();
        return true;
      }
      else
      {
        $app->errorMessage('Unable to start the process in the background.');
        echo 'DEBUG: Command = ', $command, '<p />';
        echo 'DEBUG: Logfile = ', $this->logfile, '<p />';
      }
    }
    else 
    {
      algaeApp::errorMessage('Directory ' . $this->logfile_root . ' does not exist and could not create it.');
    }
    return false;
  }
  
  /**
   * Check if running using the process-id.
   * https://stackoverflow.com/questions/45953/php-execute-a-background-process
   * @return boolean
   */
  public function isRunning()
  // --------------------------------------------------------------------------
  {
    try
    {
      $result = shell_exec(sprintf("ps %d", $this->pid));
      if( count(preg_split("/\n/", $result)) > 2)
      {
        return true;
      }
    }
    catch(Exception $e){}
    return false;
  }
  
  public function isFinished()
  // --------------------------------------------------------------------------
  {
    if ( ($this->process_status->name == 'Finished') || ($this->process_status->name == 'Success') )
    {
      return True;
    }
    return False;
  }
  
  /**
   * Get a results link.
   * @return string Completed results link with label.
   */
  public function getLink($url, $label)
  // --------------------------------------------------------------------------
  {
    $html = '';
    if (strlen($url) > 0)
    {
      if (strncasecmp($url, '<a href', 7) == 0)
      {
        $html = $url;
      }
      else 
      {
        $html = '<a href="' . $url . '">' . $label . '</a>';  
      }
    }
    return $html;
  }
  
  protected function getResultslink()
  // --------------------------------------------------------------------------
  {
    $html = '';
    if (! $this->isFinished()) return '';
    if (strncasecmp($this->result_url, 'download', 8) == 0)
    {
      $parts = explode(' ', $this->result_url);
      if (count($parts) == 2)
      {
        $html = algaeFile::getDownloadLink($parts[1]);
      }
    }
    else 
    {
      $html = $this->getLink($this->result_url, 'Results');
    }
    return $html;
  }
  
  public function getStatusMessage()
  // --------------------------------------------------------------------------
  {
    if (strlen($this->process_status->name) > 0)
      return algaeCore::getColorBlock($this->process_status->html_color, True, $this->process_status->name);
    return null;
  }
  
  protected function reportOverallDetails()
  // --------------------------------------------------------------------------
  {
    algaeTable::start('processDetailsTable', 'algae_table', 'width:60%');
    algaeTable::writeHeader(array(), False);
    algaeTable::writeTwoColumns('Application', $this->application);
    algaeTable::writeTwoColumns('Status', $this->getStatusMessage(), False);
    algaeTable::writeTwoColumns('Progress', algaeCore::getPercentageBar($this->progress, 200, 0), False);
    algaeTable::writeTwoColumns('Message', $this->progress_message);
    algaeTable::writeTwoColumns('Started', $this->timestamp_loaded_utc);
    if ($this->isFinished())
    {
      algaeTable::writeTwoColumns('Finished', $this->timestamp_modified_utc);
      algaeTable::writeTwoColumns('Run Time', $this->run_time);
      algaeTable::writeTwoColumns('Results', $this->getResultslink(), False);
      algaeTable::writeTwoColumns('Source', $this->getLink($this->starting_url, 'Run Again'), False);
    }
    algaeTable::writeTwoColumns('Command', $this->command);
    algaeTable::writeTwoColumns('Logfile', $this->logfile);
    algaeTable::writeTwoColumns('Parameters File', $this->parmsfile);
    algaeTable::writeTwoColumns('Owner', $this->owner);
    algaeTable::writeTwoColumns('PID', $this->pid);
    algaeTable::writeTwoColumns('Description', algaeCore::getStringWithLinks($this->description), False);
    algaeTable::writeTwoColumns('Rowid', $this->rowid);
    algaeTable::end();
  }
  
  public function updateStatus()
  // --------------------------------------------------------------------------
  {
    if (! $this->isFinished())
    {
      if (! $this->isRunning())
      {
        $sql = "UPDATE $this->table_name SET process_status_rowid_fk = ";
        $sql .= algaeDB::getRowidSQLOrNull('ref.process_status', 'name', 'Finished');
        $sql .= ", progress = 100";
        $sql .= " WHERE rowid = $1";
        algaeDB::executeQuery($sql, array($this->rowid));
      }
    }
  }
  
  public function reportDetails()
  // --------------------------------------------------------------------------
  {
    $this->updateStatus();
    if ($this->process_status->name == 'Running')
    {
      echo algaeForm::button('refresh', 'Refresh', 'location.reload();');
      echo '<p />';
    }
    //
    // ----- initial tabs setup
    //
    algaeForm::startTabs(array(
      array('#overview_tab', 'Overview'),
      array('#parms_tab', 'Parameters'),
      array('#log_tab', 'Log')
    ));
    //
    // ----- overview_tab
    //
    echo '<div id="overview_tab">';
    $this->reportOverallDetails();
    /*
    if ($this->process_status_name == 'Running')
    {
      echo '<p />';
      echo algaeForm::button('refresh', 'Refresh', 'location.reload();');
    }
    */
    echo '</div>';
    //
    // ----- parms_tab
    //
    echo '<div id="parms_tab">';
    algaeFile::showTextFile($this->parmsfile);
    echo '</div>';
    //
    // ----- log_tab
    //
    echo '<div id="log_tab">';
    algaeFile::showTextFile($this->logfile);
    echo '</div>';
    //
    // ----- end of all tabs div
    //
    algaeForm::endTabs();
  }
  
  public function reportRecords($tableId='processesTable', $whereClause = '', $maxRecords = 10000)
  // --------------------------------------------------------------------------
  {
    $class = get_class($this);
    $p = new $class();
    $sql = $p->getSQL();
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
          echo date("d-M-Y H:i:s"), ' UTC<p />';
          //
          // ----- initial the table
          //
          algaeTable::initTablesorterJavascript($tableId, '[[1,1]]', True, "headers: {1: {sorter:'milDate'} }");
          algaeTable::start($tableId, 'tablesorter', 'width:100%;');
          //
          // ----- table header
          //
          $header_array = array(
            array('Action', '8%'),
            array('Started', '10%'),
            array('Application', '10%'),
            array('Status', '8%'),
            array('Progress', '10%'),
            array('Message', '15%'),
            array('PID', '5%')
          );
          algaeTable::writeHeader($header_array, True);
          //
          // ----- loop through the results
          //
          while ($row = pg_fetch_array($result))
          {
            $p->init();
            $p->readRowFromDatabase($row);
            echo '<tr>';
            algaeTable::writeData($p->getActionLinks(), False);
            algaeTable::writeData($p->timestamp_loaded_utc);
            algaeTable::writeData($p->application);
            algaeTable::writeData($p->getStatusMessage(), False);
            algaeTable::writeData(algaeCore::getPercentageBar($p->progress, 100, 0), False);
            algaeTable::writeData($p->progress_message);
            algaeTable::writeData($p->pid);
            echo '</tr>';
          }
          algaeTable::end();
        }
        else
        {
          echo 'No data in ', $p->table_name, '.<p />';
        }
        algaeDB::close($db, $result);
      }
    }
  }
  
  /**
   * Report processes in a tabbed dialog.
   */
  public function reportWithTabs()
  // --------------------------------------------------------------------------
  {
    algaeForm::startSingleTab('Processes');
    $this->reportRecords();
    algaeForm::endSingleTab();
  }
  
  public function checkStatusButton()
  // --------------------------------------------------------------------------
  {
    return algaeForm::button('check_status', 'Check Status', "window.open('process_status.php?rowid=" . $this->rowid . "', '_self');");
  }
  
  public function showStatus()
  // --------------------------------------------------------------------------
  {
    $this->readRowFromDatabaseWithRowid($this->rowid);
    $this->reportDetails();
  }
  
  /**
   * 
   * @param string $link
   * @return boolean
   */
  public function updateResultsURL($link)
  // --------------------------------------------------------------------------
  {
    $sql = "UPDATE $this->table_name SET result_url = $1 WHERE rowid = $2";
    return algaeDB::executeQuery($sql, array($link, $this->rowid));
  }
  
  public function writeParametersFile($parms)
  // --------------------------------------------------------------------------
  {
    global $app;
    if (strlen($this->parmsfile) == 0)
    {
      $app->errorMessage('Parameter filename not defined in ' . get_class($this) . '::' . __FUNCTION__ . '().');
    }
    $fp = fopen($this->parmsfile, 'w');
    fwrite($fp, json_encode($parms, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fclose($fp);
  }
  
  /**
   * Show what will be deleted.
   * {@inheritDoc}
   * @see algaeTblBase::showWhatsBeingDeleted()
   */
  public function showWhatsBeingDeleted()
  // --------------------------------------------------------------------------
  {
    echo '1 row from ', $this->table_name, '.<p />';
  }
  
  /**
   * Delete the process.
   * {@inheritDoc}
   * @see algaeTblBase::delete()
   */
  public function delete()
  // --------------------------------------------------------------------------
  {
    $num_errors = 0;
    if ($num_errors == 0)
    {
      if (strlen($this->logfile) > 0)
      {
        if (! algaeFile::deleteFileFromFilesystem($this->logfile))
        {
          $num_errors += 1;
        }
      }
    }
    if ($num_errors == 0)
    {
      if (strlen($this->parmsfile) > 0)
      {
        if (! algaeFile::deleteFileFromFilesystem($this->parmsfile))
        {
          $num_errors += 1;
        }
      }
    }
    if ($num_errors == 0)
    {
      if (! algaeDB::deleteFromTable($this->table_name, 'rowid', $this->rowid))
      {
        $num_errors += 1;
      }
    }
    if ($num_errors == 0) return True;
    return False;
  }
  
}


