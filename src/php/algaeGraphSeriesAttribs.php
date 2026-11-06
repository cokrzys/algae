<?php

/**

  algae framework | Graph series attributes.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeGraphSeriesAttribs
{
  
  public $longName;
  public $urlSuffix;
  public $includeColorBlock;
  public $color;
  public $asLine;
  public $symbol;
  public $symbolSize;
  public $lineColor;
  public $fillColor;
  public $lineStyle;
  public $labelCSSClass;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    $this->longName = null;
    $this->clickURL = null;
    $this->includeColorBlock = False;
    $this->lineColor = 'black';
    $this->fillColor = 'white';
    $this->asLine = False;
    $this->symbol = algaeGraph::GRAPH_SYMBOL_SQUARE;
    $this->lineStyle = algaeGraph::GRAPH_LINE_SOLID;
    $this->symbolSize = 15;
    $this->labelCSSClass = 'graphAxisTitle';
  }
  
  /**
   * Get the object as an array.
   */
  public function getArray()
  // --------------------------------------------------------------------------
  {
    $a = array();
    $a['longName'] = $this->longName;
    $a['clickURL'] = $this->clickURL;
    $a['includeColorBlock'] = algaeCore::getTrueFalse($this->includeColorBlock);
    $a['asLine'] = algaeCore::getTrueFalse($this->asLine);
    $a['lineColor'] = $this->lineColor;
    $a['fillColor'] = $this->fillColor;
    $a['symbol'] = $this->symbol;
    $a['symbolSize'] = $this->symbolSize;
    $a['labelCSSClass'] = $this->labelCSSClass;
    $a['lineStyle'] = $this->lineStyle;
    return $a;
  }
  
  /**
   * Get the object as a JSON string.
   * @return string
   */
  public function getJSON()
  // --------------------------------------------------------------------------
  {
    return json_encode($this->getArray(), JSON_NUMERIC_CHECK);
  }
  
}