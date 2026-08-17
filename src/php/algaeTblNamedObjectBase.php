<?php

/**

  algae framework | Base class for named object tables.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeTblNamedObjectBase extends algaeTblBase
{
  
  public $name;
  public $description;
  public $record_status;
  public $html_color;
  
  /**
   * Constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    parent::__construct();
    $this->init();
  }
  
  /**
   * Initial default values.
   */
  public function init()
  // --------------------------------------------------------------------------
  {
    parent::init();
    $this->table_name = 'algae.named_object_base';
    $this->name = null;
    $this->html_color = '#000000';
    $this->description = null;
    $this->record_status = new algaeTblRecordStatus();
  }
  
  /**
   * TODO: Remove, handled in base class.
   * Get a link to the homepage for a record.
   * @param string $label Label for the link, will be the name if not specified.
   * @param integer $role Role constant, algaeAccess::ROLE_READ if not defined.
   * @param boolean $new_page True to open in a new tab, default is False.
   * @return string The link.
   */
  public function getHomepageLinkObsolete($label = null, $role = algaeAccess::ROLE_READ, $new_page = False, $title = null)
  // --------------------------------------------------------------------------
  {
    if ($this->homepage != null)
    {
      global $app;
      if ($label == null) $label = $this->name;
      return $app->getPageLink($app->getURLBase() . $this->homepage . '?rowid=' . $this->rowid, $label, $role, $app->settings->appName, '', $new_page, $title);
    }
    return '';
  }
  
  protected function showOverviewTab($form)
  // --------------------------------------------------------------------------
  {
    echo '<div id="overview_tab">';
    if ($this->rowid > 0)
    {
      echo '<input type="hidden" name="rowid" value="', $this->rowid, '" />';
    }
    //
    // ----- table to keep items aligned
    //
    algaeTable::start('formTable', 'algae_form_table', '');
    algaeTable::writeHeader(array(), False);
    //
    // ----- name
    //
    algaeTable::writeTwoColumns('Name', algaeForm::inputText($this->get_control_id('name'), 
      $this->name, 50, algaeForm::REQUIRED), False);
    //
    // ----- fields from a derived class
    //
    $this->addDerivedFieldsToForm();
    //
    // ----- description
    //
    algaeTable::writeTwoColumns('Description', '', False);
    echo '<tr><td colspan="2">';
    echo '<textarea name="' . $this->get_control_id('description') . '" cols="83" rows="7">', 
      algaeCore::toHtml($this->description), '</textarea><p />';
    echo '</td></tr>';
    //
    // ----- record status
    //
    algaeTable::writeTwoColumns('Status', algaeForm::selectWithTableAndField('ref.record_status', 'name', 
      $this->get_control_id('record_status_rowid_fk'), $this->record_status->name), False);
    algaeTable::end();
    $form->submitButton('Save', False);
    echo '</div>';
  }
  
  /**
   * Show form to edit a record.
   */
  public function showForm()
  // --------------------------------------------------------------------------
  {
    $f = new algaeForm();
    $f->startForm(algaeForm::getDefaultToken($this));
    //
    // ----- get data if editing
    //
    if (isset($_REQUEST['rowid']))
    {
      $this->read_row_from_database_with_rowid($_REQUEST['rowid']);
    }
    algaeForm::startTabs(array(
      array('#overview_tab', 'Overview'),
      array('#existing_tab', 'Existing ' . algaeCore::getSingularOrPlural(2, $this->itemName, $this->itemNamePlural))
    ));
    //
    // ----- overview_tab
    //
    $this->showOverviewTab($f);
    //
    // ----- existing_tab
    //
    echo '<div id="existing_tab">';
    $this->editpage = null;  // disables the "Add a ___" at the top of the table
    $this->reportExistingRecords();
    echo '</div>';
    //
    // ----- end tabs
    //
    algaeForm::endTabs('tabs');
    echo '</form>';
    echo '<p />';
    echo '<p /><br />';
  }
  
  /**
   * Report the overall details for the field.
   */
  protected function reportOverallDetails()
  // --------------------------------------------------------------------------
  {
    echo $this->getActionLinks(), '<p />';
    algaeTable::start($this->itemName . 'DetailsTable', 'algae_table', 'width:85%');
    algaeTable::writeHeader(array(), False);
    algaeTable::writeTwoColumns('Name', '<b>' . $this->name . '</b>', False);
    $this->addDerivedFieldsToOverallDetails();
    algaeTable::writeTwoColumns('Description', algaeCore::getStringWithLinks($this->description), False);
    algaeTable::writeTwoColumns('Status', algaeCore::getColorBlock($this->record_status->html_color, True, $this->record_status->name), False);
    algaeTable::writeTwoColumns('Added', $this->timestamp_loaded_utc);
    algaeTable::writeTwoColumns('Modified', $this->timestamp_modified_utc);
    algaeTable::writeTwoColumns('Rowid', $this->rowid);
    algaeTable::end();
  }
  
  /**
   * Report details for the record.
   * {@inheritDoc}
   * @see algaeTblBase::reportDetails()
   */
  protected function reportDetails()
  // --------------------------------------------------------------------------
  {
    algaeForm::startSingleTab($this->itemName);
    $this->reportOverallDetails();
    algaeForm::endSingleTab();
  }
  
  protected function getTableHeaderActionLinks($openInNewTab = False)
  // --------------------------------------------------------------------------
  {
    global $app;
    $html = '';
    $separator = '';
    if (strlen($this->editpage) > 0)
    {
      $html .= $separator . $app->getPageLink($this->editpage, 'Add ' . $this->itemName, algaeAccess::ROLE_WRITE, $app->settings->appName, '', $openInNewTab);
      $separator = $app->settings->menuSeparator;
    }
    if (strlen($this->browsepage) > 0)
    {
      $html .= $separator . $app->getPageLink($this->browsepage, algaeCore::getSingularOrPlural(2, $this->itemName), algaeAccess::ROLE_WRITE, $app->settings->appName, '', $openInNewTab);
      $separator = $app->settings->menuSeparator;
    }
    return $html;
  }
  
  /**
   * Report records.
   * @param string $tableId Id for HTML table.
   */
  public function reportRecords($tableId = '', $whereClause = '', $maxRecords = 10000)
  // --------------------------------------------------------------------------
  {
    $class = get_class($this);
    $r = new $class();
    $sql = $r->get_sql();
    $sql .= ' ' . $whereClause;
    //
    // ----- run the query
    //
    $db = algaeDB::connect();
    if ($db)
    {
      $result = pg_query($db, $sql);
      if (! $result)
      {
        algaeDB::errorWithSQL($sql);
      }
      else
      {
        echo $this->getTableHeaderActionLinks(False);
        //
        // ----- display the result rows if there are any
        //
        $num_rows = pg_num_rows($result);
        if ($num_rows > 0)
        {
          //
          // ----- initial the table
          //
          if (strlen($tableId) == 0) $tableId = str_replace(' ', '', $r->itemName) . 'ExistingRecordsTable';
          algaeTable::initTablesorterJavascript($tableId, '[[1,0]]');
          algaeTable::start($tableId, 'tablesorter', 'width:100%;');
          //
          // ----- table header
          //
          $header_array = array(
            array('Action', '15%'),
            array($r->itemName, '35%'),
            array('Description', '50%')
          );
          algaeTable::writeHeader($header_array, True);
          //
          // ----- loop through the results
          //
          while ($row = pg_fetch_array($result))
          {
            $r->init();
            $r->read_row_from_database($row);
            echo '<tr>';
            algaeTable::writeData($r->getActionLinks(), False);
            algaeTable::writeData($r->getHomepageLink(), False);
            algaeTable::writeData(algaeCore::getStringWithLinks($r->description), False);
            echo '</tr>';
          }
          algaeTable::end();
        }
        else
        {
          echo '<p />No data in ', $r->table_name, '.<p />';
        }
        algaeDB::close($db, $result);
      }
    }
  }
  
  public function reportExistingRecords()
  // --------------------------------------------------------------------------
  {
    $this->reportRecords();
  }
  
  /**
   * Process a form that's been submitted.
   */
  public function processForm()
  // --------------------------------------------------------------------------
  {
    if (isset($_POST['submit']))
    {
      if (algaeForm::validTokens(algaeForm::getDefaultToken($this)))
      {
        $this->post_control_data();
        //
        //
        //
        if (isset($_REQUEST['rowid']))
        {
          # echo 'DEBUG: ', $this->get_update_sql(), '<p />';
          if ($this->update())
          {
            /* 
            if ($this->forwardOnUpdate) TODO: Might have to enable support in algaeTblSetBase
            {
              header("Location: {$this->homepage}?rowid={$this->rowid}&message=" . urlencode($this->name . ' successfully updated.'));
            }
            */
            algaeApp::successMessage($this->name . ' successfully updated.');
            # $this->updated = True;
            return True;
          }
          else
          {
            algaeApp::errorMessage('Problem updating ' . $this->name . '.');
          }
        }
        else
        {
          if ($this->insert())
          {
            /*
            if ($this->forwardOnAdd)  TODO: Might have to enable support in algaeTblSetBase
            {
              header("Location: {$this->homepage}?rowid={$this->rowid}&message=" . urlencode($this->name . ' successfully added.'));
            }
            */
            algaeApp::successMessage($this->name . ' successfully added.');
            # $this->added = True;
            // $this->init(); TODO: Determine if this is ok, want the rowid id for further processing.
            return True;
          }
          else
          {
            algaeApp::errorMessage('Problem adding ' . $this->name . '.');
          }
        }
      }
    }
    return False;
  }
  
}

