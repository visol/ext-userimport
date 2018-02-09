#
# Table structure for table 'tx_userimport_domain_model_importjob'
#
CREATE TABLE tx_userimport_domain_model_importjob (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted smallint(5) unsigned DEFAULT '0' NOT NULL,
	hidden smallint(5) unsigned DEFAULT '0' NOT NULL,

	file int(11) unsigned DEFAULT '0',

	PRIMARY KEY (uid),
	KEY parent (pid),

);
