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

    