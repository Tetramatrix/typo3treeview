<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2012 Chi Hoang (info@chihoang.de)
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
require_once (PATH_t3lib . 'class.t3lib_treeview.php');

class tx_ch_tceFunc_selectTreeView extends t3lib_treeview
{
	var $TCEforms_Xitem;
	var $TCEforms_itemFormElName = '';
	var $TCEforms_nonSelectableItemsArray = array ();
	var $treeParentLabel='title';
	var $ExpandFirst = 1;	
	/**
	 * wraps the record titles in the tree with links or not depending on if they are in the TCEforms_nonSelectableItemsArray.
	 *
	 * @param	string		$title: the title
	 * @param	array		$v: an array with uid and title of the current item.
	 * @return	string		the wrapped title
	 */
	function wrapTitle($title, $v)
	{
		return ' style="cursor:pointer;" onclick="setFormValueFromBrowseWin(\'' . $this->TCEforms_itemFormElName . '\',' . $v ['uid'] . ',\'' . $title . '\');return false;"';
	}
	
	function getTitleStr($row,$titleLen=30)
	{
		$title = (!strcmp(trim($row[$this->treeParentLabel]),'')) ? '<em>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</em>' : htmlspecialchars(t3lib_div::fixed_lgd_cs($row[$this->treeParentLabel],$titleLen));
		return $title;
    }
 
	function getTitleAttrib($row)
	{
		return htmlspecialchars($row[$this->treeParentLabel]);
	}
	 
	/**
	 * Generate the plus/minus icon for the browsable tree.
	 *
	 * @param	array		record for the entry
	 * @param	integer		The current entry number
	 * @param	integer		The total number of entries. If equal to $a, a "bottom" element is returned.
	 * @param	integer		The number of sub-elements to the current element.
	 * @param	boolean		The element was expanded to render subelements if this flag is set.
	 * @return	string		Image tag with the plus/minus icon.
	 * @access private
	 * @see t3lib_pageTree::PMicon()
	 */
	function PMicon($row,$a,$c,$nextCount,$exp)
	{
		$PM = $nextCount ? ($exp?'minus':'plus') : 'join';
		$BTM = ($a==$c)?'bottom':'';
		$icon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.$PM.$BTM.'.gif','width="18" height="16"').' alt="" />';

