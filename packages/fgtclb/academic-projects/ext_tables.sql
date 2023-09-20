CREATE TABLE pages
(
	tx_academicprojects_project_title varchar(255) NOT NULL DEFAULT '',
	tx_academicprojects_short_description text,
	tx_academicprojects_start_date int(11) DEFAULT NULL,
	tx_academicprojects_end_date int(11) DEFAULT NULL,
	tx_academicprojects_budget decimal(10,2) DEFAULT NULL,
	tx_academicprojects_funders text
);
