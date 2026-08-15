<?php

/**
 * 
  algae framework | Tables support class.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/slate
  
*/

class algaeTable
{
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
  }
  
  /**
   * Initial a tablesorter table.
   * @param string $tableId The table id, for example "dataTable" do not include the # on front.
   * @param string $sortOrder The default sort order, for example '[[0,0]]' to sort on the first column ascending.
   * @param boolean $addJavaScriptBrackets True (default) to add enclosing JavaScript brackets.
   * @param string $headers Optional headers setup, for example to sort by a military
   * formatted date column enter "headers: {0: {sorter:'milDate'} }" if the date is in column 0.  Use the custom parser named
   * thousands for sorting dollar amounts with a dollar sign or numbers with thousands separators.
   * @param string $widgets Optional widgets, default is "['zebra', 'filter', 'stickyHeaders']".
   * @example algaeTable::initTablesorterJavascript($tableId, '[[0,0]]', True, "headers: { 2:{sorter:false, filter:false}, 3:{sorter:false, filter:false}, 4:{filter:false}, 5:{sorter:false, filter:false}, 6:{sorter:false, filter:false} }");
   */
  public static function initTablesorterJavascript($tableId, $sortOrder = '[[0,0]]', $addJavaScriptBrackets = True,
    $headers = '', $widgets = "['zebra', 'filter', 'stickyHeaders']")
    // --------------------------------------------------------------------------
  {
    global $app;
    $code = '';
    if ($addJavaScriptBrackets) $code .= '<script type="text/javascript">';
    $code .= "$(document).ready(function() {";
    $code .= 'addMilDateParser();';
    $code .= 'addThousandsParser();';
    $code .= "$(\"#$tableId\").tablesorter(
  	{theme: '" . $app->settings->tablesorterTheme ."', sortList: $sortOrder, widgets: $widgets,
  	widgetOptions: {filter_hideFilters : false}";
    if (strlen($headers) > 0)
    {
      $code .= ',' . $headers;
    }
    $code .= '})';
    $code .= ".find('.colStats')
    .mouseup(function(e){
  	    colStatsDialog.showDialog(this);
        return false;
    });";
    # $code .= ';';
    $code .= '});';
    if ($addJavaScriptBrackets) $code .= '</script>';
    echo $code;
  }
  
  /**
   * Start a table.
   * @param string $id The table id.
   * @param string $class Class or classes (seprated by spaces) for the table.  Provide an empty class to skip.
   * @param string $style Optional style parameter, typically used to set a table width like width:50%;.
   */
  public static function start($id, $class = 'tablesorter', $style = 'width:100%;')
  // --------------------------------------------------------------------------
  {
    echo '<table id="', $id, '"';
    if (strlen($class) > 0)
    {
      echo ' class="', $class, '"';
    }
    if (strlen($style) > 0)
    {
      echo ' style="', $style, '"';
    }
    echo '>';
  }
  
  /**
   * End a table.
   */
  public static function end()
  // --------------------------------------------------------------------------
  {
    echo '</tbody></table>';
  }
  
  /**
   * Get the HTML to include a clickable stats icon in a table header.  This is typically called
   * automatically from algaeTable::writeHeader(), but may be needed when writeHeader() cannot
   * be called directly.
   * @return string The HTML for the icon.
   */
  public static function getHeaderStatsHTML()
  // --------------------------------------------------------------------------
  {
    return '<span class="colStats"><img src="/algae/img/algae_bar_graph_icon.png" height="16" style="margin-top:1px;" valign="top"/>&nbsp;&nbsp;</span>';
  }
  
  /**
   * Write a simple html table header section.
   * @param array $arrayHeaders Array of headers to write.  The array can by a string, optionally including a width
   * and True/False to include the icon for summary stats.
   * @param boolean $bIncludeStatsOption True to include an icon in the header that can be clicked to get stats for the column.
   * Default is True.
   */
  public static function writeHeader($arrayHeaders, $bIncludeStatsOption = True, $graphTooltipColumn = 1)
  // --------------------------------------------------------------------------
  {
    global $app;
    $statsHTML = '';
    if ($bIncludeStatsOption) $statsHTML = algaeTable::getHeaderStatsHTML();
    if (count($arrayHeaders) > 0)
    {
      echo '<thead><tr>';
      foreach ($arrayHeaders AS $header)
      {
        if ( (is_array($header)) && (count($header) == 3) )
        {
          if ($header[2] == True)
          {
            echo '<th width="', $header[1], '">', $statsHTML, algaeCore::toHtml($header[0]), '</th>';
          }
          else
          {
            echo '<th width="', $header[1], '">', algaeCore::toHtml($header[0]), '</th>';
          }
        }
        else
        {
          if ( (is_array($header)) && (count($header) == 2) )
          {
            echo '<th width="', $header[1], '">', $statsHTML, algaeCore::toHtml($header[0]), '</th>';
          }
          else
          {
            //
            // ----- just the header
            //
            echo '<th>', $statsHTML, algaeCore::toHtml($header), '</th>';
          }
        }
      }
      echo '</tr></thead>';
    }
    echo '<tbody>';
    //
    // ----- create the dialog for the column stats
    //
    if ( ($bIncludeStatsOption) && (! $app->colStatsDialogAdded) )
    {
      algaeTable::addColumnStatsDialog($graphTooltipColumn);
      $app->colStatsDialogAdded = True;
    }
  }
  
  public static function writeHeaderWithAssociativeArray($arrayHeaders, $graphTooltipColumn = 1)
  // --------------------------------------------------------------------------
  {
    global $app;
    $total_width = 0;
    $statsHTML = algaeTable::getHeaderStatsHTML();
    if (count($arrayHeaders) > 0)
    {
      echo '<thead><tr>';
      //
      // ----- loop through each header
      //
      foreach ($arrayHeaders AS $key=>$header)
      {
        //
        // ----- name
        //
        $name = 'Col ' . strval($key);
        if (array_key_exists('name', $header)) 
        {
          $name = $header['name'];
        }
        //
        // ----- width
        //
        $width = '';
        if (array_key_exists('width', $header))
        {
          $width = 'width="' . strval($header['width']) . '"';
          $total_width += str_replace('%', '', $header['width']);
        }
        //
        // ----- filter
        //
        $filter = '';
        if ( (array_key_exists('filter', $header)) && (strlen($header['filter']) > 0) )
        {
          $filter = 'data-value="' . strval($header['filter']) . '"';
        }
        //
        // ----- sorter
        //
        $sorter = '';
        if ( (array_key_exists('sorter', $header)) && (strlen($header['sorter']) > 0) )
        {
          $sorter = 'data-sorter="' . strval($header['sorter']) . '"';
        }
        //
        // ----- stats, show icon for stats dialog
        //
        $statsHTML = algaeTable::getHeaderStatsHTML();
        if (array_key_exists('stats', $header)) 
        {
          if (! $header['stats']) $statsHTML = '';
        }
        //
        // ----- write final header html
        //
        echo '<th ', $width, ' ', $filter, ' ', $sorter, '>', $statsHTML, algaeCore::toHtml($name), '</th>';
      }
      echo '</tr></thead>';
    }
    echo '<tbody>';
    if ($total_width > 100)
    {
      // $app->errorMessage('Total width for table columns is ' . strval($total_width) . '.');
    }
    //
    // ----- create the dialog for the column stats, do this no matter what if not already added
    //
    if (! $app->colStatsDialogAdded)
    {
      algaeTable::addColumnStatsDialog($graphTooltipColumn);
      $app->colStatsDialogAdded = True;
    }
  }
  
  /**
   * Write a single data value to a table enclosed by <td> tags.
   * @param string $str The data value to write.
   * @param string $toHtml True (the default) to convert the strings to html before writing to the page.
   * @param string $blankSubstitute String to substitude for blank data, empty string by default.
   * @param string $title Title for the cell, blank no title by default.
   * @param string $class Class for the cell, no class by default.
   * @param string $sortValue Optional value to write as a 'data-text' attribute with the cell.  If the attribute is
   * there tablesorter will use it for sorting.
   */
  public static function writeData($str, $toHtml = True, $blankSubstitute = '', $title = '', $class = '', $sortValue = null)
  // --------------------------------------------------------------------------
  {
    echo '<td';
    if (strlen($title) > 0)
    {
      echo ' title = "', algaeCore::toHtml($title), '"';
    }
    if (strlen($class) > 0)
    {
      echo ' class = "', $class, '"';
    }
    if ($sortValue != null)
    {
      echo ' data-text = "', $sortValue, '"';
    }
    echo '>';
    if (strlen($str) > 0)
    {
      if ($toHtml)
      {
        echo algaeCore::toHtml($str);
      }
      else
      {
        echo $str;
      }
    }
    else
    {
      echo $blankSubstitute;
    }
    echo '</td>';
  }
  
  /**
   * Write a row with two columns to a table.
   * @param string $label The label for the first column, will be made bold by convention.
   * @param string $str The data value to write.
   * @param string $toHtml True (the default) to convert the strings to html before writing to the page.
   */
  public static function writeTwoColumns($label, $str, $toHtml = True, $class = '')
  // --------------------------------------------------------------------------
  {
    echo '<tr';
    if (strlen($class) > 0)
    {
      // echo ' class = "', $class, '"';
    }
    echo '>';
    algaeTable::writeData($label, $toHtml);
    algaeTable::writeData($str, $toHtml, '', '', $class);
    echo '</tr>';
  } 
  
  /**
   * Write two columns to a table with the data string or a dash if the string is empty.
   * @param string $label The label for the first column.
   * @param string $datastr The data string.
   * @param boolean $toHtml True (the default) to convert the strings to html before writing to the page.
   * @param string $class Optional class to style the data.
   */
  public static function writeTwoColumnsWithDataOrDash($label, $datastr, $toHtml = True, $class = '')
  // --------------------------------------------------------------------------
  {
    if (strlen($datastr) > 0)
    {
      algaeTable::writeTwoColumns($label, $datastr, $toHtml, $class);
    }
    else
    {
      algaeTable::writeTwoColumns($label, '-', $toHtml, $class);
    }
  }
  
  /**
   * Add HTML to create a jQuery dialog for table column statistics.
   * @param number $graphTooltipColumn 
   */
  public static function addColumnStatsDialog($graphTooltipColumn = 1)
  // --------------------------------------------------------------------------
  {
    ?>
    <div id="colStatsDialog" title="Column Statistics">
      <div id="colStatsDialogTabs" class="tabs">
        <ul>
          <li><a href="#colStatsStats" class='colStatsTab'>Stats</a></li>
          <li><a href="#colStatsGraphTab" class='colStatsTab'>Graph</a></li>
          <li><a href="#colStatsCSVTab" class='colStatsTab'>CSV</a></li>
        </ul>
        <div id='colStatsStats'>
        <div id='colStatsStatsData'>
        </div>
        </div>
        <div id='colStatsGraphTab'>
            <div id='colStatsGraph'></div>
            <div style="margin-top:10px;">
              <select id="colStatsGraphType">
                <option value="bar">Bar</option>
                <option value="histogram">Histogram</option>
                <option value="line" selected="selected">Line</option>  <!-- change this in algaeColStats.js top default also -->
              </select>
              &nbsp;&nbsp;
              <select id="colStatsGraphScale">
                <option value="arithmetic" selected="selected">Arithmetic</option>
                <option value="cumulative">Cumulative</option>
                <option value="log">Log</option>
              </select>
            </div>
        </div>
        <div id='colStatsCSVTab' class='colStatsCSVTab'></div>
      </div>
    </div>
    <?php
    echo '<script>colStatsDialog.graphTooltipColumn = ', $graphTooltipColumn, ';</script>';
    algaeCore::addJavaScript('$( function() { $( "#colStatsGraphType" ).selectmenu({width: 150}); } );');
    algaeCore::addJavaScript('$( function() { $( "#colStatsGraphScale" ).selectmenu({width: 150}); } );');
    //
    // ----- for the schema
    //
    echo '<div id="schemaDialog" title="Schema"></div>';
  }
  
  /**
   * Open a csv file and add a download link.
   * @param string $filename Name of file to open.
   * @param boolean $addDownloadLink True (default) to add the download link.
   */
  public static function openCSVFile($filename, $addDownloadLink = true, $downloadLinkId = 'download_link')
  // --------------------------------------------------------------------------
  {
    global $app;
    $csv = fopen($filename, 'w');
    if (! $csv)
    {
      $app->errorMessage('Problem opening ' . $filename);
    }
    else
    {
      if ($addDownloadLink)
      {
        echo '<span id="', $downloadLinkId, '" class="align_right"></span>';
        echo '<br />';
      }
    }
    return $csv;
  }
  
  public static function closeCSVFile($csv, $filename, $downloadLinkId = 'download_link')
  // --------------------------------------------------------------------------
  {
    if ($csv)
    {
      fclose($csv);
      $link = algaeFile::getDownloadLink($filename, 'Download Data', True);
      algaeCore::addJavaScript("$(\"#" . $downloadLinkId . "\").html('" . $link . "');");
    }
  }
  
  /**
   * Write the header to a csv file.
   * @param object $csv Open file output stream.
   * @param array $header_array Array of headers.
   * @param array $prefix_header_array Optional array of prefix headers.
   */
  public static function writeHeaderToCSVFile($csv, $header_array, $prefix_header_array = null)
  // --------------------------------------------------------------------------
  {
    if ($csv)
    {
      $csv_header = array();
      if ($prefix_header_array != null) $csv_header = $prefix_header_array;
      foreach ($header_array as $header)
      {
        $csv_header[] = $header[0];
      }
      fputcsv($csv, $csv_header, ',', '"');
    }
  }
  
  /**
   * Write a divider row to a table.
   * @param integer $ncols Number of columns to span.
   */
  public static function writeDividerRow($ncols)
  // --------------------------------------------------------------------------
  {
    echo '<tr>';
    echo '<td colspan="', $ncols, '"></td>';
    echo '</tr>';
  }
  
}




