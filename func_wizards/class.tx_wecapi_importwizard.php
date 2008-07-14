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


require_once(PATH_t3lib.'class.t3lib_extobjbase.php');

class tx_wecapi_importwizard extends t3lib_extobjbase {
	
	function main()	{
		$t3dFiles = $GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['wec_api']['t3dImport'];

		$content = array();
		
		$gpVars = t3lib_div::_GP('tx_wecapi_importwizard');
		if($importedKey = $gpVars['t3dImport']) {
			$this->getT3DData($GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['wec_api']['t3dImport'][$importedKey], true);
			$content[] = '<div style="padding: 8px; background-color: #FFFF99">Import Successfull!</div>';
		}
		
		if(is_array($t3dFiles)) {
			$content[] = '<ul>';
			foreach($t3dFiles as $key => $filename) {
				if($data = $this->getT3DData($filename)) {

					$content[] = '<li>';
					$content[] = $this->renderT3D($key, $data);
					$content[] = '</li>';
				}
			}

			$content[] = '</ul>';
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
	
	function renderT3D($key, $data) {
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
}
?>