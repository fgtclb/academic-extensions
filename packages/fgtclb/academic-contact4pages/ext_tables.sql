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

    -- The sort order within the contract and within the contacts role. The page
    -- owns the shared "sorting" column; a "foreign_sortby" column is not derived
    -- from TCA, so both other relations declare one of their own here.
    contract_sorting int(11) unsigned DEFAULT '0' NOT NULL,
    role_sorting int(11) unsigned DEFAULT '0' NOT NULL,

    -- Signed, due to `-1` option
    email_address int(11) DEFAULT '0' NOT NULL,
    phone_number int(11) DEFAULT '0' NOT NULL,
    physical_address int(11) DEFAULT '0' NOT NULL,
);
