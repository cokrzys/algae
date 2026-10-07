"""

  algae framework | Base class for accessing a database table.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

"""

import sys
import psycopg2
import inspect

from algaeapp import algaeApp
from algaedb import algaeDB

class algaeTblBaseV2():
    
    ALT_WRITE_SQL_PROP_NAME = 'altWriteSQL'
    EXISTS_CHECK_SQL_PROP_NAME = 'existsCheckSQL'
    
    VARIABLE_NAME = 'variableName'
    # PYTHON_VARIABLE_NAME = 'pythonVariableName'; maybe needed if names are different
    
    RELATIONSHIPS_PROP_NAME = 'relationships'
    JOIN_SQL_PROP_NAME = 'joinSQL'

    def __init__(self):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        self.table_name = 'algae.base'
        self.rowid = None
        self.timestamp_loaded_utc = None
        self.timestamp_modified_utc = None
        self.debug = False

    def copy_to(self, other):
    #------------------------------------------------------------------------------
        """
        Copy attributes to another object.
        """
        other.table_name = self.table_name
        other.rowid = self.rowid
        other.timestamp_loaded_utc = self.timestamp_loaded_utc
        other.timestamp_modified_utc = self.timestamp_modified_utc
        
    def print_data(self, data):
    #------------------------------------------------------------------------------
        for attribute, value in data.items():
            print('%r %r' % (attribute, value))
  
    def get_array_from_dex(self, table_name, property_name):
    # ------------------------------------------------------------------------------
        """
        """
        columns = list()
        table_found = False
        index = 0
        while ( (table_found == False) and (index < len(app.config.dex_json)) ):
            # print(app.config.dex_json[index])
            if app.config.dex_json[index]['tableName'] == table_name:
                parentTableName = app.config.dex_json[index].get('parentTableName', None)
                if parentTableName != None:
                    if self.debug: print('DEBUG: Adding columns from parentTableName = ' + str(parentTableName))
                    columns += self.get_array_from_dex(parentTableName, property_name)
                    
                prop = app.config.dex_json[index].get(property_name, None)
                if prop != None:
                    if self.debug:
                        print('DEBUG: Columns to be added from ' + table_name) 
                        print(app.config.dex_json[index].get(property_name, None))
                    columns += app.config.dex_json[index].get(property_name, None)
                    
                table_found = True
            else:
                index += 1
        return columns

    def get_columns(self):
    # ------------------------------------------------------------------------------
        """
        Get columns.  A column is broadly a database column and all the information needed to move data back and forth
        between the database and an application object.  Derived classes will change or expand this.
        :return: List of dictionaries, one dictionary for each column.
        """
        
        return self.get_array_from_dex(self.table_name, 'columns')
        
#         columns = list()
#         columns.append({'name':'rowid', 'canInsert':False, 'canUpdate':False})
#         columns.append({'name':'timestamp_loaded_utc', 'canInsert': False, 'canUpdate': False,
#                         'altReadSQL':"to_char(" + self.table_name + ".timestamp_loaded_utc, 'DD-Mon-YYYY HH24:MI:SS') AS timestamp_loaded_utc_str"})
#         columns.append({'name':'timestamp_modified_utc', 'canInsert': False, 'canUpdate': False,
#                         'altReadSQL':"to_char(" + self.table_name + ".timestamp_modified_utc, 'DD-Mon-YYYY HH24:MI:SS') AS timestamp_modified_utc_str"})
#         return columns
    
    def get_relationships(self):
    # ------------------------------------------------------------------------------
        """
        Get relationships.  Contains relationships between the primary database table associated with the 
        class and related tables.
        :return: List of dictionaries, one dictionary for each relationship.
        """
        
        return self.get_array_from_dex(self.table_name, algaeTblBaseV2.RELATIONSHIPS_PROP_NAME)
        
