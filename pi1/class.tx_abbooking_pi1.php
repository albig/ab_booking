<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 - 2011 Alexander Bigga <linux@bigga.de>
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
 *   63: class tx_abbooking_pi1 extends tslib_pibase
 *   75:     function main($content, $conf)
 *  232:     function init()
 *  384:     public function formBookingUserData($conf, $product, $stage)
 *  649:     public function formCheckAvailability()
 *  748:     public function get_product_properties($ProductUID)
 *  805:     function check_availability($storagePid)
 *  914:     function formVerifyUserInput()
 *  998:     function log_request($logFile)
 * 1024:     function send_confirmation_email($key, &$send_errors)
 * 1153:     function insert_booking()
 * 1204:     function getMinimumStay($minimumStay, $startDate)
 * 1244:     function getDiscountRate($rate, $period)
 * 1285:     function calcRates($key, $period)
 * 1477:     function printCalculatedRates($key, $period, $printHTML = 1, $printForm = 1)
 * 1562:     function isRobot()
 *
 * TOTAL FUNCTIONS: 15
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
require_once(t3lib_extMgm::extPath('ab_booking').'lib/class.tx_abbooking_form.php'); // load form

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
		if (version_compare(TYPO3_version, '4.5', '<'))
			if (t3lib_extMgm::isLoaded('ab_swiftmailer', 0)) {
				require_once(t3lib_extMgm::extPath('ab_swiftmailer').'pi1/class.tx_abswiftmailer_pi1.php'); // load swift lib
			}

		$this->cssBooking = str_replace(PATH_site,'',t3lib_div::getFileAbsFileName($this->conf['file.']['cssBooking']));
                $GLOBALS['TSFE']->additionalHeaderData['abbooking_css'] = '<link href="'.$this->cssBooking.'" rel="stylesheet" type="text/css" />'."\n";

//~ print_r($this->piVars);
		// get all initial settings
		$this->init();

		if (!isset($interval['startDate'])) {
			$interval['startDate'] = $this->lConf['startDateStamp'];
			$interval['endDate'] = strtotime('+ '.$this->lConf['numCheckMaxInterval'].' days', $interval['startDate']);
		}
		if (!isset($interval['endDate'])) {
			$interval['endDate'] = strtotime('+ '.$this->lConf['numCheckMaxInterval'].' days', $interval['startDate']);
		}

		if (!isset($interval['startList'])) {
			$interval['startList'] = $interval['startDate'];
			$interval['endList'] = $interval['endDate'];
		}


