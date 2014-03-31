<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Alexander Bigga <linux@bigga.de>
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
 * Update class for the extension manager.
 *
 * @package TYPO3
 * @subpackage tx_abbooking
 */
class ext_update {
	const STATUS_WARNING = -1;
	const STATUS_ERROR = 0;
	const STATUS_OK = 1;

	protected $messageArray = array();

	/**
	 * Main update function called by the extension manager.
	 *
	 * @return string
	 */
	public function main() {
		$this->processUpdates();
		return $this->generateOutput();
	}

	/**
	 * Called by the extension manager to determine if the update menu entry
	 * should by showed.
	 *
	 * @return bool
	 * @todo find a better way to determine if update is needed or not.
	 */
	public function access() {
		return TRUE;
	}

	/**
	 * The actual update function. Add your update task in here.
	 *
	 * @return void
	 */
	protected function processUpdates() {



		$this->renameFlexformField('ab_booking_pi1', array('sheetGeneralOptions', 'what_to_display'), array('sheetPluginOptions', 'pluginSelection'));

		$this->renameFlexformField('ab_booking_pi1', array('sheetCalendarOptions', 'enableCalendarBookingLink'), array('sheetPluginOptions', 'enableBookingLinkCalendar'));
		$this->renameFlexformField('ab_booking_pi1', array('sheetCalendarOptions', 'showDateNavigator'), array('sheetPluginOptions', 'showDateNavigator'));
		$this->renameFlexformField('ab_booking_pi1', array('sheetCalendarOptions', 'numMonths'), array('sheetPluginOptions', 'numMonths'));
		$this->renameFlexformField('ab_booking_pi1', array('sheetCalendarOptions', 'numMonthsCols'), array('sheetPluginOptions', 'numMonthsCols'));
		$this->renameFlexformField('ab_booking_pi1', array('sheetCalendarOptions', 'showBookingRate'), array('sheetPluginOptions', 'showBookingRate'));

		$this->renameFlexformField('ab_booking_pi1', array('sheetBookingOptions', 'EmailAddress'), array('sheetPluginOptions', 'EmailAddress'));
		$this->renameFlexformField('ab_booking_pi1', array('sheetBookingOptions', 'EmailRealname'), array('sheetPluginOptions', 'EmailRealname'));
		$this->renameFlexformField('ab_booking_pi1', array('sheetBookingOptions', 'textSayThankYou'), array('sheetPluginOptions', 'textSayThankYou'));
		$this->renameFlexformField('ab_booking_pi1', array('sheetBookingOptions', 'textConfirmEmail'), array('sheetPluginOptions', 'textConfirmEmail'));

	}



	/**
	 * Renames a flex form field
	 *
	 * @param  string $pluginName The pluginName used in list_type
	 * @param  array $oldFieldPointer Pointer array the old field. E.g. array('sheetName', 'fieldName');
	 * @param  array $newFieldPointer  Pointer array the new field. E.g. array('sheetName', 'fieldName');
	 * @return void
	 */
	protected function renameFlexformField($pluginName, array $oldFieldPointer, array $newFieldPointer) {
		$title = 'Renaming flexform field for "' .  $pluginName . '" - ' .
			' sheet: ' . $oldFieldPointer[0] . ', field: ' .  $oldFieldPointer[1] . ' to ' .
			' sheet: ' . $newFieldPointer[0] . ', field: ' .  $newFieldPointer[1];

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, pi_flexform',
			'tt_content',
			'CType=\'list\' AND list_type=\'' . $pluginName . '\'');

		$flexformTools = t3lib_div::makeInstance('t3lib_flexformtools');

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			$xmlArray = t3lib_div::xml2array($row['pi_flexform']);
//~ print_r($xmlArray);
			if (!is_array($xmlArray) || !isset($xmlArray['data'])) {
				$status = t3lib_FlashMessage::ERROR;
					// @todo: This will happen when trying to update news2 > news but pluginName is already set to news
					// proposal for future: check for news2 somehow?
				$message = 'Flexform data of plugin "' . $pluginName . '" not found.';
			} elseif (!$xmlArray['data'][$oldFieldPointer[0]]) {
				$status = t3lib_FlashMessage::WARNING;
				$message = 'Flexform data of record tt_content:' . $row['uid'] . ' did not contain ' .
					'sheet: ' . $oldFieldPointer[0];
			} else {
				$updated = FALSE;

				foreach ($xmlArray['data'][$oldFieldPointer[0]] as $language => $fields) {
					if ($fields[$oldFieldPointer[1]]) {

						// wtf: why do I need to do a foreach here? why can't I access the key directly?
						foreach ($fields[$oldFieldPointer[1]] as $vdev => $vv) {
							switch ($vv) {
								case 'CALENDAR':
								case 'CALENDAR LINE':
									$xmlArray['data'][$newFieldPointer[0]][$language][$newFieldPointer[1]] = array('vDEF' => 0);
									break;
								case 'AVAILABILITY CHECK':
									$xmlArray['data'][$newFieldPointer[0]][$language][$newFieldPointer[1]] = array('vDEF' => 1);
									break;
								case 'BOOKING':
									$xmlArray['data'][$newFieldPointer[0]][$language][$newFieldPointer[1]] = array('vDEF' => 2);
									break;
								case 'CHECKIN OVERVIEW':
									$xmlArray['data'][$newFieldPointer[0]][$language][$newFieldPointer[1]] = array('vDEF' => 4);
									break;
								case 'BOOKING RATE OVERVIEW':
									$xmlArray['data'][$newFieldPointer[0]][$language][$newFieldPointer[1]] = array('vDEF' => 5);
									break;
								default:
									$xmlArray['data'][$newFieldPointer[0]][$language][$newFieldPointer[1]] = $fields[$oldFieldPointer[1]];
							}
						}

						//~ unset($xmlArray['data'][$oldFieldPointer[0]][$language][$oldFieldPointer[1]]);
						$xmlArray['data'][$oldFieldPointer[0]][$language][$oldFieldPointer[1]] = '';

						$updated = TRUE;
					}
				}

				if ($updated === TRUE) {

					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'uid=' . $row['uid'], array(
						'pi_flexform' => $flexformTools->flexArray2Xml($xmlArray)
					));

//~ 			$recRow = t3lib_BEfunc::getRecordRaw('tt_content','uid=' . $row['uid']);
//~ 			// clean flexform and save to XML-Structure ready to save to database
//~ 			$xml = $flexformTools->cleanFlexFormXML('tt_content', 'pi_flexform', $recRow);
//~
//~ 			$fields = array('pi_flexform' => $xml);
//~ 			$where = 'uid = '. $row['uid'] .' ';
//~ 			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', $where, $fields);
//~
//~


					$message = 'OK!';
					$status = t3lib_FlashMessage::OK;
				} else {
					$status = t3lib_FlashMessage::NOTICE;
					$message = 'Flexform data of record tt_content:' . $row['uid'] . ' did not contain ' .
						'sheet: ' . $oldFieldPointer[0] . ', field: ' .  $oldFieldPointer[1] . '. This can
						also be because field has been updated already...';
				}
			}

			$this->messageArray[] = array($status, $title, $message);
		}
	}


	/**
	 * Generates output by using flash messages
	 *
	 * @return string
	 */
	protected function generateOutput() {
		$output = '';
		foreach ($this->messageArray as $messageItem) {
			$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$messageItem[2],
					$messageItem[1],
					$messageItem[0]);
			$output .= $flashMessage->render();
		}

		return $output;
	}

}
