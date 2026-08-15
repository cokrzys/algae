
/**

  algae framework | Supplemental tablesorter functions.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

/**
 * Add a parser for military style date, i.e. 18-Feb-2013.
 */
function addMilDateParser() {
//--------------------------------------------------------------------------   
  $.tablesorter.addParser({ 
    id: 'milDate', 
    is: function(s) { 
        // return false so this parser is not auto detected 
        return false; 
    }, 
    format: function(s) { 
      return new Date(s).getTime() || '';
    }, 
    type: 'numeric' 
  }); 
}

/**
 * Add a parser for thousands with an optional dollar sign, for example $10,000.
 * This is from: http://stackoverflow.com/questions/9027438/jquery-tablesorter-not-sorting-column-with-formatted-currency-value
 */
function addThousandsParser() {
//--------------------------------------------------------------------------   
  $.tablesorter.addParser({ 
    // set a unique id 
    id: 'thousands',
    is: function(s) { 
        // return false so this parser is not auto detected 
        return false; 
    }, 
    format: function(s) {
        // format your data for normalization 
        return s.replace('$','').replace(/,/g,'');
    }, 
    // set type, either numeric or text 
    type: 'numeric' 
  }); 
}



