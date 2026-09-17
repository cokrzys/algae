
/**

  algae framework | Treemaps.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeTreemap = (function() {
  
  /**
   * Constructor.
   */
  function algaeTreemap() {
  // --------------------------------------------------------------------------
    algaeGraph.call(this);
    this.itemVar = 1;
    this.groupByVarNum = 0;
    this.sizeByVarNum = 1;
    this.colorByVarNum = 3;
    this.tooltipVarNum = 0;
    this.showGroupTitles = false;
    this.clickForward = true;
    this.clickURLPrefix = '';
    this.clickURLSuffixVarNum = 0;
    this.labelRectangles = false;
    return this;
  };
  
  /**
   * Setup prototype based inheritance
   */
  algaeTreemap.prototype = Object.create(algaeGraph.prototype);
  
  /**
   * Label a rectangle in the treemap.
   * @param d A d3.js treemap leaf, containing at least:
   *   - d.data.name = label for rectangle
   *   rectangle coordinates:
   *   - d.x0 = upper left x 
   *   - d.y0 = upper left y
   *   - d.x1 = lower right x
   *   - d.y1 = lower right y
   */
  algaeTreemap.prototype.labelRect = function(d) {
  // --------------------------------------------------------------------------
    var cssClass = 'graphAxisTitle';
    var horizAnchor = 'start';
    var vertAnchor = 'hanging';
    var offset = 5;
    var x = d.x0 + offset;
    var y = d.y0 + offset;
    //
    // ----- only labels that fit
    //
    bbox = this.getTextBoundingBox(x, y, d.data.name, cssClass, horizAnchor, vertAnchor);
    if ((bbox.x + bbox.width < d.x1 - offset) && (bbox.y + bbox.height < d.y1 - offset)) {
      this.drawText(x, y, d.data.name, cssClass, horizAnchor, vertAnchor, true);
    }
  }
  
  /**
   * Draw the graph.
   */
  algaeTreemap.prototype.draw = function() {
  // --------------------------------------------------------------------------
    var that = this;
    var hierarchy = { name:'root', children:[] };
    var lastGroupName = '';
    //
    // ----- initial graph area, draw title
    //
    this.init();
    this.drawTitle();    
    //
    // ----- convert data from an array into a JSON hierarchy structure to make the treemap
    //
    for (var i=0; i < this.data[0].length; i++) {
      itemName = this.data[0][i][this.itemVar];
      groupName = this.data[0][i][this.groupByVarNum];
      sizeValue = this.data[0][i][this.sizeByVarNum];
      originalValue = sizeValue;
      if (sizeValue < 0) {
        sizeValue = sizeValue * -1;
      }
      colorValue = this.data[0][i][this.colorByVarNum];
      tooltip = this.data[0][i][this.tooltipVarNum];
      clickSuffix = this.data[0][i][this.clickURLSuffixVarNum];
      if (groupName != lastGroupName) {
        var subGroup = { name:groupName, children:[] };
        hierarchy.children.push(subGroup);
        lastGroupName = groupName;
      }
      var item = { name:itemName, sizeValue:sizeValue, color:colorValue, tooltip:tooltip, clickSuffix:clickSuffix, originalValue:originalValue };
      hierarchy.children[hierarchy.children.length-1].children.push(item);
    }
    // var myJSON = JSON.stringify(hierarchy);
    // console.log(myJSON);
    //
    // ----- create summary hierarchy structure
    //
    var root = d3.hierarchy(hierarchy).sum(function(d){ return d.sizeValue})
    //
    // ----- adjust top padding to show group titles or not
    //
    var topPadding = 0;
    if (this.showGroupTitles) { 
      topPadding = 15; 
    }
    //
    // ----- calculate treemap 
    //
    d3.treemap()
    .size([that.innerWidth, that.innerHeight])
    .paddingOuter(2)
    .paddingInner(2)  
    .paddingTop(topPadding)
    (root);
    //
    // ----- draw the treemap
    //
    this.svg
    .selectAll("rect")
    .data(root.leaves())
    .enter()
    .append("rect")
      .attr('x', function (d) { return d.x0; })
      .attr('y', function (d) { return d.y0; })
      .attr('width', function (d) { return d.x1 - d.x0; })
      .attr('height', function (d) { return d.y1 - d.y0; })
      .style("stroke", "gray")
      .style("fill", function(d){ return d.data.color; })
      .style("opacity", 0.9)
      .on("mouseover", function(d) {  
        if (d.data.tooltip.length > 0) {
          that.tooltip.html(d.data.tooltip);
          d3.select(this).attr("class", "graphHighlightedBorder");
          return that.tooltip.style("visibility", "visible");
        } else {
          return that.tooltip.style("visibility", "hidden");
        }
       })
      .on("mousemove", function(d) {
        if (d.data.tooltip.length > 0) {
          return that.setTooltipPosition(event);
        }
       })
      .on("mouseout", function(d) {
        if (d.data.tooltip.length > 0) {
          d3.select(this).attr("class", "graphBlackBorder");
        }
        return that.tooltip.style("visibility", "hidden");
       })
      .on("click", function(d) {
        if ( (that.clickForward) && (d.data.clickSuffix > 0) ) {
          // alert(d.data.clickSuffix.toString());
          window.open(that.clickURLPrefix + d.data.clickSuffix.toString(), '_blank');
        }
       });
    
    //
    // ----- label rectangles
    //
    if (this.labelRectangles) {
      let leafArr = root.leaves();
      leafArr.forEach((e) => {
        this.labelRect(e);
        // console.log(e.data);
        // console.log("x0: " + e.x0.toString());
      }) 
    }
    
    //
    // ----- add group titles if applicable
    //
    if (this.showGroupTitles) {
      this.svg
      .selectAll("titles")
      .data(root.descendants().filter(function(d){return d.depth==1}))
      .enter()
      .append("text")
        .attr("class", "graphAxisTitle")
        .attr("x", function(d){ return d.x0 + 5})
        .attr("y", function(d){ return d.y0 + 10})
        .text(function(d){ return d.data.name });
    }
    //
    // ----- add legend
    //
    this.drawLegend();
  }
 
  return algaeTreemap;
})();
 