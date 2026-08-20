<?php

  /**
  
    algae framework | Add a user.
    
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
  $app->isSufficientRights(algaeAccess::ROLE_SYSADMIN, $app->config->app_name);
  //
  // ----- initial the html page
  //
  $title = 'Add a User';
  $app->startPage($title);
  $app->showHeader($title);
  //
  // ----- page content
  //
  $o = new algaeAdmin();
  $o->processAddUserForm();
  $o->showAddUserForm();
  //
  // ----- finish up and close page
  //
  $app->showFooter();
  $app->closePage();

