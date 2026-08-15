<?php

  /**
  
    algae framework | Login page.
  
    @author    Brian Krzys (brian.krzys@rtspatial.com)
    @copyright (c) 2026 RTSpatial Ltd.
    @license   SPDX-License-Identifier: MIT
    @link      https://github.com/cokrzys/slate
  
  */

  //
  // ----- initial the algae framework and application
  //
  require_once 'algaeApp.php';
  $app = new algaeApp();
  //
  // ----- if logged-in re-direct past this page
  //
  algaeAccess::isLoggedIn(False);
  //
  // ----- initial the html page
  //
  $title = 'Login';
  $app->startPage($title);
  // $app->showHeader('', False);
  //
  // ----- page content
  //
  $o = new algaeAdmin();
  $o->processLoginForm();
  $o->showLoginForm();
  //
  // ----- finish up and close page
  //
  // $app->showFooter();
  $app->closePage();
