<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_abbooking_booking'] = array (
	'ctrl' => $TCA['tx_abbooking_booking']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,startdate,enddate,title,productid,editcode'
	),
	'feInterface' => $TCA['tx_abbooking_booking']['feInterface'],
	'columns' => array (
		'hidden' => array (		## WOP:[tables][1][add_hidden]
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'startdate' => array (		## WOP:[tables][1][fields][1][fieldname]
			'exclude' => 0,		## WOP:[tables][1][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_booking.startdate',		## WOP:[tables][1][fields][1][title]
			'config' => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0'
			)
		),
		'enddate' => array (		## WOP:[tables][1][fields][2][fieldname]
			'exclude' => 0,		## WOP:[tables][1][fields][2][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_booking.enddate',		## WOP:[tables][1][fields][2][title]
			'config' => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0'
			)
		),
		'title' => array (		## WOP:[tables][1][fields][3][fieldname]
			'exclude' => 0,		## WOP:[tables][1][fields][3][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_booking.title',		## WOP:[tables][1][fields][3][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][1][fields][3][type]
				'size' => '30',	## WOP:[tables][1][fields][3][conf_size]
			)
		),
		'productid' => array (        ## WOP:[tables][1][fields][4][fieldname]
			'exclude' => 0,        ## WOP:[tables][1][fields][4][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_booking.productid',        ## WOP:[tables][1][fields][4][title]
			'config' => array (
				'type' => 'select',    ## WOP:[tables][1][fields][4][conf_rel_type]
				'internal_type' => 'db',    ## WOP:[tables][1][fields][4][conf_rel_type]
				'allowed' => 'tx_abbooking_product',    ## WOP:[tables][1][fields][4][conf_rel_table]
				'size' => 3,    ## WOP:[tables][1][fields][4][conf_relations_selsize]
				'minitems' => 1,
				'maxitems' => 10,    ## WOP:[tables][1][fields][4][conf_relations]
				"MM" => "tx_abbooking_booking_productid_mm",    ## WOP:[tables][1][fields][4][conf_relations_mm]
				'foreign_table' => 'tx_abbooking_product',
			)
		),
		'editcode' => array (		## WOP:[tables][1][fields][5][fieldname]
			'exclude' => 0,		## WOP:[tables][1][fields][5][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_booking.editcode',		## WOP:[tables][1][fields][5][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][1][fields][5][type]
				'size' => '30',	## WOP:[tables][1][fields][5][conf_size]
			)
		),
		'request' => array (		## WOP:[tables][1][add_hidden]
			'exclude' => 1,
			'label'   => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_booking.request',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, startdate, enddate, title;;;;2-2-2, productid;;;;3-3-3, editcode')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);



