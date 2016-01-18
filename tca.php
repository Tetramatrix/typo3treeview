<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_chtreeview_example"] = Array (
	"ctrl" => $TCA["tx_chtreeview_example"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,title,parent_uid"
	),
	"feInterface" => $TCA["tx_chtreeview_example"]["feInterface"],
	"columns" => Array (
		'sys_language_uid' => Array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_chtrip_hotel',
				'foreign_table_where' => 'AND tx_chtrip_hotel.pid=###CURRENT_PID### AND tx_chtrip_hotel.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"title" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ch_treeview/locallang_db.xml:tx_chtreeview_example.title",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "required",
			)
		),
		"parent_uid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ch_treeview/locallang_db.xml:tx_chtreeview_example.parent_uid",		
				'config' => Array (
				'type' => 'select',
				'form_type' => 'user',
				'userFunc' => 'tx_ch_treeview->displayCategoryTree',
				'treeView' => 1,
				'treeName' => 'txchtreeviewexample',
                		'treeMaxDepth' => 999,
				'treeParentField' => 'parent_uid', 
				'foreign_table' => 'tx_chtreeview_example',
				'orderBy' => 'title',
				'beta' => false,
				'size' => 10,
				'autoSizeMax' => 50,
				'selectedListStyle' => 'width:250px',
				'minitems' => 0,
				'maxitems' => 2,
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'LLL:EXT:ch_treeview/locallang_db.xml:tx_chtreeview_example.createNewParentCategory',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_chtreeview_example',
							'pid' => '###CURRENT_PID###',
							'setValue' => 'set'
						),
						'script' => 'wizard_add.php',
					),
					'list' => Array(
						'type' => 'script',
						'title' => 'LLL:EXT:ch_treeview/locallang_db.xml:tx_treeview_example.listCategories',
						'icon' => 'list.gif',
						'params' => Array(
							'table'=>'tx_chtreeview_example',
							'pid' => '###CURRENT_PID###',
						),
						'script' => 'wizard_list.php',
					),
				),
            ),
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, title;;;;2-2-2, parent_uid;;;;3-3-3")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);
?>
