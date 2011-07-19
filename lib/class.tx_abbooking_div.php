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

	function getRecordRaw($table, $pid, $uid, $where_extra = '', $ignore_array = array(), $select = '*') {

		$out = array();

		// the parent field name changed somewhen in TYPO3. tt_content
		// still has l18n_parent (which is wrong) but new extenions have l10n_parent
		if ($table == 'tt_content')
			$l10n_parent = 'l18n_parent';
		else
			$l10n_parent = 'l10n_parent';

		// we try to get the default language entry (normal behaviour) or, if not possible, currently the needed language (fallback if no default language entry is available)
// 		$where = 'pid='.$pid.' AND uid IN('.$uid.') AND (sys_language_uid IN (-1,0) OR (sys_language_uid = ' .$GLOBALS['TSFE']->sys_language_uid. ' AND '.$l10n_parent.' = 0))';
		$where = 'pid='.$pid.' AND uid IN('.$uid.') AND (sys_language_uid IN (-1,0) OR (sys_language_uid = ' .$GLOBALS['TSFE']->sys_language_uid. '))';
		// use the TYPO3 default function for adding hidden = 0, deleted = 0, group and date statements
		$where  .= $GLOBALS['TSFE']->sys_page->enableFields($table, $show_hidden = 0, $ignore_array);
		if (!empty($where_extra))
			$where  .= ' AND '.$where_extra;
		$order = '';
		$group = '';
		$limit = '';
//~ print_r($table."--".$pid."----------\n");
//~ print_r($where);
//~ print_r("------------\n");
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $group, $order, $limit);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
// print_r($row);
			// check for language overlay if:
			// * row is valid
			// * row language is different from currently needed language
			// * sys_language_contentOL is set
			if (is_array($row) && $row['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content && $GLOBALS['TSFE']->sys_language_contentOL) {
				$rowL = $GLOBALS['TSFE']->sys_page->getRecordOverlay($table, $row, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
				// only overwrite "title" - all other settings take from the default language
				if (!empty($rowL)) {
					if ($table != 'tt_content')
						$row['title'] = $rowL['title'];
					else
						$row = $rowL;
				}
			}
			if ($row) {
				// get correct language uid for translated realurl link
				$link_uid = ($row['_LOCALIZED_UID']) ? $row['_LOCALIZED_UID'] : $row['uid'];
// print_r("the link_uid is ".$link_uid."\n");
			}
			$out[$row['uid']] = $row;
		}
		return $out;
	}

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
	 * Get rates per day
	 *
	 * @param	string		$uid
	 * @param	array		$interval: ...
	 * @return	array		with booking periods
	 */
	function getPrices($uid, $interval) {

		$pricePerDay = array();

		if (!isset($uid))
			$uid = $this->lConf['ProductID'];

		if (!isset($interval['startList']) && !isset($interval['endList'])) {
			$interval['startList'] = $interval['startDate'];
			$interval['endList'] = $interval['endDate'];
		}

		if ($uid !='') {
			// SELECT

			// 1. get priceid for uid (old way)
			$myquery= 'pid='.$this->lConf['PIDstorage'].' AND uid IN ('.$uid.') AND capacitymax>0 AND deleted=0 AND hidden=0';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, priceid','tx_abbooking_product',$myquery,'','','');
			// one array for start and end dates. one for each pid
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$priceids = $row['priceid'];
			};

			// 1. get priceid for uid (new way)
			$where_extra = "capacitymax>0 ";
			$mrow = tx_abbooking_div::getRecordRaw('tx_abbooking_product', $this->lConf['PIDstorage'], $uid, $where_extra);

			foreach ($mrow as $muid => $mproduct) {
//~ 				$priceids =  $mrow[$uid]['priceid'];
				$priceids =  $mproduct['priceid'];
			}

			// 2. get prices for priceid in interval
			$myquery = 'tx_abbooking_price.pid='.$this->lConf['PIDstorage'].' AND tx_abbooking_price.uid IN ('.$priceids.') AND tx_abbooking_price.deleted=0 AND tx_abbooking_price.hidden=0';
			$myquery .= ' AND uid_local=tx_abbooking_price.uid AND uid_foreign=tx_abbooking_seasons.uid';
			// there are four cases of time intervals:
			// 1: start set, stop set                 |-------------|
			// 2: start set, stop open                |---------------->
			// 3: start open, stop set             <----------------|
			// 4: start open, stop open (default rate) <------------->
			$myquery .= ' AND ((tx_abbooking_seasons.starttime <='. $interval['endList'].' OR tx_abbooking_seasons.starttime = 0) ';
			$myquery .= ' AND (tx_abbooking_seasons.endtime > '.$interval['startList'].' OR tx_abbooking_seasons.endtime = 0))';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_abbooking_price.uid as uid, tx_abbooking_seasons.starttime as starttime, tx_abbooking_seasons.endtime as endtime, tx_abbooking_price.title as title, currency, adult1, adult2, adult3, adult4, child, teen, discount, discountPeriod, singleComponent1, singleComponent2, minimumStay, blockDaysAfterBooking, checkInWeekdays','tx_abbooking_price,tx_abbooking_seasons_priceid_mm,tx_abbooking_seasons',$myquery,'',' FIND_IN_SET(tx_abbooking_price.uid, '.$GLOBALS['TYPO3_DB']->fullQuoteStr($priceids, 'tx_abbooking_price').') ','');
			$p = 0;

			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$pricesAvailable[$p] = $row;
//~ print_r($row);
				$languageOverlay =  tx_abbooking_div::getRecordRaw('tx_abbooking_price', $this->lConf['PIDstorage'], $row['uid']);
				// overwrite price title
				$pricesAvailable[$p]['title'] = $languageOverlay[$row['uid']]['title'];
//~ print_r($languageOverlay);
				$p++;
			};

