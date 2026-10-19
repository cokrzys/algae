<?php
  
/**

  algae framework | Map layer support.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
*/

class algaeMapLayer
{	  	
	
	public $group;
	public $title;
	public $wmsName;
	public $legendName;
	public $description;
	public $visible;
	public $zIndex;
	public $legendText;
	public $subLegendHTML;
  public $noWrap;
  public $hasLegendImage;
  public $queryable;
  public $srid_fk;
	
	/**
	 * Constructor with basic info.
	 */
	public function __construct($title, $wmsName, $zIndex, $srid_fk, $visible = True, $queryable = True)
	// --------------------------------------------------------------------------
	{
		$this->init();
		$this->title = $title;
		$this->wmsName = $wmsName;
		$this->legendName = $title;
		$this->zIndex = $zIndex;
		$this->srid_fk = $srid_fk;
		$this->visible = $visible;
		$this->queryable = $queryable;
	}  	
	
  /**
	 * Initialize member variables to default values.
	 */
	public function init()
	// --------------------------------------------------------------------------
	{
		$this->group = 'Data';
		$this->title = '';
		$this->wmsName = '';
		$this->legendName = '';
		$this->description = '';
		$this->zIndex = 1;
		$this->visible = True;
		$this->legendText = '';
		$this->subLegendHTML = '';
		$this->hasLegendImage = True;
		$this->queryable = True;
		$this->noWrap = True;
		$this->srid_fk = 4326;
	}
	
	/**
	 * Get an array with the attributes of an object.  For example used to write to JSON
	 * with json_encode($a, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT).
	 */
	public function getArray()
	// --------------------------------------------------------------------------
	{
	  $a = array();
	  $a['group'] = $this->group;
	  $a['title'] = $this->title;
	  $a['wmsName'] = $this->wmsName;
	  $a['legendName'] = $this->legendName;
	  $a['description'] = $this->description;
	  $a['visible'] = $this->visible;
	  $a['queryable'] = $this->queryable;
	  $a['noWrap'] = $this->noWrap;
	  $a['zIndex'] = $this->zIndex;
	  $a['SRID'] = $this->srid_fk;
	  return $a;
	}
	
	public function getLegend()
	// --------------------------------------------------------------------------
	{
		$legend = '';
		$status_class = 'leaflet-legend-hidden';
		if ($this->visible)
		{
			$status_class = 'leaflet-legend-visible';
		}
		$legend = '<div id="' . $this->wmsName . '-legend" class="leaflet-legend ' . $status_class . '">';
		if ($this->hasLegendImage)
		{
		  $legend .= '<img id="' . $this->wmsName . '-legend-img" class="leaflet-legend-img-common" />';
		}
		$legend .= $this->legendText;
		$legend .= $this->subLegendHTML;
		$legend .= '</div>';
		return $legend;
	}  	
	
	/**
	 * Write a single line to a map file.
	 * @param object $mapFile An opened map file handle.
	 * @param string $str The string to write, will be followed by a PHP_EOL.
	 */
	public function writeLine($mapFile, $str)
	// --------------------------------------------------------------------------
	{
	  if ($mapFile)
	  {
	    fwrite($mapFile, $str . PHP_EOL);
	  }
	}
	
	protected function addDataSource($mapFile)
	// --------------------------------------------------------------------------
	{
	}
	
	protected function addStyling($mapFile)
	// --------------------------------------------------------------------------
	{
	}
	
	public function addToMapFile($mapFile)
	// --------------------------------------------------------------------------
	{
	  $this->writeLine($mapFile, "");
	  $this->writeLine($mapFile, "LAYER");
	  $this->writeLine($mapFile, 'NAME "' . $this->wmsName . '"');
	  $this->writeLine($mapFile, "METADATA");
	  $this->writeLine($mapFile, '"wms_title" "' . $this->title . '"');
	  $this->writeLine($mapFile, '"wms_enable_request" "*"');
	  $this->writeLine($mapFile, '"wms_feature_info_mime_type" "text/html"');
	  $this->writeLine($mapFile, "END");
	  $this->writeLine($mapFile, "STATUS ON");
	  $this->writeLine($mapFile, 'TOLERANCE 5');
	  $this->writeLine($mapFile, 'PROJECTION');
	  $this->writeLine($mapFile, '"init=epsg:' . $this->srid_fk . '"');
	  $this->writeLine($mapFile, 'END');
	  $this->addDataSource($mapFile);
	  $this->addStyling($mapFile);
	  $this->writeLine($mapFile, 'END # ' . $this->title . ' layer');
	}
	
}
