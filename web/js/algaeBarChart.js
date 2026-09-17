
/**

  algae framework | Bar chart graphs.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeBarChart = (function() {
  
  /**
   * Constructor.
   */
  function algaeBarChart() {
  // --------------------------------------------------------------------------
    algaeGraph.call(this);
    this.barVarNum = 0;
    this.valueVarNum = 1;
    this.nameVarNum = 2;
    this.colorVarNum = 3;
    this.bars = [];
    this.barWidthPctOfMin = 0.5;
    this.barWidth = null;  // calculated, do not set this
    return this;
  };
  
  /**
   * Setup prototype based inheritance
   */
  algaeBarChart.prototype = Object.create(algaeGraph.prototype);
  
  /**
   * Get scale extents from min/max values of each group of data.
   */
  algaeBarChart.prototype.setupScales = function() {
  // --------------------------------------------------------------------------
    //
    // ----- y-axis, scaled from total value of each bar
    //
    this.yAxis.scaleSetup = algaeGraph.enumAutoManual.manual;
    this.yAxis.manualMin = 0;
    this.yAxis.manualMax = 0; // will be updated below
    //
    // ----- Create summary array of bars with the sum of all values for each (needed for stacked bars).
    //       Also used to find the maximum y-axis value.
    //
    if (this.data.length > 0) {
      for (var i=0; i < this.data.length; i++) {
        if (this.data[i] != undefined) {
          for (var j=0; j < this.data[i].length; j++) {
            //
            // ----- check if a bar is already in the summary array of bars
            //
            if (this.bars.some(bar => bar.name === this.data[i][j][this.barVarNum])) {
              // console.log('Found ' + this.data[i][j][this.barVarNum] + ' in the bars array.');
              let bar = this.bars.find(bar => bar.name === this.data[i][j][this.barVarNum]);
              // console.log('Sum Values before = ' + bar.sumValues.toString());
              bar.sumValues += this.data[i][j][this.valueVarNum];
              if (bar.sumValues > this.yAxis.manualMax) {
                this.yAxis.manualMax = bar.sumValues;
              }
              if (bar.sumValues < this.yAxis.manualMin) {
                this.yAxis.manualMin = bar.sumValues;
              }
              // console.log('Sum Values after = ' + bar.sumValues.toString());
            } else {
              // console.log('Did not find ' + this.data[i][j][this.barVarNum] + ' in the bars array.');
              let newBar = {
                  'name': this.data[i][j][this.barVarNum],
                  'sumValues': this.data[i][j][this.valueVarNum]
                  };
              if (newBar.sumValues > this.yAxis.manualMax) {
                this.yAxis.manualMax = newBar.sumValues;
              }
              if (newBar.sumValues < this.yAxis.manualMin) {
                this.yAxis.manualMin = newBar.sumValues;
              }
              this.bars.push(newBar);
            }
          }
        }
      }
    }
    //
    // x-axis, sets up bar width then expands axis length to accomodate bars at each end
    //
    if (this.bars.length > 1) {
      //
      // ----- temporary x-axis scale used to determine best bar width
      //
      xAxis = new algaeGraphNumericAxis(algaeGraphAxis.enumDirection.x);
      xAxis.type = this.xAxis.type;
      xAxis.varNum = this.barVarNum;
      xAxis.autoScale(0, this.innerWidth, this.data, false);
      //
      // ----- find best bar width, i.e. minimum size between bars
      //
      for (var i=1; i < this.bars.length; i++) {
        let w = xAxis.scale(xAxis.parseDate(this.bars[i].name)) - xAxis.scale(xAxis.parseDate(this.bars[i-1].name));
        if ((this.barWidth === null) || (w < this.barWidth)) {
          this.barWidth = w;
        }
      }
      //
      // ----- expand width of x-axis scale and setup manual scaling for final x-axis
      //
      this.barWidth *= this.barWidthPctOfMin;
      // console.log('DEBUG: this.barWidth = ' + this.barWidth.toString());
      this.xAxis.manualMin = algaefw.getDateStringMilitary(xAxis.getInvertedValue(-this.barWidth));
      this.xAxis.manualMax = algaefw.getDateStringMilitary(xAxis.getInvertedValue(this.innerWidth + this.barWidth));
      // console.log('DEBUG: this.xAxis.manualMin = ' + this.xAxis.manualMin);
      // console.log('DEBUG: this.xAxis.manualMax = ' + this.xAxis.manualMax);
      this.xAxis.scaleSetup = algaeGraph.enumAutoManual.manual;
    }
  }
  
  /**
   * Draw a data series.
   */
  algaeBarChart.prototype.drawSeries = function(num, data, name, color, attributes) {  
  // --------------------------------------------------------------------------
    if ( (typeof(this.xAxis) !== 'undefined') && (typeof(this.yAxis) !== 'undefined') &&
         (typeof(data) !== 'undefined') && (typeof(this.xAxis.scale) !== 'undefined') && 
         (typeof(this.yAxis.scale) !== 'undefined') ) {
      let currentBar = null;
      let currentYBase = 0;
      let barHalfWidth = this.barWidth / 2;
      if (this.debug) { console.log('DEBUG: barHalfWidth = ' + barHalfWidth.toString()); }
      //
      // ----- check for data
      //
      for (var i=0; i < data.length; i++) {
        if ( (currentBar === null) || (currentBar.name != data[i][this.barVarNum]) ) {
          currentBar = this.bars.find(bar => bar.name === data[i][this.barVarNum]);
          currentYBase = 0;
        }
        if (this.debug) { console.log('DEBUG: data[i][this.barVarNum] = ' + data[i][this.barVarNum] + ' data[i][this.valueVarNum] = ' + data[i][this.valueVarNum].toString()); }
        let y = this.yAxis.scale(currentYBase + data[i][this.valueVarNum]);
        let height = this.yAxis.scale(currentYBase) - this.yAxis.scale(currentYBase + data[i][this.valueVarNum]);
        if (data[i][this.valueVarNum] < 0) {
          y = this.yAxis.scale(0);
          height *= -1;
        }
        let tooltip = null;
        if (data[i].length > this.tooltipPos) {
          tooltip = data[i][this.tooltipPos];
        }
        this.drawRectangle(this.xAxis.scale(this.xAxis.parseDate(data[i][this.barVarNum])) - barHalfWidth,
                           y,
                           barHalfWidth * 2, 
                           height,
                           'black', 
                           data[i][this.colorVarNum], data[i][this.nameVarNum], tooltip);
        currentYBase += data[i][this.valueVarNum];
      }
    }
    return this;
  }
  
  /**
   * Adjust scales after initial setup.
   * Can be over-ridden in a derived object to adjust scales using additional data.
   */
  algaeBarChart.prototype.adjustScales = function() {
  // --------------------------------------------------------------------------
    return this;
  }
  
  /**
   * Draw the graph.
   */
  algaeBarChart.prototype.draw = function() {
  // --------------------------------------------------------------------------
    this.init();
    this.setupScales();
    this.adjustScales();
    algaeGraph.prototype.draw.call(this);
    this.drawHorizLine(0, '#888888', 1);
    return this;
  }
 
  return algaeBarChart;
})();