
/**

  algae framework | JavaScript support.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

/**
 * Framework global container.
 */
var algaefw = {};
algaefw.codeMirror = null;

/**
 * Setup the mini graphs dialog.
 * Div initilization is in algaeApp.closePage().
 */
algaefw.miniGraphDialog = {};
algaefw.miniGraphDialog.id = '#miniGraphDialog';
algaefw.miniGraphDialog.options = {
    autoOpen: false,
    modal: true,
    width: 400,
    height: 250,
    resizable: false
};

/**
 * Determine if a string is numeric.
 * From: http://stackoverflow.com/questions/18082/validate-decimal-numbers-in-javascript-isnumeric
 */
algaefw.isNumeric = function(str) {
//--------------------------------------------------------------------------
  return !isNaN(parseFloat(str)) && isFinite(str);
}

/**
 * Get only digits and valid numeric qualifiers from a string.
 * Specifically, removes $,% characters.
 */
algaefw.getDigits = function(str) { 
//--------------------------------------------------------------------------
  return str.replace(/,|\$|\%/g,'');
}

/**
 * Convert a number to a string with commas for thousands separators.
 * @param x The number.
 * @param decimals The number of decimals.
 * @returns String with commas for thousands separators.
 */
algaefw.getFormattedNumber = function(x, decimals) {
//--------------------------------------------------------------------------
  //
  // ----- updated version handles things like 0.0077 properly
  //       from: http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
  //
  if ( (!isNaN(x)) && (x !== 'undefined') ) {
    x = x || "";
    if (typeof x == 'string') {
      return '';
    } else {
      decimals = decimals || 0;
      var parts = x.toFixed(decimals).split(".");
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      return parts.join(".");
    }
  }
  return '';
}

/**
 * Rounds, removes trailing zeros and adds thousand separators to floating point numbers.
 * Partially from: https://github.com/dperish/prettyFloat.js/blob/master/prettyFloat.js
 * @param value The value.
 * @param precision The desired precision, 0 by default.
 * @param commas To add comma thousand separators or not, default true.
 * @returns A prettified floating point number, as a string.
 */
algaefw.prettyFloat = function(value, precision, commas) {
//--------------------------------------------------------------------------
  if (value === 0.0) return '0';
  value = value || "";
  precision = precision || 0;
  commas = commas || true;
  var rounded,
      trimmed;
  rounded = (!isNaN(precision) && parseInt(precision, 10) > 0)
      ? parseFloat(value).toFixed(parseInt(precision, 10))
      : value;
  trimmed = parseFloat(rounded).toString();
  if (commas && !isNaN(trimmed)) {
    var parts = trimmed.split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    return parts.join(".");
  }
  return trimmed;
}

/**
 * Left pad a number with zero or another character.
 * @param n The number to pad.
 * @param width Total width of the string, i.e. 2 to pad like 01.
 * @param z Character to pad with, 0 by default.
 */
algaefw.pad = function(n, width, z) {
//--------------------------------------------------------------------------
  z = z || '0';
  n = n + '';
  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
};

/**
 * Get a formatted date string like 20160305 for 3 Mar 2016.
 * @param date The date to format, if left off will default to the current date.
 */
algaefw.getDateString = function(date) {
//--------------------------------------------------------------------------
  date = date || new Date();
  var day = date.getDate();
  var month = date.getMonth() + 1;
  var year = date.getFullYear();
  return year.toString() + algaefw.pad(month, 2) + algaefw.pad(day, 2);
};

/**
 * Get a formatted date string like 03-Mar-2016.
 * @param date The date to format, if left off will default to the current date.
 */
algaefw.getDateStringMilitary = function(date) {
//--------------------------------------------------------------------------
  date = date || new Date();
  let str = date.toDateString(); // like "Fri Jul 02 2021"
  const parts = str.split(' ');
  if (parts.length == 4) {
    return parts[2] + '-' + parts[1] + '-' + parts[3];
  }
  return '';
};

/**
 *  Download html table to a .csv file.
 *  - skips hidden (filtered) rows
 *  - skips blank rows
 *  - skips tfoot section
 *  
 *  Modified from source at:
 *  http://www.jqueryscript.net/table/jQuery-Plugin-To-Convert-HTML-Table-To-CSV-tabletoCSV.html
 *  
 *  @param selected JQuery style selector to select the table(s) to export.  Defaults to all tables.
 *  @param fileSuffix Optional suffix to use for the download filename.  File will be date coded
 *  by convention.  For example 20160305.csv by default, 20160305_projects_master.csv with a
 *  specified fileSuffix = 'projects_master'.
 */
