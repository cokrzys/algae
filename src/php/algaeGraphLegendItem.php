<?php

/**

  algae framework | Graph legend item.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeGraphLegendItem
{
  
  /**
   * Must match JavaScript algaeGraph.enumAutoManual.
   */
  CONST GRAPH_AUTOMATIC = 0;
  CONST GRAPH_MANUAL = 1;
  
  /**
   * Must match JavaScript algaeGraph.enumLineStyle.
   */
  CONST GRAPH_LINESTYLE_SOLID = 0;
  CONST GRAPH_LINESTYLE_DASHED = 1;
  CONST GRAPH_LINESTYLE_DOTTED = 2;
  
  public $text;
  public $lineColor;
  public $fillColor;
  public $symbol;
  public $lineStyle;
  public $lineThickness;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    $this->text = '';
    $this->lineColor = '#000000';   # black 
    $this->fillColor = '#FFFFFF';   # white
    $this->symbol = algaeGraph::GRAPH_SYMBOL_RECTANGLE;
    $this->lineThickness = 1;
    $this->lineStyle = algaeGraphLegendItem::GRAPH_LINESTYLE_SOLID;
  }
  
}





