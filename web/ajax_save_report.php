<?php

  /**
  
    algae framework | Save a report via an ajax call.
    
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
  $app->config->app_database = 'slate';
  
  //
  // ----- check login and rights
  //
  algaeAccess::isLoggedIn();
  $app->readRoles();
  $app->isSufficientRights(algaeAccess::ROLE_READ, $app->config->app_name);
  
  $r = new algaeReport();
  $r->save();