<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Alexander Bigga <linux@bigga.de>
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
 * This class returns current bookings as fullcalendar events
 *
 * @author	Alexander Bigga <linux@bigga.de>
 * @package	TYPO3
 * @subpackage	tx_abbooking
 */
class tx_abbooking_eid {

	/**
	 * Main function of the class. Outputs sitemap.
	 *
	 * @return	void
	 */
	public function main() {

		$GLOBALS['TSFE']->fe_user = tslib_eidtools::initFeUser();

		$fegroup = $_REQUEST['fegroup'];
		$storagePid = $_REQUEST['storagePid'];
//~ print_r($_REQUEST);
		if (!in_array($fegroup, explode(',', $GLOBALS['TSFE']->fe_user->user['usergroup'])))
			return;

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->updateBookingDetails($storagePid);
			return;
		}

		$start = $_GET['start'];
		$stop = $_GET['end'];
		$uids = $_GET['uids'];

		tslib_eidtools::connectDB(); //Connect to database

		$interval['startList'] = $start;
		$interval['endList'] = $stop;
		$bookedPeriods = $this->getBookings($storagePid, $uids, $interval);
		$jsonevent = array();

		foreach ($bookedPeriods as $booking) {

			$foundevent = array();

			$foundevent['id'] = $booking['uid'];
			$titleinfo = explode(',', $booking['title']);
			$foundevent['title'] = trim($titleinfo[1]);

			$foundevent['description'] .= '<form id = "submitForm' . $booking['uid'] . '" action="?eID=ab_booking" method="POST">';
			$bookingDetails = $this->getBookingDetails($storagePid, $booking['uid']);
			foreach ($bookingDetails as $key => $detail) {
				switch ($key) {
					case 'message': $foundevent['description'] .= $key.' = <textarea type="text" cols="60" rows="5" name="'.$key.'">'.$detail.'</textarea>' . "<br />";
						break;
					case 'checkinDate': $foundevent['description'] .= $key.' = <input class="'.$cssError.' datepicker" name="'.$key.'" type="'.$input.'"  value="'.strftime('%x', $booking['startdate']).'"  /> <br /> ';
						break;
					case 'daySelector': $foundevent['description'] .= $key.' = <input class="'.$cssError.' datepicker" name="'.$key.'" type="'.$input.'"  value="'.strftime('%x', $booking['enddate']).'"  /> <br /> ';
						break;
					default:		$foundevent['description'] .= $key.' = <input type="text" name="'.$key.'" value="'.$detail.'" />' . "<br />";
				}
			}

			$foundevent['description'] .= '<input type="hidden" name="uid" value="'.$booking['uid'].'">';
			$foundevent['description'] .= '<input type="hidden" name="fegroup" value="'.$fegroup.'">';
			$foundevent['description'] .= '<input type="hidden" name="storagePid" value="'.$storagePid.'">';
			$foundevent['description'] .= '<input type="submit">';

			$foundevent['description'] .= '
			<script>
			$("#submitForm' . $booking['uid'].'").submit(function() {
				var url = "?eID=ab_booking"
				$.ajax({
					   type: "POST",
					   url: url,
					   data: $("#submitForm' . $booking['uid'].'").serialize(),
					   success: function(dd) {
							alert(dd);
							parent.$.fancybox.close();
					   }
					 });

				return false;
			});
			</script>
			';
			$foundevent['description'] .= '</form>';
			//~ $foundevent['description'] = trim($titleinfo[1]) . "\n" . trim($titleinfo[2])
			//~ . "\n" . trim($titleinfo[3])
			//~ . "\n" . trim($titleinfo[4])
			//~ . "\n" . trim($titleinfo[5])
			//~ ;
			$foundevent['start'] = $booking['startdate'];
			$foundevent['className'] = 'category-' . $booking['cat'];
			$foundevent['end'] = $booking['enddate'] -10;

			$foundevent['allDay'] = true;

			$jsonevent[] = $foundevent;

		}
		print json_encode($jsonevent);
	}

	/**
	 * Get Booked Periods for an Interval
	 *
	 * @param	string		$uid
	 * @param	string		$storagePid: ...
	 * @param	array		$interval: ...
	 * @return	array		with booking periods
	 */
	function getBookings($storagePid, $uid, $interval) {

		$bookingsRaw = array();

		if ($storagePid != '' && $uid != '') {
			// SELECT
			// 1. get for bookings for these uids/pids
			$query  = 'pid IN ('. $storagePid .') AND uid_foreign IN ('.$uid.')';
			$query .= ' AND deleted=0 AND hidden=0 AND uid=uid_local';
			$query .= ' AND ( enddate >=('.$interval['startList'].') AND startdate <=('.$interval['endList'].'))';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT uid_foreign as cat, uid_local as uid, startdate, enddate, title','tx_abbooking_booking, tx_abbooking_booking_productid_mm',$query,'','startdate','');
			// one array for start and end dates. one for each pid
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$bookingsRaw[] = $row;
			};

		}

		$localbookings = $bookingsRaw;

 		return $localbookings;

	}

	/**
	 * Get Booked Periods for an Interval
	 *
	 * @param	string		$uid
	 * @param	string		$storagePid: ...
	 * @param	array		$interval: ...
	 * @return	array		with booking periods
	 */
	function updateBookingDetails($storagePid) {

		$uid = $_REQUEST['uid'];
		echo $storagePid . '/' . $uid;
		return;
		$bookingsRaw = array();

		if ($storagePid != '' && $uid != '') {
			// SELECT
			// 1. get for bookings for these uids/pids
			$query  = 'pid IN ('. $storagePid .') AND uid_foreign IN ('.$uid.')';
			$query .= ' AND deleted=0 AND hidden=0 AND uid=uid_local';
			$query .= ' AND ( enddate >=('.$interval['startList'].') AND startdate <=('.$interval['endList'].'))';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT uid_foreign as cat, uid_local as uid, startdate, enddate, title','tx_abbooking_booking, tx_abbooking_booking_productid_mm',$query,'','startdate','');
			// one array for start and end dates. one for each pid
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$bookingsRaw[] = $row;
			};

		}

		$localbookings = $bookingsRaw;

 		return $localbookings;

	}

	/**
	 * Get Booked Periods for an Interval
	 *
	 * @param	string		$uid
	 * @param	string		$storagePid: ...
	 * @return	array		with booking periods
	 */
	function getBookingDetails($storagePid, $uid) {

		$bookingDetails = array();

		if ($storagePid != '' && $uid != '') {
			// SELECT
			// 1. get for bookings for these uids/pids
			$query  = 'pid = '. $storagePid .' AND booking_id = '.$uid.' ';
			//~ $query .= ' AND deleted=0 AND hidden=0';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('meta_key, meta_value','tx_abbooking_booking_meta',$query,'','','');
			// one array for start and end dates. one for each pid
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$bookingDetails[$row['meta_key']] = $row['meta_value'];
			};

		}

 		return $bookingDetails;

	}

}

$generator = t3lib_div::makeInstance('tx_abbooking_eid');

$generator->main();

?>
