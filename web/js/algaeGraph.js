
/**

  algae framework | Graphs base class.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeGraph = (function() {
	
  algaeGraph.enumAutoManual = Object.freeze({"automatic":0, "manual":1});
  
  //
  // ----- must match the PHP class algaeGraph
  //       used for symbols on the graph and in a legend
  //
  algaeGraph.enumSymbol = Object.freeze({"none":0, "rectangle":1, "line":2, "circle":3, 
    "cross":4, "diamond":5, "square":6, "triangle":7, "star":8, "wye":9, "triangleDown":10, "squareDiamond":11});
  
  //
  // ----- must match the PHP class algaeGraph, constants: COLOR_DEFAULT, COLOR_VARIABLE, COLOR_GROUP
  //
  algaeGraph.enumColorOption = Object.freeze({"default":0, "variable":1, "group":2});
  
  //
  // ----- numbers much match the PHP class algaeGraphLegendItem
  //
  algaeGraph.enumLineStyle = Object.freeze({"solid":0, "dashed":1, "dotted":2});
  
  //
  // ----- fit styles for non-overlapping text
  //
  algaeGraph.enumNoOverlapStyle = Object.freeze({"ellipse":0, 
    "upOnly":1, "downOnly":2, "upAndDown":3, 
    "leftOnly":4, "rightOnly":5, "leftAndRight":6});
  
  //
  // ----- units for mouse coordinates
  //
  algaeGraph.enumCoordinateUnits = Object.freeze({"screen":0, "data":1});
  
  //
  // ----- setup initially to support axis mouse cooordinates formatting
  //
  algaeGraph.enumDataType = Object.freeze({"numeric":0, "currency":1, "text":2, "date":3});
  
  /**
   * Constructor.
   */
  function algaeGraph() {
  // --------------------------------------------------------------------------
    //
    // ----- setup
    //
	  this.divId = 'algaeGraph';
    this.width = 900;
    this.height = 500;
    this.margin = {top: 35, right: 20, bottom: 55, left: 60};
    this.mainTitle = 'Main Title';
    this.subTitle = '';
    this.showTitle = true;
    this.showFootprint = false;
    this.clip = true;
    this.avoidOverlaps = true;
    this.noOverlapStyle = algaeGraph.enumNoOverlapStyle.upAndDown;
    this.labelOffset = 2;
    this.shapeBuffer = 1;
    this.edgeBuffer = 10;
    this.tooltipPos = 2; // tooltip position in the data array, i.e. [x,y,tooltip]
    this.textMargin = {x:2, y:3};  // text margins, typically used for bounding boxes and overlap avoidance
    this.nameForNullValues = '[Null]';  // name to use for null values, for example when a group name is missing
    //
    // ----- color palettes
    //       1) https://colorbrewer2.org/#type=qualitative&scheme=Paired&n=12
    //       2) https://colorbrewer2.org/#type=qualitative&scheme=Set1&n=9
    //       3) https://colorbrewer2.org/#type=qualitative&scheme=Accent&n=6
    //
    this.palette = [
        ['#a6cee3','#1f78b4','#b2df8a','#33a02c','#fb9a99','#e31a1c','#fdbf6f','#ff7f00','#cab2d6','#6a3d9a','#ffff99','#b15928'],
        ['#e41a1c','#377eb8','#4daf4a','#984ea3','#ff7f00','#ffff33','#a65628','#f781bf','#999999'],
        ['#7fc97f','#beaed4','#fdc086','#ffff99','#386cb0','#f0027f']
      ];
    this.otherColor = '#dcdcdc'; // color for 'other' data when all palette colors have been used
    this.defaultPaletteNum = 2;  // default palette number, array index into this.palette
    //
    // ----- axes
    //
    this.xAxis = new algaeGraphNumericAxis(algaeGraphAxis.enumDirection.x);
    this.yAxis = new algaeGraphNumericAxis(algaeGraphAxis.enumDirection.y);
    //
    // ----- data
    //
    this.data = [];
    this.dataName = [];
    this.dataColor = [];
    this.dataAttributes = [];
    //
    // ----- legend
    //
    this.legend = true;
    this.legendAuto = false;
    this.legendText = [];
    this.legendStartingY = 15;
    this.legendCurY = this.legendStartingY;
    this.legendLineHeight = 20;
    this.legendBoxSize = 13;
    this.legendBoxMultiplier = 2.75;  // make this smaller, for a tighter box around the legend or less spacing to the margins
    this.legendTextMinWidth = 100;
    this.legendTextMaxWidth = 0;
    this.legendPositions = Object.freeze({"upperLeft":1, "upperRight":2, "lowerRight":3, "lowerLeft":4, "none":5});
    this.legendPosition = this.legendPositions.upperRight;  
    this.legendInMargin = false;
    this.legendData = [];
    //
    // ----- mouse coordinates
    //
    this.showMouseCoordinateX = false;
    this.showMouseCoordinateY = false;
    this.mouseCoordinatesPosition = this.legendPositions.upperLeft; 
    this.mouseCoordinateUnits = algaeGraph.enumCoordinateUnits.data;
    
    //
    // ----- clicking on an item to forward
    //
    this.clickForward = false;
    this.clickURLPrefix = '';
    this.clickURLSuffixVarNum = 3;
    //
    // ----- internals
    //
    this.svg = null;
    this.graphRect = null;
    this.coordsText = null;
    this.innerWidth = 0;
    this.innerHeight = 0;
    this.tooltip;
    this.debug = false;
    this.usedArea = [];
    this.overlapAmount = 0;  // set be getOverlapAmount(), can be used by callers to help find the least amount of overlap
    return this;
  }
  
  /**
   * Graph initialization, create SVG drawing area.
   */
  algaeGraph.prototype.init = function() {
  // --------------------------------------------------------------------------  
    let that = this;
    if (this.subTitle.length > 0) this.margin.top += 15;
    this.innerWidth = this.width - this.margin.left - this.margin.right;
    this.innerHeight = this.height - this.margin.top - this.margin.bottom;
    //
    // ----- create svg drawing area
    //
    this.svg = d3.select('#' + this.divId)
                .append("svg")
                .attr("width", this.width)
                .attr("height", this.height)
                .append("g")
                .attr("transform", "translate(" + this.margin.left + "," + this.margin.top + ")");
    //
    // ----- mouse coordinates
    //
    this.addMouseCoordsRect(true);
    //
    // ----- add div for a tooltip
    //
    if (typeof(this.tooltip) === 'undefined') {
      this.tooltip = d3.select("body")
        .append("div")
        .attr("class", "graphTooltip")
        .style("position", "absolute")
        .style("z-index", "10000")
        .style("visibility", "hidden");
    }
    //
    // ----- mark no-label areas
    //
    this.usedArea = [];
    this.setNoLabelAreas();
    return this;
  }
  
  /**
   * Get the next color in a palette.
   * @param {array} palette Array of palette colors, i.e. this.palette[2] to use palette 3.
   * @param {numUsed} integer Number of colors already used.
   * @returns {string} String with html color or this.otherColor if all palette entries have already been used.
   */
  algaeGraph.prototype.getNextColorInPalette = function(palette, numUsed) {
    // --------------------------------------------------------------------------
      if (numUsed < palette.length) {
        return palette[numUsed];
      } else {
        return this.otherColor;
      }
    }
  
  /**
   * Add a rectangle to the SVG drawing area to show mouse coordinates in.
   * This is often done last so the mouse coordinates are on top of everything else.
   */
  algaeGraph.prototype.addMouseCoordsRect = function(includeTrackingRect) {
  // --------------------------------------------------------------------------  
    let that = this;
    //
    // ----- mouse coordinates
    //
    if (this.showMouseCoordinateX || this.showMouseCoordinateY) {
      //
      // ----- setup text area to display the coordinates in
      //       offset and set text justification from one of the corners
      //
      let coordsTextOffset = 5;
      let coordsTextXPos = coordsTextOffset;
      let coordsTextYPos = coordsTextOffset;
      let coordsTextHorizPos = 'start';
      let coordsTextVertPos = 'hanging';
      if (this.mouseCoordinatesPosition == this.legendPositions.upperRight) {
        coordsTextXPos = this.innerWidth - coordsTextOffset;
        coordsTextYPos = coordsTextOffset;
        coordsTextHorizPos = 'end';
        coordsTextVertPos = 'hanging';
      } else if (this.mouseCoordinatesPosition == this.legendPositions.lowerLeft) {
        coordsTextXPos = coordsTextOffset;
        coordsTextYPos = this.innerHeight - coordsTextOffset;
        coordsTextHorizPos = 'start';
        coordsTextVertPos = 'bottom';
      } else if (this.mouseCoordinatesPosition == this.legendPositions.lowerRight) {
        coordsTextXPos = this.innerWidth - coordsTextOffset;
        coordsTextYPos = this.innerHeight - coordsTextOffset;
        coordsTextHorizPos = 'end';
        coordsTextVertPos = 'bottom';
      }
      this.coordsText = this.drawText(coordsTextXPos, coordsTextYPos, '', 'graphAxisTitle', 
          coordsTextHorizPos, coordsTextVertPos, true);
      //
      // ----- make invisible rect over graph area so a mousemove handler can be attached
      //       to track and update mouse coordinates
      //
      if (includeTrackingRect) {
        this.graphRect = this.svg.append("rect")
          .attr("x", 0)
          .attr("y", 0)
          .attr("fill-opacity", 0)
          .attr("width", this.innerWidth)
          .attr("height", this.innerHeight)
          .on("mousemove", function() {
            let coordinates = d3.mouse(this);
            that.updateCoordinatesDisplay(coordinates);
            // that.drawLine(coordinates[0], 0, coordinates[0], that.height, 'white', 1);
          })
          .on("mouseover", function() {
            let coordinates = d3.mouse(this);
            // that.drawLine(coordinates[0], 0, coordinates[0], that.height, 'black', 1);
          });
      }
    }
    return this;
  }
  
  algaeGraph.prototype.updateCoordinatesDisplay = function(coordinates) {
  // --------------------------------------------------------------------------
    if (this.coordsText != null) {
      let newText = '';
      let textX = '';
      let textY = '';
      if ( (this.mouseCoordinateUnits == algaeGraph.enumCoordinateUnits.data) && 
           (this.xAxis.scale != null) && (this.yAxis.scale != null) ) {
        if (this.showMouseCoordinateX) {textX = this.xAxis.getInvertedValueAsString(coordinates[0]); }
        if (this.showMouseCoordinateY) {textY = this.yAxis.getInvertedValueAsString(coordinates[1]); }
      } else {
        textX = Math.round(coordinates[0]).toString();
        textY = Math.round(coordinates[1]).toString();
      }
      if (this.showMouseCoordinateX) {
        newText = textX;
      }
      if (this.showMouseCoordinateY) {
        if (this.showMouseCoordinateX) {
          newText += ' | ';
        }
        newText += textY;
      }
      this.coordsText.text(newText);
    }
  }
  
  /**
   * Custom triangle down symbol.
   * From: https://github.com/YellowTugboat/d3-symbol-extra/blob/master/src/triangle.js
   */
  algaeGraph.triangleDown = {
    draw: function(context, size) {
      var sqrt3 = Math.sqrt(3);
      var y = -Math.sqrt(size / (sqrt3 * 3));
      context.moveTo(0, -y * 2);
      context.lineTo(-sqrt3 * y, y);
      context.lineTo(sqrt3 * y, y);
      context.closePath();
    }
  };

  /**
   * Custom triangle down symbol.
   * From: https://github.com/YellowTugboat/d3-symbol-extra/blob/master/src/triangle.js
   */
  algaeGraph.squareDiamond = {
    draw: function(context, size) {
      var half = size / 2;
      context.moveTo(0, half);
      context.lineTo(half, 0);
      context.lineTo(0, -half);
      context.lineTo(-half, 0);
      context.closePath();
    }
  };
  
  /**
   * Check if a JSON object and a specific attribute exist.
   * Example: this.attributeExists(attributes, 'symbolSize')
   */
  algaeGraph.prototype.attributeExists = function(json, attribute) {
  // --------------------------------------------------------------------------
    if ( (typeof(json) !== 'undefined') && (attribute in json) ) {
      return true;
    }
    return false;
  }
  
  /**
   * Get the symbol definition that actually draws the symbol for a given symbol number.
   */
  algaeGraph.prototype.getSymbolDefinition = function(symbolNum) {
  // --------------------------------------------------------------------------
    switch(symbolNum) {
      case algaeGraph.enumSymbol.none: return null;
      case algaeGraph.enumSymbol.rectangle: return d3.symbolSquare;  // TODO: make a rectangle symbol
      case algaeGraph.enumSymbol.line: return d3.symbolSquare; // TODO: make a line symbol
      case algaeGraph.enumSymbol.circle: return d3.symbolCircle;
      case algaeGraph.enumSymbol.cross: return d3.symbolCross;
      case algaeGraph.enumSymbol.diamond: return d3.symbolDiamond;
      case algaeGraph.enumSymbol.square: return d3.symbolSquare;
      case algaeGraph.enumSymbol.triangle: return d3.symbolTriangle;
      case algaeGraph.enumSymbol.star: return d3.symbolStar;
      case algaeGraph.enumSymbol.wye: return d3.symbolWye;
      case algaeGraph.enumSymbol.triangleDown: return algaeGraph.triangleDown;
      case algaeGraph.enumSymbol.squareDiamond: return algaeGraph.squareDiamond;
      default: alert('Symbol ' + symbolNum.toString() + ' not defined.');
    }
    return null;
  }
  
  /**
   * Set no-label areas.
   */
  algaeGraph.prototype.setNoLabelAreas = function() {
  // --------------------------------------------------------------------------
    this.setEdgeAreasUsed();
    return this;
  }
  
  /**
   * Add a data series.
   * @param attributes JSON object with additional attributes.
   */
  algaeGraph.prototype.addData = function(data, name, color, attributes) {
  // --------------------------------------------------------------------------
    this.data.push(data);
    this.dataName.push(name);
    this.dataColor.push(color);
    this.dataAttributes.push(attributes);
    return this;
  }
  
  /**
   * Remove any data attached to the graph.
   */
  algaeGraph.prototype.removeData = function() {
  // --------------------------------------------------------------------------  
    this.data = [];
    this.dataName = [];
    this.dataColor = [];
    this.dataAttributes = [];
    return this;
  } 
  
  /**
   * Determine is a point is on the graph.  If clip is false this will always return true.
   */
  algaeGraph.prototype.isOnGraph = function(x, y) {
  // --------------------------------------------------------------------------
    if (this.clip) {
      if ( (typeof(this.xAxis) !== 'undefined') && (typeof(this.yAxis) !== 'undefined') ) {
        if ( (this.xAxis.isInRange(x)) && (this.yAxis.isInRange(y)) ) {
          return true;
        }
        return false;
      }
    }
    return true;
  }
  
  /**
   * Draw outlined rectangular area for the graph.
   */
  algaeGraph.prototype.drawArea = function(fillOpacity) {
  // --------------------------------------------------------------------------
    var opacity = 100;
    if (arguments.length === 1) opacity = fillOpacity;
    this.svg.append("rect")
    .attr("class", "graphArea")
    .attr("x", 1/2)
    .attr("y", 1/2)
    .attr("fill-opacity", opacity)
    .attr("width", this.innerWidth)
    .attr("height", this.innerHeight);
    return this;
  }

  /**
   * Draw the graph footprint including the graph area and margins.
   */
  algaeGraph.prototype.drawFootprint = function() {
  // --------------------------------------------------------------------------
    let footprintColor = '#F19C38';
    this.drawRectangle(0, 0, this.innerWidth, this.innerHeight, footprintColor, '#00000000');
    this.drawRectangle(-this.margin.left, -this.margin.top, this.innerWidth + this.margin.left + this.margin.right, 
      this.innerHeight + this.margin.top + this.margin.bottom, footprintColor, '#00000000');
  }
  
  /**
   * Mark an area of the graph as being used.  This is to enable automatic relocation of
   * labels (and other objects) to avoid overprinting.
   */
  algaeGraph.prototype.setEdgeAreasUsed = function() {
  // --------------------------------------------------------------------------
    if (this.edgeBuffer > 0) {
      this.saveUsedArea(0, 0, this.edgeBuffer, this.innerHeight);
      this.saveUsedArea(this.innerWidth - this.edgeBuffer, 0, this.innerWidth, this.innerHeight); 
      this.saveUsedArea(0, 0, this.innerWidth, this.edgeBuffer);
      this.saveUsedArea(0, this.innerHeight - this.edgeBuffer, this.innerWidth, this.innerHeight);
    }
    return this;
  } 
  
  /**
   * Mark an area of the graph as being used.  This is to enable automatic relocation of
   * labels (and other objects) to avoid overprinting.
   */
  algaeGraph.prototype.saveUsedArea = function(x1, y1, x2, y2) {
  // --------------------------------------------------------------------------
    if ( (! Number.isNaN(x1)) && (! Number.isNaN(y1)) && (! Number.isNaN(x2)) && (! Number.isNaN(y2)) ) {
      
      this.usedArea.push([Math.min(x1, x2), Math.min(y1, y2), Math.max(x1, x2), Math.max(y1, y2)]); 
    }
    return this;
  }
  
  /**
   * Mark an area of the graph as being used as defined by a bounding box.
   */
  algaeGraph.prototype.saveUsedBoundingBox = function(boundingBox) {
  // --------------------------------------------------------------------------
    this.saveUsedArea(boundingBox.x, boundingBox.y, 
        boundingBox.x + boundingBox.width, boundingBox.y + boundingBox.height); 
    return this;
  }
  
  /**
   * Save the area used by a symbol to avoid overprinting text.
   */
  algaeGraph.prototype.saveUsedSymbolArea = function(x, y, size) {
  // --------------------------------------------------------------------------
    var halfSize = size / 2;
    this.saveUsedArea(x - halfSize, y - halfSize, x + halfSize, y + halfSize); 
    return this;
  }
  
  /**
   * Show the used areas.  This is largely for debug purposes.
   */
  algaeGraph.prototype.showUsedAreas = function() {
  // --------------------------------------------------------------------------
    var len = this.usedArea.length;
    for (var i=0; i < len; i++) {
      this.svg.append("rect")
          .attr("stroke", "red")
          .attr("stroke-width", "1")
          .attr("x", this.usedArea[i][0])
          .attr("y", this.usedArea[i][1])
          .attr("width", this.usedArea[i][2] - this.usedArea[i][0])
          .attr("height", this.usedArea[i][3] - this.usedArea[i][1])
          .style("fill", "none");
    }
    return this;
  } 
  
  /**
   * Get bounding box for text respecting horizontal and vertical justification settings.
   * @return object Object containing upper left (x,y), width, and height of bounding box all in graph units.
   * For example to draw the bounding box: this.drawRectangle(bbox.x, bbox.y, bbox.width, bbox.height, 'red', 'none');
   */
  algaeGraph.prototype.getTextBoundingBox = function(x, y, text, cssClass, horizAnchor, vertAnchor) {
  // --------------------------------------------------------------------------
    var bbox = {};
    var fudge = 2;
    bbox.x = x;
    bbox.y = y;
    //
    // ----- draw text, get width and height then remove text
    //
    textObj = this.drawText(x, y, text, cssClass, horizAnchor, vertAnchor, false);
    bbox.height = textObj.node().getBBox().height * 1.0;
    bbox.width = textObj.node().getBBox().width;
    textObj.remove();
    //
    // ----- adjust horizontal bounding box position
    //
    if (horizAnchor.toUpperCase() === 'MIDDLE') {
      bbox.x -= (bbox.width / 2);
    } else if (horizAnchor.toUpperCase() === 'END') {
      bbox.x -= bbox.width;
    } else {
      // no change
    }
    //
    // ----- adjust vertical bounding box position
    //
    if ((vertAnchor.toUpperCase() === 'MIDDLE') || (vertAnchor.toUpperCase() === 'CENTRAL')) {
      bbox.y -= (bbox.height / 2);
    } else if (vertAnchor.toUpperCase() === 'HANGING') {
      bbox.y -= fudge;
    } else {
      bbox.y -= (bbox.height - fudge);
    }    
    //
    // ----- debugging
    //
    // this.drawRectangle(bbox.x, bbox.y, bbox.width, bbox.height, 'red', 'none');
    // this.drawSymbol(x, y, algaeGraph.enumSymbol.circle, 20, 'red', 'none');
    // textObj.remove();
    return bbox;
  }
  
  /**
   * Get the area of overlap between two rectangles.  Notes:
   * - y increases down
   * - rectangle coordinates are in an array where the order is x1,y1,x2,y2
   * - y2 should be > y1
   * This solution is from: http://jsfiddle.net/uthyZ/
   */
  algaeGraph.prototype.getOverlapAmount = function(rect1, rect2) {
  // --------------------------------------------------------------------------
    var x_overlap = Math.max(0, Math.min(rect1[2], rect2[2]) - Math.max(rect1[0], rect2[0]));
    var y_overlap = Math.max(0, Math.min(rect1[3], rect2[3]) - Math.max(rect1[1], rect2[1]));
    this.overlapAmount = x_overlap * y_overlap;
    return this.overlapAmount;
  } 
  
  /**
   * Check if a proposed area overlaps anything else or if it's 
   * outside the graph area.
   */
  algaeGraph.prototype.isOverlapping = function(x1, y1, x2, y2) {
  // --------------------------------------------------------------------------
    //
    // ----- check if outside graph area, this is considered an overla
    //
    /* commented out to allow labels in margins for line graphs
    if (! this.labelsOutsideGraphArea) {
      if ( (x1 < 0) || (x2 < 0) || (x1 > this.innerWidth) || (x2 > this.innerWidth) ||
           (y1 < 0) || (y2 < 0) || (y1 > this.innerHeight) || (y2 > this.innerHeight) ) {
        return true;
      }
    }
    */
    this.overlapAmount = 0;
    //
    // ----- check for overlap of used areas
    //
    var len = this.usedArea.length;
    for (var i=0; i < len; i++) {
      this.overlapAmount += this.getOverlapAmount(this.usedArea[i], [x1 - this.shapeBuffer, y1 - this.shapeBuffer, 
        x2 + this.shapeBuffer, y2 + this.shapeBuffer]);
      /*
      if (this.getOverlapAmount(this.usedArea[i], [x1 - this.shapeBuffer, y1 - this.shapeBuffer, x2 + this.shapeBuffer, y2 + this.shapeBuffer]) > 0) {
        return true;
      }
      */
    }
    if (this.overlapAmount > 0) {
      return true;
    }
    return false;
  }
  
  /**
   * Check if a bounding box is overlapping any of the used graph areas.
   */
  algaeGraph.prototype.isOverlappingBoundingBox = function(boundingBox) {
  // --------------------------------------------------------------------------
    return this.isOverlapping(boundingBox.x, boundingBox.y, 
        boundingBox.x + boundingBox.width, boundingBox.y + boundingBox.height); 
  }
  
  /**
   * Check key locations around a point to see if the label will fit.
   */
  algaeGraph.prototype.getIdealLabelPosition = function(centerX, centerY, offset, width, height) {
  // --------------------------------------------------------------------------
    var x, y;
    // offset = (this.getSize(data, false) / 2) + this.labelOffset;
    // centerX = this.xAxis.scale(data.x);
    // centerY = this.yAxis.scale(data.y);
    //
    // ----- check upper right of symbol
    //
    /*
    console.log('###: In getIdealLabelPosition centerX, centerY, offset, width, height = ' 
        + centerX.toString() + ', '
        + centerY.toString() + ', '
        + offset.toString() + ', '
        + width.toString() + ', '
        + height.toString()
        );
    */
    //
    // ----- starting point
    //
    x = centerX;
    y = centerY;
    if (! this.isOverlapping(x, y - height, x + width, y)) {
      console.log('DEBUG: Fits at starting point.');
      return [x,y];
    }  
    //
    // ----- starting point plus offset
    //
    x = centerX + offset;
    y = centerY - offset;
    if (! this.isOverlapping(x, y - height, x + width, y)) {
      console.log('DEBUG: Fits at starting point plus offset.');
      return [x,y];
    }     
    //
    // ----- check to right of symbol
    //
    x = centerX + offset + (this.labelOffset * 2);
    y = centerY + (height / 2);
    if (! this.isOverlapping(x, y - height, x + width, y)) {
      return [x,y];
    }
    //
    // ----- check at top of symbol
    //
    x = centerX - (width / 2);
    y = centerY - offset - this.labelOffset;
    if (! this.isOverlapping(x, y - height, x + width, y)) {
      return [x,y];
    }    
    //
    // ----- check lower right of symbol
    //
    x = centerX + offset;
    y = centerY + offset + height;
    if (! this.isOverlapping(x, y - height, x + width, y)) {
      return [x,y];
    }    
    //
    // ----- check at bottom of symbol
    //
    x = centerX - (width / 2);
    y = centerY + offset + height + this.labelOffset;
    if (! this.isOverlapping(x, y - height, x + width, y)) {
      return [x,y];
    }      
    //
    // ----- check upper left of symbol
    //
    x = centerX - offset - width;
    y = centerY - offset;
    if (! this.isOverlapping(x, y - height, x + width, y)) {
      return [x,y];
    }     
    //
    // ----- check left of symbol
    //
    x = centerX - offset - width - (this.labelOffset * 2);
    y = centerY + (height / 2);
    if (! this.isOverlapping(x, y - height, x + width, y)) {
      return [x,y];
    }      
    //
    // ----- check lower left of symbol
    //
    x = centerX - offset - width;
    y = centerY + offset + height;
    if (! this.isOverlapping(x, y - height, x + width, y)) {
      return [x,y];
    }      
    return null;
  } 
  
  /*
  -------------------------------------------------------------------------
   CMapMask, fit a text box into the map area using the mask array
  -------------------------------------------------------------------------
   The algorithim uses an ellipse through the centroid of the label
   extents rectangle, ellipse axis is longer in X to account for the 
   label width.  Irregardless of the original text location the search
   starts to the right of the "symbol" and moves to the left, checking
   the top and bottom until a "clean" location is found to insert into.
  -------------------------------------------------------------------------
  */
  algaeGraph.prototype.fitText = function(centerX, centerY, offset, width, height) {
  // --------------------------------------------------------------------------
    var dUsed = 0.0;
    var bDone  = false;
    var bMoved = false;
    var dHeight = height;
    var dWidth  = width;
    var dOffset = offset + this.labelOffset;
    //
    // ----- a = 1/2 the long (horizontal) axis of the ellipse
    //       b = 1/2 the short (vertical) axis of the ellipse
    //
    var a = dOffset + (dWidth / 2.0);
    var b = (dOffset * 1.5) + (dHeight / 2.0);
    var dStep = 0.0;
    var h = dHeight / 2.0;
    var nLoop = 0;
    var ep = {};
    var ip = {};
    //C3dPoint ep;
    //C3dPoint ip;
    //C3dPoint ptMin;
    //C3dPoint ptMax;
    //
    // ----- to save best position if nothing works
    //
    //C3dPoint ptBestMin;
    //C3dPoint ptBestMax;
    //C3dPoint ptBestIP;
    //double   dBestUsed = 200.0;
    if (true)
    {
      //
      // ----- first just see if it fits in default location
      //
      if (true)
      {
        //
        // ----- didn't fit, move around till it does
        //       this loops in increasingly large ellipses working out
        //
        while ((! bDone) && (nLoop < 30))
        {
          // dStep = a - sqrt((1 - ((h * h) / (b * b))) * (a * a));
          dStep = a * 2.0 / 20.0;
          ep.x = a;
          //
          // ----- check positions on the current ellipse
          //
          while ((! bDone) && (ep.x >= -a))
          {
            //
            // ----- check upper position
            //
            ep.y = Math.sqrt((1 - (ep.x * ep.x) / (a * a)) * (b * b));
            ip.x = centerX + ep.x - (width / 2);
            ip.y = centerY - ep.y + (height / 2);
            // console.log('###: ip.x = ' + ip.x.toString() + ' ip.y = ' + ip.y.toString() + ' ep.x = ' + ep.x.toString() + ' ep.y = ' + ep.y.toString());
            
            if (this.debug) {
              this.svg.append("rect")
              .attr("stroke", "red")
              .attr("stroke-width", "1")
              .attr("x", ip.x)
              .attr("y", ip.y - height)
              .attr("width", width)
              .attr("height", height)
              .style("fill", "none");
            }
            
            if (! this.isOverlapping(ip.x, ip.y - height, ip.x + width, ip.y))
            {
              return {x:ip.x, y:ip.y, nLoops:nLoop};
            }
              //
              // ----- check lower position
              //
  
              ip.y = centerY + ep.y + (height / 2);
              if (this.debug) {
              this.svg.append("rect")
              .attr("stroke", "red")
              .attr("stroke-width", "1")
              .attr("x", ip.x)
              .attr("y", ip.y - height)
              .attr("width", width)
              .attr("height", height)
              .style("fill", "none");
          }
              
              if (! this.isOverlapping(ip.x, ip.y - height, ip.x + width, ip.y))
              {
                return {x:ip.x, y:ip.y, nLoops:nLoop};
              }
  
            ep.x -= dStep;
          }
          //
          // ----- goto next larger ellipse if needed
          //
          if (! bDone)
          {
            a += (dHeight * 1.25);
            b += (dHeight * 1.25);
            nLoop++;
          }
        }
      }
    }
    return null;
  } 
    
  algaeGraph.prototype.fitTextVerticallyOnly = function(boundingBox, fitStyle) {
  // --------------------------------------------------------------------------
    var maxLoops = 30;
    var fit = false;
    var curLoop = 0;
    let tempBBox = boundingBox;
    let offset = tempBBox.height * 0.25;
    
    while ((! fit) && (curLoop < maxLoops)) {
      if (! this.isOverlappingBoundingBox(tempBBox)) {
        return {BBox:tempBBox, nLoops:curLoop};
      }
      if ( (fitStyle == algaeGraph.enumNoOverlapStyle.upAndDown) || (fitStyle == algaeGraph.enumNoOverlapStyle.upOnly) ) {
        tempBBox.y -= offset;
      } else if ( (fitStyle == algaeGraph.enumNoOverlapStyle.upAndDown) || (fitStyle == algaeGraph.enumNoOverlapStyle.downOnly) ) {
        tempBBox.y += offset;
      }
      curLoop += 1;
    }
        
    return null;
  } 
  
  /**
   * Get an open space by looking up and down vertically.
   * @param boundingBox A bounding box object to find space for positioned at a starting point.
   * boundingBox contains {x, y, width, height} where x, y, is the upper left corner.
   * @return A new bounding box that fits in an open space, or null if no space is available.
   */
  algaeGraph.prototype.getSpaceVertically = function(boundingBox) {
  // --------------------------------------------------------------------------
    var offset = boundingBox.height * 0.25;
//    console.log('DEBUG: ----');
//    console.log('DEBUG: boundingBox.y = ' + boundingBox.y.toString());
//    console.log('DEBUG: boundingBox.height = ' + boundingBox.height.toString());
//    console.log('DEBUG: offset = ' + offset.toString());
//    console.log('DEBUG: this.innerHeight = ' + this.innerHeight.toString());
    // var checkBox = JSON.parse(JSON.stringify(boundingBox));
    var checkBox = Object.assign({}, boundingBox);
    //
    // ----- requested area is open, return it
    //
    if (! this.isOverlappingBoundingBox(boundingBox)) {
      return boundingBox;
    }
    //
    // ----- check up, theory is do this first since it looks better
    //
    if ((this.noOverlapStyle == algaeGraph.enumNoOverlapStyle.upAndDown) || (this.noOverlapStyle == algaeGraph.enumNoOverlapStyle.upOnly)) {
      checkBox.y -= offset;
      while (checkBox.y > 0) {
        // this.drawRectangle(checkBox.x, checkBox.y, checkBox.width, checkBox.height, '#00FF00', '#FFFFFF');
        if (! this.isOverlappingBoundingBox(checkBox)) {
          return checkBox;
        }
        checkBox.y -= offset;
      }
    }
    //
    // ----- check down
    //
    checkBox = Object.assign({}, boundingBox);
    // console.log('DEBUG: checkBox.y = ' + checkBox.y.toString());
    if ((this.noOverlapStyle == algaeGraph.enumNoOverlapStyle.upAndDown) || (this.noOverlapStyle == algaeGraph.enumNoOverlapStyle.downOnly)) {
      checkBox.y += offset;
      // console.log('DEBUG: checkBox.y = ' + checkBox.y.toString());
      while (checkBox.y < this.innerHeight) {
        // this.drawRectangle(checkBox.x, checkBox.y, checkBox.width, checkBox.height, '#0000FF', '#FFFFFF');
        if (! this.isOverlappingBoundingBox(checkBox)) {
          return checkBox;
        }
        checkBox.y += offset;
      }
    }
    return null;
  } 
  
  /**
   * Get an open space by looking left and right vertically.
   * @param boundingBox A bounding box object to find space for positioned at a starting point.
   * boundingBox contains {x, y, width, height} where x, y, is the upper left corner.
   * @return A new bounding box that fits in an open space, or null if no space is available.
   */
  algaeGraph.prototype.getSpaceHorizontally = function(boundingBox, debug) {
  // --------------------------------------------------------------------------
    if (debug === undefined) { debug = false; }
    var offset = boundingBox.height * 0.25;
    var leastOverlapAmount = 1.0e30;
    var leastOverlappingBox = null;
//    console.log('DEBUG: ----');
//    console.log('DEBUG: boundingBox.y = ' + boundingBox.y.toString());
//    console.log('DEBUG: boundingBox.height = ' + boundingBox.height.toString());
//    console.log('DEBUG: offset = ' + offset.toString());
//    console.log('DEBUG: this.innerHeight = ' + this.innerHeight.toString());
    // var checkBox = JSON.parse(JSON.stringify(boundingBox));
    var checkBox = Object.assign({}, boundingBox);
    //
    // ----- requested area is open, return it
    //
    if (! this.isOverlappingBoundingBox(boundingBox)) {
      return {boundingBox: boundingBox, overlapAmount: 0.0};
    }
    //
    // ----- check right
    //
    if ((this.noOverlapStyle == algaeGraph.enumNoOverlapStyle.leftAndRight) || (this.noOverlapStyle == algaeGraph.enumNoOverlapStyle.rightOnly)) {
      checkBox.x += offset;
      while ((checkBox.x < this.innerWidth) && (checkBox.x + checkBox.width < this.innerWidth)) {
        if (debug) {
          this.drawRectangle(checkBox.x, checkBox.y, checkBox.width, checkBox.height, '#00FF00', '#FFFFFF');
        }
        if (! this.isOverlappingBoundingBox(checkBox)) {
          return {boundingBox: checkBox, overlapAmount: 0.0};
        } else if (this.overlapAmount < leastOverlapAmount) { // keep track of least overlapping box
          leastOverlapAmount = this.overlapAmount;
          leastOverlappingBox = Object.assign({}, checkBox);
        }
        checkBox.x += offset;
      }
    }
    //
    // ----- check left
    //
    checkBox = Object.assign({}, boundingBox);
    // console.log('DEBUG: checkBox.y = ' + checkBox.y.toString());
    if ((this.noOverlapStyle == algaeGraph.enumNoOverlapStyle.leftAndRight) || (this.noOverlapStyle == algaeGraph.enumNoOverlapStyle.leftOnly)) {
      checkBox.x -= offset;
      // console.log('DEBUG: checkBox.y = ' + checkBox.y.toString());
      while (checkBox.x > 0) {
        if (debug) {
          this.drawRectangle(checkBox.x, checkBox.y, checkBox.width, checkBox.height, '#0000FF', '#FFFFFF');
        }
        if (! this.isOverlappingBoundingBox(checkBox)) {
          return {boundingBox: checkBox, overlapAmount: 0.0};
        } else if (this.overlapAmount < leastOverlapAmount) { // keep track of least overlapping box
          leastOverlapAmount = this.overlapAmount;
          leastOverlappingBox = Object.assign({}, checkBox);
        }
        checkBox.x -= offset;
      }
    }
    return {boundingBox: leastOverlappingBox, overlapAmount: leastOverlapAmount};
  } 
  
  /**
   * Get a text insertion point from a bounding box.  This is kind of the opposite of getTextBoundingBox().
   * @param horizAnchor The horizontal text anchor, typically 'start', 'middle', or 'end'.
   * @param vertAnchor The vertical text anchor, typically 'auto' (bottom), 'middle', or 'hanging' (top).
   * @return Insertion point object {x, y}.
   */
  algaeGraph.prototype.getTextInsertPtFromBoundingBox = function(horizAnchor, vertAnchor, boundingBox) {
  // --------------------------------------------------------------------------
    var ip = {x: boundingBox.x, y: boundingBox.y};
    var fudge = 2;
    //
    // ----- adjust horizontal bounding box position
    //
    if (horizAnchor.toUpperCase() === 'MIDDLE') {
      ip.x += (boundingBox.width / 2);
    } else if (horizAnchor.toUpperCase() === 'END') {
      ip.x += boundingBox.width;
    } else {
      // no change
    }
    //
    // ----- adjust vertical bounding box position
    //
    if ((vertAnchor.toUpperCase() === 'MIDDLE') || (vertAnchor.toUpperCase() === 'CENTRAL')) {
      ip.y += (boundingBox.height / 2);
    } else if (vertAnchor.toUpperCase() === 'HANGING') {
      ip.y += fudge;
    } else {
      ip.y += (boundingBox.height - fudge);
    } 
    return ip;
  }
  
  /**
   * Save the area used by a point on the line.
   */
  algaeGraph.prototype.savePtArea = function(data) {
  // --------------------------------------------------------------------------
    var halfSize = this.defaultSymbolSize / 2;
    var x = this.xAxis.scale(data[this.xAxis.varNum]);
    var y = this.yAxis.scale(data[this.yAxis.varNum]);
    this.saveUsedArea(x - halfSize, y - halfSize, x + halfSize, y + halfSize);
    return this;
  }
  
  /**
   * Remove the graph.
   */
  algaeGraph.prototype.remove = function() {
  // --------------------------------------------------------------------------
    d3.select('#' + this.divId + " svg").remove();
    this.legendCurY = this.legendStartingY;
    return this;
  }
  
  /**
   * Draw the title.
   */
  algaeGraph.prototype.drawTitle = function() {
  // --------------------------------------------------------------------------
    if (this.showTitle) {
      var y = (this.margin.top / 2) * -1;
      if (this.subTitle.length > 0) {
        y = (this.margin.top * 0.66) * -1;
      }
      if (this.mainTitle.length > 0) {
        this.svg.append("text")
          .attr("class", "graphTitle")
          .style("text-anchor", "middle")
          .attr("x", this.innerWidth / 2)
          .attr("y", y)
          .text(this.mainTitle);
      }
      if (this.subTitle.length > 0) {
        y = (this.margin.top * 0.25) * -1;
        this.svg.append("text")
          .attr("class", "graphSubTitle")
          .style("text-anchor", "middle")
          .attr("x", this.innerWidth / 2)
          .attr("y", y)
          .text(this.subTitle);
      }    
    }
    return this;
  }
  
  /**
   * Draw a data series.
   */
  algaeGraph.prototype.drawSeries = function(num, data, name, color, attributes) {  
  // --------------------------------------------------------------------------
	  alert('Defaut drawSeries() not over-ridden.')
    return this;
  }
  
  /**
   * Draw the data on the graph.
   */
  algaeGraph.prototype.drawData = function() {
  // --------------------------------------------------------------------------
    //
    // ----- draw the data
    //
    for (var i=0; i < this.data.length; i++) {
      this.drawSeries(i, this.data[i], this.dataName[i], this.dataColor[i], this.dataAttributes[i]);
    }
    return this;
  }  
  
  /**
   * Draw graph background.
   */
  algaeGraph.prototype.drawBackground = function() {
  // --------------------------------------------------------------------------	  
  }
  
  /**
   * Draw a filled rectangle.
   * @param x Upper left X coordinate, graph units.
   * @param y Upper left Y coordinate, graph units.
   * @param width Rectangle width, graph units.
   * @param height Rectangle height, graph units.
   * @param lineColor Color for line, for example '#000000' = black.
   * @param fillColor Color for fill, for example '#FF0000' = red.  Use '#000000' to make it transparent.
   * @param title Optional title.  When included adds an HTML title to the rectangle for mouse overs.
   */
  algaeGraph.prototype.drawRectangle = function(x, y, width, height, lineColor, fillColor, title, tooltip) {
  // --------------------------------------------------------------------------
    title = title || null;
    tooltip = tooltip || null;
    if (this.debug) {
      console.log('DEBUG: drawRectangle x = ' + x.toString() + ' y = ' + y.toString() + 
          ' width = ' + width.toString() + ' height = ' + height.toString());
      console.log('DEBUG: tooltip = ' + tooltip);
    }
    let that = this;
    let rect = this.svg.append("rect")
                .attr("x", x)
                .attr("y", y)
                .attr("stroke", lineColor)
                .attr("fill", fillColor)
                .attr("width", width)
                .attr("height", height)
                .on("mouseover", function(d) {  
                  if (tooltip != null) {
                    that.tooltip.html(tooltip); 
                    d3.select(this).attr("class", "graphHighlightedBorder");
                    return that.tooltip.style("visibility", "visible");
                  } else {
                    return that.tooltip.style("visibility", "hidden");
                  }
                 })
                .on("mousemove", function(d) {
                  if (tooltip != null) {
                    return that.setTooltipPosition(event);
                  }
                 })
                .on("mouseout", function(d) {
                  if (tooltip != null) {
                    d3.select(this).attr("class", "graphBlackBorder");
                  }
                  return that.tooltip.style("visibility", "hidden");
                 });
    if ((title != null) && (tooltip == null)) {
      rect.append("svg:title").text(title);
    }
    return this;
  }
  
  /**
   * Draw a clickable rectangle in a legend.  Used specifically to support toggling
   * a data series on and off.  Typically only called from drawLegendItem().
   * @param x Upper left X coordinate, graph units.
   * @param y Upper left Y coordinate, graph units.
   * @param width Rectangle width, graph units.
   * @param height Rectangle height, graph units.
   * @param fillColor Color for fill, for example '#FF0000' = red.
   */
  algaeGraph.prototype.drawRectangleInLegend = function(x, y, width, height, fillColor, series) {
  // --------------------------------------------------------------------------
    let rect = this.svg.append("rect")
                .attr("x", x)
                .attr("y", y)
                .attr("stroke", "#000000")
                .attr("fill", fillColor)
                .attr("width", width)
                .attr("height", height)
                .attr("visible", 1)
                // .attr("series", series)
                .on("mouseover", function(d) { d3.select(this).attr("stroke-width", "2"); })
                .on("mouseout", function(d) { d3.select(this).attr("stroke-width", "1"); })
                .on("click", function(d) { 
                    if (d3.select(this).attr("visible") == 1) {
                      d3.select(this).attr("visible", 0);
                      d3.select(this).style("fill", "#FFFFFF");
                      d3.selectAll("[series='" + series.toString() + "']").style("visibility", "hidden");
                    } else {
                      d3.select(this).attr("visible", 1);
                      d3.select(this).style("fill", fillColor);
                      d3.selectAll("[series='" + series.toString() + "']").style("visibility", "visible");
                    }
                   });
    return this;
  }
  
  /**
   * Draw a line.
   * Coordinates are in graph units not data units.
   * For example to convert: var y = this.yAxis.scale(0.5);
   */
  algaeGraph.prototype.drawLine = function(x1, y1, x2, y2, color, width, style) {
  // --------------------------------------------------------------------------  
    var line = this.svg.append("line")
        .attr("x1", x1)
        .attr("y1", y1)
        .attr("x2", x2)
        .attr("y2", y2)    
        .style("stroke", color)
        .style("stroke-width", width.toString());
    if (typeof style !== 'undefined')  {
      if ( (style == algaeGraph.enumLineStyle.dashed) || ((typeof style == 'string') && (style.toUpperCase() === 'DASHED')) ) {
        line.style("stroke-dasharray", ("3, 3"));
      }
      if ( (style == algaeGraph.enumLineStyle.dashed) || ((typeof style == 'string') && (style.toUpperCase() === 'DOTTED')) ) {
        line.style("stroke-dasharray", ("1, 3"));
      }
    }
  }
  
  /**
   * Draw a horizontal line.
   * 
   * @param float y Line position along y-axis, in data units.
   * @param string color Line color in HTML, for example '#888888' for medium gray.
   * @param integer width Line width in pixels.
   * @param style string Line style, i.e. SOLID, DASHED, DOTTED.
   * 
   * Examples:
   * this.drawHorizLine(0, '#000000', 1);
   * this.drawHorizLine(10, '#000000', 1, 'DASHED');
   * this.drawHorizLine(20, '#000000', 1, 'DOTTED');
   * this.drawHorizLine(0, '#888888', 1); // solid line gray axis color
   */
  algaeGraph.prototype.drawHorizLine = function(y, color, width, style) {
  // --------------------------------------------------------------------------
    var yGraph = this.yAxis.scale(y);
    if ( (yGraph >= 0) && (yGraph <= this.innerHeight) ) {
      this.drawLine(0, yGraph, this.innerWidth, yGraph, color, width, style);	
    }
  }
  
  /**
   * Draw a vertical line.
   * 
   * Examples:
   * this.drawVertLine(0, '#000000', 1);
   * this.drawVertLine(10, '#000000', 1, 'DASHED');
   * this.drawVertLine(20, '#000000', 1, 'DOTTED');
   */
  algaeGraph.prototype.drawVertLine = function(x, color, width, style) {
  // --------------------------------------------------------------------------
    var xGraph = this.xAxis.scale(x);
    if ( (xGraph >= 0) && (xGraph <= this.innerWidth) ) {
      this.drawLine(xGraph, 0, xGraph, this.innerHeight, color, width, style); 
    }
  }
  
  /**
   * Draw text.
   * 
   * Examples:
   * this.drawText(this.xAxis.scale(x2 + textOffset), this.yAxis.scale(y2), '$' + algaefw.getFormattedNumber(y2, 1), 'graphAxisTitle', 'start', 'central');
   * 
   * @param float x X coordinate in graph units.
   * @param float y Y coordinate in graph units.
   * @param string text The text to draw.
   * @param string cssClass The CSS class to use for formatting.
   * @param string horizAnchor Default 'start', options('start', 'middle', 'end').
   * @param string vertAnchor Default 'bottom', options('bottom', 'central' ...), use central for vertical centering, hanging for top.
   *   see: https://stackoverflow.com/questions/12250403/vertical-alignment-of-text-element-in-svg).
   */
  algaeGraph.prototype.drawText = function(x, y, text, cssClass, horizAnchor, vertAnchor, markAreaUsed) {
  // --------------------------------------------------------------------------  
    if (cssClass === undefined) { cssClass = 'graphAxisTitle'; }
    if (horizAnchor === undefined) { horizAnchor = 'start'; }
    if (vertAnchor === undefined) { vertAnchor = 'bottom'; }
    if (markAreaUsed === undefined) { markAreaUsed = true; }
    if (markAreaUsed) {
      bbox = this.getTextBoundingBox(x, y, text, cssClass, horizAnchor, vertAnchor);
      this.saveUsedBoundingBox(bbox);
    }
    return this.svg.append("text")
     .attr("class", cssClass)
     .attr("x", x)
     .attr("y", y)
     .style("text-anchor", horizAnchor)
     .style("dominant-baseline", vertAnchor)
     .text(text);
  }
  
  /**
   * Draw a symbol.
   * Examples:
   * this.drawSymbol(this.xAxis.scale(xData), this.yAxis.scale(yData), algaeGraph.enumSymbol.circle, 5, '#FFFFFF', color);
   * @param float x Symbol center X in graph units.
   * @param float y Symbol center Y in graph units.
   * @param integer Symbol number from enumeration algaeGraph.enumSymbol, i.e. algaeGraph.enumSymbol.circle.
   * @param float Symbol size in pixels.
   * @param string lineColor Color for linework in the symbol, i.e. an outline.
   * @param string fillColor Fill color.
   */
  algaeGraph.prototype.drawSymbol = function(x, y, num, size, lineColor, fillColor, tooltip) {
  // --------------------------------------------------------------------------  
    tooltip = tooltip || null;
    let that = this;
    var half = (size / 2) * 1.3;
    this.saveUsedArea(x - half, y - half, x + half, y + half);
    return this.svg.append("path")
          .attr("transform", "translate(" + x.toString() + "," + y.toString() + ")" )
          .attr("d", d3.symbol().type(this.getSymbolDefinition(num)).size(size * size) )
          .style("stroke", lineColor)
          .style("fill", fillColor)
          .on("mouseover", function(d) {  
                  if (tooltip != null) {
                    that.tooltip.html(tooltip); 
                    d3.select(this).attr("class", "graphHighlightedBorder");
                    return that.tooltip.style("visibility", "visible");
                  } else {
                    return that.tooltip.style("visibility", "hidden");
                  }
                 })
                .on("mousemove", function(d) {
                  if (tooltip != null) {
                    return that.setTooltipPosition(event);
                  }
                 })
                .on("mouseout", function(d) {
                  if (tooltip != null) {
                    d3.select(this).attr("class", "graphBlackBorder");
                  }
                  return that.tooltip.style("visibility", "hidden");
                 });
  }
  
  /**
   * Add text margins to a bounding box.
   * The bounding box must have attributes x, y, width, and height.
   * Text margins are defined by this.textMargin.x and this.textMargin.y.
   */
  algaeGraph.prototype.addTextMargins = function(boundingBox) {
  // --------------------------------------------------------------------------
    if (boundingBox != null) {
      let bbox = {x:boundingBox.x, y:boundingBox.y, width:boundingBox.width, height:boundingBox.height};
      bbox.x -= this.textMargin.x;
      bbox.width += (this.textMargin.x * 2);
      bbox.y -= this.textMargin.y;
      bbox.height += (this.textMargin.y * 2);
      return bbox;
    }
    return null;
  }
  
  /**
   * Subtract text margins from a bounding box.
   * The bounding box must have attributes x, y, width, and height.
   * Text margins are defined by this.textMargin.x and this.textMargin.y.
   */
  algaeGraph.prototype.subtractTextMargins = function(boundingBox) {
  // --------------------------------------------------------------------------
      if (boundingBox != null) {
        let bbox = {x:boundingBox.x, y:boundingBox.y, width:boundingBox.width, height:boundingBox.height};
        bbox.x += this.textMargin.x;
        bbox.width -= (this.textMargin.x * 2);
        bbox.y += this.textMargin.y;
        bbox.height -= (this.textMargin.y * 2);
        return bbox;
      }
      return null;
    }
  
  /**
   * Draw a label with automatic re-positioning to avoid over-printing.
   * algaeGraph.enumNoOverlapStyle = Object.freeze({"ellipse":0, "upOnly":1, "downOnly":2});
   */
  algaeGraph.prototype.drawTextWithoutOverlap = function(x, y, text, cssClass, fitStyle, horizAnchor, vertAnchor) {
  // --------------------------------------------------------------------------
    var textObj, height, width, posArray, labelPlotted;
    var offset = 10;
    if (horizAnchor === undefined) { horizAnchor = 'start'; }
    if (vertAnchor === undefined) { vertAnchor = 'bottom'; }
    var colorBlock = false;
    var colorBlockColor = null;
    var colorBlockWidth = 20;
    let startingBBox = null;
    let posObj = null;
    //
    //
    //
    var endSpanPos = text.indexOf("</span>");
    if (endSpanPos > -1) {
      var spanPart = text.substring(0, endSpanPos);
      // console.log('DEBUG: spanPart = ' + spanPart);
      var backgroundPos = spanPart.indexOf("background:");
      if (backgroundPos > -1) {
        var colorPart = spanPart.substring(backgroundPos + 11, backgroundPos + 11 + 7);
        // console.log('DEBUG: colorPart = ' + colorPart);
        x += colorBlockWidth;
        colorBlock = true;
        colorBlockColor = colorPart;
        text = text.substring(endSpanPos + 7, text.length);
      }
    }
    //
    //
    //
    textObj = this.drawText(x, y, text, cssClass, horizAnchor, vertAnchor, false);
    labelPlotted = true;
    startingBBox = textObj.node().getBBox();
    
    height = textObj.node().getBBox().height * 1.0;
    width = textObj.node().getBBox().width;
    
    //
    //
    //
    if (this.avoidOverlaps) {
      
      if (this.isOverlappingBoundingBox(this.addTextMargins(startingBBox))) {
        // console.log('DEBUG: Text ' + text + ' is overlapping, repositioning.');
        textObj.remove();
        labelPlotted = false;
        if (fitStyle == algaeGraph.enumNoOverlapStyle.ellipse) {
          posArray = this.getIdealLabelPosition(x, y, offset, width, height);
        } else {
          posArray = null;
        }
        if ( (posArray != null) && (! isNaN(posArray[0])) ) {
          x = posArray[0];
          y = posArray[1];
          // console.log('###: Ideal position works at xy = ' + x.toString() + ', ' + y.toString());
          textObj = this.drawText(x, y, text, cssClass, horizAnchor, vertAnchor, false);
          labelPlotted = true;
        } else {
          if (fitStyle == algaeGraph.enumNoOverlapStyle.ellipse) {
            posArray = this.fitText(x, y, offset, width, height);
          } else if (fitStyle == algaeGraph.enumNoOverlapStyle.upAndDown) {
            // console.log('DEBUG: Fitting vertically up and down.');
            posObj = this.fitTextVerticallyOnly(startingBBox, fitStyle);
          } else if (fitStyle == algaeGraph.enumNoOverlapStyle.upOnly) {
            // console.log('DEBUG: Fitting vertically up.');
            posObj = this.fitTextVerticallyOnly(startingBBox, fitStyle);
          } else if (fitStyle == algaeGraph.enumNoOverlapStyle.downOnly) {
            // console.log('DEBUG: Fitting vertically down.');
            posObj = this.fitTextVerticallyOnly(startingBBox, fitStyle);
          } 
          // posArray = this.fitText(x, y, offset, width, height);
          if ( ((posArray != null) && (! isNaN(posArray.x))) || (posObj != null) ) {
            
            if (posObj != null) {
              let ip = this.getTextInsertPtFromBoundingBox(horizAnchor, vertAnchor, posObj.BBox);
              x = ip.x;
              y = ip.y;
            }
            else if (posArray != null) {
              x = posArray.x;
              y = posArray.y;
            }
            
            textObj = this.drawText(x, y, text, cssClass, horizAnchor, vertAnchor, false);
            
            // console.log('  DEBUG: Fit position works at xy = ' + x.toString() + ', ' + y.toString());
            /*
            console.log('  DEBUG: nLoops = ' + posArray.nLoops.toString());
            if (posArray.nLoops > 0) {
              // this.drawLeaderLine(centerX, centerY, x, y, width, height);
            }
            */
            labelPlotted = true;
          } else {
            this.svg.append("circle")
            .attr("stroke", "red")
            .attr("fill", "none")
            .attr("cx", x)
            .attr("cy", y)
            .attr("r", 2);
          }
        }
      }

    }
    if (labelPlotted) {
      
      if (colorBlock) {
        // drawRectangle = function(x, y, width, height, lineColor, fillColor) {
        this.drawRectangle(x - colorBlockWidth, y - height + 5, colorBlockWidth / 1.8, height * 0.7, colorBlockColor, colorBlockColor);
      }
      
      /*
      if ( ((typeof(eraseBackground) !== 'undefined')) && (eraseBackground) ) {
        text.remove();
        text = this.addLabelAndEraseBackground(x, y, label);
      }
      */
      
      this.saveUsedBoundingBox(this.getTextBoundingBox(x, y, text, cssClass, horizAnchor, vertAnchor));
    }
  }
  
  /**
   * Calculate the standard deviation, from Davis, 1973.
   * @param numeric num Number of observations.
   * @param numeric sum Sum of values.
   * @param numeric ss Sum of squares of values.
   */
  algaeGraph.prototype.calcStandardDev = function(num, sum, ss) {
  //--------------------------------------------------------------------------
    var sd = null;
    if (num > 1) {
      var a = ss - ((sum * sum) / num);
      var b = a / (num - 1)
      if (b >= 0.0) {
        sd = Math.sqrt(b);
      }
    }
    return sd;
  }
  
  /**
   * Position the tooltip so it more-or-less stays on the graph area.
   */
  algaeGraph.prototype.setTooltipPosition = function(event) {
  // --------------------------------------------------------------------------
	var vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
	if (event.pageX / vw < 0.75) {
	  return this.tooltip.style("top", (event.pageY-10)+"px").style("left",(event.pageX+10)+"px"); /* right of element */
	}
    return this.tooltip.style("top", (event.pageY+10)+"px").style("left",(event.pageX-180)+"px"); /* left of element */
  }
  
  /**
   * Get the starting x coordinate for a legend.
   */
  algaeGraph.prototype.getLegendStartingX = function() {
  // --------------------------------------------------------------------------
    var x = 0;
    if (this.legend) {
      if ( (this.legendPosition === this.legendPositions.upperRight) || 
           (this.legendPosition === this.legendPositions.lowerRight) ) {
        if (! this.legendInMargin) {
          x = this.innerWidth - this.legendTextMaxWidth - (this.legendBoxSize * (this.legendBoxMultiplier * 1.5));
        } else {
          x = this.innerWidth + (this.legendBoxSize / 2);
        }
      } else {
        x = this.legendStartingY;
      }  
    }
    return x;
  }
  
  /**
   * Get the starting y coordinate for a legend.
   */
  algaeGraph.prototype.getLegendStartingY = function(numLines) {
  // --------------------------------------------------------------------------
    var y = 0;
    if (this.legend) {
      var nLines = this.seriesLegend.length;
      if (arguments.length === 1) nLines = numLines;
      if ( (this.legendPosition === this.legendPositions.lowerRight) || 
           (this.legendPosition === this.legendPositions.lowerLeft) ) {
        y = this.innerHeight - this.legendStartingY - 
          (this.legendLineHeight * nLines);
      } else {
        y = this.legendStartingY;
      }
    }
    return y;
  }  
  
  /**
   * Add an entry to the legend.
   */
  algaeGraph.prototype.drawLegendItem = function(legendEntry) {
  // --------------------------------------------------------------------------
    if (this.legend) {
      var legendCurX = this.getLegendStartingX();
      var centerY = this.legendCurY + (this.legendLineHeight / 4);
      
      if (legendEntry.symbol == algaeGraph.enumSymbol.rectangle) {
        this.drawRectangleInLegend(legendCurX, this.legendCurY, this.legendBoxSize, this.legendBoxSize, legendEntry.fillColor, legendEntry.seriesNum);
      } else if (legendEntry.symbol == algaeGraph.enumSymbol.line) {
        this.drawLine(legendCurX, centerY, legendCurX + this.legendBoxSize, centerY, legendEntry.lineColor, legendEntry.lineThickness, legendEntry.lineStyle);
      } else {
        this.drawSymbol(legendCurX + (this.legendBoxSize / 2), centerY, legendEntry.symbol, 
            this.legendLineHeight * 0.25, legendEntry.lineColor, legendEntry.fillColor);
      }
      
      this.drawText(legendCurX + (this.legendBoxSize * 1.5), this.legendCurY + (this.legendBoxSize * 0.75), legendEntry.text, 'graphAxisTitle', 'start');
      this.legendCurY += this.legendLineHeight;
    }
    return this;
  }
  
  /**
   * Add a text entry to the legend.
   */
  algaeGraph.prototype.drawLegendTextLine = function(text) {
  // --------------------------------------------------------------------------
    if (this.legend) {
      var legendCurX = this.getLegendStartingX();
      this.drawText(legendCurX, this.legendCurY + (this.legendBoxSize * 0.75), text, 'graphAxisTitle', 'start');
      this.legendCurY += this.legendLineHeight;
    }
    return this;
  }
  
  /**
   * Set the maxium width for legend text.
   */
  algaeGraph.prototype.setLegendMaxTextWidth = function() {
  // --------------------------------------------------------------------------
    this.legendTextMaxWidth = 0;
    if ( (this.legend) && (this.legendData.length > 0) ) {
      //
      // ----- only calc for right leegend positions
      //
      if ( (this.legendPosition === this.legendPositions.upperRight) || 
          (this.legendPosition === this.legendPositions.lowerRight) ) {
        //
        // ----- loop through the legend items
        //
        for (var i=0; i < this.legendData.length; i++) {
          //
          // ----- calculate width
          //
          textObj = this.drawText(0, this.legendLineHeight, this.legendData[i].text, 'graphAxisTitle', 'start');
          width = textObj.node().getBBox().width;
          textObj.remove();
          if (width > this.legendTextMaxWidth) {
            this.legendTextMaxWidth = width;
          }
        }
      }
    }
    return this;
  }

  /**
   * Build a default legend for each series. Typically only called from drawLegend().
   */
  algaeGraph.prototype.buildSeriesLegend = function() {
  // --------------------------------------------------------------------------
    for (i = 0; i < this.data.length; i++) {
      if ( (this.dataName.length > i) && (this.dataColor.length > i) ) {
        let legend = {
            "text": this.dataName[i],
            "fillColor": this.dataColor[i],
            "lineColor": '#000000',
            "symbolNum": 0,
            "symbol": algaeGraph.enumSymbol.rectangle,
            "seriesNum": i
        }
        this.legendData.push(legend);
      }
    }
  }
  
  /**
   * Draw the legend using legend lines defined in this.legendData.  If there are
   * multiple series and this.legendData is not defined it will be created automatically.
   * Typically called from draw() after the graph data has been plotted.
   */
  algaeGraph.prototype.drawLegend = function() {
  // --------------------------------------------------------------------------
    //
    // ----- build default series legend if there is a series and no legend exists
    //
    if ( (this.legend) && (this.legendAuto) && (this.legendData.length == 0) && (this.data.length > 1) ) {
      this.buildSeriesLegend();
    }
    if ( (this.legend) && (this.legendData.length > 0) ) {
      //
      // ----- set max width if needed
      //
      this.setLegendMaxTextWidth();
      //
      // ----- set the legend starting Y position if lower left or right
      //
      if ((this.legendPosition == this.legendPositions.lowerLeft) || (this.legendPosition == this.legendPositions.lowerRight)) {
        this.legendCurY = this.innerHeight - (this.legendCurY * 0.5);
        this.legendCurY -= (this.legendData.length * this.legendLineHeight);
      }
      //
      // ----- draw each legend item
      //
      for (var i=0; i < this.legendData.length; i++) {
        if (this.legendData[i].symbol == algaeGraph.enumSymbol.none) {
          this.drawLegendTextLine(this.legendData[i].text);
        } else {
          this.drawLegendItem(this.legendData[i]);
        }
      }
    }
    return this;
  }
  
  /**
   * Handle clicks on a graph object.  
   * For example to click a point and goto the webpage for the associated data.
   */
  algaeGraph.prototype.clickHandler = function(url) {
  // --------------------------------------------------------------------------
    if ( (this.clickForward) && (typeof(url) == 'string') && (url.length > 0) ) {
      window.open(url, '_blank');
    }
    return this;
  }
  
  /**
   * Draw the graph.
   */
  algaeGraph.prototype.draw = function() {
  // --------------------------------------------------------------------------
    this.remove();
    this.init();
    // this.drawArea();
    this.drawTitle();
    //
    // ----- x axis
    //
    if (this.xAxis.scaleSetup == algaeGraph.enumAutoManual.automatic) {
      this.xAxis.autoScale(0, this.innerWidth, this.data, false);
    } else {
      this.xAxis.manualScale(this.xAxis.manualMin, this.xAxis.manualMax, 0, this.innerWidth, false);
    }
    this.xAxis.draw(this.svg, this.innerWidth, this.innerHeight, this.margin);
    //
    // ----- y axis
    //
    if (this.yAxis.scaleSetup == algaeGraph.enumAutoManual.automatic) {
      this.yAxis.autoScale(this.innerHeight, 0, this.data, false);

    } else {
      this.yAxis.manualScale(this.yAxis.manualMin, this.yAxis.manualMax, this.innerHeight, 0, false);
    }
    this.yAxis.draw(this.svg, this.innerWidth, this.innerHeight, this.margin);
    //
    // ----- background
    //
    this.drawBackground();
    //
    // ----- data
    //
    this.drawData();
    //
    // ---- legend
    //
    this.drawLegend();
    //
    // ----- footprint
    //
    if (this.showFootprint) {
      this.drawFootprint();
    }
    return this;
  } 
 
  return algaeGraph;
})();







