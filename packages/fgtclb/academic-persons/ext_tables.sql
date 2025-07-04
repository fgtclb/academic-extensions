CREATE TABLE fe_users (
    tx_academicpersons_profiles int(11) unsigned DEFAULT 0 NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_address (
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    type varchar(40) DEFAULT '' NOT NULL,
    street varchar(255) DEFAULT '' NOT NULL,
    street_number varchar(255) DEFAULT '' NOT NULL,
    additional varchar(255) DEFAULT '' NOT NULL,
    zip varchar(255) DEFAULT '' NOT NULL,
    city varchar(255) DEFAULT '' NOT NULL,
    state varchar(255) DEFAULT '' NOT NULL,
    country varchar(255) DEFAULT '' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_contract (
    profile int(11) unsigned DEFAULT '0' NOT NULL,

    organisational_unit int(11) unsigned DEFAULT NULL,
    function_type int(11) unsigned DEFAULT NULL,
    valid_from int(11) DEFAULT NULL,
    valid_to int(11) DEFAULT NULL,

    employee_type int(11) unsigned DEFAULT '0' NOT NULL,
    position varchar(255) DEFAULT '' NOT NULL,
    location int(11) unsigned DEFAULT NULL,

    room varchar(255) DEFAULT '' NOT NULL,
    office_hours text,

    physical_addresses int(11) unsigned DEFAULT '0' NOT NULL,
    phone_numbers int(11) unsigned DEFAULT '0' NOT NULL,
    email_addresses int(11) unsigned DEFAULT '0' NOT NULL,

    publish smallint(5) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_email (
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    type varchar(40) DEFAULT '' NOT NULL,

    email varchar(255) DEFAULT '' NOT NULL,

    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_function_type (
    function_name varchar(255) DEFAULT '' NOT NULL,
    function_name_male varchar(255) DEFAULT '' NOT NULL,
    function_name_female varchar(255) DEFAULT '' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_location (
    title varchar(255) DEFAULT '' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_organisational_unit (
    parent int(11) unsigned DEFAULT '0' NOT NULL,
    unit_name varchar(255) DEFAULT '' NOT NULL,
    unique_name varchar(255) DEFAULT '' NOT NULL,
    display_text text,
    long_text text,
    contracts int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_phone_number (
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    type varchar(40) DEFAULT '' NOT NULL,
    phone_number varchar(255) DEFAULT '' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);


CREATE TABLE tx_academicpersons_domain_model_profile_information (
    profile int(11) unsigned DEFAULT '0' NOT NULL,

    type varchar(40) DEFAULT '' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    bodytext text,
    link varchar(2048) DEFAULT '' NOT NULL,
    year int(11)  DEFAULT NULL,
    year_start int(11)  DEFAULT NULL,
    year_end int(11)  DEFAULT NULL,

    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_profile (
    gender varchar(40) DEFAULT '' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    first_name varchar(255) DEFAULT '' NOT NULL,
    first_name_alpha varchar(1) DEFAULT '' NOT NULL,
    middle_name varchar(255) DEFAULT '' NOT NULL,
    last_name varchar(255) DEFAULT '' NOT NULL,
    last_name_alpha varchar(1) DEFAULT '' NOT NULL,
    image int(11) unsigned DEFAULT '0' NOT NULL,
    slug varchar(2048) DEFAULT '' NOT NULL,

    publications_link varchar(2048) DEFAULT '' NOT NULL,
    publications_link_title varchar(255) DEFAULT '' NOT NULL,
    website varchar(2048) DEFAULT '' NOT NULL,
    website_title varchar(255) DEFAULT '' NOT NULL,

    core_competences text,
    miscellaneous text,
    supervised_thesis text,
    supervised_doctoral_thesis text,
    teaching_area text,

    contracts int(11) unsigned DEFAULT '0' NOT NULL,
    cooperation int(11) unsigned DEFAULT '0' NOT NULL,
    lectures int(11) unsigned DEFAULT '0' NOT NULL,
    memberships int(11) unsigned DEFAULT '0' NOT NULL,
    press_media int(11) unsigned DEFAULT '0' NOT NULL,
    publications int(11) unsigned DEFAULT '0' NOT NULL,
    scientific_research int(11) unsigned DEFAULT '0' NOT NULL,
    vita int(11) unsigned DEFAULT '0' NOT NULL,

    frontend_users int(11) unsigned DEFAULT 0 NOT NULL,
);

CREATE TABLE tx_academicpersons_contract_address_mm (
    fieldname varchar(255) DEFAULT '' NOT NULL,
);
