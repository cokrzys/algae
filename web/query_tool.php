<?php

  /**
  
    algae framework | Query tool.
    
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
  require_once 'algaeQueryTool.php';
  //
  // ----- check login and rights
  //
  algaeAccess::isLoggedIn();
  $app->readRoles();
  $app->isSufficientRights(algaeAccess::ROLE_ADMIN, $app->config->app_name);
  //
  // ----- initial the html page
  //
  $title = 'Query Tool';
  $app->startPage($title, '', False);
  $app->addJavaScriptLibrary('/algae/js/algaeQueryTool.js');
  $app->closeHeadSection();
  $app->showHeader($title);
  //
  // ----- page content
  //
  $qt = new algaeQueryTool();
  $qt->show();
  //
  // ----- finish up and close page
  //
  $app->showFooter();
  $app->closePage();

