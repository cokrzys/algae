
/**

  algae framework | Generic tabular reports generator.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

var algaeReport = {};

algaeReport.name = '';
algaeReport.active = false;
algaeReport.saving = false;
algaeReport.modified = false;
algaeReport.refresh = false;
algaeReport.numErrors = 0;
algaeReport.errorMessage = '';
algaeReport.loadingReportsList = false;
algaeReport.saveURL = ''; 
algaeReport.loadURL = '';
algaeReport.getReportListURL = '';

algaeReport.saveDialog = {};
algaeReport.saveDialog.id = '#saveDialog';
algaeReport.saveDialog.options = {
    autoOpen: false,
    modal: true,
    width: 550,
    height: 200,
    resizable: false
};

algaeReport.loadDialog = {};
algaeReport.loadDialog.id = '#loadDialog';
algaeReport.loadDialog.selectControlId = 'customReportNameToLoad';
algaeReport.loadDialog.reports = [];
algaeReport.loadDialog.options = {
    autoOpen: false,
    modal: true,
    width: 550,
    height: 200,
    resizable: false
};

/**
 * Handler fires when all pending ajax requests have been completed.  This is especially
 * used to check if all the report saves worked or not.
 */
$.ajaxStopHandler = function() {
//-------------------------------------------------------------------------- 
  // console.log('DEBUG: Ajax all stopped, numErrors = ' + numErrors.toString());
  if (algaeReport.saving == true) {
    if (algaeReport.numErrors == 0) {
      // setTargetScenarioAttributes();
      // updateMessage('All changes saved.', 'message_area_ok');
      algaeReport.modified = false;
    } else {
      //updateMessage('Problem saving the scenario. ' + errorMessage, 'message_area_error');
    }
    //
    // ----- these will be restarted when a new save is initiated
    //
    algaeReport.saving = false;  
    algaeReport.numErrors = 0;
  }
  //
  // ----- handler when loading reports is done
  //
  if (algaeReport.loadingReportsList === true) {
    algaeReport.loadingReportsList = false;
    showLoadDialog();
  }
}

/**
 * Initialization at document ready.
 */
$(function() {
//-------------------------------------------------------------------------- 
  
  if (algaeReport.active) {
	
	  $('body').on('click', '#pageRefreshButtonInOptionsForm', function() {
	    algaeReport.refresh = true;
	    save('');
	    return false;
	  }); 
	  
	  //
	  // ----- draggable
	  //
	  $( "#available_fields_list li" ).draggable({
	    containment:'document', 
	    helper:'clone', 
	    cursor:'move',
	    start: function() {
	      algaeReport.dragged_contents = $(this).html();
	      // console.log('DEBUG: source html = ' + algaeReport.dragged_contents);
	      // console.log('DEBUG: source text = ' + $(this).text());
	      algaeReport.dragged_rowid = $(this).attr('rowid');
	    }
	  });
	  
	  //
	  // ----- droppable
	  //
	  $("#selected_fields_div").droppable({
	    hoverClass:'border',
	    accept:'#available_fields_list li',
	    drop: function() {
	      addToTarget();
	    } 
	  });
	  
	  //
	  // ----- sortable
	  //
	  $( "#selected_fields_list" ).sortable().disableSelection();;
	  
	  //
	  // ----- delete fields handler
	  //
	  $('body').on('click', '.delete_item', function() {
	    deleteField($(this).parent());
	    return false;
	  });
	  
	  //
	  // ----- predefined report buttons handler
	  //
	  $('body').on('click', '.predefinedButton', function() {
	    loadReport($(this).html(), 'Yes');
	    return false;
	  });
	  
	  //
	  // ----- available fields filter
	  //
	  $('#fields_filter').on('input', function() {
	    var filterText = $(this).val().toLowerCase();
	    $('#available_fields_list li').each(function () {
	      if ( $(this).text().toLowerCase().indexOf(filterText) != -1 ) {
	        $(this).show();
	      } else {
	        var title = $(this).attr('title');
	        if (title.length > 0) {
	          if ( title.toLowerCase().indexOf(filterText) != -1 ) {
	            $(this).show();
	          } else {
	            $(this).hide();
	          }
	        } else {
	          $(this).hide();
	        }
	      }
	    });
	  });
	  
	  //
	  // ----- download button handler
	  //
	  $("#downloadButton").click(function(){
	    algaefw.tableToCSV("#MasterTable", 'projects_master');
	    return false;
	  });
	 
	  //
	  // ----- setup save and load dialog
	  //
	  $("body").append("<div id='" + algaeReport.saveDialog.id.slice(1) + "' title='Save'></div>");
	  $(algaeReport.saveDialog.id).dialog(algaeReport.saveDialog.options);
	  $("body").append("<div id='" + algaeReport.loadDialog.id.slice(1) + "' title='Load'></div>");
	  $(algaeReport.loadDialog.id).dialog(algaeReport.loadDialog.options);
	  
	  //
	  // ----- open save dialog button handler
	  //
	  $("#saveReport").click(function(){
	    showSaveDialog();
	    return false;
	  });
	  
	  //
	  // ----- open load dialog button handler
	  //
	  $("#loadReport").click(function(){
	    getReportsList();
	    return false;
	  });
	  
	  //
	  // ----- ajax stop handler, fires when all pending ajax requests have stopped
	  //
	  $(document).ajaxStop(function() {
	    $.ajaxStopHandler();
	  });
  
  }
    
});

