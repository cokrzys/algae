<?php

/**

  algae framework | Download a file.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

  // example: place this kind of link into the document where the file download is offered:
  // <a href="download?download_file=some_file.pdf">Download here</a>
  
  // TODO: Download file is crude, needs work.

  $fullPath = $_GET['download_file'];
  
  if (file_exists($fullPath))
  {
    
    if ($fd = fopen ($fullPath, "r"))
    {
      $fsize = filesize($fullPath);
      $path_parts = pathinfo($fullPath);
      $ext = strtolower($path_parts["extension"]);
      switch ($ext)
      {
        //
        // ----- add additional extensions as needed
        //
        case "csv":
          header("Content-type: text/csv");
          header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a download
          break;
        case "pdf":
          header("Content-type: application/pdf");
          header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a download
          break;
        case "kmz":
          header("Content-type: application/kmz");
          header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a download
          break;
        default;
        header("Content-type: application/octet-stream");
        header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
      }
      header("Content-length: $fsize");
      header("Cache-control: private"); //use this to open files directly
      while(!feof($fd)) {
        $buffer = fread($fd, 2048);
        echo $buffer;
      }
    }
    fclose ($fd);
    
  }
  else
  {
    echo 'File ', $fullPath, ' not found.<p />';
  }
  
  exit;
