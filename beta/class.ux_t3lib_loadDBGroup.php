<?php

require_once (PATH_t3lib.'class.t3lib_loadDBGroup.php');

class ux_t3lib_loadDBGroup extends t3lib_loadDBGroup {

	/**
	 * Writes the internal itemArray to MM table:
	 *
	 * @param	string		MM table name
	 * @param	integer		Local UID
	 * @param	boolean		If set, then table names will always be written.
	 * @return	void
	 */
	function writeMM($MM_tableName,$uid,$prependTableName=0)	{

		if ($this->MM_is_foreign)	{	// in case of a reverse relation
			$uidLocal_field = 'uid_foreign';
			$uidForeign_field = 'uid_local';
			$sorting_field = 'sorting_foreign';
		} else {	// default
			$uidLocal_field = 'uid_local';
			$uidForeign_field = 'uid_foreign';
			$sorting_field = 'sorting';
		}

			// If there are tables...
		$tableC = count($this->tableArray);
		if ($tableC)	{
			$prep = ($tableC>1||$prependTableName||$this->MM_isMultiTableRelationship) ? 1 : 0;	// boolean: does the field "tablename" need to be filled?
			$c=0;

			$additionalWhere_tablenames = '';
			if ($this->MM_is_foreign && $prep)	{
				$additionalWhere_tablenames = ' AND tablenames="'.$this->currentTable.'"';
			}

			$additionalWhere = '';
				// add WHERE clause if configured
			if ($this->MM_table_where) {
				$additionalWhere.= "\n".str_replace('###THIS_UID###', intval($uid), $this->MM_table_where);
			}
				// Select, update or delete only those relations that match the configured fields
			foreach ($this->MM_match_fields as $field => $value) {
				$additionalWhere.= ' AND '.$field.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($value, $MM_tableName);
			}

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$uidForeign_field.($prep?', tablenames':'').($this->MM_hasUidField?', uid':''),
				$MM_tableName,
				$uidLocal_field.'='.$uid.$additionalWhere_tablenames.$additionalWhere,
				'',
				$sorting_field
			);

			$oldMMs = array();
			$oldMMs_inclUid = array();	// This array is similar to $oldMMs but also holds the uid of the MM-records, if any (configured by MM_hasUidField). If the UID is present it will be used to update sorting and delete MM-records. This is necessary if the "multiple" feature is used for the MM relations. $oldMMs is still needed for the in_array() search used to look if an item from $this->itemArray is in $oldMMs
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if (!$this->MM_is_foreign && $prep)	{
					$oldMMs[] = array($row['tablenames'], $row[$uidForeign_field]);
				} else {
					$oldMMs[] = $row[$uidForeign_field];
				}
				$oldMMs_inclUid[] = array($row['tablenames'], $row[$uidForeign_field], $row['uid']);
			}

				// For each item, insert it:
			foreach($this->itemArray as $val)	{
				$c++;

				if ($prep || $val['table']=='_NO_TABLE')	{
					if ($this->MM_is_foreign)	{	// insert current table if needed
						$tablename = $this->currentTable;
					} else {
						$tablename = $val['table'];
					}
				} else {
					$tablename = '';
				}

				if(!$this->MM_is_foreign && $prep) {
					$item = array($val['table'], $val['id']);
				} else {
					$item = $val['id'];
				}

				if (in_array($item, $oldMMs))	{
					$oldMMs_index = array_search($item, $oldMMs);

					$whereClause = $uidLocal_field.'='.$uid.' AND '.$uidForeign_field.'='.$val['id'].
									($this->MM_hasUidField ? ' AND uid='.intval($oldMMs_inclUid[$oldMMs_index][2]) : ''); 	// In principle, selecting on the UID is all we need to do if a uid field is available since that is unique! But as long as it "doesn't hurt" we just add it to the where clause. It should all match up.
					if ($tablename) {
						$whereClause .= ' AND tablenames="'.$tablename.'"';
					}
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($MM_tableName, $whereClause.$additionalWhere, array($sorting_field => $c));

					unset($oldMMs[$oldMMs_index]);	// remove the item from the $oldMMs array so after this foreach loop only the ones that need to be deleted are in there.
					unset($oldMMs_inclUid[$oldMMs_index]);	// remove the item from the $oldMMs array so after this foreach loop only the ones that need to be deleted are in there.
				} else {

					$insertFields = $this->MM_insert_fields;
					$insertFields[$uidLocal_field] = $uid;
					$insertFields[$uidForeign_field] = $val['id'];
					$insertFields[$sorting_field] = $c;
					if($tablename)	{
						$insertFields['tablenames'] = $tablename;
					}

					$GLOBALS['TYPO3_DB']->exec_INSERTquery($MM_tableName, $insertFields);

					if ($this->MM_is_foreign)	{
						$this->updateRefIndex($val['table'], $val['id']);
					}
				}
			}

/*
				// Delete all not-used relations:
			if(is_array($oldMMs) && count($oldMMs) > 0) {
				$removeClauses = array();
				$updateRefIndex_records = array();
				foreach($oldMMs as $oldMM_key => $mmItem) {
					if ($this->MM_hasUidField)	{	// If UID field is present, of course we need only use that for deleting...:
						$removeClauses[] = 'uid='.intval($oldMMs_inclUid[$oldMM_key][2]);
						$elDelete = $oldMMs_inclUid[$oldMM_key];
					} else {
						if(is_array($mmItem)) {
							$removeClauses[] = 'tablenames="'.$mmItem[0].'" AND '.$uidForeign_field.'='.$mmItem[1];
						} else {
							$removeClauses[] = $uidForeign_field.'='.$mmItem;
						}
					}
					if ($this->MM_is_foreign)	{
						if(is_array($mmItem)) {
							$updateRefIndex_records[] = array($mmItem[0],$mmItem[1]);
						} else {
							$updateRefIndex_records[] = array($this->firstTable,$mmItem);
						}
					}
				}
				$deleteAddWhere = ' AND ('.implode(' OR ', $removeClauses).')';
				$GLOBALS['TYPO3_DB']->exec_DELETEquery($MM_tableName, $uidLocal_field.'='.intval($uid).$deleteAddWhere.$additionalWhere_tablenames.$additionalWhere);

					// Update ref index:
				foreach($updateRefIndex_records as $pair)	{
					$this->updateRefIndex($pair[0],$pair[1]);
				}
			}

				// Update ref index; In tcemain it is not certain that this will happen because if only the MM field is changed the record itself is not updated and so the ref-index is not either. This could also have been fixed in updateDB in tcemain, however I decided to do it here ...
			$this->updateRefIndex($this->currentTable,$uid);
			*/
		}
	}
}
?>