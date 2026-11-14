"""

  algae framework | Generic class for a set of items.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

"""

import sys

from algaedb import algaeDB
from algaetblbase import algaeTblBase
from algaetblnamedobjectbase import algaeTblNamedObjectBase

class algaeTblSetBase(algaeTblNamedObjectBase):

    def __init__(self):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        super().__init__()
        self.sql = None

    