//~ print_r($this->lConf);
		switch ( $this->lConf['mode'] ) {
			case 'form':
				// check first for submit button and second for View
				if (isset ($this->piVars['submit_button_edit']) && $this->lConf['ABdo'] == 'bor3')
					$this->lConf['ABdo'] = 'bor0';


				// update/check all rates
				tx_abbooking_div::getAllRates($interval);
				$this->check_availability($this->lConf['PIDstorage']);

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
							$out .= '<p>'.strftime("%A, %d.%m.%Y", $this->lConf['startDateStamp']).' - ';
							$out .= ' '.strftime("%A, %d.%m.%Y", $this->lConf['endDateStamp']).'</p><br />';
							$out .= '<p>'.$this->pi_getLL('feld_naechte').': '.$this->lConf['daySelector'].'</p>';
							if ($this->lConf['showPersonsSelector']==1)
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
						$out .= $this->formBookingUserData($stage = 1);
						break;
					case 'bor1':
						$this->lConf['stage'] = 1;
						$out .= $this->formBookingUserData($stage = 1);
						break;
					case 'bor2':
						$this->lConf['stage'] = 2;
						$out .= $this->formBookingUserData($stage = 2);
						break;
					case 'bor3':
						/* --------------------------- */
						/* booking final - send mails  */
						/* --------------------------- */
						$this->lConf['stage'] = 3;
						if ($this->lConf['useTSconfiguration'] == 1)
							$numErrors = tx_abbooking_form::formVerifyUserInput();
						else
							$numErrors = $this->formVerifyUserInput();


						if ($numErrors == 0) {
							$out .= tx_abbooking_div::printBookingStep($stage = 4);
							$result= $this->send_confirmation_email($product['uid'], $send_errors);
							if ($result == 2) {
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
							$out .= $this->formBookingUserData($stage = 2);
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
						// update/check all rates
						tx_abbooking_div::getAllRates($interval);
						$out .= $this->formCheckAvailability();
						break;
					case 'CALENDAR':
						$out .= tx_abbooking_div::printAvailabilityCalendarDiv($this->lConf['ProductID'], (int)$this->lConf['numMonthsRows'], (int)$this->lConf['numMonthsCols']);
						break;
					case 'CALENDAR LINE':
						$out .= tx_abbooking_div::printAvailabilityCalendarLine($this->lConf['ProductID']);
						break;
					case 'CHECKIN OVERVIEW':
						$out .= tx_abbooking_div::printCheckinOverview($this->lConf['ProductID']);
						break;
					case '5': // booking rate overview
						$out .= tx_abbooking_div::printBookingRateOverview($this->lConf['ProductID']);
						break;
					default:
						/* ------------------------- */
						/* show calendar             */
						/* ------------------------- */
						$out .= tx_abbooking_div::printAvailabilityCalendarDiv($this->lConf['ProductID'], (int)$this->lConf['numMonthsRows'], (int)$this->lConf['numMonthsCols']);
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
			list($this->lConf['startDateStamp'], $this->lConf['daySelector'], $this->lConf['adultSelector'], $this->lConf['ABProductID'], $this->lConf['ABuidpid'], $this->lConf['PIDbooking'], $this->lConf['ABdo']) = explode("_", $this->piVars['ABx']);
		}

		// set dateFormat - prefered from TS otherwise from language defaults
		if ($this->conf['dateFormat'] != '') {
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
			$this->lConf['startDateStamp'] = date_format(date_time_set(date_create_from_format($this->lConf['dateFormat'],$this->piVars['checkinDate']), 0, 0), 'U');
		}

		if (isset($this->piVars['daySelector']))
			$this->lConf['daySelector'] = $this->piVars['daySelector'];
		if (isset($this->piVars['adultSelector']))
			$this->lConf['adultSelector'] = $this->piVars['adultSelector'];
		if (isset($this->piVars['childSelector']))
			$this->lConf['numChildren'] = $this->piVars['childSelector'];
		if (isset($this->piVars['teenSelector']))
			$this->lConf['numTeens'] = $this->piVars['teenSelector'];

		if (isset($this->piVars['ABuidpid']))
			$this->lConf['ABuidpid'] = $this->piVars['ABuidpid'];
		if (isset($this->lConf['ABProductID']))
			$this->lConf['ProductID'] = $this->lConf['ABProductID'];


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
		if (! isset($this->lConf['numCheckMinInterval']))
			$this->lConf['numCheckMinInterval'] = 1;

		if (! isset($this->lConf['numCheckMaxInterval']))
			$this->lConf['numCheckMaxInterval'] = 21;

		if (is_numeric($this->lConf['PIDbooking']))
			$this->lConf['gotoPID'] = $this->lConf['PIDbooking'];
		else {
			$this->lConf['gotoPID'] = $GLOBALS['TSFE']->id;
		}

		// set defaults if still empty:
		if (! isset($this->lConf['adultSelector']))
			$this->lConf['adultSelector'] = $this->lConf['numDefaultPersons'];
		if (! isset($this->lConf['daySelector']))
			$this->lConf['daySelector'] = $this->lConf['numDefaultNights'];
		if (! isset($this->lConf['daySteps']))
			$this->lConf['daySteps'] = 1;

		// ---------------------------------
		// calculate endDateStamp
		// ---------------------------------
		if (empty($this->lConf['startDateStamp']))
			$this->lConf['startDateStamp'] = strtotime(strftime("%Y-%m-%d 00:00:00"));
//~ 		if (empty($this->lConf['daySelector']))
//~ 			$this->lConf['endDateStamp'] = strtotime('+ '.$this->lConf['numCheckMaxInterval'].' days', $this->lConf['startDateStamp']);
//~ 		else
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
		// if set, use the new (as of 0.6.0) TS configuration instead of db table settings
		if (intval($this->conf['useTSconfiguration']) == 1) {
			$this->lConf['useTSconfiguration'] = 1;
			if (isset($this->lConf['ProductID'])) {
				$this->lConf['productDetails'] = $this->getTSproductProperties($this->lConf['ProductID']);
				// merge array of available and offtime product IDs
				$this->lConf['ProductID'] = implode(',', array_unique(array_merge(explode(',', $this->lConf['ProductID']), $this->lConf['OffTimeProductIDs'])));
			}
		} else {
			if (isset($this->lConf['ProductID'])) {
				$this->lConf['productDetails'] = $this->get_product_properties($this->lConf['ProductID']);
				// merge array of available and offtime product IDs
				$this->lConf['ProductID'] = implode(',', array_unique(array_merge(explode(',', $this->lConf['ProductID']), $this->lConf['OffTimeProductIDs'])));
			}
		}

		// save session data
		if (isset($this->piVars['submit_button'])) {
			$customerData = $this->piVars; // copy all - is this bad?
			$customerData["address_name"] = $this->piVars['name'];
			$customerData["address_street"] = $this->piVars['street'];
			$customerData["address_postalcode"] = $this->piVars['zip'];
			$customerData["address_town"] = $this->piVars['town'];
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
			$GLOBALS["TSFE"]->fe_user->setKey("ses","customData", $customerData);
		} else {
			$customerData = $GLOBALS["TSFE"]->fe_user->getKey("ses","customData");
		}
		$this->lConf['customerData'] = $customerData;
	}


	/**
	 * Request Formular
	 * The customer enters his personal data and submits the form.
	 *
	 * @param	[type]		$conf: ...
	 * @param	integer		$stage of booking process
	 * @param	[type]		$stage: ...
	 * @return	HTML		form with booking details
	 */
	public function formBookingUserData($stage) {

		// jump to dynamic form if configured
		if ($this->lConf['useTSconfiguration'] == 1 && count($this->lConf['form'])>1)
			return tx_abbooking_form::printUserForm($stage);

		$interval = array();
		$product = $this->lConf['productDetails'][$this->lConf['AvailableProductIDs'][0]];
		if (empty($product)) {
			$content = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
			return $content;
		}


		$interval['startDate'] = $this->lConf['startDateStamp'];
		$interval['endDate'] = $this->lConf['endDateStamp'];
		$interval['startList'] = strtotime('-2 day', $interval['startDate']);
		$interval['endList'] = strtotime('+2 day', $interval['startDate']);

		if ($stage > 1) {
			$numErrors = $this->formVerifyUserInput();
			if ($stage == 2 && $numErrors > 0)
					$stage = 1;
			else
					$stage = 3;
			if ($stage == 3 && $numErrors > 0)
					$stage = 2;
		}
		$customer = $this->lConf['customerData'];

		$content .= tx_abbooking_div::printBookingStep($stage);

		$content .='<div class="requestForm">';

		$content .='<h3>'.htmlspecialchars($this->pi_getLL('title_request')).' '.$product['detailsRaw']['header'].'</h3>';

		$content .= '<p class=available><b>'.$this->pi_getLL('result_available').'</b>';
		$content .= ' '.strftime("%A, %d.%m.%Y", $this->lConf['startDateStamp']).' - ';
		$availableMaxDate = strtotime('+ '.$product['maxAvailable'].' days', $this->lConf['startDateStamp']);
		$content .= ' '.strftime("%A, %d.%m.%Y", $availableMaxDate);
		$content .= '</p><br />';
		$content .= tx_abbooking_div::printAvailabilityCalendarLine($this->lConf['ProductID'], $interval);



		$selected='selected="selected"';
		if (isset($this->lConf['adultSelector']))
			if ($this->lConf['adultSelector'] > $product['capacitymax'])
				$seladultSelector[$product['capacitymax']] = $selected;
			else if ($this->lConf['adultSelector'] < $product['capacitymin'])
				$seladultSelector[$product['capacitymin']] = $selected;
			else
				$seladultSelector[$this->lConf['adultSelector']] = $selected;
		else
			$seladultSelector[2] = $selected;

		if (isset($this->lConf['daySelector']))
			$seldaySelector[$this->lConf['daySelector']] = $selected;
		else
			$seldaySelector[2] = $selected;

		$contentError = '';
		/* handle errors */
		if (isset($this->form_errors['name'])) {
			$ErrorName='class="error"';
			$contentError.='<li>'.$this->form_errors['name'].'</li>';
		}
		if (isset($this->form_errors['street'])) {
			$ErrorStreet='class="error"';
			$contentError.='<li>'.$this->form_errors['street'].'</li>';
		}
		if (isset($this->form_errors['email'])) {
			$ErrorEmail='class="error"';
			$contentError.='<li>'.$this->form_errors['email'].'</li>';
		}
		if (isset($this->form_errors['town'])) {
			$ErrorTown='class="error"';
			$contentError.='<li>'.$this->form_errors['town'].'</li>';
		}
		if (isset($this->form_errors['PLZ'])) {
			$ErrorPLZ='class="error"';
			$contentError.='<li>'.$this->form_errors['PLZ'].'</li>';
		}
		if (isset($this->form_errors['vacancies'])) {
			$ErrorVacancies='class="error"';
			$contentError.='<li>'.$this->form_errors['vacancies'].'</li>';
		}
		if (isset($this->form_errors['vacancies_limited'])) {
			$ErrorVacanciesLimited='class="error"';
			$contentError.='<li>'.$this->form_errors['vacancies_limited'].'</li>';
		}
		if (isset($this->form_errors['startDateInThePast'])) {
			$ErrorVacancies='class="error"';
			$contentError.='<li>'.$this->form_errors['startDateInThePast'].'</li>';
		}
		if (isset($this->form_errors['endDateNotValid'])) {
			$ErrorVacanciesLimited='class="error"';
			$contentError.='<li>'.$this->form_errors['endDateNotValid'].'</li>';
		}
		if (isset($this->form_errors['daySelectorNotValid'])) {
			$ErrorVacanciesLimited='class="error"';
			$contentError.='<li>'.$this->form_errors['daySelectorNotValid'].'</li>';
		}

		if ($product['minimumStay'] > $product['maxAvailable']) {
			$ErrorVacanciesLimited='class="error"';
			if ($product['minimumStay'] == 1)
				$text_periods = ' '.$this->pi_getLL('period');
			else
				$text_periods = ' '.$this->pi_getLL('periods');

			$contentError.='<li>'.$this->pi_getLL('error_minimumStay').' '.$product['minimumStay'].' '.$text_periods.'</li>';
		}

		if (!empty($contentError)) {
			$content.='<div class="errorForm">';
			$content.='<ul>';
			$content.= $contentError;
			$content.='</ul>';
			$content.='</div>';
		}

		// check if configured email is present
		if (version_compare(TYPO3_version, '4.5', '<'))
			if ((!class_exists('tx_abswiftmailer_pi1') || !$this->lConf['useSwiftMailer']) && empty($this->lConf['EmailAddress'])) {
				$content.= '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noEmailConfigured').'</b></h2>';
			}

		/* handle stages */
		if ($stage == 3) {
			$content.='<div class="noteForm"><p>'.htmlspecialchars($this->pi_getLL('please_confirm')).'</p></div>';

			$SubmitButtonEdit=htmlspecialchars($this->pi_getLL('submit_button_edit'));
			$SubmitButton=htmlspecialchars($this->pi_getLL('submit_button_final'));

			$content.='<form class="requestForm" action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">
					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_name')).'</b></div>

					<div class="noteForm">
					<p class="yourSettings">'.htmlspecialchars($customer['address_name']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[name]" value="'.htmlspecialchars($customer['address_name']).'" >
					<p class="yourSettings">'.htmlspecialchars($customer['address_street']).'</p>
					<input  type="hidden" name="'.$this->prefixId.'[street]" value="'.htmlspecialchars($customer['address_street']).'" >
					<p class="yourSettings">'.htmlspecialchars($customer['address_postalcode']).' '.htmlspecialchars($customer['address_town']).'</p>
					<input  type="hidden" size="5" maxlength="10" name="'.$this->prefixId.'[zip]" value="'.htmlspecialchars($customer['address_postalcode']).'" >
					<input  type="hidden" name="'.$this->prefixId.'[town]" value="'.htmlspecialchars($customer['address_town']).'">
					<p class="yourSettings">'.htmlspecialchars($customer['address_email']).'</p>
					<input  type="hidden" name="'.$this->prefixId.'[email]" value="'.htmlspecialchars($customer['address_email']).'" >
					<p class="yourSettings">'.htmlspecialchars($customer['address_telephone']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[telephone]" value="'.htmlspecialchars($customer['address_telephone']).'" >
					</div>

					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_anreise')).'</b></div>
					<div class="noteForm">
					<p class="yourSettings">'.strftime("%A, %d.%m.%Y", $this->lConf['startDateStamp']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[checkinDateStamp]" value="'.$this->lConf['startDateStamp'].'" >
					</div>

					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_abreise')).'</b></div>
					<div class="noteForm">
					<p class="yourSettings">'.strftime("%A, %d.%m.%Y", $this->lConf['endDateStamp']).'</p>
					</div>

					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_naechte')).':</b></div>
					<div class="noteForm">
					<p class="yourSettings">'.htmlspecialchars($this->piVars['daySelector']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[daySelector]" value="'.htmlspecialchars($this->lConf['daySelector']).'" >
					</div>

					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_personen')).':</b></div>
					<div class="noteForm">
					<p class="yourSettings">'.htmlspecialchars($this->piVars['adultSelector']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[adultSelector]" value="'.htmlspecialchars($this->piVars['adultSelector']).'" >
					</div>

					<div class="elementForm">'.htmlspecialchars($this->pi_getLL('feld_mitteilung')).'</div>
					<div class="noteForm">
					<p class="yourSettings">'.htmlspecialchars($this->piVars['mitteilung']).'</p>
					<input type="hidden" name="'.$this->prefixId.'[mitteilung]" value="'.$this->piVars['mitteilung'].'">
					</div>';

					$content .= $this->printCalculatedRates($product['uid'], $this->piVars['daySelector'], 1);

					$params_united = $this->lConf['startDateStamp'].'_'.$this->lConf['daySelector'].'_'.$this->lConf['adultSelector'].'_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor'.($stage);
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

			$content.='<form  class="requestForm" action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">
					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_name')).'</b></div>
					<input '.$ErrorName.' type="text" name="'.$this->prefixId.'[name]" value="'.htmlspecialchars($customer['address_name']).'" ><br/>

					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_street')).'</b></div>
					<input '.$ErrorStreet.' type="text" name="'.$this->prefixId.'[street]" value="'.htmlspecialchars($customer['address_street']).'" ><br/>

					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_zip')).' '.htmlspecialchars($this->pi_getLL('feld_town')).'</b></div>
					<input '.$ErrorPLZ.' type="text" size="5" maxlength="10" name="'.$this->prefixId.'[zip]" value="'.htmlspecialchars($customer['address_postalcode']).'" >
					<input '.$ErrorTown.' type="text" name="'.$this->prefixId.'[town]" value="'.htmlspecialchars($customer['address_town']).'"><br/>

					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_email')).'</b></div>
					<input '.$ErrorEmail.' type="text" name="'.$this->prefixId.'[email]" value="'.htmlspecialchars($customer['address_email']).'" ><br/>
					'.htmlspecialchars($this->pi_getLL('feld_telephone')).'<br/><input type="text" name="'.$this->prefixId.'[telephone]" value="'.htmlspecialchars($customer['address_telephone']).'" ><br/>
					<b>'.htmlspecialchars($this->pi_getLL('feld_anreise')).'</b><br/>';
			$content .= tx_abbooking_div::getJSCalendarInput($this->prefixId.'[checkinDate]', $startdate, $ErrorVacancies);
			$content .= '<br/>
					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_naechte')).'</b></div>
						<select '.$ErrorVacanciesLimited.' name="'.$this->prefixId.'[daySelector]" size="1">';

					/* how many days/nights are available? */
					for ($i = $product['minimumStay']; $i <= $product['maxAvailable']; $i+=$product['daySteps']) {
							$endDate = strtotime('+'.$i.' day', $startdate);
							$content.='<option '.$seldaySelector[$i].' value='.$i.'>'.$i.' ('.strftime('%d.%m.%Y', $endDate).')</option>';
					}
					$content .= '</select><br/>
					<div class="elementForm"><b>'.htmlspecialchars($this->pi_getLL('feld_personen')).'</b></div>
						<select name="'.$this->prefixId.'[adultSelector]" size="1">';

					/* how many persons are possible? */
					for ($i = $product['capacitymin']; $i<=$product['capacitymax']; $i++) {
						if ($this->lConf['numCheckMaxInterval'] < $this->piVars['daySelector'])
							$daySelector = $this->lConf['numCheckMaxInterval'];
						else
							$daySelector = $this->piVars['daySelector'];
						$content.='<option '.$seladultSelector[$i].' value='.$i.'>'.$i.' </option>';
					}

					$params_united = '0_0_0_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor'.($stage + 1);
					$params = array (
						$this->prefixId.'[ABx]' => $params_united,
					);

					$content .= '</select><br/>';

					$content .= $this->printCalculatedRates($product['uid'], $this->lConf['daySelector'], 1);

					$content .= '<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'">';
					$content .= '<div class="elementForm">'.htmlspecialchars($this->pi_getLL('feld_mitteilung')).'</div>
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
	public function formCheckAvailability() {

		// assume that only one valid uid and some offTimeProducts in ProductID..
		$product = $this->lConf['productDetails'][$this->lConf['AvailableProductIDs'][0]];
//~ print_r("-formCheckAvailability---Start---\n");
//~ print_r($product);
//~ print_r("-formCheckAvailability---End---\n");
		if (empty($this->lConf['productDetails'])) {
			$content = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
			return $content;
		} else {
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
			// reset checkinDate
			unset($this->lConf['startDate']);
			unset($this->lConf['startDateStamp']);
		}
		if (isset($this->form_errors['endDateTooFarInFuture'])) {
			$ErrorVacancies='class="error"';
			$content.='<h2><b>'.$this->form_errors['endDateTooFarInFuture'].'</b></h2>';
		}

		$content .='<form action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">
				<label for="'.$this->prefixId.'[checkinDate]'.$this->lConf['uidpid'].'_hr"><b>'.htmlspecialchars($this->pi_getLL('feld_anreise')).'</b></label>';
//				<label for="'.$this->prefixId.'[checkinDate]'.$this->lConf['uidpid'].'_cb">&nbsp;</label><br/>';

		if (isset($this->lConf['startDateStamp']))
			$startdate = $this->lConf['startDateStamp'];
		else
			$startdate = time();

		$content .= tx_abbooking_div::getJSCalendarInput($this->prefixId.'[checkinDate]'.$this->lConf['uidpid'], $startdate, $ErrorVacancies);

		$content .= '<br />
				<label for="fielddaySelector"><b>'.htmlspecialchars($this->pi_getLL('feld_naechte')).'</b></label><br/>
				<select '.$ErrorVacanciesLimited.' name="'.$this->prefixId.'[daySelector]" id="fielddaySelector" size="1">';
		for ($i = $this->lConf['numCheckMinInterval']; $i<=$this->lConf['numCheckMaxInterval']; $i++) {
			$content.='<option '.$seldaySelector[$i].' value='.$i.'>'.$i.'</option>';
		}
		$content .= '</select><br/>';

		if ($this->lConf['showPersonsSelector'] == 1) {
			$content .= '<label for="fieldadultSelector"><b>'.htmlspecialchars($this->pi_getLL('feld_personen')).'</b></label><br/>
					<select name="'.$this->prefixId.'[adultSelector]" id="fieldadultSelector" size="1">';
			/* how many persons are possible? */
			for ($i = 1; $i<=$overallCapacity; $i++) {
					$content.='<option '.$seladultSelector[$i].' value='.$i.'>'.$i.'</option>';
			}
			$content .= '</select><br/>';
		} else
			$content .= '<input type="hidden" name="'.$this->prefixId.'[adultSelector]" value="'.$this->lConf['numDefaultPersons'].'">';

		$params_united = '0_0_0_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_availabilityList';
		$params = array (
			$this->prefixId.'[ABx]' => $params_united,
		);

		$content .= '
		<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'"><br />';
		if (!$this->isRobot())
			$content .= '<input class="submit" type="submit" name="'.$this->prefixId.'[submit_button_checkavailability]" value="'.htmlspecialchars($this->pi_getLL('submit_button_label')).'">';
		$content .= '</form>
			<br />
		';

		return $content;
	}


	/**
	 * get all properties, description text and prices of a product
	 * with the given UID
	 *
	 * @param	[type]		$ProductUID: ...
	 * @return	[type]		array of properties..
	 */
	public function get_product_properties($ProductUID) {

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
			$where_extra = 'capacitymax > 0 ';
			$product_properties =  tx_abbooking_div::getRecordRaw('tx_abbooking_product', $this->lConf['PIDstorage'], $ProductUID, $where_extra);

			$pi = 0;
			// step through found products
			foreach ( $product_properties as $uid => $product ) {
				$availableProductIDs[$pi] = $uid;
				$pi++;

				// get all prices for given UID and given dates
//~ 				$product['prices'] = tx_abbooking_div::getPrices($uid, $interval);

				$product['maxAvailable'] = $this->lConf['numCheckMaxInterval'];
//~ print_r("get_product_properties\n");
//~ print_r($product);
				// get uid and pid of the detailed description content element
				$uidpid = explode("#", $product['uiddetails']);
				if (is_numeric($uidpid[0])) {
					$product['detailsRaw'] =  array_shift(tx_abbooking_div::getRecordRaw('tt_content', $uidpid[0], $uidpid[1]));
  				}
				$product_properties_return[$uid] = $product;
			}
		}

		$offTimeProductIDs  = array_diff(explode(",", $ProductUID), $availableProductIDs);
		$this->lConf['AvailableProductIDs'] = $availableProductIDs;
		$this->lConf['OffTimeProductIDs'] = $offTimeProductIDs;

		return $product_properties_return;
	}

	/**
	 * get title text with current language setting
	 *
	 * @param	[type]		$ProductUID: ...
	 * @return	[type]		array of properties..
	 */
	public function getTSTitle($title) {

		$lang = $GLOBALS['TSFE']->config['config']['language'];

		return $title[$lang];
	}

	/**
	 * get all properties, description text and prices of a product
	 * with the given UID
	 *
	 * @param	[type]		$ProductUID: ...
	 * @return	[type]		array of properties..
	 */
	public function getTSproductProperties($ProductUID) {

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

//~ print_r("ProductUID\n");
//~ print_r($ProductUID);
//~ print_r("\n");
		if (!empty($ProductUID)) {
			// SELECT:
			// FixMe: we have to set the closingtimes by TS
			#$where_extra = 'capacitymax > 0 ';
			$table = 'tx_abbooking_product';
			$select = 'uid, tstitle, uiddetails';
			$order = '';
			$group = '';
			$limit = '';
			$where = 'pid='.$this->lConf['PIDstorage'].' AND uid IN('.$ProductUID.') ';
			//AND (sys_language_uid IN (-1,0) OR (sys_language_uid = ' .$GLOBALS['TSFE']->sys_language_uid. '))';
			// use the TYPO3 default function for adding hidden = 0, deleted = 0, group and date statements
			$where  .= $GLOBALS['TSFE']->sys_page->enableFields($table, $show_hidden = 0, $ignore_array);
			if (!empty($where_extra))
				$where  .= ' AND '.$where_extra;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $group, $order, $limit);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$out[$row['uid']] = $row;
			}
			$product_properties = $out;

//~ print_r("product_properties\n");
//~ print_r($product_properties);
//~ print_r("\n");
			// step through found products
			foreach ( $product_properties as $uid => $product ) {

				$product = array_merge($product, $this->conf['products.'][$product['tstitle'].'.']);
				$product['title'] = $this->getTSTitle($product['title.']);

				$availableProductIDs[] = $uid;

				$product['maxAvailable'] = $this->lConf['numCheckMaxInterval'];

				// get uid and pid of the detailed description content element
				$uidpid = explode("#", $product['uiddetails']);
				if (is_numeric($uidpid[0])) {
					$product['detailsRaw'] =  array_shift(tx_abbooking_div::getRecordRaw('tt_content', $uidpid[0], $uidpid[1]));
  				}
				$product_properties_return[$uid] = $product;
			}
		}

