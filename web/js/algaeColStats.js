
/**

  algae framework | Table column statistics.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

/**
 * Setup column stats dialog object.
 */
var colStatsDialog = {};
colStatsDialog.id = '#colStatsDialog';
colStatsDialog.options = {
    autoOpen: false,
    modal: true,
    width: 550,
    height: 500,
    resizable: false
};
colStatsDialog.dataTypeOptions = Object.freeze({"text":0, "numeric":1});
colStatsDialog.graphTypeOptions = Object.freeze({"line":0, "bar":1, "histogram":2});
colStatsDialog.graphScaleOptions = Object.freeze({"arithmetic":0, "cumulative":1, "log":2});
colStatsDialog.dataType = colStatsDialog.dataTypeOptions.text;
colStatsDialog.graphType = colStatsDialog.graphTypeOptions.line;  // change this in algaeTable::addColumnStatsDialog() selected element also
colStatsDialog.graphScale = colStatsDialog.graphScaleOptions.arithmetic;
colStatsDialog.htmlStats = '';
colStatsDialog.htmlCSV = '';
colStatsDialog.numericFootnote = '';
colStatsDialog.title = '';
colStatsDialog.data = [];
colStatsDialog.graphTooltipColumn = 1;
colStatsDialog.min = null;
colStatsDialog.max = null;
colStatsDialog.minGreaterThanZero = null;
colStatsDialog.tableTitle = null;

/**
 * Initial stats dialog if it exists at document ready.
 */
$(function() {
//-------------------------------------------------------------------------- 
  if ($(colStatsDialog.id).length > 0) {
    $("#colStatsDialogTabs").tabs().css({
      'min-height':'96%',
      'max-height':'96%',
      'overflow':'hidden'
    });
    $(colStatsDialog.id).dialog(colStatsDialog.options);
    $(colStatsDialog.id).on( "dialogopen", function( event, ui ) { colStatsDialog.onOpen(); } );
  }
  //
  // ----- attach change handlers for graph type or scale
  //
  $("#colStatsGraphType").on( "selectmenuchange", function( event, ui ) { colStatsDialog.toggleType(this); } );
  $("#colStatsGraphScale").on( "selectmenuchange", function( event, ui ) { colStatsDialog.toggleScale(this); } );
});

/**
 * Make text stats table sortable if it exists when the dialog opens.
 * This could not be done when the HTML table is added.
 */
colStatsDialog.onOpen = function() {
//--------------------------------------------------------------------------
  var st = document.getElementById('colStatsTextStatsTable');
  if (st != null) {
    sorttable.makeSortable(st);
  }
}

/**
 * Toggle graph type.
 */
colStatsDialog.toggleType = function(control) {
// --------------------------------------------------------------------------
  var graphType = $(control).children("option:selected").val();  
  console.log('graphType = ' + graphType);
  if (graphType == 'line') {
    this.graphType = this.graphTypeOptions.line;
  } else if (graphType == 'bar') {
    this.graphType = this.graphTypeOptions.bar;
  } else if (graphType == 'histogram') {
    this.graphType = this.graphTypeOptions.histogram;
  } else {
    alert('Graph type ' + graphType + ' not defined.');
  }
  this.drawGraph();
}

/**
 * Toggle graph scale.
 */
colStatsDialog.toggleScale = function(control) {
// --------------------------------------------------------------------------
  var graphScale = $(control).children("option:selected").val();  
  // console.log('DEBUG: graphScale = ' + graphScale);
  if (graphScale == 'cumulative') {
	  this.graphScale = this.graphScaleOptions.cumulative;
  } else if (graphScale == 'arithmetic') {
    this.graphScale = this.graphScaleOptions.arithmetic;
  } else if (graphScale == 'log') {
    this.graphScale = this.graphScaleOptions.log;
  } else {
    alert('Graph scale ' + graphScale + ' not defined.');
  }
  this.drawGraph();
}

/**
 * Read data in a column and detect the data type.
 */
colStatsDialog.detectDataType = function(tableId, colIndex) {
//--------------------------------------------------------------------------
  var that = this;
  var num_numeric = 0;
  var num_text = 0;
  $(tableId + ' tbody tr td:nth-child(' + colIndex.toString() + ')').each( function() {  
    if ($(this).parent().css('display') != 'none') {
      text = $(this).text();
      if ((text.length > 0) && (text != '-')) {
        digitsText = algaefw.getDigits(text);
        if (digitsText.length > 0) {
          if (algaefw.isNumeric(digitsText)) {
            // console.log('DEBUG: ' + digitsText + ' is numeric.');
            num_numeric++;
          } else {
            // console.log('DEBUG: ' + digitsText + ' is text.');
            num_text++;
          }
        }
      }
    }
  });
  if (num_text > 0) {
    // console.log('DEBUG: Text detected.');
    that.dataType = that.dataTypeOptions.text;
  } else {
    that.dataType = that.dataTypeOptions.numeric;
    // console.log('DEBUG: All numerics detected.');
  }
}

