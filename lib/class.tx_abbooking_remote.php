<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Alexander Bigga <linux@bigga.de>
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
 *   38: class tx_abbooking_remote
 *   49:     function getAllProducts($pidList)
 *   77:     function flexFormListProductIDs($config)
 *  117:     function flexFormListRemoteProductIDs($config)
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
class tx_abbooking_remote  {
	var $prefixId      = 'tx_abbooking_remote';		// Same as class name
	var $scriptRelPath = 'lib/class.tx_abbooking_remote.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'ab_booking';	// The extension key.

	/**
	 * Get List of all Products listed
	 *
	 * @param	[type]		$pidList: ...
	 * @return	array		with product name and uid
	 */
	function getAllProducts($pidList) {
		$allProductIDs['products'] = array();

		// SELECT:
		/* get a list of all products */
		$myquery='pid IN ('. $pidList .') AND deleted=0 AND hidden=0';


		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title','tx_abbooking_product', $myquery,'','uid','');

		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			array_push($allProductIDs['products'], $row);
		}

		return $allProductIDs;
	}

	/**
	 * list local product IDs in flexform
	 * This function is called twice. Once for the already selected items on
	 * the left and once for the items to be selected on the right.
	 * Don't know why, but TYPO3 changes the content of the flexform data
	 * between these two calls. That's why it's difficult to get the storage
	 * PID out of it.
	 *
	 * @param	array		$config: array with parameters
	 * @return	the		modified config array
	 */
	function flexFormListProductIDs($config) {
		$optionList = array();

		$flexToolObj = t3lib_div::makeInstance('t3lib_flexformtools');

		$TSconfig = t3lib_befunc::getTCEFORM_TSconfig($config['table'],$config['row']);

		$piFlexForm = t3lib_div::xml2array($config['row']['pi_flexform']);

		$this->lConf['PIDstorage']=$flexToolObj->getArrayValueByPath('data/sheetGeneralOptions/lDEF/PIDstorage/vDEF', $piFlexForm);

		if ($this->lConf['PIDstorage']!= '') {
			if ($config['config']['form_type'] == 'select') {
				$pidStorage=substr($this->lConf['PIDstorage'],strpos($this->lConf['PIDstorage'],'_')+1,strlen($this->lConf['PIDstorage']));
				$pidStorage=substr($pidStorage,0,strpos($pidStorage,'|'));
			}
			else
				$pidStorage = $this->lConf['PIDstorage'];
		}
		else
			$pidStorage = intval($TSconfig['_STORAGE_PID']);

		$allProductIDs = $this->getAllProducts($pidStorage);

		// add first option
		foreach ($allProductIDs['products'] as $key => $val) {
				array_push($optionList, array(0 => $val['title'], 1 => $val['uid']));
		}
		$config['items'] = array_merge($config['items'],$optionList);

		return $config;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/lib/class.tx_abbooking_remote.php']) {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ab_booking/lib/class.tx_abbooking_remote.php']);
}

?>
