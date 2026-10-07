"""

  algae framework | Processes and core.process support.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

"""

import sys
import os

from algaedb import algaeDB
from algaetblbase import algaeTblBase
from concurrent.futures import process

class algaeTblCoreProcess(algaeTblBase):

    def __init__(self):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        super(self.__class__, self).__init__()
        self.table_name = 'core.process'
        self.username = None
        self.application = None
        self.command = None
        self.logfile = None
        self.parmsfile = None
        self.starting_url = None
        self.result_url = None
        self.process_status = None
        self.progress = None
        self.progress_message = None
        self.pid = None
    
    def open_or_create(self, db, process_rowid_fk, username, application):
    #------------------------------------------------------------------------------
        """
        Open an existing process or create a new one.
        """
        if process_rowid_fk != None and process_rowid_fk > 0:
            self.read_row_from_database_with_rowid(db, process_rowid_fk)
            if self.application != application:
                print('ERROR: Cannot open a process from a different application.')
                sql = 'SELECT MAX(rowid) FROM {table} WHERE application = %(application)s'.format(table=self.table_name)
                max_rowid = db.get_scalar_integer(sql, {'application':application})
                if max_rowid > 0:
                    print('Maximum process rowid for application ' + application + ' = ' + str(max_rowid) + '.')
                else:
                    print('No existing processes found for application ' + application + '.')
                # self.rowid = None
                # return
                sys.exit()  # hard fail, potentially better ways to handle this
        if process_rowid_fk == None or process_rowid_fk <= 0:
            self.create(db, username, application)
    
    def update_status(self, db, status):
    #------------------------------------------------------------------------------
        """
        Update the status.
        """
        if self.rowid != None and self.rowid > 0:
            sql = u"UPDATE {table} SET process_status_rowid_fk = ".format(table=self.table_name)
            sql += algaeDB.get_rowid_sql_or_null('ref.process_status', 'name', status)
            sql += " WHERE rowid = '"
            sql += str(self.rowid) + "'"
            return db.execute_query(sql, ())
        return False
    
    def update_progress(self, db, progress):
    #------------------------------------------------------------------------------
        """
        Update the progress.
        """
        if self.rowid != None and self.rowid > 0:
            sql = u"UPDATE {table} SET progress = ".format(table=self.table_name)
            sql += algaeDB.get_numeric_or_null(progress, 1, -99)
            sql += " WHERE rowid = '"
            sql += str(self.rowid) + "'"
            ret = db.execute_query(sql, ())
            if ret == True and progress == 100.0:
                self.update_status(db, 'Finished')
            return ret
        return False
    
    def update_message(self, db, message):
    #------------------------------------------------------------------------------
        """
        Update the progress message.
        """
        if self.rowid != None and self.rowid > 0:
            sql = u"UPDATE {table} SET progress_message = ".format(table=self.table_name)
            sql += algaeDB.get_string_or_null(message)
            sql += " WHERE rowid = '"
            sql += str(self.rowid) + "'"
            return db.execute_query(sql, ())
        return False
    
    
    