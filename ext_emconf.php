<?php

########################################################################
# Extension Manager/Repository config file for ext "ab_booking".
#
# Auto generated 06-06-2011 22:25
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Simple booking extension',
	'description' => 'An availability calendar is presentable for each item/room. You may check the date and enter your details for booking.
Another view is an availability check.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.4.4',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Alexander Bigga',
	'author_email' => 'linux@bigga.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.4.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'date2cal' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:33:{s:9:"ChangeLog";s:4:"a57f";s:10:"README.txt";s:4:"ee2d";s:21:"ext_conf_template.txt";s:4:"b12e";s:12:"ext_icon.gif";s:4:"787f";s:17:"ext_localconf.php";s:4:"38ec";s:14:"ext_tables.php";s:4:"bb8e";s:14:"ext_tables.sql";s:4:"df2a";s:15:"flexform_ds.xml";s:4:"b347";s:20:"flexform_ds.xml.orig";s:4:"571a";s:19:"flexform_ds.xml.rej";s:4:"ab89";s:29:"icon_tx_abbooking_booking.gif";s:4:"475a";s:26:"icon_tx_abbooking_item.gif";s:4:"d00b";s:27:"icon_tx_abbooking_price.png";s:4:"2d34";s:27:"icon_tx_abbooking_price.xcf";s:4:"1c86";s:29:"icon_tx_abbooking_product.gif";s:4:"475a";s:29:"icon_tx_abbooking_seasons.png";s:4:"9698";s:29:"icon_tx_abbooking_seasons.xcf";s:4:"ba5b";s:25:"locallang_csh_product.xml";s:4:"3089";s:16:"locallang_db.xml";s:4:"88d5";s:17:"locallang_tca.xml";s:4:"83b3";s:22:"locallang_tca.xml.orig";s:4:"b8bb";s:21:"locallang_tca.xml.rej";s:4:"e70e";s:7:"tca.php";s:4:"d347";s:14:"doc/manual.sxw";s:4:"99d9";s:19:"doc/wizard_form.dat";s:4:"112b";s:20:"doc/wizard_form.html";s:4:"4f72";s:30:"lib/class.tx_abbooking_div.php";s:4:"331d";s:33:"lib/class.tx_abbooking_remote.php";s:4:"7fab";s:30:"pi1/class.tx_abbooking_pi1.php";s:4:"b45b";s:17:"pi1/locallang.xml";s:4:"3199";s:18:"res/cssBooking.css";s:4:"9559";s:20:"static/constants.txt";s:4:"cd51";s:16:"static/setup.txt";s:4:"2f65";}',
	'suggests' => array(
	),
);

?>