<?php

/**

  algae framework | Files support class.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/slate

*/

class algaeFile
{
  
  public $target_dir;
  public $max_size;
  public $bytes_uploaded;
  public $uploaded_array;
  public $uploaded_single_file;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    $this->target_dir = null;
    $this->max_size = 2097152000; // 2GB
    $this->bytes_uploaded = 0;
    $this->uploaded_array = array();
    $this->uploaded_single_file = '';
    
    //
    // 22 Jul 2022, not tested extensively, but does not seem to work
    // see https://www.kavoir.com/2010/02/php-get-the-file-uploading-limit-max-file-size-allowed-to-upload.html
    //
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $this->max_size = min($max_upload, $max_post, $memory_limit);
    
  }
  
  /**
   * Delete a single file from the filesystem.  If the file deletion is not
   * successful a message is written to the Apache error log.
   * @param string $filename The fully qualified file to delete.
   */
  public static function deleteFileFromFilesystem($filename)
  // --------------------------------------------------------------------------
  {
    // error_log('Debug: Looking for file ' . $filename, 0);
    if (file_exists($filename))
    {
      // error_log('Debug: Found file ' . $filename . ', deleting it.', 0);
      if (! unlink($filename))
      {
        error_log('Error: Unable to delete file ' . $filename, 0);
        return false;
      }
      else
      {
        // error_log('Debug: File ' . $filename . ' deleted successfully.');
      }
    }
    return true;
  }
  
  /**
   * Delete a directory and all files in it.
   * From: https://paulund.co.uk/php-delete-directory-and-files-in-directory
   * @param string $dirname Directory to delete.
   * @throws InvalidArgumentException
   */
  public static function deleteDirectory($dirname)
    // --------------------------------------------------------------------------
  {
    if (is_dir($dirname))
      $dir_handle = opendir($dirname);
    if (! $dir_handle)
      return false;
    while ($file = readdir($dir_handle)) {
      if ($file != "." && $file != "..") {
        if (! is_dir($dirname . "/" . $file))
          unlink($dirname . "/" . $file);
        else
          delete_directory($dirname . '/' . $file);
      }
    }
    closedir($dir_handle);
    rmdir($dirname);
    return true;
  }
  
  /**
   * Remove extension including the dot from a given file.
   * @param string $filename The filename with extension.
   * @return string Filename without extension.
   */
  public static function removeExtension($fileName)
  // --------------------------------------------------------------------------
  {
    $ext = strrchr($fileName, '.');
    if ($ext !== false)
      $fileName = substr($fileName, 0, -strlen($ext));
      return $fileName;
  }
  
  /**
   * Parse the filename from a path.
   * @param string $filename A fully qualified path and filename.
   * @return string Filename without the path.
   */
  public static function parseFilenameFromPath($path)
  // --------------------------------------------------------------------------
  {
    $slash = strrchr($path, '/');
    if ($slash !== false)
      $path = substr($slash, 1);
      return $path;
  }
  
  /**
   * Get a human readable filesize.  This is from: http://www.php.net/manual/en/function.filesize.php
   * @param integer $bytes The filesize in bytes, use filesize($filename) to get it.
   * @param integer $decimals The number of decimals for the return.
   * @return string The human readable filesize, i.e. 1.0M
   */
  public static function getHumanFilesize($bytes, $decimals = 2)
  // --------------------------------------------------------------------------
  {
    $sz = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
  }
  
  /**
   * Get a link to download a file.
   * @param string $filename Fully qualified name of file to download.
   * @return string A download link if the file exists.
   */
  public static function getDownloadLink($filename, $label='Download', $showSize = true)
  // --------------------------------------------------------------------------
  {
    global $app;
    if (file_exists($filename))
    {
      
      $str = '<a href="' . $app->settings->downloadLink . '?download_file=' . urlencode($filename) . '">' . $label . '</a>';
      if ($showSize)
      {
        $str .= ' (' . algaeFile::getHumanFilesize(filesize($filename), 1) . ')';
      }
      return $str;
    }
    return '';
  }
  
  /**
   * Placeholder for post processing after a file is uploaded.
   * @param string $uploaded_file The uploaded file, fully pathed.
   */
  public function uploadPostProcessor($uploaded_file)
  // --------------------------------------------------------------------------
  {
  }
  
  /**
   * Upload multiple files at once.
   * Original code from:
   * http://www.w3bees.com/2013/02/multiple-file-upload-with-php.html
   * @param string $id Posted id (name) of object with the files.
   * @param $showMessages True (default) to show summary messages about what was uploaded.
   */
  public function uploadMultiple($id, $showMessages = False)
  // --------------------------------------------------------------------------
  {
    global $app;
    $num_uploaded = 0;
    $this->bytes_uploaded = 0;
    $this->uploaded_array = array();
    if (count($_FILES[$id]) > 0)
    {      
      foreach ($_FILES[$id]["error"] as $key => $error) 
      {
        $name = basename($_FILES[$id]["name"][$key]);
        $target_file = $this->target_dir . $name;
        if ($error == UPLOAD_ERR_OK) 
        {
          if (! file_exists($target_file))
          {
            if (move_uploaded_file($_FILES[$id]["tmp_name"][$key], $target_file))
            {
              $num_uploaded++;
              $this->bytes_uploaded += $_FILES[$id]['size'][$key];
              $this->uploaded_array[] = $target_file;
              $this->uploadPostProcessor($target_file);
            }
          }
          else 
          {
            if (unlink($target_file))
            {
              if (move_uploaded_file($_FILES[$id]["tmp_name"][$key], $target_file))
              {
                $num_uploaded++;
                $this->bytes_uploaded += $_FILES[$id]['size'][$key];
                $this->uploaded_array[] = $target_file;
                $this->uploadPostProcessor($target_file);
              }
            }
            else 
            {
              $app->errorMessage('Unable to delete ' . $target_file . ' before updating it.');
            }
          }
        }
        else 
        {
          if ($_FILES[$id]['size'][$key] > $this->max_size)
          {
            $app->errorMessage('File ' . $name . ' is larger than the maximum allowed size of ' . $this->max_size . ' bytes.');
          }
          else 
          {
            $app->errorMessage('Unknown error uploading ' . $name . '.');
            $app->errorMessage('Maximum filesize = ' . algaeFile::getHumanFilesize($this->max_size) . '.');
          }
        }
      }
      if ($showMessages)
      {
        echo algaeCore::getFormattedNumber($this->bytes_uploaded, 0), ' bytes uploaded in ',
          count($this->uploaded_array), ' file(s).<p />';
      }
    }
    else
    {
      $app->errorMessage('No files found to upload.');
    }
    return $num_uploaded;
  }
  
  /**
   * Show the contents of a text file.
   * @param string $filename The name of the file to show.
   * @param string $header_message An optional header message to show before the file contents.
   */
  public static function showTextFile($filename, $header_message = '')
  // --------------------------------------------------------------------------
  {
    global $app;
    if (file_exists($filename))
    {
      $handle = @fopen($filename, "r");
      if ($handle)
      {
        if (strlen($header_message) > 0)
        {
          echo '<h2>', $header_message, '</h2><p />';
        }
        echo '<pre><code>';
        while (($buffer = fgets($handle)) !== false)
        {
          echo algaeCore::toHtml($buffer);
          // echo $buffer, '<p />';
        }
        if (!feof($handle))
        {
          $app->errorMessage('Unexpected fgets() fail');
        }
        fclose($handle);
        echo '</code></pre>';
      }
    }
    else
    {
      $app->errorMessage('Could not open file ' . $filename);
    }
  } 
  
  /**
   * Get the wildcard specification for all files associated with a filename.
   * @param string $filename Filename, for example: /ebs1/gcco/shp/test.shp
   * @return string Wildcard spec, for example: /ebs1/gcco/shp/test*
   */
  public static function getWildcardSpec($filename)
  // --------------------------------------------------------------------------
  {
    return pathinfo($filename, PATHINFO_DIRNAME) . '/' . pathinfo($filename, PATHINFO_FILENAME) . '*';
  }
  
  /**
   * Normalize a string into a nice filename.
   * @param string $filename The string to normalize.
   * @return mixed Normalized filename string.
   */
  public static function normalizeFilename($filename = '')
  // --------------------------------------------------------------------------
  {
    $str = preg_replace("[^\w\s\d\.\-_~,;:\[\]\(\]]", '', $filename);
    $str = strtolower($str);
    $str = str_replace(',', '', $str);
    $str = str_replace(' ', '_', $str);
    $str = str_replace('-', '', $str);
    $str = str_replace('(', '', $str);
    $str = str_replace(')', '', $str);
    $str = str_replace('__', '_', $str);
    return $str;
  }
  
  /**
   * Get a date_time prefix like 20210130_185633.  Format string is "Ymd_Hms".
   * @return string
   */
  public static function getDateTimePrefix()
  // --------------------------------------------------------------------------
  {
    return date("Ymd_Hms");
  }
  
  /**
   * Get a temporary filename prefix with a date-time stamp fully pathed if $includePath is True (the default).
   * @param boolean $includePath Include the path from $app->settings->temporaryDownloadsFolder.
   * @param string $std_prefix A standard prefix to use instead of the default date-time, for example used to set
   * multiple filenames with the same prefix.
   * @return string The prefix, for example: /Library/WebServer/Documents/downloads/20200302_021822
   */
  public static function getTemporaryFilenamePrefix($includePath = True, $std_prefix = null)
  // --------------------------------------------------------------------------
  {
    global $app;
    $prefix = algaeFile::getDateTimePrefix();
    if ($std_prefix != null) $prefix = $std_prefix;
    if ($includePath)
    {
      $prefix = $app->settings->temporaryDownloadsFolder . $prefix;
    }
    return $prefix;
  }
  
  /**
   * Make a folder if it doesn't already exist.
   * @param string $folder Folder to make.
   * @param boolean $showErrorMessage True (default) so show an error message if it doesn't work.
   * @return boolean True on success, False on fail.
   */
  public static function makeFolder($folder, $showErrorMessage = True)
  // --------------------------------------------------------------------------
  {
    global $app;
    if (! file_exists($folder))
    {
      mkdir($folder);
    }
    if (file_exists($folder))
    {
      return True;
    }
    else
    {
      if ($showErrorMessage) $app->errorMessage('Unable to create directory ' . $folder . '.');
    }
    return False;
  }
  
  public function getActions($filename)
  // --------------------------------------------------------------------------
  {
    return '';
  }
  
  protected function showPreview($filename)
  // --------------------------------------------------------------------------
  {
    algaeTable::writeData('');
  }
  
  /**
   * Report files in a directory.
   * @param $directory string Directory to report files for.$this
   * @param $showPreview boolean Show previews or not, default is False.
   * @param $extensionsToHide array Array of case sensitive extensions not to show.  Do no include the dot prefix.
   */
  public function reportFiles($directory, $showPreview = False, $extensionsToHide = array())
  // --------------------------------------------------------------------------
  {
    $files = scandir($directory);
    // echo sizeof($files) - 2, ' file(s).<p />';
    //
    // ----- initial the table
    //
    $tableId = 'filesTable';
    algaeTable::initTablesorterJavascript($tableId, '[[0,0]]');
    algaeTable::start($tableId, 'tablesorter', 'width:80%;');
    //
    // ----- table header
    //
    $header_array = array();
    if ($showPreview)
    {
      $header_array[] = array('Preview', '15%');
    }
    $header_array[] = array('Filename', '35%');
    $header_array[] = array('Size (bytes)', '25%');
    $header_array[] = array('Actions', '25%');
    algaeTable::writeHeader($header_array, True);
    //
    // ----- loop through the results
    //
    foreach ($files as $file)
    {
      if ( ($file != '.') && ($file != '..') && (! is_dir($directory . '/' . $file)) )
      {
        $full_filename = $directory . '/' . $file;
        $ext = pathinfo($full_filename, PATHINFO_EXTENSION);
        if (!in_array($ext, $extensionsToHide))
        {
          echo '<tr>';
          if ($showPreview) $this->showPreview($full_filename);
          algaeTable::writeData($file);
          algaeTable::writeData(algaeCore::getFormattedNumber(filesize($full_filename), 0));
          algaeTable::writeData($this->getActions($file), False);
          echo '</tr>';
        }
      }
    }
    algaeTable::end();
  }
  
  /**
   * Rename a file.  Uses the PHP rename() function.
   * @param string $old_name Full path and name of file to rename.
   * @param string $new_name Full path and new name for file.
   * @param boolean $messaging True (default) to include status and success messaging.  Error messages
   * are reported no matter what.
   * @return boolean True on success, False on fail.
   */
  public static function rename($old_name, $new_name, $messaging = True)
  // --------------------------------------------------------------------------
  {
    global $app;
    if (file_exists($old_name))
    {
      if ($messaging) { echo 'Renaming ', $old_name, ' to ', $new_name, '<p />'; }
      if (rename($old_name, $new_name))
      {
        if ($messaging) { $app->successMessage('Success'); }
        return True;
      }
      else
      {
        $app->errorMessage('Unable to rename ' . $old_name . ' to ' . $new_name . '.');
      }
    }
    else
    {
      $app->errorMessage($old_name . ' does not exist.');
    }
    return False;
  }
  
}



