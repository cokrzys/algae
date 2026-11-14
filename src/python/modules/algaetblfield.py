"""

  algae framework | Base class for data fields and ref.field (or derivatives).
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

"""

import sys

from algaedb import algaeDB
from algaetblbase import algaeTblBase
from algaetblnamedobjectbase import algaeTblNamedObjectBase
from algaetblreferencebase import algaeTblReferenceBase
from algaetblreffieldtype import algaeTblRefFieldType

class algaeTblField(algaeTblReferenceBase):

    def __init__(self):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        super().__init__()
        self.table_name = 'ref.field'
        self.field_type = algaeTblRefFieldType()
        self.num_decimals = 2
        self.calc_sql = None
        
    def isNumeric(self):
    #------------------------------------------------------------------------------
        if ( (self.field_type.name == 'NUMERIC') or (self.field_type.name == 'INTEGER') or
          (self.field_type.name == 'SCORE') or (self.field_type.name == 'PERCENTAGE') or
          (self.field_type.name == 'CURRENCY') or (self.field_type.name == 'MODEL')
          ):
            return True
        return False
    
    def isInteger(self):
    #------------------------------------------------------------------------------
        if self.field_type.name == 'INTEGER':
            return True
        return False
    
    def isText(self):
    #------------------------------------------------------------------------------
        if self.field_type.name == 'TEXT' or self.field_type.name == 'LINK':
            return True
        return False
    
    def isDateTime(self):
    #------------------------------------------------------------------------------
        if self.field_type.name == 'DATETIME':
            return True
        return False
    
    def getDataColumn(self):
    #------------------------------------------------------------------------------
        if self.isInteger():
            return 'integer_val'
        elif self.isNumeric():
            return 'numeric_val'
        elif self.isText():
            return 'text_val'
        elif self.isDateTime():
            return 'datetime_val'
        else:
            algaeApp.error_message('Cannot classify field type ' + self.field_type.name + '.');
        

    