#         relationships = list()
#         return relationships

    def check_columns(self, columns, type):
        """
        """
        names = set([d.get('name', '') for d in columns])
        if len(names) != len(columns):
            print('ERROR: Non-unique ' + type + ' column names found.')
            print('  Check to make sure the old get_columns() does not exist in bases classes for the object. ')  
    
    def get_read_columns(self):
    # ------------------------------------------------------------------------------
        """
        Get columns to be used in a SELECT statement.
        :return: List of dictionaries, one dictionary for each column.
        """
        return [d for d in self.get_columns() if d.get('canRead', True) == True]

    def get_insert_columns(self):
    # ------------------------------------------------------------------------------
        """
        Get columns to be used in an INSERT statement.
        :return: List of dictionaries, one dictionary for each column.
        """
        columns = [d for d in self.get_columns() if d.get('canInsert', True) == True]
        self.check_columns(columns, 'INSERT')
        return columns

    def get_update_columns(self):
    # ------------------------------------------------------------------------------
        """
        Get columns to be used in an UPDATE statement.
        :return: List of dictionaries, one dictionary for each column.
        """
        columns = [d for d in self.get_columns() if d.get('canUpdate', True) == True]
        self.check_columns(columns, 'INSERT')
        return columns
    
    def get_columns_for_name(self, name):
    # ------------------------------------------------------------------------------
        """
        Get column(s) for a specified column name.
        :return: List of dictionaries, one dictionary for each column.
        """
        return [d for d in self.get_columns() if d.get('name', None) == name]

    def get_unique_key_columns(self):
    # ------------------------------------------------------------------------------
        """
        Get columns that are used to define a unique key.
        :return: List of dictionaries, one dictionary for each column.
        """
        return [d for d in self.get_columns() if d.get('uniqueKey', False) == True]

    def get_column_value_for_key(self, column, key):
    # ------------------------------------------------------------------------------
        """
        Get a column value (typically a name) for a dictionary key in the columns dictionary.
        If the key does not exist assign a default value (typically the column name).
        :param column: Dictionary with column details.
        :param key: Key to check for and use value if there or else a default.
        :return: Value from key or default.
        """
        name = column.get(key, None)
        if name == None:
            name = column.get('name')
        return name

    def get_sql_data_name(self, column):
    # ------------------------------------------------------------------------------
        """
        Get name for value in SQL statements, used for '%(name)s' placeholders
        and the matching key name in a SQL data dictionary.
        If not specified the column name is the default.
        :param column: Dictionary with column details.
        :return: SQL data name.
        """
        return self.get_column_value_for_key(column, 'sqlDataName')

    def get_class_variable_name(self, column):
    # ------------------------------------------------------------------------------
        """
        Get the name of the class variable associated with the database column.
        Names can reference values within variable that is itself a class.
        For example use company.rowid to refernce the rowid within a class instance named company.
        If not specified the column name is the default.
        :param column: Dictionary with column details.
        :return: Class variable name.
        """
        return self.get_column_value_for_key(column, self.VARIABLE_NAME)

    def get_data_placeholder(self, column, prop=None):
    # ------------------------------------------------------------------------------
        """
        """
        if prop == None: prop = self.ALT_WRITE_SQL_PROP_NAME
        if column.get(prop, None) != None:
            return column.get(prop, None)
        return "%(" + column.get('name') + ")s"

    def get_insert_sql(self):
    # ------------------------------------------------------------------------------
        """
        Get sql to use in a database INSERT.
        :return:
        """
        separator = ''
        #
        # ----- make a local copy of columns so we don't add to the columns list twice
        #
        columns = self.get_insert_columns()
        sql = u"""INSERT INTO {table} (""".format(table=self.table_name)
        for column in columns:
            sql += separator + column.get('name')
            separator = ','
        sql += ") VALUES ("
        separator = ''
        for column in columns:
            sql += separator + self.get_data_placeholder(column)
            separator = ','
        sql += ") RETURNING rowid"
        return sql

    def get_unique_key_sql(self):
    # ------------------------------------------------------------------------------
        """
        :return:
        """
        sql = None
        columns = self.get_unique_key_columns()
        if (len(columns) > 0):
            sql = u"""SELECT rowid FROM {table} WHERE""".format(table=self.table_name)
            separator = ' '
            for column in columns:
                sql += separator + column.get('name') + ' = '
                sql += self.get_data_placeholder(column, self.EXISTS_CHECK_SQL_PROP_NAME)
                separator = ' AND '
        return sql

    def get_update_sql(self):
    # ------------------------------------------------------------------------------
        """
        Get sql to use in a database UPDATE.
        :return:
        """
        columns = self.get_update_columns()
        separator = ' '
        sql = u"UPDATE {table} SET".format(table=self.table_name)
        for column in columns:
            sql += separator + column.get('name') + ' = ' + self.get_data_placeholder(column)
            separator = ', '
        sql += " WHERE {table}.rowid = {rowid}".format(table=self.table_name, rowid=self.rowid)
        return sql

    def get_data(self, columns):
    # ------------------------------------------------------------------------------
        """
        :return:
        """
        data = dict()
        for column in columns:
            sqlDataName = self.get_sql_data_name(column)
            #
            # ----- check if a specific class variable name is specified
            #
            classVariableName = column.get('classVariableName', None)
            if classVariableName != None:
                if '.' in classVariableName:
                    #
                    # ----- process nested variable names, i.e. company.rowid
                    #
                    parts = classVariableName.split('.')
                    val = self
                    for part in parts:
                        if hasattr(val, part):
                            val = getattr(val, part, None)
                            data[sqlDataName] = val
                        else:
                            algaeApp.error_message('Missing variable name ' + part + ' from ' + classVariableName + ', check columns setup.')
                else:
                    #
                    # ----- get variable name value from specified non-nested name
                    #
                    if hasattr(self, classVariableName):
                        data[sqlDataName] = getattr(self, classVariableName, None)
            else:
                #
                # ----- instance variable name is the same name as the column (the default)
                #
                if hasattr(self, column.get('name')):
                    data[sqlDataName] = getattr(self, column.get('name'), None)
        return data
    
    def get_parameters_dict(self, sql):
    # ------------------------------------------------------------------------------
        """
        """
        parameters = dict()
        start = 0
        while start < len(sql):
            pos1 = sql.find('%(', start)
            if pos1 > -1:
                pos2 = sql.find(')s', pos1)
                if pos2 > -1:
                    # print('pos1 = ' + str(pos1) + ' pos2 = ' + str(pos2))
                    parameter_name = sql[pos1+2:pos2]
                    parameters[parameter_name] = None
                    # print('[' + parameter_name + ']')
                    start = pos2 + 2
                else:
                    start = len(sql)
            else:
                start = len(sql)
        return parameters
    
    def get_value_from_variable_name(self, varname):
    # ------------------------------------------------------------------------------
        # print('DEBUG: Checking ' + varname)
        if '.' in varname:
            #
            # ----- process nested variable names, i.e. company.rowid
            #
            parts = varname.split('.')
            val = self
            for part in parts:
                if hasattr(val, part):
                    val = getattr(val, part, None)
                else:
                    algaeApp.error_message('Missing variable name ' + part + ' from ' + classVariableName + ', check columns setup.')
                    val = None
            return val
        else:
            #
            # ----- get variable name value from specified non-nested name
            #
            if hasattr(self, varname):
                return getattr(self, varname, None)
        return None
    
    def get_data_v2(self, sql):
    # ------------------------------------------------------------------------------
        """
        :return:
        """
        parameters = self.get_parameters_dict(sql)
        for key in parameters:
            #
            # ----- (1) easiest case, check if an instance variable name = the sql parameter name
            #
            if hasattr(self, key):
                parameters[key] = getattr(self, key, None)
            else:
                #
                # ----- (2) get associated column then check if the classVariableName is set and use it
                #           checks single names and class.variable names
                #
                column = self.get_columns_for_name(key)
                if len(column) == 1:
                    cvn = self.get_class_variable_name(column[0])
                    if cvn != None:
                        parameters[key] = self.get_value_from_variable_name(cvn)
                #
                # ----- (3) look for instance variables that match class_name.variable
                #           for example data_type.name
                #
                elif column == None or len(column) == 0 or len(column) > 1:
                    parameters[key] = self.get_value_from_variable_name(key)
                    
        return parameters

    def get_insert_data(self):
    # ------------------------------------------------------------------------------
        return self.get_data_v2(self.get_insert_sql())

    def get_update_data(self):
    # ------------------------------------------------------------------------------
        return self.get_data_v2(self.get_update_sql())

    def get_unique_key_data(self):
    # ------------------------------------------------------------------------------
        return self.get_data_v2(self.get_unique_key_sql())
        
    def get_fields(self):
    #------------------------------------------------------------------------------
        """
        Get fields.
        """
        prop_table_name = 'table_name'  # property of the local class
        altReadSQLProp = 'altReadSQL'  # property of the dex JSON
        if not hasattr(self, prop_table_name) or getattr(self, prop_table_name, None) == None:
            algaeApp.error_message('Missing ' + prop_table_name + ' attribute in class ' + self.__class__.__name__ + '.')
            return
        #
        #
        #
        separator = ''
        columns = self.get_read_columns()
        sql = ""
        for column in columns:
            cname = ''
            altReadSQL = column.get(altReadSQLProp, None)
            if altReadSQL != None:
                if '{' + prop_table_name + '}' in altReadSQL: 
                    cname = altReadSQL.replace('{' + prop_table_name + '}', getattr(self, prop_table_name))
                else:
                    cname = altReadSQL
            else:
                cname = getattr(self, prop_table_name) + '.' + column.get('name')
            sql += separator + cname
            separator = ', '
        return sql
                
    def get_joins(self):
    #------------------------------------------------------------------------------
        """
        Get joins.
        """
        sql = " FROM " + self.table_name
        relationships = self.get_relationships()
        for relationship in relationships:
            joinSQL = relationship.get(algaeTblBaseV2.JOIN_SQL_PROP_NAME, None)
            if joinSQL != None:
                sql += ' ' + joinSQL
        return sql
    
    def get_sql(self):
    #------------------------------------------------------------------------------
        """
        Get the SQL to read a record.
        """
        return "SELECT " + self.get_fields() + self.get_joins();
    
    def read_row_from_database(self, row):
    #------------------------------------------------------------------------------
        """
        Read data from a database row.
        If there is not a class variable for the value reat it's created.
        https://stackoverflow.com/questions/31174295/getattr-and-setattr-on-nested-subobjects-chained-properties
        """
        columns = self.get_read_columns()
        for i, column in enumerate(columns):
            classVariableName = self.get_class_variable_name(column)
            if '.' in classVariableName:
                # handles one parent and child, i.e. record_status.rowid
                parent, child = classVariableName.split('.')
                setattr(getattr(self, parent), child, row[i])
            else:
                setattr(self, classVariableName, row[i])

    def read_extra_data(self, db):
    # ------------------------------------------------------------------------------
        """
        Read extra data after a row has been read.
        :param db: Database handle.
        :return: True (default).
        """
        if self.debug: print('DEBUG: Starting extra data read.')
        # relationships = self.get_relationships()  NOT USED ?
        #
        # ----- loop through class variables
        #
        for var_name, var_value in self.__dict__.items():
            # print(f"Variable name: {var_name}, Value: {var_value}")
            if self.debug: 
                className = type(getattr(self, var_name)).__name__
                print('DEBUG: Checking ' + className + ' for data to read.')
            obj = getattr(self, var_name)
            #
            # ----- if an object has the right attribute (rowid) and method call it to read the data
            #
            if hasattr(obj, 'rowid') and callable(getattr(obj, 'read_row_from_database_with_rowid')):
                if self.debug: print('DEBUG: class has read_row_from_database_with_rowid().')
                #
                # ----- by including True in (db, obj.rowid, True) will make this recursive
                #       so it reads down through nested classes and calls their individual data readers
                #
                rowid = getattr(obj, 'rowid', None)
                if rowid != None:
                    if self.debug: print('DEBUG: Reading data from ' + obj.table_name)
                    obj.read_row_from_database_with_rowid(db, rowid, True)        
        return True
    
    def read_row_from_database_with_sql(self, db, sql, data, deep_read = True):
    #------------------------------------------------------------------------------
        """
        Read a row from the database with a SQL statement.
        """
        # print('DEBUG: Reading data for class ' + self.__class__.__name__)
        try:
            cur = db.connection.cursor()
            cur.execute(sql, data)
            row = cur.fetchone()
            if row != None: 
                self.read_row_from_database(row)
                if deep_read: self.read_extra_data(db)
                return True
        except psycopg2.DatabaseError as e:
            algaeApp.error_message('%r' % e)
            print('SQL: %s' % sql)
        return False
    
    def read_row_from_database_with_rowid(self, db, rowid, deep_read = True):
    #------------------------------------------------------------------------------
        """
        Read a row from the database with a rowid.
        """
        sql = self.get_sql()
        sql += u" WHERE {table}.rowid = %(rowid)s".format(table=self.table_name)
        return self.read_row_from_database_with_sql(db, sql, {'rowid': rowid}, deep_read)
    
    def not_implemented_message(self, method):
    #------------------------------------------------------------------------------
        """
        Print a message indicating a method is not implemented.
        self.__class__.__name__ = get name of derived class
        """
        print(algaeApp.ERROR + method + '() not implemented in ' + self.__class__.__name__ + '.')
        
    def get_rowid(self, db):
    #------------------------------------------------------------------------------
        """
        Get the rowid of an existing record using unique values.
        Typically used to check if a record already exists in the database.
        
        sys._getframe(  ).f_code.co_name = get name of method, no built-in clean way
        see: https://stackoverflow.com/questions/57866318/get-the-name-of-the-current-method-function-in-python
        """
        print(algaeApp.ERROR + method + '() not implemented in ' + self.__class__.__name__ + '.')
        
    def exists(self, db):
    #------------------------------------------------------------------------------
        """
        Check if a unique row already exists in the database.
        """
        sql = self.get_unique_key_sql()
        if self.debug:
            print(self.get_unique_key_sql())
            print(self.get_unique_key_data())
        if sql != None and len(sql) > 0:
            self.rowid = db.get_scalar_integer(self.get_unique_key_sql(), self.get_unique_key_data())
            if self.rowid != None and self.rowid > 0: return True
        else:
            algaeApp.error_message('Unique key column(s) not defined in connections to ' + self.table_name + '.')
        return False

    def ok_to_insert(self):
    # ------------------------------------------------------------------------------
        """
        :return:
        """
        error_message_suffix = ' found in the INSERT data dictionary.'
        num_errors = 0
        columns = self.get_insert_columns()
        data = self.get_insert_data()
        #
        # ----- TODO MAJOR: Update for newer v2 data logic
        #
