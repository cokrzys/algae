<?php

  /**
  
    algae framework | View a text file.
    
    @author    Brian Krzys (brian.krzys@rtspatial.com)
    @copyright (c) 2026 RTSpatial Ltd.
    @license   SPDX-License-Identifier: MIT
    @link      https://github.com/cokrzys/algae
  
  */
  
  //
  // ----- initial the algae framework and application
  //
  require_once 'algaeApp.php';
  $app = new algaeApp();
  //
  // ----- check login and rights
  //
  algaeAccess::isLoggedIn();
  $app->readRoles();
  $app->isSufficientRights(algaeAccess::ROLE_READ, $app->config->app_name);
  //
  // ----- read object first so the filename can be displayed in the titles
  //
  $title = 'File Viewer';
  if (isset($_REQUEST['filename']))
  {
    $filename = algaeForm::cleanInput($_REQUEST['filename']);
    if (strlen($filename) > 0) $title = basename($filename);
  }
  //
  // ----- initial the html page
  //
  $app->startPage($title);
  $app->showHeader($title);
  //
  // ----- page content
  //
  algaeForm::startSingleTab($title);
  algaeFile::showTextFile($filename);
  algaeForm::endSingleTab();
  //
  // ----- finish up and close page
  //
  $app->showFooter();
  $app->closePage();
