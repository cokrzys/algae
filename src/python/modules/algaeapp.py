"""

  algae | Application base class.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
"""

import sys
import io
import os
from datetime import datetime

from algaeconfig import algaeConfig

class algaeApp():
    
    ERROR  = 'ERROR: '
    
    def __init__(self, load_detailed_config = True, debug = False):
    #------------------------------------------------------------------------------
        """
        Constructor.
        """
        self.config = algaeConfig(load_detailed_config, debug)
        #
        # ----- this is redone completely in a derived app so commented out to avoid duplication
        #       potentially uncomment to debug just the framework
        #
        # self.config = algaeConfig()
        #
        # ----- standard color breaks for scores
        #
        self.score_colors = [[0.1429, '#d73027'],
                             [0.2857, '#fc8d59'],
                             [0.4286, '#fee08b'],
                             [0.5714, '#ffffbf'],
                             [0.7143, '#d9ef8b'],
                             [0.8571, '#91cf60'],
                             [1.5000, '#1a9850']
                             ]
        
    @staticmethod
    def show_search_path():
    #------------------------------------------------------------------------------
        """
        Show the search path.
        """
        print(u"\nSearch path for modules:")
        for path in sys.path:
            print(path)
            
    @staticmethod
    def error_message(str):
    #------------------------------------------------------------------------------
        """
        Print an error message.
        """
        print('ERROR: ' + str)
    
    @staticmethod
    def print_to_string(*args, **kwargs):
    #------------------------------------------------------------------------------
        """
        Print to a string.
        From: https://stackoverflow.com/questions/39823303/python3-print-to-string
        """
        output = io.StringIO()
        print(*args, file=output, **kwargs, end='')
        contents = output.getvalue()
        output.close()
        return contents
            
    def have_db_connection_parms(self):
    #------------------------------------------------------------------------------
        """
        Check if the database connection parameters are set.
        """
        if self.settings.master_database != None and self.settings.app_database != None and self.settings.database_port != None \
            and self.settings.database_username != None and self.settings.database_password != None:
            return True
        algaeApp.error_message('Database connection parameters not set.')
        self.settings.show()
        return False
    
    @staticmethod
    def get_year_based_filename(base_folder, suffix, date = None):
    #------------------------------------------------------------------------------
        """
        Gets a standardized filename based on a date and year based folder.
        If the year folder does not exist it will be created.
        The base folder must exist and must not contain a trailing backslash.
        Optional date parameter is a datetime object.  When not specified the current date will be used.
        
        Example:
        base_folder = '/dat/stocks/exchangerate-api'
        suffix = '_exchangerate-api_usd.json'
        return = /dat/stocks/exchangerate-api/2023/20230304_exchangerate-api_usd.json
        """
        if date == None: date = datetime.now()
        # output_path = base_folder + '/' + datetime.now().strftime("%Y")
        output_path = base_folder + '/' + date.strftime("%Y")
        if not os.path.exists(output_path):
            os.makedirs(output_path)
        # output_file = base_folder + '/' + datetime.now().strftime("%Y") + '/' + datetime.now().strftime("%Y%m%d") + suffix
        output_file = base_folder + '/' + date.strftime("%Y") + '/' + date.strftime("%Y%m%d") + suffix
        return output_file

    @staticmethod
    def create_instance(module_name, class_name):
    # ------------------------------------------------------------------------------
        """
        Create an instance of a class from a module and class name.
        https://www.sqlpey.com/python/top-4-ways-to-dynamically-create-class-instances-in-python/
        """
        module = __import__(module_name, fromlist=[class_name])
        instance = getattr(module, class_name)
        return instance()
    
        