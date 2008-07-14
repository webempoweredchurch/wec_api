<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// get extension configuration
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wecapi']);

t3lib_extMgm::addStaticFile($_EXTKEY, 'static/rss2/', 'WEC RSS 2.0 Feed' );
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/itunes/', 'WEC iTunes Compatible Feed' );

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_func',
		'tx_wecapi_importwizard',
		t3lib_extMgm::extPath($_EXTKEY).'func_wizards/class.tx_wecapi_importwizard.php',
		'LLL:EXT:wec_api/locallang.xml:wiz_t3dimport',
		'wiz'
	);
}

?>