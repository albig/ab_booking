<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

## WOP:[tables][1][allow_on_pages]
t3lib_extMgm::allowTableOnStandardPages('tx_abbooking_booking');

$TCA['tx_abbooking_booking'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_booking',		## WOP:[tables][1][title]
		'label'     => 'startdate',	## WOP:[tables][1][header_field]
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY startdate DESC',	## WOP:[tables][1][sorting] / [tables][1][sorting_field] / [tables][1][sorting_desc]
		'delete' => 'deleted',	## WOP:[tables][1][add_deleted]
		'enablecolumns' => array (		## WOP:[tables][1][add_hidden] / [tables][1][add_starttime] / [tables][1][add_endtime] / [tables][1][add_access]
			'disabled' => 'hidden',	## WOP:[tables][1][add_hidden]
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_abbooking_booking.gif',
	),
);


## WOP:[tables][2][allow_on_pages]
t3lib_extMgm::allowTableOnStandardPages('tx_abbooking_product');


## WOP:[tables][2][allow_ce_insert_records]
t3lib_extMgm::addToInsertRecords('tx_abbooking_product');

$TCA['tx_abbooking_product'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_product',		## WOP:[tables][2][title]
		'label'     => 'title',	## WOP:[tables][2][header_field]
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField'            => 'sys_language_uid',	## WOP:[tables][2][localization]
		'transOrigPointerField'    => 'l10n_parent',	## WOP:[tables][2][localization]
		'transOrigDiffSourceField' => 'l10n_diffsource',	## WOP:[tables][2][localization]
		'default_sortby' => 'ORDER BY title',	## WOP:[tables][2][sorting] / [tables][2][sorting_field] / [tables][2][sorting_desc]
		'delete' => 'deleted',	## WOP:[tables][2][add_deleted]
		'enablecolumns' => array (		## WOP:[tables][2][add_hidden] / [tables][2][add_starttime] / [tables][2][add_endtime] / [tables][2][add_access]
			'disabled' => 'hidden',	## WOP:[tables][2][add_hidden]
			'starttime' => 'starttime',	## WOP:[tables][2][add_starttime]
			'endtime' => 'endtime',	## WOP:[tables][2][add_endtime]
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_abbooking_product.gif',
	),
);


## WOP:[tables][3][allow_on_pages]
t3lib_extMgm::allowTableOnStandardPages('tx_abbooking_price');


## WOP:[tables][3][allow_ce_insert_records]
t3lib_extMgm::addToInsertRecords('tx_abbooking_price');

$TCA['tx_abbooking_price'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_price',		## WOP:[tables][3][title]
		'label'     => 'title',	## WOP:[tables][3][header_field]
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField'            => 'sys_language_uid',	## WOP:[tables][2][localization]
		'transOrigPointerField'    => 'l10n_parent',	## WOP:[tables][2][localization]
		'transOrigDiffSourceField' => 'l10n_diffsource',	## WOP:[tables][2][localization]
		'default_sortby' => 'ORDER BY adult1',	## WOP:[tables][3][sorting] / [tables][3][sorting_field] / [tables][3][sorting_desc]
		'delete' => 'deleted',	## WOP:[tables][3][add_deleted]
		'enablecolumns' => array (		## WOP:[tables][3][add_hidden] / [tables][3][add_starttime] / [tables][3][add_endtime] / [tables][3][add_access]
			'disabled' => 'hidden',	## WOP:[tables][3][add_hidden]
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_abbooking_price.png',
	),
);

## WOP:[tables][4][allow_on_pages]
t3lib_extMgm::allowTableOnStandardPages('tx_abbooking_seasons');


## WOP:[tables][4][allow_ce_insert_records]
t3lib_extMgm::addToInsertRecords('tx_abbooking_seasons');

$TCA['tx_abbooking_seasons'] = array (
    'ctrl' => array (
        'title'     => 'LLL:EXT:ab_booking/locallang_db.xml:tx_abbooking_seasons',        ## WOP:[tables][4][title]
		'label'     => 'title',	## WOP:[tables][3][header_field]
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField'            => 'sys_language_uid',    ## WOP:[tables][4][localization]
        'transOrigPointerField'    => 'l10n_parent',    ## WOP:[tables][4][localization]
        'transOrigDiffSourceField' => 'l10n_diffsource',    ## WOP:[tables][4][localization]
        'sortby' => 'sorting',    ## WOP:[tables][4][sorting]
        'delete' => 'deleted',    ## WOP:[tables][4][add_deleted]
        'enablecolumns' => array (        ## WOP:[tables][4][add_hidden] / [tables][4][add_starttime] / [tables][4][add_endtime] / [tables][4][add_access]
            'disabled' => 'hidden',    ## WOP:[tables][4][add_hidden]
            'starttime' => 'starttime',    ## WOP:[tables][4][add_starttime]
            'endtime' => 'endtime',    ## WOP:[tables][4][add_endtime]
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_abbooking_seasons.png',
    ),
);

## WOP:[pi][1][addType]
t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


## WOP:[pi][1][addType]
t3lib_extMgm::addPlugin(array(
	'LLL:EXT:ab_booking/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
),'list_type');

//---------------------------------
// flexform
//---------------------------------
// remove starting point (pages) and recursive from flexform
// $TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';                  // new!
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY.'/flexform_ds.xml');            // new!

t3lib_extMgm::addStaticFile($_EXTKEY,'static/','Default CSS ab_booking');

include_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_abbooking_remote.php');

//t3lib_extMgm::addLLrefForTCAdescr('tx_abbooking_product','EXT:'.$_EXTKEY.'/locallang_csh_product.xml');

// include userfunc
// add CSH (context sensitive help) to TYPO3 >= 4.5
t3lib_extMgm::addLLrefForTCAdescr('tt_content.pi_flexform.ab_booking_pi1.list', 'EXT:'.$_EXTKEY.'/locallang_csh.xml');
?>
