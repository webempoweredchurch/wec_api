<?php
/***************************************************************
* Copyright notice
*
* (c) 2005-2008 Christian Technology Ministries International Inc.
* All rights reserved
*
* This file is part of the Web-Empowered Church (WEC)
* (http://WebEmpoweredChurch.org) ministry of Christian Technology Ministries 
* International (http://CTMIinc.org). The WEC is developing TYPO3-based
* (http://typo3.org) free software for churches around the world. Our desire
* is to use the Internet to help offer new life through Jesus Christ. Please
* see http://WebEmpoweredChurch.org/Jesus.
*
* You can redistribute this file and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation;
* either version 2 of the License, or (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This file is distributed in the hope that it will be useful for ministry,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the file!
***************************************************************/

define('WEC_API_CLEAR', 0);
define('WEC_API_DUPLICATE', 1);
define('WEC_API_FOLDER_ERROR', 2);


require_once(PATH_t3lib.'class.t3lib_extobjbase.php');

class tx_wecapi_importwizard extends t3lib_extobjbase {
	
	function main()	{
		$t3dImportSettings = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wec_api']['t3dImport'];
		$content = array();
		
		$gpVars = t3lib_div::_GP('tx_wecapi_importwizard');
		if($importedKey = $gpVars['t3dImport']) {
			$this->getT3DData($t3dImportSettings[$importedKey]['path'], true);
			$this->saveImportIndicator($this->pObj->id, $importedKey);
			$content[] = '<div style="padding: 8px; background-color: #FFFF99">Import Successful!</div>';
		}
		
		$imports = array(
			WEC_API_CLEAR => array(),
			WEC_API_DUPLICATE => array(),
			WEC_API_FOLDER_ERROR => array()
		);
		
		if(is_array($t3dImportSettings)) {
			foreach($t3dImportSettings as $key => $settings) {

				if($data = $this->getT3DData($settings['path'])) {
					
					if(!$this->isImportAllowedOnPage($settings['allowOnStandardPages'])) {
						$imports[WEC_API_FOLDER_ERROR][$key] = $settings;
					} else if($this->hasPriorImportOnPage($this->pObj->id, $key)) {
						$imports[WEC_API_DUPLICATE][$key] = $settings;
					} else {
						$imports[WEC_API_CLEAR][$key] = $settings;
					} 
					

				}
			}

			foreach( $imports as $mode => $import ) {
				$importsForThisMode = array();
				foreach( $import as $key => $settings ) {
					$importsForThisMode[] = $this->renderT3D($key, $settings, $data);
				}
				$content[] = $this->renderMode($mode, $importsForThisMode);

			}

		} else {
			$content[] = '<p>No extension data is available for import.</p>';
		}
		
		return implode(chr(10), $content);
	}
	
	function getT3DData($filename, $doImport = false) {
		$absFilename = t3lib_div::getFileAbsFileName($filename);
		$data = null;
		
		if(@is_file($absFilename)) {
			require_once(t3lib_extMgm::extPath('impexp').'class.tx_impexp.php');
			$import = t3lib_div::makeInstance('tx_impexp');
			$import->init(0,'import');
			$import->enableLogging = TRUE;

			if ($import->loadFile($absFilename, $doImport)) {
				$data = $import->dat;
				
				if($doImport) {
					$import->importData($this->pObj->id);
				}
			}
		}
		
		return $data;
	}
	
	function renderMode($mode, $imports) {
		if(empty($imports)) return null;
		
		$import = implode(chr(10), $imports);
		
		$content = array();
		switch ($mode) {
			case WEC_API_FOLDER_ERROR:
				$content[] = '<h2 style="border-bottom: dotted 1px black;">Page Error</h2>';
				$content[] = '<p style="margin-bottom: 10px;">The data cannot be imported to normal pages, it needs to go into a SysFolder</p>';
				$content[] = $import;
				$content[] = '<div style="margin-bottom: 20px;"></div>';
				break;

			case WEC_API_DUPLICATE:
				$content[] = '<h2 style="border-bottom: dotted 1px black;">Duplication</h2>';
				$content[] = '<p style="margin-bottom: 10px;">The data has already been imported to this page before, but you can import it again if you like.</p>';
				$content[] = $import;
				$content[] = '<div style="margin-bottom: 20px;"></div>';
				break;

			case WEC_API_CLEAR:
			default:
				$content[] = '<h2 style="border-bottom: dotted 1px black;">Available Imports</h2>';
				$content[] = '<p style="margin-bottom: 10px;">Select "Import Data" to import the data into this page.</p>';
				$content[] = $import;
				$content[] = '<div style="margin-bottom: 20px;"></div>';
				break;
		}
		return implode(chr(10), $content);
	}
	
	
	function renderT3D($key, $settings, $data) {
		$content = array();
		
		if($data['header']['meta']['title']) {
			$content[] = '<h4>' . $data['header']['meta']['title'] . '</h4>';
		} else {
			$content[] = '<h4>' . $key . '</h4>';
		}
		$content[] = '<p>' . $data['header']['meta']['description'] . '</p>';
		
		$url = 'index.php?id=' . $this->pObj->id . '&tx_wecapi_importwizard[t3dImport]=' . $key;
		$content[] = '<p><a style="text-decoration: underline" href="' . $url . '">Import Data</a></p>';

		return implode(chr(10), $content);
	}
	
	function isImportAllowedOnPage($allowNormal = true) {
		$page = t3lib_BEfunc::getRecord('pages', $this->pObj->id, 'doktype');
		
		switch($page['doktype']) {
			case '1':
			case '2':
			case '5':
				if($allowNormal) {
					$importAllowed = true;
				} else {
					$importAllwoed = false;
				}
				break;
			case '254':
				$importAllowed = true;
				break;
			default:
				$importAllowed = false;
				break;
		}
		
		return $importAllowed;
	}
	
	function saveImportIndicator($pid, $key) {
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_api']['t3dImport'][$key]);
		if($extConf['pid']) {
			$extConf['pid'] .= ','.$pid;
		} else {
			$extConf['pid'] = $pid;
		}

		// Instance of install tool
		$instObj = t3lib_div::makeInstance('t3lib_install');
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = 'WEC API T3D importer';

		// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\'wec_api\'][\'t3dImport\'][\''.$key.'\']', serialize($extConf));
		$instObj->writeToLocalconf_control($lines);
		
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_api']['t3dImport'][$key] = serialize($extConf);
	}
	
	function hasPriorImportOnPage($pid, $key) {
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wec_api']['t3dImport'][$key]);

		if(t3lib_div::inList($extConf['pid'], $pid)) {
			$hasPrior = true;
		} else {
			$hasPrior = false;
		}
		
		return $hasPrior;
	}
}
?>