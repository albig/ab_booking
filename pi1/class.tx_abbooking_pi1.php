<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2014 Alexander Bigga <linux@bigga.de>
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
 * Plugin 'Booking Calendar' for the 'ab_booking' extension.
 *
 * @author	Alexander Bigga <linux@bigga.de>
 * @package	TYPO3
 * @subpackage	tx_abbooking
 */
require_once(t3lib_extMgm::extPath('ab_booking').'lib/class.tx_abbooking_div.php'); // load div
require_once(t3lib_extMgm::extPath('ab_booking').'lib/class.tx_abbooking_form.php'); // load form

class tx_abbooking_pi1 extends tslib_pibase {

	var $prefixId      = 'tx_abbooking_pi1';	// Same as class name
	var $scriptRelPath = 'pi1/class.tx_abbooking_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'ab_booking';			// The extension key.

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
		$this->pi_USER_INT_obj = 0;

		$this->cssBooking = str_replace(PATH_site,'',t3lib_div::getFileAbsFileName($this->conf['file.']['cssBooking']));
		$GLOBALS['TSFE']->additionalHeaderData['abbooking_css'] = '<link href="'.$this->cssBooking.'" rel="stylesheet" type="text/css" />'."\n";

		// get all initial settings
		$this->init();

		if (!isset($interval['startDate'])) {
			$interval['startDate'] = $this->lConf['startDateStamp'];
			$interval['endDate'] = $this->lConf['endDateStamp'];
		}

		if (!isset($interval['endDate'])) {
			$interval['endDate'] = strtotime('+ '.$this->lConf['numCheckMaxInterval'].' days', $interval['startDate']);
		}

		if (!isset($interval['startList'])) {
			$minListInterval = strtotime('- '.$this->lConf['numCheckMaxInterval'].' days', $interval['startDate']);
			$maxListInterval = strtotime('+ '.$this->lConf['numCheckMaxInterval'].' days', $interval['startDate']);

			// increase endList to end for form with calendar month view.
			if (!empty($this->lConf['form']['showCalendarMonth']) && strtotime('+'.$this->lConf['form']['showCalendarMonth'].' months', $interval['startDate']) > $maxListInterval) {
				$maxListInterval = strtotime('+ '.$this->lConf['form']['showCalendarMonth'].' months', $interval['startDate']);
				$minListInterval = strtotime('- '.$this->lConf['form']['showCalendarMonth'].' months', $interval['startDate']);
			}

			$interval['startList'] = $minListInterval;
			$interval['endList'] = $maxListInterval;
		}

		switch ( $this->lConf['mode'] ) {
			case 'form':
				// check first for submit button and second for View
				if (isset ($this->piVars['submit_button_edit']) && $this->lConf['ABdo'] == 'bor3')
					$this->lConf['ABdo'] = 'bor0';

				// update/check all rates
				tx_abbooking_div::getAllRates($interval);
				$this->check_availability($interval);

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
						}

 						$offers = tx_abbooking_div::printOfferList();

						/* ------------------------- */
						/* list available items      */
						/* ------------------------- */
						if ($this->lConf['startDateStamp'] < (time()-86400) ) {
							$this->form_errors['startDateInThePast'] = $this->pi_getLL('error_startDateInThePast');
						} else if (!isset($this->form_errors['endDateTooFarInFuture'])) {

							$out .= '<div class="offer">';
							if ($offers['numOffers']>0)
								$out .= '<p class="offer">'.$this->pi_getLL('we_may_offer').'</p>';
							else
								$out .= '<p class="offer">'.$this->pi_getLL('no_offer').'</p>';
							$out .= '<p>'.strftime("%A, %x", $this->lConf['startDateStamp']).' - ';
							$out .= ' '.strftime("%A, %x", $this->lConf['endDateStamp']).'</p><br />';
							$out .= '<p>'.$this->pi_getLL('feld_naechte').': '.$this->lConf['daySelector'].'</p>';
							if ($this->lConf['showPersonsSelector'] == 1)
								$out .= '<p>'.$this->pi_getLL('feld_personen').': '.$this->lConf['adultSelector'].'</p>';
							$out .= '<ul>';
							for ($i=0; $i<=$offers['amount']; $i++)
								$out .= $offers[$i];
							$out .= '</ul>';
							$out .= '</div>';

							return $this->pi_wrapInBaseClass($out);
						}
						$out .= $this->formCheckAvailability();
						return $this->pi_wrapInBaseClass($out);
						break;
					case 'bor0':
						/* ------------------------- */
						/* booking request formular  */
						/* ------------------------- */
						$this->lConf['stage'] = 0;
						$out .= tx_abbooking_form::printUserForm($stage = 1);
						break;
					case 'bor1':
						$this->lConf['stage'] = 1;
						$out .= tx_abbooking_form::printUserForm($stage = 1);
						break;
					case 'bor2':
						$this->lConf['stage'] = 2;
						$out .= tx_abbooking_form::printUserForm($stage = 2);
						break;
					case 'bor3':
						/* --------------------------- */
						/* booking final - send mails  */
						/* --------------------------- */
						$this->lConf['stage'] = 3;
						$numErrors = tx_abbooking_form::formVerifyUserInput();

						if ($numErrors == 0) {
							$out .= tx_abbooking_div::printBookingStep($stage = 4);
							$result= $this->send_confirmation_email($product['uid'], $send_errors);
							if (($result == 2 && $this->lConf['sendCustomerConfirmation'] == '1') || $result == 1) {
								if (isset($this->lConf['textSayThankYou']))
									$out .= '<div class="requestForm"><p>'.nl2br($this->lConf['textSayThankYou']).'</p></div>';
								else
									$out .= '<div class="requestForm"><p>'.nl2br($this->pi_getLL('send_success')).'</p></div>';

								// only insert booking if successfully sent both mails
								$this->insert_booking();
							} else {
								$out .= '<div class="requestForm"><p><b>'.nl2br($this->pi_getLL('send_failure')).'</b><br />'.$result.'</p>';
								$out .= '<br/>&nbsp;<br/>';
								$out .= $send_errors;
								$out .= '</div>';
							}

						} else {
							$out .= tx_abbooking_form::printUserForm($stage = 2);
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
				switch ($this->lConf['pluginSelection']) {
					case '0':
						$out .= tx_abbooking_div::printAvailabilityCalendarDiv($this->lConf['ProductID'], array(), (int)$this->lConf['numMonths'], (int)$this->lConf['numMonthsCols']);
						break;
					case '1':
						$out .= $this->formCheckAvailability();
						break;
					case '2':
						$out .= '<div class="offer">';
						$out .= $this->pi_getLL('error_availability_list');
						$out .= '</div>';
						break;
					case '4':
						$out .= tx_abbooking_div::printCheckinOverview($this->lConf['ProductID']);
						break;
					case '6': // list of future bookings
						$out .= tx_abbooking_div::printFutureBookings($this->lConf['ProductID']);
						break;
					default:
						/* ------------------------- */
						/* show calendar             */
						/* ------------------------- */
						$out .= tx_abbooking_div::printAvailabilityCalendarDiv($this->lConf['ProductID'], array(), (int)$this->lConf['numMonths'], (int)$this->lConf['numMonthsCols']);
						break;
				}
				break;
		}

