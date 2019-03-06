CREATE TABLE tx_transactor_transactions (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	reference varchar(255) DEFAULT '0' NOT NULL,
	gatewayid varchar(255) DEFAULT '0' NOT NULL,
	orderuid int(11) DEFAULT '0' NOT NULL,
	currency varchar(3) DEFAULT '' NOT NULL,
	amount decimal(19,2) DEFAULT '0.00' NOT NULL,
	state int(3) unsigned DEFAULT '0' NOT NULL,
	state_time int(11) unsigned DEFAULT '0' NOT NULL,
	message mediumtext NOT NULL,
	ext_key varchar(100) DEFAULT '' NOT NULL,
	paymethod_key varchar(100) DEFAULT '' NOT NULL,
	paymethod_method varchar(100) DEFAULT '' NOT NULL,
	config text,
	config_ext text,
	user text,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY reference (reference),
	KEY orderuid (orderuid)
);