		if ($nextCount)
		{
			$cmd=$this->bank.'_'.($exp?'0_':'1_').$row['uid'].'_'.$this->treeName."_".$this->paUid;
			$bMark=($this->bank.'_'.$row['uid']);
			$icon = $this->PM_ATagWrap($icon,$cmd,$bMark);
		}
		return $icon;
	}
	
	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @param	boolean		If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PM_ATagWrap($icon, $cmd, $bMark = '')
	{	
		if ($this->useXajax)
		{
			list(,$expand) = explode ( '_', $cmd );
			if ($expand == '1')
			{
				$title = 'Expand';
			} else
			{
				$title = 'Collapse';
			}
			return '<span onclick="if(event.stopPropagation){event.stopPropagation;}event.cancelBubble=true;tx_chtreeview_sendResponse(\'' . $cmd . '\');return false;" style="cursor:pointer;" title="' . $title . '">' . $icon . '</span>';
		}
		
			// Probably obsolete
		if ($this->thisScript)
		{
			if ($bMark)
			{
				$name = ' name="' . $bMark . '"';
			}
			return '<a href="javascript:void(0);" onClick="set' . $this->treeName . 'PM(\'' . $cmd . '\');TBE_EDITOR_submitForm();"' .
					$name . '>' . $icon . '</a>';
		} else
		{
			return $icon;
		}
	}
	
	function initializePositionSaving( )
	{	
		$backup = $this->treeName;
		$this->treeName = $tree = preg_replace ( '/_.{4}_.{1,3}/', '', $this->treeName );
		
			// Get stored tree structure:			 
		$this->stored = unserialize ( $this->BE_USER->uc ['browseTrees'] [$this->treeName] );	
		$this->treeName = $backup;
		
		// PM action
		// (If an plus/minus icon has been clicked, the PM GET var is sent and we must update the stored positions in the tree):
		$PM = explode ( '_', t3lib_div::_GET ( 'PM' ) ); // 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
		
		if (count ( $PM ) >= 5 && $PM [3] == $tree && $PM [0] == 'X' && $PM [1])
		{	
				// expand root
			$this->expandFirst = 1;
			$this->TCEforms_Xitem = $PM [7];
			
				// close all nodes
			$this->stored [0] = array ();
			$rootline = $this->getRootline ( $PM [2],
											preg_replace ( '/\+/', '_', $PM [5] ),
											preg_replace ( '/\+/', '_', $PM [6] )
											);
			
			foreach ( $rootline as $k => $v )
			{
				$this->stored [0] [$v ['uid']] = 1;
			}
			
			if (is_array($this->stored [$PM [0]] [$PM [2]]) )
			{
				$this->stored [0] [$PM [2]] = 1;
			}
			$this->savePosition ( );
			
		} elseif (isset ( $this->MOUNTS [$PM [0]] )	&& $PM [1])
		{
			$this->stored [$PM [0]] [$PM [2]] = 1;
			$this->savePosition ();
		
		} else
		{ // clear
			
			if (isset($this->stored [$PM [0]] [$PM [2]]) )
			{
				unset ( $this->stored [$PM [0]] [$PM [2]] );
			}
			$this->savePosition ( );
		}
	}
	
	/**
	 * Returns array with fields of the pages from here ($uid) and back to the root
	 * NOTICE: This function only takes deleted pages into account! So hidden, starttime and endtime restricted pages are included no matter what.
	 * Further: If any "recycler" page is found (doktype=255) then it will also block for the rootline)
	 * If you want more fields in the rootline records than default such can be added by listing them in $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']
	 *
	 * @param	integer		The page uid for which to seek back to the page tree root.
	 * @param	string		The pid-field.
	 * @param	string		The table.
	 * @return	array		Array with page records from the root line as values. The array is ordered with the outer records first and root record in the bottom. The keys are numeric but in reverse order. So if you traverse/sort the array by the numeric keys order you will get the order from root and out. If an error is found (like eternal looping or invalid mountpoint) it will return an empty array.
	 * @see tslib_fe::getPageAndRootline()
	 */
	function getRootLine($uid, $pid, $table = '')
	{	
			// Initialize:
		$selFields = t3lib_div::uniqueList ( 'uid,pid,title,parent_uid' );
		$this->error_getRootLine = '';
		$this->error_getRootLine_failPid = $loopCheck = 0;
		
		$theRowArray = Array ();
		$uid = intval ( $uid );
		
		while ( $uid != 0 && $loopCheck < 20 )
		{ // Max 20 levels in the page tree.
			$res = $GLOBALS ['TYPO3_DB']->exec_SELECTquery ( $selFields, $table, 'uid=' . intval ( $uid ) . " AND $table.deleted=0 AND $table.hidden=0" );
			if ($row = $GLOBALS ['TYPO3_DB']->sql_fetch_assoc ( $res ))
			{
				if (is_array ( $row ))
				{
					$uid = $row ['parent_uid']; // Next uid
				}
				$theRowArray [] = $row;
			} else
			{
				$this->error_getRootLine = 'Broken rootline';
				$this->error_getRootLine_failPid = $uid;
				return array (); // broken rootline.
			}
			$loopCheck ++;
		}
		
		// Create output array (with reversed order of numeric keys):
		$output = Array ();
		$c = count ( $theRowArray );
		foreach ( $theRowArray as $key => $val )
		{
			$c --;
			$output [$c] = $val;
		}
		return $output;
	}
	
	/**
	 * Saves the content of ->stored (keeps track of expanded positions in the tree)
	 * $this->treeName will be used as key for BE_USER->uc[] to store it in
	 *
	 * @return	void
	 * @access private
	 */
 	function savePosition()
	{
		$backup = $this->treeName;
		$this->treeName = preg_replace ( '/_.{4}_.{1,3}/', '', $this->treeName );
		$this->BE_USER->uc ['browseTrees'] [$this->treeName] = serialize ( $this->stored );
		$this->BE_USER->writeUC ();
		$this->treeName = $backup;
	}
	
	/**
	 * Fetches the data for the tree
	 *
	 * @param	integer		item id for which to select subitems (parent id)
	 * @param	integer		Max depth (recursivity limit)
	 * @param	string		HTML-code prefix for recursive calls.
	 * @param	string		? (internal)
	 * @param	string		CSS class to use for <td> sub-elements
	 * @return	integer		The count of items on the level
	 */
	function getTree($uid, $depth = 999, $depthData = '', $blankLineCode = '', $subCSSclass = '')
	{
			// Buffer for id hierarchy is reset:
		$this->buffer_idH = array();

			// Init vars
		$depth = intval($depth);
		$HTML = '';
		$a = 0;

		$res = $this->getDataInit($uid, $subCSSclass);
		$c = $this->getDataCount($res);
		$crazyRecursionLimiter = 999;

		$idH = array();

			// Traverse the records:
		while ($crazyRecursionLimiter > 0 && $row = $this->getDataNext($res, $subCSSclass))
		{
			$a++;
			$crazyRecursionLimiter--;

			$newID = $row['uid'];

			if ($newID == 0)
			{
				throw new RuntimeException('Endless recursion detected: TYPO3 has detected an error in the database. Please fix it manually (e.g. using phpMyAdmin) and change the UID of ' . $this->table . ':0 to a new value.<br /><br />See <a href="http://bugs.typo3.org/view.php?id=3495" target="_blank">bugs.typo3.org/view.php?id=3495</a> to get more information about a possible cause.', 1294586383);
			}

			$this->tree[] = array(); // Reserve space.
			end($this->tree);
			$treeKey = key($this->tree); // Get the key for this space
			$LN = ($a == $c) ? 'blank' : 'line';

				// If records should be accumulated, do so
			if ($this->setRecs)
			{
				$this->recs[$row['uid']] = $row;
			}

				// Accumulate the id of the element in the internal arrays
			$this->ids_hierarchy[$depth][] = $this->ids[] = $idH[$row['uid']]['uid'] = $row['uid'];
			$this->orig_ids_hierarchy[$depth][] = $row['_ORIG_uid'] ? $row['_ORIG_uid'] : $row['uid'];

				// Make a recursive call to the next level
			$HTML_depthData = $depthData . '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' .
											$LN . '.gif', 'width="18" height="16"') . ' alt="" />';
			if ($depth > 1 && $this->expandNext($newID) && !$row['php_tree_stop'])
			{
				$nextCount = $this->getTree(
					$newID,
					$depth - 1,
					$this->makeHTML ? $HTML_depthData : '',
					$blankLineCode . ',' . $LN,
					$row['_SUBCSSCLASS']
				);
				if (count($this->buffer_idH))
				{
					$idH[$row['uid']]['subrow'] = $this->buffer_idH;
				}
				$exp = 1; // Set "did expand" flag
			} else
			{
				$nextCount = $this->getCount($newID);
				$exp = 0; // Clear "did expand" flag
			}

				// Set HTML-icons, if any:
			if ($this->makeHTML)
			{
				$HTML = $depthData . $this->PMicon($row, $a, $c, $nextCount, $exp);
				$HTML .= $this->wrapStop($this->getIcon($row), $row);
				#	$HTML.=$this->wrapStop($this->wrapIcon($this->getIcon($row),$row),$row);
			}

				// Finally, add the row/HTML content to the ->tree array in the reserved key.
			$this->tree[$treeKey] = array(
				'row' => $row,
				'HTML' => $HTML,
				'HTML_depthData' => $this->makeHTML == 2 ? $HTML_depthData : '',
				'invertedDepth' => $depth,
				'blankLineCode' => $blankLineCode,
				'bank' => $this->bank,
				'hasSub' => $nextCount&&$this->expandNext($newID),
				'isFirst'=> $a==1,
				'isLast' => FALSE,	
			);
		}
		
		if($a)
		{
			$this->tree[$treeKey]['isLast'] = TRUE;
		}

		$this->getDataFree($res);
		$this->buffer_idH = $idH;
		return $c;
	}
	
	function getBrowsableTree ( $subtree_Uid = 0, $maxDepth = 999, $highlight = 0 )
	{
			// Get stored tree structure AND updating it if needed according to incoming PM GET var.
		$this->initializePositionSaving();

			// Init done:
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		$treeArr = array();

		$this->MOUNTS [0] = 0;
		
			// Traverse mounts:
		foreach($this->MOUNTS as $idx => $uid)
		{
				// Set first:
			$this->bank = $idx;
			$isOpen = $this->stored[$idx][$uid] || $this->expandFirst || $uid === '0';

				// Save ids while resetting everything else.
			$curIds = $this->ids;
			$this->reset();
			$this->ids = $curIds;

				// Set PM icon for root of mount:
			$cmd = $this->bank.'_'.($isOpen? "0_" : "1_").$uid.'_'.$this->treeName;
				// only, if not for uid 0
			if ($uid)
			{
				$icon = '<img' . t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/' .
							($isOpen ? 'minus' :'plus' ) . 'only.gif') . ' alt="" />';
				$firstHtml = $this->PMiconATagWrap($icon, $cmd, !$isOpen);
			}

				// Preparing rootRec for the mount
			if ($uid)
			{
				$rootRec = $this->getRecord($uid);
				$firstHtml.=$this->getIcon($rootRec);
			} else
			{
				// Artificial record for the tree root, id=0
				$rootRec = $this->getRootRecord($uid);
				$firstHtml.=$this->getRootIcon($rootRec);
			}

			if (is_array($rootRec))
			{
					// In case it was swapped inside getRecord due to workspaces.
				$uid = $rootRec['uid'];

					// Add the root of the mount to ->tree
				$this->tree[] = array(	'HTML'=>$firstHtml,
										'row'=>$rootRec,
										'bank'=>$this->bank,
										'hasSub'=>TRUE,
										'invertedDepth'=>1000);

					// If the mount is expanded, go down:
				if ($isOpen)
				{
						// Set depth:
					if ($this->addSelfId)
					{
						$this->ids[] = $uid;
					}
					$this->getTree($uid, 999, '', $rootRec['_SUBCSSCLASS']);
				}
					// Add tree:
				$treeArr=array_merge($treeArr,$this->tree);
			}
		}
		return $this->printTree($treeArr, $subtree_Uid, $highlight);
	}
	
	
	/**
	 * Create the folder navigation tree in HTML
	 * 
	 * @param	mixed		Input tree array. If not array, then $this->tree is used.
	 * @return	string		HTML output of the tree.
	 */
	function printTree($treeArr = '', $open=0, $highlight)
	{	
		$titleLen = intval ( $this->BE_USER->uc ['titleLen'] );
		if (! is_array ( $treeArr ))
			$treeArr = $this->tree;
	
			// put a table around it with IDs to access the rows from JS
			// not a problem if you don't need it
			// In XHTML there is no "name" attribute of <td> elements - but Mozilla will not be able to highlight rows if the name attribute is NOT there.
			
		$out = '<ul class="tree" id="'.$this->treeName.'" style="padding:0px;margin:0px">';
		
		$c = 0;
		foreach ( $treeArr as $k => $v )
		{	
			$alt = $c % 2;
			if ($alt == 0)
			{
				$v ['row'] ['_CSSCLASS'] = "#f8f8f8";
			}
			
				//check & mark selected
			if ( $v ['row'] ['uid'] == $highlight && $highlight != 0)
			{
				$v ['row'] ['_CSSCLASS'] = "#d0e4c9";
			}
	
			$classAttr = $v [ 'row' ] [ '_CSSCLASS' ];
			$uid	   = $v [ 'row' ][ 'uid' ];
			$idAttr	= htmlspecialchars($this->domIdPrefix.$this->getId($v['row']).'_'.$v['bank']);

			// if this item is the start of a new level,
			// then a new level <ul> is needed, but not in ajax mode
			if($v['isFirst'] && $open != $uid)
			{
				$out .= '<ul style="padding:0px;margin:0px;">';
			} elseif ($open == $uid)
			{
				$out .= '<ul style="padding:0px;margin:0px;">';
			}
			
			// add CSS classes to the list item
			if($v['hasSub'])
			{
				$classAttr .= ($classAttr) ? ' expanded' : 'expanded';
			}
			if($v['isLast'])
			{
				$classAttr .= ($classAttr) ? ' last' : 'last';
			}
	
			$v ['row'] ['_CSSCLASS'] = $v ['row'] ['_CSSCLASS'] ? $v ['row'] ['_CSSCLASS'] : "#ffffff";
	
			$out  .= '<li id="' . $idAttr .
					 '" style="cursor:pointer;margin:0px;padding:0px;height:16px;display:block;background-color:'. $v ['row'] ['_CSSCLASS'] .
					 '" onMouseOver="this.style.backgroundColor=\'#ebebeb\'" onMouseOut="this.style.backgroundColor=\'' .
					 $v['row']['_CSSCLASS'] . '\'" '.
					 $this->wrapTitle ( $this->getTitleStr ( $v ['row'], $titleLen ), $v ['row'] ).'>'. $v ['HTML'] .
					 $this->getTitleStr ( $v ['row'], $titleLen ). '</li>';
			
			
			if(!$v['hasSub'])
			{
				$out  .= '</li>';
			}

			// we have to remember if this is the last one
			// on level X so the last child on level X+1 closes the <ul>-tag
			if($v['isLast'] && $open != $uid)
			{
				$closeDepth[$v['invertedDepth']] = 1;
			}

			// if this is the last one and does not have subitems, we need to close
			// the tree as long as the upper levels have last items too
			if($v['isLast'] && !$v['hasSub'] && $open != $uid)
			{
				for ($i = $v['invertedDepth']; $closeDepth[$i] == 1; $i++)
				{
					$closeDepth[$i] = 0;
					$out  .= '</ul></li>';
				}
			}
			$c ++;
		}
		$out .= '</ul>';
		return $out;
	}	
}