algaefw.tableToCSV = function(selector, fileSuffix) {
//--------------------------------------------------------------------------
  selector = selector || 'table';
  fileSuffix = fileSuffix || '';
  var clean_text = function(text) {
      text = text.replace(/"/g, '""');
      return '"'+text+'"';
  };
  $(selector).each(function() {
      var table = $(this);
      var title = [];
      var rows = [];
      $(this).children('tbody,thead').each(function(){
        $(this).find('tr').each(function(){
          if ($(this).css('display') != 'none') {
            var data = [];
            var numNonBlank = 0;
            $(this).find('th').each(function(){
              var text = clean_text($(this).text().trim());
              title.push(text);
            });
            $(this).find('td').each(function(){
              var text = $(this).text().trim();
              if (text.length > 0) numNonBlank++;
              text = clean_text(text);
              data.push(text);
            });
            if (numNonBlank > 0) {
              data = data.join(",");
              rows.push(data);
            }
          }
        });
      });
      title = title.join(",");
      rows = rows.join("\n");
      var csv = title + '\n' + rows;
      var uri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
      var download_link = document.createElement('a');
      download_link.href = uri;
      var df = algaefw.getDateString();
      if (fileSuffix.length > 0) df = df + '_' + fileSuffix;
      df = df + '.csv';
      download_link.download = df;
      document.body.appendChild(download_link);
      download_link.click();
      document.body.removeChild(download_link);
  });
};

/**
 * Convert a textarea into a CodeMirror area.
 * @param id Id of the textarea conrol.
 * @param mode Mode, examples 'text/x-plsql', 'text/x-sh'.
 * @param height Height of the CodeMirror area in pixels.
 */
algaefw.makeTextareaCodeMirror = function(id, mode, height = 600) {
//--------------------------------------------------------------------------
  algaefw.codeMirror = CodeMirror.fromTextArea(document.getElementById(id), {
    mode: mode,
      indentWithTabs: true,
      smartIndent: true,
      lineNumbers: true,
      matchBrackets : true,
      autofocus: true,
      theme: 'eclipse'
  });
  algaefw.codeMirror.setSize(null, height);
}

/**
 * Convert a textarea into a SQL code area.
 * @param id Id of the textarea conrol.
 * @param height Height of the CodeMirror area in pixels.
 */
algaefw.makeTextareaSQL = function(id, height = 600) {
//--------------------------------------------------------------------------
  algaefw.makeTextareaSQL(id, 'text/x-plsql', height);
}

/**
 * Show a mini graph dialog (or really any dialog).
 * @param title Dialog title.
 * @param html HTML to go in the dialog.
 */
algaefw.showMiniGraphDialog = function(title, html) {
//--------------------------------------------------------------------------
  if ($(this.miniGraphDialog.id).length > 0) {
    //
    // ----- initial dialog
    //
    $(this.miniGraphDialog.id).dialog(this.miniGraphDialog.options);
    $(this.miniGraphDialog.id).dialog('option', 'title', title);
    $(this.miniGraphDialog.id).html(html);
    //
    // ----- show the dialog
    //
    if (! $(this.miniGraphDialog.id).dialog('isOpen')) {
      $(this.miniGraphDialog.id).dialog('open');
    } else {
      $(this.miniGraphDialog.id).dialog('close');
    }
  } else {
    alert('Mini graph dialog div not defined, make sure algaeApp.closePage() has been called.');
  }
  return this;
}

/**
 * Get a color block, for example to use with a label.
 * Mimics the PHP function in algaeCore with the same name.
 * @param string color The color as an HTML color code, i.e. '#FF0000' for red.
 * @param boolean includeLabel (optional) Set true to include a label, default false.
 * @param string label (optional) Label to include, the color string by default.
 * Returns HTML string with the color block and optionally a label.
 */
algaefw.getColorBlock = function(color, includeLabel, label) {
//--------------------------------------------------------------------------
  let html = '<span style="background:' + color + ';">&nbsp;&nbsp;&nbsp;</span>';
  if (includeLabel === undefined) { includeLabel = false; }
  if (label === undefined) { label = color; }
  if (includeLabel) {
    html += '&nbsp;&nbsp;';
    html += label;
  }
  return html;
}

/**
 * Show a dialog.
 * @param dialog Dialog object, see examples.
 * @param title Dialog title.
 * @param html HTML to go in the dialog.
 * @param invalidateMapSize Use true for a map dialog so the map is displayed properly.
 */
algaefw.showDialog = function(dialog, title, html, invalidateMapSize) {
//--------------------------------------------------------------------------
  if ($(dialog.id).length > 0) {
    if (invalidateMapSize === undefined) { invalidateMapSize = false; }
    //
    // ----- initial dialog
    //
    $(dialog.id).dialog(dialog.options);
    $(dialog.id).dialog('option', 'title', title);
    //
    // ----- for map dialogs do map.invalidateSize() on dialog open so the map is displayed properly
    //       dialogclose handler allows update of coordinate controls in calling form
    //
    if (invalidateMapSize) {
      // document.activeElement.blur(); removes focus on outer map container
      $(dialog.id).on("dialogopen", function( event, ui ) { if (typeof map != 'undefined') {
          map.invalidateSize(false);
          map.getContainer().blur();
        } } );
      $(dialog.id).on("dialogclose", function( event, ui ) { algaeMap.updateCallingFormCoordinates(); } );
    }
    $(dialog.id).html(html);
    //
    // ----- show the dialog
    //
    if (! $(dialog.id).dialog('isOpen')) {
      $(dialog.id).dialog('open');
    } else {
      $(dialog.id).dialog('close');
    }
  } else {
    alert('Dialog id ' + dialog.id + ' not found.');
  }
  return this;
}










