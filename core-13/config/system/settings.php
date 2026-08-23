<?php
return [
    'BE' => [
        'debug' => true,
        'installToolPassword' => '$argon2i$v=19$m=65536,t=16,p=1$ZWROczVUeWxjbURxZnNYTQ$sAHxaeg2kwN3dC9/sfJ/ElRuouZws3eVFcPgelZCkOY',
        'passwordHashing' => [
            'className' => 'TYPO3\\CMS\\Core\\Crypto\\PasswordHashing\\Argon2iPasswordHash',
            'options' => [],
        ],
    ],
    'DB' => [
        'Connections' => [
            'Default' => [
                'charset' => 'utf8',
                'driver' => 'pdo_sqlite',
                // Advisory only: "additional.php" recomputes this from __DIR__ on every
                // request, so the instance works inside DDEV and on a host stack alike.
                'path' => __DIR__ . '/../../var/sqlite/core-13.sqlite',
            ],
        ],
    ],
    'EXTENSIONS' => [
        'academic_persons' => [
            'demand' => [
                'allowedGroupByValues' => 'firstNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.first_name,lastNameAlpha=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.groupBy.items.last_name',
                'allowedSortByValues' => 'firstName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.first_name,lastName=LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:flexform.el.sortBy.items.last_name',
            ],
            'profile' => [
                'autoCreateProfiles' => '0',
                'createProfileForUserGroups' => '',
            ],
            'types' => [
                'emailAddressTypes' => 'private=Private,business=Business',
                'phoneNumberTypes' => 'private=Private,business=Business,mobile=Mobile',
                'physicalAddressTypes' => 'private=Private,business=Business',
            ],
        ],
        'academic_persons_edit' => [
            'profile' => [
                'allowedLanguages' => '',
                'autoCreateProfiles' => '0',
                'createProfileForUserGroups' => '',
            ],
        ],
        'backend' => [
            'backendFavicon' => '',
            'backendLogo' => '',
            'loginBackgroundImage' => '',
            'loginFootnote' => '',
            'loginHighlightColor' => '',
            'loginLogo' => '',
            'loginLogoAlt' => '',
        ],
        'bootstrap_package' => [
            'disableCssProcessing' => '0',
            'disableGoogleFontCaching' => '0',
            'disablePageTsBackendLayouts' => '0',
            'disablePageTsContentElements' => '0',
            'disablePageTsRTE' => '0',
            'disablePageTsTCADefaults' => '0',
            'disablePageTsTCEFORM' => '0',
            'disablePageTsTCEMAIN' => '0',
        ],
        'extensionmanager' => [
            'automaticInstallation' => '1',
            'offlineMode' => '0',
        ],
        'indexed_search' => [
            'catdoc' => '/usr/bin/',
            'deleteFromIndexAfterEditing' => '1',
            'disableFrontendIndexing' => '0',
            'flagBitMask' => '192',
            'fullTextDataLength' => '0',
            'ignoreExtensions' => '',
            'indexExternalURLs' => '0',
            'maxExternalFiles' => '5',
            'minAge' => '24',
            'pdf_mode' => '20',
            'pdftools' => '/usr/bin/',
            'ppthtml' => '/usr/bin/',
            'unrtf' => '/usr/bin/',
            'unzip' => '/usr/bin/',
            'useMysqlFulltext' => '0',
            'xlhtml' => '/usr/bin/',
        ],
        'redirects' => [
            'showCheckIntegrityInfoInReports' => '1',
            'showCheckIntegrityInfoInReportsSeconds' => '86400',
        ],
        'scheduler' => [
            'maxLifetime' => '1440',
        ],
        'styleguide' => [
            'boolean_1' => '0',
            'boolean_2' => '1',
            'boolean_3' => '',
            'boolean_4' => '0',
            'color_1' => 'black',
            'color_2' => '#000000',
            'color_3' => '000000',
            'color_4' => '',
            'compat_default_1' => 'value',
            'compat_default_2' => '',
            'compat_input_1' => 'value',
            'compat_input_2' => '',
            'int_1' => '1',
            'int_2' => '',
            'int_3' => '-100',
            'int_4' => '2',
            'intplus_1' => '1',
            'intplus_2' => '',
            'intplus_3' => '2',
            'nested' => [
                'input_1' => 'aDefault',
                'input_2' => '',
            ],
            'offset_1' => 'x,y',
            'offset_2' => 'x',
            'offset_3' => ',y',
            'offset_4' => '',
            'options_1' => 'default',
            'options_2' => 'option_2',
            'options_3' => '',
            'predefined' => [
                'boolean_1' => '1',
                'int_1' => '42',
            ],
            'small_1' => 'value',
            'small_2' => '',
            'string_1' => 'value',
            'string_2' => '',
            'user_1' => '0',
            'wrap_1' => 'value',
            'wrap_2' => '',
            'zeroorder_input_1' => 'value',
            'zeroorder_input_2' => '',
            'zeroorder_input_3' => '',
        ],
    ],
    'FE' => [
        'cacheHash' => [
            'enforceValidation' => true,
        ],
        'debug' => true,
        'disableNoCacheParameter' => true,
        'passwordHashing' => [
            'className' => 'TYPO3\\CMS\\Core\\Crypto\\PasswordHashing\\Argon2iPasswordHash',
            'options' => [],
        ],
    ],
    'GFX' => [
        'processor' => 'ImageMagick',
        'processor_effects' => true,
        'processor_enabled' => true,
        'processor_path' => '/usr/bin/',
    ],
    'MAIL' => [
        'transport' => 'sendmail',
        'transport_sendmail_command' => '/usr/sbin/sendmail -t -i',
        'transport_smtp_encrypt' => '',
        'transport_smtp_password' => '',
        'transport_smtp_server' => '',
        'transport_smtp_username' => '',
    ],
    'SYS' => [
        'UTF8filesystem' => true,
        // Pinned, and deliberately not the timezone of whoever runs this instance.
        // TYPO3 stores a date field as a timestamp it derives with the server
        // timezone, so the same seed produces different integers in Berlin and in
        // UTC - and the committed "sqlite-databases/core-NN.sqlite" snapshot has to
        // be reproducible by anyone, not only by somebody in the same timezone as
        // the person who last regenerated it. Left empty, TYPO3 falls back to the
        // server timezone (Bootstrap::setDefaultTimezone()).
        'phpTimeZone' => 'UTC',
        'caching' => [
            'cacheConfigurations' => [
                'hash' => [
                    'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
                ],
                'pages' => [
                    'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
                    'options' => [
                        'compression' => true,
                    ],
                ],
                'rootline' => [
                    'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
                    'options' => [
                        'compression' => true,
                    ],
                ],
            ],
        ],
        'devIPmask' => '*',
        'displayErrors' => 1,
        'encryptionKey' => 'a10587afa729dad2a2ab8cb2b5e9460f3d5e371e6a0b4e6df4ad86118a5f0242804a1d1b127380e778abc1e64bb76af9',
        'exceptionalErrors' => 12290,
        'features' => [
            'security.system.enforceAllowedFileExtensions' => true,
        ],
        'sitename' => '[ACE] Academic Extensions Instance Core13',
        'systemMaintainers' => [
            1,
        ],
    ],
];
