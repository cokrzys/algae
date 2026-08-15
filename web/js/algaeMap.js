
/**

  algae framework | Map support.
    
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

/**
 * Global container.
 * Serve as defaults as of Apr 2025, typically overridden by PHP calls.
 * TODO: Eventually all map support should be in the container.
 */
var algaeMap = {};

algaeMap.divID = 'algaeMap';
algaeMap.centerLatitude = 10;
algaeMap.centerLongitude = 0;
algaeMap.zoom = 3;
algaeMap.zoomToBounds = false;
algaeMap.showLayerControl = true;
algaeMap.boundsMinLatitude = -90;
algaeMap.boundsMaxLatitude = 90;
algaeMap.boundsMinLongitude = -180;
algaeMap.boundsMaxLongitude = 180;
algaeMap.clickedLat = null; // used principally to store clicked coords on a map dialog so they can be displayed in a calling form
algaeMap.clickedLong = null;
algaeMap.latControlId = 'latitude'; // keep track of coordinate input controls
algaeMap.longControlId = 'longitude';

/**
 * Map dialog.
 * Div initilization is in algaeApp.closePage().
 */
algaeMap.mapDialog = {};
algaeMap.mapDialog.id = '#mapDialog';
algaeMap.mapDialog.options = {
    autoOpen: false,
    modal: true,
    width: 500,
    height: 500,
    resizable: false
};

/**
 * Show a modal dialog with a map typically to pick a location and feed it back to a form.
 * Control IDs are used to get values and setup the map displayed in the dialog.
 * @param centerLatId ID of latitude control from a calling form.
 * @param centerLongId ID of longitude control from a calling form.
 * @param zoomLevelId ID of zoom level control from a calling form.
 */
algaeMap.showMapDialog = function(centerLatId, centerLongId, zoomLevelId) {
//--------------------------------------------------------------------------
  let html = '<div id="algaeMap"></div><script type="text/javascript">showMap();</script>';
  //
  // ----- get current values from controls to adjust map display
  //
  let latControl = $("#" + centerLatId);
  let longControl = $("#" + centerLongId);
  let zoomControl = $("#" + zoomLevelId);
  if (typeof latControl !== typeof undefined) { this.centerLatitude = parseFloat(latControl.val()); }
  if (typeof longControl !== typeof undefined) { this.centerLongitude = parseFloat(longControl.val()); }
  if (typeof zoomControl !== typeof undefined) { this.zoom = parseInt(zoomControl.val()); }
  //
  // ----- defaults if not set
  //
  if (isNaN(this.centerLatitude)) { this.centerLatitude = 39; }
  if (isNaN(this.centerLongitude)) { this.centerLongitude = -105.5; }
  if (isNaN(this.zoom)) { this.zoom = 6; }
  //
  // ----- save control ids to write coordinates to when clicking the map dialog for a location
  //
  this.latControlId = centerLatId;
  this.longControlId = centerLongId;
  //
  // ----- show map dialog
  //
  algaefw.showDialog(this.mapDialog, 'Map', html, true);
  return this;
}

/**
 * Update coordinates in a calling form when a location is selected from a modal map dialog.
 * Control IDs are saved from when the map dialog was opened.
 */
algaeMap.updateCallingFormCoordinates = function() {
//--------------------------------------------------------------------------
  let latControl = $("#" + this.latControlId);
  let longControl = $("#" + this.longControlId);
  if ((typeof latControl !== typeof undefined) && (this.clickedLat !== null)) {
    latControl.val(this.clickedLat.toFixed(6));
  }
  if ((typeof longControl !== typeof undefined) && (this.clickedLong !== null)) {
    longControl.val(this.clickedLong.toFixed(6));
  }
}

//
// ----- sidebar is global because it's used in leaflet.groupedlayercontrol.js
//       other globals added to support independent zoomToPolygon function.
//
var sidebar;
var map;
var popup;

//
// ----- Global callbacks/events to allow loose coupling
//

var mapDataChanged = $.Callbacks();
var mapFeatureDrawn = $.Callbacks();
var selectedFeatureChanged = $.Callbacks();
var latlongInputsChanged = $.Callbacks();

