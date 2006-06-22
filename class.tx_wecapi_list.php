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
 * class 'WEC Lists' for the 'WEC API' library
 *
 * @author	Web Empowered Church Team, Foundation For Evangelism <wec_api@webempoweredchurch.org>
 */

require_once(PATH_t3lib.'class.t3lib_div.php');

class tx_wecapi_list extends tslib_cObj{
	

	/**
	 * Used to initialize this class
	 *
	 * @param	array		$conf: TypoScript setup configuration for this object
	 *
	 */
	function init($conf) {
		$this->local_cObj = t3lib_div::makeInstance('tslib_cObj'); // Local cObj.
		$this->conf = $conf;
	}
	
	/**
	 *	The entry point into the class. Creates an instance of this class, and returns the XML content given a dataArray
	 *
	 *	@param	reference	$cObj: An array of associative arrays, composing each of the data elements which will become <items> in our XML output
	 *	@param	mixed		$dataArray: Must be either a resource for a database result set, or an array of associative arrays. These compose each of the data elements which will replace the ITEM markers from our template
	 *	@param	string		$tableName: An array of associative arrays, composing each of the data elements which will become <items> in our XML output
	 *
	 *	@return	string	Content from a processed template, with all markers and subparts substituted
	 */
	function getContent( &$cObj, $dataArray, $tableName ) {

	
		$tx_wecapi_list = t3lib_div::makeInstance('tx_wecapi_list'); 
		$tx_wecapi_list->init($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wecapi_list.']);
		$tx_wecapi_list->cObj = $cObj;

		$content = '';
		$template = $this->getTemplate();

			//	Page mapping array
		$pageArray = $this->conf['pageArray.'];
		
			//	Data array representing a row of data
		$dataArray = array();

			// Hook for pre-processing the page marker array
		if( is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessPageArray'] ) ) {
			
			foreach( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessPageArray'] as $classRef ) {
				
				$processObject = &t3lib_div::getUserObj( $classRef, 'tx_' );
				
				$processObject->preProcessPageArray( &$this, &$dataArray, &$pageArray );
			}
			
		}
		
			// Process all the page array markers
		$template = $this->getRowContent( $tableName, $dataArray, $template, $pageArray );

		$itemArray = $this->conf['itemArray.'];

			// Hook for pre-processing the item marker array
		if( is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessItemArray'] ) ) {
			
			foreach( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessItemArray'] as $classRef ) {
				
				$processObject = &t3lib_div::getUserObj( $classRef, 'tx_' );
				
				$processObject->preProcessItemArray( &$this, &$itemArray );
			}
			
		}

			// Retrieve the ###ITEM### subpart
		$itemTemplate = $this->local_cObj->getSubpart( $template, getMarkerTagName('item') );


		if( gettype( $dataArray ) == 'array' ) {
			
			foreach( $dataArray as $offset => $row ) {
			
				$content .= $this->getRowContent( $tableName, $row, $itemTemplate, $itemArray );
			}

		}
		else if( gettype( $dataArray ) == 'resource' ) {
			
				//	Iterate every data item, aggregate the content
			while( $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) ) {
				
				$content .= $this->getRowContent( $tableName, $row, $itemTemplate, $mappingArray );
	
			}
		}
		else {
			
				return '
				WEC XML Error!<br>
				The parameter sent to tx_wecapi_list::getXMLContent was not of type \'resource\' or \'array\'<br>
				The parameter type was: ' .  $gettype( $dataArray );
		}
		
			//	Return template content fully populated with data
		return $this->local_cObj->substituteSubpart( $template, getMarkerTagName('item'), $content );
		

	}

	/**
	 *	Processes a database resource, returning a fully populated template with all markers and subparts substituted.	
	 *
	 * @param	resource	$res: A resource to a database result
	 * @param	string		$tableName: The tablename that the result set was selected from

	 * @return	string		Template content fully populated with data
	 */
	function getContentFromResource( $res, $tableName ) {

		$content = '';
		$template = $this->getTemplate();
		$itemTemplate = $this->local_cObj->getSubpart( $template, getMarkerTagName('item') );

			//	Substitute all the channel information
		$template = $this->local_cObj->substituteMarkerArrayCached( $template, $this->conf['pageArray.'], array(), array() );
		
			//	Channel mapping array
		$mappingArray = $this->conf['pageArray.'];
		$mappingArray['channel_link'] = $this->cObj->getUrlToList( true, $tableName );

			// Hook for post-processing the page marker array
		if( is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['postProcessPageArray'] ) ) {
			
			foreach( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['postProcessPageArray'] as $classRef ) {
				
				$processObject = &t3lib_div::getUserObj( $classRef, 'tx_' );
				
				$processObject->postProcessPageArray( $this, $mappingArray );
			}
			
		}

		$template = $this->getRowContent( $tableName, $mappingArray, $template, $mappingArray );

		$mappingArray = $this->conf['itemArray.'];
		