function addToTarget() {
//--------------------------------------------------------------------------  
  var existing_project = $("#selected_fields_list [rowid='" + algaeReport.dragged_rowid + "']");
  if (existing_project.length) {
    // console.log('DEBUG: Matched existing project.');
    return;
  }
  var attribs = ' rowid="' + algaeReport.dragged_rowid + '"';
  var deleteLink = '<a href="#" class="delete_item align_right"><img src="/algae/img/red_minus_128px.png" height="18px" width="18px" style="margin-top:3px;" /></a>';
  // console.log('DEBUG: ' + algaeReport.dragged_contents);
  // console.log('DEBUG: ' + attribs);
  // <span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
  $("#selected_fields_list").append('<li class="ui-state-default"' + attribs + '>' + 
      algaeReport.dragged_contents + deleteLink + '</li>');
  // updateHeader(parseFloat(new_budget), new_target_group, parseFloat(new_au_target));
  // setModified();
}

function deleteField(itemToDelete) {
//--------------------------------------------------------------------------  
  if (itemToDelete.length > 0) {
    itemToDelete.remove();
    // setModified();
  }
}

/**
 * Declare this same function in a "derived" js file to pass optional data.
 * @returns JSON object with optional data.
 */
function getOptionalData() {
//--------------------------------------------------------------------------
  return {};
}

/**
 * Save the report.
 */
function save(version) {
// --------------------------------------------------------------------------
  if (algaeReport.name.length == 0) {
	  alert("algaeReport.name not set.");
	  return;
  }
  if (algaeReport.saveURL.length == 0) {
	  alert("algaeReport.saveURL not set.");
	  return;
  }
  var data = [];
  //
  // ----- loop and get each row in the current report
  //
  $('#selected_fields_list li').each(function(i, li) {
    var field_rowid = $(li).attr('rowid');
    //
    // ----- loop through the items in the list and add them to an array 
    //
    if (field_rowid != null) {
      var item = new Object();
      item.field_rowid = field_rowid;
      data.push(item); 
    }
  });
  //
  // ----- json for ajax call
  //
  var json_data = { 
      'name' : algaeReport.name,
      'version' : version,
      'data' : data,
      'optionalData' : getOptionalData()
      };
  //
  // ----- make ajax call to save the report
  //
  jQuery.ajax({
    'url' : algaeReport.saveURL,
    'data' : json_data,
    'type' : 'post',
    'dataType' : 'json',
    'cache' : false,
    'success' : function(data) {
      if (data.status != 'success') {
        algaeReport.numErrors++;
        algaeReport.errorMessage = 'Unable to save all or part of the data on the server.';
        alert(algaeReport.errorMessage);
      } else {
        if (algaeReport.refresh) {
          // location.reload(false);
          window.location.replace(location.pathname);
        }
        // alert('DEBUG: Save ok.');
      }
    },
    'error': function(xhr, textStatus, errorThrown) {
      algaeReport.numErrors++;
      algaeReport.errorMessage = 'Problem saving the report, the connection may be lost.';
      alert(algaeReport.errorMessage);
    }
  });
}

/**
 * Load a report.
 */
function loadReport(name, predefined) {
// --------------------------------------------------------------------------
  if (algaeReport.loadURL.length == 0) {
	  alert("algaeReport.loadURL not setup in call from algaeReport.js");
	  return;
  }
  var data = [];
  //
  // ----- setup data for json to pass to php on the server
  //
  var json_data = {'name':name, 'predefined':predefined};
  //
  // ----- make ajax call to get a report
  //
  jQuery.ajax({
    'url' : algaeReport.loadURL,
    'data' : json_data,
    'type' : 'post',
    'dataType' : 'json',
    'cache' : false,
    'success' : function(data) {
      if (data.status != 'success') {
        algaeReport.numErrors++;
        algaeReport.errorMessage = 'Unable to load the report.';
        alert(algaeReport.errorMessage);
      } else {
        $("#selected_fields_list").empty();
        // console.log(data);
        var len = data.fields.length;
        for (var i = 0; i < len; i++) {
          algaeReport.dragged_rowid = data.fields[i][0];
          algaeReport.dragged_contents = data.fields[i][1];
          addToTarget();
        }
      }
    },
    'error': function(xhr, textStatus, errorThrown) {
      algaeReport.numErrors++;
      algaeReport.errorMessage = 'The connection may be lost.';
      alert(algaeReport.errorMessage);
    }
  });
}

/**
 * Show save dialog to save a custom report.
 */