class tx_ch_treeview
{
	var $useXajax = false;
	var $treeItemC, $treeIDs, $treeName, $hiddenField, $TCEforms_itemFormElName, $table, $itemArray,$parentField,$foreignTable;
	
	function displayCategoryTree($PA, $fobj)
	{	
		$this->PA = &$PA;
 		$this->pObj = &$pObj;
		$this->table = $this->PA['table'];
		$this->field = $this->PA['field'];
		$this->row = $this->PA['row'];
		
			// parent obj class.t3lib_tceforms.php
		$this->pObj = &$this->PA['pObj'];
		
			// number of trees already rendered
		$this->pObj->inline_tree ++;
		
			// add add js
		$this->pObj->additionalJS_pre['tx_ch_treeview']='
			function getFormValueSelected(fName)
			{	//
				var formObj = setFormValue_getFObj(fName)
				if (formObj)
				{
					var result = "";
					var localArray_V = new Array();
					var fObjSel = formObj[fName+"_list"];
					var l=fObjSel.length;
					var c=0;
					for (a=0;a<l;a++)
					{
						if (fObjSel.options[a].selected==1)
						{
							localArray_V[c++]=fObjSel.options[a].value;
						}
					}
				}
				result = localArray_V.join("_");
				return result;
			}
				// Highlighting rows in the page tree:
			var hilight_old;
			function hilight_row(frameSetModule,highLightID)
			{	//
				// Remove old:
				theObj = document.getElementById(top.fsMod.navFrameHighlightedID["navframe"]);
				if (theObj)
				{
					theObj.style.backgroundColor=hilight_old;
				}
	
					// Set new:
				top.fsMod.navFrameHighlightedID["navframe"] = highLightID;
				theObj = document.getElementById(highLightID);
				if (theObj)
				{
					hilight_old = theObj.style.backgroundColor;
					theObj.style.backgroundColor="#d0e4c9";
				}
			}
			function disabledEventPropagation(event)
			{
			   if (event.stopPropagation){
				   event.stopPropagation();
			   }
			   else if(window.event){
				  window.event.cancelBubble=true;
			   }
			}';
		$needle = array ('/ {2,}/','/\}\r\n/','/\t{2,}/');
		$replace = array (' ','}',' ');
		$this->pObj->additionalJS_pre['tx_ch_treeview']=preg_replace($needle,$replace,$this->pObj->additionalJS_pre['tx_ch_treeview']);
		
			//Todo: Optimized
		$table = $PA ['table'];
		$field = $PA ['field'];
		$row = $PA ['row'];
		
		if (t3lib_extMgm::isLoaded ( 'xajax' ))
		{
			global $TYPO3_CONF_VARS;
			
			$this->useXajax = TRUE;
			require_once (t3lib_extMgm::extPath ( 'xajax' ) . 'class.tx_xajax.php');
			
			if ($TYPO3_CONF_VARS ['BE'] ['forceCharset'])
			{
				define ( 'XAJAX_DEFAULT_CHAR_ENCODING', $TYPO3_CONF_VARS ['BE'] ['forceCharset'] );
				$this->xajax = t3lib_div::makeInstance ( 'tx_xajax' );							
				$this->xajax->cleanBufferOn();
				$this->xajax->decodeUTF8InputOn();
				$this->xajax->setCharEncoding('utf-8');
			} else
			{
				define ( 'XAJAX_DEFAULT_CHAR_ENCODING', 'iso-8859-15' );
				define ( 'XAJAX_DEFAULT_CHAR_ENCODING', 'iso-8859-15' );
				$this->xajax = t3lib_div::makeInstance ( 'tx_xajax' );							
				$this->xajax->cleanBufferOn();
				$this->xajax->setCharEncoding('iso-8859-15');
			}
			
			$this->xajax = t3lib_div::makeInstance ( 'tx_xajax' );
			$this->xajax->setWrapperPrefix ( 'tx_chtreeview_' );
			$this->xajax->registerFunction ( array ('sendResponse', &$this, 'sendResponse' ) );
	
			$content .= $this->xajax->getJavascript ( '../' . t3lib_extMgm::siteRelPath ( 'xajax' ) );
			$this->xajax->processRequests ();
		}
		
		return $content . $this->renderCategoryFields ();
	}
	