/**
 * Get list of active layer wms names as a comma delimited string, this is a helper for
 * the call to GetFeatureInfo.
 * @returns {String} Comma delimited list of wms layer names that are turned on.
 */  
function getListOfActiveLayers() {
// --------------------------------------------------------------------------
  var sep = '';
  var layerList = '';
  if (typeof algaeMap.layers != 'undefined') {
    for (var i=0; i < algaeMap.layers.length; i++) {
      if ( (algaeMap.layers[i].hasOwnProperty('tileLayer')) &&
           (map.hasLayer(algaeMap.layers[i].tileLayer)) &&
           (algaeMap.layers[i].queryable) ) {
        layerList = layerList + sep + algaeMap.layers[i].wmsName;
        sep = ',';
      }
    }
  }
  // console.log('DEBUG: layerList = ' + layerList);
  return layerList;
}

/**
 * Update coordinates on a form with the lat-long mouse location when clicked.
 * @param e Mouse event with coordinates in e.latlng.lat and e.latlng.lng.
 * @returns
 */
function updateFormCoordinates(e) {
//--------------------------------------------------------------------------
  let latControl = $("#latitude");
  let longControl = $("#longitude");
  if (typeof latControl !== typeof undefined) {
    latControl.val(e.latlng.lat.toFixed(6));
  }
  if (typeof longControl !== typeof undefined) {
    longControl.val(e.latlng.lng.toFixed(6));
  }
}

/**
 * When clicking on the map make a call to the WMS GetFeatureInfo to identify objects.
 * @param e The click event.
 * @returns {Boolean}
 */
function onMapClick(e) {
// --------------------------------------------------------------------------
  //
  // ----- update coordinates on a form with the lat-long mouse location when clicked
  //
  if (algaeMap.updateFormCoordinates) {
    updateFormCoordinates(e);
  }
  //
  //
  //
  if (typeof algaeMap.mapDialog !== typeof undefined) {
    let mapDialogControl = $(algaeMap.mapDialog.id);
    if (typeof mapDialogControl !== typeof undefined) {
      algaeMap.clickedLat = e.latlng.lat;
      algaeMap.clickedLong = e.latlng.lng;
      mapDialogControl.dialog('close');
    }
  }
  
  // console.log('DEBUG: algaeMap here A.');

  var layerList = getListOfActiveLayers();
  if (layerList.length > 0) {
    
    // console.log('DEBUG: algaeMap here B.');
    //
    // ----- map corners in epsg3857 coords
    //
    var sw = L.CRS.EPSG3857.project(map.getBounds()._southWest);
    // console.log('DEBUG: sw projected = ' + sw.x + ' ' + sw.y);
    var ne = L.CRS.EPSG3857.project(map.getBounds()._northEast);
    // console.log('DEBUG: ne projected = ' + ne.x + ' ' + ne.y);
    
    //
    // ----- Bounding box for map extent. Value is minx,miny,maxx,maxy in units of the SRS.
    //
    var BBOX = sw.x + ',' + sw.y + ',' + ne.x + ',' + ne.y;
    var WIDTH = map.getSize().x;
    var HEIGHT = map.getSize().y;
    var SRS = map.options.crs.code;
    var X = map.layerPointToContainerPoint(e.layerPoint).x;
    var Y = map.layerPointToContainerPoint(e.layerPoint).y;
    var URL = algaeMap.url
        + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&LAYERS='
        + layerList + '&QUERY_LAYERS=' + layerList + '&STYLES=&BBOX=' + BBOX
        + '&HEIGHT=' + HEIGHT + '&WIDTH=' + WIDTH
        + '&FORMAT=image%2Fpng&INFO_FORMAT=text%2Fhtml&SRS=' + SRS + '&X='
        + X + '&Y=' + Y;
    
    // console.log('DEBUG: url = ' + URL);

    $.ajax({
      url : URL,
      dataType : "html",
      type : "GET",
      success : function(data) {
        if (data.length > 0) {
          popup.setContent(data);
          popup.setLatLng(e.latlng);
          popup.openOn(map);
        }
      }
    });

  }
  return false;
}

/**
 * Zoom map to a specific area.
 */
