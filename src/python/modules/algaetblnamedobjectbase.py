"""

  algae framework | Base class for named objects.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

"""

import sys

from algaedb import algaeDB
from algaetblbase import algaeTblBase
from algaetblrecordstatus import algaeTblRecordStatus

class algaeTblNamedObjectBase(algaeTblBase):

    def __init__(self):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        super().__init__()
        self.name = None
        self.description = None
        self.html_color = None
        self.record_status = algaeTblRecordStatus()
    
    def read_row_from_database_with_name(self, db, name, deep_read = True):
    #------------------------------------------------------------------------------
        """
        Read a row from the database with a name.
        """
        sql = self.get_sql()
        sql += u""" WHERE {table}.name = %(name)s""".format(table=self.table_name)
        return self.read_row_from_database_with_sql(db, sql, {'name': name}, deep_read)
         
        
    
    
    
    