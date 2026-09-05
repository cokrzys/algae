<?php

/**

  algae framework | Forms support class.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
*/

class algaeForm
{
  
  const REQUIRED = array('required' => 'required');
  const AUTOCOMPLETE_OFF = array('autocomplete' => 'off');
  const REQUIRED_AND_AUTOCOMPLETE_OFF = array('requried' => 'required', 'autocomplete' => 'off');
  const PASSWORD = 'password';
  const TEXT = 'text';
  const SELECT_DEFAULT_WIDTH = 200;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
  }
  
  /**
   * Add hidden tokens to a form to help prevent CSRF attacks.
   * @param string $prefix A unique prefix for the token name, for example the name of the form.
   */
  public static function addFormTokens($prefix)
  // --------------------------------------------------------------------------
  {
    if (!session_id())
    {
      session_start();
      session_regenerate_id(true);
    }
    $token = md5(uniqid(rand(), true));
    $_SESSION[$prefix . '_token'] = $token;
    $current_time = time();
    $_SESSION[$prefix . '_token_time'] = $current_time;
    echo '<input type="hidden" name="', $prefix, '_token" value="', $token, '" />';
  }
  
  /**
   * Check if the form tokens are valid, used in conjunction with algaeForm::addFormTokens() to help prevent CSRF attacks.
   * @param string $prefix A unique prefix for the token name, for example the name of the form.
   * @return True if the tokens pass the checks, False if suspected CSRF attack.
   */
  public static function validTokens($prefix)
  // --------------------------------------------------------------------------
  {
    global $app;
    if (!session_id())
    {
      session_start();
      session_regenerate_id(true);
    }
    //
    // ----- token time check is in seconds, i.e. 900 = 15 minutes, 3600 = 1 hr
    //
    if (isset($_SESSION[$prefix . '_token']) && isset($_SESSION[$prefix . '_token_time']))
    {
      if ( ($_SESSION[$prefix . '_token'] == $_POST[$prefix . '_token']) &&
        (time() - $_SESSION[$prefix . '_token_time'] < 3600) )
      {
        return true;
      }
    }
    $app->errorMessage('Invalid tokens or session timed-out.');
    return false;
  }
  
  /**
   * Start an HTML form with the given token.
   * @param string $formToken Security token to associate with the form.
   */
  public function startForm($formToken)
  // --------------------------------------------------------------------------
  {
    echo '<form method="post">';
    algaeForm::addFormTokens($formToken);
    $this->form_token = $formToken;
  }
  
  public function submitButton($label = 'Submit', $leading_spaces = True, $name = 'submit')
  // --------------------------------------------------------------------------
  {
    if ($leading_spaces) echo '&nbsp;&nbsp;&nbsp;';
    echo '<input class="ui-button ui-widget ui-corner-all" type="submit" name="', $name, '" value="', $label, '">';
  }
  
  /**
   * End a form.
   * @param string $label The label for the submit button.
   */
  public function endForm($label = 'Submit', $leading_spaces = True)
  // --------------------------------------------------------------------------
  {
    $this->submitButton($label, $leading_spaces);
    echo '</form>';
  }
  
  /**
   * Start an options form.
   * @param string $formToken A unique prefix for the token name, for example the name of the form.
   * @param string $initialDisplayState Set the initial display state, can be 'inline-block' to show, or 'none' (the default) to hide.
   * @param string $afterOptionsHTML Additional HTML to display after the Options button.
   */
  public function startOptionsForm($formToken, $initialDisplayState = 'none', $afterOptionsHTML = '')
  // --------------------------------------------------------------------------
  {
    echo algaeForm::button('optionsButton', 'Options');
    echo '&nbsp;<input class="ui-button ui-widget ui-corner-all" type="submit" name="submit" id="pageRefreshButton" value="Refresh">';
    echo $afterOptionsHTML;
    echo '<div id="optionsFormDiv" style="display:', $initialDisplayState, ';">';
    echo '<form id="optionsForm" method="post">';
    algaeForm::addFormTokens($formToken);
    $this->form_token = $formToken;
  }
  
  /**
   * End an options form.
   */
  public function endOptionsForm($withCodeMirror = False)
  // --------------------------------------------------------------------------
  {
    echo '<p /><input class="ui-button ui-widget ui-corner-all" type="submit" id="pageRefreshButtonInOptionsForm" name="submit" value="Refresh"><p />';
    echo '</form>';
    echo '</div>';
    echo '<script>';
    echo "$('#pageRefreshButton').on('click', function(e) { var f = $('#pageRefreshButtonInOptionsForm'); if (f.length > 0) { f.click(); } });";
    // TODO: Total kludge, need a better option than this boolean !!  See also algaeFramework.js
    if ($withCodeMirror)
    {
      echo "$('#optionsButton').on('click', function() { $('#optionsFormDiv').toggle(0); window.scrollTo(0, 0); algaefw.codeMirror.refresh(); return false; });";
    }
    else 
    {
      echo "$('#optionsButton').on('click', function() { $('#optionsFormDiv').toggle(0); window.scrollTo(0, 0); return false; });";
    }
    echo '</script>';
  }
  
  /**
   * Clean an input string getting it ready to be used in a SQL statement.
   * @param string $str The input string to clean.
   * @return string The cleaned up string.
   */
  public static function cleanInput($str)
  // --------------------------------------------------------------------------
  {
    // return trim(pg_escape_string(utf8_encode($str)));
    return trim(pg_escape_string($str));
  }
  
  /**
   */
  public static function inputText($id, $default, $size = 10, $attributes = array(), $type=algaeForm::TEXT)
  // --------------------------------------------------------------------------
  {
    $escaped_default = str_replace('"', '&quot;', $default);  // TODO: Make more generic.
    $html = '<input';
    $html .= ' type="' . $type . '"';
    $html .= ' name="' . $id . '"';
    $html .= ' id="' . $id . '"';
    $html .= ' value="' . $escaped_default . '"';
    $html .= ' size="' . $size . '"';
    if (count($attributes) > 0)
    {
      $html .= algaeCore::getAttributesString($attributes);
    }
    $html .= ' />';
    return $html;
  }
  
  /**
   * Create an HTML text input field that allows you to select from a list of items or enter a new value.
   * @param string $id Id for the control.
   * @param string $default Default value.
   * @param string $datalist_sql SQL to get a list of values.  SQL must return one text field.
   * @param number $size Width of control.
   * @param array $attributes Additional attributes.
   * @param string $type Control type, algaeForm::TEXT by default.
   * @return string The completed HTML control as a text string.
   */
  public static function inputTextWithSelection($id, $default, $datalist_sql, $size = 10, $attributes = array(), $type=algaeForm::TEXT)
  // --------------------------------------------------------------------------
  {
    //
    // ----- basic text prompt with list attribute added to reference a datalist of possible values
    //
    $html = '<input';
    $html .= ' type="' . $type . '"';
    $html .= ' name="' . $id . '"';
    $html .= ' id="' . $id . '"';
    $html .= ' value="' . $default . '"';
    $html .= ' size="' . $size . '"';
    $html .= ' list="' . $id . '_datalist"';
    if (count($attributes) > 0)
    {
      $html .= algaeCore::getAttributesString($attributes);
    }
    $html .= ' />';
    //
    // ----- get items for the datalist
    //
    $data = algaeDB::getArrayOfStrings($datalist_sql);
    //
    // ----- make the datalist to go with the select
    //
    if (count($data) > 0)
    {
      $html .= '<datalist id="' . $id . '_datalist">';
      foreach ($data as $str)
      {
        $html .= '<option value="' . algaeCore::toHtml($str) . '">';
      }
      $html .= '</datalist>';
    }
    return $html;
  }
  
  /**
   * Input a color using jscolor.
   * See: https://jscolor.com
   * @param string $id Control id.
   * @param string $default Hex color with leading #.
   * @param array $attributes Optional attributes.
   * @return string
   */
  public static function inputColor($id, $default, $attributes = array())
  // --------------------------------------------------------------------------
  {
    $html = '<input data-jscolor="{}"';
    $html .= ' name="' . $id . '"';
    $html .= ' id="' . $id . '"';
    $html .= ' value="' . $default . '"';
    if (count($attributes) > 0)
    {
      $html .= algaeCore::getAttributesString($attributes);
    }
    $html .= ' />';
    return $html;
  }
  
  /**
   * Input a date value.
   * @param string $id The id and name for the control.
   * @param string $default The default value, in the format 17-Jul-2020.
   * @param number $size Size of input field.
   * @param array $attributes Optionl attributes.
   * @return string The HTML input element with Javascript to make it into a date control.
   */
  public static function inputDate($id, $default, $size = 15, $attributes = array())
  // --------------------------------------------------------------------------
  {
    $html = algaeForm::inputText($id, $default, $size, $attributes);
    $html .= '<script type="text/javascript">$(function() { $("#' . $id . '").datepicker({ dateFormat: "dd-M-yy" }); });</script>';
    return $html;
  }
  
  /**
   * Display a button.
   * @param string $id The id for the control.
   * @param string $label The label on the button.
   * @param string $onclick Optional handler when clicked, for example 'graph.updateTargetPrice();'.
   * @return string The HTML button element.
   */
  public static function button($id, $label, $onclick=null)
  // --------------------------------------------------------------------------
  {
    $html = '<input class="ui-button ui-widget ui-corner-all" type="button"';
    $html .= ' id="' . $id . '"';
    $html .= ' value="' . $label . '"';
    if ($onclick != null)
    {
      $html .= ' onclick="' . $onclick . '"';
    }
    $html .= ' />';
    return $html;    
  }
  
  public static function getClickableImage($src=null, $width=null, $height=null, 
    $addTableJustifiers=True, $onClick=null)
  // --------------------------------------------------------------------------
  {
    $html = '';
    if ($src == null) { $src = '/algae/img/algae_details_icon_32px.png'; }
    if ($width == null) { $width = '15px'; }
    if ($height == null) { $height = '15px'; }
    if ($addTableJustifiers) { $html .= '&nbsp;<span class="row_container"><span class="vertical-center">'; }
    $html .= '<img src="' . $src . '"';
    $html .= ' height="' . $height . '"';
    $html .= ' width="' . $width . '"';
    if ($onClick != null)
    {
      $html .= ' onclick="' . $onClick . '"';
    }
    $html .= '>';
    if ($addTableJustifiers) { $html .= '</span></span>'; }
    return $html;
  }
  
  /**
   * Check to see if a required value exists and display a red error message if it doesn't.
   * @param string $value The value to check.
   * @param string $name The name of the value for use in the error message if needed.
   * @param integer $num_errors The total number of errors, will be incremented by 1 if the value doesn't exist.
   * @param boolean $show_errors True (default) to show errors.
   * @return boolean True if the value exists, false otherwise.
   */
  public static function checkRequiredValue($value, $name, &$num_errors, $show_errors = True)
  // --------------------------------------------------------------------------
  {
    global $app;
    if ( (! isset($value)) || 
         ( (gettype($value) == 'string') && (strlen($value) == 0) )
       )
    {
      if ($show_errors) $app->errorMessage('Required ' . $name . ' is missing.');
      $num_errors++;
      return false;
    }
    return true;
  }
  
  /**
   * 
   */
  public static function selectWithSQL($sql, $id, $default, $required = False, $attributes = array(), $width = null)
  // --------------------------------------------------------------------------
  {
    $html = '';
    $db = algaeDB::connect();
    if ($db)
    {
      $result = pg_query($db, $sql);
      if (! $result)
      {
        algaeDB::errorWithSQL($sql);
      }
      //
      // ----- create the select control and add choices
      //
      $html = '<select name="' . $id . '" id="' . $id . '">';
      if ($required)
      {
        $html .= ' required="required"';
      }
      $html .= '>';
      //
      // ----- add a blank value at the top if no default is set
      //       &nbsp; added to support blank choice using chosen()
      //
      if ( (strlen($default) == 0) || (! $required) )
      {
        $html .= '<option value="">&nbsp;</option>';
      }
      //
      // ----- add choices from the database
      //
      while ($row = pg_fetch_array($result))
      {
        $str = algaeDB::cleanDataRead($row[0]);
        if (isset($row[1]))
        {
          $val = algaeDB::cleanDataRead($row[1]);
        }
        else
        {
          $val = $str;
        }
        $html .= '<option'; 
        if (isset($default) && ($str == $default))
        {
          $html .= ' selected';
        }
        $html .= ' value="' . algaeCore::toHtml($val) . '">' . algaeCore::toHtml($str) . '</option>';
      }
      $html .= '</select>';
      $width_str = strval(algaeForm::SELECT_DEFAULT_WIDTH);
      if ($width != null) $width_str = strval($width);
      algaeCore::addJavaScript('$( function() { $( "#'  . $id . '" ).chosen({width:' . $width_str . ', search_contains:true, inherit_select_classes:true}); } );');  // selectmenu
      algaeDB::close($db, $result);
    }
    return $html;
  }

  public static function selectWithTableAndField($table, $field, $id, $default, $required = False, 
    $attributes = array(), $width = null, $activeOnly = False)
  // --------------------------------------------------------------------------
  {
    $sql = "SELECT DISTINCT t.{$field} FROM {$table} t";
    if ($activeOnly)
    {
      $sql .= " WHERE t.record_status_rowid_fk = (SELECT rowid FROM ref.record_status WHERE name = 'Active')";
    }
    $sql .= " ORDER BY t.{$field}";
    return algaeForm::selectWithSQL($sql, $id, $default, $required, $attributes, $width);
  }
  
  /**
   * Build a combobox from an array for use in a form.
   * @param string $name The id and name for the control.
   * @param string $value The default, currently set value.
   * @param array $array An array of choices for the combobox.
   * @param array $attributes An array of attributes, for example: array('required' => 'required').
   * @return string The HTML to render the control.
   */
  public static function selectWithArray($id, $default, $array, $attributes = array(), $width = null)
  // --------------------------------------------------------------------------
  {
    $combobox = '<select ';
    $combobox .= 'name="' . $id . '" ';
    $combobox .= 'id="' . $id . '" ';
    $combobox .= algaeCore::getAttributesString($attributes);
    $combobox .= '>';
    if ( (strlen($default) == 0) || (! array_key_exists('required', $attributes)) )
    {
      $combobox .= '<option value=""></option>';
    }
    foreach ($array AS $item)
    {
      $combobox .= '<option ';
      if ( (is_array($item)) && (count($item) == 2) )
      {
        if ( (isset($default)) && (($item[0] == $default) || ($item[1] == $default)) )
        {
          $combobox .= 'selected ';
        }
        $combobox .= 'value="' . algaeCore::toHtml($item[0]) . '">' . algaeCore::toHtml($item[1]) . '</option>';
      }
      else
      {
        if ((isset($default)) && ($item == $default))
        {
          $combobox .= 'selected ';
        }
        $combobox .= 'value="' . algaeCore::toHtml($item) . '">' . algaeCore::toHtml($item) . '</option>';
      }
    }
    $combobox .= '</select>';
    $width_str = strval(algaeForm::SELECT_DEFAULT_WIDTH);
    if ($width != null) $width_str = strval($width);
    algaeCore::addJavaScript('$( function() { $( "#'  . $id . '" ).selectmenu({width:' . $width_str . '}); } );');
    return $combobox;
  }
  
  /**
   * Start a jQuery tabs section.
   * @param array $arrayTabs An array of tabs, for example: array(
   array('#overview', 'Overview'),
   array('#statistics', 'Statistics'))
   * @param string $id Id for the tabs div, 'tabs' by default.
   * @param boolean $toHtml True (default) to convert the tab label to html.
   */
  public static function startTabs($arrayTabs, $id = 'tabs', $toHtml = True)
  // --------------------------------------------------------------------------
  {
    if (count($arrayTabs) > 0)
    {
      echo '<div id="', $id, '" class="tabs">';
      echo '<ul>';
      foreach ($arrayTabs AS $tab)
      {
        if (count($tab) == 2)
        {
          echo '<li><a href="', $tab[0], '">';
          if ($toHtml)
          {
            echo algaeCore::toHtml($tab[1]);
          }
          else
          {
            echo $tab[1];
          }
          echo '</a></li>';
        }
      }
      echo '</ul>';
    }
  }
  
  /**
   * End a jQuery tabs section.  Includes the JavaScript that initializes the tabs.
   * @param string $id Id for the tabs div, 'tabs' by default.
   * @param string $minHeight Minimum height for each tab.
   */
  public static function endTabs($id = 'tabs', $minHeight = '600px')
  // --------------------------------------------------------------------------
  {
    echo '</div>';
    $js = '$(function() { $( "#' . $id . '" ).tabs()';
    $js .= '.css({"min-height": "' . $minHeight . '", "overflow": "auto", "height": "' . $minHeight . '"});';
    // $js .= '.css({"min-height": "' . $minHeight . '", "overflow":"auto"});';
    $js .= '});';
    algaeCore::addJavaScript($js);
  }
  
  /**
   * Start a jQuery tabs section for a single tab.
   * @param string $label Label for the tab.
   * @param string $id Id for the tabs div, 'tabs' by default.
   */
  public static function startSingleTab($label, $id = 'tabs')
  // --------------------------------------------------------------------------
  {
    algaeForm::startTabs(array(array('#single_tab', $label)), $id);
    echo '<div id="single_tab">';
  }
  
  /**
   * End a jQuery tabs section for a single tab including the tab </div> and the JavaScript that initializes the tabs.
   * @param string $id Id for the tabs div, 'tabs' by default.
   * @param string $minHeight Minimum height for each tab.
   */
  public static function endSingleTab($id = 'tabs', $minHeight = '600px')
  // --------------------------------------------------------------------------
  {
    echo '</div>';
    algaeForm::endTabs($id, $minHeight);
  }
  
  /**
   * Get a default forms token based on the class name.  Uses reflection to
   * get the class name.  This is meant to be repeatable from showing to
   * processing a form.
   * @example algaeForm::getDefaultToken($this);
   * @example $f->startOptionsForm(algaeForm::getDefaultToken($this));
   * @param object $class An instance of a class.
   * @return string The token.
   */
  public static function getDefaultToken($class)
  // --------------------------------------------------------------------------
  {
    return get_class($class) . 'ft';
  }
  
  /**
   * Get a default value from a number of places in order of preference 1) from current user input via a post, 2)
   * from a previous parameter stored in the database, or 3) as a default value passed in.
   * @param string $id The id of the control to check for a posted input.
   * @param string $default The default value if nothing else works.
   * @param string $parameterName The database parameter name of a previously stored value.
   * @return string The default as a string.
   */
  public static function getDefaultValue($id, $default, $parameterName = '')
  // --------------------------------------------------------------------------
  {
    $ret = '';
    if (isset($_POST[$id]))
    {
      //
      // ----- get value if posted
      //
      if (strlen($parameterName) > 0)
      {
        $ret = algaeDB::cleanInput($_POST[$id]);
        algaeTblCoreUserParameter::saveParameter($parameterName, $ret);
      }
      //
      // ----- return the 'un-database-escaped' version for re-use in a form, etc.
      //       for example this doesn't duplicate the single quotes
      //
      $ret = $_POST[$id];
    }
    else
    {
      if (isset($_GET[$id]))
      {
        //
        // ----- get value if specified on the url
        //
        if (strlen($parameterName) > 0)
        {
          $ret = algaeDB::cleanInput($_GET[$id]);
          algaeTblCoreUserParameter::saveParameter($parameterName, $ret);
        }
        //
        // ----- return the 'un-database-escaped' version for re-use in a form, etc.
        //       for example this doesn't duplicate the single quotes
        //
        $ret = $_GET[$id];
      }
      else
      {
        //
        // ----- try to read a user default saved in the database
        //
        if (strlen($parameterName) > 0)
        {
          $ret = algaeTblCoreUserParameter::getParameter($parameterName);
          if (strlen($ret) == 0) $ret = $default;
        }
      }
    }
    return $ret;
  }
  
  /**
   * Helper function to get SQL for an input/select control.
   * @param string $table Table name including schema, i.e. sdb.analyst_action.
   * @param string $field Field (table column) name.
   * @return string SQL to select values.
   */
  public static function getDistinctSQL($table, $field)
  // --------------------------------------------------------------------------
  {
    return 'SELECT DISTINCT ' . $field . ' FROM ' . $table;
  }
  
  /**
   * Build a checkbox for use in a form
   *
   * @param string $name The name of this element
   * @param string $value Value to be passed back when the form is submitted.
   * @param array $attributes
   *   A keyed array to add different attributes to the checkbox.
   *   <code>
   *    array(
   *      'id' => 'CheckboxID',
   *      'class' => 'class1 class2'
   *    )
   *   </code>
   *
   * @param boolean $selected
   *   Should this box be checked when it is first diplayed?
   *
   * @return string
   *   HTML Checkbox form element
   *
   */
  public static function checkbox($name, $value, $attributes = array(), $selected = FALSE)
  // --------------------------------------------------------------------------
  {
    $checkbox = '<label class="container">';
    $checkbox .= '<input ';
    $checkbox .= 'type="checkbox" ';
    $checkbox .= 'id="' . $name . '" ';
    $checkbox .= 'name="' . $name . '" ';
    $checkbox .= 'value="' . $value . '" ';
    $checkbox .= algaeCore::getAttributesString($attributes);
    $checkbox .= $selected ? ' checked="checked"' : '';
    $checkbox .= ' />';
    $checkbox .= '<span class="checkmark"></span>';
    $checkbox .= '</label>';
    return $checkbox;
  }
  
  /**
   * Process a value returned from a checkbox on a form.
   * @param string $name The name of the checkbox control.
   * @param boolean $default The default if the checkbox is not posted.
   * @param boolean $is_post True if the value is posted.
   * @param string $parameterName The name of the parameter to look for and save to in core.user_parameter. This is optional,
   * if not specified it will be ignored.
   * @return boolean True if the item was checked, False otherwise, the default if not posted.
   */
  public static function processCheckbox($name, $default, $is_post, $parameterName = '')
  // --------------------------------------------------------------------------
  {
    $ret = $default;
    if ($is_post)
    {
      if (isset($_POST[$name]))
      {
        $ret = True;
      }
      else
      {
        $ret = False;
      }
      if (strlen($parameterName) > 0)
      {
        algaeTblCoreUserParameter::saveParameter($parameterName, algaeCore::getTrueFalse($ret));
      }
    }
    else
    {
      if (strlen($parameterName) > 0)
      {
        $str = algaeTblCoreUserParameter::getParameter($parameterName);
        if (strlen($str) > 0)
        {
          $ret = algaeCore::getBoolean($str);
        }
      }
    }
    return $ret;
  }
  
}


