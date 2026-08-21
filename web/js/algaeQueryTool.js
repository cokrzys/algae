
/**

  algae framework | Query tool JavaScript support.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

/**
 * Global variables, not great.
 */
var gettingNewSQL = false;
var editor;

var algaeQueryTool = { sqlMinHeight: 250, sqlMaxHeight: 450, sqlExpanded: false};

/**
 * Setup the schema dialog object.
 */
var schemaDialog = {};
schemaDialog.id = '#schemaDialog';
schemaDialog.options = {
    autoOpen: false,
    modal: true,
    width: 400,
    height: 500,
    resizable: false
};
schemaDialog.title = '';
schemaDialog.data = [];

/**
 * Initialization at document ready.
 */
$(function() {
//--------------------------------------------------------------------------  
	
  editor = CodeMirror.fromTextArea(document.getElementById('sql'), {
		mode: 'text/x-plsql',
	    indentWithTabs: true,
	    smartIndent: true,
	    lineNumbers: true,
	    matchBrackets : true,
	    autofocus: true,
	    theme: 'eclipse'
  });
  
  editor.setSize(null, algaeQueryTool.sqlMinHeight);
  
  if ($(schemaDialog.id).length > 0) {
    $(schemaDialog.id).dialog(schemaDialog.options);
    $(schemaDialog.id).on( "dialogopen", function( event, ui ) { schemaDialog.onOpen(); } );
  }
  
});

/**
 * Handler fires when all pending ajax requests have been completed.
 */
$.ajaxStopHandler = function() {
//-------------------------------------------------------------------------- 
  if (gettingNewSQL) {
    gettingNewSQL = false;
  }
}

function loadLastQuery() {
//--------------------------------------------------------------------------
  // console.log('DEBUG: last call, current sql rowid = ' + curSQLRowid.toString());
  gettingNewSQL = true;
  getNewSQL(curSQLRowid, -1);
}

function loadNextQuery() {
//--------------------------------------------------------------------------
  // console.log('DEBUG: next call, current sql rowid = ' + curSQLRowid.toString());
  gettingNewSQL = true;
  getNewSQL(curSQLRowid, 1);
}

function expandSQL() {
//--------------------------------------------------------------------------
  if (! algaeQueryTool.sqlExpanded) {
    editor.setSize(null, algaeQueryTool.sqlMaxHeight);
    algaeQueryTool.sqlExpanded = true;
  } else {
	editor.setSize(null, algaeQueryTool.sqlMinHeight);
	algaeQueryTool.sqlExpanded = false;  
  }
}

/**
 * Get a new last or next SQL statement via an AJAX call.
 * @param current_sql_rowid The rowid of the currently loaded SQL (if applicable).
 * @param direction The direction to get the next query from, -1 = last, 1 = next.
 * @returns {Boolean}
 */
function getNewSQL(current_sql_rowid, direction) {
//--------------------------------------------------------------------------   
  var url = 'ajax_get_query.php?current_sql_rowid=' + current_sql_rowid.toString() + '&direction=' + direction.toString();
  jQuery.ajax({
    'url' : url,
    'type' : 'post',
    'dataType' : 'json',
    'cache' : false,
    'success' : function(data) {
      if (data.status == 'success') {
        curSQLRowid = JSON.parse(data.rowid);
        editor.getDoc().setValue(data.sql);
      }
    }
  });
  return false;
};

/**
 * Show the schema dialog.
 */
schemaDialog.show = function(tableAndSchema, html) {
//--------------------------------------------------------------------------
  if ($(this.id).length > 0) {

    $(this.id).dialog('option', 'title', tableAndSchema);
    
    $(this.id).html(html);
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

/**
 * Make table sortable if it exists when the dialog opens.
 * This could not be done when the HTML table is added.
 */
schemaDialog.onOpen = function() {
//--------------------------------------------------------------------------
  var st = document.getElementById('schemaTable');
  if (st != null) {
    sorttable.makeSortable(st);
  }
}

/**
 * 
 * @returns
 */
function showSchema() {
//--------------------------------------------------------------------------
  var tableAndSchema = $('#schema'). children("option:selected"). val();
  if (tableAndSchema.length > 0) {
	  // console.log('DEBUG: tableAndSchema = ' + tableAndSchema);
	  var url = 'ajax_get_schema.php?schema_and_table=' + tableAndSchema;
	  jQuery.ajax({
	    'url' : url,
	    'type' : 'post',
	    'dataType' : 'json',
	    'cache' : false,
	    'success' : function(data) {
	      if (data.status == 'success') {
	        schemaDialog.show(tableAndSchema, data.html);
	      } else {
	        alert('Problem getting the schema for ' + tableAndSchema + '.');
	      }
	    }
	  });
  } else {
    alert('Table not selected.');
  }
  return false;
}
