
/**

  algae framework | Graph axes.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeGraphAxis = (function() {
  
  algaeGraphAxis.enumDirection = Object.freeze({"x":0, "y":1});
  algaeGraphAxis.enumType = Object.freeze({"arithmetic":0, "log":1, "time":2});
  
  /**
   * Constructor.
   * varNum = variable index, 0 = first position in array, 1 = second
   */
  function algaeGraphAxis(direction) {
  // --------------------------------------------------------------------------
    this.show = true;
    this.direction = direction;
    this.directionName = 'X';
    this.varNum = 0; 
    if (direction === algaeGraphAxis.enumDirection.y) {
      this.directionName = 'Y';
      this.varNum = 1; 
    }
    this.type = algaeGraphAxis.enumType.arithmetic;
    this.scaleSetup = algaeGraph.enumAutoManual.automatic;
    this.manualMin = 0;
    this.manualMax = 100;
    this.title = this.directionName + ' Axis Title';  
    this.antiLogTickLabels = false;
    this.offset = 15;
    this.reverse = false;
    this.tickFormat = '';
    this.numTicks = 0;  // set to > 0 for a manual number of ticks
    this.numDecimals = 2;  // setup initially for use with formatting mouse coordinates
    this.dataType = algaeGraph.enumDataType.numeric;
    this.gridLines = false;
    this.parseDate = d3.timeParse("%d-%b-%Y");
    this.scale = null;
    this.axis = null;
    this.timeTickLabels = false;
	  this.timeTickLabelsSpacingSec = 3600;  // spacing for time axis tick labels in seconds, i.e. 3600 = every hour
	  this.timeTickLabelsShowMinutes = true;
	  this.timeTickLabelsShowSeconds = true;
    return this;
  }

  algaeGraphAxis.prototype.showForDebugging = function(label) {
  // --------------------------------------------------------------------------
    console.log('DEBUG: ' + label);
    console.log('DEBUG: ' + this.directionName + ' axis parameters');
    console.log('DEBUG: manualMin = ' + this.manualMin.toString());
    console.log('DEBUG: manualMax = ' + this.manualMax.toString());
  }
  
  /**
   * Get the minimum data value for the scale.  
   * This is directly from the final d3 scale for the axis.
   * @return varies Minimum or null if the scale is not defined.
   */
  algaeGraphAxis.prototype.getScaleDataMin = function() {
  // --------------------------------------------------------------------------
    if (this.scale != null) {
      return this.scale.domain()[0];
    }
    return null;
  }
  
  /**
   * Get the maximum data value for the scale.  
   * This is directly from the final d3 scale for the axis.
   * @return varies Maximum or null if the scale is not defined.
   */
  algaeGraphAxis.prototype.getScaleDataMax = function() {
  // --------------------------------------------------------------------------
    if (this.scale != null) {
      return this.scale.domain()[1];
    }
    return null;
  }

  /**
   * Get the minimum range value for the scale.  
   * This is directly from the final d3 scale for the axis.
   * @return varies Minimum or null if the scale is not defined.
   */
  algaeGraphAxis.prototype.getScaleRangeMin = function() {
  // --------------------------------------------------------------------------
    if (this.scale != null) {
      return this.scale.range()[0];
    }
    return null;
  }
  
  /**
   * Get the maximum range value for the scale.  
   * This is directly from the final d3 scale for the axis.
   * @return varies Maximum or null if the scale is not defined.
   */
  algaeGraphAxis.prototype.getScaleRangeMax = function() {
  // --------------------------------------------------------------------------
    if (this.scale != null) {
      return this.scale.range()[1];
    }
    return null;
  }
  
  /**
   * Get the midpoint data value for the scale.  
   * This is directly from the final d3 scale for the axis.
   * @return varies Midpoint or null if the scale is not defined.
   */
  algaeGraphAxis.prototype.getScaleDataMidpoint = function() {
  // --------------------------------------------------------------------------
    if (this.scale != null) {
      return this.getScaleDataMin() + ((this.getScaleDataMax() - this.getScaleDataMin()) / 2.0);
    }
    return null;
  }
  
  /***
   * Convert a value from graph (i.e. screen) units to data units.
   */
  algaeGraphAxis.prototype.getInvertedValue = function(graphVal) {
  // --------------------------------------------------------------------------
    if (this.scale != null) {
      return this.scale.invert(graphVal);
    }
    return null;
  }
  
  /***
   * Convert a value from graph (i.e. screen) units to data units and
   * return it as a formatted string.
   */
  algaeGraphAxis.prototype.getInvertedValueAsString = function(graphVal) {
  // --------------------------------------------------------------------------
    let stringVal = '';
    let dataVal = this.getInvertedValue(graphVal);
    if (dataVal != null) {
      // console.log('DEBUG: ' + dataVal.toString());
      if ( (this.dataType == algaeGraph.enumDataType.numeric) ||
           (this.dataType == algaeGraph.enumDataType.currency) ) {
        stringVal = algaefw.getFormattedNumber(dataVal, this.numDecimals);
      }
      if (this.dataType == algaeGraph.enumDataType.currency) {
        stringVal = '$' + stringVal;
      }
    }
    return stringVal;
  }
  
  /**
   * Adjust the manual scale range to accomodate val.
   * @param float or date string val Value to test against the current manual scale min and max.
   * If the axis type is time then the val should be a string in military date format, i.e. 21-Aug-2024.
   */
  algaeGraphAxis.prototype.adjustManualScaleRange = function(val) {
  // --------------------------------------------------------------------------
    if (this.type == algaeGraphAxis.enumType.time) {
      dval = this.parseDate(val);
      curmin = this.parseDate(this.manualMin);
      curmax = this.parseDate(this.manualMax);
      if (dval < curmin) { this.manualMin = val; }
      if (dval > curmax) { this.manualMax = val; }
    } else {
      if (val < this.manualMin) { this.manualMin = val; }
      if (val > this.manualMax) { this.manualMax = val; }
    }
  }
  
  /**
   * Manually setup a scale, supports all axis types.
   * @param float or date domainMin Actual data minimum.
   * @param float or date domainMax Actual data maximum.
   * @param float rangeMin Minimum for units to scale into, graph units.
   * @param float rangeMax Maximum for units to scale into, graph units.
   * @param boolean nice True to round axis to even units.
   * 
   * For dates the domainMin and domainMax can be Date objects or strings like '19-Aug-2024'.
   */
  algaeGraphAxis.prototype.manualScale = function(domainMin, domainMax, rangeMin, rangeMax, nice) {
  // --------------------------------------------------------------------------
    //
    // ----- setup scale based on type
    //
    delete this.scale;
    if (this.type == algaeGraphAxis.enumType.log) {
      this.scale = d3.scaleLog();
    } else if (this.type == algaeGraphAxis.enumType.arithmetic) {
      this.scale = d3.scaleLinear().domain([domainMin, domainMax]);
    } else if (this.type == algaeGraphAxis.enumType.time) {
      
      // var timeMin = new Date('2021-01-01T' + domainMin + 'Z');
      // var timeMax = new Date('2021-01-01T' + domainMax + 'Z');
      // this.scale = d3.scaleTime().domain([timeMin, timeMax]);
      
      //
      // ----- dates can be a string like '19-Aug-2024' or a Date object
      //
      let dmin = domainMin;
      if (dmin instanceof Date == false) {
        dmin = this.parseDate(domainMin);
      }
      let dmax = domainMax;
      if (dmax instanceof Date == false) {
        dmax = this.parseDate(domainMax);
      }
      this.scale = d3.scaleTime().domain([dmin, dmax]);
    }
    //
    // ----- add range, reverse if specified
    //
    if (this.reverse === true) {
      this.scale.range([rangeMax, rangeMin]);
    } else {
      this.scale.range([rangeMin, rangeMax]);
    }
    //
    // ----- make nice
    //
    if (nice) {
      this.scale.nice();
    }
    // console.log('DEBUG: this.scale.domain()[0] = ' + this.scale.domain()[0].toString());
    // console.log('DEBUG: this.scale.domain()[1] = ' + this.scale.domain()[1].toString());
    return this;
  } 
  
  /**
   * Draw grid lines for the axis.  This is a placeholder to be overridden
   * by a derived object.
   */
  algaeGraphAxis.prototype.drawGridLines = function(svg, innerWidth, innerHeight) {
  // --------------------------------------------------------------------------
    return this;
  }
  
  /**
   * Format tick labels in HH:MM:SS.
   * @param integer d Number of seconds.
   */
  algaeGraphAxis.prototype.timeFormat = function(d) {
  // --------------------------------------------------------------------------
    var seconds = d;
    //
    // ----- convert to hours, minutes seconds
    //
    hours = Math.floor(d / 3600);
    d %= 3600;
    minutes = Math.floor(d / 60);
    seconds = d % 60;
    //
    // ----- format
    //
    let timestr = algaefw.pad(hours, 2) + ':' + algaefw.pad(minutes, 2);
    return timestr;
  }
  
  /**
   * Draw the axis.
   */
  algaeGraphAxis.prototype.draw = function(svg, innerWidth, innerHeight, margin) {
  // --------------------------------------------------------------------------
    if (this.show === false) {
      return this;
    }
    if (typeof(this.scale) !== 'undefined') {
      if (this.direction === algaeGraphAxis.enumDirection.x) {
        this.axis = d3.axisBottom().scale(this.scale);
      } else {
        this.axis = d3.axisLeft().scale(this.scale);
      }
      if (this.numTicks > 0) {
        this.axis.ticks(this.numTicks);
      }
      if (this.tickFormat.length > 0) {   
      	if (this.numTicks == 0) {
      	  alert('Set the number of ticks when using a custom tick format.');
      	}
      	this.axis.ticks(this.numTicks);
        // this.axis.tickFormat(d3.format(this.tickFormat));        
        // this.axis.ticks(this.numTicks); 
      }
      //
      //
      //
      if (this.antiLogTickLabels) {
        // this.axis.tickFormat((d,i) => Math.pow(10, d));
        
        // console.log('DEBUG: domain[0] = ' + this.scale.domain()[0].toString());
        // console.log('DEBUG: domain[1] = ' + this.scale.domain()[1].toString());
        
        var tickValues = [];
        for (let i = -10; i < 11; i++) {
          if ((i >= this.scale.domain()[0]) && (i <= this.scale.domain()[1])) {
            tickValues.push(i);
          }
        }
        
        this.axis.tickValues(tickValues);
        
        this.axis.tickFormat(function(d,i) { if (Number.isInteger(d)) { return Math.pow(10, d); } return '';});
        
        // function(d) { return that.getColor(d, color); }
      }
      //
      // ----- setup time scale ticks and labels
      //
      if (this.timeTickLabels) {
        this.axis.tickFormat(this.timeFormat);
        //
        // ----- label every hour that is within the range of the axis
        //
        var tickValues = []; // 86400
        for (let i = 3600; i < 604800; i += 3600) {
          if ((i >= this.scale.domain()[0]) && (i <= this.scale.domain()[1]) && (i % this.timeTickLabelsSpacingSec == 0)) {
            tickValues.push(i);
          }
        }
        this.axis.tickValues(tickValues);
      }
      //
      // ----- draw the axis
      //
      if (this.direction === algaeGraphAxis.enumDirection.x) {
    	// console.log('DEBUG: ' + this.axis);
        svg.append("g")
        .attr("class", "graphAxis")
        .attr("transform", "translate(0," + (innerHeight + this.offset) + ")")
        .call(this.axis);
      } else {
        svg.append("g")
        .attr("class", "graphAxis")
        .attr("transform", "translate(" + (this.offset * -1) + ",0)")
        .call(this.axis);
      }
      //
      // ----- axis title
      //
      if (this.title.length > 0) {
        if (this.direction === algaeGraphAxis.enumDirection.x) {
          svg.append("text")
             .attr("class", "graphAxisTitle")
             .attr("text-anchor", "middle")
             .attr("x", innerWidth / 2)
             .attr("y", innerHeight + (margin.bottom - 20) + this.offset)
             .text(this.title);
        } else {
          var leftAnchor = ((margin.left - 10) * -1);
          svg.append("text")
             .attr("class", "graphAxisTitle")
             .attr("text-anchor", "middle")
             .attr("x", leftAnchor + (this.offset * -1))
             .attr("y", innerHeight / 2)
             .attr("transform", "rotate(-90 " + leftAnchor + "," + innerHeight / 2 + ")")
             .text(this.title);
        }
      }
      if (this.gridLines) {
        this.drawGridLines(svg, innerWidth, innerHeight);
      }
    }
    return this;
  }; 
 
  return algaeGraphAxis;
})();






