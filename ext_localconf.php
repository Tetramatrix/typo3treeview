<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_chtreeview_pi1 = < plugin.tx_chtreeview_pi1.CSS_editor
',43);

  // patch for loaddbgroup
//$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_loaddbgroup.php']=t3lib_extMgm::extPath('ch_trip').'patch/class.ux_t3lib_loadDBGroup.php';

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_chtreeview_pi1.php','_pi1','list_type',1);
?>