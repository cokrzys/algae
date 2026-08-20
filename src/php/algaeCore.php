<?php

/**

  algae framework | Miscellaneous mostly static core utillities.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
*/

class algaeCore
{
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
  }
  
  /**
   * Get a string representing a boolean value.
   * @param boolean $status Boolean value to get a string representation of.
   * @return string The string "True" or "False".
   */
  public static function getTrueFalse($status)
  // --------------------------------------------------------------------------
  {
    return ($status) ? 'True' : 'False';
  }
  
  /**
   * Get a Yes/No string representing a boolean value.
   * @param boolean $status Boolean value to get a string representation of.
   * @return string The string "Yes" or "No".
   */
  public static function getYesNo($status)
  // --------------------------------------------------------------------------
  {
    return ($status) ? 'Yes' : 'No';
  }
  
  /**
   * Convert a string value to boolean.
   * @param string $str String representation of a boolean, case in-sensitive True/False, Yes/No or On/Off.
   * @return Boolean True or False.
   */
  public static function getBoolean($str)
  // --------------------------------------------------------------------------
  {
    if ( (strcasecmp($str, 'True') == 0) || (strcasecmp($str, 'Yes') == 0) || (strcasecmp($str, 'On') == 0) )
    {
      return True;
    }
    return False;
  }
  
  /**
   * Encode a string so it can be properly displayed on an HTML page.
   * @param string $str The string to encode.
   * @return string The encoded string.
   */
  public static function toHtml($str)
  // --------------------------------------------------------------------------
  {
    return htmlspecialchars($str);
  }
  
  /**
   * Get a number as a string formatted to the specified number of decimals,
   * accounts for the value not being set or default value.
   * @param string or numeric $value The value to format.
   * @param integer $decimals The number of decimals, default is 2.
   * @param float $nan Comparison numeric if the value has not been set, -99 by default.
   * @param string $thousandsSeparator The separator to use for thousands, a comma by default, use an empty string
   * for not thousands separator.
   * @param boolean $red_negatives True to color negatives red, default is False.
   * @return string Formatted string version of the number if it's a valid number, empty string otherwise.
   */
  public static function getFormattedNumber($value, $decimals = 2, $nan = -99, $thousandsSeparator = ',', $red_negatives = False)
  // --------------------------------------------------------------------------
  {
    $ret = '';
    if ( (isset($value)) && (strlen($value) > 0) )
    {
      $temp = str_replace(array(',', '$', '%'), '', $value);
      if (floatval($temp) !== $nan)
      {
        $ret = number_format(floatval($temp), $decimals, '.', $thousandsSeparator);
      }
    }
    if ($red_negatives) $ret = algaeCore::addRedNegativesSpan($value, $ret);
    return $ret;
  }
  
  /**
   * Add a <span> element with the 'loss' class to color a string red.
   * @param string or numeric $value Value to check if it's less than zero.
   * @param string $str String to color red if negative.
   * @return string String with <span> element added if negative or original string if not negative.
   */
  public static function addRedNegativesSpan($value, $str)
  // --------------------------------------------------------------------------
  {
    if ($value < 0)
    {
      return '<span class="loss">' . $str . '</span>';
    }
    return $str;
  }
  
  /**
   * Get a formatted percentage value.
   * @param string or numeric $value The value to format, 0-100.
   * @param integer $decimals The number of decimals, default is 2.
   * @param float $nan Comparison numeric if the value has not been set, 0 by default.
   * @param boolean $red_negatives True to color negatives red, default is False.
   * @return string Formatted dollar amount.
   */
  public static function getFormattedPercentage($value, $decimals = 2, $nan = 0, $red_negatives = False)
  // --------------------------------------------------------------------------
  {
    $str = algaeCore::getFormattedNumber($value, $decimals, $nan);
    if (strlen($str) > 0) $str = $str . '%';
    if ($red_negatives) $str = algaeCore::addRedNegativesSpan($value, $str);
    return $str;
  }
  
  /**
   * Get a dollar amount formatted with a leading dollar sign if there is a value.
   * @param string or numeric $value The value to format.
   * @param integer $decimals The number of decimals, default is 2.
   * @param float $nan Comparison numeric if the value has not been set, 0 by default.
   * @param boolean $red_negatives True to color negatives red, default is False.
   * @return string Formatted dollar amount.
   */
  public static function getFormattedDollarAmount($value, $decimals = 2, $nan = 0, $red_negatives = False)
  // --------------------------------------------------------------------------
  {
    $str = algaeCore::getFormattedNumber($value, $decimals, $nan);
    if (strlen($str) > 0) $str = '$' . $str;
    if ($red_negatives) $str = algaeCore::addRedNegativesSpan($value, $str);
    return $str;
  }
  
  /**
   * Get a formatted currency value.
   * @param string or numeric $value The value to format.
   * @param integer $decimals The number of decimals, default is 2.
   * @param float $nan Comparison numeric if the value has not been set, 0 by default.
   * @param boolean $red_negatives True to color negatives red, default is False.
   * @return string Formatted dollar amount.
   */
  public static function getFormattedCurrency($value, $decimals = 2, $nan = 0, $red_negatives = False, $currency_symbol = '$')
  // --------------------------------------------------------------------------
  {
    $str = algaeCore::getFormattedNumber($value, $decimals, $nan);
    if (strlen($str) > 0) $str = $currency_symbol . $str;
    if ($red_negatives) $str = algaeCore::addRedNegativesSpan($value, $str);
    return $str;
  }
  
  /**
   * Get a small block of color based on an html color code.  
   * The block is built from a span element.
   * @param string $html_color Color string, i.e. '#FF0000' is red.
   * @param boolean $include_label True to include the label, default is False.
   * @param string $label Label when $include_label is True.  If label is not specified the color string is the label.
   * @return string String with HTML for the block.
   */
  public static function getColorBlock($html_color, $include_label = False, $label = null)
  // --------------------------------------------------------------------------
  {
    $html = '<span style="background:' . $html_color . ';">&nbsp;&nbsp;&nbsp;</span>';
    if ($include_label)
    {
      $html .= '&nbsp;&nbsp;';
      $final_label = $html_color;
      if ($label != null) $final_label = $label;
      $html .= $final_label;
    }
    return $html;
  }
  
  /**
   * Get a simple percentage bar using an html div element.
   * @param float $percentage The percentage to represent in the bar, for example 75.6 = 75.6%.
   * @param integer $maxWidthPixels The maximum width of the bar in pixels, if the percentage is 100 the bar will be this width.
   * The default value is 100.
   * @param integer $numDecimals The number of decimals, default 1.
   * @param boolean $showPercentage True (default) to show the percentage at the end of the bar.
   * @return string The html to display the bar.
   */
  public static function getPercentageBar($percentage, $maxWidthPixels = 100, $numDecimals = 1, $showPercentage = True)
  // --------------------------------------------------------------------------
  {
    $html = '';
    if (floatval($percentage) > 0)
    {
      $width = intval(round($percentage / 100.0 * $maxWidthPixels));
      if ($width > $maxWidthPixels) $width = $maxWidthPixels;
      $html .= '<div class="bar" style="width:' . $width . 'px"></div>';
      if ($showPercentage)
      {
        $html .= '&nbsp;<span class="bar_text">' . algaeCore::getFormattedNumber($percentage, $numDecimals, -99, '') . '%</span>';
      }
    }
    return $html;
  }
  
  /**
   * Check if a string starts with a specific substring.
   * From: https://www.geeksforgeeks.org/php-startswith-and-endswith-functions/
   * @param string $haystack The string to check if it starts with a specific substring.
   * @param string $needle The substring on the front to check for.
   * @return boolean True if the string starts with the substring.
   */
  public static function startsWith($haystack, $needle)
  // --------------------------------------------------------------------------
  {
    $len = strlen($needle);
    return (substr($haystack, 0, $len) === $needle);
  } 
 
  /**
   * Check if a string ends with a specific substring.  
   * From: http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
   * @param string $haystack The string to check if it ends with a specific substring.
   * @param string $needle The substring on the end to check for.
   * @return boolean True if the string ends with the substring or if the substring is empty.
   */
  public static function endsWith($haystack, $needle)
  // --------------------------------------------------------------------------
  {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
  }
  
  /**
   * Add a JavaScript code block.
   * @param string $code The JavaScript code to add.
   */
  public static function addJavaScript($code)
  // --------------------------------------------------------------------------
  {
    echo '<script type="text/javascript">', $code, '</script>';
  }
  
  /**
   * Send a string value to JavaScript.
   * @param string $varName The name of the variable.
   * @param string $str The value.
   */
  public static function addJavaScriptStringVar($varName, $str)
  // --------------------------------------------------------------------------
  {
    echo $varName, '="', $str, '";';
  }
  
  /**
   * Send an integer value to JavaScript.
   * @param string $varName The name of the variable.
   * @param integer $val The value.
   */
  public static function addJavaScriptIntegerVar($varName, $val)
  // --------------------------------------------------------------------------
  {
    echo $varName, '=', $val, ';';
  }
  
  /**
   * Send a numeric value to JavaScript.
   * @param string $varName The name of the variable.
   * @param float $val The value.
   */
  public static function addJavaScriptNumericVar($varName, $val)
  // --------------------------------------------------------------------------
  {
    echo $varName, '=', $val, ';';
  }
  
  /**
   * Send a boolean value to JavaScript.
   * @param string $varName The name of the variable.
   * @param integer $val The value.
   */
  public static function addJavaScriptBooleanVar($varName, $val)
  // --------------------------------------------------------------------------
  {
    echo $varName, '=', strtolower(algaeCore::getTrueFalse($val)), ';';
  }
  
  /**
   * Convert all the URLs in a string into tagged href elements.
   * This solution is from:
   * http://stackoverflow.com/questions/12538358/convert-url-to-links-from-string-except-if-they-are-in-a-attribute-of-a-html-tag
   * @param string $content Text string with URLs to convert to href elements.
   * @return string Converted string with URLS changed to href elements.
   */
  public static function convertURLsToHrefs($content)
  // --------------------------------------------------------------------------
  {
    $content =
    preg_replace(
      '~(\s|^)(https?://.+?)(\s|$)~im',
      '$1<a href="$2" target="_blank">$2</a>$3',
      $content
      );
    $content =
    preg_replace(
      '~(\s|^)(www\..+?)(\s|$)~im',
      '$1<a href="http://$2" target="_blank">$2</a>$3',
      $content
      );
    $content = nl2br($content);
    return $content;
  }
  
  /**
   * Get the base URL for the webpage.
   * @return string The base url.
   */
  public static function getBaseURL()
  // --------------------------------------------------------------------------
  {
    $pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") 
    {
      $pageURL .= "s";
    }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") 
    {
      $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"];
    } 
    else 
    {
      $pageURL .= $_SERVER["SERVER_NAME"];
    }
    return $pageURL;
  }
  
  /**
   * Add Javascript code to setup tabs.
   * @param string $id
   * @param string $minHeight
   */
  public static function setupJavaScriptForTabs($id = '#tabs', $minHeight = '400px')
  // --------------------------------------------------------------------------
  {
    $js = "$(function() {
      	  $('$id').tabs().css({
      	  'min-height': '$minHeight',
      	  'overflow': 'auto'
        	});
        	});";
    algaeCore::addJavaScript($js);
  }
  
  /**
   * Simple linear interpolation between two points.
   * @param float $val Value in original units to interpolate into new units.
   * @param float $minOrig Minimum scale value in original units.
   * @param float $maxOrig Maximum scale value in original units.
   * @param float $minNew Minimum scale value in new units corresponding to $minOrig.
   * @param float $maxNew Maximum scale value in new units corresponding to $maxOrig.
   * @return float value corresponding to $val but scaled against the new scale.
   */
  public static function interpolate($val, $minOrig, $maxOrig, $minNew, $maxNew)
  // --------------------------------------------------------------------------
  {
    $ret = 0.0;
    $dataRange = 0.0;
    $sizeRange = 0.0;
    if ( ($minNew == $maxNew) || ($val <= $minOrig) )
    {
      $ret = $minNew;
    }
    else
    {
      if ($val >= $maxOrig)
      {
        $ret = $maxNew;
      }
      else
      {
        $dataRange = $maxOrig - $minOrig;
        $sizeRange = $maxNew - $minNew;
        if ($dataRange != 0.0)
        {
          $ret = $minNew + (($sizeRange / $dataRange) * ($val - $minOrig));
        }
        else
        {
          $ret = $minNew;
        }
      }
    }
    return $ret;
  }
  
  /**
   * Build an attributes list for HTML elements.
   * @param array $attributes A keyed array to add different attributes to the checkbox.
   * For example: array('id' => 'CheckboxID', 'class' => 'class1 class2')
   * @return string String of elements in a single string.
   */
  public static function getAttributesString($attributes)
  // --------------------------------------------------------------------------
  {
    if (is_array($attributes)) {
      $t = '';
      foreach ($attributes as $key => $value) {
        $t .= " $key='" . algaeCore::toHtml($value) . "'";
      }
      return $t;
    }
  }
  
  /**
   * Convert links in a string into clickable links.
   * From: https://stackoverflow.com/questions/1960461/convert-plain-text-urls-into-html-hyperlinks-in-php
   * @param string $str String with links.
   * @return mixed String with clickable links.
   */
  public static function getStringWithLinks($str)
  // --------------------------------------------------------------------------
  {
    $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
    return nl2br(preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $str));
  }
  
  /**
   * Get the plural of a word.
   * From: https://stackoverflow.com/questions/12786819/singular-or-plural-setting-to-a-variable-php
   * @param integer $num Number of items.
   * @param string $singular Singular form of word
   * @param string $plural Plural form of word; function will attempt to deduce plural form from singular if not provided
   * @return string Pluralized word if quantity is not one, otherwise singular.
   */
  public static function getSingularOrPlural($num, $singular, $plural = null) 
  // --------------------------------------------------------------------------
  {
    if ($num == 1)
    {
      return $singular;
    }
    if ($plural !== null) return $plural;
    $last_letter = strtolower($singular[strlen($singular)-1]);
    switch($last_letter) 
    {
      case 'y': return substr($singular,0,-1) . 'ies';
      case 's': return $singular . 'es';
      default: return $singular . 's';
    }
  }
  
  /**
   * Highlight case insensitive search term in a string.
   * @param string $str Input string.
   * @param string $searchstr String to search for.
   * @return string Input string with search string delimited by <span> elements to highlight the search term.
   */
  public static function addHighlights($str, $searchstr, &$num_occurrences)
  // --------------------------------------------------------------------------
  {
    $strRet = '';
    if (strlen($str) > 0)
    {
      if (stripos($str, $searchstr) >= 0)
      {
        $offset = 0;
        while (($pos = stripos($str, $searchstr, $offset)) !== FALSE)
        {
          $strRet .= substr($str, $offset, $pos - $offset);
          $strRet .= '<span class="search_highlight">';
          $strRet .= substr($str, $pos, strlen($searchstr));
          $strRet .= '</span>';
          $offset = $pos + strlen($searchstr);
          $num_occurrences += 1;
        }
        if ($offset < strlen($str))
        {
          $strRet .= substr($str, $offset);
        }
      }
      else
      {
        $strRet = $str;
      }
      //
      // ----- simpler but does not preserve case of input string
      //
      // $strRet = str_ireplace($searchstr, "<span class = 'search_highlight'>$searchstr</span>", $str);
    }
    return $strRet;
  }
  
  /**
   * Split a rowid into manageable parts for use in a hierarchical directory structure or filename.
   * The rowid is left padded with zeros before splitting into parts.
   * For example the rowid 2789 split into two levels is 002 789.
   * @param integer $rowid Rowid to split, integer and not zero padded.
   * @param integer $num_levels Number of three digit levels to split across.
   * @return array Array of split parts.
   */
  public static function getPartsFromRowid($rowid, $num_levels=2)
  // --------------------------------------------------------------------------
  {
    $level_len = 3;
    $rs = str_pad($rowid, $level_len * $num_levels, '0', STR_PAD_LEFT);
    return str_split($rs, $level_len);
  }
  
  /**
   * Split and concatenate a rowid into a string for use in a hierarchical directory structure or filename.
   * For example the rowid 2789 across two levels would be '002/789'.
   * @param integer $rowid Rowid to split, integer and not zero padded.
   * @param integer $num_levels Number of three digit levels to split across.
   * @param string $separator Separator to add between parts, backspace by default.
   * @return string|NULL String typically for use in a directory structure or filename.
   */
  public static function getConcatenatedPartsFromRowid($rowid, $num_levels=2, $separator='/')
  // --------------------------------------------------------------------------
  {
    $parts = algaeCore::getPartsFromRowid($rowid, $num_levels);
    if (count($parts) >= 1)
    {
      $ret = '';
      $sep = '';
      foreach ($parts as $part)
      {
        $ret .= $sep . $part;
        $sep = $separator;
      }
      return $ret;
    }
    return null;
  }
  
  /**
   * Get a path for use in a hierarchical directory structure from a rowid.
   * For example the rowid 2789 across two levels would be '002/789'.
   * @param integer $rowid Rowid to split, integer and not zero padded.
   * @param integer $num_levels Number of three digit levels to split across.
   * @return string|NULL String for use in a directory structure.
   */
  public static function getPathFromRowid($rowid, $num_levels=2)
  // --------------------------------------------------------------------------
  {
    return algaeCore::getConcatenatedPartsFromRowid($rowid, $num_levels, '/');
  }
  
}


