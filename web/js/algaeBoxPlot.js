
/**

  algae framework | Boxplots.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeBoxplot = (function() {
  
  /**
   * Constructor.
   */
  function algaeBoxplot() {
  // --------------------------------------------------------------------------
    algaeGraph.call(this);
    this.groupVarNum = 0;
    this.numVarNum = 1;
    this.minVarNum = 2;
    this.maxVarNum = 3;
    this.meanVarNum = 4;
    this.q1VarNum = 5;
    this.medianVarNum = 6;
    this.q3VarNum = 7;
    return this;
  };
  
  /**
   * Setup prototype based inheritance
   */
  algaeBoxplot.prototype = Object.create(algaeGraph.prototype);
  
  /**
   * Get scale extents from min/max values of each group of data.
   */
  algaeBoxplot.prototype.setupScales = function() {
  // --------------------------------------------------------------------------
    var that = this;
    var nan = 1.e30;
    var minData = nan;
    var maxData = -nan;
    var min1 = 0.0;
    var max1 = 0.0; 
    var min2 = 0.0;
    var max2 = 0.0;
    if (this.data.length > 0) {
      for (var i=0; i < this.data.length; i++) {
        if (this.data[i] != undefined) {
          if (this.data[i][0].constructor === Array) {
            min1 = d3.min(this.data[i], function(d) { return d[that.minVarNum]; });
            max1 = d3.max(this.data[i], function(d) { return d[that.minVarNum]; });  
            min2 = d3.min(this.data[i], function(d) { return d[that.maxVarNum]; });
            max2 = d3.max(this.data[i], function(d) { return d[that.maxVarNum]; }); 
          } else {
            alert('Object datasets not supported in algaeBoxplot.setupScales().');
          }        
          if (min1 < minData) minData = min1;
          if (max1 > maxData) maxData = max1;
          if (min2 < minData) minData = min2;
          if (max2 > maxData) maxData = max2;
        }
      }
      //
      // ----- x axis, scaled from data min/max
      //
      this.xAxis.scaleSetup = algaeGraph.enumAutoManual.manual;
      this.xAxis.manualMin = minData;
      this.xAxis.manualMax = maxData;
      //
      // ----- y axis, scaled from number of items
      //
      this.yAxis.scaleSetup = algaeGraph.enumAutoManual.manual;
      this.yAxis.manualMin = 0;
      this.yAxis.manualMax = this.data[0].length;
      this.yAxis.show = false;
    }
  }
  
  /**
   * Draw outliers for a group if they exist.
   */
  algaeBoxplot.prototype.drawOutliers = function(groupData, yMidpoint, height) {  
  // --------------------------------------------------------------------------
    if (typeof(this.outliersArray) !== 'undefined') {
      let found = false;
      let i = 0;
      let outlierColor = '#888888';
      //
      // ----- check if any outliers for the group
      //
      while ((! found) && (i < this.outliersArray.length)) {
        if (groupData[this.groupVarNum] == this.outliersArray[i]['group']) {
          found = true;
        } else {
          i++;
        }
      }
      //
      // ----- if found plot them
      //
      if (found) {
        for (var j=0; j < this.outliersArray[i]['outliers'].length; j++) {
          let x = this.xAxis.scale(this.outliersArray[i]['outliers'][j]);
          this.drawSymbol(x, yMidpoint, algaeGraph.enumSymbol.circle, height / 5, outlierColor, outlierColor);
        }
      }
    }
  }
  
  /**
   * Draw a data series.
   */
  algaeBoxplot.prototype.drawSeries = function(num, data, name, color, attributes) {  
  // --------------------------------------------------------------------------
    if ( (typeof(this.xAxis) !== 'undefined') && (typeof(this.yAxis) !== 'undefined') &&
         (typeof(data) !== 'undefined') && (typeof(this.xAxis.scale) !== 'undefined') && 
         (typeof(this.yAxis.scale) !== 'undefined') ) {
      //
      // ----- check for data
      //
      for (var i=0; i < data.length; i++) {
        let height = this.yAxis.scale(0.5) - this.yAxis.scale(1);
        let x1 = this.xAxis.scale(data[i][this.q1VarNum]);
        let x2 = this.xAxis.scale(data[i][this.q3VarNum]);
        let iqr = data[i][this.q3VarNum] - data[i][this.q1VarNum];
        let yTop = this.yAxis.scale(data.length - i) + (height * 0.5);
        let yMidpoint = yTop + (height * 0.5);
        let x = data[i][this.q1VarNum] - (iqr * 1.5);
        if (data[i][this.minVarNum] > x) { x = data[i][this.minVarNum]; }
        let xWhiskerMin = this.xAxis.scale(x);
        x = data[i][this.q3VarNum] + (iqr * 1.5);
        if (data[i][this.maxVarNum] < x) { x = data[i][this.maxVarNum]; }
        let xWhiskerMax = this.xAxis.scale(x);
        let xMedian = this.xAxis.scale(data[i][this.medianVarNum]);
        let xMean = this.xAxis.scale(data[i][this.meanVarNum]);
        let yWhiskerTop = yTop + (height / 4);
        let yWhiskerBottom = yWhiskerTop + (height / 2);
        let whiskerColor = '#888888';
        //
        // ----- main boxplot rectangle and median line
        //
        this.drawRectangle(x1, yTop, x2-x1, height, '#000000', '#DFDFDF');
        this.drawLine(xMedian, yTop, xMedian, yTop + height, '#000000', 1);
        //
        // ----- whiskers
        //
        this.drawLine(xWhiskerMin, yMidpoint, x1, yMidpoint, whiskerColor, 1);
        this.drawLine(xWhiskerMin, yWhiskerTop, xWhiskerMin, yWhiskerBottom, whiskerColor, 1);
        this.drawLine(x2, yMidpoint, xWhiskerMax, yMidpoint, whiskerColor, 1);
        this.drawLine(xWhiskerMax, yWhiskerTop, xWhiskerMax, yWhiskerBottom, whiskerColor, 1);
        //
        // ----- group name on left, number of datapoints in each group on right
        //
        this.drawText(-this.margin.left, yMidpoint, data[i][this.groupVarNum], 'graphAxisTitle', 'start', 'central', false);
        this.drawText(this.innerWidth + 10, yMidpoint, 
            'n ' + data[i][this.numVarNum].toString() + 
            ' | x̄ ' + algaefw.getFormattedNumber(data[i][this.meanVarNum], this.xAxis.numDecimals),
            'graphAxisTitle', 'start', 'central', false);
        //
        // ----- circle for mean
        //
        this.drawSymbol(xMean, yMidpoint, algaeGraph.enumSymbol.circle, height / 3, '#000000', '#d73027');
        //
        // ----- outliers
        //
        this.drawOutliers(data[i], yMidpoint, height);
      }
    }
  }
  
  /**
   * Draw the graph.
   */
  algaeBoxplot.prototype.draw = function() {
  // --------------------------------------------------------------------------
    this.setupScales();
    algaeGraph.prototype.draw.call(this);
    this.drawVertLine(0, '#888888', 1, 'DASHED');
  }
 
  return algaeBoxplot;
})();
 