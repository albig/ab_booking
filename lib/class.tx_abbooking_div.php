<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Alexander Bigga <linux@bigga.de>
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
 * ab_booking misc functions
 *
 * @author	Alexander Bigga <linux@bigga.de>
 * @package	TYPO3
 * @subpackage	tx_abbooking
 */
class tx_abbooking_div {

	/**
	 * Get Booked Periods for an Interval
	 *
	 * @param	string		$uid
	 * @param	string		$storagePid: ...
	 * @param	array		$interval: ...
	 * @return	array		with booking periods
	 */
	function getBookings($uid, $storagePid, $interval) {

		$bookingsRaw = array();
		$remotebookings = array();

		if (!isset($uid))
			$uid = $this->lConf['ProductID'];

		if (!isset($interval['startList']) && !isset($interval['endList'])) {
			$interval['startList'] = $interval['startDate'];
			$interval['endList'] = $interval['endDate'];
		}

		if ($storagePid !='' && $uid !='') {
			// SELECT
			// 2. get for bookings for these uids/pids
			$query= 'pid IN ('. $storagePid .') AND uid_foreign IN ('.$uid.') AND deleted=0 AND hidden=0 AND request=0 AND uid=uid_local AND ( enddate >=('.$interval['startList'].') AND startdate <=('.$interval['endList'].'))';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT uid_foreign, startdate, enddate','tx_abbooking_booking, tx_abbooking_booking_productid_mm',$query,'','startdate','');

			// one array for start and end dates. one for each pid
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$bookingsRaw[] = $row;
			};
		}

		$localbookings['bookings'] = $bookingsRaw;

