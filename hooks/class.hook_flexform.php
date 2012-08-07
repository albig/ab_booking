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
	 * @param array &$fieldArray Array of changed values
	 * @param array $table current table
	 * @param array $id uid of the table row
	 * @param array &$pObj
	 * @return void
	 */

	function processDatamap_preProcessFieldArray(&$fieldArray, $table, $id, &$pObj) {
		// only check changes in tt_content
		if ($table === 'tt_content' && $fieldArray['list_type'] === 'ab_booking_pi1') {
			$recRow = t3lib_BEfunc::getRecordRaw('tt_content','uid='.$id);

			$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
			// clean flexform and save to XML-Structure ready to save to database
			$xml = $flexObj->cleanFlexFormXML('tt_content', 'pi_flexform', $recRow);
			// convert to array for easier comparisson
			$cleanFlexArray = t3lib_div::xml2array($xml);
			// compare the old and new pluginSelection value:
			// if the onChange Selection has changed, don't cleanup Flexform
			// otherwise always the same flexform is shown as onChange doesn't work anymore
			if ($cleanFlexArray['data']['sheetPluginOptions']['lDEF']['pluginSelection']['vDEF'] ==
				$fieldArray['pi_flexform']['data']['sheetPluginOptions']['lDEF']['pluginSelection']['vDEF']) {
				$fieldArray['pi_flexform'] = $xml;
			}
		}
      }
}
?>
