<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Web Empowered Church Team, Foundation For Evangelism (wec_sermons@webempoweredchurch.org)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'Sermon Repository' for the 'wec_sermons' extension.
 *
 * @author	Web Empowered Church Team, Foundation For Evangelism <wec_sermons@webempoweredchurch.org>
 */
 
require_once(PATH_t3lib.'class.t3lib_div.php');
require_once(PATH_typo3conf.'ext/wec_api/class.tx_wecxml.php');

class tx_wecapi_weclists {

	/**
	 *	Parses a SQL result, returning items wrapped in valid RSS XML
	 *
	 *	This function is used to generate RSS files, based on a list of records from an SQL result.
	 *
	 * @param	array		$$mappingArray: ...
	 * @param	pointer		$res: Result pointer to a SQL result which can be traversed
	 * @return	string		...
	 */
	function getRssFeed( $mappingArray, $res ) {
			
			//	Simply call from wec_xml, where the actual function resides
		return wec_xml::getRssFeed( $mappingArray, $res );
	}

	/**
	 * @param	[type]		$$mappingArray: ...
	 * @param	[type]		$template: ...
	 * @param	[type]		$res: ...
	 * @return	[type]		...
	 */
	function getTemplateList( $mappingArray, $template, $res ) {
		
			//	If mappingArray is not an array, see if it is xml by converting it
		if( ! is_array( $mappingArray) ) 
			$mappingArray = t3lib_div::xml2array( $mappingArray );
		
			//	If mappingArray is still not an array, assume xml2array returned a string error, to bubble this up	
		if( ! is_array( $mappingArray ) ) return $mappingArray;
		
		$markerArray = array();
		$wrappedSubparts = array();

		foreach( $mappingArray as $key => $value ) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$markerArray[$key] = $row[$value];			
		}
		
		$local_cObj = t3lib_div::makeInstance('tslib_cObj'); // Local cObj.
		
			//	Return template populated with data
		return $local_cObj->substituteMarkerArrayCached( $template, $markerArray, array(), $wrappedSubparts );


	}
}	//	class wec_lists end

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_api/class.tx_wecapi_weclists.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_api/class.tx_wecapi_weclists.php']);
}

?>