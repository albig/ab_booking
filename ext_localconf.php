<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_abbooking_booking=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_abbooking_product=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_abbooking_price=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_abbooking_seasons=1
');
## WOP:[pi][1][addType]
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_abbooking_pi1.php', '_pi1', 'list_type', 1);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] = 'EXT:' . $_EXTKEY. '/hooks/class.hook_flexform.php:tx_abbooking_Hook_Flexform';
?>
