<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// class for displaying the category tree in BE forms.
include_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_ch_treeview.php');

$TCA["tx_chtreeview_example"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:ch_treeview/locallang_db.xml:tx_chtreeview_example",		
		"label" => "title",	
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"languageField" => "sys_language_uid",	
		"transOrigPointerField" => "l18n_parent",	
		"transOrigDiffSourceField" => "l18n_diffsource",
		"default_sortby" => "ORDER BY crdate",	
		"delete" => "deleted",
        	"treeParentField" => "parent_uid", 
		"treeLabelField" => "Custom Treelabel defined in Treetable",
		"enablecolumns" => Array (		
			"disabled" => "hidden",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_chtreeview_example.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, title, parent_uid",
	)
);

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

t3lib_extMgm::addPlugin(Array('LLL:EXT:ch_treeview/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:ch_treeview/flexform_ds_pi1.xml');

if (TYPO3_MODE=="BE")
    $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_chtreeview_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_chtreeview_pi1_wizicon.php';
?>