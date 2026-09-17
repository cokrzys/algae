
/**

  algae framework | Histograms.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeHistogram = (function() {
  
  /**
   * Constructor.
   */
  function algaeHistogram() {
  // --------------------------------------------------------------------------
  	algaeGraph.call(this);
  	this.numBins = 30;
  	this.logTransform = false;
  	this.binArray = null;
  	this.binWidth = null; // calculated
  	this.drawBoxplots = true;
  	return this;
  };
  
  /**
   * Setup prototype based inheritance
   */
  algaeHistogram.prototype = Object.create(algaeGraph.prototype);
  
  /**
   * Bin a value.
   */
  algaeHistogram.prototype.binValue = function(val) {
  // --------------------------------------------------------------------------
    if ( (val >= this.xAxis.scale.domain()[0]) && (val <= this.xAxis.scale.domain()[1]) ) {
      var bin = Math.floor((val - this.xAxis.scale.domain()[0]) / this.binWidth);
      if (bin >= this.numBins) bin = this.numBins - 1;
      // console.log('DEBUG: val ' + val.toString() + ' is in bin ' + bin.toString());
      this.binArray[bin] += 1;
    }
    return this;
  }
  
  /**
   * Bin the data.
   */
  algaeHistogram.prototype.binData = function() {
  // --------------------------------------------------------------------------
	//
	// ----- initial bin array to zeros
	//
	this.binArray = new Array(this.numBins);
	for (var i = 0; i < this.binArray.length; i++) {
	  this.binArray[i] = 0;
	}
	//
	// ----- if the scale is defined continue
	//       this.scale.domain()[0] = min
    //       this.scale.domain()[1] = max
	//
	if (this.xAxis.scale != 'undefined') {
	  this.binWidth = (this.xAxis.scale.domain()[1] - this.xAxis.scale.domain()[0]) / this.numBins;
	  console.log('DEBUG: binWidth = ' + this.binWidth.toString());
	  //
	  // ----- bin the data
	  //
	  for (var i = 0; i < this.data[0].length; i++) {
		  this.binValue(this.data[0][i]);
	  }
	}
    return this;
  } 
  
  /**
   * Draw the data.
   */
  algaeHistogram.prototype.drawData = function() {
  // --------------------------------------------------------------------------
	var maxBin = d3.max(this.binArray);
	if (maxBin > 0) {
	  this.yAxis.manualScale(0, maxBin, this.innerHeight, 0, false);
	  this.yAxis.draw(this.svg, this.innerWidth, this.innerHeight, this.margin);
	  //
	  // x, y, width, height, lineColor, fillColor
	  //
	  /*
	  console.log('DEBUG: this.xAxis.scale.domain()[0] = ' + this.xAxis.scale.domain()[0].toString());
	  console.log('DEBUG: this.xAxis.scale.domain()[1] = ' + this.xAxis.scale.domain()[1].toString());
	  console.log('DEBUG: this.xAxis.scale.range()[0] = ' + this.xAxis.scale.range()[0].toString());
	  console.log('DEBUG: this.xAxis.scale.range()[1] = ' + this.xAxis.scale.range()[1].toString());
	  */
	  for (var i = 0; i < this.binArray.length; i++) {
	    if (this.binArray[i] > 0) {
	      var x1 = this.xAxis.scale(this.xAxis.scale.domain()[0] + (i * this.binWidth)) + 0.5;
	      var x2 = this.xAxis.scale(this.xAxis.scale.domain()[0] + ((i + 1) * this.binWidth)) + 0.5;
	      // console.log('DEBUG: x1 = ' + x1.toString() + ' x2 = ' + x2.toString() + ' width = ' + (x2-x1).toString());
	      var y = this.yAxis.scale(this.binArray[i]);
	      this.drawRectangle(x1, y, x2 - x1, this.innerHeight - y, '#000000', '#CDCDCD');
	    }
	  }
	}
    return this;
  }
  
  /**
   * Log transform.
   */
  algaeHistogram.prototype.transform = function() {
  // --------------------------------------------------------------------------
	if (this.logTransform) {
      var minRawData = d3.min(this.data[0]);
      var shift = 0.0;
      if (minRawData <= 0) {
        shift = (minRawData * -1.0) + 0.001;
        console.log('DEBUG: shift = ' + shift.toString());
      }
	  for (var i = 0; i < this.data[0].length; i++) {
        this.data[0][i] = Math.log10(this.data[0][i] + shift);
	  }
	}
    return this;
  }
  
  /**
   * Draw stats on the histogram.
   */
  algaeHistogram.prototype.drawStats = function(stats, color, heightScaler) {
  // --------------------------------------------------------------------------
  	if (this.data[0].length > 0) {
  	  var yEnd = this.innerHeight - (this.innerHeight * heightScaler);
  	  //
  	  // ----- mean
  	  //
  		var x = this.xAxis.scale(stats.mean);
  		this.drawLine(x, this.innerHeight, x, yEnd, color, 2);
  		//
  		// ----- +/- 1sd
  		//
      x = this.xAxis.scale(stats.mean + stats.standard_deviation);
      this.drawLine(x, this.innerHeight, x, yEnd, color, 1, 'DASHED');
      x = this.xAxis.scale(stats.mean - stats.standard_deviation);
      this.drawLine(x, this.innerHeight, x, yEnd, color, 1, 'DASHED');
      //
      // ----- +/- 2sd
      //
      x = this.xAxis.scale(stats.mean + (stats.standard_deviation * 2));
      this.drawLine(x, this.innerHeight, x, yEnd, color, 1, 'DOTTED');
      x = this.xAxis.scale(stats.mean - (stats.standard_deviation * 2));
      this.drawLine(x, this.innerHeight, x, yEnd, color, 1, 'DOTTED');
  	}
    return this;
  }
  
  /**
   * Draw a boxplot below the histogram.
   */
  algaeHistogram.prototype.drawBoxplot = function(stats, color, yMidpoint) {
  // --------------------------------------------------------------------------
    var x = this.xAxis.scale(stats.q1);
    var width = this.xAxis.scale(stats.q3) - x;
    var rectHalfHeight = 5;
    // x, y, width, height, lineColor, fillColor
    this.drawRectangle(x, yMidpoint - rectHalfHeight, width, rectHalfHeight * 2, color, '#CDCDCD');
    // x1, y1, x2, y2, color, width, style
    this.drawLine(this.xAxis.scale(stats.min), yMidpoint, this.xAxis.scale(stats.q1), yMidpoint, color, 1, 'SOLID');
    this.drawLine(this.xAxis.scale(stats.min), yMidpoint - rectHalfHeight, this.xAxis.scale(stats.min), yMidpoint + rectHalfHeight, color, 1, 'SOLID');
    this.drawLine(this.xAxis.scale(stats.q3), yMidpoint, this.xAxis.scale(stats.max), yMidpoint, color, 1, 'SOLID');
    this.drawLine(this.xAxis.scale(stats.max), yMidpoint - rectHalfHeight, this.xAxis.scale(stats.max), yMidpoint + rectHalfHeight, color, 1, 'SOLID');
    this.drawLine(this.xAxis.scale(stats.median), yMidpoint - rectHalfHeight, this.xAxis.scale(stats.median), yMidpoint + rectHalfHeight, color, 1, 'SOLID');

    
    // x, y, num, size, lineColor, fillColor
    this.drawSymbol(this.xAxis.scale(stats.low_ref_percentile_value), yMidpoint, algaeGraph.enumSymbol.square, rectHalfHeight, color, '#CDCDCD');
    this.drawSymbol(this.xAxis.scale(stats.high_ref_percentile_value), yMidpoint, algaeGraph.enumSymbol.square, rectHalfHeight, color, '#CDCDCD');

    
    return this;
  }
  
  /**
   * Draw the graph.
   */
  algaeHistogram.prototype.draw = function() {
  // --------------------------------------------------------------------------
    this.remove();
    this.init();
    this.drawTitle();
    this.transform();
    this.xAxis.autoScale(0, this.innerWidth, this.data, true);    
    this.xAxis.draw(this.svg, this.innerWidth, this.innerHeight, this.margin);
    this.binData();
    this.drawData();
    this.drawStats(this.origDataStats, '#000000', 1.0);
    // this.drawStats(this.outlierClippedStats, '#0000FF', 0.9);
    this.drawBoxplot(this.origDataStats, '#000000', this.innerHeight + (this.xAxis.offset / 3));
    this.drawBoxplot(this.outlierClippedStats, '#0000FF', this.innerHeight + ((this.xAxis.offset / 3) * 2));
    return this;
  } 
 
  return algaeHistogram;
})();
 