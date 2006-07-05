<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// get extension configuration
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['wecapi']);

t3lib_extMgm::addStaticFile($_EXTKEY, 'static/rss2/', 'WEC RSS 2.0 Feed' );
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/itunes/', 'WEC iTunes Compatible Feed' );


?>