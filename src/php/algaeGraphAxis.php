<?php

/**

  algae framework | Graph axis base class.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeGraphAxis
{
  
  /**
   * Must be the same as algaeGraphAxis.enumDirection
   */
  CONST X_DIRECTION = 0;
  CONST Y_DIRECTION = 1;
  
  /**
   * Must be the same as algaeGraphAxis.enumType
   */
  CONST AXIS_TYPE_ARITHMETIC = 0;
  CONST AXIS_TYPE_LOG = 1;
  CONST AXIS_TYPE_TIME = 2;
  
  public $title;
  public $direction;
  public $type;
  public $scaleSetup;
  public $manualMin;
  public $manualMax;
  public $tickFormat;
  public $numTicks;
  public $numDecimals;
  public $dataType;
  public $varNum;
  public $antiLogTickLabels;
  public $timeTickLabels;
  public $timeTickLabelsSpacingSec;
  public $offset;
  public $show;
  
  /**
   * Constructor.
   */
  public function __construct($direction)
  // --------------------------------------------------------------------------
  {
    $this->direction = $direction;
    $this->type = algaeGraphAxis::AXIS_TYPE_ARITHMETIC;
    $this->scaleSetup = algaeGraph::GRAPH_AUTOMATIC;
    $this->manualMin = 0;
    $this->manualMax = 100;
    $this->title = '';
    $this->tickFormat = '';
    $this->numTicks = 0;
    $this->numDecimals = 2;
    $this->dataType = algaeGraph::GRAPH_DATA_TYPE_NUMERIC;
    $this->timeTickLabels = False;
    $this->timeTickLabelsSpacingSec = 3600;
    $this->antiLogTickLabels = False;
    $this->offset = 15;
    $this->show = True;
    if ($this->direction == algaeGraphAxis::X_DIRECTION) 
    {
      $this->varNum = 0;
      $this->title .= 'X ';
    }
    if ($this->direction == algaeGraphAxis::Y_DIRECTION) 
    {
      $this->varNum = 1;
      $this->title .= 'Y ';
    }
    $this->title .= 'Axis Title';
  }
  
  /**
   * Initial the manual min/max values when using a date/time axis.
   * Note that 01-Jan-1970 is the date used to get "seconds past" in strtotime().
   * See also updateManualMin() and updateManualMax().
   */
  public function initTimeAxisManualMinMax()
  // --------------------------------------------------------------------------
  {
    $this->manualMin = '31-Dec-2100';
    $this->manualMax = '01-Jan-1970';
  }
  
  protected function getJavaScriptVar()
  // --------------------------------------------------------------------------
  {
    if ($this->direction == algaeGraphAxis::X_DIRECTION) return 'xAxis'; 
    if ($this->direction == algaeGraphAxis::Y_DIRECTION) return 'yAxis'; 
    return '';
  }
  
  /**
   * Send axis settings to JavaScript.
   */
  public function sendSettingsToJavaScript($javaScriptGraphVar = 'graph')
  // --------------------------------------------------------------------------
  {
    $varPrefix = $javaScriptGraphVar . '.' . $this->getJavaScriptVar() . '.';
    echo '<script type="text/javascript">';
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'type', $this->type);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'scaleSetup', $this->scaleSetup);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'varNum', $this->varNum);
    algaeCore::addJavaScriptStringVar($varPrefix . 'title', $this->title);
    algaeCore::addJavaScriptStringVar($varPrefix . 'tickFormat', $this->tickFormat);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'numTicks', $this->numTicks);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'numDecimals', $this->numDecimals);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'dataType', $this->dataType);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'antiLogTickLabels', $this->antiLogTickLabels);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'timeTickLabels', $this->timeTickLabels);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'timeTickLabelsSpacingSec', $this->timeTickLabelsSpacingSec);
    algaeCore::addJavaScriptNumericVar($varPrefix . 'offset', $this->offset);
    algaeCore::addJavaScriptBooleanVar($varPrefix . 'show', $this->show);
    //
    // ----- add manual min based on data type, i.e. date is a string
    //
    if (is_numeric($this->manualMin))
    {
      algaeCore::addJavaScriptNumericVar($varPrefix . 'manualMin', $this->manualMin);
    }
    else 
    {
      algaeCore::addJavaScriptStringVar($varPrefix . 'manualMin', $this->manualMin);
    }
    //
    // ----- add manual max based on data type, i.e. date is a string
    //
    if (is_numeric($this->manualMax))
    {
      algaeCore::addJavaScriptNumericVar($varPrefix . 'manualMax', $this->manualMax);
    }
    else
    {
      algaeCore::addJavaScriptStringVar($varPrefix . 'manualMax', $this->manualMax);
    }
    echo '</script>';
  }
  
  /**
   * Update the manual minimum if $val < the minimum.
   * Supports AXIS_TYPE_TIME formats when values are in the 'DD-MON-YYYY' format.
   * When using this with AXIS_TYPE_TIME call initTimeAxisManualMinMax() first.
   * @param mixed $val A numeric value or date in the 'DD-MON-YYYY' format.
   */
  public function updateManualMin($val)
  // --------------------------------------------------------------------------
  {
    if ($this->type == algaeGraphAxis::AXIS_TYPE_TIME)
    {
      $minTime = strtotime($this->manualMin);
      $valTime = strtotime($val);
      if ($valTime < $minTime) $this->manualMin = $val;
    }
    else 
    {
      if ($val < $this->manualMin) $this->manualMin = $val;
    }
  }
  
  /**
   * Update the manual maximum if $val > the maximum.
   * Supports AXIS_TYPE_TIME formats when values are in the 'DD-MON-YYYY' format.
   * When using this with AXIS_TYPE_TIME call initTimeAxisManualMinMax() first.
   * @param mixed $val A numeric value or date in the 'DD-MON-YYYY' format.
   */
  public function updateManualMax($val)
  // --------------------------------------------------------------------------
  {
    if ($this->type == algaeGraphAxis::AXIS_TYPE_TIME)
    {
      $maxTime = strtotime($this->manualMax);
      $valTime = strtotime($val);
      if ($valTime > $maxTime) $this->manualMax = $val;
    }
    else
    {
      if ($val > $this->manualMax) $this->manualMax = $val;
    }
  }
  
}