$TCA['tx_abbooking_product'] = array (
	'ctrl' => $TCA['tx_abbooking_product']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,hidden,starttime,endtime,title,tstitle,capacitymin,capacitymax,filterprices,priceid,uiddetails',
		'always_description' => 1
	),
	'feInterface' => $TCA['tx_abbooking_product']['feInterface'],
	'columns' => array (
		'sys_language_uid' => array (		## WOP:[tables][2][localization]
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array (		## WOP:[tables][2][localization]
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_abbooking_product',
				'foreign_table_where' => 'AND tx_abbooking_product.pid=###CURRENT_PID### AND tx_abbooking_product.sys_language_uid IN (-1,0)',
			)
		),
		'l10n_diffsource' => array (		## WOP:[tables][2][localization]
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		## WOP:[tables][2][add_hidden]
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'starttime' => array (		## WOP:[tables][2][add_starttime]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array (		## WOP:[tables][2][add_endtime]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
			)
		),
		'title' => array (		## WOP:[tables][2][fields][1][fieldname]
			'exclude' => 0,		## WOP:[tables][2][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_product.title',		## WOP:[tables][2][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][1][type]
				'size' => '30',	## WOP:[tables][2][fields][1][conf_size]
			)
		),
		'tstitle' => array (		## WOP:[tables][2][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_product.tstitle',		## WOP:[tables][2][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][1][type]
				'size' => '30',	## WOP:[tables][2][fields][1][conf_size]
				'eval' => 'nospace',
			)
		),
		'capacitymin' => array (		## WOP:[tables][2][fields][2][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][2][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_product.capacitymin',		## WOP:[tables][2][fields][2][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][2][type]
				'size' => '30',	## WOP:[tables][2][fields][2][conf_size]
				'range' => array ('lower'=>0,'upper'=>1000),	## WOP:[tables][2][fields][2][conf_eval] = int+ results in a range setting
				'eval' => 'int,nospace',	## WOP:[tables][2][fields][2][conf_eval] / [tables][2][fields][2][conf_stripspace]
			)
		),
		'capacitymax' => array (		## WOP:[tables][2][fields][3][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][3][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_product.capacitymax',		## WOP:[tables][2][fields][3][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][3][type]
				'size' => '30',	## WOP:[tables][2][fields][3][conf_size]
				'range' => array ('lower'=>0,'upper'=>1000),	## WOP:[tables][2][fields][3][conf_eval] = int+ results in a range setting
				'eval' => 'int,nospace',	## WOP:[tables][2][fields][3][conf_eval] / [tables][2][fields][3][conf_stripspace]
			)
		),
		'filterprice' => array (		## WOP:[tables][2][fields][3][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][3][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_product.filterprice',		## WOP:[tables][2][fields][3][title]

			'description' => 'title hallo',
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][3][type]
				'size' => '30',	## WOP:[tables][2][fields][3][conf_size]
			)
		),
		'priceid' => array (		## WOP:[tables][2][fields][4][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][4][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_product.priceid',		## WOP:[tables][2][fields][4][title]
			'config' => array (
				'type' => 'select',	## WOP:[tables][2][fields][4][conf_rel_type]
				'internal_type' => 'db',	## WOP:[tables][2][fields][4][conf_rel_type]
				'allowed' => 'tx_abbooking_price',	## WOP:[tables][2][fields][4][conf_rel_table]
				'size' => 5,	## WOP:[tables][2][fields][4][conf_relations_selsize]
				'minitems' => 0,
				'maxitems' => 10,	## WOP:[tables][2][fields][4][conf_relations]
				'foreign_table' => 'tx_abbooking_price',
				# quite stupid syntax introduced in TYPO3 4.4.1 :-(
				'foreign_table_where' => 'AND tx_abbooking_price.title like "%"\'###REC_FIELD_filterprice###\'"%" ORDER BY title ASC',
			)
		),
		'uiddetails' => array (        ## WOP:[tables][2][fields][5][fieldname]
			'exclude' => 0,        ## WOP:[tables][2][fields][5][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_product.uiddetails',        ## WOP:[tables][2][fields][5][title]
			'config' => array (
				'type'     => 'input',
				'size'     => '15',
				'max'      => '255',
				'checkbox' => '',
				'eval'     => 'trim',
				'wizards'  => array(
				'_PADDING' => 2,
				'link'     => array(
					'type'         => 'popup',
					'title'        => 'Link',
					'icon'         => 'link_popup.gif',
					'script'       => 'browse_links.php?mode=wizard',
					'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
				)
				)
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, title;;;;2-2-2, tstitle, capacitymin;;;;3-3-3, capacitymax, filterprice, priceid, uiddetails')
	),
	'palettes' => array (
		'1' => array('showitem' => 'starttime, endtime')
	)
);



