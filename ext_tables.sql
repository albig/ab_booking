#
# Table structure for table 'tx_abbooking_booking_productid_mm'
# ## WOP:[tables][1][fields][4][conf_relations_mm]
#
CREATE TABLE tx_abbooking_booking_productid_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_abbooking_booking'
#
CREATE TABLE tx_abbooking_booking (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	startdate int(11) DEFAULT '0' NOT NULL,
	enddate int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	productid int(11) DEFAULT '0' NOT NULL,
	editcode tinytext,
	request tinyint(4) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_abbooking_product'
#
CREATE TABLE tx_abbooking_product (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	title tinytext,
	capacitymin int(11) DEFAULT '0' NOT NULL,
	capacitymax int(11) DEFAULT '0' NOT NULL,
	filterprice tinytext,
	priceid text,
	uiddetails tinytext,

	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_abbooking_price'
#
CREATE TABLE tx_abbooking_price (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title tinytext,
	currency tinytext,
	adult1 tinytext,
	adult2 tinytext,
	adult3 tinytext,
	adult4 tinytext,
	adultX tinytext,
	child tinytext,
	teen tinytext,
	extraComponent1 tinytext,
	extraComponent2 tinytext,
	discount double(11,2) DEFAULT '0.00' NOT NULL,
	discountPeriod int(11) DEFAULT '0' NOT NULL,
	singleComponent1 tinytext,
	singleComponent2 tinytext,

	minimumStay tinytext,
	blockDaysAfterBooking int(11) DEFAULT '0' NOT NULL,
	checkInWeekdays tinytext,

	seasonid int(11) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_abbooking_seasons_priceid_mm'
# ## WOP:[tables][4][fields][1][conf_relations_mm]
#
CREATE TABLE tx_abbooking_seasons_priceid_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(30) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);



#
# Table structure for table 'tx_abbooking_seasons'
#
CREATE TABLE tx_abbooking_seasons (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title tinytext,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
