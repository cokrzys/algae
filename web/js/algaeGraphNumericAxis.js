
/**

  algae framework | Numeric axis for graphs.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeGraphNumericAxis = (function() {
  
  /**
   * Constructor.
   */
  function algaeGraphNumericAxis(direction) {
  // --------------------------------------------------------------------------
    algaeGraphAxis.call(this, direction);  // call base class constructor
    this.majorGridSpacing = 10;
    this.minorGridSpacing = 5;
    return this;
  }
  
  /**
   * Setup prototype based inheritance
   */
  algaeGraphNumericAxis.prototype = Object.create(algaeGraphAxis.prototype);
  
  /**
   * Automatically set a date/time scale based on the first and last values
   * in all datasets.  Data must be sorted by the date/time value.
   */
  algaeGraphNumericAxis.prototype.autoTimeScale = function(rangeMin, rangeMax, data, nice) {
  // --------------------------------------------------------------------------
    let min = null;
    let max = null;
    for (var i=0; i < data.length; i++) {
      if (data[i].length >= 2) {
        let dmin = this.parseDate(data[i][0][this.varNum]);
        let dmax = this.parseDate(data[i][data[i].length - 1][this.varNum]);
        if ((min == null) || (dmin < min)) {
          min = dmin;
        }
        if ((max == null) || (dmax > max)) {
          max = dmax;
        }
      }
    }
    if ((min != null) && (max != null)) {
      this.scale = d3.scaleTime().domain([min, max]);
      this.scale.range([rangeMin, rangeMax]);
    }
    /* 
     * older version that works with just the first dataset
     * 
    if ( (data.length >= 1) && (data[0].length >= 2) ) {
      this.scale = d3.scaleTime().domain([this.parseDate(data[0][0][this.varNum]), 
        this.parseDate(data[0][data[0].length - 1][this.varNum])]);
      this.scale.range([rangeMin, rangeMax]);
    }
    */
    return this;
  }
  
  /**
   * Automatically scale an axis to fit the data.
   */
  algaeGraphNumericAxis.prototype.autoScale = function(rangeMin, rangeMax, data, nice) {
  // --------------------------------------------------------------------------
    var that = this;
    var nan = 1.e30;
    var minData = nan;
    var maxData = -nan;
    var min = 0.0;
    var max = 0.0;
    //
    // ----- auto scaling for a date/time axis
    //
    if (this.type == algaeGraphAxis.enumType.time) {
      return this.autoTimeScale(rangeMin, rangeMax, data, nice);
    }
    //
    // ----- figure out min and max across all data
    //
    for (var i=0; i < data.length; i++) {
      if ((data[i] != undefined) && (data[i].length > 0)) {
        // console.log('DEBUG: data[' + i.toString() + '].length = ' + data[i].length.toString());
    	//
    	// ----- this checks if it's an array of arrays
    	//       see: https://stackoverflow.com/questions/31104879/how-to-check-if-array-is-multidimensional-jquery
    	//
    	if (data[i][0].constructor === Array) {
            min = d3.min(data[i], function(d) { return d[that.varNum]; });
            max = d3.max(data[i], function(d) { return d[that.varNum]; });   
          //
          // ----- older support for when the data was in an object
          //
		  //    	} else if (data[0][0].constructor === Object) {
		  //            min = d3.min(data[i], function(d) { return d[that.varName]; });
		  //            max = d3.max(data[i], function(d) { return d[that.varName]; });   
    	} else {
            min = d3.min(data[i]);
            max = d3.max(data[i]);   
    	}        
        if (min < minData) minData = min;
        if (max > maxData) maxData = max;
      }
    }
    //
    // ----- setup scale
    //
    if ( (minData < nan) && (maxData > -nan) ) {
      // console.log('DEBUG: minData in algaeGraphNumericAxis.prototype.autoScale = ' + minData.toString());
      // console.log('DEBUG: maxData in algaeGraphNumericAxis.prototype.autoScale = ' + maxData.toString());
      this.manualScale(minData, maxData, rangeMin, rangeMax, nice);
    }
    return this;
  }
  
  /**
   * Setup the scale either automatically or manually.
   * 
   * NOT TESTED, autoTag is obsolete
   */
  algaeGraphNumericAxis.prototype.setupScale = function(minGraph, maxGraph, data, nice) {
  // --------------------------------------------------------------------------
    //
    // ----- if both are manual then just setup a manual scale
    //
    if ((this.minData != this.autoTag) && (this.maxData != this.autoTag)) {
      this.manualLinearScale(minGraph, maxGraph, this.minData, this.maxData, false);
      return this;
    }
    var origMin = this.minData;
    var origMax = this.maxData;
    //
    // ----- if either axis is automatic then setup an automatic scale first
    //
    if ((this.minData === this.autoTag) || (this.maxData === this.autoTag)) {
      this.autoLinearScale(minGraph, maxGraph, data);
      // console.log('DEBUG: Automatic scale setup, data min = ' + this.minData.toString() + ' data max = ' + this.maxData.toString());
    }
    //
    // ----- min automatic, max specific
    //
    if ((origMin === this.autoTag) && (origMax != this.autoTag)) {
      this.manualLinearScale(minGraph, maxGraph, this.minData, origMax, nice);
      return this;
    }
    //
    // ----- max automatic, min specific
    //
    if ((origMin != this.autoTag) && (origMax === this.autoTag)) {
      this.manualLinearScale(minGraph, maxGraph, origMin, this.maxData, nice);
      return this;
    }    
    return this;
  } 
  
  /**
   * Expand the maximum by a given percentage.  For example 1.2 to expand by 20%.
   */
  algaeGraphNumericAxis.prototype.expandMax = function(percent) {
  // --------------------------------------------------------------------------
    this.maxData = this.maxData + ((this.maxData - this.minData) * percent);
    return this;
  }
  
  /**
   * Adjust scale to accomodate a new value.
   * Scale must be setup before calling.
   * @param val Data value to check and adjust axis to include if needed.
   * 
   * For dates the domainMin and domainMax can be Date objects or strings like '19-Aug-2024'.
   */
  algaeGraphNumericAxis.prototype.adjust = function(val) {
  // --------------------------------------------------------------------------
    if (this.scale != null) {
      let dval = val;
      if (this.type == algaeGraphAxis.enumType.time) {
        if (dval instanceof Date == false) {
          dval = this.parseDate(val);
        }
      }
      if (dval < this.getScaleDataMin()) {
        this.manualScale(dval, this.getScaleDataMax(), this.getScaleRangeMin(), this.getScaleRangeMax(), this.nice);
      } else if (dval > this.getScaleDataMin()) {
        this.manualScale(this.getScaleDataMin(), dval, this.getScaleRangeMin(), this.getScaleRangeMax(), this.nice);
      }
    }
    return this;
  }
  
  /**
   * Draw one set of log grid lines for the axis.
   */
  algaeGraphNumericAxis.prototype.drawOneSetOfLogGridLines = function(svg, innerWidth, innerHeight, spacing, color) {
  // --------------------------------------------------------------------------
    if ((spacing > 0) && (typeof(this.scale) !== 'undefined')) {
      // var cur = Math.floor(Math.log10(this.minData));
      var d = this.scale.domain();
      // console.log('DEBUG: len = ' + d.length.toString() + ' ' + d[0].toString() + ' ' + d[1].toString());
      // var cur = Math.log(d[0]) / Math.LN10;
      // var stop = Math.log(d[1]) / Math.LN10;
      var cur = d[0];
      var stop = d[1];
      var x1, y1, x2, y2;
      if (this.direction === algaeGraphAxis.enumDirection.x) {
        y1 = 0;
        y2 = innerHeight;
      } else {
        x1 = 0;
        x2 = innerWidth;
      }
      // console.log('DEBUG: cur = ' + cur.toString() + ' stop = ' + stop.toString());
      var step = 1;
      var base = cur;
      while (cur < stop) {
        // console.log('DEBUG: cur = ' + cur.toString());
        if (this.direction === algaeGraphAxis.enumDirection.x) {
          x1 = this.scale(cur);
          x2 = x1;
        } else {
          y1 = this.scale(cur);
          y2 = y1;
        }
        // console.log('DEBUG: x1 ' + x1.toString() + ' x2 ' + x2.toString() + ' y1 ' + y1.toString() + ' y2 ' + y2.toString());
        svg.append('line')
           .attr("stroke", color)
           .attr("stroke-width", "1")
           .attr("x1", x1)
           .attr("y1", y1)   
           .attr("x2", x2)
           .attr("y2", y2);
        if (spacing === 1) {
          cur *= 10.0;
        } else {
          if (step === 9) {
            base *= 10;
            step = 1;
          }
          step ++;
          cur = base * step;
        }
      }
    }
  }  
  
  /**
   * Draw one set of grid lines for the axis.
   */
  algaeGraphNumericAxis.prototype.drawOneSetOfGridLines = function(svg, innerWidth, innerHeight, spacing, color) {
  // --------------------------------------------------------------------------
    if ((spacing > 0) && (typeof(this.scale) !== 'undefined')) {
      if (this.type == algaeGraphAxis.enumType.log) {
        this.drawOneSetOfLogGridLines(svg, innerWidth, innerHeight, spacing, color);
      } else {
        var cur = Math.floor(this.minData / spacing) * spacing;
        var x1, y1, x2, y2;
        if (this.direction === algaeGraphAxis.enumDirection.x) {
          y1 = 0;
          y2 = innerHeight;
        } else {
          x1 = 0;
          x2 = innerWidth;
        }
        if (cur === this.minData) {
          cur += spacing;
        }
        while (cur < this.maxData) {
          // console.log('DEBUG: Drawing grid line at ' + cur.toString());
          if (this.direction === algaeGraphAxis.enumDirection.x) {
            x1 = this.scale(cur);
            x2 = x1;
          } else {
            y1 = this.scale(cur);
            y2 = y1;
          }
          // console.log('DEBUG: x1 ' + x1.toString() + ' x2 ' + x2.toString() + ' y1 ' + y1.toString() + ' y2 ');
          svg.append('line')
             .attr("stroke", color)
             .attr("stroke-width", "1")
             .attr("x1", x1)
             .attr("y1", y1)   
             .attr("x2", x2)
             .attr("y2", y2);
          cur += spacing;
        }
      }
    }
  }   
  
  /**
   * Draw grid lines for the axis.
   */
  algaeGraphNumericAxis.prototype.drawGridLines = function(svg, innerWidth, innerHeight) {
  // --------------------------------------------------------------------------
    if ((this.majorGridSpacing === this.autoTag) || (this.minorGridSpacing === this.autoTag)) {
      if (this.minData != this.maxData) {
        if (this.direction === algaeGraphAxis.enumDirection.x) {
          svg.append("g")
             .attr("stroke", this.minorGridColor)
             .attr("stroke-width", "1")
             .attr("transform", "translate(0," + innerHeight + ")")
             .call(this.axis.tickSize(-innerHeight, 0, 0).tickFormat(""));
        } else {
          svg.append("g")
             .attr("stroke", this.minorGridColor)
             .attr("stroke-width", "1")
             .call(this.axis.tickSize(-innerWidth, 0, 0).tickFormat(""));
        }
      }
    } else {
      this.drawOneSetOfGridLines(svg, innerWidth, innerHeight, this.minorGridSpacing, this.minorGridColor);
      this.drawOneSetOfGridLines(svg, innerWidth, innerHeight, this.majorGridSpacing, this.majorGridColor);
    }
  }  
  
  /**
   * Tests if a value is within the range of the axis.
   */
  algaeGraphNumericAxis.prototype.isInRange = function(val) {
  // --------------------------------------------------------------------------
    if ((val >= this.minData) && (val <= this.maxData)) {
      return true;
    }
    return false;
  }
 
  return algaeGraphNumericAxis;
})();






