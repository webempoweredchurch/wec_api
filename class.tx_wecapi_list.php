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
 * class 'WEC Lists' for the 'WEC API' library. This class allows us to generate list views for front end plugins. 
 *
 * @author	Web Empowered Church Team, Foundation For Evangelism <wec_api@webempoweredchurch.org>
 * @package TYPO3
 * @subpackage tx_wecapi
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
	*	Not used, as we need to pass in a dataArray
	*
	*/
	function main( $content, $conf ) {
		
		return '';
	}
	
	/**
	 *	The entry point into the class. Creates an instance of this class, and returns the XML content given a dataArray
	 *
	 *	@param	reference	$pObj: The parent object calling this function
	 *	@param	mixed		$dataArray: Must be either a resource for a database result set, or an array of associative arrays. These compose each of the data elements which will replace the ITEM markers from our template
	 *	@param	string		$tableName: Name of table we are rendering records for
	 *
	 *	@return	string	Content from a processed template, with all markers and subparts substituted
	 */
	function getContent( &$pObj, $dataArray, $tableName ) {

	
		$tx_wecapi_list = t3lib_div::makeInstance('tx_wecapi_list'); 
		$tx_wecapi_list->init($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wecapi_list.']);
		$tx_wecapi_list->cObj = $pObj;

		return $tx_wecapi_list->getListContent($dataArray, $tableName);

	}
	
	/**
	 * Processes a given data array and a template, returning the list content
	 *
	 * @param	mixed	$dataArray: See getContent()
	 *	@param	string	$tableName: Name of table we are rendering records for
	 *
	 * @return	string	Return is the list content, populated from dataArray and a template
	 */
	function getListContent($dataArray, $tableName) {
		
		$content = '';
		$template = $this->getTemplate();

			//	Page mapping array
		$pageArray = $this->conf['pageArray.'];
		
			// Hook for pre-processing the page marker array
		if( is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessPageArray'] ) ) {
			
			foreach( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessPageArray'] as $classRef ) {
				
				$processObject = &t3lib_div::getUserObj( $classRef, 'tx_' );
				
				$processObject->preProcessPageArray( &$this, &$dataArray, &$pageArray );
			}
			
		}
	

			// Process all the page array markers
		$template = $this->getRowContent( $tableName, $pageArray, $template, $pageArray );

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
				
				$content .= $this->getRowContent( $tableName, $row, $itemTemplate, $itemArray );
	
			}
		}
		else {
			
				return '
				WEC List Error!<br>
				The parameter sent to tx_wecapi_list::getXMLContent was not of type \'resource\' or \'array\'<br>
				The parameter type was: ' .  $gettype( $dataArray );
		}
		
			//	Return template content fully populated with data
		return $this->local_cObj->substituteSubpart( $template, getMarkerTagName('item'), $content );

	}
	
	/**
	 * This function processes one row of data and substitutes markers in the template with the data
	 *
	 * @param	string		$tableName: The tablename that the result set was selected from 
	 * @param	array		$row: An associative array with lowercase TYPO tag names as keys, that maps data to markers
	 * @param	string		$rowTemplate: A marker-based template defining the layout of the data
	 * @param	string		$markerArray: An associative array of marker tags as keys
	 *
	 * @return	string		Returns the content of the data array $row, formatted by the template $rowTemplate
	 */
	function getRowContent($tableName, $row, $rowTemplate, $markerArray)
	{

			// Hook for pre-processing the row
		if( is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessContentRow'] ) ) {
			
			foreach( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_wecapi_list']['preProcessContentRow'] as $classRef ) {
				
				$processObject = &t3lib_div::getUserObj( $classRef, 'tx_' );
				
				$processObject->preProcessContentRow( &$this, &$row, $tableName );
			}
			
		}


		//	use the local_cObj to render each record row
		$this->local_cObj->start( $row, $tableName );
		
		if( !is_array($markerArray) ) {
			return '
				WEC List Error!<br>
				The $markerArray parameter sent to tx_wecapi_list::getRowContent was not of type \'array\'<br>
				The parameter type was: ' .  $gettype( $dataArray );
			
		}
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
	 * Retrieves the template content associated with the tx_wecapi_list class
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