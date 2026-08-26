
/**

  algae framework | Line graph.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeLineGraph = (function() {
  
  /**
   * Constructor.
   */
  function algaeLineGraph() {
  // --------------------------------------------------------------------------
	algaeGraph.call(this);
	  this.defaultSymbol = algaeGraph.enumSymbol.circle;
    this.defaultSymbolSize = 15;
    this.symbolColorOptions = Object.freeze({"default":0, "variable":1});
    this.symbolColorStyle = this.symbolColorOptions.default;
    this.symbolColorPos = 3; // color position in the data array, i.e. [x,y,tooltip,color]
    this.showSymbols = true;
    this.dashedLine = false;
    this.dottedLine = false;
    this.showLineTooltip = true;
    this.labelLines = false;
    this.lineTooltip; // a div for the line tooltip
    this.endPoints = [];
  };
  
  /**
   * Setup prototype based inheritance
   */
  algaeLineGraph.prototype = Object.create(algaeGraph.prototype);
  
  /**
   * Graph initialization.
   */
  algaeLineGraph.prototype.init = function() {
  // --------------------------------------------------------------------------  

	
	algaeGraph.prototype.init.call(this); // calls super init function
	  
    //
    // ----- add div for a tooltip
    //
    if (typeof(this.lineTooltip) === 'undefined') {
      this.lineTooltip = d3.select("body")
        .append("div")
        .attr("class", "graphTooltip")
        .style("position", "absolute")
        .style("z-index", "10000")
        .style("visibility", "hidden");
    }

    return this;
  }
  
  /**
   * Save the area used by a point on the line.
   */
  algaeLineGraph.prototype.savePtArea = function(data) {
  // --------------------------------------------------------------------------
    var halfSize = this.defaultSymbolSize / 2;
    var x = this.xAxis.scale(data[this.xAxis.varNum]);
    var y = this.yAxis.scale(data[this.yAxis.varNum]);
    this.saveUsedArea(x - halfSize, y - halfSize, x + halfSize, y + halfSize);
    return this;
  }
  
  /**
   * Get a symbol color based on how the color options are set.
   */
  algaeLineGraph.prototype.getSymbolColor = function(data, defaultColor, attributes, attribute) {
  // --------------------------------------------------------------------------
    //
    // ----- (1) get color from a color variable in the data
    //
    if (this.symbolColorStyle === this.symbolColorOptions.variable) {
      if (data.length > this.symbolColorPos) {
        return data[this.symbolColorPos];
      } else {
        console.log('Warning: Color at index ' + this.symbolColorPos.toString() + ' does not exist.');
      }
    }
    //
    // ----- (2) get color from an attribute of the series
    //
    if (this.attributeExists(attributes, attribute)) {
      return attributes[attribute];
    }
    //
    // ----- (3) default color
    //
    return defaultColor;
  } 
  
  /**
   * Draw a data series.
   */
  algaeLineGraph.prototype.drawSeries = function(num, data, name, color, attributes) {  
  // --------------------------------------------------------------------------
    var that = this;
    if ( (typeof(this.xAxis) !== 'undefined') && (typeof(this.yAxis) !== 'undefined') &&
         (typeof(data) !== 'undefined') && (typeof(this.xAxis.scale) !== 'undefined') && 
         (typeof(this.yAxis.scale) !== 'undefined') ) {
      //
      // ----- convert x axis variable to a date if it's a time series
      //
      if (this.xAxis.type == algaeGraphAxis.enumType.time) {
        data.forEach(function (item, index) {
          item[that.xAxis.varNum] = that.xAxis.parseDate(item[that.xAxis.varNum]);
    	});
      }
      //
      // ----- convert y axis variable to a date if it's a time series
      //
      if (this.yAxis.type == algaeGraphAxis.enumType.time) {
        data.forEach(function (item, index) {
          
          // item[that.yAxis.varNum] = new Date('2021-01-01T' + item[that.yAxis.varNum] + 'Z');
          
          item[that.yAxis.varNum] = that.yAxis.parseDate(item[that.yAxis.varNum]);
    	});
      }
      //
      // ----- setup function to make a line
      //
      var lineFunc = d3.line()
	      .x(function(d) { return that.xAxis.scale(d[that.xAxis.varNum]); })
	      .y(function(d) { return that.yAxis.scale(d[that.yAxis.varNum]); });
      
      var clickURL = '';
      if ( (typeof(attributes) !== 'undefined') && (typeof(attributes.clickURL) !== 'undefined') ) {
        clickURL = attributes.clickURL;
      }

      //
      // ----- note poor use of 'False' as a string
      //
      let drawLine = true;
      if ( (typeof(attributes) !== 'undefined') && (attributes.asLine == 'False') ) {
        drawLine = false;
      }
      
      //
      // ----- use line function to draw a line between the points
      //
      if (drawLine) {
        var line = this.svg.append('path')
        	.attr('d', lineFunc(data))
        	.attr('stroke', color)
        	.attr('stroke-width', 1.5)
        	.attr('fill', 'none')
        	.on("click", function(d) { that.clickHandler(clickURL); });
      }

      //
      // ----- show tooltip for the line
      //
      if ( (that.showLineTooltip) && (drawLine) ) {
	      line.on("mouseover", function(d) {  
	    	  that.lineTooltip.html(name); 
	    	  d3.select(this).attr("stroke-width", 3);
	    	  d3.select(this).attr("stroke", '#000000');
	    	  return that.lineTooltip.style("visibility", "visible");
	      });
	      line.on("mousemove", function(d) {
	    	  return that.lineTooltip.style("top", (event.pageY-10)+"px").style("left",(event.pageX+10)+"px");
	      });
	      line.on("mouseout", function(d) {
	    	  d3.select(this).attr("stroke-width", 1.5);
	    	  d3.select(this).attr("stroke", color);
	    	  return that.lineTooltip.style("visibility", "hidden");
	      });
      }
      //
      // ----- change line styles
      //
      if ( (this.dashedLine) ||
           ( (typeof(attributes) !== 'undefined') &&
             (attributes.lineStyle == algaeGraph.enumLineStyle.dashed) 
            )
          ) {
        line.style("stroke-dasharray", ("3, 3"));
      }
      if ( (this.dottedLine) ||
           ( (typeof(attributes) !== 'undefined') &&
             (attributes.lineStyle == algaeGraph.enumLineStyle.dotted) 
            )
          ) {
        line.style("stroke-dasharray", ("1, 3"));
      }
      //
      // ----- draw points at each XY location with mouseover support
      //
      if (this.showSymbols) {
      	var seriesName = name.split(" ").join("");
      	
      	// var regex = 's/^[^a-zA-Z_]+|[^a-zA-Z_0-9]+//g';
      	
      	// seriesName = seriesName.replace(regex, '');
      	
      	// name cannot start with a number thus the leading '_' +
      	
      	seriesName = '_' + seriesName.replace(/[&\/\\#, +()$~%.'":*?<>{}]/g, '_')
      	
      	// console.log('DEBUG: ' + seriesName);
      	
        //
        // ----- use default symbol or one from series attributes
        //
        var symbol = this.defaultSymbol;
        if (this.attributeExists(attributes, 'symbol')) {
          symbol = attributes.symbol;
        }
        //
        // ----- use default symbol size or one from series attributes
        //
        var symbolSize = this.defaultSymbolSize;
        if (this.attributeExists(attributes, 'symbolSize')) {
          symbolSize = attributes.symbolSize;
        }
    	
        this.svg.selectAll(seriesName)
          .data(data)
          .enter()
          .append("path")
          // .filter(function(d) { return that.isOnGraph(d[that.xAxis.varNum], d[that.yAxis.varNum]); }) 
          .attr("class", "graphBlackBorder")
          
          .attr("transform", function(d) { that.savePtArea(d); return "translate(" + that.xAxis.scale(d[that.xAxis.varNum]).toString() + "," + that.yAxis.scale(d[that.yAxis.varNum]).toString() + ")"; })
          .attr("d", function(d) { 
            if ((that.symbolStyle == algaeGraph.enumColorOption.variable) && (that.symbolStylePos < d.length)) { symbol = d[that.symbolStylePos]; }
            return d3.symbol().type(that.getSymbolDefinition(symbol)).size(symbolSize)(); 
          })
          .style("stroke", function(d) { return that.getSymbolColor(d, 'black', attributes, 'lineColor'); })
          .style("fill", function(d) { return that.getSymbolColor(d, color, attributes, 'fillColor'); })
          
          // .attr("cx", function(d) { that.savePtArea(d); return that.xAxis.scale(d[that.xAxis.varNum]); })
          // .attr("cy", function(d) { return that.yAxis.scale(d[that.yAxis.varNum]); })
          .attr("visibility", function(d) { if (d[that.yAxis.varNum] == null) { return 'hidden' } else { return 'visible'; } })
          // .attr("r", this.defaultSymbolSize)
          // .style("fill", function(d) { return that.getColor(d, color); })
          .on("click", function(d) { that.clickHandler(d); })  
          .on("mouseover", function(d) {  
            if (d.length > that.tooltipPos) {
              that.tooltip.html(d[that.tooltipPos]); 
              d3.select(this).attr("class", "graphHighlightedBorder");
              return that.tooltip.style("visibility", "visible");
            } else {
              return that.tooltip.style("visibility", "hidden");
            }
           })
          .on("mousemove", function(d) {
            if (d.length > that.tooltipPos) {
              return that.setTooltipPosition(event);
            }
           })
          .on("mouseout", function(d) {
            if (d.length > that.tooltipPos) {
              d3.select(this).attr("class", "graphBlackBorder");
            }
            return that.tooltip.style("visibility", "hidden");
           });
      }
      //
      // ----- optionally label lines
      //
      if ((that.labelLines) && (data.length >= 1)) {
    	  
  	    var label = name;
  	    if ( (typeof(attributes) !== 'undefined') && (typeof(attributes.longName) !== 'undefined') ) {
  	      label = attributes.longName;
  	    }
  	    
  	    var labelCSSClass = 'graphAxisTitle';
        if ( (typeof(attributes) !== 'undefined') && (typeof(attributes.labelCSSClass) !== 'undefined') ) {
          labelCSSClass = attributes.labelCSSClass;
        }
	      
  	    //
  	    // ----- for the stocks compare group members the additions were x + 20 and y + 5
  	    //       when time check/adjust the logic to use the +7 and +0 changes for labeling the endpoints of
  	    //       graphs with a single series, i.e. stocks financial graphs
  	    //
        x = that.xAxis.scale(data[data.length - 1][that.xAxis.varNum]) + 7;  // 20
        y = that.yAxis.scale(data[data.length - 1][that.yAxis.varNum]) + 0;  // 5
        
        that.endPoints.push({ x:x, y:y, label:label, labelCSSClass:labelCSSClass});
      }
      
    }
  }
  
  /**
   * Set no-label areas.
   */
  algaeLineGraph.prototype.setNoLabelAreas = function() {
  // --------------------------------------------------------------------------
    if (this.labelLines) {
      //
      // ----- restrict labels to right margin
      //
      this.saveUsedArea(-this.margin.left, -this.margin.top, this.innerWidth, this.innerHeight + this.margin.bottom);
    } else {
      algaeGraph.prototype.setNoLabelAreas.call(this);
    }
    return this;
  }
  
  /**
   * Draw the graph.
   */
  algaeLineGraph.prototype.draw = function() {
  // --------------------------------------------------------------------------
    algaeGraph.prototype.draw.call(this);
    
    if ((this.labelLines) && (this.endPoints.length > 0)) {
      
      //
      // ----- only one series just label it
      //
      if (this.data.length == 1) {
        this.drawText(this.endPoints[0].x, this.endPoints[0].y, this.endPoints[0].label,
            this.endPoints[0].labelCSSClass, 'start', 'central', false);
      } else {
    
        this.endPoints.sort(function (a, b) { return a.y - b.y; });
        
        /**
        for (var i = 0; i < this.endPoints.length; i++) { 
          console.log('DEBUG: ' + this.endPoints[i].label); 
        }
        */
        
        var midpoint_index = Math.floor(this.endPoints.length / 2);
        
        // console.log('DEBUG: midpoint label = ' + this.endPoints[midpoint_index].label);
        
        cur = midpoint_index;
        while (cur >= 0) {
          // console.log('DEBUG: Fitting up ' + this.endPoints[cur].label);
          this.drawTextWithoutOverlap(this.endPoints[cur].x, this.endPoints[cur].y, this.endPoints[cur].label, 
              'graphAxisTitle', algaeGraph.enumNoOverlapStyle.upOnly);
          cur -= 1;
        }
        
        cur = midpoint_index + 1;
        while (cur < this.endPoints.length) {
          // console.log('DEBUG: Fitting down ' + this.endPoints[cur].label);
          this.drawTextWithoutOverlap(this.endPoints[cur].x, this.endPoints[cur].y, this.endPoints[cur].label, 
              'graphAxisTitle', algaeGraph.enumNoOverlapStyle.downOnly);
          cur += 1;
        }
      
      }
    
      // console.log('DEBUG: number of label points = ' + this.endPoints.length.toString());
      // console.log('DEBUG: midpoint_index = ' + midpoint_index.toString());
      // console.log('DEBUG: midpoint label = ' + this.endPoints[midpoint_index].label);
    }
    
    // this.showUsedAreas();
    
    return this;
  } 
 
  return algaeLineGraph;
})();
 