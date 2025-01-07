CREATE TABLE pages (
    tx_academiccontacts4pages_contacts int(11) DEFAULT NULL
);

CREATE TABLE tx_academicpersons_domain_model_contract (
    tx_academiccontacts4pages_contacts int(11) DEFAULT NULL
);

CREATE TABLE tx_academiccontacts4pages_domain_model_role (
    name varchar(255) DEFAULT '' NOT NULL,
    description text,
    contacts int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academiccontacts4pages_domain_model_contact (
    page int(11) unsigned DEFAULT '0' NOT NULL,
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    role int(11) unsigned DEFAULT '0' NOT NULL,
);
