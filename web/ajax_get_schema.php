<?php

  /**
  
    algae framework | Get table schema via an ajax call.
    
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
  
  $app->doNothing();         // do this just to clear the warning about not using $app

  algaeQueryTool::processAjaxGetSchema();