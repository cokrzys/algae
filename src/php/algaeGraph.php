<?php

/**

  algae framework | Graphs base class.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeGraph
{
  
  /**
   * Must be the same as algaeGraph.enumAutoManual
   */
  CONST GRAPH_AUTOMATIC = 0;
  CONST GRAPH_MANUAL = 1;
  
  /**
   * Must be the same as algaeGraph.legendPositions
   * @var unknown
   */
  CONST LEGEND_UPPER_LEFT = 1;
  CONST LEGEND_UPPER_RIGHT = 2;
  CONST LEGEND_LOWER_RIGHT = 3;
  CONST LEGEND_LOWER_LEFT = 4;
  CONST LEGEND_NONE = 5;
  
  /**
   * Must match JavaScript algaeGraph.enumSymbol.
   */
  CONST GRAPH_SYMBOL_NONE = 0;
  CONST GRAPH_SYMBOL_RECTANGLE = 1;
  CONST GRAPH_SYMBOL_LINE = 2;
  CONST GRAPH_SYMBOL_CIRCLE = 3;
  CONST GRAPH_SYMBOL_CROSS = 4;
  CONST GRAPH_SYMBOL_DIAMOND = 5;
  CONST GRAPH_SYMBOL_SQUARE = 6;
  CONST GRAPH_SYMBOL_TRIANGLE = 7;
  CONST GRAPH_SYMBOL_STAR = 8;
  CONST GRAPH_SYMBOL_WYE = 9;
  CONST GRAPH_SYMBOL_TRIANGLE_DOWN = 10;
  
  /**
   * Must match JavaScript algaeGraph.enumNoOverlapStyle.
   */
  CONST GRAPH_NO_OVERLAP_ELLIPSE = 0;
  CONST GRAPH_NO_OVERLAP_UP_ONLY = 1;
  CONST GRAPH_NO_OVERLAP_DOWN_ONLY = 2;
  CONST GRAPH_NO_OVERLAP_UP_AND_DOWN = 3;
  CONST GRAPH_NO_OVERLAP_LEFT_ONLY = 4;
  CONST GRAPH_NO_OVERLAP_RIGHT_ONLY = 5;
  CONST GRAPH_NO_OVERLAP_LEFT_AND_RIGHT = 6;
  
  /**
   * Must match JavaScript algaeGraph.enumLineStyle.
   */
  CONST GRAPH_LINE_SOLID = 0;
  CONST GRAPH_LINE_DASHED = 1;
  CONST GRAPH_LINE_DOTTED = 2;
  
  CONST COLOR_DEFAULT = 0;
  CONST COLOR_VARIABLE = 1;
  CONST COLOR_GROUP = 2;
  
  CONST GRAPH_COORDINATE_UNITS_SCREEN = 0;
  CONST GRAPH_COORDINATE_UNITS_DATA = 1;
    
  CONST GRAPH_DATA_TYPE_NUMERIC = 0;
  CONST GRAPH_DATA_TYPE_CURRENCY = 1;
  CONST GRAPH_DATA_TYPE_TEXT = 2;
  CONST GRAPH_DATA_TYPE_DATE = 3;
    
  public $divId;
  public $divShiftY;
  public $javaScriptVar;
  public $javaScriptClass;
  public $seriesName;
  public $mainTitle;
  public $subTitle;
  public $width;
  public $height;
  public $xAxis;
  public $yAxis;
  public $tooltipPos;
  public $marginTop;
  public $marginLeft;
  public $marginRight;
  public $marginBottom;
  public $sql;
  public $sql_parms;
  public $data;
  public $legendData;
  public $legendPosition;
  public $legendInMargin;
  public $click_forward;
  public $click_url_prefix;
  public $click_url_suffix_var_num;
  public $defaultColor;
  public $noOverlapStyle;
  public $graphAndDataTabs;
  public $showMouseCoordinateX;
  public $showMouseCoordinateY;
  public $mouseCoordinatesPosition;
  public $mouseCoordinateUnits;
  public $showFootprint;
  public $debug;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    $this->divId = 'algaeGraphDiv';
    $this->divShiftY = null;
    $this->javaScriptVar = 'graph';
    $this->javaScriptClass = null;
    $this->seriesName = 'Series 1';
    $this->width = 900;
    $this->height = 500;
    $this->mainTitle = 'Main Title';
    $this->subTitle = '';
    $this->showTitle = True;
    $this->tooltipPos = 2;
    $this->xAxis = new algaeGraphAxis(algaeGraphAxis::X_DIRECTION);
    $this->yAxis = new algaeGraphAxis(algaeGraphAxis::Y_DIRECTION);
    $this->marginTop = 35;
    $this->marginBottom = 55;
    $this->marginLeft = 60;
    $this->marginRight = 20;
    $this->sql = '';
    $this->sql_parms = array();
    $this->data = array();
    $this->legendData = array();
    $this->legendPosition = algaeGraph::LEGEND_UPPER_RIGHT;
    $this->legendInMargin = False;
    $this->click_forward = False;
    $this->click_url_prefix = null;
    $this->click_url_suffix_var_num = 3;
    $this->defaultColor = '#FF0000';
    $this->noOverlapStyle = algaeGraph::GRAPH_NO_OVERLAP_UP_AND_DOWN;
    $this->graphAndDataTabs = array(
      array('#graph_tab', 'Graph'),
      array('#data_tab', 'Data'),
      array('#sql_tab', 'SQL')
    );
    $this->showMouseCoordinateX = False;
    $this->showMouseCoordinateY = False;
    $this->mouseCoordinatesPosition = algaeGraph::LEGEND_UPPER_LEFT;
    $this->mouseCoordinateUnits = algaeGraph::GRAPH_COORDINATE_UNITS_DATA;
    $this->showFootprint = False;
    $this->debug = False;
  }
  
  /**
   * Method not implemented error handler.
   * @param string $method Method name.
   */
  protected function errorNotImplemented($method)
  // --------------------------------------------------------------------------
  {
    global $app;
    $app->errorMessage($method . '() not implemented in ' . get_class($this) . '.');
  }
  
  /**
   * Load the data, often overridden in the derived class.
   */
  protected function loadData()
  // --------------------------------------------------------------------------
  { 
    global  $app;
    $this->data = array();
    if ($this->sql != null)
    {
      $this->data = algaeDB::getArray($this->sql, $this->sql_parms);
      if (count($this->data) > 0)
      {
        //
        // ----- send main data array to JavaScript
        //
        algaeCore::addJavaScript($this->javaScriptVar . ".addData(" . json_encode($this->data, JSON_NUMERIC_CHECK) . ", '" . $this->seriesName . "','" . $this->defaultColor . "');");
        return True;
      }
    }
    else
    {
      $app->errorMessage('Data SQL not defined in ' . get_class($this) . '.');
    }
    return False;
  }
  
  /**
   * Send graph settings to JavaScript.
   */
  protected function sendSettingsToJavaScript()
  // --------------------------------------------------------------------------
  {
    $varPrefix = $this->javaScriptVar . '.';
    echo '<script type="text/javascript">'; 
    algaeCore::addJavaScriptStringVar($varPrefix . 'divId', $this->divId);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'height', $this->height);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'width', $this->width);
    algaeCore::addJavaScriptStringVar($varPrefix . 'mainTitle', $this->mainTitle);
    algaeCore::addJavaScriptStringVar($varPrefix . 'subTitle', $this->subTitle);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'showTitle', $this->showTitle);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'tooltipPos', $this->tooltipPos);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'margin.top', $this->marginTop);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'margin.bottom', $this->marginBottom);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'margin.left', $this->marginLeft);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'margin.right', $this->marginRight);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'legendPosition', $this->legendPosition);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'legendInMargin', $this->legendInMargin);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'clickForward', $this->click_forward);
    algaeCore::addJavaScriptStringVar($varPrefix . 'clickURLPrefix', $this->click_url_prefix);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'clickURLSuffixVarNum', $this->click_url_suffix_var_num);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'noOverlapStyle', $this->noOverlapStyle);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'showMouseCoordinateX', $this->showMouseCoordinateX);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'showMouseCoordinateY', $this->showMouseCoordinateY);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'mouseCoordinatesPosition', $this->mouseCoordinatesPosition);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'mouseCoordinateUnits', $this->mouseCoordinateUnits);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'showFootprint', $this->showFootprint);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'debug', $this->debug);
    echo '</script>';
    //
    // ----- axis settings
    //
    $this->xAxis->sendSettingsToJavaScript($this->javaScriptVar);
    $this->yAxis->sendSettingsToJavaScript($this->javaScriptVar);
    //
    // ----- custom legend data
    //
    algaeCore::addJavaScript($this->javaScriptVar . ".legendData = " . json_encode($this->legendData, JSON_NUMERIC_CHECK) . ";");
  }
  
  /**
   * 
   */
  public function createJavaScriptVariable()
  // --------------------------------------------------------------------------
  {
    algaeCore::addJavaScript('if (' . $this->javaScriptVar . ' === undefined) {var ' . $this->javaScriptVar . ' = new ' . $this->javaScriptClass . '();}');
  }
  
  /**
   * Draw the graph.  This does everything from loading the data to creating the
   * JavaScript objects.
   */
  public function draw()
  // --------------------------------------------------------------------------
  {
    global $app;
    if ($this->javaScriptClass != null)
    {
      echo '<div id="', $this->divId, '"';
      if ($this->divShiftY != null)
      {
        echo ' style="margin-top:', $this->divShiftY, ';"';
      }
      echo '></div>';
            
      $this->createJavaScriptVariable();
      // algaeCore::addJavaScript('var ' . $this->javaScriptVar . ' = new ' . $this->javaScriptClass . '();');
      //
      // ----- loadData() loads the data and sends it to JavaScript
      //
      if ($this->loadData())
      {
        $this->sendSettingsToJavaScript();
        algaeCore::addJavaScript($this->javaScriptVar . '.draw();');
      }
    }
    else 
    {
      $app->errorMessage('javaScriptClass not defined in ' . get_class($this) . '.');
    }
  }
  
  /**
   * Show graph data in a table.
   */
  protected function reportData()
  // --------------------------------------------------------------------------
  {
    echo 'Not defined, override reportData() in the derived class.<p />';
    echo 'See algaeGraph::reportData() for an example to build from.<p />';
    return;
    //
    // ----- initial the table
    //
    $tableId = $this->javaScriptClass . 'Table';
    algaeTable::initTablesorterJavascript($tableId, '[[0,0]]', True, "headers: {0: {sorter:'milDate'} }");
    algaeTable::start($tableId, 'tablesorter', 'width:40%;');
    //
    // ----- table header
    //
    $header_array = array(
      array('Date', '25%'),
      array('Num Actions', '25%')
    );
    algaeTable::writeHeader($header_array, True);
    //
    // ----- loop through the results
    //
    foreach ($this->data as $row)
    {
      echo '<tr>';
      algaeTable::writeData($row[0]);
      algaeTable::writeData($row[1]);
      echo '</tr>';
    }
    algaeTable::end();
  }
  
  /**
   * Show options form typically above the graph tabs.
   */
  protected function showForm()
  // --------------------------------------------------------------------------
  {
  }
  
  /**
   * Show graph, data, and SQL tabs.
   */
  public function showGraphAndData()
  // --------------------------------------------------------------------------
  {
    $this->showForm();
    algaeForm::startTabs($this->graphAndDataTabs);
    //
    // ----- graph_tab
    //
    echo '<div id="graph_tab">';
    $this->draw();
    echo '</div>';
    //
    // ----- data_tab
    //
    echo '<div id="data_tab">';
    $this->reportData();
    echo '</div>';
    //
    // ----- sql_tab
    //
    echo '<div id="sql_tab">';
    echo SqlFormatter::format(algaeDB::getSQLFromQueryAndParams($this->sql, $this->sql_parms));
    echo '</div>';
    algaeForm::endTabs();
  }
  
}





