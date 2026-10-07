"""

  algae | Database support.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
"""

import psycopg2
import random
import string
from datetime import datetime

from algaeapp import algaeApp

class algaeDB():

    def __init__(self):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        self.connection = None

    @staticmethod
    def clean(val):
    # ------------------------------------------------------------------------------
        """
        Clean a data value.  Principally looks at strings to make sure there is no leading or
        trailing whitespace and if it's an empty string replace in with None.
        Setup for newer logic that uses data dictionaries for most/all database interaction.
        :param val: Value to clean, remains untouched.
        :return: Cleaned value.
        """
        cleaned_val = val
        if isinstance(cleaned_val, str):
            cleaned_val = cleaned_val.strip()
            if cleaned_val == '': cleaned_val = None
        return cleaned_val
        
    def open(self, database, port, username, password):
    #------------------------------------------------------------------------------
        """
        Open a connection to the database.
        """
        try:
            self.connection = psycopg2.connect(u"dbname='{database}' user='{username}' port='{port}' password='{password}'".
                                               format(database=database, username=username, port=port, password=password))
            return True
        except:
            algaeApp.error_message("Unable to connect to the database.")
            print(u"dbname='{database}' user='{username}' port='{port}' password='{password}'".format(
                database=database, username=username, port=port, password=password))
        return False
    
    def close(self):
    #------------------------------------------------------------------------------
        """
        Close the database connection.
        """
        if self.connection != None:
            self.connection.close()
            self.connection = None
            
    @staticmethod
    def get_string_or_null(str):
    #------------------------------------------------------------------------------
        """
        Get a quote delimited string to use in a SQL statement or NULL if the
        string does not exist or is empty.
        """
        if str != None and len(str.strip()) > 0:
            str = str.strip().replace('%', '%%')
            str = str.strip().replace("'", "''")
            return "'" + str + "'"
        else:
            return "NULL"
            
    @staticmethod
    def get_numeric_or_null(value, num_decimals, null_value):
    #------------------------------------------------------------------------------
        """
        Get a string representation of a number or a NULL if the value does not exist.
        """
        if value != None and value != null_value:
            return algaeDB.get_string_or_null("{num:.{decimals}f}".format(num=float(value), decimals=num_decimals))
        else:
            return "NULL"
        
    @staticmethod
    def get_rowid_sql_or_null(table, field, value):
    #------------------------------------------------------------------------------
        """
        Get a portion of an insert statement to get the rowid for a lookup table.
        For example returns "(SELECT rowid FROM std.record_status WHERE name = 'Active')"
        """
        sql = 'NULL'
        if value != None and len(value.strip()) > 0:
          sql = "(SELECT rowid FROM {table} WHERE {field} = '{value}')"\
              .format(table=table, field=field, value=value.strip())
        return sql
    
    @staticmethod
    def get_current_date():
    #------------------------------------------------------------------------------
        """
        Get the current date in a format like '07-Dec-2020'.
        """
        return "'" + datetime.now().strftime("%d-%b-%Y") + "'"
    
    def execute_query(self, sql, data):
    #------------------------------------------------------------------------------
        """
        Execute a query.
        sql = The sql string to execute.
        data = A data array to use with the string, or () for no data.
        """
        # print 'DEBUG: %s' % sql
        try:
            cur = self.connection.cursor()
            cur.execute(sql, data)
            self.connection.commit()
            return True
        except psycopg2.DatabaseError as e:
            if self.connection:
                self.connection.rollback()
            algaeApp.error_message('%r' % e)
            print('SQL: %s' % sql)
        return False
        
    def execute_insert(self, sql, data):
    #------------------------------------------------------------------------------
        """
        Execute a SQL insert statement.
        sql = The sql string to execute.
        data = A data array to use with the string, or () for no data.
        """
        rowid = None
        try:
            cur = self.connection.cursor()
            cur.execute(sql, data)
            rowid = cur.fetchone()[0]
            self.connection.commit()
        except psycopg2.DatabaseError as e:
            if self.connection:
                self.connection.rollback()
            algaeApp.error_message('%r' % e)
            print('SQL: %s' % sql)
            print('Data: %r' % data)
        return rowid
    
    def get_scalar_string(self, sql, data):
    #------------------------------------------------------------------------------
        """
        Get a scalar string value from a PostgreSQL database.
        Returns None on fail.
        """
        ret = None
        try:
            cur = self.connection.cursor()
            cur.execute(sql, data)
            row = cur.fetchone()
            if row != None and row[0] != None:
                ret = row[0]
        except psycopg2.DatabaseError as e:
            algaeApp.error_message('%r' % e)
            print('SQL: %s' % sql)
        return ret
    
    def get_scalar_integer(self, sql, data):
    #------------------------------------------------------------------------------
        """
        Get a scalar integer value from a PostgreSQL database.
        Returns None on fail.
        """
        str = self.get_scalar_string(sql, data)
        if str != None: return int(str)
        return None
    
    def get_scalar_float(self, sql, data):
    #------------------------------------------------------------------------------
        """
        Get a scalar float value from a PostgreSQL database.
        Returns None on fail.
        """
        str = self.get_scalar_string(sql, data)
        if str != None: return float(str)
        return None

    def get_all(self, sql, data):
    #------------------------------------------------------------------------------
        """
        Get all records from a query.
        See: https://pynative.com/python-cursor-fetchall-fetchmany-fetchone-to-read-rows-from-table/
        Returns None on fail.
        """
        ret = None
        try:
            cur = self.connection.cursor()
            cur.execute(sql, data)
            records = cur.fetchall()
            return records
        except psycopg2.DatabaseError as e:
            algaeApp.error_message('%r' % e)
            print('SQL: %s' % sql)
        return ret
    
    def delete_data(self, table, field, value, messages = False):
    #------------------------------------------------------------------------------
        """
        Delete data from a table where a field = a value.
        Check if any data exists before deleting it.
        Returns True if no data to delete or the data was deleted.
        """
        sql_end = " FROM {table} WHERE {field} = %(value)s"\
            .format(table=table, field=field)
        #
        # ----- check if any data exists
        #
        sql = "SELECT COUNT(*)" + sql_end
        num = self.get_scalar_integer(sql, {'value':value})
        if num != None and num > 0:
            if messages: print('Deleting %r row(s) from %s where %s = %r.' % (num, table, field, value))
            sql = "DELETE" + sql_end
            return self.execute_query(sql, {'value':value})
        else:
            if messages: print('No data to delete from %s where %s = %r.' % (table, field, value))
        return True
    
    def table_exists(self, schema, table):
    #------------------------------------------------------------------------------
        """
        Check if a table exists.
        """
        sql = u"""SELECT table_name FROM information_schema.tables
                WHERE table_schema = %(schema)s AND table_name = %(table)s"""
        name = self.get_scalar_string(sql, {'schema':schema, 'table':table})
        if name != None and len(name) > 0: return True
        return False
    
    def get_unique_new_tablename(self, schema, prefix = 'tmp', digits = 5):
    #------------------------------------------------------------------------------
        """
        Get the name of a unique new table.
        Format will be prefixXXXXX, i.e. tmp03876.
        """
        max_tries = 500
        for i in range(0, max_tries):
          table = prefix + ''.join(random.sample(string.digits, digits)).zfill(digits)
          if not self.table_exists(schema, table): return table
        print(algaeApp.ERROR + 'Exceeded %r tries trying to make a uique table name.' % max_tries)
        return None
        
        


