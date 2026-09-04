<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Profile;

use FGTCLB\AcademicPersons\Types\PhoneNumberTypes;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * @internal
 */
final readonly class FrontendUserPhoneNumberTypeResolver
{
    private const DEFAULT_TYPE = 'business';

    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private PhoneNumberTypes $phoneNumberTypes,
    ) {}

    public function getTelephoneNumberType(): string
    {
        return $this->resolve('profile/fe_users/telephoneNumberType');
    }

    public function getFaxNumberType(): string
    {
        return $this->resolve('profile/fe_users/faxNumberType');
    }

    public function isSelectable(string $type): bool
    {
        return $type === '' || array_key_exists($type, $this->phoneNumberTypes->getAll());
    }

    private function resolve(string $path): string
    {
        try {
            $configuredType = (string)$this->extensionConfiguration->get('academic_persons', $path);
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            $configuredType = self::DEFAULT_TYPE;
        }
        return $this->isSelectable($configuredType) ? $configuredType : '';
    }
}
