<?php

  /**
  
    algae framework | Logout page.
    
    @author    Brian Krzys (brian.krzys@rtspatial.com)
    @copyright (c) 2026 RTSpatial Ltd.
    @license   SPDX-License-Identifier: MIT
    @link      https://github.com/cokrzys/algae
  
  */
  
  require_once 'algaeApp.php';
  $app = new algaeApp();
  
  //
  // ----- destroy session variables and goto the login screen
  //
  session_unset();
  session_start();
  session_destroy();
  setcookie("sid", "", time() - 3600);
  header("Location: " . $app->config->login_page);
