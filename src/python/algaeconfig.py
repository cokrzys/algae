"""

  algae | algae configuration.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
"""

import sys
import os
import json
from collections import OrderedDict
from dotenv import dotenv_values

class algaeConfig():
    
    KEY_RTSPATIAL_LOCAL_CONFIG_PATH = 'RTSPATIAL_LOCAL_CONFIG_PATH'
    
    def __init__(self, load_detailed_config = True, debug = False):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        self.app_name = 'algae'
        self.config_path = '/opt/algae-main/config/'
        self.local_config_path = '/opt/rtspatial/config/'
        
        self.admin_database = 'algae'
        self.app_database = 'algae'
        self.database_username = 'postgres'
        self.database_port = 5432
        self.database_host = 'localhost'
        self.database_password = 'take_a_chance'
        
        self.dex_json = list()
        self.wm_json = list()
        self.apps_json = list()
        
        #
        # ----- path for main configuration files
        #       config that a user should not edit
        #       updated with application updates
        #
        if debug: print('OK: algaeconfig.py found at ' +  __file__)
        p = __file__.find('/src')
        if (p >  -1):
            self.config_path = __file__[:p] + '/config'
            if debug: print('OK: config_path set to ' + self.config_path)
        else:
            print('ERROR: Unable to convert ' + __file__ + ' into the config path, defaulting to ' + self.config_path)

        
        if os.environ.get(algaeConfig.KEY_RTSPATIAL_LOCAL_CONFIG_PATH) != None:
            self.local_config_path = os.environ.get(algaeConfig.KEY_RTSPATIAL_LOCAL_CONFIG_PATH)
        else:
            print('Environment varible ' + algaeConfig.KEY_RTSPATIAL_LOCAL_CONFIG_PATH + ' is not setup.')
            
#         if os.path.isdir(self.config_path):
#             self.loadAppConfigFile()
#             self.loadDataExchangeConfig()
#         else:
#             print('Configuration path ' + self.config_path + ' does not exist.')
            
    def loadAppConfigFile(self):
    #------------------------------------------------------------------------------
        """
        Load configuration data from a file.
        File is read from the path defined by the RTSPATIAL_CONFIG_PATH environment variable.
        Filename = app_name.ini.
        The configuratinon is read and stored in an ordered dictionary self.config.
        When data is read with the same key (i.e. APP_DATABASE) newer configurations replace older.
        """
        filename = self.config_path + '/' + self.app_name + '.ini'
        if os.path.isfile(filename):
            if len(self.config) == 0:
                self.config = dotenv_values(filename)
            else:
                app_config = dotenv_values(filename)
                for key, value in app_config.items():
                    self.config[key] = value
        else:
            print('Configuration file ' + filename + ' does not exist.')
            
    def loadDataExchangeConfig(self):
    #------------------------------------------------------------------------------
        """
        """
        filename = self.config_path + '/' + self.app_name + '_dex.json'
        if os.path.isfile(filename):
            with open(filename) as f:
                self.dex_json += json.load(f)
                # print('DEBUG: JSON data exchange setup loaded from ' + filename + '.')
        else:
            print('Data exchange JSON file ' + filename + ' does not exist.')
            
    def getItem(self, key):
    #------------------------------------------------------------------------------
        """
        Get a configuration item for a specified key.
        Key names are typically defined by a constant.
        Returns None if the key does not exist.
        """
        if key in self.config:
            return self.config[key]
        else:
            print('A configuration item for key ' + key + ' does not exist.')
        return None

    def show(self):
    #------------------------------------------------------------------------------
        """
        Simple tabular report to show the configuration parameters.
        """
        print('%r item(s) in self.config' % len(self.config))
        print('Key | Value')
        for key, value in self.config.items():
            print('%s | %r' % (key, value))
        
        
        
        