	return $this->pi_wrapInBaseClass($out);

	}
	/**
	 * initializes all config options in $this->lConf
	 *
	 * priority:
	 *   1. GET/POST (piVars)
	 *   --> if not set 2. Flexform
	 *   --> if not set 3. Typoscript
	 *
	 * @return	empty
	 */
	function init() {

		$this->extConf = array();
		$this->pi_initPIflexForm(); // Init and get the flexform data of the plugin
		$this->lConf = array(); // Setup our storage array...
		// Assign the flexform data to a local variable for easier access
		$piFlexForm = $this->cObj->data['pi_flexform'];


		// only the following flexform sheets are allowed.
		// due to refactoring the FF, old values on other sheets may
		// be are still present and confuse ...
		$allowedFFSheets = array('sheetGeneralOptions', 'sheetPluginOptions');

		// Traverse the entire array based on the language...
		// and assign each configuration option to $this->lConf array...
		if (sizeof($piFlexForm)>0)
			foreach ( $piFlexForm['data'] as $sheet => $data ) {
				if (in_array($sheet, $allowedFFSheets))
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
			list($this->lConf['startDateStamp'], $this->lConf['daySelector'], $this->lConf['adultSelector'], $this->lConf['ABProductID'], $this->lConf['ABuidpid'], $this->lConf['ABPIDbooking'], $this->lConf['ABdo']) = explode("_", $this->piVars['ABx']);
		}

		// set dateFormat - prefered from TS otherwise from language defaults
		if (isset($this->conf['dateFormat'])) {
			$this->lConf['dateFormat'] = $this->conf['dateFormat'];
		} else {
				if ($GLOBALS['TSFE']->config['config']['language'] == 'de')
					$this->lConf['dateFormat'] = 'd.m.Y';
				else if ($GLOBALS['TSFE']->config['config']['language'] == 'en')
					$this->lConf['dateFormat'] = 'd/m/Y';
				else
					$this->lConf['dateFormat'] = 'Y-m-d';
		}

		// overwrite some settings if post-vars are set:
		if (isset($this->piVars['checkinDate'])) {
			// set date timestamp to 00:00:00
			$this->lConf['startDateStamp'] = date_format(date_time_set(date_create_from_format($this->lConf['dateFormat'], $this->piVars['checkinDate']), 0, 0), 'U');
		}

		if (isset($this->piVars['daySelector']))
			$this->lConf['daySelector'] = $this->piVars['daySelector'];
		if (isset($this->piVars['adultSelector']))
			$this->lConf['adultSelector'] = $this->piVars['adultSelector'];
		if (isset($this->piVars['childSelector']))
			$this->lConf['numChildren'] = $this->piVars['childSelector'];
		if (isset($this->piVars['teenSelector']))
			$this->lConf['numTeens'] = $this->piVars['teenSelector'];

		if (!empty($this->lConf['ABProductID']))
			$this->lConf['ProductID'] = $this->lConf['ABProductID'];

		// if no booking PID is set, we assume the booking is the same
		if (!empty($this->lConf['ABPIDbooking']))
			$this->lConf['PIDbooking'] = $this->lConf['ABPIDbooking'];
		else if (empty($this->lConf['PIDbooking']))
			$this->lConf['PIDbooking'] = $this->cObj->data['pid'];

		if (!isset($this->lConf['ABdo']))
			$this->lConf['mode'] = 'display';
		else {
			// check if formular or display mode:
			if (($this->lConf['pluginSelection'] == '2') &&
			      ($this->lConf['uidpid'] == $this->lConf['ABuidpid'] ||
				$this->lConf['PIDbooking'] == $this->cObj->data['pid'])) {
				$this->lConf['mode'] = 'form';
			} else
				$this->lConf['mode'] = 'display';
		}

		// ---------------------------------
		// check flexform data
		// ---------------------------------
		// maximum of availability check
		if (! isset($this->lConf['numCheckMinInterval']))
			$this->lConf['numCheckMinInterval'] = 1;

		if (! isset($this->lConf['numCheckMaxInterval']))
			$this->lConf['numCheckMaxInterval'] = 21;

		if (is_numeric($this->lConf['PIDbooking']))
			$this->lConf['gotoPID'] = $this->lConf['PIDbooking'];
		else {
			$this->lConf['gotoPID'] = $GLOBALS['TSFE']->id;
		}

		// set default 12 months
		if (! is_numeric($this->lConf['numCheckNextMonths']) || empty($this->lConf['numCheckNextMonths']))
			$this->lConf['numCheckNextMonths'] = 12;

		// set defaults if still empty:
		// the values are set either in Calendar or Availability Check view
		if (empty($this->lConf['adultSelector'])) {
			if (!empty($this->lConf['numDefaultPersonsAvailabilitycheck']))
				$this->lConf['adultSelector'] = $this->lConf['numDefaultPersonsAvailabilitycheck'];
			else if (!empty($this->lConf['numDefaultPersonsCalendar']))
				$this->lConf['adultSelector'] = $this->lConf['numDefaultPersonsCalendar'];
			else
				$this->lConf['adultSelector'] = 2;
		}
		if (empty($this->lConf['daySelector'])) {
			if (!empty($this->lConf['numDefaultNightsAvailabilitycheck']))
				$this->lConf['daySelector'] = $this->lConf['numDefaultNightsAvailabilitycheck'];
			else if (!empty($this->lConf['numDefaultNightsCalendar']))
				$this->lConf['daySelector'] = $this->lConf['numDefaultNightsCalendar'];
			else
				$this->lConf['daySelector'] = 2;
		}

		// set bool value for Booking Link in Calendar and Availability Check view:
		if (!empty($this->lConf['enableBookingLinkAvailabilityCheck']))
			$this->lConf['enableBookingLink'] = $this->lConf['enableBookingLinkAvailabilityCheck'];
		else if (!empty($this->lConf['enableBookingLinkCalendar']))
			$this->lConf['enableBookingLink'] = $this->lConf['enableBookingLinkCalendar'];
		else
			$this->lConf['enableBookingLink'] = 0;

		if (! isset($this->lConf['daySteps']))
			$this->lConf['daySteps'] = 1;

		// on missconfigurated servers, the TYPO3 backend timezone differs to the mysql timezone - or whatever
		if (!empty($this->conf['overwritePHPTimezone']))
			date_default_timezone_set($this->conf['overwritePHPTimezone']);

		// ---------------------------------
		// calculate endDateStamp
		// ---------------------------------
		if (empty($this->lConf['startDateStamp']))
			$this->lConf['startDateStamp'] = strtotime('today');

		$this->lConf['endDateStamp'] =  strtotime('+ '.$this->lConf['daySelector'].' days', $this->lConf['startDateStamp']);

		// get the storage pid from flexform
		if (! intval($this->lConf['PIDstorage'])>0) {
			$storagePid = $GLOBALS['TSFE']->getStorageSiterootPids();
			$this->lConf['PIDstorage'] = $storagePid['_STORAGE_PID'];
		}

		// ---------------------------------
		// check TS data
		// ---------------------------------

		if (intval($this->conf['showPrice']) > 0)
			$this->lConf['showPrice'] = $this->conf['showPrice'];
		if (intval($this->conf['showPriceDetails']) > 0)
			$this->lConf['showPriceDetails'] = $this->conf['showPriceDetails'];
		if (count($this->conf['form.']) > 0)
			$this->lConf['form'] = $this->conf['form.'];

		// ---------------------------------
		// get Product Properties
		// ---------------------------------
		if (isset($this->lConf['ProductID'])) {
			$this->lConf['productDetails'] = $this->getProductPropertiesFromDB($this->lConf['ProductID']);
			// merge array of available and offtime product IDs
			$this->lConf['ProductID'] = implode(',', array_unique(array_merge(explode(',', $this->lConf['ProductID']), $this->lConf['OffTimeProductIDs'])));
		}

		// ---------------------------------
		// save user session data
		// ---------------------------------
		if (isset($this->piVars['submit_button'])) {
			$customerData = $this->piVars; // copy all - is this bad?
			if (empty($this->piVars['name']))
				$customerData["address_name"] =  $this->piVars['firstname'] . ' ' . $this->piVars['lastname'];
			else
				$customerData["address_name"] = $this->piVars['name'];
			$customerData["address_street"] = $this->piVars['street'];
			$customerData["address_zip"] = $this->piVars['zip'];
			$customerData["address_city"] = $this->piVars['city'];
			$customerData["address_email"] = $this->piVars['email'];
			$customerData["address_telephone"] = $this->piVars['telephone'];
			if (isset($this->piVars['rateOption']))
				foreach ($this->piVars['rateOption'] as $key => $value) {
					if (strpos($value, '_1') == (strlen($value)-2)) {
						$origValue = substr($value,0,strlen($value)-2);
						if (empty($customerData[$origValue]))
							$customerData[$origValue] = '0';
					}
					else
						$customerData[$value] = '1';
				}
			$GLOBALS["TSFE"]->fe_user->setKey("ses","customData", array());
			$GLOBALS["TSFE"]->fe_user->setKey("ses","customData", $customerData);
		} else {
			$customerData = $GLOBALS["TSFE"]->fe_user->getKey("ses","customData");
		}
		$this->lConf['customerData'] = $customerData;
	}



	/**
	 * availability prices form ;-)
	 *
	 * @return	HTML	form to check availability
	 */
	public function formCheckAvailability() {

		if (empty($this->lConf['productDetails'])) {
			$content = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
			return $content;
		} else {
			$overallCapacity = 0;
			foreach ( $this->lConf['productDetails'] as $key => $val ) {
				$overallCapacity += $val['capacitymax'];
			}
		}

		$selected='selected="selected"';
		if (isset($this->lConf['adultSelector']))
			$seladultSelector[$this->lConf['adultSelector']] = $selected;
		else
			$seladultSelector[2] = $selected;

		if (isset($this->lConf['daySelector']))
			$seldaySelector[$this->lConf['daySelector']] = $selected;
		else
			$seldaySelector[2] = $selected;

		if (isset($this->form_errors['vacancies_limited'])) {
			$ErrorVacanciesLimited='error';
			$content.='<h2><b>'.$this->form_errors['vacancies_limited'].'</b></h2>';
		}
		if (isset($this->form_errors['vacancies'])) {
			$ErrorVacancies='error';
			$content.='<h2><b>'.$this->form_errors['vacancies'].'</b></h2>';
		}
		if (isset($this->form_errors['startDateInThePast'])) {
			$ErrorVacancies='error';
			$content.='<h2><b>'.$this->form_errors['startDateInThePast'].'</b></h2>';
			// reset checkinDate
			unset($this->lConf['startDate']);
			unset($this->lConf['startDateStamp']);
		}
		if (isset($this->form_errors['endDateTooFarInFuture'])) {
			$ErrorVacancies='error';
			$content.='<h2><b>'.$this->form_errors['endDateTooFarInFuture'].'</b></h2>';
		}

		$content .= '<form action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="post">';
		// -------------------------------------
		// field startDate with - possible datepicker
		// -------------------------------------
		$content .= '<div class="startdate">';
		$content .= '<label for="checkinDate-'.$this->lConf['uidpid'].'">'.htmlspecialchars($this->pi_getLL('feld_anreise')).'</label><br/>';
		if (isset($this->lConf['startDateStamp']))
			$startdate = $this->lConf['startDateStamp'];
		else
			$startdate = time();

		$content .= '<input class="'.$ErrorVacancies.' datepicker" id="checkinDate-'.$this->lConf['uidpid'].'" name="'.$this->prefixId.'[checkinDate]" type="text" size="12" maxsize="12" value="'.date($this->lConf['dateFormat'], $startdate).'" />';

		$content .= '</div>';

		// -------------------------------------
		// field days select
		// -------------------------------------
		$content .= '<div class="selector">';
		$content .= '<label for="fielddaySelector-'.$this->lConf['uidpid'].'">'.htmlspecialchars($this->pi_getLL('feld_naechte')).'</label><br/>
				<select class="'.$ErrorVacanciesLimited.'" name="'.$this->prefixId.'[daySelector]" id="fielddaySelector-'.$this->lConf['uidpid'].'" size="1">';

		// set global day steps
		if ((int)$this->lConf['numCheckDaySteps']>0)
			$dayStep = $this->lConf['numCheckDaySteps'];
		else
			$dayStep = 1;

		for ($i = $this->lConf['numCheckMinInterval']; $i<=$this->lConf['numCheckMaxInterval']; $i+=$dayStep) {
			$content.='<option '.$seldaySelector[$i].' value='.$i.'>'.$i.'</option>';
		}
		$content .= '</select>';
		$content .= '</div>';

		// -------------------------------------
		// field persons select
		// -------------------------------------
		if ($this->lConf['showPersonsSelector'] == 1 && $overallCapacity > 0) {
			$content .= '<div class="selector">';
			$content .= '<label for="fieldadultSelector-'.$this->lConf['uidpid'].'">'.htmlspecialchars($this->pi_getLL('feld_personen')).'</label><br/>
					<select name="'.$this->prefixId.'[adultSelector]" id="fieldadultSelector-'.$this->lConf['uidpid'].'" size="1">';
			// you may set in flexform the maximum amount of persons to show in the selector
			if (intval($this->lConf['numCheckMaxPersons'])>0)
				$selectorMax = min($this->lConf['numCheckMaxPersons'],  $overallCapacity);
			else
				$selectorMax = $overallCapacity;
			/* how many persons are possible? */
			for ($i = 1; $i<=$selectorMax; $i++) {
					$content.='<option '.$seladultSelector[$i].' value='.$i.'>'.$i.'</option>';
			}
			$content .= '</select><br/>';
			$content .= '</div>';
		} else
			$content .= '<div><input type="hidden" name="'.$this->prefixId.'[adultSelector]" value="'.$this->lConf['numDefaultPersons'].'"></div>';

		$params_united = '0_0_0_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_availabilityList';
		$params = array (
			$this->prefixId.'[ABx]' => $params_united,
		);

		$content .= '<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'">';
		// always render the offer page...
		$content .= '<input type="hidden" name="'.$this->prefixId.'[abnocache]" value="1">';

		$content .= '<input class="submit" type="submit" name="'.$this->prefixId.'[submit_button_checkavailability]" value="'.htmlspecialchars($this->pi_getLL('submit_button_label')).'">';

		$content .= '</form><br />';

		return $content;
	}


	/**
	 * get all properties, description text and prices of a product
	 * with the given UID
	 *
	 * @param	string	$ProductUID: comma separated list of ids
	 * @return	[type]	array of properties..
	 */
	public function getProductPropertiesFromDB($ProductUID) {

//~ print_r("getProductPropertiesFromDB:" . $ProductUID . "\n");

		$availableProductIDs = array();
		$offTimeProductIDs = array();

		if (!isset($interval['startDate']) && !isset($interval['endDate'])) {
			$interval['startDate'] = $this->lConf['startDateStamp'];
			$interval['endDate'] = $this->lConf['endDateStamp'];
		}
		if (!isset($interval['startList']) && !isset($interval['endList'])) {
			$interval['startList'] = $interval['startDate'];
			$interval['endList'] = strtotime('+'.$this->lConf['numCheckMaxInterval'].' day', $this->lConf['startDateStamp']);
		}

		if (!empty($ProductUID)) {
			// SELECT:
			$where_extra = 'capacitymax >= 0 AND offtime_dummy = 0';
			$product_properties =  tx_abbooking_div::getRecordRaw('tx_abbooking_product', $this->lConf['PIDstorage'], $ProductUID, $where_extra);

			$pi = 0;
			// step through found products
			foreach ( $product_properties as $uid => $product ) {
				$availableProductIDs[$pi] = $uid;
				$pi++;

				$product['maxAvailable'] = $this->lConf['numCheckMaxInterval'];

				// get uid and pid of the detailed description content element
				$uidpid = explode("#", $product['uiddetails']);
				if (is_numeric($uidpid[0])) {
					$product['detailsRaw'] =  array_shift(tx_abbooking_div::getRecordRaw('tt_content', $uidpid[0], $uidpid[1]));
  				}
				$product_properties_return[$uid] = $product;
			}

		}

		// given UIDs not in availableProductIDs must be OffTimeProductIDs
		$offTimeProductIDs  = array_diff(explode(",", $ProductUID), $availableProductIDs);
		$this->lConf['AvailableProductIDs'] = $availableProductIDs;
		$this->lConf['OffTimeProductIDs'] = $offTimeProductIDs;

		return $product_properties_return;
	}

	/**
	 * get title text with current language setting
	 *
	 * @param	array	$title: ...
	 * @return	string	array of properties..
	 */
	public function getTSTitle($title) {

		$lang = $GLOBALS['TSFE']->config['config']['language'];

		if (! is_array($title))
			return '';

		if (!empty($title[$lang]))
			$langTitle = $title[$lang];
		else
			$langTitle = current($title);

		return $langTitle;
	}


	/**
	 * Check vacancies for given date
	 *
	 * all information is filled in global $this->lConf['productDetails'] array
	 *
	 * @param	array	$interval
	 * @return	0	on success, 1 on error
	 */
	function check_availability($interval) {
		$item = array();

		if (!isset($interval['startDate'])) {
			$interval['startDate'] = $this->lConf['startDateStamp'];
			$interval['endDate'] = strtotime('+ '.$this->lConf['numCheckMaxInterval'].' days', $interval['startDate']);
		}

		if (!isset($interval['startList'])) {
			$interval['startList'] = $interval['startDate'];
			$interval['endList'] = $interval['endDate'];
		}

		if ($endDate > strtotime('+ '.($this->lConf['numCheckNextMonths']).' months')) {
			$this->form_errors['endDateTooFarInFuture'] = sprintf($this->pi_getLL('error_tooFarInFuture'), strftime("%a, %x", strtotime('+ '.($this->lConf['numCheckNextMonths']).' months')))."<br />";
		}


		// 1. step through bookings to find maximum availability
		$bookings = tx_abbooking_div::getBookings($this->lConf['ProductID'], $interval);

		foreach ($bookings['bookings'] as $key => $row) {

			// start with something reasonable: the set checkMaxInterval
			if (!isset($item[$row['uid']]['maxAvailable']))
				$item[$row['uid']]['maxAvailable'] = $this->lConf['numCheckMaxInterval'];

			// booked period is in future of startDate
			if ($row['startdate'] > $interval['startDate'])
				$item[$row['uid']]['available'] = (int) date("z", $row['startdate'] - $interval['startDate']); /* day diff */
			else if ($row['enddate'] > $interval['startDate'])
				// booked period overlaps startDate
				$item[$row['uid']]['available'] = 0;

			// check if found "available" in this run is small than the previous maxAvailable
			if ($item[$row['uid']]['available'] < $item[$row['uid']]['maxAvailable'])
				$item[$row['uid']]['maxAvailable'] = $item[$row['uid']]['available'];

		}

 		// 2. step through prices to find maximum availability
 		foreach ($this->lConf['productDetails'] as $uid => $product) {

			// if maxAvailable is not yet set by any booking above, start again with checkMaxInterval
			if (!isset($item[$uid]['maxAvailable']))
				$item[$uid]['maxAvailable'] = $this->lConf['numCheckMaxInterval'];

			for ($d = $interval['startDate']; $d < $interval['endDate']; $d = strtotime('+1 day', $d)) {

				if ($product['prices'][$d] == 'noPrice') {
					if ($d > $interval['startDate'] && ((int)date("z", $d - $interval['startDate'])) < $item[$uid]['available'])
						$item[$uid]['available'] = (int)date("z", $d - $interval['startDate']); /* day diff */
					else
						$item[$uid]['available'] = 0;
				}
				// reduce available days by blockDaysAfterBooking value
				if ($product['prices'][$d]['blockDaysAfterBooking'] > $item[$uid]['blockDaysAfterBooking']) {
					$item[$uid]['blockDaysAfterBooking'] = $product['prices'][$d]['blockDaysAfterBooking'];
				}
				// reduce available days by minimumStay value
				if ($this->getMinimumStay($product['prices'][$d]['minimumStay'], $interval['startDate']) > $item[$uid]['minimumStay']) {
					$item[$uid]['minimumStay'] = $this->getMinimumStay($product['prices'][$d]['minimumStay'], $interval['startDate']);
				}

				// get highest daySteps...
				if ($product['prices'][$d]['daySteps'] > $item[$uid]['daySteps']) {
					$item[$uid]['daySteps'] = $product['prices'][$d]['daySteps'];
				}
			}

			// find the minimum "maxAvailable" for the given product in the given interval
			// min of:
			//  - the (global) setting: $this->lConf['numCheckMaxInterval']
			//  - the end of the maximal booking period (numCheckNextMonths) ($this->lConf['numCheckNextMonths']).' months', strtotime('today')) - $interval['startDate']) / 86400)
			//  - the found "maxAvailable" after parsing existing bookings (step 1): $item[$uid]['maxAvailable']
			//  - the found "available" up to the next "noPrice" (?): $item[$uid]['available']

			if (strlen($item[$uid]['available']) > 0) {
				$item[$uid]['maxAvailable'] = (int)min($this->lConf['numCheckMaxInterval'], (1 + (strtotime('+ '.($this->lConf['numCheckNextMonths']).' months', strtotime('today')) - $interval['startDate']) / 86400), $item[$uid]['available'], $item[$uid]['maxAvailable']);
			} else
				$item[$uid]['maxAvailable'] = (int)min($this->lConf['numCheckMaxInterval'], (1 + (strtotime('+ '.($this->lConf['numCheckNextMonths']).' months', strtotime('today')) - $interval['startDate']) / 86400), $item[$uid]['maxAvailable']);

		}

		// 3. look for off-times and reduce maxAvailable for all items
		$maxAvailableAll = $this->lConf['numCheckMaxInterval'];
		foreach($this->lConf['OffTimeProductIDs'] as $id => $offTimeID) {
			if (isset($item[$offTimeID]['maxAvailable']) && $item[$offTimeID]['maxAvailable'] < $maxAvailableAll)
				$maxAvailableAll = $item[$offTimeID]['maxAvailable'];
		}

		// join all information from step 1. to 3. into array
		foreach($this->lConf['AvailableProductIDs'] as $id => $productID) {
			if (is_numeric($item[$productID]['maxAvailable']))
				$this->lConf['productDetails'][$productID]['maxAvailable'] = $item[$productID]['maxAvailable'];

			if (is_numeric($item[$productID]['minimumStay']) && $item[$productID]['minimumStay'] > 0)
				$this->lConf['productDetails'][$productID]['minimumStay'] = $item[$productID]['minimumStay'];
			else
				$this->lConf['productDetails'][$productID]['minimumStay'] = 1;

			if (is_numeric($item[$productID]['blockDaysAfterBooking']) && $item[$productID]['blockDaysAfterBooking'] > 1)
				$this->lConf['productDetails'][$productID]['maxAvailable'] -= $item[$productID]['blockDaysAfterBooking'] - 1;

			if (is_numeric($item[$productID]['daySteps']))
				$this->lConf['productDetails'][$productID]['daySteps'] = $item[$productID]['daySteps'];
			else
				$this->lConf['productDetails'][$productID]['daySteps'] = 1;

			if ($maxAvailableAll < $this->lConf['productDetails'][$productID]['maxAvailable'])
				$this->lConf['productDetails'][$productID]['maxAvailable'] = $maxAvailableAll;

			if ($this->lConf['productDetails'][$productID]['minimumStay'] > $this->lConf['productDetails'][$productID]['maxAvailable']) {
				$this->lConf['productDetails'][$productID]['maxAvailable'] = 0;
			}

		}

		return 0;
	}



	/**
	 * Logs
	 *
	 * @param	string	$logFile: filename with full path
	 * @return	void
	 */
	function log_request($logFile) {

		$ip = $_SERVER['REMOTE_ADDR'];
		$longisp = @gethostbyaddr($ip);

		$log = strftime("%Y-%m-%d %H:%M:%S").','.$ip.','.$longisp.','.strftime("%d.%m.%Y", $this->lConf['startDateStamp']).','.strftime("%d.%m.%Y", $this->lConf['endDateStamp']).','.$this->piVars['adultSelector'].','.$this->lConf['daySelector']."\n";

		//Daten schreiben
		$fp2=fopen($logFile, "a");
		if ($fp2) {
			fputs($fp2, $log);
			fclose($fp2);
		}
	}

	/**
	 * Send Confirmation Email
	 *
	 * HTML mail is not fully supported yet!
	 *
	 * @param	[type]		$key: ...
	 * @param	string		$send_errors: ...
	 * @return	number		of successfully sent emails
	 */
	function send_confirmation_email($key, &$send_errors) {

		$product = $this->lConf['productDetails'][$key];
		$customer = $this->lConf['customerData'];
		$text_mail .= $this->lConf['textConfirmEmail']."\n\n";
		$text_mail .= "===\n";

		// use TS form settings
 		if (is_array($this->lConf['form']) && count($this->lConf['form'])>1) {
			$text_mail .= $this->pi_getLL('product_title').": ".$product['title']."\n";

			foreach ($this->lConf['form'] as $formname => $form) {
				$formname = str_replace('.', '', $formname);
				// skip settings which are no form fields
				if (!is_array($form) || empty($customer[$formname]))
					continue;

				// special case: radio (and later checkbox):
				if (is_array($form['radio.'])) {

					$text_mail .= $this->getTSTitle($form['title.']). ': ' . $this->getTSTitle($form['radio.'][$customer[$formname]]['title.'])."\n";

				} else if (is_array($form['checkbox.'])) {

					$text_mail .= $this->getTSTitle($form['title.']). ': ' . "\n";
					foreach ($form['checkbox.'] as $checkboxname => $checkbox) {
						if (in_array($checkboxname, $customer[$formname]))
							$text_mail .= ' + ' . $this->getTSTitle($checkbox['title.']) . "\n";
					}

				} else if (is_array($form['option.'])) {

					foreach ($form['option.'] as $checkboxname => $checkbox) {
						if ($checkboxname == $customer[$formname])
							$text_mail .= $this->getTSTitle($form['title.']). ': ' . $this->getTSTitle($checkbox['title.']) . "\n";
					}

				}
				else
					$text_mail .= $this->getTSTitle($form['title.']). ': ' . $customer[$formname]."\n";

			}
		} else {
			$send_success = 0;
			return $send_success;
		}

		$text_mail .= "---------------------------------------------------------\n";

		// text for text/plain mail part
		$text_plain_mail = strip_tags($text_mail);
		$text_plain_mail .= $this->printCalculatedRates($key, $this->lConf['daySelector'], 0);
		$text_plain_mail .= "===\n";

		// text for text/html mail part
		$text_html_mail = str_replace("\n", "<br />", $text_mail);
		$text_html_mail .= str_replace("\n", "<br />", $this->printCalculatedRates($key, $this->lConf['daySelector'], 0));
		$text_html_mail .= "===<br/>";

		$result = 0;

		// the admin email may be set via flexform. If nothing is found
		// the system default mailfrom is taken
		if (!empty($this->lConf['EmailAddress']))
			$email_owner = array($this->lConf['EmailAddress'] => $this->lConf['EmailRealname']);
		else
			$email_owner = t3lib_utility_Mail::getSystemFrom();

		$email_customer = array($customer['address_email'] => $customer['address_name']);
		$subject_customer = $this->pi_getLL('email_your_booking').': '.$product['title'].' '.strftime("%a, %d.%m.%Y", $this->lConf['startDateStamp']).' - '.strftime("%a, %d.%m.%Y", $this->lConf['endDateStamp']);
		$subject_owner = $this->pi_getLL('email_new_booking').' '.$customer['address_name'].': '.$product['title'].' '.strftime("%a, %d.%m.%Y", $this->lConf['startDateStamp']).' - '.strftime("%a, %d.%m.%Y", $this->lConf['endDateStamp']);

		// TYPO3 4.5 has swiftmailer included
		// 1. send email to admin
		$mail = t3lib_div::makeInstance('t3lib_mail_Message');
		$mail->setFrom($email_owner);
		$mail->setTo($email_owner);
		$mail->setReplyTo($email_customer);
		$mail->setSubject($subject_owner);
		$mail->setBody($text_html_mail, 'text/html', 'utf-8');
		$mail->addPart(strip_tags($text_plain_mail), 'text/plain', 'utf-8');

		if ($mail->send() == 1)
			$send_success = 1;

		// 2. send email to customer
		if ($this->lConf['sendCustomerConfirmation'] == '1') {
			$mail = t3lib_div::makeInstance('t3lib_mail_Message');
			$mail->setFrom($email_owner);
			$mail->setTo($email_customer);
			$mail->setSubject($subject_customer);
			$mail->setBody($text_html_mail, 'text/html', 'utf-8');
			$mail->addPart(strip_tags($text_plain_mail), 'text/plain', 'utf-8');

			if ($mail->send() == 1)
				$send_success++;
		}

		return $send_success;
	}

	/**
	 * Insert Booking into Database
	 *
	 * @param	[type]		$request: ...
	 * @return	inserted		ID
	 */
	function insert_booking() {

		// assume that only one valid uid and and some offTimeProducts in ProductID..
		$product = $this->lConf['productDetails'][$this->lConf['AvailableProductIDs'][0]];
		$customer = $this->lConf['customerData'];


		$startDate = $this->lConf['startDateStamp'];

		if (isset($this->lConf['daySelector']))
			$endDate = strtotime('+'.($this->lConf['daySelector']+$product['prices'][$startDate]['blockDaysAfterBooking']).' day', $startDate);
		else
			$endDate = $startDate;

		$title = strftime('%Y%m%d', $startDate).', '.str_replace(',', ' ', $customer['address_name']).', '.str_replace(',', ' ', $customer['city']).', '.$customer['email'].', '.str_replace(',', ' ', $customer['telephone']);
		$editCode = md5($title.$this->lConf['ProductID']);

		$fields_values = array(
			'pid' => $this->lConf['PIDstorage'],
			'tstamp' => time(),
			'crdate' => time(),
			'startdate' => $startDate,
			'enddate' => $endDate,
			'title' => $title,
			'editcode' => $editCode,
			'deleted' => 0,
		);

		$query = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_abbooking_booking', $fields_values);

		$id_booking = $GLOBALS['TYPO3_DB']->sql_insert_id();

		// to be fixed if AvailableProductIDs is more than one...
		$fields_values = array(
			'uid_local' => $id_booking,
			'uid_foreign' => implode(',', $this->lConf['AvailableProductIDs']),
		);
  		$query = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_abbooking_booking_productid_mm', $fields_values);

		$id_inserted = $GLOBALS['TYPO3_DB']->sql_insert_id();


		foreach ($this->lConf['form'] as $formname => $form) {
			$formname = str_replace('.', '', $formname);
			$formvalue = str_replace('.', '', $customer[$formname]);

			// skip settings which are no form fields
			if (!is_array($form))
				continue;

			// skip empty values
			if (empty($formvalue))
				continue;

			// fill new meta-database:
			$fields_values = array(
				'pid' => $this->lConf['PIDstorage'],
				'tstamp' => time(),
				'crdate' => time(),
				'booking_id' => $id_booking,
				'meta_key' => $formname,
				'meta_value' => $formvalue,
			);

			$query = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_abbooking_booking_meta', $fields_values);

			$id_inserted = $GLOBALS['TYPO3_DB']->sql_insert_id();
		}

		$GLOBALS['TSFE']->clearPageCacheContent_pidList($this->getPluginPageIds());

		return $id_inserted;
	}

	/**
	 * Calculate the Minimum Stay Period
	 *
	 * @param	string	$minimumStay: minimum stay string
	 * @param	int		$startDate: start date timestamp
	 * @return	double	amount
	 */
	function getMinimumStay($minimumStay, $startDate) {

		$valueDetails = explode(',', $minimumStay);

		$today = strtotime(strftime("%Y-%m-%d 00:00:00"));
		$period = (int)(($startDate - $today)/86400);

		$valueArray['standardValue'] = $valueDetails[0];

		foreach ($valueDetails as $id => $value) {
			// W:2:2
			$dpd = explode(':', $value);
			if ($dpd[0] === 'W' && is_numeric($dpd[1]))
				$valueArray[$dpd[1] * 7] = $dpd[2];
			else if ($dpd[0] === 'D' && is_numeric($dpd[1]))
				$valueArray[$dpd[1]] = $dpd[2];
		}

		if (sizeof($valueArray)>0)
			foreach ($valueArray as $days => $value) {
				if (is_numeric($days))
					if ($period <= $days)
						$minimumStayToApply = $value;
			}

		if (is_numeric($minimumStayToApply)) {
			$valueArray['minimumStay'] = $minimumStayToApply;
		} else
			$valueArray['minimumStay'] = $valueArray['standardValue'];

		return $valueArray['minimumStay'];
	}

	/**
	 * Calculate the Rate per Day using the discount settings and the booking period
	 *
	 * @param	[type]		$rate: rate and discount settings
	 * @param	[type]		$period: booking period
	 * @return	double		amount
	 */
	function getRatePerDayAndPerson($rate, $period, $dayStep, $numPersons) {

		// get the discount array for price
		$discountRate = $this->getDiscountRate($rate['price'], $period, $dayStep);
		$discountRate['standardRate'] *= $numPersons;
		$discountRate['discountRate'] *= $numPersons;

		$discountRate['title'] = $this->getTSTitle($rate['title.']);

		if ($rate['priceIsPerWeek'] == 1) {
			$discountRate['incrementUse'] = 1;
			$discountRate['priceIsPerWeek'] = 1;
		} else {
			$discountRate['incrementUse'] = $dayStep;
			$discountRate['priceIsPerWeek'] = 0;
		}

		return $discountRate;
	}

	/**
	 * Calculate the Rate per Day using the discount settings and the booking period
	 *
	 * @param	[type]		$rate: rate and discount settings
	 * @param	[type]		$period: booking period
	 * @return	double		amount
	 */
	function getDiscountRate($rate, $period, $dayStep = 1) {

		$discountDetails = explode(',', $rate);

		$discountRate['standardRate'] = $discountDetails[0];
		foreach ($discountDetails as $id => $value) {
			// D:3:10%
			$dpd = explode(':', $value);
			if ($dpd[0] == 'D' && is_numeric($dpd[1]))
				$discountPeriodArray[$dpd[1]] = $dpd[2];
			else if ($dpd[0] == 'O') {
				$discountRate['isOption'] = 1;
				if (strlen($dpd[1]) == 0 || $dpd[1] == 1)
					$discountRate['isOptionSelected'] = 1;
				else
					$discountRate['isOptionSelected'] = 0;
			}
		}

		if (sizeof($discountPeriodArray)>0)
			foreach ($discountPeriodArray as $days => $value) {
				if ($period >= $days)
					$discountToApply = $value;
			}

		if (is_numeric($discountToApply)) {
			$discountRate['discountRate'] = $discountToApply;
		}
		else if (strpos($discountToApply, '%') > 0) {
			// e.g 1% --> strpos = 1; strpos = 0 makes no sence here
			$percentage = intval(substr($discountToApply, 0, strpos($discountToApply, '%')));
			$discountRate['discountRate'] = $discountRate['standardRate'] * (1-($percentage/100));
		} else
			$discountRate['discountRate'] = $discountRate['standardRate'];

		$discountRate['discount'] = $discountRate['standardRate'] - $discountRate['discountRate'];

		if ($dayStep == 7) {
			$discountRate['incrementUse'] = 1;
			$discountRate['priceIsPerWeek'] = 1;
		} else {
			$discountRate['incrementUse'] = $dayStep;
			$discountRate['priceIsPerWeek'] = 0;
		}

		return $discountRate;
	}

	/**
	 * Calculate the Rates
	 *
	 * @param	integer		$prId: the product id
	 * @param	integer		$period: booking period in full days
	 * @return	string		with amount, currency...
	 */
	function calcRates($prId, $period) {

		$priceDetails = array();

		$customer = $this->lConf['customerData'];
		$product = $this->lConf['productDetails'][$prId];

		$interval['startDate'] = $this->lConf['startDateStamp'];
		$interval['endDate'] = strtotime('+'.$period.' day', $this->lConf['startDateStamp']);

		$dayStep = 1;

		$max_amount = 0;
		// assuming every adult costs more;
		// e.g. 1 adult 10, 2 adults 20, 3 adults 25...
		// if you don't have prices per person, please use adult2 for the entire object
		for ($i=1; $i<=$product['capacitymax']; $i++) {

			if ($product['prices'][$interval['startDate']]['adult'.$i] >= $max_amount) {
				$max_amount = $product['prices'][$interval['startDate']]['adult'.$i];
				$max_persons = $i;
			}
			if ($max_amount > 0 && $i >= $this->lConf['adultSelector'])
					break;
		}

		// step through days from startdate to (enddate | maxAvailable) and add rate for every day
		$total_amount = 0;
		$priceArray['adult'.$max_persons] = '+';

		if ($this->lConf['adultSelector'] > $max_persons) {
			$adultX = $this->lConf['adultSelector'] - $max_persons;
			$priceArray['adultX'] = '*x';
		}

		$priceArray['extraComponent1'] = '*+';
		$priceArray['extraComponent2'] = '*+';

		foreach($priceArray as $key => $operator) {
			unset($cur_title);
			unset($pre_title);

			for ($d = $interval['startDate']; $d < $interval['endDate']; $d=strtotime('+'.$dayStep.' day', $d)) {

				$rateValue = $this->getDiscountRate($product['prices'][$d][$key], $period);
				if (!is_numeric($rateValue['discountRate']) || $rateValue['discountRate'] < 0)
						continue;

				switch ($key) {
					case 'extraComponent1':	$cur_title = $this->pi_getLL('extraComponent1') . $rateValue['discountRate'];
										break;
					case 'extraComponent2':	$cur_title = $this->pi_getLL('extraComponent2'). $rateValue['discountRate'];
										break;
					case 'adultX':			$cur_title = $this->pi_getLL('adultX'). $rateValue['discountRate'];
										break;
					default: $cur_title = str_replace(" ", "", $product['prices'][$d]['title'].$key);
				}

				$usedPrices[$cur_title]['numPersons'] = $max_persons;

				if ($operator == '*+') {
					$rateValue['discountRate'] = ($max_persons + $adultX) * $rateValue['discountRate'];
					$usedPrices[$cur_title]['numPersons'] = $max_persons + $adultX;
				}

				if ($operator == '*x'){
					$rateValue['discountRate'] = $adultX * $rateValue['discountRate'];
					$usedPrices[$cur_title]['numPersons'] = $adultX;
				}

				$usedPrices[$cur_title]['rateUsed']++;
				$usedPrices[$cur_title]['rateValue'] = $rateValue['discountRate'];
				$usedPrices[$cur_title]['discount'] = $rateValue['discount'];
				$usedPrices[$cur_title]['isOption'] = $rateValue['isOption'];
				$usedPrices[$cur_title]['isOptionSelected'] = $rateValue['isOptionSelected'];

				switch ($key) {
					case 'extraComponent1':	$usedPrices[$cur_title]['title'] = $this->pi_getLL('extraComponent1');
										break;
					case 'extraComponent2':	$usedPrices[$cur_title]['title'] = $this->pi_getLL('extraComponent2');
										break;
					case 'adultX':			$usedPrices[$cur_title]['title'] = $this->pi_getLL('adultX');
										break;
					default: $usedPrices[$cur_title]['title'] = $product['prices'][$d]['title'];
				}

				// get rate start and stop by comparing current and predecessor rate title
				if (empty($usedPrices[$cur_title]['dateStart'])) {
					$usedPrices[$cur_title]['dateStart'] = $d;
				}
				// if change title:
				if (strcmp($cur_title, $pre_title) != 0) {
					if (! empty($usedPrices[$pre_title]['dateStart'])) {
						if ($usedPrices[$pre_title]['rateUsed'] > 1)
							$usedPrices[$pre_title]['rateDates'][] = strftime('%a %x', $usedPrices[$pre_title]['dateStart']).' - '.strftime('%a %x', $d );
						else
							$usedPrices[$pre_title]['rateDates'][] = strftime('%a %x', strtotime('-1 day', $d)).' - '.strftime('%a %x', $d);
						unset($usedPrices[$pre_title]['dateStart']);
					}

				}
				// cleanup at the end
				if (strtotime('+1 day', $d) == strtotime('+'.$period.' day', $interval['startDate'])) {
					if (! empty($usedPrices[$cur_title]['dateStart'])) {
						if ($usedPrices[$cur_title]['dateStart'] < $d)
							$usedPrices[$cur_title]['rateDates'][] = strftime('%a %x', $usedPrices[$cur_title]['dateStart']).' - '.strftime('%a %x', strtotime('+1 day', $d));
						else
							$usedPrices[$cur_title]['rateDates'][] = strftime('%a %x', $d).' - '.strftime('%a %x', strtotime('+1 day', $d));
					}
				}
				$pre_title = $cur_title;
			}
		}
		// take currency from startDate
		$currency = $product['prices'][$this->lConf['startDateStamp']]['currency'];

		// input form element for selectable options
		if (is_array($usedPrices))
			foreach ($usedPrices as $title => $value) {
				if (empty($value['rateUsed']))
					continue;
				if ($value['rateUsed'] == 1)
					$text_periods = ' '.$this->pi_getLL('period');
				else
					$text_periods = ' '.$this->pi_getLL('periods');

				if ($value['isOption'] == 1) {
					if ($customer[$title] == 1 || ($customer[$title] == '' && $value['isOptionSelected'] == 1) ) {
						$checked = ' checked="checked"';
						$total_amount += $value['rateValue'] * $value['rateUsed'];
					}
					else {
						$checked = ' ';
					}
					$lDetails['form']  = '<input type="checkbox" name="tx_abbooking_pi1[rateOption][]" value="'.$title.'" '.$checked.'>';
					$lDetails['form'] .= '<input type="hidden" name="tx_abbooking_pi1[rateOption][]" value="'.$title.'_1">';
				}
				else {
					$lDetails['form'] = '';
					$total_amount += $value['rateValue'] * $value['rateUsed'];
				}
				$lDetails['dates'] = $value['rateDates'];
				// some useful texts
				if ($this->lConf['showPersonsSelector'] == 1) {
					if ($value['numPersons'] == 1)
						$text_persons = ', '.$value['numPersons'].' '.$this->pi_getLL('person');
					else
						$text_persons = ', '.$value['numPersons'].' '.$this->pi_getLL('persons');
				}

				$lDetails['value'] = $value['rateUsed'].' x '.number_format($value['rateValue'], 2, ',', '').' '.$currency.' = '.number_format($value['rateUsed']*$value['rateValue'],2,',','').' '.$currency;

				$lDetails['description'] = $value['rateUsed'].' '.$text_periods.', '.$value['title'].$text_persons;

				$priceDetails[] = $lDetails;
			}

		// apply discount; discountValue is taken from startDate
		$discountrate = $product['prices'][$this->lConf['startDateStamp']]['discount'];

		if (intval($discountrate)>0 && $period >= $product['prices'][$this->lConf['startDateStamp']]['discountPeriod']) {
			$discountValue = round($total_amount * ($discountrate/100), 2);
			$total_amount -= $discountValue;

			$lDetails['form'] = '';
			$lDetails['description'] = $this->pi_getLL('discount').' '.round($discountrate,0).'%';
			$lDetails['dates'] = '';
			$lDetails['value'] = '-'.number_format($discountValue, 2, ',', '').' '.$currency;
			$priceDetails[] = $lDetails;
		}

		// get singleComponent 1 and 2 from startDate
		for ($i=1; $i<3; $i++) {
			if ($product['prices'][$interval['startDate']]['singleComponent'.$i]>0) {
				$s2value = $this->getDiscountRate($product['prices'][$interval['startDate']]['singleComponent'.$i], $period);

				$singleComponent = $s2value['discountRate'];
				if ($singleComponent >= 0) {
					if ($s2value['isOption'] == 1) {
						if ($customer['singleComponent'.$i] == 1 || ($customer['singleComponent'.$i] == '' && $s2value['isOptionSelected'] == 1) ) {
							$checked = ' checked="checked"';
							$total_amount += $singleComponent;
						}
						else {
							$checked = ' ';
						}
						$lDetails['form']  = '<input type="checkbox" name="tx_abbooking_pi1[rateOption][]" value="singleComponent'.$i.'" '.$checked.'>';
						$lDetails['form'] .= '<input type="hidden" name="tx_abbooking_pi1[rateOption][]" value="singleComponent'.$i.'_1">';
					}
					else {
						$lDetails['form'] = '';
						$total_amount += $singleComponent;
					}
					$lDetails['description'] = $this->pi_getLL('specialComponent'.$i);
					$lDetails['dates'] = '';
					$lDetails['value'] = number_format($singleComponent, 2, ',', '').' '.$currency;
					$priceDetails[] = $lDetails;
				}
			}
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
	 * @param	array		$product key
	 * @param	int		$period: ...
	 * @param	bool		$printHTML: ...
	 * @param	[type]		$printForm: ...
	 * @return	string		string for output...
	 */
	function printCalculatedRates($key, $period, $printHTML = 1, $printForm = 1) {

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

							if ($id%2 == 0)
								$cssExtra = "even";
							else
								$cssExtra = "odd";

							if ($id == 0)
								$cssExtra = "first";

							$lengthOfDescription = strlen($priceLine['description'])+2+strlen($priceLine['value']);
							$content .= '<li class="'.$cssExtra.'">';
							if ($printForm == 1) {
								$content .= $priceLine['form'];
								if (!empty($priceLine['form']) && strpos($priceLine['form'], 'checked') === FALSE) {
									$priceDeselectedPre = '<span class="priceDeselected">';
									$priceDeselectedPost = '</span>';
								} else {
									unset($priceDeselectedPre);
									unset($priceDeselectedPost);
								}
							}
							$content .= $priceDeselectedPre.'<span class="priceDescription">'.$priceLine['description'].'</span>'.$priceDeselectedPost;
							$content .= $priceDeselectedPre.'<span class="priceValue">'.$priceLine['value'].'</span>'.$priceDeselectedPost;
 							if (!empty($priceLine['dates']) && empty($priceDeselectedPre))
								foreach($priceLine['dates'] as $id => $dateString) {
									$content .= '<span class="priceDates">'.$dateString.'</span>';
								}
							$content .= '</li>';
						}
					$content .= '</ul></div>';
				}
				$content .= '<div class="priceTotal"><span class="priceDescription"><b>'.$this->pi_getLL('total_amount').': </b></span>';
				$content .= '<span class="priceValue"><b>'.$rates['textPriceTotalAmount'].'</b></div>';
			} else {
				// without HTML e.g. for mail output
				if ($this->lConf['showPriceDetails'] == '1') {
					foreach ($rates['priceDetails'] as $id => $priceLine) {
						// skip pricedetails for deselected positions
						if (!empty($priceLine['form']) && strpos($priceLine['form'], 'checked') === FALSE)
							continue;

						$lengthOfDescription = strlen($priceLine['description'])+2+strlen($priceLine['value']);
						$content .= $priceLine['description'].": ";
						if (strlen($priceLine['description'])>40) {
							//newline if text is to long
							$content .= "\n";
							$lengthOfDescription = strlen($priceLine['value']);
						}
						for ($i=(50-$lengthOfDescription); $i>0; $i--)
							$content.= ' ';
						$content .= $priceLine['value']."\n";
						if (!empty($priceLine['dates']))
							foreach($priceLine['dates'] as $id => $dateString) {
								$content .= $dateString."\n";
							}
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
	 * Get all uids of ab_booking plugin
	 *
	 * @return	string	csv list of page uids
	 */
	function getPluginPageIds() {

		$select = 'DISTINCT pid';
		$table = 'tt_content';
		$query = 'list_type = \'ab_booking_pi1\' AND hidden = 0 AND deleted = 0';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table ,$query);

		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$pluginPageIds[] = $row['pid'];
		};

		return implode(',', $pluginPageIds);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/pi1/class.tx_abbooking_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/pi1/class.tx_abbooking_pi1.php']);
}

?>