			//	Iterate every data item, aggregate the content
		while( $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) ) {
			
				//	Make a callback to get the URL to this record. Parent object must support getUrlToSingle function call!
			$row['item_link'] = $this->cObj->getUrlToSingle( true, $tableName, $row['uid'] );
			$content .= $this->getRowContent( $tableName, $row, $itemTemplate, $mappingArray );

		}

			//	Return template content fully populated with data
		return $this->local_cObj->substituteSubpart( $template, getMarkerTagName('item'), $content );
		
	}

	/**
	 *	Processes an array of associative arrays, returning a fully populated template with all markers and subparts substituted. 
	 *
	 * @param	array		$dataArray: An array of associative arrays, composing each of the data elements which will replace the ITEM subpart in our template
	 * @param	string		$tableName: The tablename that the result set was selected from
	 * 
	 * @return	string		Template content fully populated with data
	 */
	function getContentFromArray( $dataArray, $itemTemplate, $tableName ) {

		foreach( $dataArray as $offset => $row ) {
		
			$content .= $this->getRowContent( $tableName, $row, $itemTemplate, $itemArray );
		}

			//	Return XML content fully populated with data
		return $this->local_cObj->substituteSubpart( $template, getMarkerTagName('item'), $content );

	}
	
	/**
	 *	Processes an XML structure, returning a fully populated template with all markers and subparts substituted. 
	 *
	 * @param	string		$xml: An array of associative arrays, composing each of the data elements which will replace the ITEM subpart in our template
	 * @param	string		$tableName: The tablename that the result set was selected from
	 * 
	 * @return	string		Template content fully populated with data
	 */
	function getContentfromXMLString( $xml, $tableName ) {

		/*	Future functionality to parse if passed in as XML instead of array
					//	If mappingArray is not an array, see if it is xml by converting it
				if( ! is_array( $mappingArray) )
					$mappingArray = t3lib_div::xml2array( $mappingArray );
		
					//	If mappingArray is still not an array, assume xml2array returned a string error, to bubble this up
				if( ! is_array( $mappingArray ) ) return $mappingArray;
		
		*/		
	}

	/**
	 * This function processes one row of data and substitutes markers in the template with the data
	 *
	 * @param	string		$tableName: The tablename that the result set was selected from 
	 * @param	array		$row: An associative array with lowercase TYPO tag names as keys, that maps data to markers
	 * @param	string		$rowTemplate: A marker-based template defining the layout of the data
	 *
	 * @return	string		Returns the content of the data array $row, formatted by the template $rowTemplate
	 */
	function getRowContent($tableName, $row, $rowTemplate, $markerArray)
	{

			// Hook for pre-processing the row
		if( is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessContentRow'] ) ) {
			
			foreach( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessContentRow'] as $classRef ) {
				
				$processObject = &t3lib_div::getUserObj( $classRef, 'tx_' );
				
				$processObject->preProcessContentRow( &$this, &$row );
			}
			
		}


		//	use the local_cObj to render each record row
		$this->local_cObj->start( $row, $tableName );
		foreach( $markerArray as $marker => $value ) {
				
				//	Only process if marker is not a typotag already
			if( ! strrpos( $marker, '###') ) {
				
				//	Set the key field for the CASE cObject, rendering the correct marker
				$this->conf['tag_rendering.']['key'] = $marker;	
	
				//	Call cObjGetSingle to render our content, assigning it back to the markerArray
				$markerArray[getMarkerTagName( $marker )] = $this->local_cObj->cObjGetSingle( $this->conf['tag_rendering'], $this->conf['tag_rendering.'] );
			}
		}

		//	Render links for wrapped subparts
		if( $wrappedSubpartArray ) {
			foreach( $wrappedSubpartArray as $marker ) {
				$this->conf['tag_rendering.']['key'] = $marker;
				$wrappedSubpartArray[getMarkerTagName( $marker )] = $this->local_cObj->typolinkWrap($this->conf['tag_rendering'][$marker.'.']['typolink.'] );
			}
		}
//debug( $this->conf['tag_rendering.']  );		
		return $this->local_cObj->substituteMarkerArrayCached($rowTemplate, $markerArray, array(), $wrappedSubpartArray );
	}
	
	/**
	 * Retrieves the template content associated with this class
	 *
	 * @return	string		The content of the template content associated with this class
	 */
	function getTemplate() {

			//	Read in the template content. Must have 'xmlFormat' specified in setup field, which determines the template that is read in.
		return $this->local_cObj->getSubpart( $this->local_cObj->fileResource( $this->conf['templateFile'] ), getMarkerTagName('template_'.$this->conf['templateName']) );
		
	}

}	//	class wec_xml end


/**
 *	getMarkerTagName	A helper function that returns a marker wrapped with # signs, and capitalized
 *
 *	@param	string	$name	A TYPOTag marker that may not be formatted properly
 *	@return	string	A properly formatted marker tag
 */
function getMarkerTagName( $name ) {
		//	Fix subpart name if TYPO tags were not inserted
	return strrpos( $name, '###') ? strtoupper( $name ) :  '###'.strtoupper( $name ).'###';

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_api/class.tx_wecapi_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_api/class.tx_wecapi_list.php']);
}

?>