function zoomToBounds() {
//--------------------------------------------------------------------------  
  if (algaeMap.zoomToBounds) {
    // console.log('DEBUG: zooming to bounds.');
    var southWest = L.latLng(algaeMap.boundsMinLatitude, algaeMap.boundsMinLongitude);
    var northEast = L.latLng(algaeMap.boundsMaxLatitude, algaeMap.boundsMaxLongitude);
    var bounds = L.latLngBounds(southWest, northEast);  
    // var zoom = map.getBoundsZoom(bounds);
    // var center = bounds.getCenter();
    // console.log('DEBUG: center.lat = ' + center.lat.toString());
    // console.log('DEBUG: center.lng = ' + center.lng.toString());
    // map.setView(center, zoom);
    map.fitBounds(bounds);
    map.invalidateSize(true);
  }
}

/**
 * Map function to show a map made with the algaeMap PHP class.
 */
function showMap() {
//--------------------------------------------------------------------------

  let groupedLayers = {};
  popup = new L.Popup({maxWidth: 500});
  
  // console.log('DEBUG: Leaflet version = ' + L.version);
  
  // console.log('DEBUG: In showMap() divId = ' + algaeMap.divID);
  // console.log('DEBUG: In showMap() centerLatitude = ' + algaeMap.centerLatitude.toString());
  
  //
  // ----- create the map
  //
  map = new L.Map(algaeMap.divID, {center: new L.LatLng(algaeMap.centerLatitude, algaeMap.centerLongitude), zoom: algaeMap.zoom});
  
  if (algaeMap.showSidebar) {
    //
    // ----- create the sidebar for a legend, it's hidden to start
    //
    // sidebar = L.control.sidebar('sidebar', {position: 'right', closeButton: true});
    // map.addControl(sidebar);
    
    //
    // ----- add zoom controls and legend display/hide toolbar button
    //
    // var viewCenter = new L.Control.ViewCenter();
    // map.addControl(viewCenter);
  }
    
  //
  // ----- add the mouse position coordinates
  //
  L.control.mousePosition({separator: ", "}).addTo(map);  
  
  //
  // ----- control to toggle full-screen
  //
  L.control.fullscreen({position: 'topleft'}).addTo(map);
  
  //
  // ----- locate control
  //
  L.control.locate().addTo(map);
  
  //
  // ----- zoom to a specific area if applicable
  //
  zoomToBounds();
  
  //
  // ----- highlight and zoom to a point (marker) if applicable
  //
  if (algaeMap.addMarker) {   
    if (algaeMap.markerTitle != 'undefined') {
      L.marker([algaeMap.centerLatitude, algaeMap.centerLongitude], {title: algaeMap.markerTitle}).addTo(map).bindPopup(algaeMap.markerTitle);
    } else {
      L.marker([algaeMap.centerLatitude, algaeMap.centerLongitude]).addTo(map);
    }
  }
  
  var rtspatial = '<a href="https://www.rtspatial.com">RTSpatial</a> |';
  
  //
  // ----- create the google base layers
  //
  var satellite = new L.TileLayer('https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
            {subdomains:'0123', attribution:' &copy; ' + rtspatial + ' Basemap &copy; Google 2012', zIndex: 0, noWrap: true}
            );
  var roadmap = new L.TileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
            {subdomains:'0123', attribution:' &copy; ' + rtspatial + ' Basemap &copy; Google 2012', zIndex: 0, noWrap: true}
            );
  var terrain = new L.TileLayer('https://mt{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
            {subdomains:'0123', attribution:' &copy; ' + rtspatial + ' Basemap &copy; Google 2012', zIndex: 0, noWrap: true}
            );  
  
  var mbDark = new L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
	  attribution: ' &copy; ' + rtspatial + ' © <a href="https://www.mapbox.com/about/maps/">Mapbox</a> | © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> <strong><a href="https://www.mapbox.com/map-feedback/" target="_blank">Improve this map</a></strong>',
	  tileSize: 512,
	  maxZoom: 18,
	  zoomOffset: -1,
	  noWrap: true,
	  id: 'mapbox/dark-v10',
	  accessToken: ''
	  });
  
  var mbLight = new L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
	  attribution: ' &copy; ' + rtspatial + ' © <a href="https://www.mapbox.com/about/maps/">Mapbox</a> | © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> <strong><a href="https://www.mapbox.com/map-feedback/" target="_blank">Improve this map</a></strong>',
	  tileSize: 512,
	  maxZoom: 18,
	  zoomOffset: -1,
	  noWrap: true,
	  id: 'mapbox/light-v10',
	  accessToken: ''
	  });
  
  mapLink = 
      '<a href="http://openstreetmap.org">OpenStreetMap</a>';
  var osm = new L.tileLayer(
      'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: ' &copy; ' + rtspatial + ' &copy; ' + mapLink + ' Contributors',
      maxZoom: 18,
      noWrap: true
      });
  
  //
  // ----- add overlay layers defined in algaeMap, these are the layers that a user
  //       can see with his/her rights
  //
  if (algaeMap.hasOwnProperty('layers')) {
    // console.log('DEBUG: Map has ' + algaeMap.layers.length + ' user layers.');
    // console.log('DEBUG: mapfile = ' + algaeMap.url);
    for (var i=0; i < algaeMap.layers.length; i++) {
      // console.log('DEBUG: legendName = ' + algaeMap.layers[0].legendName);
      // console.log('DEBUG: layerName = ' + algaeMap.layers[i].wmsName);
      algaeMap.layers[i].tileLayer = L.tileLayer.wms(algaeMap.url, {
        layers: algaeMap.layers[i].wmsName,
        format: 'image/png',
        transparent: true,
        zIndex: algaeMap.layers[i].zIndex,
        noWrap: algaeMap.layers[i].noWrap
        // handled above in tile layer definitions
        // attribution: '&copy; <a href="https://www.rtspatial.com">RTSpatial</a> |'  
      });
      if (algaeMap.layers[i].hasOwnProperty('tileLayer')) {
        if (algaeMap.layers[i].wmsName === 'country') {
          algaeMap.countryLayerIndex = i;
          console.log('DEBUG: Found country base layer at index ' + i.toString());
        } else {
          if (algaeMap.layers[i].visible) {
            // console.log('DEBUG: Layer ' + algaeMap.layers[i].wmsName + ' is on.');
            map.addLayer(algaeMap.layers[i].tileLayer);
          }
          //
          // ----- setup structure for the grouped layer control picker
          //
          if (groupedLayers[algaeMap.layers[i].group] == null) {
            console.log('DEBUG: group = ' + algaeMap.layers[i].group);
            groupedLayers[algaeMap.layers[i].group] = {};
          }
          console.log('DEBUG: ' + algaeMap.layers[i].legendName)
          groupedLayers[algaeMap.layers[i].group][algaeMap.layers[i].legendName] = algaeMap.layers[i].tileLayer;
          // console.log('DEBUG: ' + groupedLayers.length.toString());
        }
      }
    }
  }
  
  //
  // ----- define group of base layers
  //    
  var baseLayers;

  //
  // ----- turn on default base layer
  //

    baseLayers = {
        "OpenStreeetMap": osm,
        "Google Terrain": terrain,
          "Google Satellite": satellite,
          "Google Streets": roadmap,
          "MapBox Dark": mbDark,
          "MapBox Light": mbLight
      };

    map.addLayer(osm);
    
  
  
  //
  // ----- add layer control, note this has to be after having at least one layer turned on
  //
  if (algaeMap.showLayerControl) {

    L.control.groupedLayers(baseLayers, groupedLayers).addTo(map);
  }  
  
  //
  // ----- this gets around popups not working one after another, see
  //       leaflet forumn post for more info
  //
  function onPopupClose(e) {$('.leaflet-container').first().trigger('click');}

  //
  // ----- attach event handlers to the map
  //
  map.on('click', onMapClick);
  // map.on('popupclose', onPopupClose);   
  // map.on('zoomend', function(e) {console.log('DEBUG: Zoom level = ' + map.getZoom().toString());});
  
  // ----- add a listener for the map div resizing
  $("#" + algaeMap.divID).on("resize", function() {
    map.invalidateSize(false); //false prevents animation effect on resize
  });
      
}
