<?php

  /**
  
    algae framework | Initial setup page.
    
    @author    Brian Krzys (brian.krzys@rtspatial.com)
    @copyright (c) 2026 RTSpatial Ltd.
    @license   SPDX-License-Identifier: MIT
    @link      https://github.com/cokrzys/slate
  
  */

  //
  // ----- initial the algae framework
  //
  require_once 'algaeApp.php';
  $app = new algaeApp();
  //
  // ----- check login and rights
  //
  algaeAccess::isLoggedIn();
  $app->readRoles();
  $app->isSufficientRights(algaeAccess::ROLE_SYSADMIN, $app->config->app_name);
  //
  // ----- initial the html page
  //
  $title = 'Setup';
  $app->startPage($title);
  $app->showHeader($title);
  //
  // ----- page content
  //
  algaeForm::startSingleTab($title);
  // $app->showSummary();
  algaeForm::endSingleTab();
  //
  // ----- finish up and close page
  //
  $app->showFooter();
  $app->closePage();


