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
        self.debug = debug
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
            if debug: print('Environment varible ' + algaeConfig.KEY_RTSPATIAL_LOCAL_CONFIG_PATH + ' is not setup.')
            
        #
        # ----- basic config for algae applications
        #       this is to support "boostrapping" an app so it can find it's include files
        #       do not add this to the loadConfigFiles() method
        #        
        self.loadJSONConfig(os.path.join(self.local_config_path, 'algae_apps.json'), self.apps_json);
        #
        # ----- load detailed configuration files
        #
        if load_detailed_config: self.loadConfigFiles()
        
    def loadConfigFiles(self):
    #------------------------------------------------------------------------------
        """
        Files are typically loaded from a derived class as well so it's broken out here.
        """    
        self.loadINIConfig(os.path.join(self.config_path, self.app_name + '.ini'))
        self.loadINIConfig(os.path.join(self.local_config_path, self.app_name + '.ini'))
        self.loadJSONConfig(os.path.join(self.config_path, self.app_name + '_dex.json'), self.dex_json)
        self.loadJSONConfig(os.path.join(self.config_path, self.app_name + '_wm.json'), self.wm_json)
        
    def mergeINIConfig(self, config):
    #------------------------------------------------------------------------------
        for key, value in config.items():
            if hasattr(self, key):
                if self.debug:
                    print('OK: Replacing attribute ' + key + ' = ' + str(getattr(self, key)) + ' with ' + str(value))
                setattr(self, key, value)
            else:
                setattr(self, key, value)
                if self.debug:
                    print('OK: Adding attribute ' + key + ' with value = ' + str(value))
            
    def loadINIConfig(self, filename):
    #------------------------------------------------------------------------------
        """
        Load configuration data from a file.
        File is read from the path defined by the RTSPATIAL_CONFIG_PATH environment variable.
        Filename = app_name.ini.
        The configuratinon is read and stored in an associative array $this->config.
        When data is read with the same key (i.e. APP_DATABASE) newer configurations replace older.
        """
        if os.path.isfile(filename):
            if self.debug: print('OK: Parsing ' + filename)
            ini_config = dotenv_values(filename)
            if self.debug: print('OK: ' + str(len(ini_config)) + ' INI config items(s) read from ' + filename)
            self.mergeINIConfig(ini_config)
        else:
            if self.debug: print('WARNING: Configuration file ' + filename + ' does not exist.')
            
    def loadJSONConfig(self, filename, var):
    #------------------------------------------------------------------------------
        """
        Load configuration settings from a JSON file.
        """
        if os.path.isfile(filename):
            with open(filename) as f:
                json_data = json.load(f)
                var += json_data
                if self.debug: print('OK: ' + str(len(json_data)) + ' JSON config items(s) read from ' + filename)
        else:
            print('JSON config file ' + filename + ' does not exist.')
            
    def getAppConfigParameter(self, app_name, parameter_name):
    #------------------------------------------------------------------------------
        name_tag = 'name'
        for app in self.apps_json:
            if app.get(name_tag, None) == app_name:
                if app.get(parameter_name, None) != None:
                    return app.get(parameter_name, None)
        print('ERROR: Unable to get configuration parameter ' + parameter_name + ' for app ' + app_name + '.')
        return None

            

            

        
        
        
        