//~ print_r("get_product_properties\n");
//~ print_r($product_properties_return);
//~ print_r("availableProductIDs\n");
//~ print_r($availableProductIDs);
//~
		$offTimeProductIDs  = array_diff(explode(",", $ProductUID), $availableProductIDs);
		$this->lConf['AvailableProductIDs'] = $availableProductIDs;
		$this->lConf['OffTimeProductIDs'] = $offTimeProductIDs;

		return $product_properties_return;
	}

	/**
	 * Check vacancies for given date
	 *
	 * all information is filled in global $this->lConf['productDetails'] array
	 *
	 * @param	[type]		$storagePid: ...
	 * @return	0		on success, 1 on error
	 */
	function check_availability($storagePid) {
		$item = array();

		if (!isset($interval['startDate'])) {
			$interval['startDate'] = $this->lConf['startDateStamp'];
			$interval['endDate'] = strtotime('+ '.$this->lConf['numCheckMaxInterval'].' days', $interval['startDate']);
		}

		if (!isset($interval['startList'])) {
			$interval['startList'] = $interval['startDate'];
			$interval['endList'] = $interval['endDate'];
		}

		$startDate = $interval['startDate'];
		//$endDate =  strtotime('+ '.$this->lConf['daySelector'].' days', $startDate);
		$endDate = $interval['endDate'];

		if ($endDate > strtotime('+ '.($this->lConf['numCheckNextMonths'] + 1).' months')) {
			$this->availability = 2;
			$this->form_errors['endDateTooFarInFuture'] = $this->pi_getLL('error_tooFarInFuture')."<br/>";
			return 1;
		}

		if (!isset($interval['startDate']) && !isset($interval['endDate'])) {
			$interval['startDate'] = $startDate;
			$interval['endDate'] = $endDate;
		}
		if (!isset($interval['startList']) && !isset($interval['endList'])) {
			$interval['startList'] = $startDate;
			$interval['endList'] = $endDate;
		}

		// 1. step through bookings to find maximum availability
		$bookings = tx_abbooking_div::getBookings($this->lConf['ProductID'], $storagePid, $interval);

		foreach ($bookings['bookings'] as $key => $row) {
			if (!isset($item[$row['uid']]['maxAvailable']))
				$item[$row['uid']]['maxAvailable'] = $this->lConf['numCheckMaxInterval'];

			// booked period is in future of startDate
			if ($row['startdate']>$startDate)
				$item[$row['uid']]['available'] = (int)date("d",$row['startdate'] - $startDate) - 1; /* day diff */
			else if ($row['enddate']>$startDate)
				// booked period overlaps startDate
				$item[$row['uid']]['available'] = 0;

			// find maximum available period for item[UID]
			if ($item[$row['uid']]['available'] < $item[$row['uid']]['maxAvailable'])
				$item[$row['uid']]['maxAvailable'] = $item[$row['uid']]['available'];
		}

		// 2. step through prices to find maximum availability
 		foreach ($this->lConf['productDetails'] as $uid => $product) {
			if (!isset($item[$uid]['maxAvailable']))
				$item[$uid]['maxAvailable'] = $this->lConf['numCheckMaxInterval'];


			for ($d=$interval['startDate']; $d < $interval['endDate']; $d=strtotime('+1 day', $d)) {
				if ($product['prices'][$d] == 'noPrice') {
					if ($d > $startDate && ((int)date("d",$d - $startDate) - 1) < $item[$uid]['available'])
						$item[$uid]['available'] = (int)date("d", $d - $startDate) - 1 ; /* day diff */
					else
						$item[$uid]['available'] = 0;
				}
				// reduce available days by blockDaysAfterBooking value
				if ($product['prices'][$d]['blockDaysAfterBooking'] > $item[$uid]['blockDaysAfterBooking']) {
					$item[$uid]['blockDaysAfterBooking'] = $product['prices'][$d]['blockDaysAfterBooking'];
				}
				// reduce available days by minimumStay value
				if ($product['prices'][$d]['minimumStay'] > $item[$uid]['minimumStay']) {
					$item[$uid]['minimumStay'] = $this->getMinimumStay($product['prices'][$d]['minimumStay'], $startDate);
				}

				// get highest daySteps...
				if ($product['prices'][$d]['daySteps'] > $item[$uid]['daySteps']) {
					$item[$uid]['daySteps'] = $product['prices'][$d]['daySteps'];
				}
			}
			// find maximum available period for item[UID]
			if ($item[$uid]['available'] < $item[$uid]['maxAvailable'])
				$item[$uid]['maxAvailable'] = $item[$uid]['available'];
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

			if ($item[$uid]['minimumStay'] > $this->lConf['productDetails'][$productID]['maxAvailable']) {
				$this->lConf['productDetails'][$productID]['maxAvailable'] = 0;
			}

		}
//~ print_r($this->lConf['productDetails']);

		return 0;
	}


	/*
	 * Checks the form data for validity
	 *
	 * @return	amount		of errors found
	 */
	function formVerifyUserInput() {

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

		$customer = $this->lConf['customerData'];

		// check for limited vacancies...
		if ($product['maxAvailable'] < $this->lConf['daySelector']) {
			$this->form_errors['vacancies_limited'] = $this->pi_getLL('error_vacancies_limited')."<br/>";
			$numErrors++;
		}

		// Email mit Syntax und Domaincheck
		$motif1="#^[[:alnum:]]([[:alnum:]\._-]{0,})[[:alnum:]]";
		$motif1.="@";
		$motif1.="[[:alnum:]]([[:alnum:]\._-]{0,})[\.]{1}([[:alpha:]]{2,})$#";

		if (preg_match($motif1, $customer['address_email'])){
			list($user, $domain)=preg_split('/@/', $customer['address_email'], 2);
			$dns_ok=checkdnsrr($domain, "MX");
			// nobody of this domain will write an email - expect spamers...
			if ($domain == $mail_from_domain)
				$dns_ok = 0;
		}
		if (!$dns_ok || !t3lib_div::validEmail($customer['address_email'])){
			$this->form_errors['email'] = $this->pi_getLL('error_email')."<br/>";
			$numErrors++;
		}



		if (empty($customer['address_name'])) {
			$this->form_errors['name'] = $this->pi_getLL('error_empty_name')."<br/>";
			$numErrors++;
		}
		if (empty($customer['address_street'])) {
			$this->form_errors['street'] = $this->pi_getLL('error_empty_street')."<br/>";
			$numErrors++;
		}
		if (empty($customer['address_town'])) {
			$this->form_errors['town'] = $this->pi_getLL('error_empty_town')."<br/>";
			$numErrors++;
		}
		if (empty($customer['address_postalcode'])) {
			$this->form_errors['PLZ'] = $this->pi_getLL('error_empty_zip')."<br/>";
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

		if (empty($this->lConf['daySelector'])) {
			$this->form_errors['daySelectorNotValid'] = $this->pi_getLL('error_daySelectorNotValid')."<br/>";
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

		$log = strftime("%Y-%m-%d %H:%M:%S").','.$ip.','.$longisp.','.strftime("%d.%m.%Y", $this->lConf['startDateStamp']).','.strftime("%d.%m.%Y", $this->lConf['endDateStamp']).','.$this->piVars['adultSelector'].','.$this->lConf['daySelector']."\n";

		//Daten schreiben
		$fp2=fopen($logFile, "a");
		fputs($fp2, $log);
		fclose($fp2);
	}

	/**
	 * Send Confirmation Email
	 *
	 *  HTML mail is fully not supported yet!
	 *
	 * @param	[type]		$key: ...
	 * @param	[type]		$send_errors: ...
	 * @return	number		of successfully sent emails
	 */
	function send_confirmation_email($key, &$send_errors) {

		$product = $this->lConf['productDetails'][$key];

		$customer = $this->lConf['customerData'];

		$text_mail .= $this->lConf['textConfirmEmail']."\n\n";
		$text_mail .= "===\n";

		if ($this->lConf['useTSconfiguration'] == 1) {

			foreach ($this->lConf['form'] as $formname => $form) {
				$formname = str_replace('.', '', $formname);
				$text_mail .= $this->getTSTitle($form['title.']). ': ' . $customer[$formname]."\n";
			}


		} else {
			$text_mail .= $this->pi_getLL('feld_name').": ".$customer['address_name']."\n";
			$text_mail .= $this->pi_getLL('feld_street').": ".$customer['address_street']."\n";
			$text_mail .= $this->pi_getLL('feld_zip').": ".$this->piVars['plz']."\n";
			$text_mail .= $this->pi_getLL('feld_town').": ".$customer['address_town']."\n\n";
			$text_mail .= $this->pi_getLL('feld_email').": ".$customer['address_email']."\n";
			$text_mail .= $this->pi_getLL('feld_telephone').": ".$customer['address_telephone']."\n\n";

			$text_mail .= $this->pi_getLL('product_title').": ".$product['title']."\n";
			$text_mail .= $this->pi_getLL('feld_anreise').": ".strftime("%A, %d.%m.%Y", $this->lConf['startDateStamp'])."\n";
			$text_mail .= $this->pi_getLL('feld_abreise').": ".strftime("%A, %d.%m.%Y", $this->lConf['endDateStamp'])."\n";
			$text_mail .= $this->pi_getLL('feld_naechte').": ".$this->lConf['daySelector']."\n";
			$text_mail .= $this->pi_getLL('feld_personen').": ".$this->piVars['adultSelector']."\n\n";
			if (isset($this->piVars['mitteilung']))
				$text_mail .= $this->pi_getLL('feld_mitteilung').": ".$this->piVars['mitteilung']."\n\n";
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

		// prefere ab_swiftmailer in TYPO3 < 4.5
		// TYPO3 4.5 has swiftmailer included

		if (!empty($this->lConf['EmailAddress']))
			$email_owner = array($this->lConf['EmailAddress'] => $this->lConf['EmailRealname']);
		else
			$email_owner = t3lib_utility_Mail::getSystemFrom();
		$email_customer = array($customer['address_email'] => $customer['address_name']);
		$subject_customer = $this->pi_getLL('email_your_booking').': '.$product['title'].' '.strftime("%a, %d.%m.%Y", $this->lConf['startDateStamp']).' - '.strftime("%a, %d.%m.%Y", $this->lConf['endDateStamp']);
		$subject_owner = $this->pi_getLL('email_new_booking').' '.$customer['address_name'].': '.$product['title'].' '.strftime("%a, %d.%m.%Y", $this->lConf['startDateStamp']).' - '.strftime("%a, %A, %d.%m.%Y", $this->lConf['endDateStamp']);

		if (version_compare(TYPO3_version, '4.5', '<')) {
			// send mail for TYPO3 4.4.x....
			// does tx_abswiftmailer_pi1 exists?
			if (class_exists('tx_abswiftmailer_pi1') && $this->lConf['useSwiftMailer']) {
				$this->swift = t3lib_div::makeInstance('tx_abswiftmailer_pi1');

				// send booking mail to owner
				$result = $this->swift->swift_send_message($email_customer,
					$subject_owner,
					$text_plain_mail);
				if ($result != 1) {
					$send_errors = $result;
				} else
					$send_success++;

				// send acknowledge mail to customer
				$result = $this->swift->swift_send_message($email_customer,
					$subject_customer,
					$text_plain_mail, 1);
				if ($result != 1) {
					$send_errors .= $result;
				} else
					$send_success++;
			} else {
				foreach ($email_owner as $emailAddress => $emailName) {
					$email_owner_string = $emailName.' <'.$emailAddress.'>';
				}
				foreach ($email_customer as $emailAddress => $emailName) {
					$email_customer_string = $emailName.' <'.$emailAddress.'>';
				}

				// send booking mail to owner, reply-to customer
				t3lib_div::plainMailEncoded($email_owner_string,
						$this->pi_getLL('email_new_booking').' '.$customer['address_name'].' ('.$customer['address_email'].')',
						$text_plain_mail,
						'From: '.$email_owner_string.chr(10).'Reply-To: '.$customer['address_email']);

				// send acknolewdge mail to customer
				t3lib_div::plainMailEncoded($email_customer_string,
						$this->pi_getLL('email_your_booking').' '.strftime("%d.%m.%Y", $this->lConf['startDateStamp']),
						$text_plain_mail,
						'From: '.$email_owner_string.chr(10).'Reply-To: '.$this->lConf['EmailAddress']);

				// assume everything went ok because there is no return value of plainMailEncoded())
				$send_success = 2;
			}
		}
		else {
			// send mail for TYPO3 4.5.x....
			$mail = t3lib_div::makeInstance('t3lib_mail_Message');
			$mail->setFrom($email_owner);
			$mail->setTo($email_owner);
			$mail->setReplyTo($email_customer);
			$mail->setSubject($subject_owner);
			$mail->setBody($text_html_mail, 'text/html', 'utf-8');
			$mail->addPart(strip_tags($text_plain_mail), 'text/plain', 'utf-8');

			if ($mail->send() == 1)
				$send_success = 1;

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

		$title = strftime('%Y%m%d', $startDate).', '.str_replace(',', ' ', $customer['address_name']).', '.str_replace(',', ' ', $customer['address_town']).', '.$customer['address_email'].', '.str_replace(',', ' ', $customer['address_telephone']);
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
	 * Calculate the Minimum Stay Period
	 *
	 * @param	[type]		$rate: rate and discount settings
	 * @param	[type]		$period: booking period
	 * @return	double		amount
	 */
	function getMinimumStay($minimumStay, $startDate) {

		$valueDetails = explode(',', $minimumStay);

		$today = strtotime(strftime("%Y-%m-%d 00:00:00"));
		$period = (int)(($startDate - $today)/86400);

		$valueArray['standardValue'] = $valueDetails[0];

		foreach ($valueDetails as $id => $value) {
			// W:2:2
			$dpd = explode(':', $value);
			if ($dpd[0] == 'W' && is_numeric($dpd[1]))
				$valueArray[$dpd[1] * 7] = $dpd[2];
			else if ($dpd[0] == 'D' && is_numeric($dpd[1]))
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
		}
		else {
			$discountRate['incrementUse'] = $dayStep;
			$discountRate['priceIsPerWeek'] = 0;
		}

//~ 		if ($rate['priceIsPerWeek'] == 1)
//~ 			$discountRate['rateIncrement'] = 1;
//~ 		else
//~ 			$discountRate['rateIncrement'] = $dayStep;
//~ print_r($discountRate);

		return $discountRate;
	}
	/**
	 * Calculate the Rate per Day using the discount settings and the booking period
	 *
	 * @param	[type]		$rate: rate and discount settings
	 * @param	[type]		$period: booking period
	 * @return	double		amount
	 */
	function getDiscountRate($rate, $period, $dayStep) {

		$discountDetails = explode(',', $rate);

		$discountRate['standardRate'] = $discountDetails[0];

		foreach ($discountDetails as $id => $value) {
			// D:3:10%
			$dpd = explode(':', $value);
			if ($dpd[0] == 'D' && is_numeric($dpd[1]))
				$discountPeriodArray[$dpd[1]] = $dpd[2];
			else if ($dpd[0] == 'O')
				$discountRate['isOption'] = 1;
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
		}
		else {
			$discountRate['incrementUse'] = $dayStep;
			$discountRate['priceIsPerWeek'] = 0;
		}


		return $discountRate;
	}

	/**
	 * Calculate the Rates
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$maxAvailable: ...
	 * @return	string		with amount, currency...
	 */
	function calcRates($key, $period) {

			if ($this->lConf['useTSconfiguration'] == 1)
				return $this->calcRatesTSMode($key, $period);
			else
				return $this->calcRatesDBMode($key, $period);

	}

	/**
	 * Calculate the Rates
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$maxAvailable: ...
	 * @return	string		with amount, currency...
	 */
	function calcRatesDBMode($key, $period) {

		$priceDetails = array();
		$customer = $this->lConf['customerData'];

		$product = $this->lConf['productDetails'][$key];

		$periodDateStamp = strtotime('+'.$period.' day', $this->lConf['startDateStamp']);
		$max_amount = 0;
		// assuming every adult costs more;
		// e.g. 1 adult 10, 2 adults 20, 3 adults 25...
		// if you don't have prices per person, please use adult2 for the entire object
		for ($i=1; $i<=$product['capacitymax']; $i++) {
//~ print_r("i: ".$i.", adultSelector: ".$this->lConf['adultSelector'].", capacitymax: ".$product['capacitymax']."\n");
//~ print_r("i: ".$i.", startDateStamp: ".$this->lConf['startDateStamp'].", price adult: ".$product['prices'][$this->lConf['startDateStamp']]['adult'.$i].", max_persons: ".$max_persons."\n");
			if ($product['prices'][$this->lConf['startDateStamp']]['adult'.$i] >= $max_amount) {
				$max_amount = $product['prices'][$this->lConf['startDateStamp']]['adult'.$i];
				$max_persons = $i;
			}
			if ($max_amount > 0 && $i >= $this->lConf['adultSelector'])
					break;
		}

		// step through days from startdate to (enddate | maxAvailable) and add rate for every day
		$total_amount = 0;
		$priceArray['adult'.$max_persons] = '+';
		if ($this->lConf['adultSelector']>$max_persons)
			$priceArray['adultX'] = '*+';
		$priceArray['extraComponent1'] = '*+';
		$priceArray['extraComponent2'] = '*+';

		foreach($priceArray as $key => $operator) {
			unset($cur_title);
			unset($pre_title);
			for ($d = $this->lConf['startDateStamp'];
			$d < $periodDateStamp;
				$d = strtotime('+1 day', $d)) {
				$rateValue = $this->getDiscountRate($product['prices'][$d][$key], $period);
//~ print_r($rateValue);

//				$rateValue = $rateValue['discountRate'];

				if (!is_numeric($rateValue['discountRate']) || $rateValue['discountRate'] < 0)
						continue;

//~ 				$rateValue = $this->getDiscountRate($product['prices'][$d]['adult'.$max_persons], $period);
				if ($operator == '*+')
					$rateValue['discountRate'] = $max_persons * $rateValue['discountRate'];

//~  				$total_amount += $rateValue['discountRate'];

				$cur_title = str_replace(" ", "", $product['prices'][$d]['title'].$rateValue['discountRate'].$key);

				$usedPrices[$cur_title]['rateUsed']++;
				$usedPrices[$cur_title]['rateValue'] = $rateValue['discountRate'];
				$usedPrices[$cur_title]['discount'] = $rateValue['discount'];
				$usedPrices[$cur_title]['isOption'] = $rateValue['isOption'];

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
				if (strtotime('+1 day', $d) == strtotime('+'.$period.' day', $this->lConf['startDateStamp'])) {
					if (! empty($usedPrices[$cur_title]['dateStart'])) {
// 						if ($usedPrices[$cur_title]['rateUsed'] > 1)
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

		// some useful texts
		if ($this->lConf['showPersonsSelector'] == 1) {
			if ($max_persons == 1)
				$text_persons = ', '.$max_persons.' '.$this->pi_getLL('person');
			else
				$text_persons = ', '.$max_persons.' '.$this->pi_getLL('persons');
		}

		// input form element for selectable options
		foreach ($usedPrices as $title => $value) {
			if ($value['rateUsed'] == 1)
				$text_periods = ' '.$this->pi_getLL('period');
			else
				$text_periods = ' '.$this->pi_getLL('periods');

			if ($value['isOption'] == 1) {
				if ($customer[$title] == 1 || $customer[$title] == '' ) {
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
		// get singleComponent from startDate
		if ($product['prices'][$this->lConf['startDateStamp']]['singleComponent1']>0) {
			$singleComponent1 = $this->getDiscountRate($product['prices'][$this->lConf['startDateStamp']]['singleComponent1'], $period);
			$singleComponent1 = $singleComponent1['discountRate'];
			if ($singleComponent1 >= 0) {
				$total_amount += $singleComponent1;

				$lDetails['form'] = '';
				$lDetails['description'] = $this->pi_getLL('specialComponent1');
				$lDetails['dates'] = '';
				$lDetails['value'] = number_format($singleComponent1, 2, ',', '').' '.$currency;
				$priceDetails[] = $lDetails;
			}
		}
		if ($product['prices'][$this->lConf['startDateStamp']]['singleComponent2']>0) {
			if ($singleComponent2 >= 0) {
				$singleComponent2 = $this->getDiscountRate($product['prices'][$this->lConf['startDateStamp']]['singleComponent2'], $period);
				$singleComponent2 = $singleComponent2['discountRate'];
				$total_amount += $singleComponent2;

				$lDetails['form'] = '';
				$lDetails['description'] = $this->pi_getLL('specialComponent2');
				$lDetails['dates'] = '';
				$lDetails['value'] = number_format($singleComponent2, 2, ',', '').' '.$currency;
				$priceDetails[] = $lDetails;
			}
		}

		$rate['priceTotalAmount'] = number_format($total_amount, 2, ',', '');
		$rate['priceCurrency'] = $currency;
		$rate['priceDetails'] = $priceDetails;

		$rate['textPriceTotalAmount'] = number_format($total_amount, 2, ',', '').' '.$currency;
		return $rate;
	}

	/**
	 * Calculate the Rates from TS settings
	 *
	 * @param	[type]		$key: the rate tstitle
	 * @param	[type]		$period: the period to stay
	 * @return	array		with rate components ready to output...
	 */
	function calcRatesTSMode($key, $period) {

//~ print_r("calcRatesTSMode\n");
//~ print_r($period);
		$product = $this->lConf['productDetails'][$key];

		$priceDetails = array();
		$pricePerDay = array();
		$keyArray = array();

		$customer = $this->lConf['customerData'];

		$maxAdults = $this->lConf['adultSelector'];
		$maxChildren = $this->lConf['numChildren'];
		$maxTeens = $this->lConf['numTeens'];


//~ 		print_r("-calcRatesTSMode---START---\n");
//~ 		print_r($product);
//~ 		print_r("-calcRatesTSMode---End---\n");

		$interval['startDate'] = $this->lConf['startDateStamp'];
		$interval['endDate'] = strtotime('+'.$period.' day', $this->lConf['startDateStamp']);

		$pricePerDay = $product['prices'];
		if (!is_array($pricePerDay))
			return FALSE;

//~  		print_r("-pricePerDay---Start---\n");
//~  		print_r($pricePerDay);
//~  		print_r("-pricePerDay---End---\n");

		// now we have an array with the right rates per day.
		// let's begin magic in calculation for every day the final amount

		if ($maxAdults > 0)
			$keyArray[] = 'adult';
		if ($maxChildren > 0)
			$keyArray[] = 'child';
		if ($maxTeens > 0)
			$keyArray[] = 'teen';

 		$keyArray[] = 'ratesPerDayAndPerson';
//~ 		$keyArray[] = 'ratesPerStay';

		foreach($keyArray as $key) {
			unset($cur_title);
			unset($pre_title);
			unset($rateValueArray);

			if ($pricePerDay[$interval['startDate']]['priceIsPerWeek'] == 1)
				$dayStep = 7;
			else
				$dayStep = 1;
			for ($d = $interval['startDate']; $d < $interval['endDate']; $d=strtotime('+'.$dayStep.' day', $d)) {

//~ 				if ($d == $interval['startDate']) {
//~ 				}
				unset($rateValueArray);

				if ($key != 'ratesPerDayAndPerson') {
						$rateValueArray[] = $this->getDiscountRate($pricePerDay[$d][$key.'.'][$maxAdults], $period, $dayStep);
				} else {
					if ($key == 'ratesPerDayAndPerson') {
						if (is_array($pricePerDay[$d][$key.'.']))
							foreach ($pricePerDay[$d][$key.'.'] as $ratePerDayAndPerson) {
								$rateValueArray[] = $this->getRatePerDayAndPerson($ratePerDayAndPerson, $period, $dayStep, $maxAdults);
							}
					}
				}
				if (is_array($rateValueArray))
				foreach ($rateValueArray as $rateValue) {
					unset($cur_title);
					unset($pre_title);
					if (!is_numeric($rateValue['discountRate']) || $rateValue['discountRate'] < 0)
							continue;

						if (empty($rateValue['title']))
							$title = $pricePerDay[$d]['title'];
						else
							$title = $rateValue['title'];

						$cur_title = str_replace(" ", "", $title.$rateValue['discountRate'].$key);
						$usedPrices[$cur_title]['title'] = $title;
//~ 						$usedPrices[$cur_title]['rateUsed']++;
						$usedPrices[$cur_title]['rateUsed'] += $rateValue['incrementUse'];
						$usedPrices[$cur_title]['priceIsPerWeek'] = $rateValue['priceIsPerWeek'];
						$usedPrices[$cur_title]['rateValue'] = $rateValue['discountRate'];
						$usedPrices[$cur_title]['discount'] = $rateValue['discount'];
						$usedPrices[$cur_title]['isOption'] = $rateValue['isOption'];
						$usedPrices[$cur_title]['usedDates'][] = $d;

						$pre_title = $cur_title;
				}
			}
		}
//~ print_r($usedPrices);
		// now we have the simple rates per adult/child/teen
		unset($keyArray);

//~ 		print_r("-rateValue---Start---\n");
//~ 		print_r($rateValue);
//~ 		print_r($usedPrices);
//~ 		print_r("-rateValue---End---\n");

		// take currency from TS
		$currency = $this->conf['rates.']['currency'];

		// some useful texts
		if ($this->lConf['showPersonsSelector'] == 1) {
			if ($maxAdults == 1)
				$text_persons = ', '.$maxAdults.' '.$this->pi_getLL('person');
			else
				$text_persons = ', '.$maxAdults.' '.$this->pi_getLL('persons');

			if ($maxChildren == 1)
				$text_children = ', '.$maxChildren.' '.$this->pi_getLL('child');
			else
				$text_children = ', '.$maxChildren.' '.$this->pi_getLL('children');

			if ($maxTeens == 1)
				$text_teens = ', '.$maxTeens.' '.$this->pi_getLL('teen');
			else
				$text_teens = ', '.$maxTeens.' '.$this->pi_getLL('teens');
		}

		// input form element for selectable options
		foreach ($usedPrices as $title => $value) {
			unset($lDetails);
			if ($value['priceIsPerWeek'] == 1) {
				if ($value['rateUsed'] == 1)
					$text_periods = ' '.$this->pi_getLL('week');
				else
					$text_periods = ' '.$this->pi_getLL('weeks');

			} else {
				if ($value['rateUsed'] == 1)
					$text_periods = ' '.$this->pi_getLL('period');
				else
					$text_periods = ' '.$this->pi_getLL('periods');
			}
			if ($value['isOption'] == 1) {
				if ($customer[$title] == 1 || $customer[$title] == '' ) {
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
//~ 			$lDetails['dates'] = $value['rateDates'];

			// step through used dates and create date interval string
			$openInterval = 0;
			$lastday = 0;
			foreach ($value['usedDates'] as $id => $currday) {
				$dayDiff = (int)($currday - $lastday);
				if ($id == 0 || $openInterval == 0) {
					$dateUsed = strftime('%a %x', $currday).' - ';
					$openInterval = 1;
				} else if ($openInterval == 1 && $dayDiff > 86400) {
					$dateUsed .= strftime('%a %x', $lastday );
					$openInterval = 0;
				}
				$lastday = $currday;
			}
			if ($openInterval == 1)
				$dateUsed .= strftime('%a %x', $lastday+($dayStep*86400) );

			$lDetails['dates'][] = $dateUsed;
			$lDetails['value'] = $value['rateUsed'].' x '.number_format($value['rateValue'], 2, ',', '').' '.$currency.' = '.number_format($value['rateUsed']*$value['rateValue'],2,',','').' '.$currency;
			$lDetails['description'] = $value['rateUsed'].' '.$text_periods.', '.$value['title'].$text_persons;
			$priceDetails[] = $lDetails;
		}


		//-------------------------------------------------------------
		// now get the ratesPerStay from the startDate
		//-------------------------------------------------------------
		unset($rateValueArray);
		if (is_array($pricePerDay[$this->lConf['startDateStamp']]['ratesPerStay.']))
			foreach ($pricePerDay[$this->lConf['startDateStamp']]['ratesPerStay.'] as $ratePerDayAndPerson) {
						$rateValueArray[] = $this->getRatePerDayAndPerson($ratePerDayAndPerson, $period, 1, 1);
			}
		if (is_array($rateValueArray))
			foreach ($rateValueArray as $rateValue) {
				if ($rateValue['discountRate'] >= 0) {
					$total_amount += $rateValue['discountRate'];

					$lDetails['form'] = '';
					$lDetails['description'] = $rateValue['title'];
					$lDetails['dates'] = '';
					$lDetails['value'] = number_format($rateValue['discountRate'], 2, ',', '').' '.$currency;
					$priceDetails[] = $lDetails;
				}
			}
//~ 		print_r("---ab--ratesPerStay-\n");
//~ 		print_r($rateValueArray);
//~ 		print_r("---ab--ratesPerStay-\n");

//~ print_r($priceDetails);
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
//~ print_r($rates);
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
