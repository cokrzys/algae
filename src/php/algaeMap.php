<?php

/**

  algae framework | Map support.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
*/

class algaeMap
{
  
  protected $divId;
  public $file;
  protected $javaScriptVar;

  public $centerLatitude;
  public $centerLongitude;
  public $zoom;
  public $zoomToBounds;
  public $bounds_min_lat;
  public $bounds_max_lat;
  public $bounds_min_long;
  public $bounds_max_long;
  public $showLayerControl;
  public $addMarker;
  public $layers;
  public $updateFormCoordinates;
  
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    $this->divId = 'algaeMap';
    $this->javaScriptVar = 'algaeMap';
    $this->file = null;
    $this->centerLatitude = 10;
    $this->centerLongitude = 0;
    $this->zoom = 3;
    $this->zoomToBounds = False;
    $this->updateFormCoordinates = True;
    $this->bounds_min_lat = -90.0;
    $this->bounds_max_lat = 90.0;
    $this->bounds_min_long = -180.0;
    $this->bounds_max_long = 180.0;
    $this->showLayerControl = True;
    $this->addMarker = False;
    $this->layers = array();
  }
  
  /**
   * Get the name of the user's map file for the session.
   * @return string The name of the map file.
   */
  public function getMapFilename()
  // --------------------------------------------------------------------------
  {
    global $app;
    if (algaeFile::makeFolder($app->config->session_maps_folder))
    {
      return algaeCore::getFullPath($app->config->session_maps_folder, session_id() . '.map');
    }
    else
    {
      algaeApp::errorMessage('Unable to make or access ' . $app->config->session_maps_folder . ' in ' . __METHOD__);
    }
    return null;
  }
  
  /**
   * Write a single line to a map file.
   * @param string $str The string to write, will be followed by a PHP_EOL.
   */
  public function writeLine($str)
  // --------------------------------------------------------------------------
  {
    if ($this->file)
    {
      fwrite($this->file, $str . PHP_EOL);
    }
  }
  
  /**
   * Write the map file header information.
   */
  protected function writeHeader()
  // --------------------------------------------------------------------------
  {
    global $app;
    $this->writeLine("MAP");
    $this->writeLine("");
    $this->writeLine('CONFIG MS_ERRORFILE "' . $app->config->mapserver_error_file . '"');
    $this->writeLine("");
    $this->writeLine('NAME "algaeMap"');
    //
    // ----- not required for a WMS service, but needed when using shp2img for testing
    //
    // $this->writeLine("EXTENT 8865484.246776307 3016430.900654626 9864020.0791919 3587377.91450212");
    // $this->writeLine("SIZE 800 400");
    // $this->writeLine("IMAGECOLOR 0 255 0");
    $this->writeLine('WEB');
    $this->writeLine('METADATA');
    $this->writeLine('"wms_title" "algae WMS"');
    $this->writeLine('"wms_feature_info_mime_type" "text/html"'); # needed to return html from feature queries
    $this->writeLine('END # METADATA');
    $this->writeLine('END # WEB');
    $this->writeLine('PROJECTION');
    $this->writeLine('"init=epsg:3857"');
    $this->writeLine('END # PROJECTION');
    $this->writeLine('SYMBOL');
    $this->writeLine('NAME "circle"');
    $this->writeLine('TYPE ellipse');
    $this->writeLine('FILLED true');
    $this->writeLine('POINTS');
    $this->writeLine('1 1');
    $this->writeLine('END');
    $this->writeLine('END # CIRCLE');
  }
  
  /**
   * Start a map file that is unique for the session.
   */
  public function startMapFile()
  // --------------------------------------------------------------------------
  {
    global $app;
    $filename = $this->getMapFilename();
    $this->file = fopen($filename, 'w');
    if ($this->file)
    {
      $this->writeHeader();
    }
    else
    {
      $app->errorMessage('Unable to create map file ' . $filename . '.');
    }
  }
  
  /**
   * End the map and close it.
   */
  public function endMapFile()
  // --------------------------------------------------------------------------
  {
    if ($this->file)
    {
      $this->writeLine("");
      $this->writeLine('END # MAP');
      fclose($this->file);
      $this->file = null;
    }
  }
  
  /**
   * Send map parameters to Javascript.
   */
  protected function toJavascript()
  // --------------------------------------------------------------------------
  {
    //
    // ----- build full url for the map file
    //
    $url = 'https://' . $_SERVER["SERVER_NAME"] . '/cgi-bin/mapserv?map=' . $this->getMapFilename();
    //
    // ----- send parameters to Javascript
    //
    echo '<script type="text/javascript">';
    echo 'var ', $this->javaScriptVar, ' = {';
    echo "url:'", $url, "'";
    echo ",centerLatitude:", $this->centerLatitude;
    echo ",centerLongitude:", $this->centerLongitude;
    echo ",zoom:", $this->zoom;
    echo ",showLayerControl:" . strtolower(algaeCore::getTrueFalse($this->showLayerControl));
    echo ",zoomToBounds:" . strtolower(algaeCore::getTrueFalse($this->zoomToBounds));
    echo ",updateFormCoordinates:" . strtolower(algaeCore::getTrueFalse($this->updateFormCoordinates));
    echo ",boundsMinLatitude:", $this->bounds_min_lat;
    echo ",boundsMaxLatitude:", $this->bounds_max_lat;
    echo ",boundsMinLongitude:", $this->bounds_min_long;
    echo ",boundsMaxLongitude:", $this->bounds_max_long;
    if ($this->addMarker)
    {
      echo ",addMarker: true";
    }
    echo ",divID: '", $this->divId, "'";
    
    if (count($this->layers) > 0)
    {
      echo ',layers:[';
      $separator = '';
      foreach ($this->layers as $layer)
      {
        echo $separator, json_encode($layer->getArray(), JSON_NUMERIC_CHECK);
        $separator = ',';
      }
      echo ']';
    }
    
    echo '};';
    echo '</script>';
  }
  
  /**
   * Add a shapefile layer.
   * @param string $wmsLayerName WMS name for the layer.
   * @param string $title Title for the layer, used in the WMS metadata.
   */
  public function addShapefileLayer($filename, $geometry_type, $title, $wmsName, $zIndex, $srid_fk, $visible = True, $filter = null, $style = null, $query_template=null, $queryable = True)
  // --------------------------------------------------------------------------
  {
    if ($query_template == null)
    {
      $queryable = False;
    }
    $l = new algaeMapLayer($title, $wmsName, $zIndex, $srid_fk, $visible, $queryable);
    $this->layers[] = $l;
    
    $this->writeLine("");
    $this->writeLine("LAYER");
    $this->writeLine('NAME "' . $wmsName . '"');
    $this->writeLine("METADATA");
    $this->writeLine('"wms_title" "' . $wmsName . '"');
    $this->writeLine('"wms_enable_request" "*"');
    $this->writeLine('"wms_feature_info_mime_type" "text/html"');
    $this->writeLine("END");
    if ($visible)
    {
      $this->writeLine("STATUS ON");
    }
    else 
    {
      $this->writeLine("STATUS OFF");
    }
    $this->writeLine('DATA "' . $filename . '"');
    if ($filter != null)
    {
      $this->writeLine('FILTER ' . $filter);
    }
    $this->writeLine('TOLERANCE 10');
    $this->writeLine('PROJECTION');
    $this->writeLine('"init=epsg:' . $srid_fk . '"');
    $this->writeLine('END');
    //
    // ----- points
    //
    if (stripos($geometry_type, 'Point') != False)
    {
      $this->writeLine("TYPE POINT");
      $this->writeLine('CLASS');
      if ($query_template != null)
      {
        $this->writeLine("TEMPLATE 'templates/" . $query_template . "'");
      }
      # echo 'DEBUG: style = ', $style, '<p />';
      # error_log('algaeDebug: ' . $title . ', style = ' . $style, 0);
      if ($style != null)
      {
        $this->writeLine($style);
      }
      else 
      {
        $this->writeLine('STYLE');
        $this->writeLine('SYMBOL "circle"');
        $this->writeLine('SIZE 8');
        $this->writeLine('COLOR 255 255 255');
        $this->writeLine('OUTLINECOLOR 0 0 0');
        $this->writeLine('END');
        # $this->writeLine('END');
      }
      $this->writeLine('END');
    }
    elseif (stripos($geometry_type, 'Polygon') != False) 
    {
      //
      // ----- polygons
      //
      $this->writeLine("TYPE POLYGON");
      $this->writeLine('CLASS');
      if ($query_template != null)
      {
        $this->writeLine("TEMPLATE 'templates/" . $query_template . "'");
      }
      if ($style != null)
      {
        $this->writeLine($style);
      }
      else
      {
        $this->writeLine('STYLE');
        $this->writeLine('OUTLINECOLOR 0 0 0');
        $this->writeLine('WIDTH 1');
        $this->writeLine('END');
      }
      $this->writeLine('END');
    }
    else
    {
      //
      // ----- all other geometries
      //
      $this->writeLine("TYPE LINE");
      $this->writeLine('CLASS');
      $this->writeLine('STYLE');
      if ($style != null)
      {
        $this->writeLine($style);
      }
      else 
      {
        $this->writeLine('COLOR 0 0 0');
      }
      $this->writeLine('END');
      $this->writeLine('END');
    }
    $this->writeLine('END # Shapefile Layer');
  }
  
  /**
   * 
   * @param string $filename Fully pathed filename.
   * @param string $wmsLayerName Name for the WMS layer.
   * @param string $title Title.
   * @param integer $zIndex
   * @param integer $srid_fk
   * @param number $opacity Default 75, smaller for more transparency, larger for less.
   */
  public function addRasterLayer($filename, $wmsLayerName, $title, $zIndex, $srid_fk, $opacity = 75)
  // --------------------------------------------------------------------------
  {
    // $l = new algaeMapLayer($wmsLayerName);
    $l = new algaeMapLayer($title, $wmsLayerName, $zIndex, $srid_fk, True, False);
    
    $this->layers[] = $l;
    
    $this->writeLine("");
    $this->writeLine("LAYER");
    $this->writeLine('NAME "' . $title . '"');
    $this->writeLine('METADATA');
    $this->writeLine('"wms_title" "' . $wmsLayerName . '"');
    $this->writeLine('"wms_enable_request" "*"');
    $this->writeLine('"wms_feature_info_mime_type" "text/html"');
    $this->writeLine('END # METADATA');
    $this->writeLine('PROJECTION');
    $this->writeLine('"init=epsg:' . $srid_fk . '"');
    $this->writeLine('END');
    $this->writeLine('STATUS ON');
    $this->writeLine('DATA "' . $filename . '"');
    $this->writeLine('OPACITY ' . $opacity); // make this smaller for more transparency
    $this->writeLine('TYPE RASTER');
    $this->writeLine('END # Raster Layer');
  }
  
  public function addLayer($layer)
  // --------------------------------------------------------------------------
  {
    $this->layers[] = $layer;
    $layer->addToMapFile($this->file);
  }
  
  /**
   * Show the map, write information for Javascript, create the legend, create Javascript call to render the map.
   * @param boolean $showMap True (default) to include Javascript that actually shows the map, otherwise the map is just
   * setup and you need to call Javascript showMap(); independently.  This was added to support showing the map in
   * a jQueryUI dialog.
   */
  public function show($showMap = True)
  // --------------------------------------------------------------------------
  {
    $this->toJavascript();
    echo '<div id="algaeMapOuterDiv"><div id="', $this->divId, '"></div></div>';
    // echo '<div id="', $this->divId, '" style="width:600px;height:400px;"';
    // echo '></div>';
    if ($showMap)
    {
      algaeCore::addJavaScript('showMap();');
    }
  }
  
}





