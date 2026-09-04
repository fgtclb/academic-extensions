#
# The integer year columns academic_persons 2.x stored on profile information
# records. They left the extension's ext_tables.sql with 3.0.0; this fixture
# re-declares them so the schema of a test instance looks like an updated
# installation, where the schema analyzer keeps them as "unused" columns.
#
CREATE TABLE tx_academicpersons_domain_model_profile_information (
    year int(11) DEFAULT NULL,
    year_start int(11) DEFAULT NULL,
    year_end int(11) DEFAULT NULL
);
