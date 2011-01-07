<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 - 2010 Alexander Bigga <linux@bigga.de>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   62: class tx_abbooking_pi1 extends tslib_pibase
 *   74:     function main($content, $conf)
 *  239:     function init()
 *  361:     public function request_form($conf, $product, $stage)
 *  571:     public function availability_form()
 *  665:     function print_request_overview($conf)
 *  800:     public function get_product_properties($ProductUID)
 *  879:     function check_availability($storagePid)
 *  945:     function check_form_input()
 * 1027:     function log_request($logFile)
 * 1051:     function send_confirmation_email($key, &$send_errors)
 * 1127:     function insert_booking($request)
 * 1182:     function calcRates($key, $maxAvailable)
 * 1277:     function printCalculatedRates($key, $period, $printHTML = 1)
 * 1335:     function isRobot()
 *
 * TOTAL FUNCTIONS: 14
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Plugin 'Booking Calendar' for the 'ab_booking' extension.
 *
 * @author	Alexander Bigga <linux@bigga.de>
 * @package	TYPO3
 * @subpackage	tx_abbooking
 */
require_once(t3lib_extMgm::extPath('ab_booking').'lib/class.tx_abbooking_div.php'); // load div

class tx_abbooking_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_abbooking_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_abbooking_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'ab_booking';	// The extension key.

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The		content that is displayed on the website
	 */
	function main($content, $conf) {

		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;

		if (t3lib_extMgm::isLoaded('date2cal', 0)) {
			include_once(t3lib_extMgm::extPath('date2cal') . '/src/class.jscalendar.php');
		}
		if (t3lib_extMgm::isLoaded('ab_swiftmailer', 0)) {
			require_once(t3lib_extMgm::extPath('ab_swiftmailer').'pi1/class.tx_abswiftmailer_pi1.php'); // load swift lib
		}

		$this->cssBooking = str_replace(PATH_site,'',t3lib_div::getFileAbsFileName($this->conf['file.']['cssBooking']));
                $GLOBALS['TSFE']->additionalHeaderData['abbooking_css'] = '<link href="'.$this->cssBooking.'" rel="stylesheet" type="text/css" />'."\n";

		// get all initial settings
		$this->init();

		switch ( $this->lConf['mode'] ) {
			case 'form':
				// check first for submit button and second for View
				if (isset ($this->piVars['submit_button_edit']) && $this->lConf['ABdo'] == 'bor3')
					$this->lConf['ABdo'] = 'bor0';

				$this->check_availability($this->lConf['PIDstorage']);

				// in case of the booking request formular only
				// one product is allowed at a time:
				foreach ( $this->lConf['productDetails'] as $key => $val ) {
					$product = $val;
					break;
				}

				switch ( $this->lConf['ABdo'] ) {
					case 'availabilityList':
						// DEBUG - log requests only if enableDebug in extConf is selected
						if ($this->lConf['enableDebug'] == 1) {
							$this->log_request($this->lConf['debugLogFile']);
							$this->insert_booking(1);
						}

						$offers = tx_abbooking_div::printOfferList();

						/* ------------------------- */
						/* list available items      */
						/* ------------------------- */
						if ($this->lConf['startDateStamp'] < (time()-86400) ) {
							$this->form_errors['startDateInThePast'] = $this->pi_getLL('error_startDateInThePast');
						} else if (!isset($this->form_errors['endDateTooFarInFuture'])) {

							$out .= '<div class=offer>';
							if ($offers['numOffers']>0)
								$out .= '<p class="offer">'.$this->pi_getLL('we_may_offer').'</p>';
							else
								$out .= '<p class="offer">'.$this->pi_getLL('no_offer').'</p>';
							$out .= '<p>'.strftime("%A, %d.%m.%Y", $this->lConf['startDateStamp']).' - ';
							$out .= ' '.strftime("%A, %d.%m.%Y", $this->lConf['endDateStamp']).'</p><br />';
							$out .= '<p>'.$this->pi_getLL('feld_naechte').': '.$this->lConf['numNights'].'</p>';
							if ($this->lConf['showPersonsSelector']==1)
								$out .= '<p>'.$this->pi_getLL('feld_personen').': '.$this->lConf['numPersons'].'</p>';
							$out .= '<ul>';
							for ($i=0; $i<=$offers['amount']; $i++)
								$out .= $offers[$i];
							$out .= '</ul>';
							$out .= '</div>';

							return $this->pi_wrapInBaseClass($out);
						}
						$out .= $this->availability_form();
						return $this->pi_wrapInBaseClass($out);
						break;
					case 'bor0':
						/* ------------------------- */
						/* booking request formular  */
						/* ------------------------- */
						$out .= $this->request_form($conf, $product, $stage = 1);
						break;
					case 'bor1':
						$out .= '<p class=available><b>'.$this->pi_getLL('result_available').'</b>';
						$out .= ' '.strftime("%A, %d.%m.%Y", $this->lConf['startDateStamp']).' - ';
						$availableMaxDate = strtotime('+ '.$product['maxAvailable'].' days', $this->lConf['startDateStamp']);
						$out .= ' '.strftime("%A, %d.%m.%Y", $availableMaxDate);
						$out .= '</p><br />';

						$out .= $this->request_form($conf, $product, $stage = 1);
						break;
					case 'bor2':
						$numErrors = $this->check_form_input();
						if ($numErrors > 0) {
							$out .= $this->request_form($conf, $product, $stage = 1);
						}
						else {
							$out .= $this->request_form($conf, $product, $stage = 3);
						}
						break;
					case 'bor3':
						/* --------------------------- */
						/* booking final - send mails  */
						/* --------------------------- */
						$numErrors = $this->check_form_input();
						if ($numErrors == 0) {
							$out .= tx_abbooking_div::printBookingStep($stage = 4);
							$result= $this->send_confirmation_email($product['uid'], $send_errors);
							if ($result == 2) {
								if (isset($this->lConf['textSayThankYou']))
									$out .= '<p>'.nl2br($this->lConf['textSayThankYou']).'</p>';
								else
									$out .= '<p>'.nl2br($this->pi_getLL('send_success')).'</p>';

								// only insert booking if successfully sent both mails
								$this->insert_booking(0);
							} else {
								$out .= '<p><b>'.nl2br($this->pi_getLL('send_failure')).'</b><br />'.$result.'</p>';
								$out .= "<br/>&nbsp;<br/>";
								$out .= $send_errors;
							}

						} else {
							$out .= $this->request_form($conf, $product, $stage = 2);
						}

						break;
					default:
						/* ------------------------- */
						/* show calendar             */
						/* ------------------------- */
					}
					return $this->pi_wrapInBaseClass($out);
				break;

			case 'display':
			default:
				switch ($this->lConf['what_to_display']) {
					case 'AVAILABILITY CHECK':
						$out .= $this->availability_form();
						break;
					case 'CALENDAR':
						$out .= tx_abbooking_div::printAvailabilityCalendar($this->lConf['ProductID']);
						break;
					case 'CALENDAR LINE':
						$out .=tx_abbooking_div::printAvailabilityCalendarLine($this->lConf['ProductID']);
						break;
					case 'REQUEST':
						$out .= $this->print_request_overview($conf);
						break;
					default:
						/* ------------------------- */
						/* show calendar             */
						/* ------------------------- */
						$out .= tx_abbooking_div::printAvailabilityCalendar($this->lConf['ProductID']);
						break;
				}
				break;
		}

	return $this->pi_wrapInBaseClass($out);

	}
	/**
	 * initializes the flexform and all config options ;-)
	 *
	 * @return	empty
	 */
	function init() {
		$this->extConf = array();
		$this->pi_initPIflexForm(); // Init and get the flexform data of the plugin
		$this->lConf = array(); // Setup our storage array...
		// Assign the flexform data to a local variable for easier access
		$piFlexForm = $this->cObj->data['pi_flexform'];


		// Traverse the entire array based on the language...
		// and assign each configuration option to $this->lConf array...
		if (sizeof($piFlexForm)>0)
			foreach ( $piFlexForm['data'] as $sheet => $data ) {
				foreach ( $data as $lang => $value ) {
					foreach ( $value as $key => $val ) {
						$this->lConf[$key] = $this->pi_getFFvalue($piFlexForm, $key, $sheet);
					}
				}
			}

		// get global extConf settings
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ab_booking']);

		// add global extConf settings to lConf array
		if (isset($this->extConf))
			foreach ( $this->extConf as $key => $val ) {
				$this->lConf[$key] = $val;
			}

		$this->lConf['uidpid'] = $this->cObj->data['uid'].$this->cObj->data['pid'];

		// ---------------------------------
		// check GET/POST data
		// ---------------------------------
		if (isset($this->piVars['ABdo']))
			$this->lConf['ABdo'] = $this->piVars['ABdo'];
		if (isset($this->piVars['ProductID']))
			$this->lConf['ProductID'] = $this->piVars['ProductID'];

		if (isset($this->piVars['ABx'])) {
			list($this->lConf['startDateStamp'], $this->lConf['numNights'], $this->lConf['numPersons'], $this->lConf['ABProductID'], $this->lConf['ABuidpid'], $this->lConf['PIDbooking'], $this->lConf['ABdo']) = explode("_", $this->piVars['ABx']);
		}

		// overwrite some settings if post-vars are set:
		if (isset($this->piVars['ABstartDate']))
			$this->lConf['startDateStamp'] = strtotime($this->piVars['ABstartDate']);
		if (isset($this->piVars['ABnumNights']))
			$this->lConf['numNights'] = $this->piVars['ABnumNights'];
		if (isset($this->piVars['ABnumPersons']))
			$this->lConf['numPersons'] = $this->piVars['ABnumPersons'];

		if (isset($this->piVars['ABuidpid']))
			$this->lConf['ABuidpid'] = $this->piVars['ABuidpid'];
		if (isset($this->lConf['ABProductID']))
			$this->lConf['ProductID'] = $this->lConf['ABProductID'];

		// ---------------------------------
		// calculate endDateStamp
		// ---------------------------------
		if (empty($this->lConf['startDateStamp']))
			$this->lConf['startDateStamp'] = strtotime(strftime("%Y-%m-%d"));
		if (empty($this->lConf['numNights']))
			$this->lConf['endDateStamp'] = strtotime('+ 14 days', $this->lConf['startDateStamp']);
		else
			$this->lConf['endDateStamp'] =  strtotime('+ '.$this->lConf['numNights'].' days', $this->lConf['startDateStamp']);

		if (!isset($this->lConf['ABdo']))
			$this->lConf['mode'] = 'display';
		else {
			// check if formular or display mode:
			if (($this->lConf['what_to_display'] == 'BOOKING') &&
			      ($this->lConf['uidpid'] == $this->lConf['ABuidpid'] ||
				$this->lConf['PIDbooking'] == $this->cObj->data['pid'])) {
				$this->lConf['mode'] = 'form';
				// overwrite flexform setting
				if (isset($this->lConf['ABProductID']))
					$this->lConf['ProductID'] = $this->lConf['ABProductID'];
			} else
				$this->lConf['mode'] = 'display';
		}
		// ---------------------------------
		// check flexform data
		// ---------------------------------
		// maximum of availability check
		if (! isset($this->lConf['maxavailable']))
			$this->lConf['maxavailable'] = $this->lConf['numCheckMaxInterval'];

		if (is_numeric($this->lConf['PIDbooking']))
			$this->lConf['gotoPID'] = $this->lConf['PIDbooking'];
		else {
			$this->lConf['gotoPID'] = $GLOBALS['TSFE']->id;
		}


		// get the storage pid from flexform
		if (! intval($this->lConf['PIDstorage'])>0) {
			$storagePid = $GLOBALS['TSFE']->getStorageSiterootPids();
			$this->lConf['PIDstorage'] = $storagePid['_STORAGE_PID'];
		}

 		if (isset($this->lConf['ProductID'])) {
			$this->lConf['productDetails'] = $this->get_product_properties($this->lConf['ProductID']);
			// merge array of available and offtime product IDs
			$this->lConf['ProductID'] = implode(',', array_unique(array_merge(explode(',', $this->lConf['ProductID']), $this->lConf['OffTimeProductIDs'])));
		}

		if (intval($this->conf['showPrice']) > 0)
			$this->lConf['showPrice'] = $this->conf['showPrice'];
		if (intval($this->conf['showPriceDetails']) > 0)
			$this->lConf['showPriceDetails'] = $this->conf['showPriceDetails'];

	}


	/**
	 * Request Formular
	 * The customer enters his address and sends the form.
	 *
	 * @param	[type]		$conf: ...
	 * @param	integer		$stage of booking process
	 * @param	[type]		$stage: ...
	 * @return	HTML		form with booking details
	 */
	public function request_form($conf, $product, $stage) {

		$interval = array();

		if (empty($product)) {
			$content = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
			return $content;
		}

		$interval['startDate'] = $this->lConf['startDateStamp'];
		$interval['endDate'] = $this->lConf['endDateStamp'];
		$interval['startList'] = strtotime('-2 day', $interval['startDate']);
		$interval['endList'] = strtotime('+10 day', $interval['startDate']);

		$content .= tx_abbooking_div::printAvailabilityCalendarLine($this->lConf['ProductID'], $interval);

		$content .= tx_abbooking_div::printBookingStep($stage);

		$content.='<div class="requestForm">';


		$content.='<h3>'.htmlspecialchars($this->pi_getLL('title_request')).' '.$product['detailsRaw']['header'].'</h3>';

		$selected='selected="selected"';
		if (isset($this->lConf['numPersons']))
			if ($this->lConf['numPersons'] > $product['capacitymax'])
				$selNumPersons[$product['capacitymax']] = $selected;
			else
				$selNumPersons[$this->lConf['numPersons']] = $selected;
		else
			$selNumPersons[2] = $selected;

		if (isset($this->lConf['numNights']))
			$selNumNights[$this->lConf['numNights']] = $selected;
		else
			$selNumNights[2] = $selected;

		// if stage=0 forget all errors!
		if ($stage > 0) {
			/* handle errors */
			if (isset($this->form_errors['email'])) {
				$ErrorEmail='class="error"';
				$content.='<h2><b>'.$this->form_errors['email'].'</b></h2>';
			}
			if (isset($this->form_errors['name'])) {
				$ErrorName='class="error"';
				$content.='<h2><b>'.$this->form_errors['name'].'</b></h2>';
			}
			if (isset($this->form_errors['street'])) {
				$ErrorStreet='class="error"';
				$content.='<h2><b>'.$this->form_errors['street'].'</b></h2>';
			}
			if (isset($this->form_errors['town'])) {
				$ErrorTown='class="error"';
				$content.='<h2><b>'.$this->form_errors['town'].'</b></h2>';
			}
			if (isset($this->form_errors['PLZ'])) {
				$ErrorPLZ='class="error"';
				$content.='<h2><b>'.$this->form_errors['PLZ'].'</b></h2>';
			}
			if (isset($this->form_errors['vacancies'])) {
				$ErrorVacancies='class="error"';
				$content.='<h2><b>'.$this->form_errors['vacancies'].'</b></h2>';
			}
			if (isset($this->form_errors['vacancies_limited'])) {
				$ErrorVacanciesLimited='class="error"';
				$content.='<h2><b>'.$this->form_errors['vacancies_limited'].'</b></h2>';
			}
			if (isset($this->form_errors['startDateInThePast'])) {
				$ErrorVacancies='class="error"';
				$content.='<h2><b>'.$this->form_errors['startDateInThePast'].'</b></h2>';
			}
			if (isset($this->form_errors['endDateNotValid'])) {
				$ErrorVacanciesLimited='class="error"';
				$content.='<h2><b>'.$this->form_errors['endDateNotValid'].'</b></h2>';
			}
			if (isset($this->form_errors['numNightsNotValid'])) {
				$ErrorVacanciesLimited='class="error"';
				$content.='<h2><b>'.$this->form_errors['numNightsNotValid'].'</b></h2>';
			}
		}

		// check if configured email is present
		if ((!class_exists('tx_abswiftmailer_pi1') || !$this->lConf['useSwiftMailer']) && empty($this->lConf['EmailAddress'])) {
			$content.= '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noEmailConfigured').'</b></h2>';
		}

		/* handle stages */
		if ($stage == 3) {
			$content.='<h2><b>'.htmlspecialchars($this->pi_getLL('please_confirm')).'</b></h2>';
// 			$ReadOnly='class=readonly readonly="readonly"';
			$SubmitButtonEdit=htmlspecialchars($this->pi_getLL('submit_button_edit'));
			$SubmitButton=htmlspecialchars($this->pi_getLL('submit_button_final'));

			$content.='<form action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">
					<b>'.htmlspecialchars($this->pi_getLL('feld_name')).'</b>
					<p class="yourSettings">'.htmlspecialchars($this->piVars['name']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[name]" value="'.htmlspecialchars($this->piVars['name']).'" >
					<p class="yourSettings">'.htmlspecialchars($this->piVars['street']).'</p>
					<input  type="hidden" name="'.$this->prefixId.'[street]" value="'.htmlspecialchars($this->piVars['street']).'" >
					<p class="yourSettings">'.htmlspecialchars($this->piVars['plz']).' '.htmlspecialchars($this->piVars['town']).'</p>
					<input  type="hidden" size="5" maxlength="10" name="'.$this->prefixId.'[plz]" value="'.htmlspecialchars($this->piVars['plz']).'" >
					<input  type="hidden" name="'.$this->prefixId.'[town]" value="'.htmlspecialchars($this->piVars['town']).'">
					<p class="yourSettings">'.htmlspecialchars($this->piVars['email']).'</p>
					<input  type="hidden" name="'.$this->prefixId.'[email]" value="'.htmlspecialchars($this->piVars['email']).'" >
					<p class="yourSettings">'.htmlspecialchars($this->piVars['telefon']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[telefon]" value="'.htmlspecialchars($this->piVars['telefon']).'" >

					<b>'.htmlspecialchars($this->pi_getLL('feld_anreise')).'</b>
					<p class="yourSettings">'.strftime("%A, %d.%m.%Y", $this->lConf['startDateStamp']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[ABstartDateStamp]" value="'.$this->lConf['startDateStamp'].'" >

					<b>'.htmlspecialchars($this->pi_getLL('feld_abreise')).'</b>
					<p class="yourSettings">'.strftime("%A, %d.%m.%Y", $this->lConf['endDateStamp']).'</p>

					<b>'.htmlspecialchars($this->pi_getLL('feld_naechte')).':</b>
					<p class="yourSettings">'.htmlspecialchars($this->piVars['ABnumNights']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[ABnumNights]" value="'.htmlspecialchars($this->lConf['numNights']).'" >

					<b>'.htmlspecialchars($this->pi_getLL('feld_personen')).':</b>
					<p class="yourSettings">'.htmlspecialchars($this->piVars['ABnumPersons']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[ABnumPersons]" value="'.htmlspecialchars($this->piVars['ABnumPersons']).'" >

					'.htmlspecialchars($this->pi_getLL('feld_mitteilung')).'
					<p class="yourSettings">'.htmlspecialchars($this->piVars['mitteilung']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[mitteilung]" value="'.$this->piVars['mitteilung'].'">';

					$content .= $this->printCalculatedRates($product['uid'], $this->piVars['ABnumNights'], 1);

					$params_united = $this->lConf['startDateStamp'].'_'.$this->lConf['numNights'].'_'.$this->lConf['numPersons'].'_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor'.($stage);
					$params = array (
						$this->prefixId.'[ABx]' => $params_united,
					);

					$content .= '<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'">';
					$content .= '<input type="hidden" name="'.$this->prefixId.'[ABwhatToDisplay]" value="BOOKING">
							<div class="buttons">
							<input class="edit" type="submit" name="'.$this->prefixId.'[submit_button_edit]" value="'.$SubmitButtonEdit.'">
							<input class="submit_final" type="submit" name="'.$this->prefixId.'[submit_button]" value="'.$SubmitButton.'">
							</div>
				</form>
			';
		}
		else {
			$SubmitButton=htmlspecialchars($this->pi_getLL('submit_button_check'));

			if (isset($this->lConf['startDateStamp']))
				$startdate = $this->lConf['startDateStamp'];
			else
				$startdate = time();

			$content.='<form action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">
					<b>'.htmlspecialchars($this->pi_getLL('feld_name')).'</b><br/>
					<input '.$ErrorName.' type="text" name="'.$this->prefixId.'[name]" value="'.htmlspecialchars($this->piVars['name']).'" ><br/>

					<b>'.htmlspecialchars($this->pi_getLL('feld_street')).'</b><br/>
					<input '.$ErrorStreet.' type="text" name="'.$this->prefixId.'[street]" value="'.htmlspecialchars($this->piVars['street']).'" ><br/>

					<b>'.htmlspecialchars($this->pi_getLL('feld_plz')).' '.htmlspecialchars($this->pi_getLL('feld_town')).'</b><br/>
					<input '.$ErrorPLZ.' type="text" size="5" maxlength="10" name="'.$this->prefixId.'[plz]" value="'.htmlspecialchars($this->piVars['plz']).'" >
					<input '.$ErrorTown.' type="text" name="'.$this->prefixId.'[town]" value="'.htmlspecialchars($this->piVars['town']).'"><br/>

					<b>'.htmlspecialchars($this->pi_getLL('feld_email')).'</b><br/>
					<input '.$ErrorEmail.' type="text" name="'.$this->prefixId.'[email]" value="'.htmlspecialchars($this->piVars['email']).'" ><br/>
					'.htmlspecialchars($this->pi_getLL('feld_telefon')).'<br/><input type="text" name="'.$this->prefixId.'[telefon]" value="'.htmlspecialchars($this->piVars['telefon']).'" ><br/>
					<b>'.htmlspecialchars($this->pi_getLL('feld_anreise')).'</b><br/>';
			$content .= tx_abbooking_div::getJSCalendarInput($this->prefixId.'[ABstartDate]', $startdate, $ErrorVacancies);
			$content .= '<br/>
					<b>'.htmlspecialchars($this->pi_getLL('feld_naechte')).'</b><br/>
						<select '.$ErrorVacanciesLimited.' name="'.$this->prefixId.'[ABnumNights]" size="1">';
					/* how many days/nights are available? */
					for ($i = 1; $i<=$product['maxAvailable']; $i++) {
							$endDate = strtotime('+'.$i.' day', $startdate);
							$content.='<option '.$selNumNights[$i].' value='.$i.'>'.$i.' ('.strftime('%d.%m.%Y', $endDate).')</option>';
					}
					$content .= '</select><br/>
					<b>'.htmlspecialchars($this->pi_getLL('feld_personen')).'</b><br/>
						<select name="'.$this->prefixId.'[ABnumPersons]" size="1">';
					/* how many persons are possible? */
					for ($i = 1; $i<=$product['capacitymax']; $i++) {
						if ($this->lConf['maxavailable'] < $this->piVars['ABnumNights'])
							$numNights = $this->lConf['maxavailable'];
						else
							$numNights = $this->piVars['ABnumNights'];
						$content.='<option '.$selNumPersons[$i].' value='.$i.'>'.$i.' </option>';
					}

					$params_united = '0_0_0_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor'.($stage + 1);
					$params = array (
						$this->prefixId.'[ABx]' => $params_united,
					);

					$content .= '</select><br/>';
					$content .= '<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'">';
					$content .= 	htmlspecialchars($this->pi_getLL('feld_mitteilung')).'<br/>
							<textarea name="'.$this->prefixId.'[mitteilung]" rows=5 cols=30 wrap="PHYSICAL">'.htmlspecialchars($this->piVars['mitteilung']).'</textarea><br/>
							<input type="hidden" name="'.$this->prefixId.'[ABwhatToDisplay]" value="BOOKING"><br/>
							<input class="submit" type="submit" name="'.$this->prefixId.'[submit_button]" value="'.$SubmitButton.'">
				</form>
				<br />
			';
			}
		$content.='</div>';
		return $content;
	}
	/**
	 * availability prices form ;-)
	 *
	 * @return	HTML		form to check availability
	 */
	public function availability_form() {

		if (empty($this->lConf['productDetails'])) {
			$content = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
			return $content;
		} else {
			foreach ( $this->lConf['productDetails'] as $key => $val ) {
				$overallCapacity += $val['capacitymax'];
			}
		}

		$selected='selected="selected"';
		if (isset($this->lConf['numPersons']))
			$selNumPersons[$this->lConf['numPersons']] = $selected;
		else
			$selNumPersons[2] = $selected;

		if (isset($this->lConf['numNights']))
			$selNumNights[$this->lConf['numNights']] = $selected;
		else
			$selNumNights[2] = $selected;

		if (isset($this->form_errors['vacancies_limited'])) {
			$ErrorVacanciesLimited='class="error"';
			$content.='<h2><b>'.$this->form_errors['vacancies_limited'].'</b></h2>';
		}
		if (isset($this->form_errors['vacancies'])) {
			$ErrorVacancies='class="error"';
			$content.='<h2><b>'.$this->form_errors['vacancies'].'</b></h2>';
		}
		if (isset($this->form_errors['startDateInThePast'])) {
			$ErrorVacancies='class="error"';
			$content.='<h2><b>'.$this->form_errors['startDateInThePast'].'</b></h2>';
			// reset ABstartDate
			unset($this->lConf['startDate']);
			unset($this->lConf['startDateStamp']);
		}
		if (isset($this->form_errors['endDateTooFarInFuture'])) {
			$ErrorVacancies='class="error"';
			$content.='<h2><b>'.$this->form_errors['endDateTooFarInFuture'].'</b></h2>';
		}

		$content .='<form action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">
				<label for="'.$this->prefixId.'[ABstartDate]'.'_hr"><b>'.htmlspecialchars($this->pi_getLL('feld_anreise')).'</b></label>
				<label for="'.$this->prefixId.'[ABstartDate]'.'_cb">&nbsp;</label><br/>';

		if (isset($this->lConf['startDateStamp']))
			$startdate = $this->lConf['startDateStamp'];
		else
			$startdate = time();

		$content .= tx_abbooking_div::getJSCalendarInput($this->prefixId.'[ABstartDate]', $startdate, $ErrorVacancies);

		$content .= '<br />
				<label for="fieldNumNights"><b>'.htmlspecialchars($this->pi_getLL('feld_naechte')).'</b></label><br/>
				<select '.$ErrorVacanciesLimited.' name="'.$this->prefixId.'[ABnumNights]" id="fieldNumNights" size="1">';
		for ($i = 1; $i<=$this->lConf['maxavailable']; $i++) {
			$content.='<option '.$selNumNights[$i].' value='.$i.'>'.$i.'</option>';
		}
		$content .= '</select><br/>';

		if ($this->lConf['showPersonsSelector'] == 1) {
			$content .= '<label for="fieldNumPersons"><b>'.htmlspecialchars($this->pi_getLL('feld_personen')).'</b></label><br/>
					<select name="'.$this->prefixId.'[ABnumPersons]" id="fieldNumPersons" size="1">';
			/* how many persons are possible? */
			for ($i = 1; $i<=$overallCapacity; $i++) {
					$content.='<option '.$selNumPersons[$i].' value='.$i.'>'.$i.'</option>';
			}
			$content .= '</select><br/>';
		} else
			$content .= '<input type="hidden" name="'.$this->prefixId.'[ABnumPersons]" value="'.$this->lConf['numDefaultPersons'].'">';

		$params_united = '0_0_0_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_availabilityList';
		$params = array (
			$this->prefixId.'[ABx]' => $params_united,
		);

		$content .= '
		<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'"><br />';
		if (!$this->isRobot())
			$content .= '<input class="submit" type="submit" name="'.$this->prefixId.'[submit_button]" value="'.htmlspecialchars($this->pi_getLL('submit_button_label')).'">';
		$content .= '</form>
			<br />
		';

		return $content;
	}

	/**
	 * Display all Request
	 *
	 * @param	[type]		$conf: ...
	 * @return	HTML-table		of the bookings
	 */
	function print_request_overview($conf) {

		$this->pi_loadLL();
		$this->conf=$conf;
		$pid = array();
		$rows = (int)$this->lConf['numMonthsRows'];
		$columns = (int)$this->lConf['numMonthsCols'];
		$months = $this->lConf['numMonths'];

		$out .= '<table class="listlegend"><tr>';
		$out .= '<td class="vacant">&nbsp;</td><td class="legend">' . $this->pi_getLL('vacant day') .'</td>';
		$out .= '<td class="booked">&nbsp;</td><td class="legend">'.	$this->pi_getLL('booked day').' </td>';
		$out .= '</tr></table>';
		// get the years of booking
		$pidList = $this->pi_getPidList($this->cObj->data['pages'],$this->cObj->data['recursive']);

		// check what year and month will be in $months
		$m = ( date(m) + $months) % 12 ;
		$endmon = (int)(($m == 0) ? 12 : $m);
		$endyear = (int)date(Y) + (int)floor( ( date(m)  + $months ) / 12 )  ;

		$i = 0;

		// SELECT
		// 2. get for bookings for these uids/pids
		$myquery= 'pid IN ('. $pidList .') AND uid_foreign IN ('.$this->lConf['ProductID'].') AND deleted=1 AND hidden=0 AND request=1 AND uid=uid_local AND ( enddate >=(unix_timestamp(\''. (int)date(Y) .'-'. (int)date(m) .'-01\')) AND startdate <=(unix_timestamp(\''. $endyear .'-'. $endmon .'-01\')))';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT uid, startdate, enddate','tx_abbooking_booking, tx_abbooking_booking_productid_mm',$myquery,'','startdate','');

		// one array for start and end dates. one for each pid
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			for ($d = $row['startdate']; $d <= $row['enddate']; $d=strtotime("+ 1day", $d)) {
				$myBooked[$d]['booked']++ ;
			}
			$myBooked[$row['startdate']]['isStart'] = 1;
			$myBooked[$row['enddate']]['isEnd'] = 1;
		};
		$weekend = 0;

		$out .= '<table class="availabilityCalendar">';
		$out .= '<tr>';

		// runs for 18 times for 18 months
		for ($i=0; $i<$months; $i++) {
			$days = 0;
			if ($i % $columns == 0 && $i != 0 && $i != $months) {
				$out .= '</tr><tr>';
			}
			// adding leading zero
			$m = ( $i + date(m) ) % 12 ;
			$mon = (int)(($m == 0) ? 12 : $m);
			$year = (int)date(Y) + (int)( ( $i + date(m) - 1) / 12 ) ;

			$out .= '<td class="ABmonth">';
			$out .= '<h3 class="ABmonthname">'. $this->pi_getLL(date("M", strtotime( $year . "-".$mon."-01"))).' '. $year .'</h3>';
			$out .= '<table class="ABcalendar">';
			$out .= '<tr>
			<td class="DayTitle" title="'.$this->pi_getLL("Mon").'">'.$this->pi_getLL("Mo").'</td>
			<td class="DayTitle" title="'.$this->pi_getLL("Tus").'">'.$this->pi_getLL("Tu").'</td>
			<td class="DayTitle" title="'.$this->pi_getLL("Wed").'">'.$this->pi_getLL("We").'</td>
			<td class="DayTitle" title="'.$this->pi_getLL("Thu").'">'.$this->pi_getLL("Th").'</td>
			<td class="DayTitle" title="'.$this->pi_getLL("Fri").'">'.$this->pi_getLL("Fr").'</td>
			<td class="DayTitle" title="'.$this->pi_getLL("Sat").'">'.$this->pi_getLL("Sa").'</td>
			<td class="DayTitle" title="'.$this->pi_getLL("Sun").'">'.$this->pi_getLL("Su").'</td>
			</tr>';
			$out .= '<tr>';

			// calculating the left spaces to get the layout right
			for ($s = 0; $s < date('w', strtotime($year."-".$mon."-7")) ; $s++){
				$out .= '<td class="noDay">&nbsp;</td>';
				$days++;
			}
			for ($d=1; $d <= date("t", strtotime( $year . "-".$mon."-01")); $d++){
				if ($days % 7 == 0 && $days != 0 ) {
					$out .= '</tr><tr>';
				}

				// Weekend Check
				if (date("w", strtotime($year."-".$mon."-".$d))== 0 || date("w", strtotime($year."-".$mon."-".$d))== 6 )
					$weekend = 1;
				else
					$weekend = 0;

// ------------------ ab
				if ($weekend)
					$cssClass = "Weekend";
				else
					$cssClass = "Day";

				$numBooked = $myBooked[strtotime($year."-".$mon."-".$d)]['booked'];
				switch($numBooked) {
					case 0:
						$cssClass .= " vacant";
						break;
					default:
						$cssClass .= " booked";
						if ($myBooked[strtotime($year."-".$mon."-".$d)]['isStart'] == $numBooked &&
							$myBooked[strtotime($year."-".$mon."-".$d)]['isEnd'] != $numBooked)
							$cssClass .= " Start";

						else if ($myBooked[strtotime($year."-".$mon."-".$d)]['isStart'] != $numBooked &&
							$myBooked[strtotime($year."-".$mon."-".$d)]['isEnd'] == $numBooked)
							$cssClass .= " End";
						break;
				}

				$out .= '<td class="'.$cssClass.'">'.$d.':'.$myBooked[strtotime($year."-".$mon."-".$d)]['booked'].'</td>';

				// booking end
				$days++;
			}
			for (; $days < 42; $days++ ) {
				if ($days % 7 == 0) {
					$out .= '</tr><tr>';
				}
				$out .= '<td class="noDay">&nbsp;</td>';
				$out .= "\n";
			}
			$out .= '</tr>';
			$out .= '</table></td>';
			$out .= "\n";
		}

		$out .= '</tr></table>';

		return $this->pi_wrapInBaseClass($out);
	}

	/**
	 * get all properties of a product with the given UID
	 *
	 * @param	[type]		$storagePid: ...
	 * @param	[type]		$ProductUID: ...
	 * @return	[type]		...
	 */
	public function get_product_properties($ProductUID) {

		$availableProductIDs = array();
		$offTimeProductIDs = array();

		if (!empty($ProductUID)) {
			// SELECT:
			$myquery= 'pid='.$this->lConf['PIDstorage'].' AND uid IN ('.$ProductUID.') AND capacitymax>0 AND deleted=0 AND hidden=0';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title, capacitymin, capacitymax, priceid, uiddetails','tx_abbooking_product',$myquery,'','','');
			// one array for start and end dates. one for each pid
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
 				$product_properties[$row['uid']] = $row;
			};

			$pi = 0;
			// step through found products
			foreach ( $product_properties as $uid => $product ) {
				$availableProductIDs[$pi] = $uid;
				$pi++;
				// get prices if any
				if (!empty($product['priceid'])) {
					$myquery = 'tx_abbooking_price.pid='.$this->lConf['PIDstorage'].' AND tx_abbooking_price.uid IN ('.$product['priceid'].') AND tx_abbooking_price.deleted=0 AND tx_abbooking_price.hidden=0';
					$myquery .= ' AND uid_local=tx_abbooking_price.uid AND uid_foreign=tx_abbooking_seasons.uid';
					$myquery .= ' AND (tx_abbooking_seasons.starttime <'. $this->lConf['endDateStamp'].' AND tx_abbooking_seasons.endtime >= '.$this->lConf['startDateStamp'].' ';
					// check also for default rate without valid start and stopdate
					$myquery .= 'OR (tx_abbooking_seasons.starttime = 0 AND tx_abbooking_seasons.endtime = 0))';
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_abbooking_seasons.starttime as starttime, tx_abbooking_seasons.endtime as endtime, tx_abbooking_price.title as title, currency, adult1, adult2, adult3, adult4, child, teen, discount, discountPeriod, singleComponent1, singleComponent2','tx_abbooking_price,tx_abbooking_seasons_priceid_mm,tx_abbooking_seasons',$myquery,'',' FIND_IN_SET(tx_abbooking_price.uid, '.$GLOBALS['TYPO3_DB']->fullQuoteStr($product['priceid'], 'tx_abbooking_price').') ','');

					// one array for start and end dates. one for each pid
					$p = 0;
					while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
						$product['prices'][$p] = $row;
						$p++;
					};
					// get the valid prices per day
					for ($d = $this->lConf['startDateStamp']; $d <= $this->lConf['endDateStamp']; $d=strtotime('+1 day', $d)) {
						for ($i=0; $i<$p; $i++) {
							if ($product['prices'][$i]['starttime'] <= $d && $product['prices'][$i]['endtime'] >= $d)
							break;
							else if ($product['prices'][$i]['starttime'] == 0)
							break;
							// if no valid price is found - go further in the price array. otherwise the first in the list is the right.
						}
						$priceDay = $product['prices'][$i];
// 						if ($priceDay['adult2'] < $product_properties[$product]['prices'][$d]['adult2'] || empty($product_properties[$product]['prices'][$d]['adult2']))
						$product['prices'][$d] = $priceDay;
					}

				} else {
					// product has no associated priceid --> this may be an error
					$product['noPriceID'] = 1;
				}
				$product['maxAvailable'] = $this->lConf['numCheckMaxInterval'];

				// get uid and pid of the detailed description content element
				$uidpid = explode("#", $product['uiddetails']);
				if (is_numeric($uidpid[0])) {
					$product['detailsRaw']=t3lib_BEfunc::getRecordRaw(tt_content,'pid='.$uidpid[0].' AND sys_language_uid='.$GLOBALS['TSFE']->sys_language_uid.' AND deleted=0 AND hidden=0','header,bodytext');
					// if there is no detailed description in current language, try default...
					if (empty($product['detailsRaw']))
						$product['detailsRaw']=t3lib_BEfunc::getRecordRaw(tt_content,'pid='.$uidpid[0].' AND deleted=0 AND hidden=0','header,bodytext');
				}
 			$product_properties_return[$uid]=$product;
			}
		}

		$offTimeProductIDs  = array_diff(explode(",", $ProductUID), $availableProductIDs);
		$this->lConf['AvailableProductIDs'] = $availableProductIDs;
		$this->lConf['OffTimeProductIDs'] = $offTimeProductIDs;

		return $product_properties_return;
	}

	/**
	 * Check vacancies for given date
	 *
	 * @param	[type]		$storagePid: ...
	 * @return	0		on success, 1 on error
	 */
	function check_availability($storagePid) {
		$item = array();

		// calculate startDate and endDate of booking request
		if (isset($this->lConf['startDateStamp']))
			$startDate = $this->lConf['startDateStamp'];
		else
			$startDate = time();

		$endDate =  strtotime('+ '.$this->lConf['numCheckMaxInterval'].' days', $startDate);

		if ($endDate > strtotime('+ '.($this->lConf['numCheckNextMonths'] + 1).' months')) {
			$this->availability = 2;
			$this->form_errors['endDateTooFarInFuture'] = $this->pi_getLL('error_tooFarInFuture')."<br/>";
			return 1;
		}

		if (!isset($interval['startList']) && !isset($interval['endList'])) {
			$interval['startList'] = $startDate;
			$interval['endList'] = $endDate;
		}
		$bookings = tx_abbooking_div::getBookings($this->lConf['ProductID'], $storagePid, $interval);

		foreach ($this->lConf['productDetails'] as $key => $product) {
			foreach ($bookings['bookings'] as $key => $row) {
				if (!isset($item[$row['uid_foreign']]['maxAvailable']))
					$item[$row['uid_foreign']]['maxAvailable'] = $this->lConf['numCheckMaxInterval'];

				// booked period is in future of startDate
				if ($row['startdate']>$startDate)
					$item[$row['uid_foreign']]['available'] = (int)date("d",$row['startdate'] - $startDate) - 1; /* day diff */
				else if ($row['enddate']>$startDate)
					// booked period overlaps startDate
					$item[$row['uid_foreign']]['available'] = 0;

				// find maximum available period for item[UID]
				if ($item[$row['uid_foreign']]['available'] < $item[$row['uid_foreign']]['maxAvailable'])
					$item[$row['uid_foreign']]['maxAvailable'] = $item[$row['uid_foreign']]['available'];
			}
		}

		//look for off-times and reduce maxAvailable for all items
		$maxAvailableAll = $this->lConf['numCheckMaxInterval'];
		foreach($this->lConf['OffTimeProductIDs'] as $id => $offTimeID) {
			if (isset($item[$offTimeID]['maxAvailable']) && $item[$offTimeID]['maxAvailable'] < $maxAvailableAll)
				$maxAvailableAll = $item[$offTimeID]['maxAvailable'];
		}


		foreach($this->lConf['AvailableProductIDs'] as $id => $productID) {
			if (is_numeric($item[$productID]['maxAvailable']))
				$this->lConf['productDetails'][$productID]['maxAvailable'] = $item[$productID]['maxAvailable'];

			if ($maxAvailableAll < $this->lConf['productDetails'][$productID]['maxAvailable'])
				$this->lConf['productDetails'][$productID]['maxAvailable'] = $maxAvailableAll;
		}

		return 0;
	}


	/**
	 * Checks the form data for validity
	 *
	 * @return	amount		of errors found
	 */
	function check_form_input() {

		$this->pi_loadLL();
		$this->form_errors = array();
		$numErrors = 0;
		$dns_ok = 0;

		if (empty($this->lConf['productDetails'])) {
			$content = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
			return $content;
		} else {
			foreach ( $this->lConf['productDetails'] as $key => $val ) {
				$product = $val;
			}
		}

		// check for limited vacancies...
		if ($product['maxAvailable'] < $this->lConf['numNights']) {
			$this->form_errors['vacancies_limited'] = $this->pi_getLL('error_vacancies_limited')."<br/>";
			$numErrors++;
		}

		// Email mit Syntax und Domaincheck
		$motif1="#^[[:alnum:]]([[:alnum:]\._-]{0,})[[:alnum:]]";
		$motif1.="@";
		$motif1.="[[:alnum:]]([[:alnum:]\._-]{0,})[\.]{1}([[:alpha:]]{2,})$#";

		if (preg_match($motif1, $this->piVars['email'])){
			list($user, $domain)=preg_split('/@/', $this->piVars['email'], 2);
			$dns_ok=checkdnsrr($domain, "MX");
			// nobody of this domain will write an email - expect spamers...
			if ($domain == $mail_from_domain)
				$dns_ok = 0;
		}
		if (!$dns_ok || !t3lib_div::validEmail($this->piVars['email'])){
			$this->form_errors['email'] = $this->pi_getLL('error_email')."<br/>";
			$numErrors++;
		}



		if (empty($this->piVars['name'])) {
			$this->form_errors['name'] = $this->pi_getLL('error_empty_name')."<br/>";
			$numErrors++;
		}
		if (empty($this->piVars['street'])) {
			$this->form_errors['street'] = $this->pi_getLL('error_empty_street')."<br/>";
			$numErrors++;
		}
		if (empty($this->piVars['town'])) {
			$this->form_errors['town'] = $this->pi_getLL('error_empty_town')."<br/>";
			$numErrors++;
		}
		if (empty($this->piVars['plz'])) {
			$this->form_errors['PLZ'] = $this->pi_getLL('error_empty_plz')."<br/>";
			$numErrors++;
		}

		if ($this->lConf['startDateStamp'] < (time()-86400)) {
			$this->form_errors['startDateInThePast'] = $this->pi_getLL('error_startDateInThePast')."<br/>";
			$numErrors++;
		}
		if ($this->lConf['startDateStamp']+86400 > $this->lConf['endDateStamp']) {
			$this->form_errors['endDateNotValid'] = $this->pi_getLL('error_endDateNotValid')."<br/>";
			$numErrors++;
		}

		if (empty($this->lConf['numNights'])) {
			$this->form_errors['numNightsNotValid'] = $this->pi_getLL('error_numNightsNotValid')."<br/>";
			$numErrors++;
		}

		return $numErrors;
	}


	/**
	 * Logs
	 *
	 * @param	[type]		$logFile: ...
	 * @return	number		of successfully sent emails
	 */
	function log_request($logFile) {

		// don't log robots...
		if ($this->isRobot())
			return;

		$ip = $_SERVER['REMOTE_ADDR'];
		$longisp = @gethostbyaddr($ip);

		$log = strftime("%Y-%m-%d %H:%M:%S").','.$ip.','.$longisp.','.strftime("%d.%m.%Y", $this->lConf['startDateStamp']).','.strftime("%d.%m.%Y", $this->lConf['endDateStamp']).','.$this->piVars['ABnumPersons'].','.$this->lConf['productDetails'][$this->lConf['AvailableProductIDs'][0]]['title']."\n";

		//Daten schreiben
		$fp2=fopen($logFile, "a");
		fputs($fp2, $log);
		fclose($fp2);
	}

	/**
	 * Send Confirmation Email via ab_swiftmailer
	 *
	 * @param	[type]		$$send_errors: ...
	 * @param	[type]		$send_errors: ...
	 * @return	number		of successfully sent emails
	 */
	function send_confirmation_email($key, &$send_errors) {

		$product = $this->lConf['productDetails'][$key];

		$text_mail .= $this->lConf['textConfirmEmail']."\n\n";
		$text_mail .= "===\n";
		$text_mail .= $this->pi_getLL('feld_name').": ".$this->piVars['name']."\n";
		$text_mail .= $this->pi_getLL('feld_street').": ".$this->piVars['street']."\n";
		$text_mail .= $this->pi_getLL('feld_plz').": ".$this->piVars['plz']."\n";
		$text_mail .= $this->pi_getLL('feld_town').": ".$this->piVars['town']."\n\n";
		$text_mail .= $this->pi_getLL('feld_email').": ".$this->piVars['email']."\n";
		$text_mail .= $this->pi_getLL('feld_telefon').": ".$this->piVars['telefon']."\n\n";

		$text_mail .= $this->pi_getLL('product_title').": ".$product['title']."\n";
		$text_mail .= $this->pi_getLL('feld_anreise').": ".strftime("%A, %d.%m.%Y", $this->lConf['startDateStamp'])."\n";
		$text_mail .= $this->pi_getLL('feld_abreise').": ".strftime("%A, %d.%m.%Y", $this->lConf['endDateStamp'])."\n";
		$text_mail .= $this->pi_getLL('feld_naechte').": ".$this->lConf['numNights']."\n";
		$text_mail .= $this->pi_getLL('feld_personen').": ".$this->piVars['ABnumPersons']."\n\n";
		if (isset($this->piVars['mitteilung']))
			$text_mail .= $this->pi_getLL('feld_mitteilung').": ".$this->piVars['mitteilung']."\n\n";

		$text_mail .= "---------------------------------------------------------\n";

		$text_mail .= $this->printCalculatedRates($key, $this->lConf['numNights'], 0);

		if ($this->lConf['showPrice'] == '1') {
		}
		$text_mail .= "===\n";
		$result = 0;

		// prefere ab_swiftmailer if available
		if (class_exists('tx_abswiftmailer_pi1') && $this->lConf['useSwiftMailer']) {
			$this->swift = t3lib_div::makeInstance('tx_abswiftmailer_pi1');
			// send booking mail to owner
			$result = $this->swift->swift_send_message(array($this->piVars['email'] => $this->piVars['name']),
				$this->pi_getLL('email_new_booking').' '.$this->piVars['name'].' ('.$this->piVars['email'].')',
				$text_mail);
			if ($result != 1) {
				$send_errors = $result;
			} else
				$send_success++;

			// send acknoledge mail to customer
			$result = $this->swift->swift_send_message(array($this->piVars['email'] => $this->piVars['name']),
				$this->pi_getLL('email_your_booking').' '.strftime("%d.%m.%Y", $this->lConf['startDateStamp']).' ',
				$text_mail, 1);
			if ($result != 1) {
				$send_errors .= $result;
			} else
				$send_success++;
		}
		// else send the TYPO3-way:
		else {
			// send booking mail to owner, reply-to customer
			t3lib_div::plainMailEncoded($this->lConf['EmailAddress'],
					$this->pi_getLL('email_new_booking').' '.$this->piVars['name'].' ('.$this->piVars['email'].')',
					$text_mail,
					'From: '.$this->lConf['EmailRealname'].' <'.$this->lConf['EmailAddress'].'>'.chr(10).'Reply-To: '.$this->piVars['email']);
			// send acknoledge mail to customer
			t3lib_div::plainMailEncoded($this->piVars['email'],
					$this->pi_getLL('email_your_booking').' '.strftime("%d.%m.%Y", $this->lConf['startDateStamp']),
					$text_mail,
					'From: '.$this->lConf['EmailRealname'].' <'.$this->lConf['EmailAddress'].'>'.chr(10).'Reply-To: '.$this->lConf['EmailAddress']);
			// assume everything went ok because there is no return value of plainMailEncoded())
			$send_success = 2;
		}

		return $send_success;
	}

	/**
	 * Insert Booking into Database
	 *
	 * @param	[type]		$request: ...
	 * @return	inserted		ID
	 */
	function insert_booking($request) {

		$product = $this->lConf['productDetails'];

		$startDate = $this->lConf['startDateStamp'];
		if (isset($this->lConf['numNights']))
			$endDate = strtotime('+'.$this->lConf['numNights'].' day', $startDate);
		else
			$endDate = $startDate;

		if ($request == 0) {
			$title = strftime('%Y%m%d', $startDate).', '.$this->piVars['name'].', '.$this->piVars['town'];
			$editCode = md5($title.$this->lConf['ProductID']);
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
			$longisp = @gethostbyaddr($ip);
			$title = 'request,'.$ip.','.$longisp;
			$editCode = "request";
		}

		$fields_values = array(
			'pid' => $this->lConf['PIDstorage'],
			'tstamp' => time(),
			'crdate' => time(),
			'startdate' => $startDate,
			'enddate' => $endDate,
			'title' => $title,
			'editcode' => $editCode,
			'deleted' => $request,
			'request' => $request,
		);

		$query = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_abbooking_booking', $fields_values);

		$id_inserted = $GLOBALS['TYPO3_DB']->sql_insert_id();

		// to be fixed if AvailableProductIDs is more than one...
		$fields_values = array(
			'uid_local' => $id_inserted,
			'uid_foreign' => implode(',', $this->lConf['AvailableProductIDs']),
		);
		$query = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_abbooking_booking_productid_mm', $fields_values);

		$id_inserted = $GLOBALS['TYPO3_DB']->sql_insert_id();

		return $id_inserted;
	}

	/**
	 * Calculate the Rates
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$maxAvailable: ...
	 * @return	string		with amount, currency...
	 */
	function calcRates($key, $maxAvailable) {

		$priceDetails = array();

		$product = $this->lConf['productDetails'][$key];

		if ($maxAvailable < $this->lConf['numNights'])
			$period = $maxAvailable;
		else
			$period = $this->lConf['numNights'];

		$max_amount = 0;
		// asuming every adult costs more;
		// e.g. 1 adult 10, 2 adults 20, 3 adults 25...
		// if you don't have prices per person, please use adult2 for the entire object
		for ($i=1; $i<=$this->lConf['numPersons'] && $i<=$product['capacitymax']; $i++) {
			if ($product['prices'][$this->lConf['startDateStamp']]['adult'.$i] > $max_amount) {
				$max_amount = $product['prices'][$this->lConf['startDateStamp']]['adult'.$i];
				$max_persons = $i;
			}
		}
		// step through days from startdate to (enddate | maxAvailable) and add rate for every day
		$total_amount = 0;
		for ($d = $this->lConf['startDateStamp'];
			$d < $this->lConf['endDateStamp'] && $d < strtotime('+'.$period.' day', $this->lConf['startDateStamp']);
				$d = strtotime('+1 day', $d)) {
				$total_amount += $product['prices'][$d]['adult'.$max_persons];
				$usedPrices[$product['prices'][$d]['title']]['rateUsed']++;
				$usedPrices[$product['prices'][$d]['title']]['rateValue'] = $product['prices'][$d]['adult'.$max_persons];
		}
		// take currency from startDate
		$currency = $product['prices'][$this->lConf['startDateStamp']]['currency'];

		// some useful texts
		if ($this->lConf['showPersonsSelector'] == 1) {
			if ($max_persons == 1)
				$text_persons = ', '.$max_persons.' '.$this->pi_getLL('person');
			else
				$text_persons = ', '.$max_persons.' '.$this->pi_getLL('persons');
		}

		foreach ($usedPrices as $title => $value) {
			if ($value['rateUsed'] == 1)
				$text_periods .= ' '.$this->pi_getLL('period');
			else
				$text_periods .= ' '.$this->pi_getLL('periods');

			$lDetails['description'] = $value['rateUsed'].' '.$text_periods.', '.$title.$text_persons;
			$lDetails['value'] = $value['rateUsed'].' x '.number_format($value['rateValue'], 2, ',', '').' '.$currency;
			$priceDetails[] = $lDetails;
		}

		// apply discount; discountValue is taken from startDate
		$discountrate = $product['prices'][$this->lConf['startDateStamp']]['discount'];
		if (intval($discountrate)>0 && $period >= $product['prices'][$this->lConf['startDateStamp']]['discountPeriod']) {
			$discountValue = round($total_amount * ($discountrate/100), 2);
			$total_amount -= $discountValue;

			$lDetails['description'] = $this->pi_getLL('discount').' '.round($discountrate,0).'%';
			$lDetails['value'] = '-'.number_format($discountValue, 2, ',', '').' '.$currency;
			$priceDetails[] = $lDetails;
		}
		// get singleComponent from startDate
		if ($product['prices'][$this->lConf['startDateStamp']]['singleComponent1']>0) {
			$total_amount += $product['prices'][$this->lConf['startDateStamp']]['singleComponent1'];

			$lDetails['description'] = $this->pi_getLL('specialComponent1');
			$lDetails['value'] = number_format($product['prices'][$this->lConf['startDateStamp']]['singleComponent1'], 2, ',', '').' '.$currency;
			$priceDetails[] = $lDetails;
		}
		if ($product['prices'][$this->lConf['startDateStamp']]['singleComponent2']>0) {
			$total_amount += $product['prices'][$this->lConf['startDateStamp']]['singleComponent2'];

			$lDetails['description'] = $this->pi_getLL('specialComponent2');
			$lDetails['value'] = number_format($product['prices'][$this->lConf['startDateStamp']]['singleComponent2'], 2, ',', '').' '.$currency;
			$priceDetails[] = $lDetails;
		}

		$rate['priceTotalAmount'] = number_format($total_amount, 2, ',', '');
		$rate['priceCurrency'] = $currency;
		$rate['priceDetails'] = $priceDetails;

		$rate['textPriceTotalAmount'] = number_format($total_amount, 2, ',', '').' '.$currency;

		return $rate;
	}

	/**
	 * Print the calculates rates
	 *
	 * @param	array		$rates
	 * @param	bool		$printHTML: ...
	 * @param	[type]		$printHTML: ...
	 * @return	string		string for output...
	 */
	function printCalculatedRates($key, $period, $printHTML = 1) {

		$content = '';
		$product = $this->lConf['productDetails'][$key];

		if ($product['noPriceID'] == 1 && $this->lConf['showPrice'] == 1)
			return '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noPriceSelected').'</b></h2>';

		if ($this->lConf['showPrice'] == '1') {
			$rates = $this->calcRates($key, $period);


			if ($printHTML == 1) {
				if ($this->lConf['showPriceDetails'] == '1') {
					$content .= '<div class="priceDetails">';
					$content .= '<ul>';
						foreach ($rates['priceDetails'] as $id => $priceLine) {
							$lengthOfDescription=strlen($priceLine['description'])+2+strlen($priceLine['value']);
							$content .= '<li><span class="priceDescription">'.$priceLine['description'].'</span>';
							$content .= '<span class="priceValue">'.$priceLine['value'].'</span></li>';
						}
					$content .= '</ul></div>';
				}
				$content .= '<div class="priceTotal"><span class="priceDescription"><b>'.$this->pi_getLL('total_amount').'</b></span>: ';
				$content .= '<span class="priceValue"><b>'.$rates['textPriceTotalAmount'].'</b></div>';
			} else {
				// without HTML e.g. for mail output
				if ($this->lConf['showPriceDetails'] == '1') {
					foreach ($rates['priceDetails'] as $id => $priceLine) {
						$lengthOfDescription=strlen($priceLine['description'])+2+strlen($priceLine['value']);
						$content .= $priceLine['description'].": ";
						if (strlen($priceLine['description'])>40) {
							//newline if text is to long
							$content .= "\n";
							$lengthOfDescription = strlen($priceLine['value']);
						}
						for ($i=(50-$lengthOfDescription); $i>0; $i--)
							$content.= ' ';
						$content .= $priceLine['value']."\n";
					}
					$content .= "---------------------------------------------------------\n";
				}
				$lengthOfDescription = strlen($this->pi_getLL('total_amount'))+2+strlen($rates['textPriceTotalAmount']);
				$content .= $this->pi_getLL('total_amount').": ";
				for ( $i = (50-$lengthOfDescription); $i > 0; $i-- )
						$content.= ' ';
				$content.= $rates['textPriceTotalAmount']."\n";
			}
		}
		return $content;

	}

	/**
	 * Check for Robots
	 *
	 * @return	0		if visitor is no robot, else 1
	 */
	function isRobot() {
		$trackUserAgent = $_SERVER['HTTP_USER_AGENT'];

		if (stristr($trackUserAgent, 'archiver' )) return 1;
		else if (stristr($trackUserAgent, 'exabot' )) return 1;
		else if (stristr($trackUserAgent, 'firefly' )) return 1;
		else if (stristr($trackUserAgent, 'msnbot' )) return 1;
		else if (stristr($trackUserAgent, 'scooter' )) return 1;
		else if (stristr($trackUserAgent, 'googlebot' )) return 1;
		else if (stristr($trackUserAgent, 'bigfinder' )) return 1;
		else if (stristr($trackUserAgent, 'yandex' )) return 1;
		else if (stristr($trackUserAgent, 'slurp' )) return 1;
		else if (stristr($trackUserAgent, 'gonzo' )) return 1;

		else return 0;
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/pi1/class.tx_abbooking_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/pi1/class.tx_abbooking_pi1.php']);
}

?>