#         for column in columns:
#             sqlDataName = self.get_sql_data_name(column)
#             if sqlDataName in data:
#                 if column.get('required', False) == True:
#                     if data.get(sqlDataName, None) == None:
#                         algaeApp.error_message('Required value for ' + sqlDataName + ' not' + error_message_suffix)
#                         num_errors += 1
#                 if data.get(sqlDataName, None) == '':
#                     algaeApp.error_message('Empty value for ' + sqlDataName + error_message_suffix)
#                     num_errors += 1
#             else:
#                 algaeApp.error_message('Key ' + sqlDataName + ' not' + error_message_suffix)
#                 num_errors += 1
        if num_errors > 0: return False
        return True
    
    def ok_to_update(self):
    # ------------------------------------------------------------------------------
        """
        :return:
        """
        if self.rowid != None and self.rowid > 0:
            # TODO: Expand ok_to_update() with more checks, i.e. on required fields not becoming null
            return True
        else:
            algaeApp.error_message('Rowid not specified for UPDATE to ' + self.table_name + '.')
        return False

    def insert(self, db):
    #------------------------------------------------------------------------------
        """
        Add a new row to the database.
        """
        if self.debug:
            print(self.get_columns())
            print(self.get_insert_sql())
            print(self.get_insert_data())
        self.rowid = db.execute_insert(self.get_insert_sql(), self.get_insert_data())
        if self.rowid != None and self.rowid > 0: return True
        return False

    def update(self, db):
    #------------------------------------------------------------------------------
        """
        Update an existing row in the database.
        """
        return db.execute_query(self.get_update_sql(), self.get_update_data())
    
    def write_to_database(self, db):
    #------------------------------------------------------------------------------
        """
        """
        if self.exists(db):
            if self.ok_to_update():
                return self.update(db)
        else:
            if self.ok_to_insert():
                return self.insert(db)
        return False
                
    def load_source_data(self, source_data):
    # ------------------------------------------------------------------------------
        """
        :param source_data:
        :return:
        """
        columns = self.get_columns()
        for column in columns:
            if 'sourceDataTag' in column:
                sourceDataTag = column.get('sourceDataTag', None)
                if sourceDataTag != None:
                    classVariableName = self.get_class_variable_name(column)
                    val = algaeDB.clean(source_data.get(sourceDataTag, None))
                    if val != None:
                        setattr(self, classVariableName, val)

        
        
    
    
    