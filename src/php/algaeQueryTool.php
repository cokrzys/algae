<?php

/**

  algae framework | Query tool support class.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

*/

class algaeQueryTool
{
  
  public $form;
  public $tableId;
  public $sql;
  public $last_sql;
  public $schema;
  
  CONST SCHEMA_PARAMETER = 'queryToolSchemaAndTable';
  
  /**
   * Default constructor.
   */
  public function __construct()
  // --------------------------------------------------------------------------
  {
    $this->form = new algaeForm();
    $this->sql = '';
    $this->last_sql = '';
    $this->schema = algaeTblCoreUserParameter::getParameter(algaeQueryTool::SCHEMA_PARAMETER);
    $max_rowid = algaeDB::getScalarInteger('SELECT MAX(rowid) FROM core.query', array(), 0);
    if ($max_rowid > 0)
    {
      $this->last_sql = algaeDB::getScalarString('SELECT sql FROM core.query WHERE rowid = $1', array($max_rowid));
    }
    $this->max_rows = 5000;
    $this->tableId = 'QueryResultsTable';
    //
    // ----- get SQL from the form if posted
    //
    if (isset($_POST['submit']))
    {
      if (isset($_POST['sql']))
      {
        $this->sql = $_POST['sql'];
      }
    }
    if (strlen($this->sql) == 0) $this->sql = $this->last_sql;
    //
    // ----- write current rowid so it can be used to get next/last queries via an ajax call
    //
    algaeCore::addJavaScript('var curSQLRowid = ' . $max_rowid . ';');
  }
  
  /**
   * Show the input form.
   */
  public function showForm()
  // --------------------------------------------------------------------------
  {
    $this->form->startForm('query_tool');
    // TODO: rows does nothing here, see the JavaScript setup in algaeFramework.js
    echo '<textarea name="sql" id="sql" cols="110" rows="18">', algaeCore::toHtml($this->sql), '</textarea><p />';
    echo '<p />';
    echo '&nbsp;';
    $sql = "SELECT table_schema || '.' || table_name
            FROM information_schema.tables
            WHERE table_schema NOT IN ('information_schema', 'pg_catalog', 'topology')
            ORDER BY table_schema || '.' || table_name";
    echo algaeForm::selectWithSQL($sql, 'schema', $this->schema);
    // echo '&nbsp;<input type="button" value="Select Data" onclick="selectElementContents( document.getElementById(\'', $this->tableId, '\') );">';
    echo '&nbsp;', algaeForm::button('schema', 'Show Schema', 'showSchema();');
    // echo '&nbsp;', algaeForm::button('code_schema', 'Code Schema', 'codeSchema();');
    echo '&nbsp;&nbsp;&nbsp;', algaeForm::button('clear', 'Clear', "editor.getDoc().setValue('');");
    echo '&nbsp;', algaeForm::button('last', 'Last', 'loadLastQuery();');
    echo '&nbsp;', algaeForm::button('next', 'Next', 'loadNextQuery();');
    echo '&nbsp;', algaeForm::button('expand_sql', 'Expand SQL', 'expandSQL();');
    $this->form->endForm('Execute');
  }
  
  /**
   * Show the results.
   */
  public function showResultsTable()
  // --------------------------------------------------------------------------
  {
    //
    // ----- run the query
    //
    $db = algaeDB::connect();
    if ($db)
    {
      $result = pg_query($db, $this->sql);
      if (! $result)
      {
        algaeDB::errorWithSQL($this->sql);
      }
      else
      {
        //
        // ----- display the result rows if there are any
        //
        $num_rows = pg_num_rows($result);
        $num_fields = pg_num_fields($result);
        if ( ($num_rows > 0) && ($num_fields > 0) )
        {
          if ($num_rows > $this->max_rows)
          {
            echo 'The query returned ' .
            algaeCore::getFormattedNumber($num_rows, 0, - 99) . ' rows, but only the top ' .
            algaeCore::getFormattedNumber($this->max_rows, 0, - 99) . ' are displayed.<p />';
          }
          //
          // ----- initial the table
          //
          
          algaeTable::initTablesorterJavascript($this->tableId);
          algaeTable::start($this->tableId);
          //
          // ----- table header
          //
          $header_array = array();
          // $field_type_array = array();
          for ($i=0; $i < $num_fields; $i++)
          {
            $header_array[] = pg_field_name($result, $i);
            // $field_type_array[] = pg_field_type($result, $i);
          }
          algaeTable::writeHeader($header_array, True);
          //
          // ----- loop through the results
          //
          $num_rows_read = 0;
          while (($row = pg_fetch_array($result)) && ($num_rows_read < $this->max_rows))
          {
            echo '<tr>';
            for ($i=0; $i < $num_fields; $i++)
            {
              $str = algaeDB::cleanDataRead($row[$i]);
              if ((strlen($str) == 7) && ($str[0] == '#'))
              {
                $str = algaeCore::getColorBlock($str, True, $str);
              }
              algaeTable::writeData($str, False);
            }
            echo '</tr>';
            $num_rows_read++;
          }
          algaeTable::end();
        }
        pg_free_result($result);
        algaeDB::close($db);
      }
    }
  }
  
