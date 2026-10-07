#!/usr/bin/python3

"""

 algae | Python configuration tester.

 @author    Brian Krzys (brian.krzys@rtspatial.com)
 @copyright (c) 2026 RTSpatial Ltd.
 @license   SPDX-License-Identifier: MIT
 @link      https://github.com/cokrzys/algae

"""

import sys
import json
import builtins

from algaecore import algaeCore
from algaeconfig import algaeConfig
from algaeapp import algaeApp
from algaedb import algaeDB

print(u"\nSearch path for modules:")
for path in sys.path:
    print(path)

app = algaeApp(True, True)

builtins.app = app # add app to builtins for true globl access

#
# ----- open database
#
db = algaeDB()
if db.open(app.config.admin_database, app.config.database_port, app.config.database_username,
           app.config.database_password):
    print('OK: Admin database opened.')
    db.close()