/**
 * Get a nicely formatted percentage.
 */
colStatsDialog.getPercent = function(subset, total, brackets) {
//--------------------------------------------------------------------------
  if (total > 0) {
    var str = ((subset/total)*100.0).toFixed(1) + '%';
    if (brackets) return ' (' + str + ')';
    return str;
  }
  return '';
}

/**
 * Compile text statistics.
 */
colStatsDialog.compileTextStats = function(tableId, colIndex) {
//--------------------------------------------------------------------------
  var that = this;
  // this.htmlStats += 'Text values detected.<p />';
  var num_valid = 0;
  var num_rows = 0;
  var num_blank = 0;
  var min_length = 1000000;
  var max_length = -min_length;
  var counts = {};
  $(tableId + ' tbody tr td:nth-child(' + colIndex.toString() + ')').each( function() {  
    if ($(this).parent().css('display') != 'none') {
      text = $(this).text();
      if ((text.length > 0) && (text != '-')) {
        num_valid++;
        if (text.length > max_length) max_length = text.length;
        if (text.length < min_length) min_length = text.length;
        counts[text] = 1 + (counts[text] || 0);
        that.htmlCSV += '"' + text + '",';
      } else {
        num_blank++;
      }
      num_rows++;
    }
  });

  this.htmlStats += num_rows.toString() + ' row(s) in the column<p />';
  this.htmlStats += num_valid.toString() + this.getPercent(num_valid, num_rows, true) + ' non-blank item(s)<p />';
  this.htmlStats += num_blank.toString() + this.getPercent(num_blank, num_rows, true) + ' blank item(s)<p />';
  this.htmlStats += 'Minimum item length = ' + min_length.toString() + '<p />';
  this.htmlStats += 'Maximum item length = ' + max_length.toString() + '<p />';
  
  var keys = Object.keys(counts);
  var i, k;
  var len = keys.length;
  
  if (len == num_valid) {
    this.htmlStats += 'Each item is unique.<p />';
  } else {
    keys.sort();
    this.htmlStats += '<table id="colStatsTextStatsTable" class="algae_table"><thead><tr><th>Item</th><th>Count</th><th>Percent</th></tr></thead><tbody>';
    for (i = 0; i < len; i++) {
      k = keys[i];
      // console.log(k + ':' + counts[k]);
      this.htmlStats += '<tr>';
      this.htmlStats += '<td>' + k + '</td>';
      this.htmlStats += '<td>' + counts[k].toString() + '</td>';
      this.htmlStats += '<td>' + this.getPercent(counts[k], num_rows, false) + '</td>';
      this.htmlStats += '</tr>';
      this.data.push({x:i+1, y:counts[k], tooltip:k});
    }
    this.htmlStats += '</tbody></table><p />';
  }
  
}

/**
 * Show a statistic in a table row.
 */
colStatsDialog.showStatistic = function(statistic, value, percentage) {
//--------------------------------------------------------------------------
  this.htmlStats += '<tr>';
  this.htmlStats += '<td>' + statistic + '</td>';
  this.htmlStats += '<td>' + value + percentage + '</td>';
  this.htmlStats += '</tr>';
}

/**
 * Calculate the square root, from Davis, 1973 (?).
 * @param numeric num Number of observations.
 * @param numeric sum Sum of values.
 * @param numeric ss Sum of squares of values.
 */
