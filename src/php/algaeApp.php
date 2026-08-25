<?php

/**

  algae framework | Application base class.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
*/

class algaeApp
{

  public $starttime;
  public $settings;
  public $roleSet;
  public $colStatsDialogAdded;
  public $config;

  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    //
    // ----- core classes
    //
    require_once 'algaeConfig.php';
    require_once 'algaeAccess.php';
    require_once 'algaeCore.php';
    require_once 'algaeAdmin.php';
    require_once 'algaeFile.php';
    require_once 'algaeDB.php';
    require_once 'algaeTable.php';
    require_once 'algaeForm.php';
    /*
    require_once 'algaeQueryTool.php';
    require_once 'algaeSVG.php';
    require_once 'algaeGraph.php';
    require_once 'algaeGraphAxis.php';
    require_once 'algaeGraphSeriesAttribs.php';
    require_once 'algaeGraphLegendItem.php';
    require_once 'algaeXYGraph.php';
    require_once 'algaeLineGraph.php';
    require_once 'algaeHistogram.php';
    require_once 'algaeTreemap.php';
    require_once 'algaeBoxplot.php';
    require_once 'algaeBarChart.php';
    require_once 'algaeNumericStats.php';
    require_once 'algaeMapLayer.php';
    require_once 'algaeMapLayerShapefile.php';
    require_once 'algaeMapLayerPostGIS.php';
    require_once 'algaeMap.php';
    //
    // ----- database tables
    //
     */
    require_once 'algaeTblBase.php';
    require_once 'algaeTblRecordStatus.php';
    require_once 'algaeTblNamedObjectBase.php';
    /*
    require_once 'algaeTblReferenceBase.php';
    require_once 'algaeTblSetItemBase.php';
    require_once 'algaeTblSetBase.php';
    */
    require_once 'algaeTblCoreUser.php';
    require_once 'algaeTblCoreUserRight.php';
    require_once 'algaeTblCoreUserParameter.php';
    /*
    require_once 'algaeTblCoreStandardQuery.php';
    require_once 'algaeTblField.php';
    require_once 'algaeTblFieldSet.php';
    require_once 'algaeTblBreak.php';
    require_once 'algaeTblStyle.php';
    require_once 'algaeTblCalcType.php';
    require_once 'algaeTblFile.php';
    require_once 'algaeTblDataValue.php';
    //
    // ----- database tables V2
    //
    require_once '/var/www/html/algae/classes/V2/algaeTblBaseV2.php';
    require_once '/var/www/html/algae/classes/V2/algaeTblRecordStatusV2.php';
    require_once '/var/www/html/algae/classes/V2/algaeTblCoreUserV2.php';
    require_once '/var/www/html/algae/classes/V2/algaeTblNamedObjectBaseV2.php';
    require_once '/var/www/html/algae/classes/V2/algaeTblReferenceBaseV2.php';
    // require_once 'algaeTblNamedObjectBase.php';
    // require_once 'algaeTblReferenceBase.php';
    //
    // ----- reporting and stats
    //
    require_once 'algaeReport.php';
    require_once 'algaeFieldStats.php';
    //
    // ----- processing
    //
    require_once 'algaeTblCoreProcess.php';
    //
    //
    //
     */
    $this->config = new algaeConfig();
    /*
    //
    // ----- variables
    //
    $this->starttime = microtime(true);
    $this->settings = new algaeSettings();
    $this->roleSet = null;
    $this->colStatsDialogAdded = False;
    //
    // ----- 3rd party libraries
    //
    */
    require_once 'SqlFormatter.php';
    //
    // ----- application specific classes
    //       moved to application constructor 11 Mar 2025
    //       supports improved pathing
    //
    // $this->addAppSpecificClasses();
  }
  
  /**
   * Add support for the Tasks module.
   */
  protected function addTasksSupport()
  // --------------------------------------------------------------------------
  {
    require_once 'algaeTblPriority.php';
    require_once 'algaeTblTaskCategory.php';
    require_once 'algaeTblTask.php';
  }	
  
  /**
   * Add support for linked photos.
   */
  public function addPhotosLinkSupport()
  // --------------------------------------------------------------------------
  {
    $prefix = $this->config->web_root_folder . 'phobel' . '/classes/';
    require_once $prefix . 'paApp.php';
    require_once $prefix . 'paMediaType.php';
    require_once $prefix . 'paPhoto.php';
    require_once $prefix . 'paPhotoLink.php';
    require_once $prefix . 'paPhotoLinkEditor.php';
  }
  
  /**
   * Call this just to eliminate the error messages about $app not being used on AJAX calls.
   */
  public function doNothing() {}
  
  /**
   * Add application specific classes.
   */
  protected function addAppSpecificClasses()
  // --------------------------------------------------------------------------
  {
  }	

  /**
   * Add a JavaScript library.
   */
  public function addJavaScriptLibrary($filename, $type = 'text/javascript')
  // --------------------------------------------------------------------------
  {
    echo '<script type="', $type, '" src="', $filename, '"></script>';
  }
  
  /**
   * Add a style sheet.
   */
  public function addStyleSheet($filename)
  // --------------------------------------------------------------------------
  {
    echo '<link rel="stylesheet" type="text/css" href="', $filename, '" />';
  }
  
  /**
   * Add a JavaScript component.
   * @param array $cssFiles Array of CSS files associated with the component.
   * @param array $javascriptFiles Array of JavaScript files associated with the component.
   */
  public function addComponentObsolete($cssFiles, $javascriptFiles, $javaScriptType = 'text/javascript')
  // --------------------------------------------------------------------------
  {
    //
    // ----- note that the CSS file is often required before the JavaScript file (i.e. Leaflet)
    //
    foreach ($cssFiles as $css)
    {
      $this->addStyleSheet($css);
    }
    foreach ($javascriptFiles as $js)
    {
      $this->addJavaScriptLibrary($js, $javaScriptType);
    }
  }
  
  public function addWebModule($files, $javaScriptType = 'text/javascript')
  // --------------------------------------------------------------------------
  {
    foreach ($files as $file)
    {
      if (str_ends_with($file, '.css'))
      {
        $this->addStyleSheet($file);
      }
      else 
      {
        $this->addJavaScriptLibrary($file, $javaScriptType);
      }
    }
  }

  /**
   * Add D3.js files to a webpage.
   */
  public function addD3js()
  // --------------------------------------------------------------------------
  {
    $this->addJavaScriptLibrary('d3/d3.v3.min.js');
  }

  /**
   * Start an HTML page with standard stuff.
   */
  public function startPage($title, $css = '', $close_head_section = True)
  // --------------------------------------------------------------------------
  {
    $files_tag = 'files';
    //
    // ----- start the page
    //
    if (session_status() != PHP_SESSION_ACTIVE)
    {
      session_start();
    }
    header('Content-type: text/html; charset=utf-8');
    // echo '<!DOCTYPE html><html lang="en">';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />';
    echo '<title>', $title, ' - ', $this->config->app_name, '</title>';
    echo '<link rel="icon" type="image/png" href="', $this->config->favicon, '">';
    //
    // javascript components
    //
    foreach ($this->config->wm_json as $module)
    {
      // echo 'DEBUG: ', $module->name, '<p />';
      if (property_exists($module, $files_tag))
      {
        $this->addWebModule($module->{$files_tag});
      }
    }
    /*
    $this->addComponent($this->settings->jQueryCSS, $this->settings->jQuery);
    $this->addComponent($this->settings->tablesorterCSS, $this->settings->tablesorter);
    $this->addComponent($this->settings->codeMirrorCSS, $this->settings->codeMirrorJavaScript);
    $this->addComponent($this->settings->graphsCSS, $this->settings->graphsJavaScript);
    $this->addComponent($this->settings->leafletCSS, $this->settings->leafletJavaScript);
    $this->addComponent(array(), $this->settings->sorttableJavaScript);
    */
    //
    // ----- algae CSS, typically loaded as the last css file since it overrides some of what happens earlier
    //
    if (strlen($css) == 0) $css = $this->config->default_css;
    $this->addStyleSheet($css);
    //
    // ----- algae JavaScript
    //
    /*
    foreach ($this->settings->algaeJavaScript as $js)
    {
      $this->addJavaScriptLibrary($js);
    }
    */

    if ($close_head_section) $this->closeHeadSection();
      
  }

  /**
   * Close the head section and start the body.
   */
  public function closeHeadSection()
  // --------------------------------------------------------------------------
  {
    echo '</head>';
    echo '<body>';
  }

  /**
   * Close an html page with standard stuff.
   */
  public function closePage()
  // --------------------------------------------------------------------------
  {
    //
    // ----- dialog containers
    //
    echo '<div id="miniGraphDialog" title="Mini Graphs Dialog"></div>';
    echo '<div id="mapDialog" title="Map Dialog"></div>';
    echo '</body>';
    echo '</html>';
  }
  
  protected function addMenu()
  // --------------------------------------------------------------------------
  {
  }

  /**
   * Show a standard header for the application.
   *
   * @param string $title Page title.
   * @param boolean $addMenu Add the lower level main menu.
   * @param boolean $addRTSMenu Add the main RTSpatial and shortcuts menu across the top left.
   */
  public function showHeader($title, $addMenu = True, $addRTSMenu = True)
  // --------------------------------------------------------------------------
  {
    /*
    echo '<h1>', $this->settings->appName, ' ', $title, '</h1>';
    if (algaeAccess::isLoggedIn(False, False)) 
    {
      $upperRightText = '<a href="query_tool.php">Query</a>';
      $upperRightText .= '&nbsp;|&nbsp;' . algaeAccess::getLogoutLink();
      echo '<div class="top_right_links">', $upperRightText, '</div>';
    }
    echo '<hr class="header">';
    if ($addMenu) $this->addMenu();
    echo '<div class="main_body_indent">';
    if (isset($_REQUEST['message']))
    {
      $this->successMessage($_REQUEST['message']);
    }
    */
    
    echo '<a href="/algae/home.php">algae</a>';
    echo $this->config->menu_separator, $title;
    
    /*
    if ($addRTSMenu)
    {
      echo '<a href="/admdal/home.php">RTS</a>';
      echo ' | <a href="/exptog/edit_expense.php">exp</a>';
      echo ' | <a href="/stkard/home.php">stk</a>';
      echo ' | <a href="/tacobi/edit_activity.php">tac</a>';
      echo ' | ', $title; // , $this->settings->appName;
    }
    else 
    {
      if ($addMenu)
      {
        $this->addMenu();
      }
      else 
      {
        echo $title;
      }
    }
    */
    
    // echo '<h1><a href="/admin/home.php">RTSpatial</a>&nbsp;';
    // echo '<img src="', $this->settings->favicon, '" alt="AppIcon">&nbsp;';
    // echo ucfirst($this->settings->appName), '</h1>';
    // echo '<h1><a href="/admin/home.php">RTSpatial</a> ', $this->settings->appName, ' ', $title, '</h1>';
    if ( ($this->config->show_query_link) && ((algaeAccess::isLoggedIn(False, False)) || (! $this->config->security_on)) )
    {
      $upperRightText = '<a href="' . $this->getURLBase() . 'query_tool.php" target="_blank">Query</a>';
      if ($this->config->security_on) $upperRightText .= '&nbsp;|&nbsp;' . algaeAccess::getLogoutLink();
      // $upperRightText .= '&nbsp;|&nbsp;' . '<a href="/admin/home.php">RTSpatial</a>';
      echo '<div class="top_right_links">', $upperRightText, '</div>';
    }
    echo '<hr class="header">';
    if ($addMenu && $addRTSMenu) $this->addMenu();
    echo '<div class="main_body_indent">';
    if (isset($_REQUEST['message']))
    {
      $this->successMessage($_REQUEST['message']);
    }
  }

  /**
   * Show a standard footer.
   */
  public function showFooter()
  // --------------------------------------------------------------------------
  {
    echo '</div>';  // ends the main body indent
    echo '<br /><hr class="footer">';
    echo '<div class="page_footer">';
    echo $this->config->footer_message;
    echo $this->config->menu_separator;
    echo gmdate("d-M-Y H:i:s"), ' UTC';
    echo '<p /></div>';
  }

  /**
   * Show elapsed time since the application object was instantiated.
   */
  public function showElapsedTime($message = 'Elapsed time: ')
  // --------------------------------------------------------------------------
  {
    echo $message, algaeCore::getFormattedNumber(microtime(true) - $this->starttime, 2, - 99), ' seconds.<p />';
  }

  /**
   * Encrypt a string.
   */
  public function encrypt($str)
  // --------------------------------------------------------------------------
  {
    $iv_size = openssl_cipher_iv_length($this->settings->encryptionMethod);
    $iv = openssl_random_pseudo_bytes($iv_size);
    return base64_encode($iv) . base64_encode(openssl_encrypt($str, $this->settings->encryptionMethod, $this->settings->encryptionKey, 0, $iv));
  }

  /**
   * Decrypt a string.
   */
  public function decrypt($str)
  // --------------------------------------------------------------------------
  {
    $iv_size = openssl_cipher_iv_length($this->settings->encryptionMethod);
    $encoded_size = strlen(base64_encode(openssl_random_pseudo_bytes($iv_size)));
    $iv = base64_decode(substr($str, 0, $encoded_size));
    return openssl_decrypt(base64_decode(substr($str, $encoded_size)), $this->settings->encryptionMethod, $this->settings->encryptionKey, 0, $iv);    
  }

  /**
   * Get the database password from an encrypted Apache environment variable.
   */
  public function getDbPassword()
  // --------------------------------------------------------------------------
  {
    if (isset($_SERVER[$this->settings->databasePasswordEnvVariable])) 
    {
      $pw = $_SERVER[$this->settings->databasePasswordEnvVariable];
      // error_log('DEBUG: $pw = ' . $pw, 0);
      if (strlen($pw) > 0) 
      {
        return $this->decrypt($pw);
      }
    }
    return '';
  }
  
  /**
   * Read the roles for the user, must be logged-in first.
   */
  public function readRoles()
  // --------------------------------------------------------------------------
  {
    $this->roleSet = algaeAccess::readRoles();
  }
  
  /**
   * Check if the logged in user has sufficient rights to proceed, must have called readRoles() first.
   * @param integer $requiredRole The role required, from a constant like algaeAccess::ROLE_READ.
   */
  public function isSufficientRights($requiredRole, $object, $die = True)
  // --------------------------------------------------------------------------
  {
    if ($this->config->security_on == False) return True;
    $ret = False;
    //
    // ----- check role
    //
    if (isset($this->roleSet))
    {
      $ret = algaeAccess::isSufficientRights($this->roleSet, $requiredRole, $object);
    }
    else
    {
      $this->errorMessage("Rights not defined.");
    }
    if ((! $ret) && ($die))
    {
      //
      // ----- assumption is a new page is started so get it formatted right
      //
      $title = "Insufficient Rights";
      $this->startPage($title);
      $this->showHeader($title);
      $this->errorMessage("Sorry, you don't have rights to view the page.");
      //
      // ----- add a nice back link
      //
      echo  '<a href="javascript:history.back()">Back</a><p />';
      //
      // ----- footer and close the page
      //
      $this->showFooter();
      $this->closePage();
      die();
    }
    return $ret;
  }

  /**
   * Display an error message.
   * @param string $message Message to display.
   * @param boolean $encloseInTab True to enclose in a tab, default False.
   */
  public static function errorMessage($message, $encloseInTab = False, $writeToErrorLog = False)
  // --------------------------------------------------------------------------
  {
    if ($encloseInTab) { algaeForm::startSingleTab('Error'); }
    echo '<span class="error_message">', $message, '</span><p />';
    if ($encloseInTab) { algaeForm::endSingleTab(); }
    #
    # ----- write to error log if flag is set
    #
    if ($writeToErrorLog)
    {
      error_log('algaeError: ' . $message, 0);
    }
  }
  
  /**
   * Display a success message.
   */
  public static function successMessage($message)
  // --------------------------------------------------------------------------
  {
    echo '<span class="success_message">', $message, '</span><p />';
  }
  
  public function getDetailString($str, $addSeparatorPrefix = True)
  // --------------------------------------------------------------------------
  {
    $html = '<span class="detail_text">';
    if ($addSeparatorPrefix)
    {
      $html .= $this->settings->menuSeparator;
    }
    $html .= $str . '</span>';
    return $html;
  }
  
  /**
   * Check a variable to see if it's set, error message if not.
   * @param object $var The variable.
   * @param string $varName Variable name.
   * @param string $className Class name.
   * @param integer $numErrors Number of errors.
   * @return boolean True if set, false if not and $numErrors is incremented.
   */
  public static function checkVariable($var, $varName, $className, &$numErrors)
  // --------------------------------------------------------------------------
  {
    if ( (! isset($var)) || (strlen($var) == 0) || ($var == null) )
    {
      algaeApp::errorMessage($varName . ' not set in ' . $className . '.');
      $numErrors++;
      return false;
    }
    return true;
  }
  
  /**
   * Tests is a page is the current page.
   * @param string $urlPage Page, i.e. browse_files.php.
   * @return boolean True if current, False if not.
   */
  public function isCurrentPage($urlPage)
  // --------------------------------------------------------------------------
  {
    $fromPage = basename(strtolower(basename($_SERVER['PHP_SELF'])), '.php');
    $fromPage .= '.php';
    if (strcmp($fromPage, $urlPage) == 0) return True;
    return False;
  }
  
  /**
   * Get the base URL for the application, i.e. '/name/'.
   * The name is sourced from $this->settings->appFolder.
   * @return string Base URL with backslashes.
   */
  public function getURLBase()
  // --------------------------------------------------------------------------
  {
    return '/' . $this->config->app_folder . '/';
  }
  
  /**
   * Get a link to a page if a user has sufficient rights.
   * @param string $urlPage URL of the page, for example home.php.
   * @param string $label Label for the link.
   * @param integer $requiredRole The role required, from a constant like algaeAccess::ROLE_READ.
   * @param string $object Object to access, typically the application name, for example $app->settings->appName.
   * @param string $separator Separator to add at the end.
   * @param boolean $openInNewTab True to open in a new tab, default is False.
   * @return string HTML for the link.
   */
  public function getPageLink($urlPage, $label, $requiredRole, $object, $separator = '&nbsp;|&nbsp;', $openInNewTab = False, $title = null)
  // --------------------------------------------------------------------------
  {
    $html = '';
    $fromPage = basename(strtolower(basename($_SERVER['PHP_SELF'])), '.php');
    $fromPage .= '.php';
    $rights = False;
    if ($requiredRole == algaeAccess::ROLE_GUEST)
    {
      $rights = True;
    }
    else
    {
      $rights = $this->isSufficientRights($requiredRole, $object, False);
    }
    if ( (strcmp($fromPage, $urlPage) == 0) || (! $rights) )
    {
      if ($rights)
      {
        $html .= '<b>' . algaeCore::toHtml($label) . '</b>';
      }
    }
    else
    {
      $html .= '<a';
      if ($openInNewTab)
      {
        $html .= ' target="_blank"';
      }
      $html .= ' href="' . $urlPage . '"';
      if ($title != null)
      {
        $title = str_replace('"', '', $title);
        $title = str_replace("'", '', $title);
        $html .= ' title="' . $title . '"';
      }
      $html .= '>' . algaeCore::toHtml($label) . '</a>';
    }
    if (strlen($html) > 0) $html .= $separator;
    return $html;
  }
  
  /**
   * Show output from an exec() command using the CSS class preformatted.
   * @param array $output Array of output lines.
   */
  public static function showExecOutput($output)
  // --------------------------------------------------------------------------
  {
    echo '<div class="preformatted">';
    foreach ($output as $line)
    {
      echo $line, '<p />';
    }
    echo '</div>';
  }
  
}