"""

  algae framework | App user and table core.app_user support.
  
  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae

"""

import sys

from algaedb import algaeDB
from algaetblbase import algaeTblBase

class algaeTblCoreAppUser(algaeTblBase):

    def __init__(self):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        super().__init__()
        self.table_name = 'core.app_user'
        self.username = None


    