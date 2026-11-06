<?php

/**

  algae framework | XY graph.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeGraphXY extends algaeGraph
{
  
  /**
   * Must be the same as algaeGraphAxis.enumType
   */
  CONST AXIS_TYPE_ARITHMETIC = 0;
  CONST AXIS_TYPE_LOG = 1;
  CONST AXIS_TYPE_TIME = 2;
  
  public $defaultSymbol;
  public $defaultSymbolSize;
  public $symbolColorStyle;
  public $symbolColorPos;
  public $symbolStylePos;
  public $symbolStyle;
  public $groupVarNum;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    parent::__construct();
    $this->javaScriptClass = 'algaeXYGraph';
    $this->defaultSymbol = algaeGraph::GRAPH_SYMBOL_SQUARE;
    $this->defaultSymbolSize = 20;
    $this->symbolColorStyle = algaeGraph::COLOR_DEFAULT;
    $this->symbolColorPos = 3;  // color position in the data array, i.e. [x,y,tooltip,color]
    $this->symbolStyle = algaeGraph::COLOR_DEFAULT;
    $this->symbolStylePos = 4;  // symbol number position in the data array, i.e. [x,y,tooltip,color,symbol_number]
    $this->groupVarNum = -1; // group variable number, set if applicable, used to color by a group field
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
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'defaultSymbol', $this->defaultSymbol);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'defaultSymbolSize', $this->defaultSymbolSize);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'symbolColorStyle', $this->symbolColorStyle);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'symbolColorPos', $this->symbolColorPos);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'symbolStyle', $this->symbolStyle);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'symbolStylePos', $this->symbolStylePos);
    algaeCore::addJavaScriptIntegerVar($varPrefix . 'groupVarNum', $this->groupVarNum);
    echo '</script>';
  }
  
}
