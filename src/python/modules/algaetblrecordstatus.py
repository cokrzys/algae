"""

  algae framework | Record status and support for ref.record_status.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

"""

import sys

from algaedb import algaeDB
from algaetblbase import algaeTblBase

class algaeTblRecordStatus(algaeTblBase): # do NOT inherit from algaeTblNamedObjectBase to avoid endless loops

    def __init__(self):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        super().__init__()
        self.table_name = 'ref.record_status'
        self.name = None
        self.html_color = None
        self.sort_order = None
        self.description = None

    