	function renderCatTree ( $cmd = '' )
	{
		global $TCA, $LANG;
		
		$config = $this->PA['fieldConf']['config'];
		$this->treeParentLabel=$TCA[$config['foreign_table']]['ctrl']['treeLabelField']?$TCA[$config['foreign_table']]['ctrl']['treeLabelField']:'title';
		
		$obj = t3lib_div::makeInstance ( 'tx_ch_tceFunc_selectTreeView' );
		$obj->table = $this->foreignTable = $config['foreign_table'];
		$obj->backPath = $this->pObj->backPath;
		$obj->parentField = $this->parentField = $TCA [$config ['foreign_table']] ['ctrl'] ['treeParentField'];
		$obj->expandFirst = $this->expandFirst;
		
		$SPaddWhere = empty ( $this->row [ "pid" ] ) ? '' : ' AND pid='.$this->row [ "pid" ];
		if (! empty ( $config [ 'treeParentUid' ] ) )
		{
			$SPaddWhere .= ' AND ' . $obj->parentField . ' IN ( 0,' . $config [ 'treeParentUid' ] . ')';
		}
		$orderBy = $config ['orderBy'] ? $config['orderBy'] : 'uid';
		
		$obj->init ( $SPaddWhere, $orderBy );
		$obj->maxDepth = $config ['treeMaxDepth'];
		$obj->expandAll = 0;
		$obj->expandFirst = 1;
		$obj->fieldArray = array ('uid', 'title', $TCA [$config ['foreign_table']] ['ctrl'] ['treeParentField'] ); // those fields will be filled to the array $obj->tree
		$obj->ext_IconMode = '1'; // no context menu on icons
		$obj->title = $LANG->sL ( $TCA [$config ['foreign_table']] ['ctrl'] ['title'] );
		$obj->thisScript = 'alt_doc.php';
		
		if (isset($this->PA['row']['sys_language_uid']))
		{
			$obj->clause = ' and sys_language_uid='.addslashes(intval($this->PA['row']['sys_language_uid']));
		}
		
		if (empty($cmd))
		{
			$obj->treeName = $this->treeName = $config ['treeName'] . '_' . substr ( md5 ( $config ['treeName'] ), 1, 4 ) . '_' . $this->pObj->inline_tree;
		} else
		{
			list(,,,$treeName,,$obj->uid,$obj->ele_uid) = explode('_',$cmd);
			$obj->treeName = $this->treeName = $config ['treeName'] . '_' . substr ( md5 ( $config ['treeName'] ), 1, 4 ) . '_' .$obj->ele_uid;
		}
		
		$obj->hiddenField = $this->hiddenField = '<input type="hidden" name="' . $obj->treeName . '_pm" value="">';
		$obj->itemArray = $this->itemArray = t3lib_div::trimExplode ( ',', $this->PA ['itemFormElValue'], 1 );
		$obj->makeHTML = 1;
		
		if (preg_match('/pi_flexform/',$this->PA ['itemFormElName']))
		{				
			$obj->TCEforms_itemFormElName = $this->TCEforms_itemFormElName = $this->PA ['itemFormElName'];
		} else if (empty($this->ele_uid))
		{
			$this->ele_uid = $this->PA ['row'] ['uid'];
			$obj->TCEforms_itemFormElName = $this->TCEforms_itemFormElName = 'data[' . $this->PA ['table'] . '][' .
																						$this->ele_uid .
																						'][' . $this->PA['field'] . ']';
		} else
		{
			$obj->TCEforms_itemFormElName = $this->TCEforms_itemFormElName = 'data[' . $this->PA ['table'] . '][' .
																						$this->ele_uid .
																					'][' . $this->PA['field'] . ']';
		}
		
			// PA-uid
		$obj->paUid = $this->PA['row']['uid'];
		
			// Use Xajax
		$obj->useXajax = $this->useXajax;
				
		if ($config ['treeRadio'] && empty($cmd))
		{
			$obj->uid = $this->PA ['row'] [$this->PA ['fieldConf'] ['config'] ['treeField']] ? $this->PA ['row'] [$this->PA ['fieldConf'] ['config'] ['treeField']] : 1;
		} elseif ( !empty($cmd))
		{
				// Default Tree-Value
			$obj->uid = 1;
		}
		
			// Todo: Obsolete
		$treeContent = '<script type="text/javascript">
							function set' . $obj->treeName . 'PM(pm) {
								document.editform.' . $obj->treeName . '_pm.value = pm;
						}</script>';
		
			// render tree html
		$treeContent .= $obj->getBrowsableTree ( $obj->uid, $obj->maxDepth );
		
		$this->treeItemC = count ( $obj->ids );
		$this->treeIDs = $obj->ids;
		
		return $treeContent;
	}
	
