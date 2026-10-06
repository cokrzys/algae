"""

  algae | Core python items.

  @author    Brian Krzys (brian.krzys@rtspatial.com)
  @copyright (c) 2026 RTSpatial Ltd.
  @license   SPDX-License-Identifier: MIT
  @link      https://github.com/cokrzys/algae
 
"""

import math

class algaeCore():

	@staticmethod
	def interpolate(val, minOrig, maxOrig, minNew, maxNew):
	#-------------------------------------------------------------------------------
		"""
		Simple linear interpolation between two points.
		------------------------------------------------------------------------
		@param float val Value in original units to interpolate into new units.
		@param float minOrig Minimum scale value in original units.
		@param float maxOrig Maximum scale value in original units.
		@param float minNew Minimum scale value in new units corresponding to minOrig.
		@param float maxNew Maximum scale value in new units corresponding to maxOrig.
		@return float value corresponding to val but scaled against the new scale.
		"""
		ret = 0.0
		dataRange = 0.0
		sizeRange = 0.0
		if ((minNew == maxNew) or (val <= minOrig)):
			ret = minNew
		elif val >= maxOrig:
			ret = maxNew
		else:
			dataRange = maxOrig - minOrig
			sizeRange = maxNew - minNew
			if dataRange != 0.0:
				ret = minNew + ((sizeRange / dataRange) * (val - minOrig))
			else:
				ret = minNew
		return ret

	@staticmethod
	def pretty_print(clas, indent=0):
	# ------------------------------------------------------------------------------
		"""
		Pretty print an instance of a class using recursion.
		* From: https://stackoverflow.com/questions/51753937/python-pretty-print-nested-objects
		------------------------------------------------------------------------
		:param clas: Class instance.
		:param indent: Starting indentation 0 default and if not specified.
		:return:
		"""
		print(' ' * indent + type(clas).__name__ + ':')
		indent += 2
		for k, v in clas.__dict__.items():
			if '__dict__' in dir(v):
				algaeCore.pretty_print(v, indent)
			else:
				print(' ' * indent + k + ': ' + str(v))
				
	@staticmethod
	def get_parts_from_rowid(rowid, num_levels=2):
	# ------------------------------------------------------------------------------
		"""
		Split a rowid into manageable parts for use in a hierarchical directory 
		structure or filename.
        * The rowid is left padded with zeros before splitting into parts.
        * For example the rowid 2789 split into two levels is 002 789.
        * https://stackoverflow.com/questions/9475241/split-string-every-nth-character
        ------------------------------------------------------------------------
        @param integer rowid Rowid to split, integer and not zero padded.
        @param integer num_levels Number of three digit levels to split across.
        @return array Array of split parts.
		"""
		level_len = 3
		rs = str(rowid).zfill((num_levels) * level_len)
		return [rs[i:i+level_len] for i in range(0, len(rs), level_len)]
	
	@staticmethod
	def get_concatenated_parts_from_rowid(rowid, num_levels=2, separator='/'):
	# ------------------------------------------------------------------------------
		"""
		Split and concatenate a rowid into a string for use in a hierarchical directory structure or filename.
        * For example the rowid 2789 across two levels would be '002/789'.
        ------------------------------------------------------------------------
        @param integer rowid Rowid to split, integer and not zero padded.
        @param integer num_levels Number of three digit levels to split across.
        @param string separator Separator to add between parts, backspace by default.
        @return string|NULL String typically for use in a directory structure or filename.
		"""
		parts = algaeCore.get_parts_from_rowid(rowid, num_levels)
		if len(parts) >= 1:
			ret = ''
			sep = ''
			for i in range(len(parts)):
				ret += sep + parts[i]
				sep = separator
			return ret
		return ''
	
	@staticmethod
	def get_path_from_rowid(rowid, num_levels=2):
	# ------------------------------------------------------------------------------
		"""
		Get a path for use in a hierarchical directory structure from a rowid.
        * For example the rowid 2789 across two levels would be '002/789'.
        ------------------------------------------------------------------------
	    @param integer rowid Rowid to split, integer and not zero padded.
	    @param integer num_levels Number of three digit levels to split across.
	    @return string|NULL String for use in a directory structure.
		"""
		return algaeCore.get_concatenated_parts_from_rowid(rowid, num_levels, '/')

	@staticmethod
	def get_filename_from_rowid(rowid, num_levels=2):
	# ------------------------------------------------------------------------------
		"""
		"""
		return algaeCore.get_concatenated_parts_from_rowid(rowid, num_levels, '_')
	
	
	