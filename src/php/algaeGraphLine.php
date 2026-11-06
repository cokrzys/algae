<?php

/**

  algae framework | Line graph.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeGraphLine extends algaeGraph
{
  
  public $showLineTooltip;
  public $showSymbols;
  public $defaultSymbolSize;
  public $labelLines;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    parent::__construct();
    $this->javaScriptClass = 'algaeLineGraph';
    $this->showLineTooltip = True;
    $this->showSymbols = True;
    $this->defaultSymbolSize = 3;
    $this->labelLines = False;
  }
  
  /**
   * Send graph settings to JavaScript.
   */
  protected function sendSettingsToJavaScript()
  // --------------------------------------------------------------------------
  {
    parent::sendSettingsToJavaScript();
    $varPrefix = $this->javaScriptVar . '.';
    echo '<script type="text/javascript">';
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'showLineTooltip', $this->showLineTooltip);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'showSymbols', $this->showSymbols);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'defaultSymbolSize', $this->defaultSymbolSize);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'labelLines', $this->labelLines);
    echo '</script>';
  }
  
  protected function addRandomData($xmin, $xmax, $ymin, $ymax, $title = 'Random Data', $color = 'steelblue')
  // --------------------------------------------------------------------------
  {
    $data = array();
    for ($x = $xmin; $x <= $xmax; $x++)
    {
      $label = 'Pt ' . $x;
      $y = rand($ymin, $ymax);
      $data[] = array($x, $y, $label);
    }
    algaeCore::addJavaScript($this->javaScriptVar . ".addData(" . json_encode($data) . ", '$title', '$color');");
  }
  
  protected function addDataFromVariables()
  // --------------------------------------------------------------------------
  {
    algaeCore::addJavaScript("var globalDataSeries1 = [[0,10,'Pt1'], [10,20,'Pt2'], [20,15,'Pt3']];");
    algaeCore::addJavaScript($this->javaScriptVar . ".addData(globalDataSeries1, 'Series 1', 'steelblue');");
    algaeCore::addJavaScript("var globalDataSeries2 = [[0,20,'Pt1'], [10,30,'Pt2'], [20,25,'Pt3']];");
    algaeCore::addJavaScript($this->javaScriptVar . ".addData(globalDataSeries2, 'Series 2', 'darkgreen');");
  }
  
  protected function loadData()
  // --------------------------------------------------------------------------
  {
    return parent::loadData();
    //
    // ----- earlier testing work
    //
    // $this->addRandomData(0, 100, 0, 25, 'Random Data 1', 'steelblue');
    // $this->addRandomData(0, 100, 26, 50, 'Random Data 2', 'darkgreen');
    // $this->addDataFromVariables();
    // return True;
  }
  
}