function showSaveDialog() {
// --------------------------------------------------------------------------
  var dlg = $(algaeReport.saveDialog.id);
  if (dlg.length > 0) {
    
    html = '<div id="loadSaveDialogInnerDiv">';
    html += 'Report:&nbsp;&nbsp;<input type="text" size="40" id="customReportName" />';
    html += '<p />';
    html += '<button class="ui-button ui-widget ui-corner-all" id="dialogSaveReportButton">Save</button>';
    html += '</div>';
    dlg.html(html);
    
    $("#dialogSaveReportButton").click(function() { saveCustomReport(); });
      
    //
    // ----- show the dialog
    //
    if (! dlg.dialog('isOpen')) {
      dlg.dialog('open');
    } else {
      dlg.dialog('close');
    }
  } else {
    alert('Dialog does not exist.');
  }
  return this;
}

function saveCustomReport() {
//--------------------------------------------------------------------------
  var dlg = $(algaeReport.saveDialog.id);
  if (dlg.length > 0) {
    var reportName = $("#customReportName").val();
    if (reportName.length > 0) {
      save(reportName);
    }
    dlg.dialog('close');
  }
}

/**
 * Load a list of custom reports.
 */
function loadReportsList() {
// --------------------------------------------------------------------------
  if (algaeReport.getReportListURL.length == 0) {
	  alert("algaeReport.getReportListURL not setup in call from algaeReport.js");
	  return;
  }
  var data = [];
  //
  // ----- setup data for json to pass to php on the server
  //
  var json_data = {};
  //
  // ----- make ajax call to get a report
  //
  jQuery.ajax({
    'url' : globl.getReportListURL,
    'data' : json_data,
    'type' : 'post',
    'dataType' : 'json',
    'cache' : false,
    'success' : function(data) {
      if (data.status != 'success') {
        algaeReport.numErrors++;
        algaeReport.errorMessage = 'Unable to load the report.';
        alert(algaeReport.errorMessage);
      } else {
        $("#selected_fields_list").empty();
        // console.log(data);
        var len = data.fields.length;
        for (var i = 0; i < len; i++) {
          algaeReport.dragged_rowid = data.fields[i][0];
          algaeReport.dragged_contents = data.fields[i][1];
          addToTarget();
        }
      }
    },
    'error': function(xhr, textStatus, errorThrown) {
      algaeReport.numErrors++;
      algaeReport.errorMessage = 'The connection may be lost.';
      alert(algaeReport.errorMessage);
    }
  });
}

/**
 */
function getReportsList() {
//--------------------------------------------------------------------------   
  if (algaeReport.getReportListURL.length == 0) {
	  alert("algaeReport.getReportListURL not setup in call from algaeReport.js");
	  return;
  }
  var data = [];
  algaeReport.loadingReportsList = true;
  jQuery.ajax({
    'url' : algaeReport.getReportListURL,
    'type' : 'post',
    'dataType' : 'json',
    'cache' : false,
    'success' : function(data) {
      if (data.status == 'success') {
        if (data.num_reports > 0) {
          algaeReport.loadDialog.reports = data.reports;
        } else {
          alert('No custom reports saved to load.');
        }
      }
    }
  });
  return false;
}

/**
 * 
 */
function loadCustomReport() {
//--------------------------------------------------------------------------
  var reportName = $('#' + algaeReport.loadDialog.selectControlId + ' option:selected').text();
  if (reportName.length > 0) {
    console.log('DEBUG: Selected ' + reportName);
    loadReport(reportName, 'No');
    var dlg = $(algaeReport.loadDialog.id);
    dlg.dialog('close');
  }
}

/**
 * Show dialog to select and load a custom saved report.
 */
function showLoadDialog() {
// --------------------------------------------------------------------------
  var dlg = $(algaeReport.loadDialog.id);
  var loadButtonControlId = 'dialogLoadReportButton';
  if (dlg.length > 0) {
    if (algaeReport.loadDialog.reports.length > 0) {
      //
      // ----- add controls to form
      //
      html = '<div id="loadSaveDialogInnerDiv">';
      html += 'Report:&nbsp;&nbsp;<select id="' + algaeReport.loadDialog.selectControlId + '"></select>';
      html += '<p />';
      html += '<button class="ui-button ui-widget ui-corner-all" id="' + loadButtonControlId + '">Load</button>';
      html += '</div>';
      dlg.html(html);
      $('#' + loadButtonControlId).click(function() { loadCustomReport(); });
      //
      // ----- add report names to the selection control
      //
      var selectCtrl = $('#' + algaeReport.loadDialog.selectControlId);
      var arrayLength = algaeReport.loadDialog.reports.length;
      for (var i = 0; i < arrayLength; i++) {
        selectCtrl.append($('<option/>', { 
            text : algaeReport.loadDialog.reports[i].name 
          }));
      }
      selectCtrl.selectmenu();
      //
      // ----- open the dialog
      //
      if (! dlg.dialog('isOpen')) {
        dlg.dialog('open');
      } else {
        dlg.dialog('close');
      }
    } else {
      alert('No custom reports saved to pick from.');
    }
  } else {
    alert('Dialog does not exist.');
  }
  return this;
}