colStatsDialog.calcStandardDev = function(num, sum, ss) {
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
 * Compile numeric statistics.
 */
colStatsDialog.compileNumericStats = function(tableId, colIndex) {
//--------------------------------------------------------------------------
  var that = this;
  // this.htmlStats += 'Numeric values detected.<p />';
  var num_zeros = 0;
  var num_rows = 0;
  var num_blank = 0;
  var num_valid = 0;
  var num_valid_gtzero = 0;
  var min = 1.0e30;
  var max = -1.0e30;
  var min_gtzero = 1.0e30;
  var max_gtzero = -1.0e30;
  var sum = 0.0;
  var ss = 0.0;
  var sd = 0.0;
  var mean = 0.0;
  var project = '';
  $(tableId + ' tbody tr td:nth-child(' + colIndex.toString() + ')').each( function() {  
    if ($(this).parent().css('display') != 'none') {
      num_rows++;
      text = $(this).text();
      project = $('td:nth-child(' + colStatsDialog.graphTooltipColumn + ')', $(this).parents('tr')).text();
      if ((text.length > 0) && (text != '-')) {
        num_valid++;
        digitsText = algaefw.getDigits(text);
        that.htmlCSV += digitsText + ',';
        val = parseFloat(digitsText);
        sum += val;
        ss += (val * val);
        that.data.push([num_valid, val, sum, project]);
        if (val < min) min = val;
        if (val > max) max = val;
        if (val > 0.0) {
          num_valid_gtzero++;
          if (val < min_gtzero) min_gtzero = val;
          if (val > max_gtzero) max_gtzero = val;
        } else {
          if (val == 0.0) {
            num_zeros++;
          }
        }
      } else {
        num_blank++;
      }
    }
  });
    
  /*
  min = min_gtzero;
  max = max_gtzero;
  if (num_zeros > 0) {
    if (0 < min) min = 0;
    if (0 > max) max = 0;
  }
  */
  
  this.min = min;
  this.max = max;
  this.minGreaterThanZero = min_gtzero;
  
  this.htmlStats += '<table id="colStatsTextStatsTable" class="algae_table"><thead><tr><th>Statistic</th><th>Value</th></tr></thead><tbody>';
  this.showStatistic('Row(s) in the column', num_rows.toString(), '');
  this.showStatistic('Non-blank item(s)', num_valid.toString(), this.getPercent(num_valid, num_rows, true));
  this.showStatistic('Blank item(s)', num_blank.toString(), this.getPercent(num_blank, num_rows, true));
  this.showStatistic('Item(s) with a value of 0.0', num_zeros.toString(), this.getPercent(num_zeros, num_rows, true));
  
  // this.htmlStats += '<tr><td colspan=3 class="algaefw_table_bordered_group_header">Including Zero Values</td></tr>';
  
  this.showStatistic('N', num_valid.toFixed(0), '');
  this.showStatistic('Minimum', min.toFixed(4), '');
  this.showStatistic('Maximum', max.toFixed(4), '');
  if (num_valid > 0) {
    mean = sum / num_valid;
    this.showStatistic('Mean', mean.toFixed(4), '');
  }
  this.showStatistic('Sum', sum.toFixed(4), '');
  if (num_valid > 1) {
    sd = this.calcStandardDev(num_valid, sum, ss);
    if (sd != null) {
      this.showStatistic('Standard Deviation', sd.toFixed(4), '');
      this.showStatistic('Mean +/- 1 StdDev', (mean - sd).toFixed(4) + ' to ' + (mean + sd).toFixed(4), '');
    }
  }

  /*
  this.htmlStats += '<tr><td colspan=3 class="algaefw_table_bordered_group_header">Excluding Zero Values</td></tr>';
  this.showStatistic('Number', num_valid_gtzero.toFixed(0), '');
  this.showStatistic('Minimum', min_gtzero.toFixed(4), '');
  this.showStatistic('Maximum', max_gtzero.toFixed(4), '');
  if (num_valid_gtzero > 0) {
    this.showStatistic('Average', (sum / num_valid_gtzero).toFixed(4), '');
  }
  if (num_valid_gtzero > 1) {
    sd = this.calcStandardDev(num_valid_gtzero, sum, ss);
    if (sd != null) {
      this.showStatistic('Standard Deviation', sd.toFixed(4), '');
    }
  }
  */
  
  this.htmlStats += '</tbody></table><p />';
  
  if (num_zeros > 0) {
    this.numericFootnote = 'Statistics include zero values.<p />';
  }
}

/**
 * Draw a line graph.
 * Data order: [x, y, cumulative_y, label]
 */
colStatsDialog.drawLineGraph = function() {
//--------------------------------------------------------------------------
  var graph = new algaeLineGraph();
  graph.mainTitle = this.title;
  if (this.graphScale == this.graphScaleOptions.arithmetic) {
    graph.yAxis.varNum = 1;
    graph.yAxis.title = this.title;
  } else if (this.graphScale == this.graphScaleOptions.cumulative) {
    graph.yAxis.varNum = 2;
    graph.yAxis.title = 'Cumulative ' + this.title;
  } else if (this.graphScale == this.graphScaleOptions.log) {
    graph.yAxis.varNum = 1;
    graph.yAxis.title = 'Log(' + this.title + ')';
    graph.mainTitle = graph.yAxis.title;
  }
  if (this.graphScale == this.graphScaleOptions.log) {
    graph.yAxis.log = true;
    graph.yAxis.tickFormat = ',.1';
    graph.yAxis.gridLines = true;
    graph.yAxis.majorGridSpacing = 1.0;
    graph.yAxis.minorGridSpacing = 1.0;
  }
  if (typeof this.tableTitle !== typeof undefined) {
    graph.mainTitle = this.tableTitle;
  }
  graph.divId = 'colStatsGraph';
  graph.xAxis.title = 'Row';
  graph.addData(this.data, this.title, 'black');
  graph.tooltipPos = 1;
  graph.legend = false;
  graph.width = 470;
  graph.height = 310;
  // graph.xAxis.maxData = graph.xAxis.autoTag;
  graph.xAxis.minData = 1;
  graph.xAxis.maxData = this.data.length;
  graph.yAxis.minData = graph.yAxis.autoTag;
  graph.yAxis.maxData = graph.yAxis.autoTag;
  graph.margin.right += 5;
  graph.margin.left += 15;
  graph.draw();
}

colStatsDialog.drawGraph = function() {
//--------------------------------------------------------------------------
  if (this.data.length > 0) {
    $("#colStatsGraph").html('');
    if (this.dataType == this.dataTypeOptions.text) {
      this.graphType = this.graphTypeOptions.bar;
      this.graphScale = this.graphScaleOptions.arithmetic;
      $("#colStatsGraphType").val('bar');
      $("#colStatsGraphScale").val('arithmetic');
      this.drawBarGraph();
    } else {
      if (this.graphType == this.graphTypeOptions.line) {
        this.drawLineGraph();
      } else if (this.graphType == this.graphTypeOptions.histogram) {
        this.drawHistogram();
      } else if (this.graphType == this.graphTypeOptions.bar) {
        this.drawBarGraph();
      } else {
        alert('Graph type ' + this.graphType + ' is not yet supported.');
      }
    }
  } else {
    $("#colStatsGraph").html('Nothing to graph.');
  }
}

/**
 * Show the dialog.
 */
colStatsDialog.showDialog = function(element) {
// --------------------------------------------------------------------------
  if ($(colStatsDialog.id).length > 0) {
    
    this.data = [];
    
    // console.log('DEBUG: column = ' + $(element).parent().parent().index().toString());
    // console.log('DEBUG: id = ' + $(element).closest('table').attr('id'));
    
    var col = $(element).parent().parent().index() + 1;
    
    var data_column = $(element).parent().parent().attr('data-column');

    //
    // if the data_column attribute exisits use it, this is especially useful for
    // tables with spanned headers
    // For some browsers, `attr` is undefined; for others, `attr` is false. Check for both.
    //
    if (typeof data_column !== typeof undefined && data_column !== false) {
      col = parseInt(data_column) + 1;
    }
    
    // console.log('DEBUG: column = ' + col.toString());
    
    var tableId = '#' + $(element).closest('table').attr('id');
    var colName = $(tableId + ' thead tr th:nth-child(' + col.toString() + ')').first().text().trim();
    this.tableTitle = $(tableId).attr('title');
    this.title = colName;
    
    if (colName.length > 0) {
      $(this.id).dialog('option', 'title', colName + ' Stats');
    }
    
    this.detectDataType(tableId, col);
    
    this.htmlStats = '<div style="overflow-y:auto;max-height:375px;">';
    this.htmlCSV = '<div style="overflow-y:auto;max-height:375px;word-wrap:break-word;">';
    
    if (this.dataType == this.dataTypeOptions.text) {
      this.compileTextStats(tableId, col);
    } else {
      this.compileNumericStats(tableId, col);
    }
    //
    // ----- footnotes
    //
    this.htmlStats += '<div class="footnote">';
    this.htmlStats += this.numericFootnote;
    this.htmlStats += 'Statistics reflect filtered rows only.';
    this.htmlStats += '</div>';
    
    this.htmlStats += '</div>';
    
    //
    // ----- remove final comma if it exists
    //
    if (this.htmlCSV.slice(-1) == ',') {
      this.htmlCSV = this.htmlCSV.slice(0, -1);
    }
    this.htmlCSV += '</div>';

    $("#colStatsStatsData").html(this.htmlStats);
    $("#colStatsCSVTab").html(this.htmlCSV);
    
    this.drawGraph();

    //
    // ----- show the dialog
    //
    if (! $(this.id).dialog('isOpen')) {
      $(this.id).dialog('open');
    } else {
      $(this.id).dialog('close');
    }
  } else {
    alert('Column stats dialog div not defined, call PHP method algaeTable::addColumnStatsDialog() once.');
  }
  return this;
}