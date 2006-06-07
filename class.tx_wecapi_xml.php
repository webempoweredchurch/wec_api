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

class tx_wecapi_xml {

	function init($conf) {
		$this->local_cObj = t3lib_div::makeInstance('tslib_cObj'); // Local cObj.
		$this->conf = $conf;
	}
	
	function main($content, $conf) {

		$this->init($conf);

		$markerArray = $this->conf['markerArray.'];
				
		$dataArray = array(
			'description' => 'This is the description we\'re going to use',
			'title' => 'Channel Title',
			'link' => 'application/pluginLink/',
			'category' => 'Channel Category',
		);
		
		$GLOBALS['TSFE']->register['domain'] = "http://theslezak.com/test/";	
		
		$template = $this->cObj->getSubpart( $this->cObj->fileResource( $this->conf['templateFile'] ), getMarkerTagName('template_'.$this->conf['version']) );

		return $this->getRowContent( 'tx_wecsermons_sermons', $dataArray, $markerArray, array(), $template );

		
	}

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

	}

	/**
	 * @param	mixed		$mappingArray: Can be either an XML string, or array. Is the 
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
	
	function getRowContent($tableName, $row, $markerArray, $wrappedSubpartArray, $rowTemplate )
	{

		$this->local_cObj->start( $row, $tableName );
		foreach( $markerArray as $marker => $value ) {
	
			$this->conf['fields.']['key'] = $marker;

			$markerArray[getMarkerTagName( $marker )] = $this->local_cObj->cObjGetSingle( $this->conf['tag_rendering'], $this->conf['tag_rendering.'] );

		}
		
		foreach( $wrappedSubpartArray as $marker ) {
			$this->conf['fields.']['key'] = $marker;
			$wrappedSubpartArray[getMarkerTagName( $marker )] = $this->local_cObj->typolinkWrap($this->conf['tag_rendering'][$marker.'.']['typolink.'] );
		}
		
		return $this->local_cObj->substituteMarkerArrayCached($rowTemplate, $markerArray, array(), $wrappedSubpartArray );
	}

}	//	class wec_xml end

function getMarkerTagName( $name ) {
		//	Fix subpart name if TYPO tags were not inserted
	return strrpos( $name, '###') ? strtoupper( $name ) :  '###'.strtoupper( $name ).'###';

}	

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_api/class.tx_wecapi_xml.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_api/class.tx_wecapi_xml.php']);
}

?>