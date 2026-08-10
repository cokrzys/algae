<?php

  header('Content-type: text/html; charset=utf-8');
  echo '<html>';
  echo '<head>';
  echo '<meta charset="UTF-8">';
  echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />';
  echo '<title>algae Install Check</title>';
  echo '</head>';
  echo '<body>';
  
  echo 'algae framework installation check<p />';
  
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
    
  $config = new algaeConfig();
  
  echo 'OK: ', $mainConfigFile, ' found and instance created.';
  
  
  echo '</body>';
  echo '</html>';

?>