<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Alexander Bigga <linux@bigga.de>
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
 * ab_booking form functions
 *
 * @author	Alexander Bigga <linux@bigga.de>
 * @package	TYPO3
 * @subpackage	tx_abbooking
 */
class tx_abbooking_Hook_Flexform {

	/**
	 * Hook function of tcemain
	 *
	 * We cleanup flexform after saving to database because TYPO3 has
	 * only in this case a cleanFlexFormXML-function().
	 * If we don't cleanup the flexform, a lot of old configuration may
	 * confuse the extension because it remains untouched
	 *
	 * @return void
	 */


	function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $pObj) {
		// only check changes in tt_content with ab_booking_pi1 and status update:
		if ($table === 'tt_content'
			&& $status == 'update') {
			$recRow = t3lib_BEfunc::getRecordRaw('tt_content','uid=' . intval($id));
			// if new record --> getRecordRaw will return FALSE
			if ($recRow == FALSE && $recRow['list_type'] != 'ab_booking_pi1')
				return;

			$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
			// clean flexform and save to XML-Structure ready to save to database
			$xml = $flexObj->cleanFlexFormXML('tt_content', 'pi_flexform', $recRow);

			$fields = array('pi_flexform' => $xml);
			$where = 'uid = '. intval($id) .' ';
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fields);

		return;
		}
	}

}
?>
