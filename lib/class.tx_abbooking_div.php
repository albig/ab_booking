<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2013 Alexander Bigga <linux@bigga.de>
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
		$where = 'pid='.$pid.' AND uid IN('.$uid.') AND (sys_language_uid IN (-1,0) OR (sys_language_uid = ' .$GLOBALS['TSFE']->sys_language_uid. '))';
		// use the TYPO3 default function for adding hidden = 0, deleted = 0, group and date statements
		$where  .= $GLOBALS['TSFE']->sys_page->enableFields($table, $show_hidden = 0, $ignore_array);
		if (!empty($where_extra))
			$where  .= ' AND '.$where_extra;
		$order = '';
		$group = '';
		$limit = '';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $group, $order, $limit);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
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
	function getBookings($uid, $interval) {

		$bookingsRaw = array();
		$storagePid = $this->lConf['PIDstorage'];

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

		foreach ( $this->lConf['productDetails'] as $uid => $val ) {
			// get all prices for given UID and given dates
			$this->lConf['productDetails'][$uid]['prices'] = tx_abbooking_div::getPrices($uid, $interval);
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
	function getPrices($uid, $interval) {

		return tx_abbooking_div::getRatesFromDB($uid, $interval);
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

		if ($uid != '') {
			// SELECT

			// 1. get priceid for uid (old way)
			$myquery= 'pid='.$this->lConf['PIDstorage'].' AND uid IN ('.$uid.') AND capacitymax>0 AND deleted=0 AND hidden=0';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, priceid','tx_abbooking_product',$myquery,'','','');
			// one array for start and end dates. one for each pid
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$priceids = $row['priceid'];
			};

			// 1. get priceid for uid (new way)
			$where_extra = "capacitymax > 0 ";
			$mrow = tx_abbooking_div::getRecordRaw('tx_abbooking_product', $this->lConf['PIDstorage'], $uid, $where_extra);

			foreach ($mrow as $muid => $mproduct) {
				$priceids =  $mproduct['priceid'];
			}

			if (empty($priceids))
				return;

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
			tx_abbooking_seasons.starttime as starttime, tx_abbooking_seasons.endtime as endtime, tx_abbooking_seasons.validWeekdays as validWeekdays,
			tx_abbooking_price.title as title, currency,
			adult1, adult2, adult3, adult4, adultX, child, teen,
			extraComponent1, extraComponent2, discount, discountPeriod,
			singleComponent1, singleComponent2, minimumStay, daySteps,
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
				for ($i=0; $i < $p; $i++) {
					if (($pricesAvailable[$i]['starttime'] <= $d || $pricesAvailable[$i]['starttime'] == 0)
						&& ($pricesAvailable[$i]['endtime'] > $d || $pricesAvailable[$i]['endtime'] == 0)
						&& (tx_abbooking_div::checkCheckinWeekDays($d, $pricesAvailable[$i]['validWeekdays']))
						)
						break;
					// if no valid price is found - go further in the price array. otherwise the first in the list is the right.
				}
				if ($i == $p)
				  $pricePerDay[$d] = 'noPrice';
				else {
					$pricePerDay[$d] = $pricesAvailable[$i];
					$checkInOk = tx_abbooking_div::checkCheckinWeekDays($d, $pricePerDay[$d]['checkInWeekdays']);
					if ($checkInOk === FALSE) {
						$pricePerDay[$d]['checkInOk'] = '0';
					}
					else {
						$pricePerDay[$d]['checkInOk'] = '1';
					}
				}
			}
		}
		return $pricePerDay;
	}

	/**
	 * Check if day is in checkInWeekday --> valid
	 *
	 * @param	string		$day
	 * @param	array		$checkInWeekdays: ...
	 * @return	boolean		true on success
	 */
	function checkCheckinWeekDays($day, $checkInWeekdays) {

 		if (($checkInWeekdays == "") || ($checkInWeekdays == '*') ||
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
			return $season;
		}
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
	 * @param	array		$interval: ...
	 * @return	HTML-table		with calendar view
	 */
	function printCheckinOverview($uid, $interval = array()) {

		$this->pi_loadLL();
		$myBooked = array();

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

		$content .='<form action="'.$this->pi_getPageLink($GLOBALS['TSFE']->id).'" method="POST">';

		// always render the page...
		$content .= '<input type="hidden" name="'.$this->prefixId.'[abnocache]" value="1">';

		// -------------------------------------
		// field startDate with - possible datepicker
		// -------------------------------------
		// date select form
		$content .= '<div class="startdate">';
		$content .= '<label for="'.$this->prefixId.'-checkinDate-'.$this->lConf['uidpid'].'"><b>'.htmlspecialchars($this->pi_getLL('feld_anreise')).'</b></label><br/>';
		if (isset($this->lConf['startDateStamp']))
			$startdate = $this->lConf['startDateStamp'];
		else
			$startdate = time();

		$content .= '<input class="'.$ErrorVacancies.' datepicker" id="'.$this->prefixId.'-checkinDate-'.$this->lConf['uidpid'].'" name="'.$this->prefixId.'[checkinDate]" type="text" value="'.date($this->lConf['dateFormat'],  $interval['startDate']).'"/>';

		$content .= '</div>';


		//~ $content .= '<label for="'.$this->prefixId.'[checkinDate]'.'_cb">&nbsp;</label><br/>';

		//~ $content .= tx_abbooking_div::getJSCalendarInput($this->prefixId.'[checkinDate]', $interval['startDate'], $ErrorVacancies);

		if (!$this->isRobot())
			$content .= '<input class="submit_dateSelect" type="submit" name="'.$this->prefixId.'[submit_button_CheckinOverview]" value="'.htmlspecialchars($this->pi_getLL('submit_button_label')).'">';
		$content .= '</form>
			<br />
		';

		$out = $content;

		$bookedPeriods = tx_abbooking_div::getBookings($uid, $interval);
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

		foreach ($bookedPeriods['bookings'] as $id => $row) {
			for ($d = $row['startdate']; $d <= $row['enddate']; $d=strtotime("+ 1 day", $d)) {

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

		}
		$out .= '</div>';
		$out .= '</div>';

		return $out;
	}

	/**
	 * Display the future bookings (including current)
	 *
	 * @param	integer		$uid: ...
	 * @param	array		$interval: ...
	 * @return	HTML-table		with calendar view
	 */
	function printFutureBookings($uid, $interval = array()) {

		$this->pi_loadLL();
		$myBooked = array();

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

		if (!$this->isRobot())
			$content .= '<input class="submit_dateSelect" type="submit" name="'.$this->prefixId.'[submit_button_CheckinOverview]" value="'.htmlspecialchars($this->pi_getLL('submit_button_label')).'">';
		$content .= '</form>
			<br />
		';

		$out = $content;

		$bookedPeriods = tx_abbooking_div::getBookings($uid, $this->lConf['PIDstorage'], $interval);

		$out .= '<ul>';
		foreach ($bookedPeriods['bookings'] as $id => $booking) {
			$out .= '<li>'.strftime('%a, %x', $booking['startdate']) . ' - ' . strftime('%a, %x', $booking['enddate']) . ': '. $booking['title'].'</li>';
		}
		$out .= '</ul>';

		return $out;
	}


	/**
	 * Display the availability calendar as single line for a given interval
	 *
	 * @param	integer		$uid: ...
	 * @param	array		$interval: ...
	 * @return	HTML-list		of calendar days
	 */
	function printAvailabilityCalendarDiv($uid, $interval, $months = 0, $cols = 1) {

		$product = $this->lConf['productDetails'][$this->lConf['AvailableProductIDs'][0]];

		// disable caching of target booking page
		$conf = array(
		  // Link to booking page
		  'parameter' => $this->lConf['gotoPID'],
		  // We must add cHash because we use parameters
		  'useCacheHash' => false,
		);

		// disable booking links for robots
		if ($this->isRobot())
			$this->lConf['enableBookingLink'] = 0;

		if (!isset($interval['startDate']) && !isset($interval['endDate'])) {
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

		if ($months == 0)
			$months = date('n', $interval['endList']) - date('n', $interval['startList']) +
				(date('Y', $interval['endList']) - date('Y', $interval['startList']) +1)*12;


		// only check prices if not yet available...
		if (!empty($this->lConf['productDetails'][$uid]['prices'])) {
//~ 			print_r("printAvailabilityCalendarDiv ++++ Prices already available \n");
			$prices = $this->lConf['productDetails'][$uid]['prices'];
		}
		else
			$prices = tx_abbooking_div::getPrices($uid, $interval);

		$bookedPeriods = tx_abbooking_div::getBookings($uid, $interval);
		$myBooked = tx_abbooking_div::cssClassBookedPeriods($bookedPeriods, $prices, $interval);

		if (empty($this->lConf['ProductID']) && empty($uid)) {
			$out = '<h2 class="setupErrors"><b>'.$this->pi_getLL('error_noProductSelected').'</b></h2>';
		}

		// date select form
		if ($this->lConf['showDateNavigator']) {
			$out .='<form action="'.$this->pi_getPageLink($GLOBALS['TSFE']->id).'" method="POST">
					<label for="'.$this->prefixId.'[checkinDate]'.'_cb">&nbsp;</label><br/>';

			//~ $out .= tx_abbooking_div::getJSCalendarInput($this->prefixId.'[checkinDate]', $interval['startDate'], $ErrorVacancies);

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
								$out .= '<li class="'.$myBooked[$fillDay].' DayNames">'.substr(strftime("%a", $fillDay), 0, 2).'</li>';
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
					$printDay = strftime("%d", $d);
				} else {
					$cssClass = $myBooked[$d];
					$printDay = strftime("%d", $d);
				}
				if ($this->lConf['showBookingRate'] && strstr($cssClass, 'booked') && !strstr($cssClass, 'booked End'))
					$bookingRate ++;

				if ($this->lConf['enableBookingLink'] && $d >= strtotime(strftime("%Y-%m-%d"))
						&& $d < strtotime('+ '.($this->lConf['numCheckNextMonths'] + 1).' months')
						&& (strstr($cssClass, 'vacant') || strstr($cssClass, 'End')) // only vacant
						&& (! strstr($cssClass, 'noPrices'))  && ($prices[$d]['checkInOk']=='1')
					) {
					// set default daySelector = 2 OR minimumStay for given day, adultSelector = 2
					$params_united = $d.'_'.max($this->lConf['daySelector'], $this->getMinimumStay($prices[$d]['minimumStay'], $d)).'_'.$uid.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor0';

					// create links with cHash...
					$conf['additionalParams'] = '&'.$this->prefixId.'[ABx]='.$params_united.'&'.$this->prefixId.'[abnocache]=1';
					$url = $this->cObj->typoLink($printDay, $conf);

					$out .= '<li class="'.$cssClass.'">'.$url.'</li>';
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

		$product = $this->lConf['productDetails'][$this->lConf['AvailableProductIDs'][0]];

		// disable caching of target booking page
		$conf = array(
		  // Link to booking page
		  'parameter' => $this->lConf['gotoPID'],
		  // We must add cHash because we use parameters
		  'useCacheHash' => false,
		);

		$this->pi_loadLL();

		// disable booking links for robots
		if ($this->isRobot())
			$this->lConf['enableBookingLink'] = 0;

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

		// only check prices if not yet available...
		if (!empty($this->lConf['productDetails'][$uid]['prices'])) {
			//~ print_r("printAvailabilityCalendarLine ++++ Prices already available \n");
			$prices = $this->lConf['productDetails'][$uid]['prices'];
		}
		else
			$prices = tx_abbooking_div::getPrices($uid, $interval);

		$bookedPeriods = tx_abbooking_div::getBookings($uid, $interval);
		$myBooked = tx_abbooking_div::cssClassBookedPeriods($bookedPeriods, $prices, $interval);

		$printDayNames = 1;
		$out = '<div class="availabilityCalendarLine">';
		for ($d = $interval['startList']; $d <= $interval['endList']; $d = strtotime('+1 day', $d)) {
			if (date(w, $d) == 1) // open div on monday
				$out .= '<div class="calendarWeek">';
			$out .= '<ul class="CalendarLine">';
			unset($cssClass);
			if ($d < $interval['startDate'] || $d > $interval['endDate'])
				$cssClass = 'transp';

			$cssClass .= $myBooked[$d];

			 // print only in first line
			if ($printDayNames == 1) {
				$out .= '<li class="'.$cssClass.' DayNames">'.substr(strftime("%a", $d), 0, 2).'</li>';
			}

			if ($this->lConf['enableBookingLink'] && $d >= strtotime(strftime("%Y-%m-%d"))
					&& $d < strtotime('+ '.($this->lConf['numCheckNextMonths'] + 1).' months')
					&& (strstr($cssClass, 'vacant') || strstr($cssClass, 'End')) // only vacant
					&& (! strstr($cssClass, 'noPrices'))  && ($prices[$d]['checkInOk']=='1')
				) {
				// set default daySelector = 2 OR minimumStay for given day, adultSelector = 2
				$params_united = $d.'_'.max($this->lConf['daySelector'], $this->getMinimumStay($prices[$d]['minimumStay'], $d)).'_'.$this->lConf['adultSelector'].'_'.$uid.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor0';

				$conf['additionalParams'] = '&'.$this->prefixId.'[ABx]='.$params_united.'&'.$this->prefixId.'[abnocache]=1';;
				$url = $this->cObj->typoLink(strftime("%d", $d), $conf);

				$out .= '<li class="'.$cssClass.'">'.$url.'</li>';
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

		// disable caching of target booking page
		$conf = array(
		  // Link to booking page
		  'parameter' => $this->lConf['gotoPID'],
		  // We must add cHash because we use parameters
		  'useCacheHash' => false,
		);

		$contentError = array();
		$offers['numOffers'] = 0;
		$i = 0;

		$productIds=explode(',', $this->lConf['ProductID']);

		foreach ( $productIds as $key => $uid ) {
			if (($this->lConf['productDetails'][$uid]['capacitymin']+$this->lConf['productDetails'][$uid]['capacitymax']) > 0)
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
				$bookNights = $product['maxAvailable'];
				if ($product['maxAvailable'] == 1)
					$text_periods = ' '.$this->pi_getLL('period');
				else
					$text_periods = ' '.$this->pi_getLL('periods');

				$contentError[] = sprintf($this->pi_getLL('error_vacancies_limited'), $product['maxAvailable'].' '.$text_periods);
			} else {
				$bookNights = $this->lConf['daySelector'];
			}

			if ($product['minimumStay'] > $this->lConf['daySelector']) {
				if ($product['minimumStay'] == 1)
					$text_periods = ' '.$this->pi_getLL('period');
				else
					$text_periods = ' '.$this->pi_getLL('periods');

				$contentError[] = sprintf($this->pi_getLL('error_minimumStay'), $product['minimumStay'].' '.$text_periods);
				if ($bookNights < $product['minimumStay'])
					$bookNights = $product['minimumStay'];
			}

			// check if checkIn is ok for startDate
			if ($product['prices'][$this->lConf['startDateStamp']]['checkInOk'] == '0') {
				$contentError[] = $this->pi_getLL('error_no_checkIn_on').' '.strftime('%a, %x', $this->lConf['startDateStamp']);
				$enableBookingLink = 0;
				for ($j=$this->lConf['startDateStamp']; $j < strtotime('+14 day', $this->lConf['startDateStamp']); $j=strtotime('+1 day', $j)) {

					if ($product['prices'][$j]['checkInOk'] == '1') {
						$interval['startDate'] = $j;

						if ($this->lConf['enableBookingLink']) {
							$params_united = $interval['startDate'].'_'.$bookNights.'_'.$this->lConf['adultSelector'].'_'.$product['uid'].$offTimeProducts.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor1';
							$conf['additionalParams'] = '&'.$this->prefixId.'[ABx]='.$params_united;
							$url = $this->cObj->typoLink(strftime('%a, %x', $interval['startDate']), $conf);
						}
						else
							$link = strftime('%a, %x', $j);

						$contentError[] = $this->pi_getLL('error_next_checkIn_on').' '.$link;
						break;
					}
				}
			} else
				$enableBookingLink = $this->lConf['enableBookingLink'];

			// show calendar list only up to the vacant day
			if (empty($interval['startDate']))
				$interval['startDate'] = $this->lConf['startDateStamp'];
			$interval['endDate'] = strtotime('+'.$bookNights.' day', $this->lConf['startDateStamp']);


			$params_united = $interval['startDate'].'_'.$bookNights.'_'.$this->lConf['adultSelector'].'_'.$product['uid'].$offTimeProducts.'_'.$this->lConf['uidpid'].'_'.$this->lConf['PIDbooking'].'_bor1';

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


			if ($enableBookingLink) {
				$conf['additionalParams'] = '&'.$this->prefixId.'[ABx]='.$params_united.'&'.$this->prefixId.'[abnocache]=1';
				$link = $this->cObj->typoLink($title, $conf);
			}
			else
				$link = $title;

			$linkBookNow = '';
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

				if ($enableBookingLink)
					$offers[$i] .='<form  class="requestForm" action="'.$this->pi_getPageLink($this->lConf['gotoPID']).'" method="POST">';

				$offers[$i] .= $this->printCalculatedRates($uid, $bookNights, 1, 1);

				if ($enableBookingLink)
					$linkBookNow = '<input type="hidden" name="'.$this->prefixId.'[ABx]" value="'.$params_united.'">
									<input type="hidden" name="'.$this->prefixId.'[abnocache]" value="1">
									<input type="hidden" name="'.$this->prefixId.'[ABwhatToDisplay]" value="BOOKING"><br/>
									<input class="submit" type="submit" name="'.$this->prefixId.'[submit_button]" value="'.htmlspecialchars($this->pi_getLL('bookNow')).'">
									</form>
					';

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
				$offers[$i] .= tx_abbooking_div::printAvailabilityCalendarDiv($product['uid'].$offTimeProducts,  $intval, $this->lConf['form']['showCalendarMonth'], 0);

			} else if ($this->lConf['form']['showCalendarWeek']>0) {
					$intval['startDate'] = $interval['startDate'];
				$intval['endDate'] = strtotime('+'.$this->lConf['form']['showCalendarWeek'].' weeks', $intval['startDate']);
				$offers[$i] .= tx_abbooking_div::printAvailabilityCalendarLine($product['uid'].$offTimeProducts, $intval);
			} else
				$offers[$i] .= tx_abbooking_div::printAvailabilityCalendarLine($product['uid'].$offTimeProducts, $interval);

			if ($enableBookingLink)
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

		$dateFormat = str_replace(array('d', 'm', 'y', 'Y'), array('%d', '%m', '%y', '%Y'), $this->lConf['dateFormat']);

		if (class_exists('JSCalendar')) {
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
		} else
			$out .= '<input '.$errorClass.' type="text" class="datepicker" name="'.$name.'" id="'.$name.'" value="'.strftime($dateFormat, $value).'" ><br/>';

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
