"""

  algae framework | Generic class for data values associated with a parent object.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

"""

import sys

from algaedb import algaeDB
from algaetblbase import algaeTblBase

class algaeTblDataValueBase(algaeTblBase):

    def __init__(self, parentClass, parentColumnName, fieldClass = 'algaeTblField', fieldColumnName = 'field_rowid_fk'):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        super().__init__()
        self.parent = globals()[parentClass]()
        self.field = globals()[algaeTblField]()
        self.parent_column_name = parentColumnName
        self.field_column_name = fieldColumnName
        self.zero()
        
    def zero(self):
    #------------------------------------------------------------------------------
        """
        """
        self.text_val = None
        self.numeric_val = None
        self.integer_val = None
        self.datetime_val = None
        self.description = None

    