	function renderCategoryFields()
	{	
		$PA = &$this->PA;
		$table = $PA ['table'];
		$field = $PA ['field'];
		$row = $PA ['row'];
		
		if ($GLOBALS ['TYPO3_CONF_VARS'] ['EXT'] ['extConf'] ['ch_treeview'])
		{
			$confArr = unserialize ( $GLOBALS ['TYPO3_CONF_VARS'] ['EXT'] ['extConf'] ['ch_treeview'] );
		}

			// Field configuration from TCA:
		$config = $PA ['fieldConf'] ['config'];
		
			// it seems TCE has a bug and do not work correctly with '1'
		$config ['maxitems'] = ($config ['maxitems'] == 2) ? 1 : $config ['maxitems'];
		
			// Getting the selector box items from the system
		$selItems = $this->pObj->addSelectOptionsToItemArray ( $this->pObj->initItemArray ( $PA ['fieldConf'] ),
																$PA ['fieldConf'],
																$this->pObj->setTSconfig ( $table, $row ),
																$field );
		$selItems = $this->pObj->addItems ( $selItems, $PA ['fieldTSConfig'] ['addItems.'] );
		if ($config ['itemsProcFunc'])
		{
			$selItems = $this->pObj->procItems ( $selItems, $PA ['fieldTSConfig'] ['itemsProcFunc.'], $config, $table, $row, $field );
		}
		
			// Possibly remove some items:
		$removeItems = t3lib_div::trimExplode ( ',', $PA ['fieldTSConfig'] ['removeItems'], 1 );
		
		foreach ( $selItems as $tk => $p )
		{
			if (in_array ( $p [1], $removeItems ))
			{
				unset ( $selItems [$tk] );
			} else if (isset ( $PA ['fieldTSConfig'] ['altLabels.'] [$p [1]] ))
			{
				$selItems [$tk] [0] = $this->pObj->sL ( $PA ['fieldTSConfig'] ['altLabels.'] [$p [1]] );
			}
			
				// Removing doktypes with no access:
			if ($table . '.' . $field == 'pages.doktype')
			{
				if (! ($GLOBALS ['BE_USER']->isAdmin () || t3lib_div::inList ( $GLOBALS ['BE_USER']->groupData ['pagetypes_select'], $p [1] )))
				{
					unset ( $selItems [$tk] );
				}
			}
		}
		
			// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset ( $PA ['fieldTSConfig'] ['noMatchingValue_label'] ) ? $this->pObj->sL ( $PA ['fieldTSConfig'] ['noMatchingValue_label'] ) : '[ ' .
								$this->pObj->getLL ( 'l_noMatchingValue' ) . ' ]';
		$nMV_label = @sprintf ( $nMV_label, $PA ['itemFormElValue'] );
		
			// build tree selector		
		$item .= '<input type="hidden" name="' . $PA ['itemFormElName'] . '_mul" value="' . ($config ['multiple'] ? 1 : 0) . '" />';
		
			// Prepare some values:
		$maxitems = intval ( $config ['maxitems'] );
		$minitems = intval ( $config ['minitems'] );
		$size = intval ( $config ['size'] );
		
			// Set max and min items:
		$maxitems = t3lib_div::intInRange ( $config ['maxitems'], 0 );
		if (! $maxitems)
			$maxitems = 100000;
		$minitems = t3lib_div::intInRange ( $config ['minitems'], 0 );
		
			// Register the required number of elements:
		$this->pObj->requiredElements [$PA ['itemFormElName']] = array ($minitems, $maxitems, 'imgName' => $table . '_' . $row ['uid'] . '_' . $field );
		
		if ($config ['treeView'] and $config ['foreign_table'])
		{	
				// Field configuration from TCA:
			$config = $PA ['fieldConf'] ['config'];
				// it seems TCE has a bug and do not work correctly with '1'
			$config ['maxitems'] = ($config ['maxitems'] == 2) ? 1 : $config ['maxitems'];
			
				// get default items
			$defItems = array ();
			if (is_array ( $config ['items'] ) && $this->table == 'tt_content' && $this->row ['CType'] == 'list' && $this->row ['list_type'] == 9 && $this->field == 'pi_flexform')
			{
				reset ( $config ['items'] );
				while ( list ( $itemName, $itemValue ) = each ( $config ['items'] ) )
				{
					if ($itemValue [0])
					{
						$ITitle = $GLOBALS ['LANG']->sL ( $itemValue [0] );
						$defItems [] = '<a href="javascript:void(0);" onclick="setFormValueFromBrowseWin(\'data[' .
						$this->table . '][' . $this->row ['uid'] . '][' . $this->field . '][data][sDEF][lDEF][categorySelection][vDEF]\',' .
						$itemValue [1] . ',\'' . $ITitle . '\'); return false;" style="text-decoration:none;">' . $ITitle . '</a>';
					}
				}
			}
			
			$treeContent = '<span id="'. $this->treeName . '">' . $this->renderCatTree () . '<span>';
			
			if ($defItems [0])
			{ // add default items to the tree table. In this case the value [not categorized]
				$this->treeItemC += count ( $defItems );
				$treeContent .= '<table border="0" cellpadding="0" cellspacing="0"><tr>
					<td>' . $GLOBALS ['LANG']->sL ( $config ['itemsHeader'] ) . '&nbsp;</td><td>' . implode ( $defItems, '<br />' ) . '</td>
					</tr></table>';
			}
			
			$width = 320; // default width for the field with the category tree
			if (intval ( $confArr ['categoryTreeWidth'] ))
			{ // if a value is set in extConf take this one.
				$width = t3lib_div::intInRange ( $confArr ['categoryTreeWidth'], 1, 600 );
			} elseif ($GLOBALS ['CLIENT'] ['BROWSER'] == 'msie')
			{ // to suppress the unneeded horizontal scrollbar IE needs a width of at least 320px
				$width = 320;
			}
			
			$config ['autoSizeMax'] = t3lib_div::intInRange ( $config ['autoSizeMax'], 0 );
			
			$height = $config ['autoSizeMax'] ? t3lib_div::intInRange ( $this->treeItemC + 2, t3lib_div::intInRange ( $size, 1 ), $config ['autoSizeMax'] ) : $size;
				// hardcoded: 16 is the height of the icons
			$height = $height * 16 * 2 - 40;
			
			$divStyle = 'position:relative; left:0px; top:0px; height:' . $height . 'px; width:' . $width . 'px;border:solid 1px;overflow:auto;background:#fff;margin-bottom:5px;';
			$thumbnails = '<div  name="' . $PA ['itemFormElName'] . '_selTree" id="'.$this->treeName.'-tree-div" style="' . htmlspecialchars ( $divStyle ) . '">';
			$thumbnails .= $treeContent . $this->hiddenField;
			$thumbnails .= '</div>';
		
		} else
		{	
			$sOnChange = 'setFormValueFromBrowseWin(\'' . $PA ['itemFormElName'] . '\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text); ' . implode ( '', $PA ['fieldChangeFunc'] );
			
				// Put together the select form with selected elements:
			$selector_itemListStyle = isset ( $config ['itemListStyle'] ) ? ' style="' . htmlspecialchars ( $config ['itemListStyle'] ) . '"' : ' style="' . $this->pObj->defaultMultipleSelectorStyle . '"';
			$size = $config ['autoSizeMax'] ? t3lib_div::intInRange ( count ( $itemArray ) + 1, t3lib_div::intInRange ( $size, 1 ), $config ['autoSizeMax'] ) : $size;
			$thumbnails = '<select style="width:250px;" name="' . $PA ['itemFormElName'] . '_sel"' . $this->pObj->insertDefStyle ( 'select' ) . ($size ? ' size="' . $size . '"' : '') . ' onchange="' . htmlspecialchars ( $sOnChange ) . '" onClick="set' . $this->treeName . 'PM(\'' . $cmd . '\');TBE_EDITOR_submitForm();"' . $PA ['onFocus'] . $selector_itemListStyle . '>';
			foreach ( $selItems as $p )
			{
				$thumbnails .= '<option value="' . htmlspecialchars ( $p [1] ) . '">' . htmlspecialchars ( $p [0] ) . '</option>';
			}
			$thumbnails .= '</select>';
		}
		
			// Perform modification of the selected items array:
		$itemArray = t3lib_div::trimExplode ( ',', $PA ['itemFormElValue'], 1 );
		foreach ( $itemArray as $tk => $tv )
		{
			$tvP = explode ( '|', $tv, 2 );
			if (in_array ( $tvP [0], $removeItems ) && ! $PA ['fieldTSConfig'] ['disableNoMatchingValueElement'])
			{
				$tvP [1] = rawurlencode ( $nMV_label );
			} elseif (isset ( $PA ['fieldTSConfig'] ['altLabels.'] [$tvP [0]] ))
			{
				$tvP [1] = rawurlencode ( $this->pObj->sL ( $PA ['fieldTSConfig'] ['altLabels.'] [$tvP [0]] ) );
			} else
			{
				$tvP [1] = rawurlencode ( $this->pObj->sL ( rawurldecode ( $tvP [1] ) ) );
			}
			$itemArray [$tk] = implode ( '|', $tvP );
		}
		$sWidth = 220; // default width for the left field of the category select
		if (intval ( $confArr ['categorySelectedWidth'] ))
		{
			$sWidth = t3lib_div::intInRange ( $confArr ['categorySelectedWidth'], 1, 600 );
		}
		
		$params = array ('size' => $size, 'autoSizeMax' => t3lib_div::intInRange ( $config ['autoSizeMax'], 0 ), 'style' => ' style="width:' . $sWidth . 'px;"', 'dontShowMoveIcons' => ($maxitems <= 1), 'maxitems' => $maxitems, 'info' => '', 'headers' => array ('selector' => $this->pObj->getLL ( 'l_selected' ) . ':<br />', 'items' => $this->pObj->getLL ( 'l_items' ) . ':<br />' ), 'setValue' => 'append', 'noBrowser' => 1, 'thumbnails' => $thumbnails );
		$item .= $this->pObj->dbFileIcons ( $PA ['itemFormElName'], '', '', $itemArray, '', $params, $PA ['onFocus'] );
		
		if ($config ['treeRadio'] || $config ['treePullDown'])
		{
			$this->beta($item);
		}
		
			// add tree navigation
		if ($config ['treeNavi'] && $this->useXajax)
		{
			$item = preg_replace ( '/<select(.+?)>/', '<select\1 onclick="tx_chtreeview_sendResponse(\'X_1_\'+this.options[this.selectedIndex].value+\'_' .
								  $this->treeName . '_' .
								  preg_replace ( '/_/', '\+', $this->parentField ) . '_' . preg_replace ( '/_/', '\+', $this->foreignTable ) .
								  '_\'+getFormValueSelected(\'' . $PA ['itemFormElName'] . '\'));">', $item );
		} else if ($this->useXajax)
		{
			$item = preg_replace ( '/<select(.+?)>/', '<select\1 getFormValueSelected(\'' . $PA ['itemFormElName'] . '\'));">', $item );
			
			//Todo: xAjax
			//$item = preg_replace ( '/<select(.+?)>/', '<select\1 onClick="set' . $this->treeName . 'PM(\'X_1_\'+this.options[this.selectedIndex].value+\'_' . $this->treeName . '_' . preg_replace ( '/_/', '\+', $this->parentField ) . '_' . preg_replace ( '/_/', '\+', $this->table ) . '_\'+getFormValueSelected(\'' . $PA ['itemFormElName'] . '\'));TBE_EDITOR_submitForm();return false;">', $item );
		}
		
			// add selected
		if ($config['beta'] && $this->TCEforms_Xitem)
		{
			$PM = explode ( '_', $this->TCEforms_Xitem );
			if ($PM [0] == 'X')
			{
				$item = preg_replace ( '/(<option )(value="' . $PM [2] . '">)/', '\1selected \2', $item );
				for($i = 7; $i < 20; $i ++)
				{
					if ($PM [$i])
					{
						$item = preg_replace ( '/(<option )(value="' . $PM [$i] . '">)/', '\1selected \2', $item );
					}
				}
			}
		}
				
			// colorize even			
		$count = preg_match_all ( '/<option.+?>/', $item, $needle );
		$c = 0;
		if ($count)
		{
			foreach ( $needle [0] as $k => $v )
			{
				$alt = $c % 2;
				if ($alt)
				{
					$highlight = preg_replace ( '/<option(.+?)>/', '<option\1 style="background-color:#ede9e5">', $v );
					$item = preg_replace ( '/' . $v . '/', $highlight, $item );
				}
				$c ++;
			}
		}
		
			// Wizards:
		$altItem = '<input type="hidden" name="' . $PA ['itemFormElName'] . '" value="' . htmlspecialchars ( $PA ['itemFormElValue'] ) . '" />';
		$item = $this->pObj->renderWizards ( array ($item, $altItem ), $config ['wizards'], $table, $row, $field, $PA, $PA ['itemFormElName'], $specConf );
		
		$needle = array ('/[\r\n\t]/', '/> +?</' );
		$replace = array ('', '><' );
		$item = preg_replace ( $needle, $replace, $item );
		
		return $this->NA_Items . $item;
	}
	
