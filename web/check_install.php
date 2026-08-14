<?php

  header('Content-type: text/html; charset=utf-8');
  echo '<html>';
  echo '<head>';
  echo '<meta charset="UTF-8">';
  echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />';
  echo '<title>algae Install Check</title>';
  echo '</head>';
  echo '<body>';
  
  echo 'algae framework checks<p />';
  
  $mainConfigFile = 'algaeConfig.php';
  try 
  {
    require $mainConfigFile;
  } 
  catch (\Throwable $e) 
  {
    echo 'ERROR: ', $e->getMessage();
    exit();
  }
  
  require 'algaeCore.php';
    
  $config = new algaeConfig(True);
  
  $config->showConfig(true);
  
  require_once 'algaeApp.php';
  require_once 'algaeDB.php';
  
  $app = new algaeApp();
  
  $db = algaeDB::connect();
  if ($db)
  {
    echo 'OK: Connected to database ', $app->config->admin_database, '.<p />';
    algaeDB::close($db);
  }
  
  echo '</body>';
  echo '</html>';

?>