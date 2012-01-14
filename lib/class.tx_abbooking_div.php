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

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT uid_foreign as uid, startdate, enddate, title','tx_abbooking_booking, tx_abbooking_booking_productid_mm',$query,'','startdate','');

			// one array for start and end dates. one for each pid
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$bookingsRaw[] = $row;
			};
		}

		$localbookings['bookings'] = $bookingsRaw;

 		return $localbookings;

	}

	/**
	 * Update all rates for a given interval
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$maxAvailable: ...
	 * @return	string		with amount, currency...
	 */
	function getAllRates($interval) {

		foreach ( $this->lConf['productDetails'] as $key => $val ) {

			// get all prices for given UID and given dates
			$this->lConf['productDetails'][$key]['prices'] = tx_abbooking_div::getPrices($key, $interval);
		}

		return 0;
	}

	/**
	 * Calculate the Rates
	 *
	 * @param	[type]		$uid: ...
	 * @param	[type]		$maxAvailable: ...
	 * @return	string		with amount, currency...
	 */
	function getPrices($key, $interval) {
//~ print_r($key);
//~ print_r($interval);
//~ print_r("\n");
			if ($this->lConf['useTSconfiguration'] == 1)
				return tx_abbooking_div::getRatesFromTS($key, $interval);
			else
				return tx_abbooking_div::getRatesFromDB($key, $interval);

	}

	/**
	 * Get rates per day
	 *
	 * @param	string		$uid
	 * @param	array		$interval: ...
	 * @return	array		with booking periods
	 */
	function getRatesFromDB($uid, $interval) {

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

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_abbooking_price.uid as uid,
			tx_abbooking_seasons.starttime as starttime, tx_abbooking_seasons.endtime as endtime,
			tx_abbooking_price.title as title, currency,
			adult1, adult2, adult3, adult4, adultX, child, teen,
			extraComponent1, extraComponent2, discount, discountPeriod,
			singleComponent1, singleComponent2, minimumStay,
			blockDaysAfterBooking, checkInWeekdays',
			'tx_abbooking_price,tx_abbooking_seasons_priceid_mm,tx_abbooking_seasons',$myquery,'',' FIND_IN_SET(tx_abbooking_price.uid, '.$GLOBALS['TYPO3_DB']->fullQuoteStr($priceids, 'tx_abbooking_price').') ','');
			$p = 0;

			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$pricesAvailable[$p] = $row;
				$languageOverlay =  tx_abbooking_div::getRecordRaw('tx_abbooking_price', $this->lConf['PIDstorage'], $row['uid']);
				// overwrite price title
				$pricesAvailable[$p]['title'] = $languageOverlay[$row['uid']]['title'];
				$p++;
			};

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
		return $pricePerDay;
	}

	/**
	 * Check if Season is valild for a given date
	 *
	 * @param	string		$uid
	 * @param	array		$interval: ...
	 * @return	array		with booking periods
	 */
	function checkCheckinWeekDays($day, $checkInWeekdays) {

//~   print_r("checkCheckinWeekDays\n");
//~   print_r(explode(',', $checkInWeekdays));
//~   print_r("\n");
//~  print_r(date(w, $this->lConf['startDateStamp']));
//~ print_r("\n");
 		if (($checkInWeekdays == "") ||
			in_array(date(w, $day), explode(',', $checkInWeekdays)   ))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * Check if Season is valild for a given date
	 *
	 * @param	string		$uid
	 * @param	array		$interval: ...
	 * @return	array		with booking periods
	 */
	function checkSeasonValidation($season, $interval) {

		// there are no details about this season -> skip
		if (! is_array($season))
			return FALSE;

		// set date timestamp to 00:00:00
		$season['startDateStamp'] = date_format(date_time_set(date_create_from_format($this->lConf['dateFormatConfig'],$season['startDate']), 0, 0), 'U');
		$season['endDateStamp'] = date_format(date_time_set(date_create_from_format($this->lConf['dateFormatConfig'],$season['endDate']), 0, 0), 'U');

			// there are four cases of time intervals:
			// 1: start set, stop set                 |-------------|
			// 2: start set, stop open                |---------------->
			// 3: start open, stop set             <----------------|
			// 4: start open, stop open (default rate) <------------->

		if (($season['startDateStamp'] <= $interval['endList'] || $season['startDateStamp'] == 0) &&
			($season['endDateStamp'] >= $interval['startList'] || $season['endDateStamp'] == 0)) {
//~  				print_r('season ok' . $season['startDate'] . "\n");

			return $season;
		}
		else
//~  			print_r('season NOT ok' . $season['startDate'] . "\n");
			return FALSE;
	}
	/**
	 * Get rates per day
	 *
	 * @param	string		$uid
	 * @param	array		$interval: ...
	 * @return	array		with booking periods
	 */
	function getRatesFromTS($key, $interval) {

		$pricePerDay = array();

//~ 		if (!isset($uid))
//~ 			$uid = $this->lConf['ProductID'];

		if (!isset($interval['startList']) && !isset($interval['endList'])) {
			$interval['startList'] = $interval['startDate'];
			$interval['endList'] = $interval['endDate'];
		}

		// 1. get the rates for the product
// 		$ratesTitleArray = $this->conf['products.'][$tstitle.'.']['rates.'];
		$ratesTitleArray = $this->lConf['productDetails'][$key]['rates.'];
		if (is_array($ratesTitleArray))
		foreach ($ratesTitleArray as $rate) {
			$rateFound = $this->conf['rates.'][$rate.'.'];
			$rateFound['title'] = $this->getTSTitle($this->conf['rates.'][$rate.'.']['title.']);
			// 2. get the seasons for the rates and drop seasons outside interval
			$seasonFound = 0;
			if (is_array($this->conf['rates.'][$rate.'.']['seasons.']))
			foreach ($this->conf['rates.'][$rate.'.']['seasons.'] as $season) {
				$checkedSeason = tx_abbooking_div::checkSeasonValidation($this->conf['seasons.'][$season.'.'], $interval);
				if ($checkedSeason !== FALSE) {
					$rateFound['seasons'][$season] = $checkedSeason;
					$seasonFound++;
				}
			}
			// drop rates without any season/time description
			if ($seasonFound>0)
				$allRates[] = $rateFound;
		}
//~ print_r($interval);
//~ print_r("getrates allrates TS\n");
//~ print_r(sizeof($allRates));
//~ print_r($allRates);
//~ print_r("-----------------\n");
//~
		// 3. get the rate for every day...
		// get the valid prices per day
		for ($d = $interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
			$pricePerDay[$d] = 'noPrice';
			if (is_array($allRates))
				foreach ($allRates as $id => $singlePrice) {
					$checkInOk = tx_abbooking_div::checkCheckinWeekDays($d, $singlePrice['checkInWeekdays']);
					if ($checkInOk === FALSE) {
						$singlePrice['checkInOk'] = '0';
//~ 							print_r("checkin NOT ok on ".strftime("%x", $d)."\n");
					}
					else {
//~ 						print_r("checkin ok ".strftime("%x", $d)."\n");
						$singlePrice['checkInOk'] = '1';
					}

					$validPriceFound = 0;
					foreach ($singlePrice['seasons'] as $seasonName => $season) {
//~ print_r("getrates allrates TS\n");
//~ print_r($singlePrice['checkInWeekdays']);
//~ print_r($singlePrice);
//~ print_r("-----------------\n");
						if (($season['startDateStamp'] <= $d || $season['startDateStamp'] == 0)
							&& ($season['endDateStamp'] >= $d || $season['endDateStamp'] == 0)) {
							// if no valid price is found - go further in the price array.
							// otherwise the first in the list is the right.
							$validPriceFound = 1;
//~ 						print_r("checkinThisSingle: ".$validPriceFound."\n");
//~ 						print_r($singlePrice);
							break;
						}
					}
					if ($validPriceFound == 1) {
						$pricePerDay[$d] = $singlePrice;
						break;
					}
				}
		}
//~ print_r("getrates from TS\n");
//~ print_r(sizeof($pricePerDay));
//~ print_r($pricePerDay);
//~ print_r("-----------------\n");

		if (count($pricePerDay)>0)
			return $pricePerDay;
		else
			return FALSE;

	}

	/**
	 * Calculate Booked Days-Array for Calendar View
	 *
	 * @param	array		$bookings
	 * @param	[type]		$interval: ...
	 * @param	[type]		$interval: ...
	 * @return	array		with booking periods
	 */
	function cssClassBookedPeriods($bookings, $prices, $interval) {

		$bookedDays = array();
		$bookedDaysCSS = array();

		foreach ($bookings['bookings'] as $id => $singleBooking) {
			for ($d = $singleBooking['startdate']; $d <= $singleBooking['enddate']; $d=strtotime("+ 1day", $d)) {
				$bookedDays[$d]['booked']++ ;
			}
			$bookedDays[$singleBooking['startdate']]['isStart']++;
			$bookedDays[$singleBooking['enddate']]['isEnd']++;
		}

		// make ready to print css class like "[Day|Weekend] [booked|vacant] [Start|End]"
		for ($d = $interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
			if (date("w", $d)== 0 || date("w", $d)== 6)
				$bookedDaysCSS[$d] =' Weekend';
			else
				$bookedDaysCSS[$d] =' Day';

			if ($bookedDays[$d]['booked'] > 0) {
				$bookedDaysCSS[$d].= ' booked';
				if ($bookedDays[$d]['isStart'] == $bookedDays[$d]['booked'])
					$bookedDaysCSS[$d] .= ' Start';
				else if ($bookedDays[$d]['isEnd'] == $bookedDays[$d]['booked'])
					$bookedDaysCSS[$d] .= ' End';
			}
			else
				$bookedDaysCSS[$d] .= ' vacant';
			if ($prices[$d] == 'noPrice')
				$bookedDaysCSS[$d] .= ' noPrices';
		}
		return $bookedDaysCSS;
	}

	/**
	 * Calculate Booked Days-Array for Calendar View
	 *
	 * @param	array		$bookings
	 * @param	[type]		$interval: ...
	 * @return	array		with booking periods
	 */
	function cssClassBookedCheckInView($bookings, $interval) {

		$bookedDays = array();
		$bookedDaysCSS = array();
		$uids = array();

		foreach ($bookings['bookings'] as $id => $singleBooking) {
			for ($d = $interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
				if (empty($bookedDays[$d][$singleBooking['uid']]))
					$bookedDays[$d][$singleBooking['uid']][$id] = 'vacant';
			}
			for ($d = $singleBooking['startdate']; $d <= $singleBooking['enddate']; $d=strtotime("+ 1day", $d)) {
				$bookedDays[$d][$singleBooking['uid']][$id] = 'booked';
			}
			$bookedDays[$singleBooking['startdate']][$singleBooking['uid']][$id] .= ' Start';
			$bookedDays[$singleBooking['enddate']][$singleBooking['uid']][$id] .= ' End';
			$uids[] = $singleBooking['uid'];
		}

		return $bookedDays;
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
			if ($this->lConf['startDateStamp'] > 0) {
				$interval['startDate'] =  strtotime(strftime("%Y-%m-1", $this->lConf['startDateStamp']));
			}
			else {
				$interval['startDate'] = strtotime(strftime("%Y-%m-1"));
			}
//~ 		print_r(strftime("%Y-%m-%d", $interval['startDate']));
			$interval['endDate'] = strtotime('+'.$months.' months', $interval['startDate']);
//~ 		print_r(strftime("%Y-%m-%d", $interval['endDate']));

//~ 			$today = strtotime(strftime("%Y-%m-%d"));
//~ 			$interval['startDate'] = strtotime(strftime("%Y-%m-1"));
//~ 			$interval['endDate'] = strtotime('+'.$months.' months', $today);
		}
		$interval['startList'] = $interval['startDate'];
		$interval['endList'] = $interval['endDate'];

		$prices = tx_abbooking_div::getPrices($uid, $interval);
		$bookedPeriods = tx_abbooking_div::getBookings($uid, $this->lConf['PIDstorage'], $interval);
		$myBooked = tx_abbooking_div::cssClassBookedPeriods($bookedPeriods, $prices, $interval);

		if (empty($this->lConf['ProductID']) && empty($uid)) {
			$out = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
		}

		// date select form
		$out .='<form action="'.$this->pi_getPageLink($GLOBALS['TSFE']->id).'" method="POST">
				<label for="'.$this->prefixId.'[checkinDate]'.'_cb">&nbsp;</label><br/>';
		$out .= tx_abbooking_div::getJSCalendarInput($this->prefixId.'[checkinDate]', $interval['startDate'], $ErrorVacancies);
		if (!$this->isRobot())
			$out .= '<input class="submit_dateSelect" type="submit" name="'.$this->prefixId.'[submit_button_CheckinOverview]" value="'.htmlspecialchars($this->pi_getLL('submit_button_label')).'">';
		$out .= '</form>
			<br />
		';

		$out .= '<table class="listlegend"><tr>';
		$out .= '<td class="vacant">&nbsp;</td><td class="legend">' . $this->pi_getLL('vacant day') .'</td>';
		$out .= '<td class="booked">&nbsp;</td><td class="legend">'.	$this->pi_getLL('booked day').' </td>';
		$out .= '</tr></table>';

		$out .= '<table class="availabilityCalendar">';
		$out .= '<tr>';

		// runs $rows * $columns times
		for ($i = 0; $i < $months; $i++) {
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

			$bookingRate = 0;
			for ($d=1; $d <= date("t", strtotime( $year . "-" . $mon . "-01")); $d++){
				if ($days % $rowsCalendar == 0 && $days != 0 ) { // new row after 42 or 7 days
					$out .= '</tr><tr>';
				}

				$cssClass = $myBooked[strtotime($year."-".$mon."-".$d)];

				$params_united = strtotime($year.'-'.$mon.'-'.$d).'_'.$this->lConf['daySelector'].'_'.$this->lConf['adultSelector'].'_'.$this->lConf['ProductID'].'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor0';

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
				if ($this->lConf['showBookingRate'] && strstr($cssClass, 'booked') && !strstr($cssClass, 'booked End'))
					$bookingRate ++;
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
			$out .= '</table>';
			if ($this->lConf['showBookingRate'])
				$out .= '<p>'.round((100*$bookingRate/date("t", strtotime( $year . "-" . $mon . "-01"))),0).' %</p>';
			$out .= "\n";
		}

		$out .= '</tr></table>';

		return $out;
	}

	/**
	 * Display the availability calendar for one or more months depending on flexform configuration
	 *
	 * @param	integer		$uid: ...
	 * @param	[type]		$interval: ...
	 * @return	HTML-table		with calendar view
	 */
	function printCheckinOverview($uid, $interval = array()) {

		$this->pi_loadLL();
		$myBooked = array();
//~ 		$rows = (int)$this->lConf['numMonthsRows'];
//~ 		$columns = (int)$this->lConf['numMonthsCols'];
//~ 		$weeks = 3;

		if (!isset($interval['startDate']) && !isset($interval['endDate'])) {
			if ($this->lConf['startDateStamp']>0)
				$interval['startDate'] = $this->lConf['startDateStamp'];
			else {
				$today = strtotime(strftime("%Y-%m-%d"));
				$interval['startDate'] = $today;
			}
			$interval['endDate'] = strtotime('+1 month', $interval['startDate']);
		}
		$interval['startList'] = $interval['startDate'];
		$interval['endList'] = $interval['endDate'];

		// date select form
		$content .='<form action="'.$this->pi_getPageLink($GLOBALS['TSFE']->id).'" method="POST">
				<label for="'.$this->prefixId.'[checkinDate]'.'_cb">&nbsp;</label><br/>';

		$content .= tx_abbooking_div::getJSCalendarInput($this->prefixId.'[checkinDate]', $interval['startDate'], $ErrorVacancies);

		if (!$this->isRobot())
			$content .= '<input class="submit_dateSelect" type="submit" name="'.$this->prefixId.'[submit_button_CheckinOverview]" value="'.htmlspecialchars($this->pi_getLL('submit_button_label')).'">';
		$content .= '</form>
			<br />
		';

		$out = $content;

//~ 		$prices = tx_abbooking_div::getPrices($uid, $interval);
		$bookedPeriods = tx_abbooking_div::getBookings($uid, $this->lConf['PIDstorage'], $interval);
		// keep uids in fixed order...
		function cmpUIDs($a, $b)
		{
			if ($a['uid'] == $b['uid']) {
				if ($a['startdate'] == $b['startdate'])
					return 0;
				else if ($a['startdate'] < $b['startdate'])
					return -1;
				else
					return 1;
			}
			return ($a['uid'] < $b['uid']) ? -1 : 1;
		}
		usort($bookedPeriods['bookings'], "cmpUIDs");

		$myBooked = tx_abbooking_div::cssClassBookedCheckInView($bookedPeriods, $interval);

		if (empty($this->lConf['ProductID']) && empty($uid)) {
			$out = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
		}

//~ print_r($bookedPeriods);
		foreach ($bookedPeriods['bookings'] as $id => $row) {
			for ($d = $row['startdate']; $d <= $row['enddate']; $d=strtotime("+ 1 day", $d)) {
//~ print_r($myBooked[$d][$row['uid']]);
				$is_arrival = strpos($myBooked[$d][$row['uid']][$id], 'Start');
				$is_depature = strpos($myBooked[$d][$row['uid']][$id], 'End');
				if ($this->lConf['showOnlyChanges'] == '1' && $is_arrival === FALSE && $is_depature === FALSE )
					continue;
				$tilearray = explode(',', $row['title']);
				$product = $this->lConf['productDetails'][$row['uid']];
				$bookingInfos[$d]['title'] .= '<div class="bookingInfos">'.$product['title'].': ';
				if ($is_arrival !== FALSE)
					$bookingInfos[$d]['title'] .= '<div id="arrival">'.$this->pi_getLL("arrival");
				else if ($is_depature !== FALSE)
					$bookingInfos[$d]['title'] .= '<div id="depature">'.$this->pi_getLL("depature");
				else
					$bookingInfos[$d]['title'] .= '<div id="stay">';
				$bookingInfos[$d]['title'] .= $tilearray[1] .'</div></div>';
			}
		};
//~ print_r($bookingInfos);
		$out .= '<div class="calendarCheckinOverview">';
		$out .= '<div class="calendarWeek">';
		// step from last monday to next sunday through the list.
		$showDays = 0;
		for ($d=$interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {

			if (!is_array($bookingInfos[$d]) && $this->lConf['showOnlyChanges'] == '1')
				continue;

			$showDays++;
			if ($showDays > $this->lConf['checkInNumDays'])
				break;

//~ 			if (date(w, $d) == 1 || $d == $interval['startList']) {// open div on monday
//~ 			}
			$out .= '<ul class="CalendarLine">';
			unset($cssClass);

			// show vacant as default,
			// only booked items of any uid are marked "booked" as long as they are no "booked End"
			$cssClass = 'vacant';
			if (is_array($myBooked[$d]))
			foreach ($myBooked[$d] as $uid => $cssClassBookingUID) {
				foreach ($cssClassBookingUID as $id => $cssClassBooking) {
					if (strpos($cssClassBooking, 'booked')!==FALSE && strpos($cssClassBooking, 'booked End')===FALSE)
						$cssClass = 'booked';
				}
			}
			if ($d < $today || $d > $interval['endDate'])
				$cssClass .= ' transp';

			$out .= '<li class="'.$cssClass.' DayNames">'.strftime("%a, %x", $d).'</li>';

			unset($infoField);

			$infoField = ' '.$bookingInfos[$d]['title'];
			$out .= '<li class="'.$cssClass.'">'.$infoField.'</li>';
			$out .= '</ul>';



//~ 			if (date(w, $d) == 0 || $d == $interval['endList']) {// close div after sunday
//~ 			}
		}
		$out .= '</div>';
		$out .= '</div>';

		return $out;
	}

	/**
	 * Display the availability calendar as single line for a given interval
	 *
	 * @param	integer		$uid: ...
	 * @param	array		$interval: ...
	 * @return	HTML-list		of calendar days
	 */
//~ 	function printAvailabilityCalendarDiv($uid, $interval = array()) {
	function printAvailabilityCalendarDiv($uid, $interval, $months = 0, $cols = 1) {

//~ print_r($interval);
//~ 		$this->pi_loadLL();
//~ 		$cur_de = setlocale(LC_ALL, 0);
//~ 		$loc_en = setlocale(LC_ALL, 'en_GB.UTF-8', 'en_GB.utf8', 'eng', 'en_GB', 'en_US',  'en_GB.ISO8859-1', 'en_GB@euro', 'en');
//~ 		$loc_de = setlocale(LC_ALL, 'de_DE.UTF-8', 'de_DE.utf8', 'deu', 'de_DE', 'deu_deu', 'de_DE.ISO8859-1', 'de_DE@euro', 'de', 'ge');
//~ 		$out .=  "Current setting is " .print_r($cur_de, 1). "<br />\n";
//~ 		$out .=  "Preferred locale for german on this system is " .$loc_de. "<br />\n";
//~ 		$out .=  "Preferred locale for english on this system is " .$loc_en. "<br />\n";

		// disable booking links for robots
		if ($this->isRobot())
			$this->lConf['enableCalendarBookingLink'] = 0;

		if (!isset($interval['startDate']) && !isset($interval['endDate'])) {
			$today = strtotime(strftime("%Y-%m-%d"));
			if ($this->lConf['startDateStamp'] > 0) {
				$interval['startDate'] =  strtotime('first day of this month', $this->lConf['startDateStamp']);
			}
			else {
				$interval['startDate'] = strtotime('first day of this month');
			}
			$interval['endDate'] = strtotime('+ '.$months.' months', $interval['startDate'])-86400;
			$interval['endDate'] = strtotime('last day of this month', $interval['endDate']);
		} else {
			$interval['startDate'] =  strtotime('first day of this month', $interval['startDate']);
			$interval['endDate'] = strtotime('last day of this month', $interval['endDate']);
		}

		$interval['startList'] = strtotime( 'last monday', $interval['startDate'] );
		$interval['endList'] = strtotime( 'next sunday', $interval['endDate'] );

//~ print_r($interval);

		if ($months == 0)
		$months = date('n', $interval['endList']) - date('n', $interval['startList']) +
				(date('Y', $interval['endList']) - date('Y', $interval['startList']) +1)*12;

//~ print_r($months);
		// this form of date_diff is erroneous... so check and reduce amount of months
//~ 		if (strtotime('+ '.$months.' months', $interval['startDate']) - $interval['startDate']>0)
//~ 			$month_diff--;

		$prices = tx_abbooking_div::getPrices($uid, $interval);
		$bookedPeriods = tx_abbooking_div::getBookings($uid, $this->lConf['PIDstorage'], $interval);
		$myBooked = tx_abbooking_div::cssClassBookedPeriods($bookedPeriods, $prices, $interval);

		if (empty($this->lConf['ProductID']) && empty($uid)) {
			$out = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
		}


		// date select form
		if ($this->lConf['showDateNavigator']) {
			$out .='<form action="'.$this->pi_getPageLink($GLOBALS['TSFE']->id).'" method="POST">
					<label for="'.$this->prefixId.'[checkinDate]'.'_cb">&nbsp;</label><br/>';
			$out .= tx_abbooking_div::getJSCalendarInput($this->prefixId.'[checkinDate]', $interval['startDate'], $ErrorVacancies);
			if (!$this->isRobot())
				$out .= '<input class="submit_dateSelect" type="submit" name="'.$this->prefixId.'[submit_button_CheckinOverview]" value="'.htmlspecialchars($this->pi_getLL('submit_button_label')).'">';
			$out .= '</form>
				<br />
			';
		}

		$colCount = 0;

		$out .= '<div class="availabilityCalendar">';
		for ($m = $interval['startDate']; $m <= strtotime('+ '.($months-1).' months', $interval['startDate']); $m=strtotime('+1 month', $m)) {
			$bookingRate = 0;
			$colCount++;
			$out .= '<div class="calendarMonth"><div class="calendarMonthName">'.strftime("%B %Y", $m).'</div>';
			$printDayNames = 1;
			if (date(w, $m) != 1) // if no monday go back to last monday
				$interval['startList'] = strtotime( 'last monday', $m);
			else
				$interval['startList'] = $m;
			$interval['endList'] = strtotime( 'last day of this month', $m);

			for ($d = $interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
				if (date(w, $d) == 1) {// open div on monday

					$out .= '<div class="calendarWeek"><ul class="CalendarLine">';

					if ($printDayNames == 1) {
						// fill noDays at the end of the month
						for ($fillDay = $d; $fillDay <= strtotime('next sunday', $d); $fillDay=strtotime('+1 day', $fillDay)) {
								$out .= '<li class="'.$myBooked[$fillDay].' DayNames">'.strftime("%a", $fillDay).'</li>';
						}
						$out .= '</ul>';
						$out .= '</div>';
						$printDayNames = 0;
						$out .= '<div class="calendarWeek"><ul class="CalendarLine">';
					}
				}
				unset($cssClass);

				if ($d < strtotime('first day of this month', $m) || $d > strtotime('last day of this month', $m)) {
					$cssClass = 'noDay';
					$printDay = '&nbsp;';$printDay = strftime("%d", $d);
				} else {
					$cssClass = $myBooked[$d];
					$printDay = strftime("%d", $d);
				}
				if ($this->lConf['showBookingRate'] && strstr($cssClass, 'booked') && !strstr($cssClass, 'booked End'))
					$bookingRate ++;


				if ($this->lConf['enableCalendarBookingLink'] && $d >= strtotime(strftime("%Y-%m-%d"))
						&& (strstr($cssClass, 'vacant') || strstr($cssClass, 'End')) // only vacant
						&& (! strstr($cssClass, 'noPrices'))  && ($prices[$d]['checkInOk']=='1')
					) {
					// set default daySelector = 2, adultSelector = 2
					//#### 2_2 durch $this->lConf['daySelector'] und $this->lConf['adultSelector'] ersetzt ###
					$params_united = $d.'_'.$this->lConf['daySelector'].'_'.$this->lConf['adultSelector'].'_'.$uid.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor0';
					$params = array (
						$this->prefixId.'[ABx]' => $params_united,
					);
					$out .= '<li class="'.$cssClass.'">'.$this->pi_linkTP($printDay, $params, 0, $this->lConf['gotoPID']).'</li>';
				}
				else
					$out .= '<li class="'.$cssClass.'">'.$printDay.'</li>';
				if (date(w, $d) == 0) {// close div after sunday
					$out .= '</ul>';
					$out .= '</div>';
					//$printDayNames = 0;
				} else	if ($d == strtotime('last day of this month', $m)) {
					// fill noDays at the end of the month
					for ($fillDay = $d; $fillDay < strtotime('next sunday', $d); $fillDay=strtotime('+1 day', $fillDay)) {
							$out .= '<li class="noDay">&nbsp;</li>';
					}
					$out .= '</ul>';
					$out .= '</div>';
				}
			}
			if ($this->lConf['showBookingRate'])
				$out .= '<p>'.round((100*$bookingRate/date("t", strtotime( $year . "-" . $mon . "-01"))),0).' %</p>';
			$out .= '</div>'; // <div class="calendarMonth">
			if ($colCount == $cols) {
				$out .= '<div class="clear" style="clear: both;"></div>';
				$colCount = 0;
			}
		}

		$out .= '</div>';
		$out .= '<div class="clear" style="clear: both;"></div>';

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
		$myBooked = tx_abbooking_div::cssClassBookedPeriods($bookedPeriods, $prices, $interval);
//~ print_r("printAvailabilityCalendarLine\n");
//~ print_r($prices);
//~ print_r($myBooked);
		$printDayNames = 1;
		$out = '<div class="availabilityCalendarLine">';
		for ($d=$interval['startList']; $d <= $interval['endList']; $d=strtotime('+1 day', $d)) {
			if (date(w, $d) == 1) // open div on monday
				$out .= '<div class="calendarWeek">';
			$out .= '<ul class="CalendarLine">';
			unset($cssClass);
			if ($d < $interval['startDate'] || $d > $interval['endDate'])
				$cssClass = 'transp';

			$cssClass .= $myBooked[$d];

			 // print only in first line
			if ($printDayNames == 1) {
				$out .= '<li class="'.$cssClass.' DayNames">'.strftime("%a", $d).'</li>';
			}

			if ($this->lConf['enableCalendarBookingLink'] && $d >= strtotime(strftime("%Y-%m-%d"))
					&& (strstr($cssClass, 'vacant') || strstr($cssClass, 'End')) // only vacant
					&& (! strstr($cssClass, 'noPrices'))  && ($prices[$d]['checkInOk']=='1')
				) {
				// set default daySelector = 2, adultSelector = 2
				//#### 2_2 durch $this->lConf['daySelector'] und $this->lConf['adultSelector'] ersetzt ###
				$params_united = $d.'_'.$this->lConf['daySelector'].'_'.$this->lConf['adultSelector'].'_'.$uid.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor0';
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
				$cssStep1="step1 current";
				$cssStep2="step2";
				$cssStep3="step3";
				break;
			case '3':
				$cssStep1="step1";
				$cssStep2="step2 current";
				$cssStep3="step3";
				break;
			case '4':
				$cssStep1="step1";
				$cssStep2="step2";
				$cssStep3="step3 current";
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

		$contentError = array();
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
			unset($contentError);

			if (sizeof($this->lConf['OffTimeProductIDs']) > 0)
				$offTimeProducts = ','.implode(',', $this->lConf['OffTimeProductIDs']);

			if ($product['maxAvailable'] < $this->lConf['daySelector']) {
				$interval['limitedVacancies'] = $availableMaxDate;
				$contentError[] =  $this->pi_getLL('error_vacancies_limited');
				$bookNights = $product['maxAvailable'];
			}
			else {
				$bookNights = $this->lConf['daySelector'];
			}
			if ($product['minimumStay'] > $this->lConf['daySelector']) {
				if ($product['minimumStay'] == 1)
					$text_periods = ' '.$this->pi_getLL('period');
				else
					$text_periods = ' '.$this->pi_getLL('periods');

				$contentError[] = $this->pi_getLL('error_minimumStay').' '.$product['minimumStay'].' '.$text_periods;
				if ($bookNights < $product['minimumStay'])
				$bookNights = $product['minimumStay'];
			}

			// check if checkIn is ok for startDate
			if ($product['prices'][$this->lConf['startDateStamp']]['checkInOk'] == '0') {
				$contentError[] = $this->pi_getLL('error_no_checkIn_on').' '.strftime('%a, %x', $this->lConf['startDateStamp']);
				$enableCheckBookingLink = 0;
				for ($j=$this->lConf['startDateStamp']; $j < strtotime('+14 day', $this->lConf['startDateStamp']); $j=strtotime('+1 day', $j)) {
//~ print_r(strftime('%x', $j));
					if ($product['prices'][$j]['checkInOk'] == '1') {
						$interval['startDate'] = $j;
						$params_united = $interval['startDate'].'_'.$bookNights.'_'.$this->lConf['adultSelector'].'_'.$product['uid'].$offTimeProducts.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor1';
						$params = array (
							$this->prefixId.'[ABx]' => $params_united,
						);
						if ($this->lConf['enableCheckBookingLink'])
							$link = $this->pi_linkTP(strftime('%a, %x', $interval['startDate']), $params, 0, $this->lConf['gotoPID']);
						else
							$link = strftime('%a, %x', $j);

						$contentError.= $this->pi_getLL('error_next_checkIn_on').' '.$link;
//~ 						$this->lConf['startDateStamp'] = $j;
//~ 						$enableCheckBookingLink = 1;
						break;
					}
				}
			} else
				$enableCheckBookingLink = $this->lConf['enableCheckBookingLink'];

			// show calendar list only up to the vacant day
			if (empty($interval['startDate']))
				$interval['startDate'] = $this->lConf['startDateStamp'];
			$interval['endDate'] = strtotime('+'.$bookNights.' day', $this->lConf['startDateStamp']);


			$params_united = $interval['startDate'].'_'.$bookNights.'_'.$this->lConf['adultSelector'].'_'.$product['uid'].$offTimeProducts.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor1';
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


			if ($enableCheckBookingLink)
				$link = $this->pi_linkTP($title, $params, 0, $this->lConf['gotoPID']);
			else
				$link = $title;

			if ($product['maxAvailable'] > 0) {
				$offers['numOffers']++;
				$offers[$i] .= '<li class="offerList"><div class="productTitle">'.$link.' <b>'.strtolower($this->pi_getLL('result_available')).'</b></div>';
				$availableMaxDate = strtotime('+ '.$product['maxAvailable'].' days', $this->lConf['startDateStamp']);

				if (count($contentError)>0) {
					$offers[$i] .= '<ul class="errorHints">';
					foreach ($contentError as $error)
						$offers[$i] .= '<li>'.$error.'</li>';
					$offers[$i] .= '</ul>';
				}
				$offers[$i] .= $bodytext;

				if ($this->lConf['enableCheckBookingLink'] == 1)
					$offers[$i] .='<form  class="requestForm" action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">';

				$offers[$i] .= $this->printCalculatedRates($uid, $bookNights, 1, 1);
				if ($enableCheckBookingLink)
					$linkBookNow = '<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'">
									<input type="hidden" name="'.$this->prefixId.'[ABwhatToDisplay]" value="BOOKING"><br/>
									<input class="submit" type="submit" name="'.$this->prefixId.'[submit_button]" value="'.htmlspecialchars($this->pi_getLL('bookNow')).'">
									</form>
					';
				else
					$linkBookNow = '';

			} else {
				$offers[$i] .= '<li class="offerList"><div class="productTitle"><b>'.$title.' '.strtolower($this->pi_getLL('result_occupied')).'</b> </div>';
			}


			// show calendars following TS settings
			if ($this->lConf['form']['showCalendarMonth']>0) {
				if (intval($this->lConf['form']['showMonthsBeforeStart'])>0)
					$intval['startDate'] = strtotime('-'.$this->lConf['form']['showMonthsBeforeStart'].' months', $interval['startDate']);
				else
					$intval['startDate'] = $interval['startDate'];
				$intval['endDate'] = strtotime('+'.$this->lConf['form']['showCalendarMonth'].' months', $intval['startDate']);
	//~ 			$intval['startDate'] = strtotime('first day of this month', $interval['startDate']);
	//~ 			$intval['endDate'] = strtotime('+'.$this->lConf['form']['showCalendarMonth'].' months', $intval['startDate'])-86400;
				$offers[$i] .= tx_abbooking_div::printAvailabilityCalendarDiv($product['uid'].$offTimeProducts,  $intval, $this->lConf['form']['showCalendarMonth'], 0);

			} else if ($this->lConf['form']['showCalendarWeek']>0) {
					$intval['startDate'] = $interval['startDate'];

				$intval['endDate'] = strtotime('+'.$this->lConf['form']['showCalendarWeek'].' weeks', $intval['startDate']);
				$offers[$i] .= tx_abbooking_div::printAvailabilityCalendarLine($product['uid'].$offTimeProducts, $intval);
			} else
				$offers[$i] .= tx_abbooking_div::printAvailabilityCalendarLine($product['uid'].$offTimeProducts, $interval);


//~ 			$offers[$i] .= tx_abbooking_div::printAvailabilityCalendarLine($product['uid'].$offTimeProducts, $interval);

			if ($this->lConf['enableCheckBookingLink'])
				$offers[$i] .= $linkBookNow;
			else
				$offers[$i] .= '</form>';
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
				$dateFormat = str_replace(array('d', 'm', 'y', 'Y'), array('%d', '%m', '%y', '%Y'), $this->conf['dateFormat']);
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
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/lib/class.tx_abbooking_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/lib/class.tx_abbooking_div.php']);
}

?>