  /**
   * Process the query.
   */
  public function processQuery()
  // --------------------------------------------------------------------------
  {
    global $app;
    $this->sql = trim($this->sql);
    if (strlen($this->sql) > 0)
    {
      //
      // ----- make sure the query cannot damage the database
      //
      if ( (stripos($this->sql, 'DELETE', 0) !== False) ||
        (stripos($this->sql, 'UPDATE', 0) !== False) ||
        (stripos($this->sql, 'INSERT', 0) !== False) ||
        (stripos($this->sql, 'DROP', 0) !== False) ||
        (stripos($this->sql, 'ALTER', 0) !== False) ||
        (stripos($this->sql, 'CREATE', 0) !== False) ||
        (stripos($this->sql, 'TRUNCATE', 0) !== False) )
      {
        $app->errorMessage('One or more forbidden commands used.');
      }
      else
      {
        //
        // ----- save the sql if it's different from the last saved query
        //
        if ($this->sql != $this->last_sql)
        {
          if (strlen(algaeAccess::getUsername()) > 0)
          {
            $insert_sql = 'INSERT INTO core.query (user_rowid_fk, sql) VALUES (';
            $insert_sql .= algaeAccess::getRowidSQLforUsername(algaeAccess::getUsername());
            $insert_sql .= ', ' . algaeDB::getStringOrNull(algaeDB::cleanInput($this->sql));
            $insert_sql .= ')';
          }
          else 
          {
            $insert_sql = 'INSERT INTO core.query (sql) VALUES (';
            $insert_sql .= algaeDB::getStringOrNull(algaeDB::cleanInput($this->sql));
            $insert_sql .= ')';
          }
          algaeDB::executeQuery($insert_sql, array());
        }
        $this->showResultsTable();
      }
    }
  }
  
  /**
   * Show SQL form and results.
   */
  public function show()
  // --------------------------------------------------------------------------
  {
    algaeForm::startTabs(array(
      array('#sql_tab', 'SQL')
    ));
    //
    // ----- sql_tab
    //
    echo '<div id="sql_tab">';
    $this->showForm();
    $this->processQuery();
    echo '</div>';
    //
    // ----- end tabs
    //
    algaeForm::endTabs('tabs', '700px');
  }
  
  /**
   * Get a saved query via an AJAX call.
   */
  public static function processAjaxGetQuery()
  // --------------------------------------------------------------------------
  {
    //
    // ----- check required parameters
    //
    if ( (isset($_GET['current_sql_rowid'])) && (isset($_GET['direction'])) )
    {
      $current_sql_rowid = $_GET['current_sql_rowid'];
      $direction = $_GET['direction'];
      if ($direction < 0) $sql = 'SELECT MAX(rowid) FROM core.query WHERE rowid < $1';
      if ($direction > 0) $sql = 'SELECT MIN(rowid) FROM core.query WHERE rowid > $1';
      $sql .= ' AND sql <> (SELECT sql FROM core.query WHERE rowid = $1)';
      $rowid = algaeDB::getScalarInteger($sql, array($current_sql_rowid), 0);
      //
      // ----- make sure the rowid stays in range
      //
      if ( ($rowid == 0) && ($direction > 0) )
      {
        $rowid = algaeDB::getScalarInteger('SELECT MAX(rowid) FROM core.query', array(), 0);
      }
      if ( ($rowid == 0) && ($direction < 0) )
      {
        $rowid = algaeDB::getScalarInteger('SELECT MIN(rowid) FROM core.query', array(), 0);
      }
      //
      // ----- get the sql
      //
      $sql = algaeDB::getScalarString('SELECT sql FROM core.query WHERE rowid = $1', array($rowid));
      //
      // ----- return the result
      //
      echo json_encode(array('status'=>'success', 'rowid'=>$rowid, 'sql'=>$sql));
      exit;
    }
    else
    {
      echo json_encode(array('status'=>'fail'));
      exit;
    }
  }
  
  /**
   * Get a table schema via an AJAX call.
   */
  public static function processAjaxGetSchema()
  // --------------------------------------------------------------------------
  {
    //
    // ----- check required parameters
    //
    if (isset($_GET['schema_and_table']))
    {
      $schema_and_table = $_GET['schema_and_table'];
      algaeTblCoreUserParameter::saveParameter(algaeQueryTool::SCHEMA_PARAMETER, $schema_and_table);
      $pieces = explode('.', $schema_and_table);
      if (count($pieces) == 2)
      {
        $sql = "SELECT ordinal_position, column_name, udt_name, is_nullable
            FROM information_schema.columns
            WHERE table_schema = $1 AND table_name = $2
            ORDER BY ordinal_position";
        //
        // ----- run the query
        //
        $db = algaeDB::connect();
        if ($db)
        {
          $result = pg_query_params($db, $sql, $pieces);
          if (! $result)
          {
            algaeDB::errorWithSQL($sql, $pieces);
          }
          else
          {
            $num_rows = pg_num_rows($result);
            if ($num_rows > 0)
            {
              $html = '<table id="schemaTable" class="algae_table" style="width:100%;margin-top:0.2cm">';
              $html .= '<thead><tr>';
              $html .= '<th>Order</th>';
              $html .= '<th>Field</th>';
              $html .= '<th>Type</th>';
              $html .= '<th>Nullable</th>';
              $html .= '</tr></thead>';
              //
              // ----- loop through the results
              //
              while ($row = pg_fetch_array($result))
              {
                $html.= '<tr>';
                for ($i = 0; $i < 4; $i++)
                {
                  $html.= '<td>' . algaeDB::cleanDataRead($row[$i]) . '</td>';
                }
                $html.= '</tr>';
              }
              $html .= '</table>';
            }
            pg_free_result($result);
            algaeDB::close($db);
            //
            // ----- success
            //
            echo json_encode(array('status'=>'success', 'html'=>$html));
            exit;
          }
        }
      }
    }
    else
    {
      echo json_encode(array('status'=>'fail'));
      exit;
    }
  }
  
}





