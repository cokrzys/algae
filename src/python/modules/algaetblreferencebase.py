"""

  algae framework | Base class for reference/lookup tables.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

"""

import sys

from algaedb import algaeDB
from algaetblnamedobjectbase import algaeTblNamedObjectBase

class algaeTblReferenceBase(algaeTblNamedObjectBase):

    def __init__(self):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        super().__init__()
        self.sort_order = None