 		return $localbookings;

	}


	/**
	 * Calculate Booked Days-Array for Calendar View
	 *
	 * @param	array		$bookings
	 * @param	[type]		$interval: ...
	 * @return	array		with booking periods
	 */
	function calcBookedPeriods($bookings, $interval) {

		$bookedDays = array();
		$bookedDaysCSS = array();

		foreach ($bookings['bookings'] as $uid => $row) {
			for ($d = $row['startdate']; $d <= $row['enddate']; $d=strtotime("+ 1day", $d)) {
				$bookedDays[$d]['booked']++ ;
				$bookedDays[$d]['uid'] = $uid;
			}
			$bookedDays[$row['startdate']]['isStart']++;
			$bookedDays[$row['enddate']]['isEnd']++;
		};

		// make ready to print css class like "[Day|Weekend] [booked|vacant] [Start|End]"
		for ($d=$interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
			if (date("w", $d)== 0 || date("w", $d)== 6)
				$bookedDaysCSS[$d]=' Weekend';
			else
				$bookedDaysCSS[$d]=' Day';

			if ($bookedDays[$d]['booked']>0) {
				$bookedDaysCSS[$d].=' booked';
				if ($bookedDays[$d]['isStart'] == $bookedDays[$d]['booked'])
					$bookedDaysCSS[$d].=' Start';
				else if ($bookedDays[$d]['isEnd'] == $bookedDays[$d]['booked'])
					$bookedDaysCSS[$d].=' End';
			}
			else
				$bookedDaysCSS[$d].=' vacant';

		}

		return $bookedDaysCSS;
	}


	/**
	 * Display the availability calendar for one or more months depending on flexform configuration
	 *
	 * @param	integer		$uid: ...
	 * @param	[type]		$interval: ...
	 * @return	HTML-table		with calendar view
	 */
	function printAvailabilityCalendar($uid, $interval = array()) {

		$this->pi_loadLL();
		$myBooked = array();
		$rows = (int)$this->lConf['numMonthsRows'];
		$columns = (int)$this->lConf['numMonthsCols'];
		$months = $rows * $columns;


		// disable booking links for robots
		if ($this->isRobot())
			$this->lConf['enableCalendarBookingLink'] = 0;

		if (!isset($interval['startDate']) && !isset($interval['endDate'])) {
			$today = strtotime(strftime("%Y-%m-%d"));
			$interval['startDate'] = strtotime(strftime("%Y-%m-1"));
			$interval['endDate'] = strtotime('+'.$months.' months', $today);
		}
		$interval['startList'] = $interval['startDate'];
		$interval['endList'] = $interval['endDate'];

		$bookedPeriods = tx_abbooking_div::getBookings($uid, $this->lConf['PIDstorage'], $interval);
		$myBooked = tx_abbooking_div::calcBookedPeriods($bookedPeriods, $interval);

		if (empty($this->lConf['ProductID']) && empty($uid)) {
			$out = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
		}

		$out .= '<table class="listlegend"><tr>';
		$out .= '<td class="vacant">&nbsp;</td><td class="legend">' . $this->pi_getLL('vacant day') .'</td>';
		$out .= '<td class="booked">&nbsp;</td><td class="legend">'.	$this->pi_getLL('booked day').' </td>';
		$out .= '</tr></table>';

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

			// calender view in line mode
			if($this->lConf['monthsLineView']) {
				$out .= '<tr>
				<td class="DayTitle" title="'.$this->pi_getLL("Mon").'">'.$this->pi_getLL("Mo").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Tus").'">'.$this->pi_getLL("Tu").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Wed").'">'.$this->pi_getLL("We").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Thu").'">'.$this->pi_getLL("Th").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Fri").'">'.$this->pi_getLL("Fr").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sat").'">'.$this->pi_getLL("Sa").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sun").'">'.$this->pi_getLL("Su").'</td>

				<td class="DayTitle" title="'.$this->pi_getLL("Mon").'">'.$this->pi_getLL("Mo").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Tus").'">'.$this->pi_getLL("Tu").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Wed").'">'.$this->pi_getLL("We").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Thu").'">'.$this->pi_getLL("Th").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Fri").'">'.$this->pi_getLL("Fr").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sat").'">'.$this->pi_getLL("Sa").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sun").'">'.$this->pi_getLL("Su").'</td>

				<td class="DayTitle" title="'.$this->pi_getLL("Mon").'">'.$this->pi_getLL("Mo").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Tus").'">'.$this->pi_getLL("Tu").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Wed").'">'.$this->pi_getLL("We").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Thu").'">'.$this->pi_getLL("Th").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Fri").'">'.$this->pi_getLL("Fr").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sat").'">'.$this->pi_getLL("Sa").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sun").'">'.$this->pi_getLL("Su").'</td>

				<td class="DayTitle" title="'.$this->pi_getLL("Mon").'">'.$this->pi_getLL("Mo").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Tus").'">'.$this->pi_getLL("Tu").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Wed").'">'.$this->pi_getLL("We").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Thu").'">'.$this->pi_getLL("Th").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Fri").'">'.$this->pi_getLL("Fr").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sat").'">'.$this->pi_getLL("Sa").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sun").'">'.$this->pi_getLL("Su").'</td>

				<td class="DayTitle" title="'.$this->pi_getLL("Mon").'">'.$this->pi_getLL("Mo").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Tus").'">'.$this->pi_getLL("Tu").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Wed").'">'.$this->pi_getLL("We").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Thu").'">'.$this->pi_getLL("Th").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Fri").'">'.$this->pi_getLL("Fr").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sat").'">'.$this->pi_getLL("Sa").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sun").'">'.$this->pi_getLL("Su").'</td>

				<td class="DayTitle" title="'.$this->pi_getLL("Mon").'">'.$this->pi_getLL("Mo").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Tus").'">'.$this->pi_getLL("Tu").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Wed").'">'.$this->pi_getLL("We").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Thu").'">'.$this->pi_getLL("Th").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Fri").'">'.$this->pi_getLL("Fr").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sat").'">'.$this->pi_getLL("Sa").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sun").'">'.$this->pi_getLL("Su").'</td>
				</tr>';
				$rowsCalendar = 42;
			}
			// default calendar view
			else {
				$out .= '<tr>
				<td class="DayTitle" title="'.$this->pi_getLL("Mon").'">'.$this->pi_getLL("Mo").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Tus").'">'.$this->pi_getLL("Tu").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Wed").'">'.$this->pi_getLL("We").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Thu").'">'.$this->pi_getLL("Th").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Fri").'">'.$this->pi_getLL("Fr").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sat").'">'.$this->pi_getLL("Sa").'</td>
				<td class="DayTitle" title="'.$this->pi_getLL("Sun").'">'.$this->pi_getLL("Su").'</td>
				</tr>';
				$rowsCalendar = 7;
			}
			$out .= '<tr>';

			// calculating the left spaces to get the layout right
			for ($s = 0; $s < date('w', strtotime($year."-".$mon."-7")) ; $s++){
				$out .= '<td class="noDay">&nbsp;</td>';
				$days++;
			}


			for ($d=1; $d <= date("t", strtotime( $year . "-".$mon."-01")); $d++){
				if ($days % $rowsCalendar == 0 && $days != 0 ) { // new row after 42 or 7 days
					$out .= '</tr><tr>';
				}

				$cssClass = $myBooked[strtotime($year."-".$mon."-".$d)];

				$params_united = strtotime($year.'-'.$mon.'-'.$d).'_'.$this->lConf['numNights'].'_'.$this->lConf['numPersons'].'_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor0';

				$params = array (
					$this->prefixId.'[ABx]' => $params_united,
				);
				if ($this->lConf['enableCalendarBookingLink'] && strtotime($year.'-'.$mon.'-'.$d) >= strtotime(strftime("%Y-%m-%d")) &&
					(strstr($cssClass, 'vacant') || strstr($cssClass, 'End')) )
					$out .= '<td class="'.$cssClass.'">'.$this->pi_linkTP($d, $params, 0, $this->lConf['gotoPID']).'</td>';
				else
					$out .= '<td class="'.$cssClass.'">'.$d.'</td>';
				// booking end
				$days++;
			}

			for (; $days < 42; $days++ ) {
				if ($days % $rowsCalendar == 0) {  // new row after 42 or 7 days
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

		return $out;
	}

	/**
	 * Display the availability calendar as single line for a given interval
	 *
	 * @param	integer		$uid: ...
	 * @param	array		$interval: ...
	 * @return	HTML-list		of calendar days
	 */
	function printAvailabilityCalendarLine($uid, $interval = array()) {

		$this->pi_loadLL();

		// disable booking links for robots
		if ($this->isRobot())
			$this->lConf['enableCalendarBookingLink'] = 0;

		if (!isset($interval['startDate']) && !isset($interval['endDate'])) {
			$today = strtotime(strftime("%Y-%m-%d"));
			$interval['startDate'] = strtotime('-2 day', $today);
			$interval['endDate'] = strtotime('+10 days', $today);
		}
		if (!isset($interval['startList']) && !isset($interval['endList'])) {
			$interval['startList'] = $interval['startDate'];
			$interval['endList'] = $interval['endDate'];
		}

		$bookedPeriods = tx_abbooking_div::getBookings($uid, $this->lConf['PIDstorage'], $interval);
		$myBooked = tx_abbooking_div::calcBookedPeriods($bookedPeriods, $interval);

		$out .= '<div class="availabilityCalendar">';
		$out .= '<ul class="DayNames">';
		for ($d=$interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
			unset($cssClass);
			if ($d<$interval['startDate'] || $d > $interval['endDate'])
				$cssClass = 'transp';
			$out .= '<li class="'.$cssClass.'">'.strftime("%a", $d).'</li>';
		}
		$out .= '</ul>';

		$out .= '<ul class="Bookings">';
		for ($d=$interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
			unset($cssClass);

			if ($d<$interval['startDate'] || $d > $interval['endDate'])
				$cssClass = 'transp';

			$cssClass .= $myBooked[$d];

			if ($this->lConf['enableCalendarBookingLink'] && $d >= strtotime(strftime("%Y-%m-%d")) &&
				(strstr($cssClass, 'vacant') || strstr($cssClass, 'End')) ) {
				// set default numNights = 2, numPersons = 2
				//#### 2_2 durch $this->lConf['numNights'] und $this->lConf['numPersons'] ersetzt ###
				$params_united = $d.'_'.$this->lConf['numNights'].'_'.$this->lConf['numPersons'].'_'.$uid.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor0';
				$params = array (
					$this->prefixId.'[ABx]' => $params_united,
				);
				$out .= '<li class="'.$cssClass.'">'.$this->pi_linkTP(strftime("%d", $d), $params, 0, $this->lConf['gotoPID']).'</li>';
			}
			else
				$out .= '<li class="'.$cssClass.'">'.strftime("%d", $d).'</li>';
		}

		$out .= '</ul>';
		$out .= '</div>';

		return $out;
	}

	/**
	 * Display the Booking Step
	 *
	 * @param	integer		$step: [1..3]
	 * @return	HTML-list		with steps (input, check, final)
	 */
	function printBookingStep($step) {

		$this->pi_loadLL();

		$cssStep1='';
		$cssStep2='';
		$cssStep3='';

		switch ($step) {
			case '1':
				$cssStep1="current";
				break;
			case '3':
				$cssStep1="past";
				$cssStep2="current";
				break;
			case '4':
				$cssStep1="past";
				$cssStep2="past";
				$cssStep3="current";
				break;
		}

		$out .= '<div class="bookingSteps">';
		$out .= '<ul>';
		$out .= '<li class="'.$cssStep1.'">'.$this->pi_getLL('booking_step_1').'</li>';
		$out .= '<li class="'.$cssStep2.'">'.$this->pi_getLL('booking_step_2').'</li>';
		$out .= '<li class="'.$cssStep3.'">'.$this->pi_getLL('booking_step_3').'</li>';
		$out .= '</ul>';
		$out .= '</div>';

		return $out;
	}

	/**
	 * Get a list of offers for a given period
	 *
	 * @return	array
	 */
	function printOfferList() {

		$offers['numOffers'] = 0;
		$i = 0;

		$productIds=explode(',', $this->lConf['ProductID']);
		foreach ( $productIds as $key => $uid ) {
			if (!empty($this->lConf['productDetails'][$uid]))
				$product = $this->lConf['productDetails'][$uid];
			else
				continue; // skip because empty or OffTimeProductID
			$i++;
			$offers[$i] = '';
			unset($linkBookNow);
			if (sizeof($this->lConf['OffTimeProductIDs']) > 0)
				$offTimeProducts = ','.implode(',', $this->lConf['OffTimeProductIDs']);

			$params_united = $this->lConf['startDateStamp'].'_'.$this->lConf['numNights'].'_'.$this->lConf['numPersons'].'_'.$product['uid'].$offTimeProducts.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor1';
			$params = array (
				$this->prefixId.'[ABx]' => $params_united,
			);
			if (!empty($product['uiddetails']) && !empty($product['detailsRaw']['header'])) {
				// get detailed description:
				$title = $product['detailsRaw']['header'];
				$bodytext = $product['detailsRaw']['bodytext'];
			} else {
				$title = $product['title'];
				if (!empty($product['detailsRaw']['bodytext']))
					$bodytext = $product['detailsRaw']['bodytext'];
				else
					unset($bodytext);
			}


			if ($this->lConf['enableCheckBookingLink'] == 1)
				$link = $this->pi_linkTP($title, $params, 0, $this->lConf['gotoPID']);
			else
				$link = $title;

			if ($product['maxAvailable'] > 0) {
				$offers['numOffers']++;
				$offers[$i] .= '<li class="offerList">'.$link.' <b>'.strtolower($this->pi_getLL('result_available')).'</b><br /> '; //.$this->pi_getLL('up_to');
				$availableMaxDate = strtotime('+ '.$product['maxAvailable'].' days', $this->lConf['startDateStamp']);
// 				$offers[$i] .= ' '.strftime("%A, %d.%m.%Y", $availableMaxDate);
				if ($product['maxAvailable'] < $this->piVars['ABnumNights']) {
					$interval['limitedVacancies'] = $availableMaxDate;
					$offers[$i] .= '<br /><i>'.$this->pi_getLL('error_vacancies_limited').'</i><br />';
				}
				$offers[$i] .= '<br />';
				$offers[$i] .= $bodytext;

				$offers[$i] .= $this->printCalculatedRates($uid, $product['maxAvailable'], 1);

				$linkBookNow = '<p class="bookNow">'.$this->pi_linkTP($this->pi_getLL('bookNow'), $params, 0, $this->lConf['gotoPID']).'</p>';
			} else {
				$offers[$i] .= '<li class="offerList"><b>'.$title.' '.strtolower($this->pi_getLL('result_occupied')).'</b> ';
			}

			// show calendar list only up to the vacant day
			if (isset($interval['limitedVacancies']))
				$interval['endDate'] = $interval['limitedVacancies'];
			else
				$interval['endDate'] = $this->lConf['endDateStamp'];
			$interval['startDate'] = $this->lConf['startDateStamp'];
			$interval['startList'] = strtotime('-2 day', $interval['startDate']);
			$interval['endList'] = strtotime('+2 day', $interval['endDate']);

			$offers[$i] .= tx_abbooking_div::printAvailabilityCalendarLine($product['uid'].$offTimeProducts, $interval);

			if ($this->lConf['enableCheckBookingLink'] == 1)
				$offers[$i] .= $linkBookNow;
			// close list item...
			$offers[$i] .= '</li>';
		}
	$offers['amount'] = $i;
	return $offers;
	}

	/**
	 * Return an input field with date2cal-calendar if available
	 *
	 * @param	string		$name: of the input field
	 * @param	string		$value: of the input field
	 * @param	boolean		$error: if set the css class "error" is added
	 * @return	HTML-input		field for date selection
	 */
	function getJSCalendarInput($name, $value, $error = '') {

		if (class_exists('JSCalendar')) {
			$JSCalendar = JSCalendar::getInstance();
			// datetime format (default: time)
// 			$userParameters['inputField']['id'] = $name;
                        $JSCalendar->setDateFormat(false, "%d.%m.%Y");
			$JSCalendar->setNLP(false);
                        $JSCalendar->setInputField($name);

			$out .= $JSCalendar->render(strftime("%d.%m.%Y", $value), $userParameters);
			if (($jsCode = $JSCalendar->getMainJS()) != '') {
				$GLOBALS['TSFE']->additionalHeaderData['abbooking_jscalendar'] = $jsCode;
			}
		} else {
			$out .= '<input '.$errorClass.' type="text" class="jscalendar" name="'.$name.'" id="'.$name.'" value="'.strftime("%d.%m.%Y", $value).'" ><br/>';
		}

		if (isset($error)) {
			$out = str_replace('class="jscalendar"', 'class="jscalendar error"', $out);
		}

		return $out;
	}


}

?>