	function sendResponse ( $cmd )
	{
		if ($GLOBALS ['TYPO3_CONF_VARS'] ['EXT'] ['extConf'] ['ch_treeview'])
		{
			$this->confArr = unserialize ( $GLOBALS ['TYPO3_CONF_VARS'] ['EXT'] ['extConf'] ['ch_treeview'] );
		}

		$objResponse = new tx_xajax_response ( );

		if ($cmd == 'show' || $cmd == 'hide')
		{
			$content = $this->renderCatTree ( $cmd );
		} else
		{	
			// Todo: Remove md5???
			list(,,,$treeName,,$uid,$this->ele_uid) = explode('_',$cmd);
			
			if (preg_match('/pi_flexform/',$this->PA ['itemFormElName']))
			{		
				if ($treeName == 'txchtripfind')
				{
					$this->PA['itemFormElName'] = preg_replace('/typeSelection/','findSelection',$this->PA['itemFormElName']);
					$this->PA['row']['uid'] = $this->ele_uid;
					$this->PA ['fieldConf'] ['config']['treeName'] = $treeName;
				}
				
			} else
			{
				// Copy $GLOBALS['TCA'] tree config to local $PA
				foreach ( $GLOBALS ['TCA'] as $treeKey => $treeValue)
				{
					if (is_array($treeValue['columns']))
					{
						foreach ($treeValue['columns'] as $fieldKey => $fieldValue)
						{
							$configName = preg_replace('/_/','',$fieldValue['config']['treeName']);
							
							if (!empty($fieldValue['config']['treeName']) && $treeName == $configName)
							{
								$this->PA['fieldConf']['config'] = $fieldValue['config'];
								$this->PA['field'] = $fieldKey;
								$this->PA['table'] = $this->table = $treeKey;
								$this->PA['foreignTable'] = $this->foreignTable = $fieldValue['config']['foreignTable'];
								$this->PA['row']['uid'] = $this->ele_uid;
								$this->PA[$this->PA['fieldConf'] ['config'] ['foreign_table']] = $GLOBALS ['TCA'][$this->PA['fieldConf'] ['config'] ['foreign_table']];
								$this->PA['itemFormElName'] = 'data[' . $this->PA ['table'] . '][' .
																$this->ele_uid .
																'][' . $this->PA['field'] . ']';
								break;
							}
						}
					}
				}
			}

			//set PM get-parameter to $cmd
			t3lib_div::_GETset ( $cmd, 'PM' );
			$content = $this->renderCatTree ( $cmd );
		}
		
		$config = $this->PA ['fieldConf'] ['config'];
		
	 	if ($cmd)
		{
			$needle = explode  ('_',$cmd);
			$treeDiv = $config ['treeName'] . '_' . substr ( md5 ( $config ['treeName'] ), 1, 4 )  . '_' . $needle[5];
			
		} else
		{
			$treeDiv = $config ['treeName'] . '_' . substr ( md5 ( $config ['treeName'] ), 1, 4 )  . '_' . $this->pObj->inline_tree;
		}
				
		// 		$content .= '<div id="debug-tree">debug</div>';
		$objResponse->addAssign ( $treeDiv, 'innerHTML', $content );
		
		// 		$this->debug['treeItemC'] = $this->treeItemC;
		// 		$objResponse->addAssign('debug-tree', 'innerHTML', t3lib_div::view_array($this->debug));
		
		$size = intval ( $config ['size'] );
		$config ['autoSizeMax'] = t3lib_div::intInRange ( $config ['autoSizeMax'], 0 );
		$height = $config ['autoSizeMax'] ? t3lib_div::intInRange ( $this->treeItemC + 2, t3lib_div::intInRange ( $size, 1 ), $config ['autoSizeMax'] ) : $size;
		// hardcoded: 16 is the height of the icons
		$height = $height * 16;
		$objResponse->addAssign ( $treeDiv, 'style.height', $height . 'px;' );
		
		// 		$objResponse->addAssign('showHide', 'innerHTML', $showhideLink);

		//return the XML response
		return $objResponse->getXML ();
	}
}
?>