CREATE TABLE tx_academicstudyplan_domain_model_category (
		label varchar(255) NOT NULL DEFAULT '',
		colour varchar(7) NOT NULL DEFAULT ''
);

CREATE TABLE tx_academicstudyplan_domain_model_semester (
		label varchar(255) NOT NULL DEFAULT '',
		note varchar(255) NOT NULL DEFAULT '',
		credit_points int(11) NOT NULL DEFAULT '0',
		modules int(11) unsigned NOT NULL DEFAULT '0',
		content_element int(11) unsigned NOT NULL DEFAULT '0'
);

CREATE TABLE tx_academicstudyplan_domain_model_module (
		label varchar(255) NOT NULL DEFAULT '',
		note varchar(255) NOT NULL DEFAULT '',
		credit_points int(11) NOT NULL DEFAULT '0',
		description text,
		audio_file int(11) unsigned NOT NULL DEFAULT '0',
		categories int(11) unsigned NOT NULL DEFAULT '0',
		semester int(11) unsigned NOT NULL DEFAULT '0'
);

CREATE TABLE tx_academicstudyplan_module_category_mm (
		uid_local int(11) unsigned DEFAULT '0' NOT NULL,
		uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
		sorting int(11) unsigned DEFAULT '0' NOT NULL,
		sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

		PRIMARY KEY (uid_local,uid_foreign),
		KEY uid_local (uid_local),
		KEY uid_foreign (uid_foreign)
);

CREATE TABLE tt_content (
		tx_academicstudyplan_footer_note text,
		tx_academicstudyplan_semesters int(11) unsigned NOT NULL DEFAULT '0'
);
