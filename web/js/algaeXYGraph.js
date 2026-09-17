
/**

  algae framework | XY graphs.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeXYGraph = (function() {
    
  /**
   * Constructor.
   */
  function algaeXYGraph() {
  // --------------------------------------------------------------------------
    algaeGraph.call(this);
    this.defaultSymbolSize = 15;
    this.symbolColorStyle = algaeGraph.enumColorOption.default;
    this.symbolColorPos = 3; // color position in the data array, i.e. [x,y,tooltip,color]
    this.symbolStylePos = 4; // symbol style position in the data array, i.e. [x,y,tooltip,color,symbol_number]
    this.groupVarNum = -1; // group variable number, set if applicable, used to color by a group field 
    this.groups = []; // array of group objects, created when groupVarNum is set
    this.defaultSymbol = algaeGraph.enumSymbol.circle;
    this.symbolStyle = algaeGraph.enumColorOption.default; // using enumColorOption just because settings of default/variable are also applicable here
  };
  
  /**
   * Setup prototype based inheritance
   */
  algaeXYGraph.prototype = Object.create(algaeGraph.prototype);
  
  /**
   * Get symbol fill color based on how the color options are set.
   */
  algaeXYGraph.prototype.getColor = function(data, defaultColor) {
  // --------------------------------------------------------------------------
    //
    // ----- just use the default color
    //
    if (this.symbolColorStyle === algaeGraph.enumColorOption.default) {
      return defaultColor;
    } else if (this.symbolColorStyle === algaeGraph.enumColorOption.variable) {
      //
      // ----- get color from a color variable
      //
      if (data.length > this.symbolColorPos) {
        return data[this.symbolColorPos];
      } else {
        console.log('Warning: Color at index ' + this.symbolColorPos.toString() + ' does not exist.');
      }
    } else if (this.symbolColorStyle === algaeGraph.enumColorOption.group) {
      //
      // ----- get color from a group text value
      //
      if ( (data.length > this.groupVarNum) && (this.groupVarNum >= 0) ) {
        
        let group = this.groups.find(group => group.name === data[this.groupVarNum]);
        if (group == null) {
          group = {
            "name": data[this.groupVarNum],
            "color": this.getNextColorInPalette(this.palette[2], this.groups.length),
            "num": 1
          }
          this.groups.push(group);
          return group.color;
        } else {
          group.num += 1;
          return group.color;
        }
      } else {
        console.log('Warning: Group index ' + this.groupVarNum.toString() + ' is invalid.');
      }
    }
    return defaultColor;
  }
  
  /**
   * Draw a data series.
   */
  algaeXYGraph.prototype.drawSeries = function(num, data, name, color, attributes) {  
  // --------------------------------------------------------------------------
    var that = this;
    if ( (typeof(this.xAxis) !== 'undefined') && (typeof(this.yAxis) !== 'undefined') &&
         (typeof(data) !== 'undefined') && (typeof(this.xAxis.scale) !== 'undefined') && 
         (typeof(this.yAxis.scale) !== 'undefined') ) {
      //
      // ----- setup size scaling based on data if needed
      //
      // this.setupSizeScale(data);
      var seriesName = name.split(" ").join("");
      
      // var regex = 's/^[^a-zA-Z_]+|[^a-zA-Z_0-9]+//g';
      
      // seriesName = seriesName.replace(regex, '');
      
      // name cannot start with a number thus the leading '_' +
      
      seriesName = '_' + seriesName.replace(/[&\/\\#, +()$~%.'":*?<>{}]/g, '_');
      
      var lineFunc = d3.line()
      .x(function(d) { return that.xAxis.scale(d[that.xAxis.varNum]); })
      .y(function(d) { return that.yAxis.scale(d[that.yAxis.varNum]); });
      
      //
      // ----- use line function to draw a line between the points
      //
      if ( (this.attributeExists(attributes, 'asLine')) && (attributes.asLine) ) {
        var line = this.svg.append('path')
        .attr('d', lineFunc(data))
        .attr('stroke', color)
        .attr('stroke-width', 1.5)
        .attr('series', num)
        .attr('fill', 'none');
      }
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
      //
      // ----- draw series, symbols at xy points
      //
      if (symbol != algaeGraph.enumSymbol.none) {
        this.svg.selectAll(seriesName)
          .data(data)
          .enter()
          .append("path")
          // .filter(function(d) { return that.isOnGraph(d[that.xAxis.varNum], d[that.yAxis.varNum]); }) 
          .attr("class", "graphBlackBorder")
          .attr('series', num)
          .attr("transform", function(d) { return "translate(" + that.xAxis.scale(d[that.xAxis.varNum]).toString() + "," + that.yAxis.scale(d[that.yAxis.varNum]).toString() + ")"; })
          .attr("d", function(d) { 
            if ((that.symbolStyle == algaeGraph.enumColorOption.variable) && (that.symbolStylePos < d.length)) { symbol = d[that.symbolStylePos]; }
            return d3.symbol().type(that.getSymbolDefinition(symbol)).size(symbolSize)(); 
          })
          .style("fill", function(d) { return that.getColor(d, color); })
          .attr("visibility", function(d) { if (d[that.yAxis.varNum] == null) { return 'hidden' } else { return 'visible'; } })
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
           })
          .on("click", function(d) { that.clickHandler(that.clickURLPrefix + d[that.clickURLSuffixVarNum].toString()); });
      }
    }
  }
  
  algaeXYGraph.prototype.buildGroups = function() {
  // --------------------------------------------------------------------------
    // console.log('DEBUG: this.data.length = ' + this.data.length.toString());
    // console.log('DEBUG: this.data[0][0].length = ' + this.data[0][0].length.toString());
    if ( (this.symbolColorStyle === algaeGraph.enumColorOption.group) && (this.data.length == 1) ) {
      
      if ( (this.data[0][0].length > this.groupVarNum) && (this.groupVarNum >= 0) ) {
        let raw_groups = [];
        let data = this.data[0];
        //
        // ----- set null (missing) group values to a specific value so they can be counted like regular values
        //
        for (i = 0; i < data.length; i++) {
          if (data[i][this.groupVarNum] == null) {
            data[i][this.groupVarNum] = this.nameForNullValues;
          }
        }
        //
        // ----- get array of unique group names with counts of how many items are in each group
        //
        for (i = 0; i < data.length; i++) {
          let group = raw_groups.find(group => group.name === data[i][this.groupVarNum]);
          if (group == null) {
            if (data[i][this.groupVarNum] == null) {
              console.log('DEBUG: Adding null group name.');
            }
            if (data[i][this.groupVarNum] != null) {
              group = {
                "name": data[i][this.groupVarNum],
                "num": 1
              }
              raw_groups.push(group);
            }
            // console.log('DEBUG: Added group ' + group.name + '.');
          } else {
            group.num += 1;
          }
        }
        // console.log('DEBUG: Number of raw groups = ' + raw_groups.length.toString());        
        //
        // ----- sort array of objects by two fields, first num then name
        //
        raw_groups.sort(function (a, b) {
            let first = b.num - a.num;
            return first ? first : a.name.localeCompare(b.name);
          }
        );
        //
        // ----- determine final number of groups
        //
        let num_final_groups = raw_groups.length;
        if (num_final_groups > this.palette[this.defaultPaletteNum].length) {
          num_final_groups = this.palette[this.defaultPaletteNum].length + 1; // add 1 for 'Other'
        }
        // console.log('DEBUG: Number of final groups = ' + num_final_groups.toString());
        //
        // ----- create new data series for each group
        //       also set series name and color
        //
        for (i = 0; i < num_final_groups; i++) {
          this.data.push([]);
          if (i < num_final_groups - 1) {
            this.dataName.push(raw_groups[i].name);
          } else {
            this.dataName.push('Other');
          }
          this.dataColor.push(this.getNextColorInPalette(this.palette[this.defaultPaletteNum], i));
        }
        // console.log('DEBUG: this.data.length after adding groups = ' + this.data.length.toString());
        //
        // ----- separate data into new data serieses (groups)
        //
        for (i = 0; i < data.length; i++) {
          let index = raw_groups.findIndex(group => group.name === data[i][this.groupVarNum]);
          if ( (index == -1) || (index >= num_final_groups - 1) ) {
            this.data[this.data.length - 1].push(data[i]);  // add to 'Other'
          } else {
            this.data[index + 1].push(data[i]);
          }
        }
        //
        // ----- remove the original this.data[0] series
        //
        this.data.shift();
        this.dataName.shift();
        this.dataColor.shift();
        //
        // ----- debugging
        //
        /*
        for (i = 0; i < this.data.length; i++) {
          console.log('DEBUG: this.data[' + i.toString() + '].length = ' + this.data[i].length.toString());
          console.log('DEBUG: dataName[' + i.toString() + '] = ' + this.dataName[i]);
        }
        */
        /*
        for (i = 0; i < this.dataName.length; i++) {
          console.log('DEBUG: dataName[' + i.toString() + '] = ' + this.dataName[i]);
          console.log('DEBUG: dataColor[' + i.toString() + '] = ' + this.dataColor[i]);
        }
        */
        /*
        for (i = 0; i < raw_groups.length; i++) {
          console.log('DEBUG: ' + raw_groups[i].name + ' ' + raw_groups[i].num.toString());
        }
        */
      } else {
        alert('Group index ' + this.groupVarNum.toString() + ' is invalid.');
      }
    }
  }
  
  /**
   * Draw the graph.
   */
  algaeXYGraph.prototype.draw = function() {
  // --------------------------------------------------------------------------
    //
    //
    //
    this.buildGroups();
    //
    // ----- call base class to draw the graph
    //
    algaeGraph.prototype.draw.call(this);
  }
 
  return algaeXYGraph;
})();
 