//~ 			foreach ( $newPrices as $id => $rate ) {
//~ 				$pricesAvailable[$p] = $rate;
//~ 				$p++;
//~ 			};
//~ print_r($pricesAvailable);

			// get the valid prices per day
			for ($d = $interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
				for ($i=0; $i<$p; $i++) {
					if (($pricesAvailable[$i]['starttime'] <= $d || $pricesAvailable[$i]['starttime'] == 0)
						&& ($pricesAvailable[$i]['endtime'] > $d || $pricesAvailable[$i]['endtime'] == 0))
						break;
					// if no valid price is found - go further in the price array. otherwise the first in the list is the right.
				}
				if ($i == $p)
				  $pricePerDay[$d] = 'noPrice';
				else
				  $pricePerDay[$d] = $pricesAvailable[$i];
			}



		}
//~ 		print_r($pricePerDay);
 		return $pricePerDay;

	}

	/**
	 * Calculate Booked Days-Array for Calendar View
	 *
	 * @param	array		$bookings
	 * @param	[type]		$interval: ...
	 * @return	array		with booking periods
	 */
	function calcBookedPeriods($bookings, $prices, $interval) {

		$bookedDays = array();
		$bookedDaysCSS = array();

		foreach ($bookings['bookings'] as $uid => $row) {
			for ($d = $row['startdate']; $d <= $row['enddate']; $d=strtotime("+ 1day", $d)) {
				$bookedDays[$d]['booked']++ ;
			}
			$bookedDays[$row['startdate']]['isStart']++;
			$bookedDays[$row['enddate']]['isEnd']++;
		};

		// make ready to print css class like "[Day|Weekend] [booked|vacant] [Start|End]"
		for ($d = $interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
			if (date("w", $d)== 0 || date("w", $d)== 6)
				$bookedDaysCSS[$d]=' Weekend';
			else
				$bookedDaysCSS[$d]=' Day';

			if ($bookedDays[$d]['booked'] > 0) {
				$bookedDaysCSS[$d].=' booked';
				if ($bookedDays[$d]['isStart'] == $bookedDays[$d]['booked'])
					$bookedDaysCSS[$d].=' Start';
				else if ($bookedDays[$d]['isEnd'] == $bookedDays[$d]['booked'])
					$bookedDaysCSS[$d].=' End';
			}
			else
				$bookedDaysCSS[$d].=' vacant';
			if ($prices[$d] == 'noPrice')
				$bookedDaysCSS[$d].=' noPrices';
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

		$prices = tx_abbooking_div::getPrices($uid, $interval);
		$bookedPeriods = tx_abbooking_div::getBookings($uid, $this->lConf['PIDstorage'], $interval);
		$myBooked = tx_abbooking_div::calcBookedPeriods($bookedPeriods, $prices, $interval);

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
				if ($this->lConf['enableCalendarBookingLink'] // show booking links
					&& strtotime($year.'-'.$mon.'-'.$d) >= strtotime(strftime("%Y-%m-%d"))	// only future dates
					&& (strstr($cssClass, 'vacant') || strstr($cssClass, 'End')) // only vacant
					&& (! strstr($cssClass, 'noPrices'))
					)
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
		$interval['startList'] = strtotime( 'last monday', $interval['startList'] );
		$interval['endList'] = strtotime( 'next sunday', $interval['endList'] );

		$prices = tx_abbooking_div::getPrices($uid, $interval);
		$bookedPeriods = tx_abbooking_div::getBookings($uid, $this->lConf['PIDstorage'], $interval);
		$myBooked = tx_abbooking_div::calcBookedPeriods($bookedPeriods, $prices, $interval);

		$printDayNames = 1;
		$out = '<div class="availabilityCalendar">';
		for ($d=$interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
			if (date(w, $d) == 1) // open div on monday
				$out .= '<div class="calendarWeek">';
			$out .= '<ul class="CalendarLine">';
			unset($cssClass);
			if ($d<$interval['startDate'] || $d > $interval['endDate'])
				$cssClass = 'transp';

			$cssClass .= $myBooked[$d];

			 // print only in first line
			if ($printDayNames == 1) {
				$out .= '<li class="'.$cssClass.' DayNames">'.strftime("%a", $d).'</li>';
			}

			if ($this->lConf['enableCalendarBookingLink'] && $d >= strtotime(strftime("%Y-%m-%d"))
					&& (strstr($cssClass, 'vacant') || strstr($cssClass, 'End')) // only vacant
					&& (! strstr($cssClass, 'noPrices'))
				) {
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
			$out .= '</ul>';
			if (date(w, $d) == 0) {// close div after sunday
				$out .= '</div>';
				$printDayNames = 0;
			}
		}
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
			unset($interval);
			unset($linkBookNow);
			unset($contentError);
			if (sizeof($this->lConf['OffTimeProductIDs']) > 0)
				$offTimeProducts = ','.implode(',', $this->lConf['OffTimeProductIDs']);

			if ($product['maxAvailable'] < $this->lConf['numNights']) {
				$interval['limitedVacancies'] = $availableMaxDate;
				$contentError.= '<br /><i>'.$this->pi_getLL('error_vacancies_limited').'</i><br />';
				$bookNights = $product['maxAvailable'];
			} 
			else {
				$bookNights = $this->lConf['numNights'];
			}
			if ($product['minimumStay'] > $this->lConf['numNights']) {
				if ($product['minimumStay'] == 1)
					$text_periods = ' '.$this->pi_getLL('period');
				else
					$text_periods = ' '.$this->pi_getLL('periods');

				$contentError.='<br /><i>'.$this->pi_getLL('error_minimumStay').' '.$product['minimumStay'].' '.$text_periods.'</i><br />';
				if ($bookNights < $product['minimumStay'])
				$bookNights = $product['minimumStay'];
			}			

			$params_united = $this->lConf['startDateStamp'].'_'.$bookNights.'_'.$this->lConf['numPersons'].'_'.$product['uid'].$offTimeProducts.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor1';
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
				$offers[$i] .= '<li class="offerList"><div>'.$link.' <b>'.strtolower($this->pi_getLL('result_available')).'</b></div><br /> '; //.$this->pi_getLL('up_to');
				$availableMaxDate = strtotime('+ '.$product['maxAvailable'].' days', $this->lConf['startDateStamp']);
// 				$offers[$i] .= ' '.strftime("%A, %d.%m.%Y", $availableMaxDate);
//~ 				if ($product['maxAvailable'] < $this->piVars['ABnumNights']) {
//~ 					$interval['limitedVacancies'] = $availableMaxDate;
//~ 					$offers[$i] .= '<br /><i>'.$this->pi_getLL('error_vacancies_limited').'</i><br />';
//~ 				}
				$offers[$i] .= $contentError.'<br />';
				$offers[$i] .= $bodytext;

				$offers[$i] .= $this->printCalculatedRates($uid, $bookNights, 1);

				$linkBookNow = '<p class="bookNow">'.$this->pi_linkTP($this->pi_getLL('bookNow'), $params, 0, $this->lConf['gotoPID']).'</p>';
			} else {
				$offers[$i] .= '<li class="offerList"><div><b>'.$title.' '.strtolower($this->pi_getLL('result_occupied')).'</b> </div>';
			}

			// show calendar list only up to the vacant day
//~ 			if (isset($interval['limitedVacancies']))
//~ 				$interval['endDate'] = $interval['limitedVacancies'];
//~ 			else
//~ 				$interval['endDate'] = $this->lConf['endDateStamp'];
			$interval['startDate'] = $this->lConf['startDateStamp'];
			
			$interval['endDate'] = strtotime('+'.$bookNights.' day', $this->lConf['startDateStamp']);
// 			$interval['startList'] = strtotime('-2 day', $interval['startDate']);
//~  			$interval['endList'] = strtotime('+2 day', $interval['endDate']);

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
			if ($this->conf['dateFormat'] != '') {
				$dateFormat = $this->conf['dateFormat'];
			} else {
				// unfortunately, the jscalendar doesn't recognize %x as dateformat
				if ($GLOBALS['TSFE']->config['config']['language'] == 'de')
					$dateFormat = '%d.%m.%Y';
				else if ($GLOBALS['TSFE']->config['config']['language'] == 'en')
					$dateFormat = '%d/%m/%Y';
				else
					$dateFormat = '%Y-%m-%d';
			}
			$JSCalendar = JSCalendar::getInstance();
			// datetime format (default: time)
            $JSCalendar->setDateFormat(false, $dateFormat);
			$JSCalendar->setNLP(false);
            $JSCalendar->setInputField($name);
			$JSCalendar->setConfigOption('ifFormat', $dateFormat);
 			$out .= $JSCalendar->render(strftime($dateFormat, $value));
			if (($jsCode = $JSCalendar->getMainJS()) != '') {
				$GLOBALS['TSFE']->additionalHeaderData['abbooking_jscalendar'] = $jsCode;
			}
		} else {
			$out .= '<input '.$errorClass.' type="text" class="jscalendar" name="'.$name.'" id="'.$name.'" value="'.strftime('%x', $value).'" ><br/>';
		}

		if (isset($error)) {
			$out = str_replace('class="jscalendar"', 'class="jscalendar error"', $out);
		}

		return $out;
	}


}

?>