$TCA['tx_abbooking_price'] = array (
	'ctrl' => $TCA['tx_abbooking_price']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,title,hidden,adult1,adult2,adult3,adult4,adultX,child,teen,extraComponent1,extraComponent2,discount,discountPeriod, singleComponent1, singleComponent2, minimumStay, blockDaysAfterBooking, checkInWeekdays, seasonid',
		'always_description' => 1
	),
	'feInterface' => $TCA['tx_abbooking_price']['feInterface'],
	'columns' => array (
		'sys_language_uid' => array (		## WOP:[tables][2][localization]
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array (		## WOP:[tables][2][localization]
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_abbooking_price',
				'foreign_table_where' => 'AND tx_abbooking_price.pid=###CURRENT_PID### AND tx_abbooking_price.sys_language_uid IN (-1,0)',
			)
		),
		'l10n_diffsource' => array (		## WOP:[tables][2][localization]
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		## WOP:[tables][3][add_hidden]
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (		## WOP:[tables][2][fields][1][fieldname]
			'exclude' => 0,		## WOP:[tables][2][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.title',		## WOP:[tables][2][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][1][type]
				'size' => '30',	## WOP:[tables][2][fields][1][conf_size]
			)
		),
		'currency' => array (		## WOP:[tables][2][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.currency',		## WOP:[tables][2][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][1][type]
				'size' => '10',	## WOP:[tables][2][fields][1][conf_size]
				'default' => '€',
			)
		),
		'adult1' => array (		## WOP:[tables][3][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.adult1',		## WOP:[tables][3][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][1][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
 				'eval' => 'nospace',	## WOP:[tables][3][fields][1][conf_eval] / [tables][3][fields][1][conf_stripspace]
			)
		),
		'adult2' => array (		## WOP:[tables][3][fields][2][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][2][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.adult2',		## WOP:[tables][3][fields][2][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][2][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'adult3' => array (		## WOP:[tables][3][fields][3][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][3][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.adult3',		## WOP:[tables][3][fields][3][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][3][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'adult4' => array (		## WOP:[tables][3][fields][4][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][4][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.adult4',		## WOP:[tables][3][fields][4][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][4][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'adultX' => array (		## WOP:[tables][3][fields][4][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][4][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.adultX',		## WOP:[tables][3][fields][4][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][4][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'child' => array (		## WOP:[tables][3][fields][5][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][5][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.child',		## WOP:[tables][3][fields][5][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][5][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'teen' => array (		## WOP:[tables][3][fields][6][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][6][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.teen',		## WOP:[tables][3][fields][6][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][6][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'extraComponent1' => array (		## WOP:[tables][2][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.extraComponent1',		## WOP:[tables][2][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][4][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'extraComponent2' => array (		## WOP:[tables][2][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.extraComponent2',		## WOP:[tables][2][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][4][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'discount' => array (		## WOP:[tables][3][fields][6][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][6][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.discount',		## WOP:[tables][3][fields][6][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][6][type]
				'size' => '10',	## WOP:[tables][3][fields][6][conf_size]
				'max' => '6',	## WOP:[tables][3][fields][6][conf_max]
				'eval' => 'double2,nospace',	## WOP:[tables][3][fields][6][conf_eval] / [tables][3][fields][6][conf_stripspace]
			)
		),
		'discountPeriod' => array (		## WOP:[tables][3][fields][6][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][6][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.discountPeriod',		## WOP:[tables][3][fields][6][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][3][type]
				'size' => '10',	## WOP:[tables][2][fields][3][conf_size]
				'range' => array ('lower'=>1,'upper'=>100),	## WOP:[tables][2][fields][3][conf_eval] = int+ results in a range setting
				'eval' => 'int,nospace',	## WOP:[tables][2][fields][3][conf_eval] / [tables][2][fields][3][conf_stripspace]
			)
		),
		'singleComponent1' => array (		## WOP:[tables][2][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.singleComponent1',		## WOP:[tables][2][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][4][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'singleComponent2' => array (		## WOP:[tables][2][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][2][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.singleComponent2',		## WOP:[tables][2][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][4][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'minimumStay' => array (		## WOP:[tables][3][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.minimumStay',		## WOP:[tables][3][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][4][type]
				'size' => '20',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '40',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'nospace',	## WOP:[tables][3][fields][2][conf_eval] / [tables][3][fields][2][conf_stripspace]
			)
		),
		'blockDaysAfterBooking' => array (		## WOP:[tables][3][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.blockDaysAfterBooking',		## WOP:[tables][3][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][3][fields][1][type]
				'size' => '10',	## WOP:[tables][3][fields][1][conf_size]
				'max' => '31',	## WOP:[tables][3][fields][1][conf_max]
				'eval' => 'int,nospace',	## WOP:[tables][3][fields][1][conf_eval] / [tables][3][fields][1][conf_stripspace]
			)
		),
		'checkInWeekdays' => array (		## WOP:[tables][3][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,		## WOP:[tables][3][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.checkInWeekdays',		## WOP:[tables][3][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][1][type]
				'size' => '10',	## WOP:[tables][2][fields][1][conf_size]
				'default' => '*',
			)
		),
		
		'seasonid' => array (        ## WOP:[tables][4][fields][1][fieldname]
			'displayCond' => 'FIELD:sys_language_uid:<:1',
			'exclude' => 0,        ## WOP:[tables][4][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price.seasonid',        ## WOP:[tables][4][fields][1][title]
			'config' => array (
				'type' => 'select',    ## WOP:[tables][4][fields][1][conf_rel_type]
				'internal_type' => 'db',    ## WOP:[tables][4][fields][1][conf_rel_type]
				'allowed' => 'tx_abbooking_seasons',    ## WOP:[tables][4][fields][1][conf_rel_table]
				'size' => 5,    ## WOP:[tables][4][fields][1][conf_relations_selsize]
				'minitems' => 0,
				'maxitems' => 100,    ## WOP:[tables][4][fields][1][conf_relations]
				"MM" => "tx_abbooking_seasons_priceid_mm",    ## WOP:[tables][4][fields][1][conf_relations_mm]
				'foreign_table' => 'tx_abbooking_seasons',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, title, hidden;;1;;1-1-1, currency, adult1, adult2, adult3, adult4, adultX, child, teen, extraComponent1, extraComponent2, discount, discountPeriod, singleComponent1, singleComponent2, minimumStay, blockDaysAfterBooking, checkInWeekdays, seasonid')
	),
);
$TCA['tx_abbooking_seasons'] = array (
    'ctrl' => $TCA['tx_abbooking_seasons']['ctrl'],
    'interface' => array (
        'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,title,hidden,starttime,endtime'
    ),
    'feInterface' => $TCA['tx_abbooking_seasons']['feInterface'],
    'columns' => array (
        'sys_language_uid' => array (        ## WOP:[tables][4][localization]
            'exclude' => 1,
            'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
            'config' => array (
                'type'                => 'select',
                'foreign_table'       => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
                    array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
                )
            )
        ),
        'l10n_parent' => array (        ## WOP:[tables][4][localization]
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude'     => 1,
            'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
            'config'      => array (
                'type'  => 'select',
                'items' => array (
                    array('', 0),
                ),
                'foreign_table'       => 'tx_abbooking_seasons',
                'foreign_table_where' => 'AND tx_abbooking_seasons.pid=###CURRENT_PID### AND tx_abbooking_seasons.sys_language_uid IN (-1,0)',
            )
        ),
        'l10n_diffsource' => array (        ## WOP:[tables][4][localization]
            'config' => array (
                'type' => 'passthrough'
            )
        ),
		'title' => array (		## WOP:[tables][2][fields][1][fieldname]
			'exclude' => 0,		## WOP:[tables][2][fields][1][excludeField]
			'label' => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_seasons.title',		## WOP:[tables][2][fields][1][title]
			'config' => array (
				'type' => 'input',	## WOP:[tables][2][fields][1][type]
				'size' => '30',	## WOP:[tables][2][fields][1][conf_size]
			)
		),
        'hidden' => array (        ## WOP:[tables][4][add_hidden]
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
        'starttime' => array (        ## WOP:[tables][4][add_starttime]
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
            'config'  => array (
                'type'     => 'input',
                'size'     => '8',
                'max'      => '20',
                'eval'     => 'date',
                'default'  => '0',
                'checkbox' => '0'
            )
        ),
        'endtime' => array (        ## WOP:[tables][4][add_endtime]
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
            'config'  => array (
                'type'     => 'input',
                'size'     => '8',
                'max'      => '20',
                'eval'     => 'date',
                'checkbox' => '0',
                'default'  => '0',
            )
        ),
    ),
    'types' => array (
        '0' => array('showitem' => 'title, sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1')
    ),
    'palettes' => array (
        '1' => array('showitem' => 'starttime, endtime')
    )
);
?>
